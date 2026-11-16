<?php

declare(strict_types=1);

namespace GuiBranco\Pancake;

/**
 * Thrown by {@see Grimoire} when the Grimoire API cannot be reached, rejects the
 * credentials, or returns an unexpected response.
 *
 * A missing secret or configuration key is **not** an exception: the client returns
 * `null` (or the supplied default) instead.
 */
class GrimoireException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    /** HTTP status code returned by the API, `-1` for transport errors, `0` when not applicable. */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
