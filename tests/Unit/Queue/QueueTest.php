<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit\Queue;

use GuiBranco\Pancake\Queue\Queue;
use GuiBranco\Pancake\Queue\QueueException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Queue::class)]
class QueueTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    public function testConstructorAcceptsValidConnectionStrings(): void
    {
        $queue = new Queue(['amqp://guest:guest@localhost:5672/']);

        $this->assertInstanceOf(Queue::class, $queue);
    }

    public function testConstructorAcceptsMultipleConnectionStrings(): void
    {
        $queue = new Queue([
            'amqp://guest:guest@host1:5672/',
            'amqp://guest:guest@host2:5672/',
        ]);

        $this->assertInstanceOf(Queue::class, $queue);
    }

    public function testConstructorAcceptsCustomRetryDelays(): void
    {
        $queue = new Queue(
            ['amqp://guest:guest@localhost:5672/'],
            [30_000, 60_000, 120_000]
        );

        $this->assertInstanceOf(Queue::class, $queue);
    }

    public function testConstructorAcceptsEmptyRetryDelays(): void
    {
        $queue = new Queue(['amqp://guest:guest@localhost:5672/'], []);

        $this->assertInstanceOf(Queue::class, $queue);
    }

    public function testConstructorThrowsWhenNoConnectionStringsProvided(): void
    {
        $this->expectException(QueueException::class);
        $this->expectExceptionMessage('At least one connection string must be provided.');

        new Queue([]);
    }

    public function testConstructorExceptionHasInitOperation(): void
    {
        try {
            new Queue([]);
            $this->fail('Expected QueueException was not thrown.');
        } catch (QueueException $e) {
            $this->assertSame('init', $e->getOperation());
        }
    }

    // -------------------------------------------------------------------------
    // parseServer (tested indirectly via publish/consume with a mock connection)
    // -------------------------------------------------------------------------

    #[DataProvider('invalidConnectionStringProvider')]
    public function testPublishThrowsOnInvalidConnectionString(string $connectionString): void
    {
        // The parse error surfaces as a QueueException during publish.
        $queue = new Queue([$connectionString]);

        $this->expectException(QueueException::class);

        // This will fail at parseServer before trying to connect.
        $queue->publish('test-queue', '{}');
    }

    public static function invalidConnectionStringProvider(): array
    {
        return [
            'empty string'    => [''],
            'no host'         => ['amqp:///vhost'],
            'scheme only'     => ['amqp://'],
        ];
    }

    // -------------------------------------------------------------------------
    // Implements IQueue
    // -------------------------------------------------------------------------

    public function testQueueImplementsIQueue(): void
    {
        $queue = new Queue(['amqp://guest:guest@localhost:5672/']);

        $this->assertInstanceOf(\GuiBranco\Pancake\Queue\IQueue::class, $queue);
    }

    // -------------------------------------------------------------------------
    // getRetryCount (tested via reflection to avoid a live broker)
    // -------------------------------------------------------------------------

    public function testGetRetryCountReturnsZeroWhenNoHeader(): void
    {
        $queue = new Queue(['amqp://guest:guest@localhost:5672/']);

        $msg = $this->createStub(\PhpAmqpLib\Message\AMQPMessage::class);
        $msg->method('get')->willThrowException(new \Exception('No header'));

        $reflection = new \ReflectionMethod($queue, 'getRetryCount');

        $this->assertSame(0, $reflection->invoke($queue, $msg));
    }

    public function testGetRetryCountReturnsZeroWhenHeaderIsNotAMQPTable(): void
    {
        $queue = new Queue(['amqp://guest:guest@localhost:5672/']);

        $msg = $this->createStub(\PhpAmqpLib\Message\AMQPMessage::class);
        $msg->method('get')->willReturn(null);

        $reflection = new \ReflectionMethod($queue, 'getRetryCount');

        $this->assertSame(0, $reflection->invoke($queue, $msg));
    }

    public function testGetRetryCountReturnsValueFromAMQPTable(): void
    {
        $queue = new Queue(['amqp://guest:guest@localhost:5672/']);

        $table = new \PhpAmqpLib\Wire\AMQPTable(['x-pancake-retry-count' => 3]);

        $msg = $this->createStub(\PhpAmqpLib\Message\AMQPMessage::class);
        $msg->method('get')->willReturn($table);

        $reflection = new \ReflectionMethod($queue, 'getRetryCount');

        $this->assertSame(3, $reflection->invoke($queue, $msg));
    }

    // -------------------------------------------------------------------------
    // parseServer (tested via reflection)
    // -------------------------------------------------------------------------

    public function testParseServerExtractsAllFields(): void
    {
        $queue = new Queue(['amqp://guest:guest@localhost:5672/']);

        $reflection = new \ReflectionMethod($queue, 'parseServer');
        $result = $reflection->invoke($queue, 'amqp://myuser:mypass@myhost:5673/myvhost');

        $this->assertSame('myhost', $result['host']);
        $this->assertSame(5673, $result['port']);
        $this->assertSame('myuser', $result['user']);
        $this->assertSame('mypass', $result['password']);
        $this->assertSame('myvhost', $result['vhost']);
    }

    public function testParseServerDefaultsPortTo5672(): void
    {
        $queue = new Queue(['amqp://guest:guest@localhost:5672/']);

        $reflection = new \ReflectionMethod($queue, 'parseServer');
        $result = $reflection->invoke($queue, 'amqp://user:pass@host/');

        $this->assertSame(5672, $result['port']);
    }

    public function testParseServerDefaultsVhostToSlash(): void
    {
        $queue = new Queue(['amqp://guest:guest@localhost:5672/']);

        $reflection = new \ReflectionMethod($queue, 'parseServer');

        $resultSlash = $reflection->invoke($queue, 'amqp://user:pass@host/');
        $this->assertSame('/', $resultSlash['vhost']);

        $resultEmpty = $reflection->invoke($queue, 'amqp://user:pass@host');
        $this->assertSame('/', $resultEmpty['vhost']);
    }

    public function testParseServerStripsLeadingSlashFromVhost(): void
    {
        $queue = new Queue(['amqp://guest:guest@localhost:5672/']);

        $reflection = new \ReflectionMethod($queue, 'parseServer');
        $result = $reflection->invoke($queue, 'amqp://user:pass@host/production');

        $this->assertSame('production', $result['vhost']);
    }

    public function testParseServerThrowsOnEmptyString(): void
    {
        $queue = new Queue(['amqp://guest:guest@localhost:5672/']);

        $reflection = new \ReflectionMethod($queue, 'parseServer');

        $this->expectException(QueueException::class);
        $reflection->invoke($queue, '');
    }

    public function testParseServerThrowsOnMissingHost(): void
    {
        $queue = new Queue(['amqp://guest:guest@localhost:5672/']);

        $reflection = new \ReflectionMethod($queue, 'parseServer');

        $this->expectException(QueueException::class);
        $reflection->invoke($queue, 'amqp:///vhost');
    }
}
