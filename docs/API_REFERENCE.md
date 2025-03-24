# CircuitBreaker API Reference

## CircuitBreaker Class

### Methods

#### `__construct($cache, $failureThreshold = 5, $resetTimeout = 60)`
Constructor to initialize the CircuitBreaker.

- **Parameters**:
  - `$cache`: An instance of `MemoryCache` for state persistence.
  - `$failureThreshold`: (Optional) Number of failures before opening the circuit. Default is 5.
  - `$resetTimeout`: (Optional) Time in seconds to wait before transitioning from 'open' to 'half-open'. Default is 60.

#### `execute(callable $operation)`
Executes the given operation if the circuit is closed or half-open.

- **Parameters**:
  - `$operation`: A callable operation to execute.
- **Throws**:
  - `CircuitBreakerOpenException` if the circuit is open and the reset timeout has not been reached.
- **Returns**: The result of the operation if successful.

### Exceptions

#### `CircuitBreakerOpenException`
Exception thrown when an operation is attempted while the circuit is open.

## Example

```php
$cache = new MemoryCache();
$circuitBreaker = new CircuitBreaker($cache);

try {
    $result = $circuitBreaker->execute(function() {
        // Your operation here
    });
} catch (CircuitBreakerOpenException $e) {
    // Handle open circuit
}
```
