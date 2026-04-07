<?php

namespace GuiBranco\Pancake;

use GuiBranco\Pancake\Exceptions\CircuitBreakerOpenException;

/**
 * Class CircuitBreaker
 *
 * Implements the Circuit Breaker pattern to prevent cascading failures in distributed
 * systems or any code path that interacts with unreliable external dependencies.
 *
 * The circuit transitions between three states:
 *
 * - **closed**: Normal operation. Every call is forwarded to the wrapped operation.
 *   Failures are counted; once the {@see $failureThreshold} is reached the circuit
 *   trips to *open*.
 * - **open**: All calls are immediately rejected with a
 *   {@see CircuitBreakerOpenException}. The circuit stays open for
 *   {@see $resetTimeout} seconds, after which it transitions to *half-open*.
 * - **half-open**: A single probe call is forwarded to the wrapped operation.
 *   Success resets the circuit back to *closed*; failure returns it to *open*
 *   and resets the timeout.
 *
 * State is persisted across instances through the injected {@see MemoryCache},
 * keyed under `circuit_state`. A `__destruct` hook ensures state is flushed even
 * if `execute()` is never called.
 *
 * ### Minimal example
 *
 * ```php
 * use GuiBranco\Pancake\CircuitBreaker;
 * use GuiBranco\Pancake\MemoryCache;
 * use GuiBranco\Pancake\Exceptions\CircuitBreakerOpenException;
 *
 * // MemoryCache implements MemoryCacheInterface out of the box.
 * $cb = new CircuitBreaker(new MemoryCache(), failureThreshold: 3, resetTimeout: 120);
 *
 * try {
 *     $result = $cb->execute(fn() => myRiskyCall());
 * } catch (CircuitBreakerOpenException $e) {
 *     // Circuit is open — back off and retry later
 * } catch (\Exception $e) {
 *     // The operation itself threw — circuit failure counter was incremented
 * }
 * ```
 *
 * @package GuiBranco\Pancake
 */
class CircuitBreaker
{
    /** Circuit is closed; operations execute normally. */
    public const STATE_CLOSED = 'closed';

    /** Circuit is open; all operations are blocked. */
    public const STATE_OPEN = 'open';

    /** Circuit is probing recovery; a single operation is allowed. */
    public const STATE_HALF_OPEN = 'half-open';

    /** Cache key used to persist circuit state. */
    private const CACHE_KEY = 'circuit_state';

    /** @var string Current circuit state: 'closed' | 'open' | 'half-open' */
    private string $state = self::STATE_CLOSED;

    /** @var int Number of consecutive failures recorded. */
    private int $failureCount = 0;

    /** @var int|null Unix timestamp of the last recorded failure, or null when none. */
    private ?int $lastFailureTime = null;

    /** @var int Consecutive failures allowed before the circuit opens. */
    private int $failureThreshold;

    /** @var int Seconds the circuit remains open before transitioning to half-open. */
    private int $resetTimeout;

    /** @var MemoryCacheInterface Cache used to persist and restore state across instances. */
    private MemoryCacheInterface $cache;

    /**
     * Creates a new CircuitBreaker, restoring any previously persisted state.
     *
     * @param MemoryCacheInterface $cache            Cache instance for state persistence.
     * @param int                  $failureThreshold Consecutive failures before opening. Default: 5.
     * @param int                  $resetTimeout     Seconds the open circuit waits before probing. Default: 60.
     */
    public function __construct(
        MemoryCacheInterface $cache,
        int $failureThreshold = 5,
        int $resetTimeout = 60
    ) {
        $this->cache = $cache;
        $this->failureThreshold = $failureThreshold;
        $this->resetTimeout = $resetTimeout;
        $this->loadState();
    }

    /**
     * Flushes the current state to the cache when the instance is destroyed.
     *
     * This guarantees that state is persisted even when `execute()` is not called
     * (e.g. early returns in application code).
     */
    public function __destruct()
    {
        $this->saveState();
    }

    // -------------------------------------------------------------------------
    // Fluent configuration setters
    // -------------------------------------------------------------------------

    /**
     * Sets the number of consecutive failures allowed before the circuit opens.
     *
     * Useful for re-configuring an existing instance without rebuilding it.
     *
     * @param int $threshold Must be greater than zero.
     * @return static Fluent interface.
     * @throws \InvalidArgumentException When $threshold is not positive.
     */
    public function setFailureThreshold(int $threshold): static
    {
        if ($threshold <= 0) {
            throw new \InvalidArgumentException('failureThreshold must be greater than zero.');
        }
        $this->failureThreshold = $threshold;
        return $this;
    }

    /**
     * Sets the number of seconds the open circuit waits before probing recovery.
     *
     * @param int $timeout Must be greater than zero.
     * @return static Fluent interface.
     * @throws \InvalidArgumentException When $timeout is not positive.
     */
    public function setResetTimeout(int $timeout): static
    {
        if ($timeout <= 0) {
            throw new \InvalidArgumentException('resetTimeout must be greater than zero.');
        }
        $this->resetTimeout = $timeout;
        return $this;
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Executes a protected operation within the circuit breaker context.
     *
     * Behaviour by state:
     * - **closed / half-open**: The operation is invoked. Success resets the circuit;
     *   failure increments the counter (and may open the circuit).
     * - **open (timeout not reached)**: Throws {@see CircuitBreakerOpenException}
     *   immediately without invoking the operation.
     * - **open (timeout reached)**: Transitions to *half-open* and attempts one
     *   probe call.
     *
     * @param callable $operation Any callable that performs the guarded work.
     * @return mixed The return value of `$operation` on success.
     * @throws CircuitBreakerOpenException If the circuit is open and the timeout has not elapsed.
     * @throws \Exception Re-throws any exception thrown by `$operation`.
     */
    public function execute(callable $operation): mixed
    {
        $this->transitionIfNeeded();

        if ($this->state === self::STATE_OPEN) {
            $reopensAt = $this->lastFailureTime + $this->resetTimeout;
            $secondsLeft = $reopensAt - time();
            throw new CircuitBreakerOpenException(
                sprintf(
                    'Circuit breaker is open until %s (in %d second%s).',
                    date('Y-m-d H:i:s', $reopensAt),
                    $secondsLeft,
                    $secondsLeft === 1 ? '' : 's'
                )
            );
        }

        try {
            $result = $operation();
            $this->reset();
            return $result;
        } catch (\Exception $e) {
            $this->handleFailure();
            throw $e;
        } finally {
            $this->saveState();
        }
    }

    /**
     * Returns the current state of the circuit.
     *
     * @return string One of {@see STATE_CLOSED}, {@see STATE_OPEN}, or {@see STATE_HALF_OPEN}.
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Returns the number of consecutive failures recorded since the last reset.
     *
     * @return int
     */
    public function getFailureCount(): int
    {
        return $this->failureCount;
    }

    /**
     * Returns the Unix timestamp of the last recorded failure, or null if none.
     *
     * @return int|null
     */
    public function getLastFailureTime(): ?int
    {
        return $this->lastFailureTime;
    }

    /**
     * Manually resets the circuit to the closed state, clearing all failure history.
     *
     * Useful for administrative tooling, test teardown, or recovery overrides.
     *
     * @return void
     */
    public function forceReset(): void
    {
        $this->reset();
        $this->saveState();
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Transitions an open circuit to half-open once the reset timeout has elapsed.
     *
     * @return void
     */
    private function transitionIfNeeded(): void
    {
        if ($this->state === self::STATE_OPEN && $this->isTimeoutReached()) {
            $this->state = self::STATE_HALF_OPEN;
        }
    }

    /**
     * Increments the failure counter, records the failure timestamp, and opens
     * the circuit if the threshold has been reached.
     *
     * @return void
     */
    private function handleFailure(): void
    {
        $this->failureCount++;
        $this->lastFailureTime = time();

        if ($this->failureCount >= $this->failureThreshold) {
            $this->state = self::STATE_OPEN;
        }
    }

    /**
     * Determines whether the reset timeout has elapsed since the last failure.
     *
     * @return bool True if the circuit should attempt recovery.
     */
    private function isTimeoutReached(): bool
    {
        return $this->lastFailureTime !== null
            && (time() - $this->lastFailureTime) >= $this->resetTimeout;
    }

    /**
     * Resets the circuit to the closed state and clears all failure tracking data.
     *
     * @return void
     */
    private function reset(): void
    {
        $this->state = self::STATE_CLOSED;
        $this->failureCount = 0;
        $this->lastFailureTime = null;
    }

    /**
     * Loads the circuit state from the cache.
     *
     * Falls back to the default closed state if no data is found or if the
     * persisted data is incomplete.
     *
     * @return void
     */
    private function loadState(): void
    {
        $state = $this->cache->readJsonInMemory();

        if (
            !is_array($state)
            || !isset($state['state'], $state['failureCount'], $state['lastFailureTime'])
        ) {
            return;
        }

        $this->state = $state['state'];
        $this->failureCount = (int) $state['failureCount'];
        $this->lastFailureTime = $state['lastFailureTime'] !== null
            ? (int) $state['lastFailureTime']
            : null;
    }

    /**
     * Persists the current circuit state to the cache.
     *
     * @return void
     */
    private function saveState(): void
    {
        $this->cache->writeJsonInMemory([
            'state' => $this->state,
            'failureCount' => $this->failureCount,
            'lastFailureTime' => $this->lastFailureTime,
        ]);
    }
}
