<?php

namespace GuiBranco\Pancake\Queue;

use Exception;

/**
 * QueueException is thrown when an AMQP queue operation fails.
 *
 * @package GuiBranco\Pancake\Queue
 */
class QueueException extends Exception
{
    /** @var string */
    private $operation;

    /**
     * @param string          $message   Human-readable error description.
     * @param string          $operation AMQP operation that failed (e.g. 'connect', 'publish', 'consume').
     * @param int             $code      Exception error code.
     * @param \Throwable|null $previous  Cause of this exception.
     */
    public function __construct($message, $operation = '', $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->operation = $operation;
    }

    /**
     * Returns the AMQP operation that triggered this exception.
     *
     * @return string
     */
    public function getOperation()
    {
        return $this->operation;
    }
}
