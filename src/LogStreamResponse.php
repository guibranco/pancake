<?php

declare(strict_types=1);

namespace GuiBranco\Pancake;

/**
 * Typed wrapper around the raw pancake Response for logstream-server responses.
 *
 * Properties map directly to the JSON fields returned by the server.
 */
final class LogStreamResponse
{
    /** HTTP status code returned by the server */
    public readonly int $statusCode;

    /** True when the request was accepted (HTTP 2xx) */
    public readonly bool $success;

    /** Number of entries saved in this request */
    public readonly int $saved;

    /** The ingested log entries as returned by the server (filled on ingest) */
    public readonly array $entries;

    /** Per-entry error messages for any entries that failed to save */
    public readonly ?array $errors;

    /** Error message when the cURL request itself failed (statusCode === -1) */
    public readonly ?string $transportError;

    /** Raw response body */
    public readonly string $rawBody;

    public function __construct(
        int     $statusCode,
        string  $rawBody,
        ?string $transportError = null,
    ) {
        $this->statusCode     = $statusCode;
        $this->rawBody        = $rawBody;
        $this->transportError = $transportError;
        $this->success        = $statusCode >= 200 && $statusCode < 300;

        $decoded = json_decode($rawBody, true) ?? [];

        $this->saved   = (int)  ($decoded['saved']   ?? 0);
        $this->entries = (array)($decoded['entries'] ?? []);
        $this->errors  = isset($decoded['errors']) && is_array($decoded['errors'])
            ? $decoded['errors']
            : null;
    }

    // ──────────────────────────────────────────────────────────────────────────

    /** Build from a pancake Response object. */
    public static function fromResponse(object $response): self
    {
        $transportError = ($response->getStatusCode() === -1 && $response->getMessage() !== null)
            ? $response->getMessage()
            : null;

        return new self(
            statusCode:     $response->getStatusCode(),
            rawBody:        $response->getBody() ?? '',
            transportError: $transportError,
        );
    }
}
