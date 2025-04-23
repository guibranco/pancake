# CircuitBreaker Usage Guide

The `CircuitBreaker` class helps you build resilient applications by managing failures gracefully using the [Circuit Breaker pattern](https://martinfowler.com/bliki/CircuitBreaker.html). It monitors operation failures and blocks repeated calls to unstable services until they show signs of recovery.

This guide walks you through installing, configuring, and using the `CircuitBreaker` class in your PHP applications.

---

## 💾 Installation

Make sure you have a caching layer that supports basic `get()` and `set()` methods for storing circuit state.

```php
// Example in-memory cache implementation for demonstration:
class MemoryCache {
    private array $store = [];

    public function get(string $key) {
        return $this->store[$key] ?? null;
    }

    public function set(string $key, $value): void {
        $this->store[$key] = $value;
    }
}
```

You can replace this with Redis, APCu, or any other caching mechanism in production.

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

## ⚙️ Configuration

When constructing the `CircuitBreaker`, you can fine-tune its behavior with these parameters:

| Parameter           | Type    | Default | Description |
|---------------------|---------|---------|-------------|
| `$cache`            | object  | —       | Cache instance for storing state |
| `$failureThreshold` | int     | `5`     | Number of consecutive failures before opening the circuit |
| `$resetTimeout`     | int     | `60`    | Time in seconds the circuit remains open before allowing another attempt |

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

- Use a persistent caching layer in production (e.g. Redis, Memcached).
- Consider wrapping I/O operations (API calls, DB queries) with `execute()`.
- Monitor failure rates and alert if circuit stays open for too long.
- Extend `CircuitBreaker` to log state changes or emit metrics.

---

## 📚 See Also

- [Martin Fowler on Circuit Breaker](https://martinfowler.com/bliki/CircuitBreaker.html)
- [PSR-16: Simple Cache Interface](https://www.php-fig.org/psr/psr-16/) (for real caching integrations)

---
