<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit\Queue;

use Exception;
use GuiBranco\Pancake\Queue\QueueException;
use PHPUnit\Framework\TestCase;

class QueueExceptionTest extends TestCase
{
    public function testExceptionMessageIsSetCorrectly(): void
    {
        $message = 'Failed to connect to RabbitMQ server';
        $exception = new QueueException($message);

        $this->assertSame($message, $exception->getMessage(), 'Exception message should match');
    }

    public function testOperationIsSetCorrectly(): void
    {
        $message = 'Publish failed';
        $operation = 'publish';
        $exception = new QueueException($message, $operation);

        $this->assertSame($operation, $exception->getOperation(), 'Operation should be set correctly');
    }

    public function testDefaultOperationIsEmptyString(): void
    {
        $exception = new QueueException('Some error');

        $this->assertSame('', $exception->getOperation(), 'Default operation should be an empty string');
    }

    public function testErrorCodeIsSetCorrectly(): void
    {
        $code = 42;
        $exception = new QueueException('Error occurred', '', $code);

        $this->assertSame($code, $exception->getCode(), 'Error code should match the provided code');
    }

    public function testPreviousExceptionIsSetCorrectly(): void
    {
        $previousException = new Exception('Previous error');
        $exception = new QueueException('New error', '', 0, $previousException);

        $this->assertSame($previousException, $exception->getPrevious(), 'Previous exception should match the provided exception');
    }

    public function testIsThrowable(): void
    {
        $this->expectException(QueueException::class);
        $this->expectExceptionMessage('boom');

        throw new QueueException('boom', 'connection');
    }
}
