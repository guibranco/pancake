<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit;

use PHPUnit\Framework\TestCase;
use GuiBranco\Pancake\Database\DatabaseException;

class DatabaseExceptionTest extends TestCase
{
    public function testExceptionMessageIsSetCorrectly()
    {
        $message = 'Database connection failed';
        $exception = new DatabaseException($message);
        
        $this->assertSame($message, $exception->getMessage(), 'Exception message should match');
    }

    public function testOperationIsSetCorrectly()
    {
        $message = 'Query execution failed';
        $operation = 'execute';
        $exception = new DatabaseException($message, $operation);
        
        $this->assertSame($operation, $exception->getOperation(), 'Operation should be set correctly');
    }

    public function testDefaultOperationIsEmptyString()
    {
        $exception = new DatabaseException('Some error');
        
        $this->assertSame('', $exception->getOperation(), 'Default operation should be an empty string');
    }

    public function testErrorCodeIsSetCorrectly()
    {
        $code = 123;
        $exception = new DatabaseException('Error occurred', '', $code);
        
        $this->assertSame($code, $exception->getCode(), 'Error code should match the provided code');
    }

    public function testPreviousExceptionIsSetCorrectly()
    {
        $previousException = new \Exception('Previous error');
        $exception = new DatabaseException('New error', '', 0, $previousException);
        
        $this->assertSame($previousException, $exception->getPrevious(), 'Previous exception should match the provided exception');
    }
}
