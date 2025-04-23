<?php

namespace App\Exceptions;

use Exception;

/**
 * Class CircuitBreakerOpenException
 *
 * Exception thrown when a circuit is open and an operation is attempted.
 * This is part of the Circuit Breaker pattern and indicates that further attempts
 * should be deferred until the circuit closes or moves to a half-open state.
 */
class CircuitBreakerOpenException extends Exception
{
}
