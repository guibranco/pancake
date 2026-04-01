<?php

declare(strict_types=1);

namespace GuiBranco\Pancake;

/**
 * Represents a single log entry to be sent to the logstream-server.
 *
 * Build via the static factory methods on LogStream, or construct directly:
 *
 * ```php
 * $entry = new LogStreamEntry(
 *     appId:    'production',
 *     level:    'error',
 *     category: 'payments',
 *     message:  'Charge failed',
 *     context:  ['invoice_id' => 1234],
 * );
 * ```
 *
 * When sending a batch, individual entries may override the client-level
 * `appId` and `category` by setting their own values.
 */
final class LogStreamEntry
{
    public const LEVELS = ['debug', 'info', 'notice', 'warning', 'error', 'critical'];

    public readonly string $level;
    public readonly string $category;

    /**
     * @param string       $appId     Deployment environment / instance identifier (e.g. "production")
     * @param string       $level     Severity level — one of debug|info|notice|warning|error|critical
     * @param string       $message   Human-readable event description
     * @param string       $category  Short grouping tag (max 100 chars, default "general")
     * @param array|null   $context   Arbitrary structured metadata
     * @param string|null  $appKey    Application slug — only needed in file-storage (single-key) mode;
     *                                in MariaDB mode the server resolves this from the clients table
     * @param string|null  $traceId   Client-supplied UUID correlation ID; auto-generated server-side if omitted
     * @param string|null  $batchId   Batch / request group ID; set automatically when using LogStream::batch()
     * @param string|null  $timestamp ISO 8601 timestamp of when the event occurred; defaults to now
     * @param string|null  $userAgent Override for the User-Agent header value sent for this entry
     */
    public function __construct(
        public readonly string  $appId,
        string                  $level,
        public readonly string  $message,
        string                  $category = 'general',
        public readonly ?array  $context   = null,
        public readonly ?string $appKey    = null,
        public readonly ?string $traceId   = null,
        public readonly ?string $batchId   = null,
        public readonly ?string $timestamp = null,
        public readonly ?string $userAgent = null,
    ) {
        $this->level    = in_array($level, self::LEVELS, true) ? $level : 'info';
        $this->category = substr($category, 0, 100);
    }

    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Serialise to the array shape expected by the logstream-server ingest endpoint.
     * Null / empty fields are omitted so the server can apply its own defaults.
     */
    public function toArray(): array
    {
        $data = [
            'app_id'   => $this->appId,
            'level'    => $this->level,
            'category' => $this->category,
            'message'  => $this->message,
        ];

        if ($this->appKey    !== null) {
            $data['app_key']    = $this->appKey;
        }
        if ($this->traceId   !== null) {
            $data['trace_id']   = $this->traceId;
        }
        if ($this->batchId   !== null) {
            $data['batch_id']   = $this->batchId;
        }
        if ($this->context   !== null) {
            $data['context']    = $this->context;
        }
        if ($this->timestamp !== null) {
            $data['timestamp']  = $this->timestamp;
        }
        if ($this->userAgent !== null) {
            $data['user_agent'] = $this->userAgent;
        }

        return $data;
    }

    // ──────────────────────────────────────────────────────────────────────────

    /** Create a new entry with the given batch ID stamped on it. */
    public function withBatchId(string $batchId): self
    {
        return new self(
            appId:     $this->appId,
            level:     $this->level,
            message:   $this->message,
            category:  $this->category,
            context:   $this->context,
            appKey:    $this->appKey,
            traceId:   $this->traceId,
            batchId:   $batchId,
            timestamp: $this->timestamp,
            userAgent: $this->userAgent,
        );
    }
}
