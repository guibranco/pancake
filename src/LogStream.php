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
 * **UDP transport** (fire-and-forget, one HMAC-signed datagram per entry):
 * ```php
 * $client = new LogStream(
 *     baseUrl:   'https://logs.example.com',
 *     appKey:    'billing-api',
 *     appId:     'production',
 *     authMode:  LogStream::AUTH_API_KEY,
 *     apiKey:    'billing-api',
 *     apiToken:  'your-api-token',
 *     transport: LogStream::TRANSPORT_UDP,
 *     udpPort:   8082,
 * );
 * ```
 * The packet is signed with the same write secret (`apiToken`, or `apiSecret`
 * in bearer mode), which never travels on the wire. Entries too large for one
 * datagram are sent over HTTP instead (see `$udpFallbackToHttp`).
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

    // ── Transport constants ───────────────────────────────────────────────────

    /** Send entries with `POST /api/logs` (the server confirms what it saved). */
    public const TRANSPORT_HTTP = 'http';

    /**
     * Send each entry as one HMAC-signed UDP datagram. Fire-and-forget: the
     * server never replies, so delivery is not confirmed.
     */
    public const TRANSPORT_UDP = 'udp';

    /** The server's default UDP_PORT. */
    public const DEFAULT_UDP_PORT = 8082;

    /** The server's default UDP_MAX_PACKET_SIZE, in bytes. */
    public const DEFAULT_UDP_MAX_PACKET_SIZE = 8192;

    /** The largest payload a UDP datagram can carry over IPv4. */
    public const MAX_UDP_DATAGRAM_SIZE = 65507;

    private const UDP_PROTOCOL_VERSION = 1;

    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

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

    // Transport
    private readonly string $transport;
    private readonly string $udpHost;
    private readonly int    $udpPort;
    private readonly int    $udpMaxPacketSize;
    private readonly bool   $udpFallbackToHttp;

    /** @var \Closure(string): ?string|null */
    private readonly ?\Closure $udpSender;

    /** @var resource|null Lazily opened, reused for every datagram. */
    private $udpSocket = null;

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
     *                                 (and as `user_agent` in UDP entries that don't set one)
     * @param string       $transport  LogStream::TRANSPORT_HTTP (default) or LogStream::TRANSPORT_UDP
     * @param string|null  $udpHost    UDP host; defaults to the host of $baseUrl
     * @param int          $udpPort    UDP port (the server's UDP_PORT, default 8082)
     * @param int          $udpMaxPacketSize  Largest datagram to send; match the server's
     *                                 UDP_MAX_PACKET_SIZE (default 8192)
     * @param bool         $udpFallbackToHttp Send entries too large for one datagram over HTTP
     *                                 instead of dropping them (default true)
     * @param callable|null $udpSender Sends one datagram: fn(string $packet): ?string, returning
     *                                 null on success or an error message (useful for testing)
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
        string   $userAgent = Constants::USER_AGENT_VENDOR,
        string   $transport = self::TRANSPORT_HTTP,
        ?string  $udpHost   = null,
        int      $udpPort   = self::DEFAULT_UDP_PORT,
        int      $udpMaxPacketSize  = self::DEFAULT_UDP_MAX_PACKET_SIZE,
        bool     $udpFallbackToHttp = true,
        ?callable $udpSender = null,
    ) {
        if (!in_array($authMode, [self::AUTH_BEARER, self::AUTH_API_KEY], true)) {
            throw new \InvalidArgumentException(
                "Invalid authMode '{$authMode}'. Use LogStream::AUTH_BEARER or LogStream::AUTH_API_KEY."
            );
        }

        if (!in_array($transport, [self::TRANSPORT_HTTP, self::TRANSPORT_UDP], true)) {
            throw new \InvalidArgumentException(
                "Invalid transport '{$transport}'. Use LogStream::TRANSPORT_HTTP or LogStream::TRANSPORT_UDP."
            );
        }

        if ($transport === self::TRANSPORT_UDP) {
            if ($udpPort < 1 || $udpPort > 65535) {
                throw new \InvalidArgumentException("udpPort must be between 1 and 65535, got {$udpPort}.");
            }

            if ($udpMaxPacketSize < 256 || $udpMaxPacketSize > self::MAX_UDP_DATAGRAM_SIZE) {
                throw new \InvalidArgumentException(
                    'udpMaxPacketSize must be between 256 and ' . self::MAX_UDP_DATAGRAM_SIZE . ", got {$udpMaxPacketSize}."
                );
            }

            $signingKey    = $authMode === self::AUTH_BEARER ? $appKey : $apiKey;
            $signingSecret = $authMode === self::AUTH_BEARER ? $apiSecret : $apiToken;
            if ($signingKey === '' || $signingSecret === '') {
                throw new \InvalidArgumentException(
                    $authMode === self::AUTH_BEARER
                        ? 'UDP transport in bearer mode requires appKey and apiSecret.'
                        : 'UDP transport in API-key mode requires apiKey and apiToken.'
                );
            }

            $udpHost ??= (string) parse_url($baseUrl, PHP_URL_HOST);
            if ($udpHost === '') {
                throw new \InvalidArgumentException("udpHost is required when baseUrl '{$baseUrl}' has no host.");
            }
        }

        $this->defaultAppKey = $appKey;
        $this->defaultAppId  = $appId;
        $this->authMode      = $authMode;
        $this->apiSecret     = $apiSecret;
        $this->apiKey        = $apiKey;
        $this->apiToken      = $apiToken;
        $this->userAgent     = $userAgent;
        $this->request       = $request ?? new Request($baseUrl);

        $this->transport         = $transport;
        $this->udpHost           = $udpHost ?? '';
        $this->udpPort           = $udpPort;
        $this->udpMaxPacketSize  = $udpMaxPacketSize;
        $this->udpFallbackToHttp = $udpFallbackToHttp;
        $this->udpSender         = $udpSender !== null ? \Closure::fromCallable($udpSender) : null;
    }

    public function __destruct()
    {
        if (is_resource($this->udpSocket)) {
            fclose($this->udpSocket);
        }
    }

    /** The transport in use: LogStream::TRANSPORT_HTTP or LogStream::TRANSPORT_UDP. */
    public function getTransport(): string
    {
        return $this->transport;
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
     *
     * Over UDP the response only says whether the datagram left this host
     * (status 202, `sent` = 1); the server never confirms it.
     */
    public function log(LogStreamEntry $entry): LogStreamResponse
    {
        if ($this->transport === self::TRANSPORT_UDP) {
            return $this->sendUdp([$entry], null);
        }

        return $this->postSingle($entry);
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

        if ($this->transport === self::TRANSPORT_UDP) {
            return $this->sendUdp(array_values($stamped), $batchId);
        }

        return $this->postBatch(array_values($stamped), $batchId);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // UDP packet format
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Build one signed UDP packet (protocol v1):
     *
     *   {"v":1,"key":"…","ts":<ms>,"nonce":"…","sig":"<hex>","log":{…}}
     *
     * `sig` is hex(HMAC-SHA256(secret, "{key}:{ts}:{nonce}:{log}")), where
     * {log} is the exact JSON text embedded in the packet.
     *
     * @param string      $appKey      The app_key (the client's API key in MariaDB mode)
     * @param string      $secret      The api_token (MariaDB) or API_SECRET (file mode)
     * @param array       $log         One entry, as sent to POST /api/logs (`app_id` required)
     * @param int|null    $timestampMs Unix time in milliseconds; defaults to now
     * @param string|null $nonce       16–64 characters of A-Z a-z 0-9 _ -; defaults to 32 random hex characters
     */
    public static function buildUdpPacket(
        string  $appKey,
        string  $secret,
        array   $log,
        ?int    $timestampMs = null,
        ?string $nonce       = null,
    ): string {
        $flags   = self::JSON_FLAGS | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR;
        $ts      = $timestampMs ?? (int) floor(microtime(true) * 1000);
        $nonce ??= bin2hex(random_bytes(16));
        $logJson = json_encode((object) $log, $flags);
        $sig     = hash_hmac('sha256', "{$appKey}:{$ts}:{$nonce}:{$logJson}", $secret);

        // Built by hand so the signed log text is embedded byte for byte.
        return '{"v":' . self::UDP_PROTOCOL_VERSION
            . ',"key":' . json_encode($appKey, $flags)
            . ',"ts":' . $ts
            . ',"nonce":' . json_encode($nonce, $flags)
            . ',"sig":"' . $sig . '"'
            . ',"log":' . $logJson
            . '}';
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

    /** POST one entry to /api/logs. */
    private function postSingle(LogStreamEntry $entry): LogStreamResponse
    {
        $response = $this->request->post(
            self::ENDPOINT_LOGS,
            $this->buildHeaders(),
            json_encode($this->buildSinglePayload($entry), self::JSON_FLAGS),
        );

        return LogStreamResponse::fromResponse($response);
    }

    /**
     * POST entries (already stamped with a batch ID) to /api/logs.
     *
     * @param LogStreamEntry[] $entries
     */
    private function postBatch(array $entries, string $batchId): LogStreamResponse
    {
        $payload = [
            'app_key'  => $this->defaultAppKey,
            'app_id'   => $this->defaultAppId,
            'batch_id' => $batchId,
            'logs'     => array_map(fn (LogStreamEntry $e) => $e->toArray(), $entries),
        ];

        $response = $this->request->post(
            self::ENDPOINT_LOGS,
            $this->buildHeaders(),
            json_encode($payload, self::JSON_FLAGS),
        );

        return LogStreamResponse::fromResponse($response);
    }

    /**
     * Send each entry as one datagram. Entries over the size limit go over HTTP
     * (one request) when $udpFallbackToHttp is on, or are reported as errors.
     *
     * Status: 202 when anything was sent (errors lists what wasn't), -1 when
     * nothing was. If every entry went over HTTP, the HTTP response is returned.
     *
     * @param LogStreamEntry[] $entries
     */
    private function sendUdp(array $entries, ?string $batchId): LogStreamResponse
    {
        $sent      = 0;
        $errors    = [];
        $oversized = [];

        foreach ($entries as $i => $entry) {
            $packet = self::buildUdpPacket($this->udpKey(), $this->udpSecret(), $this->buildUdpLog($entry));
            $size   = strlen($packet);

            if ($size > $this->udpMaxPacketSize) {
                if ($this->udpFallbackToHttp) {
                    $oversized[] = $entry;
                } else {
                    $errors[] = "Entry #{$i}: packet is {$size} bytes, over the {$this->udpMaxPacketSize}-byte UDP limit";
                }
                continue;
            }

            $error = $this->sendDatagram($packet);
            if ($error === null) {
                $sent++;
            } else {
                $errors[] = "Entry #{$i}: {$error}";
            }
        }

        $http = null;
        if ($oversized !== []) {
            $http = $batchId === null && count($oversized) === 1
                ? $this->postSingle($oversized[0])
                : $this->postBatch($oversized, $batchId ?? $this->uuid4());

            if ($sent === 0 && $errors === []) {
                return $http;
            }

            if (!$http->success) {
                $errors[] = sprintf(
                    'HTTP fallback for %d oversized entr%s failed: %s',
                    count($oversized),
                    count($oversized) === 1 ? 'y' : 'ies',
                    $http->transportError ?? "HTTP {$http->statusCode}",
                );
            }
        }

        $anyDelivered = $sent > 0 || ($http !== null && $http->success);

        return new LogStreamResponse(
            statusCode:     $anyDelivered ? 202 : -1,
            rawBody:        $http?->rawBody ?? '',
            transportError: $anyDelivered ? null : ($errors[0] ?? 'Nothing was sent'),
            sent:           $sent,
            extraErrors:    $errors,
        );
    }

    /** The entry as embedded in a UDP packet. */
    private function buildUdpLog(LogStreamEntry $entry): array
    {
        $data = $entry->toArray();

        // The packet's key identifies the app; the server ignores app_key here.
        unset($data['app_key']);

        if (!isset($data['app_id']) || $data['app_id'] === '') {
            $data['app_id'] = $this->defaultAppId;
        }

        // There is no User-Agent header over UDP.
        if (!isset($data['user_agent'])) {
            $data['user_agent'] = $this->userAgent;
        }

        return $data;
    }

    private function udpKey(): string
    {
        return $this->authMode === self::AUTH_BEARER ? $this->defaultAppKey : $this->apiKey;
    }

    private function udpSecret(): string
    {
        return $this->authMode === self::AUTH_BEARER ? $this->apiSecret : $this->apiToken;
    }

    /** Returns null on success, or an error message. */
    private function sendDatagram(string $packet): ?string
    {
        if ($this->udpSender !== null) {
            return ($this->udpSender)($packet);
        }

        // A connected UDP socket can report a previous ICMP "port unreachable"
        // on the next write: reopen once before giving up.
        for ($attempt = 0; $attempt < 2; $attempt++) {
            if (!is_resource($this->udpSocket)) {
                $errno  = 0;
                $errstr = '';
                $socket = @stream_socket_client("udp://{$this->udpHost}:{$this->udpPort}", $errno, $errstr);
                if ($socket === false) {
                    return "Could not open UDP socket to {$this->udpHost}:{$this->udpPort}: {$errstr} ({$errno})";
                }
                $this->udpSocket = $socket;
            }

            $written = @fwrite($this->udpSocket, $packet);
            if ($written === strlen($packet)) {
                return null;
            }

            fclose($this->udpSocket);
            $this->udpSocket = null;
        }

        return "Could not send UDP datagram to {$this->udpHost}:{$this->udpPort}";
    }

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
