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

    /** Raw response body (empty when nothing was sent over HTTP) */
    public readonly string $rawBody;

    /**
     * Number of entries sent as UDP datagrams (always 0 over HTTP).
     *
     * UDP is fire-and-forget: the server never replies, so a sent entry is not
     * confirmed as saved and is not counted in $saved.
     */
    public readonly int $sent;

    /**
     * @param string[] $extraErrors Client-side errors (e.g. UDP send failures) added to $errors
     */
    public function __construct(
        int     $statusCode,
        string  $rawBody,
        ?string $transportError = null,
        int     $sent = 0,
        array   $extraErrors = [],
    ) {
        $this->statusCode     = $statusCode;
        $this->rawBody        = $rawBody;
        $this->transportError = $transportError;
        $this->success        = $statusCode >= 200 && $statusCode < 300;
        $this->sent           = $sent;

        $decoded = json_decode($rawBody, true) ?? [];

        $this->saved   = (int)  ($decoded['saved']   ?? 0);
        $this->entries = (array)($decoded['entries'] ?? []);
        $errors = isset($decoded['errors']) && is_array($decoded['errors'])
            ? $decoded['errors']
            : null;

        if ($extraErrors !== []) {
            $errors = array_merge($errors ?? [], array_values($extraErrors));
        }

        $this->errors = $errors;
    }

    // ──────────────────────────────────────────────────────────────────────────

    /** Build from a pancake Response object. */
    public static function fromResponse(Response $response): self
    {
        // getMessage() returns string (never null); treat empty string as no error.
        $message        = $response->getMessage();
        $transportError = ($response->getStatusCode() === -1 && $message !== '')
            ? $message
            : null;

        return new self(
            statusCode:     $response->getStatusCode(),
            rawBody:        $response->getBody() ?? '',
            transportError: $transportError,
        );
    }
}
