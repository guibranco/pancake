<?php

declare(strict_types=1);

namespace GuiBranco\Pancake;

/**
 * LogStream client for the logstream-server.
 *
 * Supports both authentication modes:
 *
 * **File-storage / single-key mode:**
 * ```php
 * $client = new LogStream(
 *     baseUrl:   'https://logs.example.com',
 *     appKey:    'billing-api',
 *     appId:     'production',
 *     authMode:  LogStream::AUTH_BEARER,
 *     apiSecret: 'your-api-secret',
 * );
 * ```
 *
 * **MariaDB / per-client mode:**
 * ```php
 * $client = new LogStream(
 *     baseUrl:  'https://logs.example.com',
 *     appKey:   'billing-api',
 *     appId:    'production',
 *     authMode: LogStream::AUTH_API_KEY,
 *     apiKey:   'billing-api',
 *     apiToken: 'your-api-token',
 * );
 * ```
 *
 * Then log at any level:
 * ```php
 * $client->info('Service started');
 * $client->error('Charge failed', ['invoice_id' => 42], 'payments');
 *
 * // Batch
 * $client->batch([
 *     $client->makeEntry('Charge initiated', 'info',  'payments'),
 *     $client->makeEntry('Charge declined',  'error', 'payments', ['code' => 'card_declined']),
 * ]);
 * ```
 */
final class LogStream
{
    // ── Auth mode constants ───────────────────────────────────────────────────

    /**
     * File-storage mode: single `Authorization: Bearer <apiSecret>` header.
     * Use when `STORAGE_TYPE=file` on the server.
     */
    public const AUTH_BEARER  = 'bearer';

    /**
     * MariaDB mode: `X-Api-Key` + `X-Api-Token` headers.
     * Use when `STORAGE_TYPE=mariadb` on the server.
     */
    public const AUTH_API_KEY = 'api_key';

    // ── Endpoints ─────────────────────────────────────────────────────────────

    private const ENDPOINT_LOGS   = '/api/logs';
    private const ENDPOINT_HEALTH = '/api/health';

    // ──────────────────────────────────────────────────────────────────────────

    private readonly Request $request;
    private readonly string  $defaultAppId;
    private readonly string  $defaultAppKey;
    private readonly string  $authMode;

    // Bearer mode
    private readonly string $apiSecret;

    // API-key mode
    private readonly string $apiKey;
    private readonly string $apiToken;

    /** Optional default category applied to every entry sent through this client. */
    private string $defaultCategory = 'general';

    /** Optional User-Agent string sent on every request. */
    private string $userAgent;

    /**
     * @param string       $baseUrl    logstream-server base URL (no trailing slash)
     * @param string       $appKey     Application slug (e.g. "billing-api")
     * @param string       $appId      Deployment environment / instance (e.g. "production")
     * @param string       $authMode   LogStream::AUTH_BEARER or LogStream::AUTH_API_KEY
     * @param string       $apiSecret  Bearer token (AUTH_BEARER mode only)
     * @param string       $apiKey     X-Api-Key value (AUTH_API_KEY mode only)
     * @param string       $apiToken   X-Api-Token value (AUTH_API_KEY mode only)
     * @param Request|null $request    Inject a custom Request instance (useful for testing)
     * @param string       $userAgent  Sent as the User-Agent header on every request
     */
    public function __construct(
        string   $baseUrl,
        string   $appKey,
        string   $appId,
        string   $authMode  = self::AUTH_BEARER,
        string   $apiSecret = '',
        string   $apiKey    = '',
        string   $apiToken  = '',
        ?Request $request   = null,
        string   $userAgent = 'LogStream-PHP-Client/1.0',
    ) {
        if (!in_array($authMode, [self::AUTH_BEARER, self::AUTH_API_KEY], true)) {
            throw new \InvalidArgumentException(
                "Invalid authMode '{$authMode}'. Use LogStream::AUTH_BEARER or LogStream::AUTH_API_KEY."
            );
        }

        $this->defaultAppKey = $appKey;
        $this->defaultAppId  = $appId;
        $this->authMode      = $authMode;
        $this->apiSecret     = $apiSecret;
        $this->apiKey        = $apiKey;
        $this->apiToken      = $apiToken;
        $this->userAgent     = $userAgent;
        $this->request       = $request ?? new Request($baseUrl);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Configuration
    // ──────────────────────────────────────────────────────────────────────────

    /** Override the default category for all entries sent through this client. */
    public function setDefaultCategory(string $category): void
    {
        $this->defaultCategory = substr($category, 0, 100);
    }

    /** Override the User-Agent sent on every request. */
    public function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Per-level convenience methods
    // ──────────────────────────────────────────────────────────────────────────

    /** Send a DEBUG-level log entry. */
    public function debug(
        string  $message,
        ?array  $context  = null,
        string  $category = '',
        ?string $traceId  = null,
    ): LogStreamResponse {
        return $this->log($this->makeEntry($message, 'debug', $category ?: $this->defaultCategory, $context, $traceId));
    }

    /** Send an INFO-level log entry. */
    public function info(
        string  $message,
        ?array  $context  = null,
        string  $category = '',
        ?string $traceId  = null,
    ): LogStreamResponse {
        return $this->log($this->makeEntry($message, 'info', $category ?: $this->defaultCategory, $context, $traceId));
    }

    /** Send a NOTICE-level log entry. */
    public function notice(
        string  $message,
        ?array  $context  = null,
        string  $category = '',
        ?string $traceId  = null,
    ): LogStreamResponse {
        return $this->log($this->makeEntry($message, 'notice', $category ?: $this->defaultCategory, $context, $traceId));
    }

    /** Send a WARNING-level log entry. */
    public function warning(
        string  $message,
        ?array  $context  = null,
        string  $category = '',
        ?string $traceId  = null,
    ): LogStreamResponse {
        return $this->log($this->makeEntry($message, 'warning', $category ?: $this->defaultCategory, $context, $traceId));
    }

    /** Send an ERROR-level log entry. */
    public function error(
        string  $message,
        ?array  $context  = null,
        string  $category = '',
        ?string $traceId  = null,
    ): LogStreamResponse {
        return $this->log($this->makeEntry($message, 'error', $category ?: $this->defaultCategory, $context, $traceId));
    }

    /** Send a CRITICAL-level log entry. */
    public function critical(
        string  $message,
        ?array  $context  = null,
        string  $category = '',
        ?string $traceId  = null,
    ): LogStreamResponse {
        return $this->log($this->makeEntry($message, 'critical', $category ?: $this->defaultCategory, $context, $traceId));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Core ingest methods
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Send a single log entry to the server.
     */
    public function log(LogStreamEntry $entry): LogStreamResponse
    {
        $payload = $this->buildSinglePayload($entry);

        $response = $this->request->post(
            self::ENDPOINT_LOGS,
            $this->buildHeaders(),
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );

        return LogStreamResponse::fromResponse($response);
    }

    /**
     * Send multiple log entries as a batch request.
     *
     * All entries in the batch will share the same `batch_id` unless they
     * already have one set. The batch_id is generated as a UUID v4.
     *
     * @param LogStreamEntry[] $entries
     */
    public function batch(array $entries, ?string $batchId = null): LogStreamResponse
    {
        if (empty($entries)) {
            throw new \InvalidArgumentException('Batch must contain at least one entry.');
        }

        $batchId ??= $this->uuid4();

        $stamped = array_map(
            fn (LogStreamEntry $e) => $e->batchId === null ? $e->withBatchId($batchId) : $e,
            $entries,
        );

        $payload = [
            'app_key'  => $this->defaultAppKey,
            'app_id'   => $this->defaultAppId,
            'batch_id' => $batchId,
            'logs'     => array_map(fn (LogStreamEntry $e) => $e->toArray(), $stamped),
        ];

        $response = $this->request->post(
            self::ENDPOINT_LOGS,
            $this->buildHeaders(),
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );

        return LogStreamResponse::fromResponse($response);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Health check
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Ping the server health endpoint.
     *
     * @return array{status: string, time: string, ws_connections: int}|null
     *         Returns null if the request failed or response is not JSON.
     */
    public function health(): ?array
    {
        $response = $this->request->get(self::ENDPOINT_HEALTH, [
            "User-Agent: {$this->userAgent}",
            'Accept: application/json',
        ]);

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        return json_decode($response->getBody(), true) ?: null;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Factory helper
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Build a LogStreamEntry pre-filled with this client's defaults.
     * Use when constructing entries for a ::batch() call.
     */
    public function makeEntry(
        string  $message,
        string  $level    = 'info',
        string  $category = '',
        ?array  $context  = null,
        ?string $traceId  = null,
    ): LogStreamEntry {
        return new LogStreamEntry(
            appId:    $this->defaultAppId,
            level:    $level,
            message:  $message,
            category: $category ?: $this->defaultCategory,
            context:  $context,
            appKey:   $this->defaultAppKey,
            traceId:  $traceId,
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    /** Build the correct authentication + common headers for the current mode. */
    private function buildHeaders(): array
    {
        $headers = [
            "User-Agent: {$this->userAgent}",
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($this->authMode === self::AUTH_BEARER) {
            $headers[] = "Authorization: Bearer {$this->apiSecret}";
        } else {
            $headers[] = "X-Api-Key: {$this->apiKey}";
            $headers[] = "X-Api-Token: {$this->apiToken}";
        }

        return $headers;
    }

    /** Build the single-entry payload, injecting client-level defaults. */
    private function buildSinglePayload(LogStreamEntry $entry): array
    {
        $data = $entry->toArray();

        // Ensure app_key is always present in bearer mode
        if (!isset($data['app_key'])) {
            $data['app_key'] = $this->defaultAppKey;
        }

        // Ensure app_id is always present
        if (!isset($data['app_id'])) {
            $data['app_id'] = $this->defaultAppId;
        }

        return $data;
    }

    private function uuid4(): string
    {
        $bytes    = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
