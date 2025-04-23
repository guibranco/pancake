<?php

namespace App;

use App\Exceptions\CircuitBreakerOpenException;

/**
 * Class CircuitBreaker
 *
 * A simple implementation of the Circuit Breaker pattern to prevent cascading failures in systems.
 * It tracks the number of operation failures and opens the circuit when the failure threshold is reached.
 * The circuit remains open for a configurable timeout before allowing a test execution.
 */
class CircuitBreaker
{
    /**
     * @var string Current state of the circuit ('closed', 'open', 'half-open')
     */
    private $state;

    /**
     * @var int Number of consecutive failures
     */
    private $failureCount;

    /**
     * @var int|null Timestamp of the last failure
     */
    private $lastFailureTime;

    /**
     * @var int Maximum allowed failures before the circuit opens
     */
    private $failureThreshold;

    /**
     * @var int Time in seconds the circuit remains open before transitioning to 'half-open'
     */
    private $resetTimeout;

    /**
     * @var mixed Cache instance to store circuit state
     */
    private $cache;

    /**
     * CircuitBreaker constructor.
     *
     * @param mixed $cache A cache instance that implements get() and set() methods
     * @param int $failureThreshold Number of failures allowed before opening the circuit
     * @param int $resetTimeout Timeout in seconds after which the circuit attempts recovery
     */
    public function __construct($cache, $failureThreshold = 5, $resetTimeout = 60)
    {
        $this->cache = $cache;
        $this->failureThreshold = $failureThreshold;
        $this->resetTimeout = $resetTimeout;
        $this->loadState();
    }

    /**
     * Load the current circuit state from the cache.
     *
     * If no data is available, initializes to the default closed state.
     *
     * @return void
     */
    private function loadState()
    {
        $state = $this->cache->get('circuit_state');
        $this->state = $state['state'] ?? 'closed';
        $this->failureCount = $state['failureCount'] ?? 0;
        $this->lastFailureTime = $state['lastFailureTime'] ?? null;
    }

    /**
     * Save the current circuit state to the cache.
     *
     * @return void
     */
    private function saveState()
    {
        $this->cache->set('circuit_state', [
            'state' => $this->state,
            'failureCount' => $this->failureCount,
            'lastFailureTime' => $this->lastFailureTime,
        ]);
    }

    /**
     * Execute a given operation with circuit breaker protection.
     *
     * @param callable $operation The operation to execute
     * @return mixed The result of the operation
     * @throws CircuitBreakerOpenException If the circuit is open and the timeout has not yet passed
     * @throws \Exception Rethrows any exception from the operation
     */
    public function execute(callable $operation)
    {
        if ($this->state === 'open' && !$this->isTimeoutReached()) {
            throw new CircuitBreakerOpenException('Circuit is open. Please try again later.');
        }

        if ($this->state === 'open' && $this->isTimeoutReached()) {
            $this->state = 'half-open';
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
     * Handle a failure by incrementing the failure count and updating the circuit state.
     *
     * @return void
     */
    private function handleFailure()
    {
        $this->failureCount++;
        $this->lastFailureTime = time();
        if ($this->failureCount >= $this->failureThreshold) {
            $this->state = 'open';
        }
    }

    /**
     * Check if the reset timeout has elapsed since the last failure.
     *
     * @return bool True if the timeout has elapsed, false otherwise
     */
    private function isTimeoutReached()
    {
        return (time() - $this->lastFailureTime) > $this->resetTimeout;
    }

    /**
     * Reset the circuit breaker to the closed state and clear failure history.
     *
     * @return void
     */
    private function reset()
    {
        $this->state = 'closed';
        $this->failureCount = 0;
        $this->lastFailureTime = null;
    }
}
