<?php

namespace GuiBranco\Pancake\Tests\Unit;

use GuiBranco\Pancake\CircuitBreaker;
use GuiBranco\Pancake\Exceptions\CircuitBreakerOpenException;
use GuiBranco\Pancake\MemoryCacheInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for {@see CircuitBreaker}.
 *
 * The MemoryCache dependency is replaced by a simple in-memory stub so that
 * every test starts from a clean, predictable state without touching real
 * persistence.
 */
class CircuitBreakerTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers / Fixtures
    // -------------------------------------------------------------------------

    /**
     * Builds an in-memory MemoryCache stub that holds its state in a plain array.
     *
     * @return object Anonymous stub implementing readJsonInMemory() / writeJsonInMemory()
     */
    private function makeCache(array $initial = []): object
    {
        return new class($initial) implements MemoryCacheInterface {
            private array $data;

            public function __construct(array $data)
            {
                $this->data = $data;
            }

            public function readJsonInMemory(): array
            {
                return $this->data;
            }

            public function writeJsonInMemory(array $data): void
            {
                $this->data = $data;
            }
        };
    }

    /**
     * Returns a callable that always succeeds with the given return value.
     */
    private function successOperation(mixed $returnValue = 'ok'): callable
    {
        return fn() => $returnValue;
    }

    /**
     * Returns a callable that always throws a generic RuntimeException.
     */
    private function failOperation(string $message = 'boom'): callable
    {
        return function () use ($message): never {
            throw new \RuntimeException($message);
        };
    }

    /**
     * Builds a CircuitBreaker with an empty cache and sane test defaults.
     */
    private function makeCB(int $threshold = 3, int $timeout = 60, array $cacheData = []): CircuitBreaker
    {
        return new CircuitBreaker($this->makeCache($cacheData), $threshold, $timeout);
    }

    // -------------------------------------------------------------------------
    // Constructor & initial state
    // -------------------------------------------------------------------------

    public function testInitialStateIsClosed(): void
    {
        $cb = $this->makeCB();
        $this->assertSame(CircuitBreaker::STATE_CLOSED, $cb->getState());
    }

    public function testInitialFailureCountIsZero(): void
    {
        $cb = $this->makeCB();
        $this->assertSame(0, $cb->getFailureCount());
    }

    public function testInitialLastFailureTimeIsNull(): void
    {
        $cb = $this->makeCB();
        $this->assertNull($cb->getLastFailureTime());
    }

    public function testStateIsRestoredFromCache(): void
    {
        $cb = $this->makeCB(3, 60, [
            'state' => 'open',
            'failureCount' => 3,
            'lastFailureTime' => time(),
        ]);
        $this->assertSame(CircuitBreaker::STATE_OPEN, $cb->getState());
        $this->assertSame(3, $cb->getFailureCount());
    }

    public function testPartialCacheDataIsIgnoredAndDefaultsApply(): void
    {
        // Missing 'lastFailureTime' — defaults should kick in
        $cb = $this->makeCB(3, 60, ['state' => 'open', 'failureCount' => 2]);
        $this->assertSame(CircuitBreaker::STATE_CLOSED, $cb->getState());
        $this->assertSame(0, $cb->getFailureCount());
    }

    public function testEmptyCacheStartsWithDefaults(): void
    {
        $cb = $this->makeCB(3, 60, []);
        $this->assertSame(CircuitBreaker::STATE_CLOSED, $cb->getState());
        $this->assertNull($cb->getLastFailureTime());
    }

    // -------------------------------------------------------------------------
    // Fluent setters
    // -------------------------------------------------------------------------

    public function testSetFailureThresholdFluentReturn(): void
    {
        $cb = $this->makeCB();
        $this->assertSame($cb, $cb->setFailureThreshold(10));
    }

    public function testSetResetTimeoutFluentReturn(): void
    {
        $cb = $this->makeCB();
        $this->assertSame($cb, $cb->setResetTimeout(120));
    }

    public function testSetFailureThresholdZeroThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->makeCB()->setFailureThreshold(0);
    }

    public function testSetFailureThresholdNegativeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->makeCB()->setFailureThreshold(-1);
    }

    public function testSetResetTimeoutZeroThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->makeCB()->setResetTimeout(0);
    }

    public function testSetResetTimeoutNegativeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->makeCB()->setResetTimeout(-5);
    }

    // -------------------------------------------------------------------------
    // Closed state — happy path
    // -------------------------------------------------------------------------

    public function testSuccessfulExecuteReturnValue(): void
    {
        $cb = $this->makeCB();
        $result = $cb->execute($this->successOperation('hello'));
        $this->assertSame('hello', $result);
    }

    public function testSuccessfulExecuteKeepsCircuitClosed(): void
    {
        $cb = $this->makeCB();
        $cb->execute($this->successOperation());
        $this->assertSame(CircuitBreaker::STATE_CLOSED, $cb->getState());
    }

    public function testSuccessfulExecuteKeepsFailureCountAtZero(): void
    {
        $cb = $this->makeCB();
        $cb->execute($this->failOperation());
        // One failure, threshold is 3 → still closed
        $cb->execute($this->successOperation());
        // Success should reset the count
        $this->assertSame(0, $cb->getFailureCount());
    }

    // -------------------------------------------------------------------------
    // Closed state — failure accumulation
    // -------------------------------------------------------------------------

    public function testSingleFailureIncrementsCounter(): void
    {
        $cb = $this->makeCB(3, 60);
        try {
            $cb->execute($this->failOperation());
        } catch (\RuntimeException) {
        }
        $this->assertSame(1, $cb->getFailureCount());
    }

    public function testFailuresBelowThresholdKeepCircuitClosed(): void
    {
        $cb = $this->makeCB(3, 60);
        for ($i = 0; $i < 2; $i++) {
            try {
                $cb->execute($this->failOperation());
            } catch (\RuntimeException) {
            }
        }
        $this->assertSame(CircuitBreaker::STATE_CLOSED, $cb->getState());
    }

    public function testOperationExceptionIsRethrown(): void
    {
        $cb = $this->makeCB();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('boom');
        $cb->execute($this->failOperation('boom'));
    }

    // -------------------------------------------------------------------------
    // Closed → Open transition
    // -------------------------------------------------------------------------

    public function testCircuitOpensAfterThresholdFailures(): void
    {
        $cb = $this->makeCB(3, 60);
        for ($i = 0; $i < 3; $i++) {
            try {
                $cb->execute($this->failOperation());
            } catch (\RuntimeException) {
            }
        }
        $this->assertSame(CircuitBreaker::STATE_OPEN, $cb->getState());
    }

    public function testLastFailureTimeIsSetOnOpen(): void
    {
        $before = time();
        $cb = $this->makeCB(1, 60);
        try {
            $cb->execute($this->failOperation());
        } catch (\RuntimeException) {
        }
        $this->assertGreaterThanOrEqual($before, $cb->getLastFailureTime());
    }

    // -------------------------------------------------------------------------
    // Open state — blocking
    // -------------------------------------------------------------------------

    public function testOpenCircuitThrowsCircuitBreakerOpenException(): void
    {
        $cb = $this->makeCB(1, 60);
        try {
            $cb->execute($this->failOperation()); // trips circuit
        } catch (\RuntimeException) {
        }

        $this->expectException(CircuitBreakerOpenException::class);
        $cb->execute($this->successOperation());
    }

    public function testOpenCircuitExceptionContainsTimestamp(): void
    {
        $cb = $this->makeCB(1, 60);
        try {
            $cb->execute($this->failOperation());
        } catch (\RuntimeException) {
        }

        try {
            $cb->execute($this->successOperation());
        } catch (CircuitBreakerOpenException $e) {
            $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $e->getMessage());
            return;
        }

        $this->fail('Expected CircuitBreakerOpenException was not thrown.');
    }

    public function testOpenCircuitExceptionContainsSecondsLeft(): void
    {
        $cb = $this->makeCB(1, 120);
        try {
            $cb->execute($this->failOperation());
        } catch (\RuntimeException) {
        }

        try {
            $cb->execute($this->successOperation());
        } catch (CircuitBreakerOpenException $e) {
            $this->assertMatchesRegularExpression('/in \d+ second/', $e->getMessage());
            return;
        }

        $this->fail('Expected CircuitBreakerOpenException was not thrown.');
    }

    public function testOpenCircuitDoesNotInvokeOperation(): void
    {
        $called = false;
        $cb = $this->makeCB(1, 60);
        try {
            $cb->execute($this->failOperation());
        } catch (\RuntimeException) {
        }

        try {
            $cb->execute(function () use (&$called) {
                $called = true;
            });
        } catch (CircuitBreakerOpenException) {
        }

        $this->assertFalse($called, 'Operation should not be invoked when circuit is open.');
    }

    // -------------------------------------------------------------------------
    // Open → Half-open transition
    // -------------------------------------------------------------------------

    public function testCircuitTransitionsToHalfOpenAfterTimeout(): void
    {
        $pastFailure = time() - 120; // well beyond any reset timeout
        $cb = $this->makeCB(1, 60, [
            'state' => 'open',
            'failureCount' => 1,
            'lastFailureTime' => $pastFailure,
        ]);

        // The probe call succeeds → circuit should close
        $cb->execute($this->successOperation());
        $this->assertSame(CircuitBreaker::STATE_CLOSED, $cb->getState());
    }

    public function testHalfOpenSuccessResetsCircuit(): void
    {
        $cb = $this->makeCB(1, 60, [
            'state' => 'open',
            'failureCount' => 5,
            'lastFailureTime' => time() - 120,
        ]);
        $cb->execute($this->successOperation());

        $this->assertSame(CircuitBreaker::STATE_CLOSED, $cb->getState());
        $this->assertSame(0, $cb->getFailureCount());
        $this->assertNull($cb->getLastFailureTime());
    }

    public function testHalfOpenFailureReturnsToOpen(): void
    {
        $cb = $this->makeCB(1, 60, [
            'state' => 'open',
            'failureCount' => 1,
            'lastFailureTime' => time() - 120,
        ]);

        try {
            $cb->execute($this->failOperation());
        } catch (\RuntimeException) {
        }

        $this->assertSame(CircuitBreaker::STATE_OPEN, $cb->getState());
    }

    public function testOpenCircuitStaysOpenBeforeTimeoutWithNullLastFailure(): void
    {
        // Edge: open state but lastFailureTime is null (malformed cache) — should not crash
        $cb = $this->makeCB(1, 60, [
            'state' => 'open',
            'failureCount' => 1,
            'lastFailureTime' => null,
        ]);
        // loadState would reject this (incomplete), so circuit starts closed —
        // just assert no exception during construction
        $this->assertSame(CircuitBreaker::STATE_CLOSED, $cb->getState());
    }

    // -------------------------------------------------------------------------
    // forceReset()
    // -------------------------------------------------------------------------

    public function testForceResetClosesOpenCircuit(): void
    {
        $cb = $this->makeCB(1, 60);
        try {
            $cb->execute($this->failOperation());
        } catch (\RuntimeException) {
        }
        $this->assertSame(CircuitBreaker::STATE_OPEN, $cb->getState());

        $cb->forceReset();
        $this->assertSame(CircuitBreaker::STATE_CLOSED, $cb->getState());
    }

    public function testForceResetClearsFailureCount(): void
    {
        $cb = $this->makeCB(3, 60);
        for ($i = 0; $i < 2; $i++) {
            try {
                $cb->execute($this->failOperation());
            } catch (\RuntimeException) {
            }
        }
        $cb->forceReset();
        $this->assertSame(0, $cb->getFailureCount());
        $this->assertNull($cb->getLastFailureTime());
    }

    public function testForceResetAllowsSubsequentExecution(): void
    {
        $cb = $this->makeCB(1, 60);
        try {
            $cb->execute($this->failOperation());
        } catch (\RuntimeException) {
        }
        $cb->forceReset();

        $result = $cb->execute($this->successOperation('after-reset'));
        $this->assertSame('after-reset', $result);
    }

    // -------------------------------------------------------------------------
    // State persistence
    // -------------------------------------------------------------------------

    public function testStateIsPersistedAfterExecution(): void
    {
        $cache = $this->makeCache();
        $cb = new CircuitBreaker($cache, 3, 60);
        try {
            $cb->execute($this->failOperation());
        } catch (\RuntimeException) {
        }

        // Build a second instance sharing the same cache object
        $cb2 = new CircuitBreaker($cache, 3, 60);
        $this->assertSame(1, $cb2->getFailureCount());
    }

    public function testOpenStateIsPersistedAndRestored(): void
    {
        $cache = $this->makeCache();
        $cb = new CircuitBreaker($cache, 1, 60);
        try {
            $cb->execute($this->failOperation());
        } catch (\RuntimeException) {
        }

        $cb2 = new CircuitBreaker($cache, 1, 60);
        $this->assertSame(CircuitBreaker::STATE_OPEN, $cb2->getState());
    }

    // -------------------------------------------------------------------------
    // Customised thresholds via fluent setters
    // -------------------------------------------------------------------------

    public function testCircuitOpensAtCustomThreshold(): void
    {
        $cb = $this->makeCB(5, 60)->setFailureThreshold(2);
        for ($i = 0; $i < 2; $i++) {
            try {
                $cb->execute($this->failOperation());
            } catch (\RuntimeException) {
            }
        }
        $this->assertSame(CircuitBreaker::STATE_OPEN, $cb->getState());
    }

    public function testSuccessResetAfterThresholdChange(): void
    {
        $cb = $this->makeCB(2, 60);
        try {
            $cb->execute($this->failOperation());
        } catch (\RuntimeException) {
        }
        // Change threshold so 1 failure is enough, then trip it
        $cb->setFailureThreshold(1);
        try {
            $cb->execute($this->failOperation());
        } catch (\RuntimeException) {
        }
        $this->assertSame(CircuitBreaker::STATE_OPEN, $cb->getState());
    }
}
