<?php

// src/CircuitBreakerOpenException.php

namespace YourProjectNamespace;

/**
 * Exception thrown when an operation is attempted while the Circuit Breaker is in the 'open' state.
 */
class CircuitBreakerOpenException extends \Exception
{
}