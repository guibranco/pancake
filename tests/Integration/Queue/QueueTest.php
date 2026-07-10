<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Integration\Queue;

use GuiBranco\Pancake\Queue\Queue;
use GuiBranco\Pancake\Queue\QueueOptions;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversClass(Queue::class)]
class QueueTest extends TestCase
{
    private static string $host;
    private static int $port;
    private static string $user;
    private static string $pass;
    private static string $vhost;
    private static string $connectionString;

    /** @var string[] Queue names created during a test, deleted again in tearDown(). */
    private array $queuesToDelete = [];

    private static function loadConfig(): void
    {
        self::$host = getenv('RABBITMQ_HOST') ?: '127.0.0.1';
        self::$port = (int) (getenv('RABBITMQ_PORT') ?: 5672);
        self::$user = getenv('RABBITMQ_USER') ?: 'guest';
        self::$pass = getenv('RABBITMQ_PASS') ?: 'guest';
        self::$vhost = getenv('RABBITMQ_VHOST') ?: '/';
        self::$connectionString = sprintf(
            'amqp://%s:%s@%s:%d/%s',
            self::$user,
            self::$pass,
            self::$host,
            self::$port,
            self::$vhost === '/' ? '' : ltrim(self::$vhost, '/')
        );
    }

    public static function setUpBeforeClass(): void
    {
        self::loadConfig();
    }

    protected function tearDown(): void
    {
        if (empty($this->queuesToDelete)) {
            return;
        }

        $connection = $this->rawConnection();

        foreach ($this->queuesToDelete as $queueName) {
            try {
                // A fresh channel per queue: a channel-level error (e.g. queue not
                // found) closes the channel, so it can't be reused for the next delete.
                $channel = $connection->channel();
                $channel->queue_delete($queueName);
                $channel->close();
            } catch (Throwable) {
                // Queue may not exist (e.g. never declared); nothing to clean up.
            }
        }

        $connection->close();
        $this->queuesToDelete = [];
    }

    private function rawConnection(): AMQPStreamConnection
    {
        return new AMQPStreamConnection(self::$host, self::$port, self::$user, self::$pass, self::$vhost);
    }

    private function uniqueQueueName(string $suffix = ''): string
    {
        return 'pancake-test-' . bin2hex(random_bytes(6)) . $suffix;
    }

    private function trackQueue(string $queueName, bool $withDlxCompanions = false): string
    {
        $this->queuesToDelete[] = $queueName;

        if ($withDlxCompanions) {
            $this->queuesToDelete[] = $queueName . '-retry';
            $this->queuesToDelete[] = $queueName . '-failed';
        }

        return $queueName;
    }

    public function testPublishAndConsumeRoundTrip(): void
    {
        $queueName = $this->trackQueue($this->uniqueQueueName());
        $queue = new Queue([self::$connectionString]);
        $body = json_encode(['id' => bin2hex(random_bytes(4))]);

        $queue->publish($queueName, $body, false);

        $received = null;
        $queue->consume(
            5,
            $queueName,
            function (int $_timeout, int $_startTime, AMQPMessage $msg) use (&$received) {
                $received = $msg->getBody();
                $msg->ack();
                $msg->getChannel()->basic_cancel($msg->getConsumerTag());
            },
            false,
            10,
            false
        );

        $this->assertSame($body, $received);
    }

    public function testConsumeReturnsWithoutInvokingCallbackWhenQueueIsEmpty(): void
    {
        $queueName = $this->trackQueue($this->uniqueQueueName());
        $queue = new Queue([self::$connectionString]);

        $called = false;
        $start = time();
        $queue->consume(
            1,
            $queueName,
            function () use (&$called) {
                $called = true;
            },
            false,
            10,
            false
        );
        $elapsed = time() - $start;

        $this->assertFalse($called, 'Callback should not be invoked when the queue is empty');
        $this->assertLessThan(5, $elapsed, 'consume() should respect the requested timeout');
    }

    public function testPublishWithDlxDeclaresRetryCompanionQueue(): void
    {
        $queueName = $this->trackQueue($this->uniqueQueueName(), true);
        $queue = new Queue([self::$connectionString]);

        $queue->publish($queueName, 'payload', true);

        $connection = $this->rawConnection();
        $channel = $connection->channel();

        // A passive declare succeeds without throwing only if the queue already exists.
        [, $messageCount] = $channel->queue_declare($queueName . '-retry', true);

        $channel->close();
        $connection->close();

        $this->assertSame(0, $messageCount, 'Retry companion queue should exist and start empty');
    }

    public function testRetryReturnsFalseAndMovesMessageToDeadQueueAfterMaxRetriesExceeded(): void
    {
        $queueName = $this->trackQueue($this->uniqueQueueName(), true);
        $options = new QueueOptions(maxRetries: 0);
        $queue = new Queue([self::$connectionString], $options);
        $body = 'exhausted-' . bin2hex(random_bytes(4));

        $scheduledAgain = $queue->retry($queueName, new AMQPMessage($body));
        $this->assertFalse($scheduledAgain, 'retry() should report no further attempt once maxRetries is exceeded');

        $received = null;
        $queue->consume(
            5,
            $queueName . '-failed',
            function (int $_timeout, int $_startTime, AMQPMessage $msg) use (&$received) {
                $received = $msg->getBody();
                $msg->ack();
                $msg->getChannel()->basic_cancel($msg->getConsumerTag());
            },
            false,
            10,
            false
        );

        $this->assertSame($body, $received, 'Exhausted message should land on the dead queue');
    }

    public function testRetrySchedulesMessageBackToOriginalQueueWithIncrementedRetryCount(): void
    {
        $queueName = $this->trackQueue($this->uniqueQueueName(), true);
        $this->queuesToDelete[] = $queueName . '-retry-1';
        $options = new QueueOptions(initialRetryDelayMs: 100, retryMultiplier: 1.0);
        $queue = new Queue([self::$connectionString], $options);
        $body = 'retry-' . bin2hex(random_bytes(4));

        // The original queue must exist before RabbitMQ can dead-letter into it.
        $queue->publish($queueName, 'seed', false);

        $scheduledAgain = $queue->retry($queueName, new AMQPMessage($body));
        $this->assertTrue($scheduledAgain, 'retry() should schedule another attempt within maxRetries');

        $received = [];
        $queue->consume(
            5,
            $queueName,
            function (int $_timeout, int $_startTime, AMQPMessage $msg) use (&$received, $queue) {
                $received[$msg->getBody()] = $queue->getRetryCount($msg);
                $msg->ack();
                if (count($received) >= 2) {
                    $msg->getChannel()->basic_cancel($msg->getConsumerTag());
                }
            },
            true,
            10,
            false
        );

        $this->assertArrayHasKey($body, $received, 'Retried message should dead-letter back onto the original queue');
        $this->assertSame(1, $received[$body], 'Retry count header should be incremented to 1');
    }
}
