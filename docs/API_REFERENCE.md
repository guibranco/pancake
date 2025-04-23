# CircuitBreaker API Reference

## Overview

The `CircuitBreaker` class provides a simple implementation of the [Circuit Breaker pattern](https://martinfowler.com/bliki/CircuitBreaker.html), designed to prevent repeated failures in remote services or unstable code paths.

It monitors failures and halts execution of operations once a threshold is reached. After a defined timeout, it allows a single test execution to verify recovery.

---

## CircuitBreaker Class

### Constructor

#### `__construct($cache, int $failureThreshold = 5, int $resetTimeout = 60)`

Initializes the CircuitBreaker with the given configuration and loads its state from the provided cache.

- **Parameters**:
  - `$cache` *(object)*: A cache object implementing `get()` and `set()` methods for state persistence.
  - `$failureThreshold` *(int)*: Optional. Maximum number of allowed failures before opening the circuit. Defaults to `5`.
  - `$resetTimeout` *(int)*: Optional. Timeout in seconds before transitioning the circuit from `'open'` to `'half-open'`. Defaults to `60`.

---

### Public Methods

#### `execute(callable $operation): mixed`

Executes a protected operation within the circuit breaker context.

- **Parameters**:
  - `$operation` *(callable)*: A function to be executed (e.g., a database query or HTTP request).
- **Returns**: The return value of the callable if successful.
- **Throws**:
  - `CircuitBreakerOpenException` if the circuit is open and the timeout has not expired.
  - Re-throws any exception raised by the `$operation`.

---

## CircuitBreaker States

- **closed**: All operations are allowed. Failures are tracked.
- **open**: No operations are allowed. New attempts are blocked until the timeout expires.
- **half-open**: A single operation is allowed to test if recovery is possible. If it fails, the circuit returns to open.

---

## Exceptions

### `CircuitBreakerOpenException`

Thrown when an operation is attempted while the circuit is still open and the reset timeout has not yet been reached.

- **Namespace**: `App\Exceptions`
- **Extends**: `\Exception`
- **Usage**: Use this exception to catch and handle cases where service access should be deferred.

---

## Example Usage

```php
use App\CircuitBreaker;
use App\Exceptions\CircuitBreakerOpenException;

$cache = new MemoryCache(); // Replace with your preferred caching implementation
$circuitBreaker = new CircuitBreaker($cache);

try {
    $result = $circuitBreaker->execute(function() {
        // Simulate risky operation (e.g. remote API call)
        return file_get_contents('https://api.example.com/data');
    });

    echo "Operation successful: " . $result;
} catch (CircuitBreakerOpenException $e) {
    echo "Circuit is open: " . $e->getMessage();
} catch (\Exception $e) {
    echo "Operation failed: " . $e->getMessage();
}
```

---

## Notes

- Ensure the provided `$cache` object persists data consistently (e.g., Redis, APCu, or file-based cache).
- Consider resetting state manually in your tests to avoid long waits.
- Extend the `CircuitBreaker` to add event hooks or logging if needed.

---
