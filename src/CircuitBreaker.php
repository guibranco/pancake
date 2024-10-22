<?php

namespace App;

use App\Exceptions\CircuitBreakerOpenException;

class CircuitBreaker
{
    private $state;
    private $failureCount;
    private $lastFailureTime;
    private $failureThreshold;
    private $resetTimeout;
    private $cache;

    public function __construct($cache, $failureThreshold = 5, $resetTimeout = 60)
    {
        $this->cache = $cache;
        $this->failureThreshold = $failureThreshold;
        $this->resetTimeout = $resetTimeout;
        $this->loadState();
    }

    private function loadState()
    {
        $state = $this->cache->get('circuit_state');
        $this->state = $state['state'] ?? 'closed';
        $this->failureCount = $state['failureCount'] ?? 0;
        $this->lastFailureTime = $state['lastFailureTime'] ?? null;
    }

    private function saveState()
    {
        $this->cache->set('circuit_state', [
            'state' => $this->state,
            'failureCount' => $this->failureCount,
            'lastFailureTime' => $this->lastFailureTime,
        ]);
    }

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

    private function handleFailure()
    {
        $this->failureCount++;
        $this->lastFailureTime = time();
        if ($this->failureCount >= $this->failureThreshold) {
            $this->state = 'open';
        }
    }

    private function isTimeoutReached()
    {
        return (time() - $this->lastFailureTime) > $this->resetTimeout;
    }

    private function reset()
    {
        $this->state = 'closed';
        $this->failureCount = 0;
        $this->lastFailureTime = null;
    }
}

?>
