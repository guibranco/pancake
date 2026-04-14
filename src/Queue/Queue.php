<?php

namespace GuiBranco\Pancake\Queue;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Class Queue
 *
 * AMQP wrapper providing message publishing, consuming, and an
 * exponential-backoff retry strategy via dead-letter topology.
 *
 * Publishing picks a random server from the pool for load balancing.
 * Consuming loops through every configured server so that messages on any
 * node are eventually processed.
 *
 * Retry topology (created when $withDlx = true):
 *   - <queueName>           – main queue (durable)
 *   - <queueName>-retry-N   – one per delay level; TTL = retryDelaysMs[N-1];
 *                             DLX routes expired messages back to the main queue
 *   - <queueName>-failed    – messages that exhausted all retry attempts
 *
 * @package GuiBranco\Pancake\Queue
 */
class Queue implements IQueue
{
    /** @var int Default AMQP port. */
    private const DEFAULT_PORT = 5672;

    /** @var int Default QoS prefetch count. */
    private const DEFAULT_QOS_COUNT = 10;

    /** @var float Timeout in seconds used for single-server connections. */
    private const CONNECTION_TIMEOUT = 10.0;

    /**
     * Custom message header used to track the number of retry attempts.
     * This header is incremented each time a message is routed to a retry queue.
     *
     * @var string
     */
    private const RETRY_COUNT_HEADER = 'x-pancake-retry-count';

    /**
     * Default retry delays in milliseconds: 1 min, 5 min, 30 min, 1 h.
     *
     * @var int[]
     */
    private const DEFAULT_RETRY_DELAYS_MS = [60_000, 300_000, 1_800_000, 3_600_000];

    /** @var string[] */
    private $connectionStrings;

    /** @var int[] */
    private $retryDelaysMs;

    /**
     * Queue constructor.
     *
     * @param string[] $connectionStrings One or more AMQP URLs,
     *                                   e.g. ["amqp://user:pass@host:5672/vhost"].
     * @param int[]    $retryDelaysMs     Millisecond delays for each retry level.
     *                                   Defaults to [60 000, 300 000, 1 800 000, 3 600 000].
     *
     * @throws QueueException When no connection string is provided.
     */
    public function __construct($connectionStrings, $retryDelaysMs = self::DEFAULT_RETRY_DELAYS_MS)
    {
        if (empty($connectionStrings)) {
            throw new QueueException('At least one connection string must be provided.', 'init');
        }

        $this->connectionStrings = $connectionStrings;
        $this->retryDelaysMs = $retryDelaysMs;
    }

    /**
     * Parses an AMQP connection URL into a server descriptor array.
     *
     * @param string $connectionString AMQP URL (amqp://user:pass@host:port/vhost).
     *
     * @return array{host: string, port: int, user: string, password: string, vhost: string}
     *
     * @throws QueueException When the URL cannot be parsed or has no host.
     */
    private function parseServer($connectionString)
    {
        $url = parse_url($connectionString);

        if ($url === false || empty($url['host'])) {
            throw new QueueException(
                "Invalid AMQP connection string: {$connectionString}",
                'parse'
            );
        }

        return [
            'host'     => $url['host'],
            'port'     => $url['port'] ?? self::DEFAULT_PORT,
            'user'     => $url['user'] ?? 'guest',
            'password' => $url['pass'] ?? 'guest',
            'vhost'    => (empty($url['path']) || $url['path'] === '/')
                            ? '/'
                            : ltrim($url['path'], '/'),
        ];
    }

    /**
     * Returns all servers parsed from the configured connection strings.
     *
     * @return array<int, array{host: string, port: int, user: string, password: string, vhost: string}>
     *
     * @throws QueueException On invalid connection string.
     */
    private function getServers()
    {
        return array_map(
            function ($cs) {
                return $this->parseServer($cs);
            },
            $this->connectionStrings
        );
    }

    /**
     * Creates an AMQP connection to the given list of servers.
     *
     * When a single server is provided, explicit timeouts are applied.
     * When multiple servers are provided, the library's built-in failover is used.
     *
     * @param array $servers One or more parsed server descriptor arrays.
     *
     * @return AMQPStreamConnection
     *
     * @throws QueueException On connection failure.
     */
    private function createConnection($servers)
    {
        $options = count($servers) === 1
            ? [
                'connection_timeout'  => self::CONNECTION_TIMEOUT,
                'read_write_timeout'  => self::CONNECTION_TIMEOUT,
              ]
            : [];

        try {
            return AMQPStreamConnection::create_connection($servers, $options);
        } catch (\Exception $e) {
            throw new QueueException(
                'Failed to connect to AMQP server: ' . $e->getMessage(),
                'connect',
                0,
                $e
            );
        }
    }

    /**
     * Creates a connection for publishing.
     *
     * The server list is shuffled before connecting so that publishing load is
     * distributed across the pool at random.
     *
     * @return AMQPStreamConnection
     *
     * @throws QueueException On connection failure.
     */
    private function getPublishConnection()
    {
        $servers = $this->getServers();
        shuffle($servers);
        return $this->createConnection($servers);
    }

    /**
     * Declares a plain durable queue without any dead-letter exchange.
     *
     * @param AMQPChannel $channel   Active AMQP channel.
     * @param string      $queueName Queue name.
     */
    private function declareQueueWithoutDLX($channel, $queueName)
    {
        $channel->queue_declare($queueName, false, true, false, false);
    }

    /**
     * Declares the main queue, one retry queue per configured delay level,
     * and a final failed queue.
     *
     * Retry queues use a message TTL so that expired messages are routed back
     * to the main queue via the default exchange. The consumer is responsible
     * for routing failed messages to the correct retry level by publishing a
     * new message with an incremented {@see Queue::RETRY_COUNT_HEADER} header.
     *
     * @param AMQPChannel $channel   Active AMQP channel.
     * @param string      $queueName Base queue name.
     */
    private function declareQueueWithDLX($channel, $queueName)
    {
        // Main queue – no built-in DLX; retry routing is managed by the consumer.
        $channel->queue_declare($queueName, false, true, false, false);

        // Retry queues: each routes expired messages back to the main queue.
        foreach ($this->retryDelaysMs as $index => $delayMs) {
            $channel->queue_declare(
                $queueName . '-retry-' . ($index + 1),
                false,
                true,
                false,
                false,
                false,
                new AMQPTable([
                    'x-dead-letter-exchange'    => '',
                    'x-dead-letter-routing-key' => $queueName,
                    'x-message-ttl'             => $delayMs,
                ])
            );
        }

        // Sink queue for messages that exhausted every retry level.
        $channel->queue_declare($queueName . '-failed', false, true, false, false);
    }

    /**
     * Reads the custom retry-count header from a message.
     *
     * @param AMQPMessage $msg Incoming message.
     *
     * @return int Current retry count (0 for first-time delivery).
     */
    private function getRetryCount($msg)
    {
        try {
            $headers = $msg->get('application_headers');

            if (!$headers instanceof AMQPTable) {
                return 0;
            }

            $data = $headers->getNativeData();
            return (int)($data[self::RETRY_COUNT_HEADER] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Handles a failed message by routing it to the appropriate retry queue or
     * the failed queue when all retry levels are exhausted.
     *
     * The original message is acknowledged before the new message is published
     * so that the broker does not redeliver it.
     *
     * @param AMQPChannel $channel   Active AMQP channel.
     * @param AMQPMessage $msg       The message that failed processing.
     * @param string      $queueName Base queue name (used to derive retry/failed queue names).
     */
    private function handleRetry($channel, $msg, $queueName)
    {
        $retryCount = $this->getRetryCount($msg);
        $maxRetries = count($this->retryDelaysMs);

        $msg->ack();

        $targetQueue = $retryCount < $maxRetries
            ? $queueName . '-retry-' . ($retryCount + 1)
            : $queueName . '-failed';

        $newMsg = new AMQPMessage(
            $msg->body,
            [
                'content_type'        => 'application/json',
                'delivery_mode'       => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'application_headers' => new AMQPTable([
                    self::RETRY_COUNT_HEADER => $retryCount + 1,
                ]),
            ]
        );

        $channel->basic_publish($newMsg, '', $targetQueue);
    }

    /**
     * Publishes a message to the specified queue.
     *
     * A random server is selected from the pool on each call for load balancing.
     * The queue (and, when $withDlx is true, its retry/failed siblings) is declared
     * before publishing so the topology is always consistent.
     *
     * @param string $queueName Target queue name.
     * @param string $message   Message body (typically JSON-encoded).
     * @param bool   $withDlx   When true, retry and failed queues are also declared.
     *
     * @throws QueueException On connection or publish failure.
     */
    public function publish($queueName, $message, $withDlx = true)
    {
        $connection = $this->getPublishConnection();
        $channel = $connection->channel();

        if ($withDlx) {
            $this->declareQueueWithDLX($channel, $queueName);
        } else {
            $this->declareQueueWithoutDLX($channel, $queueName);
        }

        $msg = new AMQPMessage($message, [
            'content_type'  => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        $channel->basic_publish($msg, '', $queueName);

        $channel->close();
        $connection->close();
    }

    /**
     * Consumes messages from the specified queue until the timeout expires.
     *
     * Every configured server is tried in sequence. If a server is unreachable,
     * the next one is attempted. The consumer loop waits for messages and checks
     * the timeout after each wait cycle.
     *
     * The callback receives the raw AMQPMessage. Returning <code>false</code>
     * or throwing any exception triggers {@see Queue::handleRetry()}; any other
     * return value (including <code>null</code>) causes the message to be
     * acknowledged.
     *
     * @param int      $timeout               Seconds before the consumer loop exits.
     * @param string   $queueName             Queue to consume from.
     * @param callable $callback              Message handler; return false to nack/retry.
     * @param bool     $withDlx               When true, retry and failed queues are declared.
     * @param bool     $resetTimeoutOnReceive Reset the timeout clock on each received message.
     * @param int      $qosCount              QoS prefetch count (unacknowledged message limit).
     *
     * @throws QueueException On connection or consume failure.
     */
    public function consume(
        $timeout,
        $queueName,
        $callback,
        $withDlx = true,
        $resetTimeoutOnReceive = false,
        $qosCount = self::DEFAULT_QOS_COUNT
    ) {
        $servers = $this->getServers();

        foreach ($servers as $server) {
            try {
                $connection = $this->createConnection([$server]);
            } catch (QueueException $e) {
                continue; // Server unreachable – try the next one.
            }

            $channel = $connection->channel();

            if ($withDlx) {
                $this->declareQueueWithDLX($channel, $queueName);
            } else {
                $this->declareQueueWithoutDLX($channel, $queueName);
            }

            $startTime = time();

            $fn = function ($msg) use (
                $callback,
                $timeout,
                &$startTime,
                $resetTimeoutOnReceive,
                $channel,
                $queueName
            ) {
                if ($resetTimeoutOnReceive) {
                    $startTime = time();
                }

                try {
                    $result = $callback($msg);

                    if ($result === false) {
                        $this->handleRetry($channel, $msg, $queueName);
                    } else {
                        $msg->ack();
                    }
                } catch (\Throwable $e) {
                    $this->handleRetry($channel, $msg, $queueName);
                }
            };

            $channel->basic_qos(null, $qosCount, null);
            $channel->basic_consume($queueName, '', false, false, false, false, $fn);

            while ($channel->is_consuming()) {
                $channel->wait(null, true);

                if ($startTime + $timeout < time()) {
                    break;
                }
            }

            $channel->close();
            $connection->close();
        }
    }
}
