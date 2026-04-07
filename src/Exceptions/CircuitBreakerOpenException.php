<?php

namespace GuiBranco\Pancake\Exceptions;

use Exception;

/**
 * Class CircuitBreakerOpenException
 *
 * Thrown by {@see \GuiBranco\Pancake\CircuitBreaker::execute()} when an operation
 * is attempted while the circuit is in the **open** state and the reset timeout
 * has not yet elapsed.
 *
 * Callers should catch this exception to implement back-off logic, return a cached
 * fallback response, or surface a service-unavailable error to the caller.
 *
 * ### Example
 *
 * ```php
 * try {
 *     $result = $circuitBreaker->execute(fn() => $apiClient->fetch('/endpoint'));
 * } catch (CircuitBreakerOpenException $e) {
 *     // The circuit is still open — avoid hammering the downstream service.
 *     return $cachedFallback;
 * }
 * ```
 *
 * The exception message always includes a human-readable timestamp indicating
 * when the circuit is expected to transition to the *half-open* state, as well
 * as the remaining wait time in seconds.
 *
 * @package GuiBranco\Pancake\Exceptions
 */
class CircuitBreakerOpenException extends Exception
{
}
