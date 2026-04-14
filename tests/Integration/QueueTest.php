<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Integration;

use GuiBranco\Pancake\Queue\Queue;
use GuiBranco\Pancake\Queue\QueueException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the Queue class.
 *
 * Requires a running RabbitMQ instance. Configure the connection via environment
 * variables or rely on the docker-compose.yml defaults:
 *
 *   RABBITMQ_HOST  – default: 127.0.0.1
 *   RABBITMQ_PORT  – default: 5672
 *   RABBITMQ_USER  – default: guest
 *   RABBITMQ_PASS  – default: guest
 *   RABBITMQ_VHOST – default: /
 *
 * Run the broker before executing:
 *   docker compose up -d rabbitmq
 */
#[CoversClass(Queue::class)]
class QueueTest extends TestCase
{
    private const CONSUME_TIMEOUT = 5;

    private static string $dsn;
    private static Queue $queue;
    private static string $baseQueueName;

    public static function setUpBeforeClass(): void
    {
        $host  = getenv('RABBITMQ_HOST') ?: '127.0.0.1';
        $port  = getenv('RABBITMQ_PORT') ?: '5672';
        $user  = getenv('RABBITMQ_USER') ?: 'guest';
        $pass  = getenv('RABBITMQ_PASS') ?: 'guest';
        $vhost = getenv('RABBITMQ_VHOST') ?: '/';

        self::$dsn = "amqp://{$user}:{$pass}@{$host}:{$port}/{$vhost}";

        // Short retry delays for tests: 500 ms, 1 s.
        self::$queue = new Queue([self::$dsn], [500, 1_000]);

        // Use a unique base name per test run to avoid cross-test pollution.
        self::$baseQueueName = 'pancake-test-' . uniqid('', true);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Consume a single message from $queueName within CONSUME_TIMEOUT seconds.
     * Returns the message body or null if nothing arrived.
     */
    private function consumeOne(string $queueName, bool $withDlx = false): ?string
    {
        $received = null;

        self::$queue->consume(
            self::CONSUME_TIMEOUT,
            $queueName,
            function ($msg) use (&$received) {
                $received = $msg->body;
                return true;
            },
            $withDlx
        );

        return $received;
    }

    // -------------------------------------------------------------------------
    // Basic publish / consume (no DLX)
    // -------------------------------------------------------------------------

    public function testPublishAndConsumeWithoutDlx(): void
    {
        $queueName = self::$baseQueueName . '-basic';
        $payload   = json_encode(['event' => 'test', 'ts' => time()]);

        self::$queue->publish($queueName, $payload, false);

        $received = $this->consumeOne($queueName, false);

        $this->assertSame($payload, $received);
    }

    // -------------------------------------------------------------------------
    // Publish / consume with DLX topology
    // -------------------------------------------------------------------------

    public function testPublishAndConsumeWithDlx(): void
    {
        $queueName = self::$baseQueueName . '-dlx';
        $payload   = json_encode(['event' => 'dlx-test']);

        self::$queue->publish($queueName, $payload, true);

        $received = $this->consumeOne($queueName, true);

        $this->assertSame($payload, $received);
    }

    // -------------------------------------------------------------------------
    // Retry pattern
    // -------------------------------------------------------------------------

    public function testFailedMessageIsRoutedToRetryQueue(): void
    {
        $queueName = self::$baseQueueName . '-retry';
        $payload   = json_encode(['event' => 'retry-test']);

        // Publish with DLX so retry queues exist.
        self::$queue->publish($queueName, $payload, true);

        // First consume: callback returns false → message goes to retry-1.
        $attempts = 0;
        self::$queue->consume(
            self::CONSUME_TIMEOUT,
            $queueName,
            function ($msg) use (&$attempts) {
                $attempts++;
                return false; // Signal failure → route to retry-1.
            },
            true
        );

        $this->assertSame(1, $attempts, 'Callback should have been invoked once.');

        // The message should now be in the retry-1 queue (waiting for TTL to expire).
        // Consume from retry-1 directly to verify it arrived there.
        $retryReceived = null;
        self::$queue->consume(
            self::CONSUME_TIMEOUT,
            $queueName . '-retry-1',
            function ($msg) use (&$retryReceived) {
                $retryReceived = $msg->body;
                return true;
            },
            false // Plain declare – retry queue has its own arguments already set.
        );

        $this->assertNotNull($retryReceived, 'Message should be in the retry-1 queue.');

        $data = json_decode($retryReceived, true);
        $this->assertSame('retry-test', $data['event']);
    }

    public function testMessageRoutedToFailedQueueAfterAllRetries(): void
    {
        // Use a queue with zero retry delays so routing to failed is instant.
        $noRetryQueue = new Queue([self::$dsn], []);
        $queueName    = self::$baseQueueName . '-exhausted';
        $payload      = json_encode(['event' => 'exhausted']);

        $noRetryQueue->publish($queueName, $payload, true);

        // Consume: callback fails immediately → should go to failed (no retries left).
        $noRetryQueue->consume(
            self::CONSUME_TIMEOUT,
            $queueName,
            function ($msg) {
                return false;
            },
            true
        );

        // Verify message is now in the failed queue.
        $failedBody = null;
        $noRetryQueue->consume(
            self::CONSUME_TIMEOUT,
            $queueName . '-failed',
            function ($msg) use (&$failedBody) {
                $failedBody = $msg->body;
                return true;
            },
            false
        );

        $this->assertNotNull($failedBody);
        $data = json_decode($failedBody, true);
        $this->assertSame('exhausted', $data['event']);
    }

    // -------------------------------------------------------------------------
    // Exception on bad callback
    // -------------------------------------------------------------------------

    public function testExceptionInCallbackRoutesToRetry(): void
    {
        $queueName = self::$baseQueueName . '-exception';
        $payload   = json_encode(['event' => 'exception-test']);

        self::$queue->publish($queueName, $payload, true);

        self::$queue->consume(
            self::CONSUME_TIMEOUT,
            $queueName,
            function ($msg): void {
                throw new \RuntimeException('Simulated processing error');
            },
            true
        );

        // Message should have been routed to retry-1.
        $retryReceived = null;
        self::$queue->consume(
            self::CONSUME_TIMEOUT,
            $queueName . '-retry-1',
            function ($msg) use (&$retryReceived) {
                $retryReceived = $msg->body;
                return true;
            },
            false
        );

        $this->assertNotNull($retryReceived);
    }

    // -------------------------------------------------------------------------
    // Multiple servers (publish uses random, consume loops)
    // -------------------------------------------------------------------------

    public function testPublishWithMultipleServersStillDelivers(): void
    {
        // Use the same DSN twice to simulate a two-server pool.
        $multiQueue = new Queue([self::$dsn, self::$dsn], []);
        $queueName  = self::$baseQueueName . '-multi';
        $payload    = json_encode(['event' => 'multi-server']);

        $multiQueue->publish($queueName, $payload, false);

        $received = null;
        $multiQueue->consume(
            self::CONSUME_TIMEOUT,
            $queueName,
            function ($msg) use (&$received) {
                $received = $msg->body;
                return true;
            },
            false
        );

        $this->assertSame($payload, $received);
    }

    // -------------------------------------------------------------------------
    // Connect failure
    // -------------------------------------------------------------------------

    public function testPublishThrowsQueueExceptionWhenAllServersDown(): void
    {
        $badQueue = new Queue(['amqp://guest:guest@127.0.0.2:5672/'], []);

        $this->expectException(QueueException::class);

        $badQueue->publish('irrelevant', '{}', false);
    }

    // -------------------------------------------------------------------------
    // Reset-timeout-on-receive
    // -------------------------------------------------------------------------

    public function testConsumeWithResetTimeoutOnReceive(): void
    {
        $queueName = self::$baseQueueName . '-reset-timeout';
        $payload   = json_encode(['event' => 'timeout-reset']);

        self::$queue->publish($queueName, $payload, false);

        $received = null;
        self::$queue->consume(
            self::CONSUME_TIMEOUT,
            $queueName,
            function ($msg) use (&$received) {
                $received = $msg->body;
                return true;
            },
            false,
            true // resetTimeoutOnReceive = true
        );

        $this->assertSame($payload, $received);
    }

    // -------------------------------------------------------------------------
    // Custom QoS count
    // -------------------------------------------------------------------------

    public function testConsumeWithCustomQosCount(): void
    {
        $queueName = self::$baseQueueName . '-qos';

        for ($i = 0; $i < 3; $i++) {
            self::$queue->publish($queueName, json_encode(['n' => $i]), false);
        }

        $count = 0;
        self::$queue->consume(
            self::CONSUME_TIMEOUT,
            $queueName,
            function ($msg) use (&$count) {
                $count++;
                return true;
            },
            false,
            false,
            3 // QoS prefetch = 3
        );

        $this->assertSame(3, $count);
    }
}
