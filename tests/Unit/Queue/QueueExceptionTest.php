<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit\Queue;

use GuiBranco\Pancake\Queue\QueueException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(QueueException::class)]
class QueueExceptionTest extends TestCase
{
    public function testDefaultOperationIsEmptyString(): void
    {
        $exception = new QueueException('Something went wrong');

        $this->assertSame('', $exception->getOperation());
    }

    public function testMessageIsStoredCorrectly(): void
    {
        $exception = new QueueException('Connection refused', 'connect');

        $this->assertSame('Connection refused', $exception->getMessage());
    }

    public function testOperationIsStoredCorrectly(): void
    {
        $exception = new QueueException('Publish failed', 'publish');

        $this->assertSame('publish', $exception->getOperation());
    }

    public function testCodeIsStoredCorrectly(): void
    {
        $exception = new QueueException('Error', 'connect', 42);

        $this->assertSame(42, $exception->getCode());
    }

    public function testPreviousExceptionIsChained(): void
    {
        $previous = new \RuntimeException('Underlying error');
        $exception = new QueueException('Queue error', 'consume', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testIsInstanceOfException(): void
    {
        $exception = new QueueException('Test');

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testDefaultCodeIsZero(): void
    {
        $exception = new QueueException('Test error');

        $this->assertSame(0, $exception->getCode());
    }

    public function testDefaultPreviousIsNull(): void
    {
        $exception = new QueueException('Test error', 'init');

        $this->assertNull($exception->getPrevious());
    }

    public function testAllOperationNamesArePreserved(): void
    {
        $operations = ['init', 'parse', 'connect', 'publish', 'consume'];

        foreach ($operations as $op) {
            $exception = new QueueException('Error', $op);
            $this->assertSame($op, $exception->getOperation());
        }
    }
}
