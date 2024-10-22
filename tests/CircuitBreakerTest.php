<?php

use PHPUnit\Framework\TestCase;
use App\CircuitBreaker;
use App\Exceptions\CircuitBreakerOpenException;

class CircuitBreakerTest extends TestCase
{
    private $cacheMock;
    private $circuitBreaker;

    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(MemoryCache::class);
        $this->circuitBreaker = new CircuitBreaker($this->cacheMock);
    }

    public function testInitialization()
    {
        $this->cacheMock->method('get')->willReturn(null);
        $circuitBreaker = new CircuitBreaker($this->cacheMock);
        $this->assertEquals('closed', $circuitBreaker->getState());
    }

    public function testStateTransitionsToOpen()
    {
        $this->cacheMock->method('get')->willReturn(null);
        $circuitBreaker = new CircuitBreaker($this->cacheMock, 1);

        try {
            $circuitBreaker->execute(function() {
                throw new Exception('Failure');
            });
        } catch (Exception $e) {
            // Ignore
        }

        $this->assertEquals('open', $circuitBreaker->getState());
    }

    public function testExecutionThrowsExceptionWhenOpen()
    {
        $this->cacheMock->method('get')->willReturn([
            'state' => 'open',
            'failureCount' => 1,
            'lastFailureTime' => time()
        ]);

        $this->expectException(CircuitBreakerOpenException::class);

        $this->circuitBreaker->execute(function() {
            return 'success';
        });
    }

    public function testExecutionResetsAfterSuccess()
    {
        $this->cacheMock->method('get')->willReturn([
            'state' => 'half-open',
            'failureCount' => 1,
            'lastFailureTime' => time() - 100
        ]);

        $this->circuitBreaker->execute(function() {
            return 'success';
        });

        $this->assertEquals('closed', $this->circuitBreaker->getState());
    }

    public function testPersistenceBetweenExecutions()
    {
        $this->cacheMock->method('get')->willReturn([
            'state' => 'open',
            'failureCount' => 3,
            'lastFailureTime' => time()
        ]);

        $circuitBreaker = new CircuitBreaker($this->cacheMock);
        $this->assertEquals('open', $circuitBreaker->getState());
    }
}

?>
