<?php

// tests/CircuitBreakerTest.php

use PHPUnit\Framework\TestCase;
use YourProjectNamespace\CircuitBreaker;
use YourProjectNamespace\CircuitBreakerOpenException;
use YourProjectNamespace\MemoryCache;
use Mockery\MockInterface;

/**
 * @covers \YourProjectNamespace\CircuitBreaker
 */
class CircuitBreakerTest extends TestCase
{
    /** @var MockInterface|MemoryCache */
    private $cacheMock;

    protected function setUp(): void
    {
        // Set up the mock for MemoryCache
        $this->cacheMock = Mockery::mock(MemoryCache::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // --- Mock Helper Methods ---

    private function createBreaker(array $initialState = []): CircuitBreaker
    {
        $defaultState = [
            "state" => 'closed',
            "failureCount" => 0,
            "lastFailureTime" => null,
        ];
        $mergedState = array_merge($defaultState, $initialState);

        $this->cacheMock->shouldReceive('readJsonInMemory')
            ->once()
            ->andReturn($mergedState);

        // Expect saveState to be called eventually
        $this->cacheMock->shouldReceive('writeJsonInMemory')
            ->atLeast()
            ->once();

        return new CircuitBreaker($this->cacheMock, 3, 10); // Threshold 3, Timeout 10s
    }

    // --- Test Cases ---

    public function testExecute_successInClosedState_resetsAndReturnsResult()
    {
        $breaker = $this->createBreaker();

        $operation = function () {
            return 'API Response';
        };

        $result = $breaker->execute($operation);

        $this->assertEquals('API Response', $result);
        $this->assertEquals('closed', $breaker->getState()); // Should stay closed and reset internally
    }

    public function testExecute_failureInClosedState_incrementsCount()
    {
        $breaker = $this->createBreaker();
        
        $this->expectException(\Exception::class);

        try {
            $operation = function () {
                throw new \Exception('API Error');
            };
            $breaker->execute($operation);
        } catch (\Exception $e) {
            // Verify state: failureCount should be 1
            // A quick and dirty way to check internal state for a test:
            // (In real code, use a public getter or a separate integration test)
            $this->assertStringContainsString('failureCount":1', $this->cacheMock->getMock()->getReceivedCalls()[1]['arguments'][0]['stateData']);
            throw $e;
        }
    }

    public function testExecute_failureReachingThreshold_opensCircuit()
    {
        // Start with 2 failures (threshold is 3)
        $breaker = $this->createBreaker(['failureCount' => 2, 'lastFailureTime' => time() - 5]);
        
        $this->expectException(\Exception::class);

        try {
            $operation = function () {
                throw new \Exception('Final straw');
            };
            $breaker->execute($operation);
        } catch (\Exception $e) {
            // Verify state: should now be open
            $this->assertStringContainsString('state":"open', $this->cacheMock->getMock()->getReceivedCalls()[1]['arguments'][0]['stateData']);
            throw $e;
        }
    }

    public function testExecute_inOpenStateBeforeTimeout_throwsException()
    {
        // State: open, last failure was 1 second ago (timeout is 10s)
        $breaker = $this->createBreaker(['state' => 'open', 'lastFailureTime' => time() - 1]);
        
        $this->expectException(CircuitBreakerOpenException::class);
        $breaker->execute(fn() => 'should not run');
    }

    public function testExecute_inOpenStateAfterTimeout_transitionsToHalfOpen()
    {
        // State: open, last failure was 15 seconds ago (timeout is 10s)
        $breaker = $this->createBreaker(['state' => 'open', 'lastFailureTime' => time() - 15]);

        // Operation succeeds in half-open state
        $operation = function () {
            return 'Test Result';
        };
        
        $result = $breaker->execute($operation);
        
        $this->assertEquals('Test Result', $result);
        // Verify state: should have gone from open -> half-open -> closed
        $this->assertEquals('closed', $breaker->getState()); 
    }

    public function testExecute_failureInHalfOpenState_transitionsBackToOpen()
    {
        // State: open (will transition to half-open), timeout reached
        $breaker = $this->createBreaker(['state' => 'open', 'lastFailureTime' => time() - 15]);

        $this->expectException(\Exception::class);

        try {
            $operation = function () {
                throw new \Exception('Failed check');
            };
            $breaker->execute($operation);
        } catch (\Exception $e) {
            // Verify state: should have gone from half-open -> open
            $this->assertStringContainsString('state":"open', $this->cacheMock->getMock()->getReceivedCalls()[1]['arguments'][0]['stateData']);
            throw $e;
        }
    }
}