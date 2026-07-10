<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit\Queue;

use GuiBranco\Pancake\Queue\Queue;
use GuiBranco\Pancake\Queue\QueueException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    public function testConstructorThrowsWhenConnectionStringsAreEmpty(): void
    {
        $this->expectException(QueueException::class);
        $this->expectExceptionMessage('RabbitMQ connection strings not found');

        new Queue([]);
    }

    public function testConstructorExceptionOperationIsConnection(): void
    {
        try {
            new Queue([]);
            $this->fail('Expected QueueException was not thrown');
        } catch (QueueException $e) {
            $this->assertSame('connection', $e->getOperation());
        }
    }

    public function testConstructorAcceptsNonEmptyConnectionStrings(): void
    {
        $queue = new Queue(['amqp://guest:guest@127.0.0.1:5672/']);

        $this->assertInstanceOf(Queue::class, $queue);
    }

    public function testGetRetryCountReturnsZeroWhenNoHeadersPresent(): void
    {
        $queue = new Queue(['amqp://guest:guest@127.0.0.1:5672/']);
        $message = new AMQPMessage('body');

        $this->assertSame(0, $queue->getRetryCount($message));
    }

    public function testGetRetryCountReturnsZeroWhenHeaderMissing(): void
    {
        $queue = new Queue(['amqp://guest:guest@127.0.0.1:5672/']);
        $message = new AMQPMessage('body', [
            'application_headers' => new AMQPTable(['some-other-header' => 'value']),
        ]);

        $this->assertSame(0, $queue->getRetryCount($message));
    }

    public function testGetRetryCountReturnsValueFromHeaders(): void
    {
        $queue = new Queue(['amqp://guest:guest@127.0.0.1:5672/']);
        $message = new AMQPMessage('body', [
            'application_headers' => new AMQPTable(['x-retry-count' => 3]),
        ]);

        $this->assertSame(3, $queue->getRetryCount($message));
    }
}
