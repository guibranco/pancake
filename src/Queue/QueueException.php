<?php

namespace GuiBranco\Pancake\Queue;

use Exception;
use Throwable;

/**
 * QueueException is thrown when queue operations fail.
 *
 * @package GuiBranco\Pancake\Queue
 */
class QueueException extends Exception
{
    private string $operation;

    /**
     * @param string $message Error message
     * @param string $operation Queue operation that failed (e.g. "connection", "publish", "consume", "retry")
     * @param int $code Error code
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message,
        string $operation = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->operation = $operation;
    }

    /**
     * Get the queue operation that failed
     *
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }
}
