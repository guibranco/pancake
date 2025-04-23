# Circuit Breaker

## Overview

The `CircuitBreaker` class provides a simple implementation of the [Circuit Breaker pattern](https://martinfowler.com/bliki/CircuitBreaker.html), designed to prevent repeated failures in remote services or unstable code paths.

It monitors failures and halts execution of operations once a threshold is reached. After a defined timeout, it allows a single test execution to verify recovery.

---

## CircuitBreaker Class

### Constructor

#### `__construct($cache, int $failureThreshold = 5, int $resetTimeout = 60)`

When constructing the `CircuitBreaker`, you can fine-tune its behavior with these parameters:

| Parameter           | Type    | Default | Description |
|---------------------|---------|---------|-------------|
| `$cache`            | object  | —       | Cache instance for storing state |
| `$failureThreshold` | int     | `5`     | Number of consecutive failures before opening the circuit |
| `$resetTimeout`     | int     | `60`    | Time in seconds the circuit remains open before allowing another attempt |

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

## Circuit Breaker States

- **closed**: All operations are allowed. Failures are tracked.
- **open**: No operations are allowed. New attempts are blocked until the timeout expires.
- **half-open**: A single operation is allowed to test if recovery is possible. If it fails, the circuit returns to open.

---

## 🔁 State Transitions

The circuit can be in one of the following states:

- **`closed`**: Normal operation. Failures are counted.
- **`open`**: All operations are blocked. Timeout starts.
- **`half-open`**: One operation is allowed to test recovery.

Transition logic:

- If the operation succeeds in **half-open**, the circuit resets to **closed**.
- If the operation fails in **half-open**, it goes back to **open**.

---

## Exceptions

### `CircuitBreakerOpenException`

Thrown when an operation is attempted while the circuit is still open and the reset timeout has not yet been reached.

- **Namespace**: `App\Exceptions`
- **Extends**: `\Exception`
- **Usage**: Use this exception to catch and handle cases where service access should be deferred.

---

## 🚀 Basic Usage

```php
use App\CircuitBreaker;
use App\Exceptions\CircuitBreakerOpenException;

$cache = new MemoryCache(); // Replace with your actual cache implementation
$circuitBreaker = new CircuitBreaker($cache, 3, 120); // Allow 3 failures, 120s reset timeout

try {
    $result = $circuitBreaker->execute(function () {
        // Your risky operation (e.g. external API call)
        return 'operation result';
    });

    echo "Success: " . $result;
} catch (CircuitBreakerOpenException $e) {
    echo "⛔ Circuit is open: " . $e->getMessage();
} catch (\Exception $e) {
    echo "❌ Operation failed: " . $e->getMessage();
}
```

---

## 🧪 Testing and Debugging

For testing purposes, you can simulate failures or directly manipulate the cache to test state transitions:

```php
// Simulate state manually (e.g., for test cases)
$cache->set('circuit_state', [
    'state' => 'open',
    'failureCount' => 3,
    'lastFailureTime' => time() - 10, // Make timeout testable
]);
```

---

## 📎 Tips

- Ensure the provided `$cache` object persists data consistently (e.g., Redis, APCu, or file-based cache).
- Consider resetting state manually in your tests to avoid long waits.
- Extend the `CircuitBreaker` to add event hooks or logging if needed.

---

## 📚 See Also

- [Martin Fowler on Circuit Breaker](https://martinfowler.com/bliki/CircuitBreaker.html)
- [PSR-16: Simple Cache Interface](https://www.php-fig.org/psr/psr-16/) (for real caching integrations)

---
