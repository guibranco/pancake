<?php

namespace GuiBranco\Pancake\Queue;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Throwable;

/**
 * Class Queue
 *
 * A thin wrapper around php-amqplib for publishing to, and consuming from, one or more
 * independent RabbitMQ servers.
 *
 * - {@see publish()} picks one server at random from the configured list.
 * - {@see consume()} loops through every configured server in turn.
 * - Queues can be declared plain, or paired with a dead-letter companion queue that bounces
 *   messages back after a fixed TTL (see {@see declareQueueWithDLX()}); both {@see publish()} and
 *   {@see consume()} accept an optional flag to choose between the two.
 * - {@see retry()} implements an exponential-backoff retry pattern on top of that: each retry
 *   attempt is published to its own per-attempt queue with a growing TTL that dead-letters back
 *   to the original queue, up to {@see QueueOptions::$maxRetries} attempts, after which the
 *   message is moved to a terminal dead queue instead.
 *
 * @package GuiBranco\Pancake\Queue
 */
class Queue implements IQueue
{
    private const HEADER_RETRY_COUNT = 'x-retry-count';

    private array $connectionStrings;
    private QueueOptions $options;

    /**
     * @param string[] $connectionStrings One or more AMQP connection strings
     *                                    (e.g. "amqp://user:pass@host:5672/vhost"), each treated as an
     *                                    independent server.
     * @param QueueOptions $options Tunable timeouts and retry settings.
     *
     * @throws QueueException If $connectionStrings is empty.
     */
    public function __construct(array $connectionStrings, QueueOptions $options = new QueueOptions())
    {
        if (empty($connectionStrings)) {
            throw new QueueException('RabbitMQ connection strings not found', 'connection');
        }

        $this->connectionStrings = $connectionStrings;
        $this->options = $options;
    }

    public function publish(string $queueName, string $message, bool $declareDlx = true): void
    {
        $connection = $this->getConnection($this->pickRandomServer());
        $channel = $connection->channel();

        $this->declareQueue($channel, $queueName, $declareDlx);
        $this->publishToChannel($channel, $queueName, $message);

        $channel->close();
        $connection->close();
    }

    public function consume(
        int $timeout,
        string $queueName,
        callable $callback,
        bool $resetTimeoutOnReceive = false,
        int $qos = 10,
        bool $declareDlx = true
    ): void {
        foreach ($this->getServers() as $server) {
            $this->consumeFromServer($server, $timeout, $queueName, $callback, $resetTimeoutOnReceive, $qos, $declareDlx);
        }
    }

    public function retry(string $queueName, AMQPMessage $message): bool
    {
        $attempt = $this->getRetryCount($message) + 1;

        if ($attempt > $this->options->maxRetries) {
            $this->publishToDeadQueue($queueName, $message);
            return false;
        }

        $delayMs = (int) round($this->options->initialRetryDelayMs * ($this->options->retryMultiplier ** ($attempt - 1)));
        $retryQueueName = "{$queueName}{$this->options->retryQueueSuffix}-{$attempt}";

        $connection = $this->getConnection($this->pickRandomServer());
        $channel = $connection->channel();

        $this->declareRetryLevelQueue($channel, $retryQueueName, $queueName, $delayMs);
        $this->publishToChannel($channel, $retryQueueName, $message->getBody(), $attempt);

        $channel->close();
        $connection->close();

        return true;
    }

    public function getRetryCount(AMQPMessage $message): int
    {
        if (!$message->has('application_headers')) {
            return 0;
        }

        $headers = $message->get('application_headers')->getNativeData();

        return (int) ($headers[self::HEADER_RETRY_COUNT] ?? 0);
    }

    private function consumeFromServer(
        array $server,
        int $timeout,
        string $queueName,
        callable $callback,
        bool $resetTimeoutOnReceive,
        int $qos,
        bool $declareDlx
    ): void {
        $startTime = time();
        $fn = function (AMQPMessage $msg) use ($callback, $timeout, &$startTime, $resetTimeoutOnReceive) {
            if ($resetTimeoutOnReceive) {
                $startTime = time();
            }
            $callback($timeout, $startTime, $msg);
        };

        $connection = $this->getConnection($server);
        $channel = $connection->channel();

        $this->declareQueue($channel, $queueName, $declareDlx);
        $channel->basic_qos(null, $qos, null);
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

    private function declareQueue(AMQPChannel $channel, string $queueName, bool $declareDlx): void
    {
        if ($declareDlx) {
            $this->declareQueueWithDLX($channel, $queueName);
        } else {
            $this->declareQueueWithoutDLX($channel, $queueName);
        }
    }

    private function declareQueueWithoutDLX(AMQPChannel $channel, string $queueName): void
    {
        $channel->queue_declare($queueName, false, true, false, false);
    }

    private function declareQueueWithDLX(AMQPChannel $channel, string $queueName): void
    {
        $channel->queue_declare(
            $queueName,
            false,
            true,
            false,
            false,
            false,
            new AMQPTable([
                'x-dead-letter-exchange' => '',
                'x-dead-letter-routing-key' => $queueName . '-retry',
            ])
        );
        $channel->queue_declare(
            $queueName . '-retry',
            false,
            true,
            false,
            false,
            false,
            new AMQPTable([
                'x-dead-letter-exchange' => '',
                'x-dead-letter-routing-key' => $queueName,
                'x-message-ttl' => $this->options->dlxRetryTtlMs,
            ])
        );
    }

    private function declareRetryLevelQueue(
        AMQPChannel $channel,
        string $retryQueueName,
        string $targetQueueName,
        int $ttlMs
    ): void {
        $channel->queue_declare(
            $retryQueueName,
            false,
            true,
            false,
            false,
            false,
            new AMQPTable([
                'x-dead-letter-exchange' => '',
                'x-dead-letter-routing-key' => $targetQueueName,
                'x-message-ttl' => $ttlMs,
            ])
        );
    }

    private function publishToDeadQueue(string $queueName, AMQPMessage $message): void
    {
        $deadQueueName = "{$queueName}{$this->options->deadQueueSuffix}";

        $connection = $this->getConnection($this->pickRandomServer());
        $channel = $connection->channel();

        $this->declareQueueWithoutDLX($channel, $deadQueueName);
        $this->publishToChannel($channel, $deadQueueName, $message->getBody());

        $channel->close();
        $connection->close();
    }

    private function publishToChannel(AMQPChannel $channel, string $queueName, string $body, ?int $retryCount = null): void
    {
        $msgOptions = [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ];

        if ($retryCount !== null) {
            $msgOptions['application_headers'] = new AMQPTable([
                self::HEADER_RETRY_COUNT => $retryCount,
            ]);
        }

        $channel->basic_publish(new AMQPMessage($body, $msgOptions), '', $queueName);
    }

    private function pickRandomServer(): array
    {
        $servers = $this->getServers();

        return $servers[array_rand($servers)];
    }

    private function getServers(): array
    {
        $servers = [];
        foreach ($this->connectionStrings as $connectionString) {
            $url = parse_url($connectionString);
            $servers[] = [
                'host' => $url['host'],
                'port' => $url['port'] ?? 5672,
                'user' => $url['user'],
                'password' => $url['pass'],
                'vhost' => ($url['path'] ?? '/') === '/' ? '/' : substr($url['path'], 1),
            ];
        }

        return $servers;
    }

    private function getConnection(array $server): AMQPStreamConnection
    {
        try {
            return new AMQPStreamConnection(
                $server['host'],
                $server['port'],
                $server['user'],
                $server['password'],
                $server['vhost'],
                false,
                'AMQPLAIN',
                null,
                'en_US',
                $this->options->connectionTimeout,
                $this->options->readWriteTimeout
            );
        } catch (Throwable $e) {
            throw new QueueException(
                "Failed to connect to RabbitMQ server at {$server['host']}:{$server['port']}: {$e->getMessage()}",
                'connection',
                0,
                $e
            );
        }
    }
}
