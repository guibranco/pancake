# CircuitBreaker Usage Guide

The `CircuitBreaker` class is designed to help manage failures in a system by implementing the Circuit Breaker design pattern. This guide provides an overview of how to use the `CircuitBreaker` class in your application.

## Installation

Ensure that you have the `MemoryCache` class available in your application, as it is required for state persistence.

## Basic Usage

```php
use App\CircuitBreaker;
use App\Exceptions\CircuitBreakerOpenException;

$cache = new MemoryCache();
$circuitBreaker = new CircuitBreaker($cache, 3, 120); // failureThreshold = 3, resetTimeout = 120 seconds

try {
    $result = $circuitBreaker->execute(function() {
        // Your operation here
        return 'operation result';
    });
    echo $result;
} catch (CircuitBreakerOpenException $e) {
    echo 'Circuit is open. Please try again later.';
} catch (Exception $e) {
    echo 'Operation failed: ' . $e->getMessage();
}
```

## Configuration

- **failureThreshold**: The number of consecutive failures before the circuit opens.
- **resetTimeout**: The time in seconds to wait before transitioning from 'open' to 'half-open'.
