<?php

namespace GuiBranco\Pancake;

use Exception;

/**
 * DatabaseException is thrown when database operations fail.
 *
 * @package GuiBranco\Pancake
 */
class DatabaseException extends Exception
{
    /** @var string */
    private $operation;

    /**
     * @param string $message Error message
     * @param string $operation Database operation that failed
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message,
        string $operation = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->operation = $operation;
    }

    /**
     * Get the database operation that failed
     *
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }
}
