<?php

namespace GuiBranco\Pancake;

use GuiBranco\Pancake\MemoryCache;

/**
 * Handles the open/half-open/closed state for a Circuit Breaker pattern.
 *
 * This pattern protects a service from being overwhelmed by repeated failures
 * to a remote resource.
 */
class CircuitBreaker
{
    // The three possible states of the circuit breaker.
    private const STATE_CLOSED    = 'closed';
    private const STATE_HALF_OPEN = 'half-open';
    private const STATE_OPEN      = 'open';

    /** @var string The current state of the circuit breaker. */
    private $state = self::STATE_CLOSED;

    /** @var int The number of consecutive failures before the circuit opens. */
    private $failureThreshold;

    /** @var int The duration (in seconds) the circuit remains open before transitioning to half-open. */
    private $resetTimeout;

    /** @var int The current count of consecutive failures. */
    private $failureCount = 0;

    /** @var int|null The timestamp of the last recorded failure. */
    private $lastFailureTime = null;

    /** @var MemoryCache The dependency to persist and load the state. */
    private $memoryCache;

    /**
     * CircuitBreaker constructor.
     *
     * @param MemoryCache $memoryCache      The in-memory cache used for state persistence.
     * @param int         $failureThreshold The number of failures allowed before opening the circuit. Defaults to 5.
     * @param int         $resetTimeout     The time in seconds the circuit stays open. Defaults to 120.
     */
    public function __construct(
        MemoryCache $memoryCache,
        int $failureThreshold = 5,
        int $resetTimeout = 120
    ) {
        $this->memoryCache = $memoryCache;
        $this->failureThreshold = $failureThreshold;
        $this->resetTimeout = $resetTimeout;
        $this->loadState();
    }

    /**
     * Saves the current state of the circuit breaker to the cache when the object is destroyed.
     */
    public function __destruct()
    {
        $this->saveState();
    }

    /**
     * Persists the circuit breaker state to the MemoryCache.
     */
    private function saveState(): void
    {
        $stateData = [
            "state" => $this->state,
            "failureCount" => $this->failureCount,
            "lastFailureTime" => $this->lastFailureTime
        ];
        $this->memoryCache->writeJsonInMemory($stateData);
    }

    /**
     * Loads the circuit breaker state from the MemoryCache.
     */
    private function loadState(): void
    {
        $stateData = $this->memoryCache->readJsonInMemory();

        if (
            !is_array($stateData) ||
            !isset($stateData["state"]) ||
            !isset($stateData["failureCount"]) ||
            !isset($stateData["lastFailureTime"])
        ) {
            // If data is missing or incomplete, keep the initial closed state.
            return;
        }

        $this->state = $stateData["state"];
        $this->failureCount = $stateData["failureCount"];
        $this->lastFailureTime = $stateData["lastFailureTime"];
    }

    /**
     * Executes the given operation, respecting the circuit breaker state.
     *
     * @param callable $operation The operation to execute.
     * @return mixed The result of the operation.
     * @throws \Exception If the operation fails.
     * @throws CircuitBreakerOpenException If the circuit is open and the reset timeout hasn't elapsed.
     */
    public function execute(callable $operation)
    {
        // 1. OPEN state transition to HALF-OPEN check
        if ($this->state === self::STATE_OPEN && $this->isTimeoutReached()) {
            $this->state = self::STATE_HALF_OPEN;
        }

        // 2. CLOSED or HALF-OPEN state execution
        if ($this->state === self::STATE_CLOSED || $this->state === self::STATE_HALF_OPEN) {
            try {
                $result = $operation();
                // Success: reset the circuit and return result
                $this->reset();
                return $result;
            } catch (\Exception $e) {
                // Failure: update state, re-throw exception
                $this->handleFailure();
                throw $e;
            } finally {
                // Always save state after an execution attempt
                $this->saveState();
            }
        }

        // 3. OPEN state, timeout not reached: block the call
        $resetTime = $this->lastFailureTime + $this->resetTimeout;
        $timeRemaining = $resetTime - time();

        throw new CircuitBreakerOpenException(
            "Circuit breaker is open until " . date('Y-m-d H:i:s', $resetTime) .
            " (in " . $timeRemaining . " seconds)"
        );
    }

    /**
     * Handles a failure in the executed operation.
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
     * Checks if the reset timeout has been reached since the last failure.
     *
     * @return bool True if the timeout has elapsed, false otherwise.
     */
    private function isTimeoutReached(): bool
    {
        if ($this->lastFailureTime === null) {
            return false;
        }
        return time() - $this->lastFailureTime >= $this->resetTimeout;
    }

    /**
     * Resets the circuit breaker state back to closed on successful operation.
     */
    private function reset(): void
    {
        $this->failureCount = 0;
        $this->lastFailureTime = null;
        $this->state = self::STATE_CLOSED;
    }

    /**
     * Public method to manually inspect the current state (useful for debugging/monitoring).
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }
}