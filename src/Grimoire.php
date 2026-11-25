<?php

declare(strict_types=1);

namespace GuiBranco\Pancake;

/**
 * Client for the Grimoire Consumer API (https://github.com/guibranco/grimoire-api).
 *
 * Reads secrets and configuration for one application and environment. Values are the
 * *effective* ones: the application's own value, falling back to global values when the
 * application inherits globals.
 *
 * ```php
 * $grimoire = new Grimoire(
 *     baseUrl:     'https://api.grimoire.example.com',
 *     apiKey:      getenv('GRIMOIRE_API_KEY'),
 *     environment: 'production',
 * );
 *
 * $password = $grimoire->getSecretValue('database-password');
 * $retries  = (int) $grimoire->getConfiguration('MaxRetries', '3');
 *
 * // Or load everything into getenv()/$_ENV at bootstrap:
 * $grimoire->loadIntoEnvironment(['database-password', 'smtp-password']);
 * ```
 *
 * With `refreshInterval`, configuration follows Grimoire's **sentinel keys** instead of a fixed
 * TTL (guibranco/grimoire-api#95): the values are kept in a snapshot shared by all requests
 * (APCu when available, otherwise files), and at most once per interval one worker checks
 * `Sentinel` and `Global:Sentinel` with a conditional request. Only when one of them changed
 * (Grimoire published changes) is the configuration fetched again.
 */
final class Grimoire
{
    private const ENDPOINT_SECRETS        = '/consumer/secrets/';
    private const ENDPOINT_CONFIGURATIONS = '/consumer/configurations';
    private const ENDPOINT_HEALTH         = '/health';

    /** Keys watched by default in sentinel refresh mode. */
    public const SENTINEL_KEYS = ['Sentinel', 'Global:Sentinel'];

    /** Longest wait between checks while Grimoire is failing, in seconds. */
    private const MAX_BACKOFF = 300;

    private readonly Request $request;

    private readonly \Closure $clock;

    /** @var array<string, array{expires: int, value: GrimoireSecret}> */
    private array $secretCache = [];

    /** @var array{expires: int, value: array<string, string>}|null */
    private ?array $configurationCache = null;

    /** @var array<string, mixed>|null this process's copy of the shared snapshot */
    private ?array $snapshot = null;

    private ?GrimoireSnapshotStore $snapshotStore;

    /** @var string[] */
    private readonly array $sentinelKeys;

    /** Shared-cache key: one snapshot per Grimoire URL, API key and environment. */
    private readonly string $cacheKey;

    /**
     * @param string        $baseUrl     Grimoire API base URL (e.g. https://api.grimoire.example.com)
     * @param string        $apiKey      Application API key (sent as X-Api-Key)
     * @param string        $environment Environment slug (e.g. "production")
     * @param Request|null  $request     Inject a custom Request instance (useful for testing)
     * @param int           $cacheTtl    Seconds to keep values in memory for this instance; 0 disables caching
     * @param string        $userAgent   Sent as the User-Agent header
     * @param \Closure|null $clock       Returns the current Unix time; injectable for tests
     * @param int           $timeout     Total timeout of each request, in seconds, when the client
     *                                   creates its own Request (an injected Request keeps its own)
     * @param int           $refreshInterval Seconds between sentinel checks; 0 (default) keeps the
     *                                   plain per-instance TTL cache. When set, configuration is
     *                                   cached in $snapshotStore and refetched only after Grimoire
     *                                   publishes changes, and $cacheTtl applies to secrets only
     * @param GrimoireSnapshotStore|null $snapshotStore Where the configuration snapshot is shared;
     *                                   null picks APCu when available, otherwise a file store in
     *                                   the system temp directory
     * @param string[]      $sentinelKeys Keys whose change triggers a reload
     */
    public function __construct(
        string $baseUrl,
        private readonly string $apiKey,
        private readonly string $environment,
        ?Request $request = null,
        private readonly int $cacheTtl = 60,
        private readonly string $userAgent = Constants::USER_AGENT_VENDOR,
        ?\Closure $clock = null,
        int $timeout = 30,
        private readonly int $refreshInterval = 0,
        ?GrimoireSnapshotStore $snapshotStore = null,
        array $sentinelKeys = self::SENTINEL_KEYS,
    ) {
        if (trim($baseUrl) === '') {
            throw new \InvalidArgumentException('Grimoire base URL must not be empty.');
        }
        if (trim($apiKey) === '') {
            throw new \InvalidArgumentException('Grimoire API key must not be empty.');
        }
        if (trim($environment) === '') {
            throw new \InvalidArgumentException('Grimoire environment must not be empty.');
        }
        if ($cacheTtl < 0) {
            throw new \InvalidArgumentException('Cache TTL must be zero or positive.');
        }
        if ($timeout < 1) {
            throw new \InvalidArgumentException('Timeout must be at least one second.');
        }
        if ($refreshInterval < 0) {
            throw new \InvalidArgumentException('Refresh interval must be zero or positive.');
        }
        if ($refreshInterval > 0 && $sentinelKeys === []) {
            throw new \InvalidArgumentException('Sentinel refresh needs at least one sentinel key.');
        }

        if ($request === null) {
            $request = new Request(rtrim($baseUrl, '/'));
            $request->setTimeout($timeout);
        }

        $this->request       = $request;
        $this->clock         = $clock ?? static fn (): int => time();
        $this->snapshotStore = $snapshotStore;
        $this->sentinelKeys  = array_values($sentinelKeys);
        $this->cacheKey      = 'pancake:grimoire:' . hash('sha256', rtrim($baseUrl, '/') . "\n{$apiKey}\n{$environment}");
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Secrets
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Get the latest active version of a secret, or null when it does not exist or has
     * no active version (disabled, expired, not yet valid) in this environment.
     *
     * @throws GrimoireException on authentication, transport or server errors
     */
    public function getSecret(string $name): ?GrimoireSecret
    {
        $cached = $this->secretCache[$name] ?? null;
        if ($cached !== null && $cached['expires'] > $this->now()) {
            return $cached['value'];
        }
        unset($this->secretCache[$name]);

        $response = $this->request->get(
            self::ENDPOINT_SECRETS . rawurlencode($name) . '?environment=' . rawurlencode($this->environment),
            $this->headers(),
        );

        if ($response->getStatusCode() === 404) {
            return null;
        }

        $secret = GrimoireSecret::fromArray($this->decode($response, "secret '{$name}'"));

        if ($this->cacheTtl > 0) {
            // Never serve a secret from the cache past its own expiry.
            $expires = $this->now() + $this->cacheTtl;
            if ($secret->expiresOn !== null) {
                $expires = min($expires, $secret->expiresOn->getTimestamp());
            }
            $this->secretCache[$name] = ['expires' => $expires, 'value' => $secret];
        }

        return $secret;
    }

    /**
     * Get a secret's value, or $default when it is not available.
     *
     * @throws GrimoireException on authentication, transport or server errors
     */
    public function getSecretValue(string $name, ?string $default = null): ?string
    {
        return $this->getSecret($name)?->value ?? $default;
    }

    /**
     * Get a secret's value, failing when it is not available.
     *
     * @throws GrimoireException when the secret is missing or the request fails
     */
    public function requireSecret(string $name): string
    {
        $secret = $this->getSecret($name);
        if ($secret === null) {
            throw new GrimoireException(
                "Secret '{$name}' has no active value in environment '{$this->environment}'.",
                404,
            );
        }

        return $secret->value;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Configuration
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Get every effective configuration entry for the environment, as key => value.
     *
     * @return array<string, string>
     * @throws GrimoireException on authentication, transport or server errors, or an unknown environment
     */
    public function getConfigurations(): array
    {
        if ($this->refreshInterval > 0) {
            return $this->sentinelConfigurations();
        }

        if ($this->configurationCache !== null && $this->configurationCache['expires'] > $this->now()) {
            return $this->configurationCache['value'];
        }

        $configurations = $this->fetchConfigurations();

        if ($this->cacheTtl > 0) {
            $this->configurationCache = ['expires' => $this->now() + $this->cacheTtl, 'value' => $configurations];
        }

        return $configurations;
    }

    /**
     * @return array<string, string>
     * @throws GrimoireException
     */
    private function fetchConfigurations(): array
    {
        $response = $this->request->get(
            self::ENDPOINT_CONFIGURATIONS . '?environment=' . rawurlencode($this->environment),
            $this->headers(),
        );

        if ($response->getStatusCode() === 404) {
            throw new GrimoireException("Environment '{$this->environment}' was not found.", 404);
        }

        $body  = $this->decode($response, 'configurations');
        $items = $body['items'] ?? null;
        if (!is_array($items)) {
            throw new GrimoireException('Invalid configuration payload: "items" is missing.', $response->getStatusCode());
        }

        $configurations = [];
        foreach ($items as $item) {
            if (is_array($item) && isset($item['key']) && is_string($item['key'])) {
                // Note: PHP turns numeric-string keys ("123") into integers; callers cast back.
                $configurations[$item['key']] = (string) ($item['value'] ?? '');
            }
        }

        return $configurations;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Sentinel refresh (guibranco/grimoire-api#95)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Check the sentinel keys now, whatever the interval, and reload configuration when one of
     * them changed. Returns true when configuration was reloaded. Does nothing (false) unless
     * `refreshInterval` is set.
     *
     * @throws GrimoireException when Grimoire can't be reached and there is no snapshot yet
     */
    public function refresh(): bool
    {
        if ($this->refreshInterval <= 0) {
            return false;
        }
        $snapshot = $this->currentSnapshot();
        if ($snapshot === null) {
            $this->saveSnapshot($this->loadSnapshot());

            return true;
        }
        $updated = $this->refreshSnapshot($snapshot, throwOnFailure: true);

        return $updated['reloadedAt'] !== $snapshot['reloadedAt'];
    }

    /** @return array<string, string> */
    private function sentinelConfigurations(): array
    {
        $snapshot = $this->currentSnapshot();
        if ($snapshot === null) {
            $snapshot = $this->loadSnapshot();
            $this->saveSnapshot($snapshot);

            return $snapshot['configurations'];
        }

        if ($this->now() < $snapshot['nextCheckAt']) {
            return $snapshot['configurations'];
        }

        // One worker checks; the others keep serving the snapshot they have.
        $store = $this->store();
        if (!$store->lock($this->cacheKey, max(5, $this->refreshInterval))) {
            return $snapshot['configurations'];
        }
        try {
            return $this->refreshSnapshot($snapshot)['configurations'];
        } finally {
            $store->unlock($this->cacheKey);
        }
    }

    /** @return array<string, mixed>|null */
    private function currentSnapshot(): ?array
    {
        $shared = $this->store()->get($this->cacheKey);
        if (self::isSnapshot($shared)) {
            // A newer snapshot from another worker may mean newly published values.
            if ($this->snapshot !== null && $shared['reloadedAt'] !== $this->snapshot['reloadedAt']) {
                $this->secretCache = [];
            }
            $this->snapshot = $shared;
        }

        return $this->snapshot;
    }

    /**
     * Fetch everything and record the current sentinel values (recording never counts as a change).
     *
     * @return array<string, mixed>
     * @throws GrimoireException
     */
    private function loadSnapshot(): array
    {
        $configurations = $this->fetchConfigurations();
        try {
            [, $sentinels] = $this->checkSentinels([]);
        } catch (GrimoireException) {
            $sentinels = []; // Read on the next check instead.
        }

        return $this->newSnapshot($configurations, $sentinels);
    }

    /**
     * @param array<string, mixed> $snapshot
     * @return array<string, mixed>
     */
    private function refreshSnapshot(array $snapshot, bool $throwOnFailure = false): array
    {
        try {
            [$changed, $sentinels] = $this->checkSentinels($snapshot['sentinels']);
            if ($changed) {
                $updated = $this->newSnapshot($this->fetchConfigurations(), $sentinels);
                $this->secretCache = [];
            } else {
                $updated = ['sentinels' => $sentinels, 'failures' => 0, 'nextCheckAt' => $this->nextCheckAt(0)] + $snapshot;
            }
        } catch (GrimoireException $e) {
            // Keep the last good values and back off.
            $failures = (int) $snapshot['failures'] + 1;
            $updated  = ['failures' => $failures, 'nextCheckAt' => $this->nextCheckAt($failures)] + $snapshot;
            $this->saveSnapshot($updated);
            if ($throwOnFailure) {
                throw $e;
            }

            return $updated;
        }

        $this->saveSnapshot($updated);

        return $updated;
    }

    /**
     * Conditional reads of the sentinel keys. A key changed when its value differs, or it
     * appeared or disappeared, compared with a previously recorded state.
     *
     * @param array<string, array{exists: bool, value: ?string, etag: ?string}> $previous
     * @return array{0: bool, 1: array<string, array{exists: bool, value: ?string, etag: ?string}>}
     * @throws GrimoireException
     */
    private function checkSentinels(array $previous): array
    {
        $changed = false;
        $current = [];
        foreach ($this->sentinelKeys as $key) {
            $before  = $previous[$key] ?? null;
            $headers = $this->headers();
            if ($before !== null && $before['etag'] !== null) {
                $headers[] = "If-None-Match: {$before['etag']}";
            }

            $response = $this->request->get(
                self::ENDPOINT_CONFIGURATIONS . '/' . rawurlencode($key) . '?environment=' . rawurlencode($this->environment),
                $headers,
            );
            $status = $response->getStatusCode();

            if ($status === 304 && $before !== null && $response->isSuccess()) {
                $current[$key] = $before;
                continue;
            }
            if ($status === 404 && $response->isSuccess()) {
                $current[$key] = ['exists' => false, 'value' => null, 'etag' => null];
                $changed       = $changed || ($before !== null && $before['exists']);
                continue;
            }

            $item          = $this->decode($response, "sentinel '{$key}'");
            $value         = isset($item['value']) ? (string) $item['value'] : null;
            $current[$key] = ['exists' => true, 'value' => $value, 'etag' => $response->getHeader('ETag')];
            $changed       = $changed || ($before !== null && (!$before['exists'] || $before['value'] !== $value));
        }

        return [$changed, $current];
    }

    /**
     * @param array<string, string> $configurations
     * @param array<string, array{exists: bool, value: ?string, etag: ?string}> $sentinels
     * @return array<string, mixed>
     */
    private function newSnapshot(array $configurations, array $sentinels): array
    {
        return [
            'configurations' => $configurations,
            'sentinels'      => $sentinels,
            'reloadedAt'     => $this->now() . '.' . bin2hex(random_bytes(4)),
            'failures'       => 0,
            'nextCheckAt'    => $this->nextCheckAt(0),
        ];
    }

    /** Interval plus 0-20 % jitter, or exponential backoff after failures (at most 5 minutes). */
    private function nextCheckAt(int $failures): int
    {
        if ($failures > 0) {
            return $this->now() + min(self::MAX_BACKOFF, $this->refreshInterval * (2 ** min($failures, 16)));
        }

        return $this->now() + $this->refreshInterval + random_int(0, intdiv($this->refreshInterval, 5));
    }

    /** @param array<string, mixed> $snapshot */
    private function saveSnapshot(array $snapshot): void
    {
        $this->snapshot = $snapshot;
        $this->store()->set($this->cacheKey, $snapshot);
    }

    /** @phpstan-assert-if-true array<string, mixed> $snapshot */
    private static function isSnapshot(mixed $snapshot): bool
    {
        return is_array($snapshot)
            && is_array($snapshot['configurations'] ?? null)
            && is_array($snapshot['sentinels'] ?? null)
            && is_string($snapshot['reloadedAt'] ?? null)
            && is_int($snapshot['nextCheckAt'] ?? null)
            && is_int($snapshot['failures'] ?? null);
    }

    private function store(): GrimoireSnapshotStore
    {
        return $this->snapshotStore ??= GrimoireApcuSnapshotStore::isAvailable()
            ? new GrimoireApcuSnapshotStore()
            : new GrimoireFileSnapshotStore();
    }

    /**
     * Get one configuration value, or $default when the key does not exist.
     *
     * Keys are matched case-insensitively, like the Grimoire API and .NET IConfiguration.
     *
     * @throws GrimoireException on authentication, transport or server errors
     */
    public function getConfiguration(string $key, ?string $default = null): ?string
    {
        $configurations = $this->getConfigurations();
        if (array_key_exists($key, $configurations)) {
            return $configurations[$key];
        }

        foreach ($configurations as $candidate => $value) {
            if (strcasecmp((string) $candidate, $key) === 0) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Configuration keys under a section, with the section prefix removed.
     *
     * `getSection('Database')` turns `Database:Host` and `Database:Port` into
     * `['Host' => ..., 'Port' => ...]`.
     *
     * @return array<string, string>
     */
    public function getSection(string $section): array
    {
        $prefix = rtrim($section, ':') . ':';
        $result = [];
        foreach ($this->getConfigurations() as $key => $value) {
            $key = (string) $key;
            if (strncasecmp($key, $prefix, strlen($prefix)) === 0) {
                $result[substr($key, strlen($prefix))] = $value;
            }
        }

        return $result;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Environment loading
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Load secrets (and optionally all configuration) into getenv(), $_ENV and $_SERVER.
     *
     * Names are converted with {@see Grimoire::toEnvironmentName()}: `database-password`
     * becomes `DATABASE_PASSWORD` and `Feature:DarkMode` becomes `FEATURE__DARKMODE`.
     * Secrets win over configuration entries that map to the same name.
     *
     * @param string[] $secretNames          Secrets to load (the Consumer API cannot list secrets yet)
     * @param bool     $includeConfigurations Also load every configuration entry
     * @param bool     $overwrite            Replace variables already set in getenv(), $_ENV or $_SERVER
     * @param bool     $failOnMissing        Throw when a requested secret has no active value
     * @return string[] Environment variable names that were set
     * @throws GrimoireException
     */
    public function loadIntoEnvironment(
        array $secretNames,
        bool $includeConfigurations = true,
        bool $overwrite = false,
        bool $failOnMissing = true,
    ): array {
        $values = [];

        if ($includeConfigurations) {
            foreach ($this->getConfigurations() as $key => $value) {
                $values[self::toEnvironmentName((string) $key)] = $value;
            }
        }

        $missing = [];
        foreach ($secretNames as $name) {
            $value = $this->getSecretValue($name);
            if ($value === null) {
                $missing[] = $name;
                continue;
            }
            $values[self::toEnvironmentName((string) $name)] = $value;
        }

        if ($missing !== [] && $failOnMissing) {
            throw new GrimoireException(
                'Secrets with no active value in environment \'' . $this->environment . '\': ' . implode(', ', $missing),
                404,
            );
        }

        $set = [];
        foreach ($values as $name => $value) {
            $name = (string) $name;
            if (!$overwrite && $this->isEnvironmentVariableSet($name)) {
                continue;
            }
            putenv("{$name}={$value}");
            $_ENV[$name]    = $value;
            $_SERVER[$name] = $value;
            $set[]          = $name;
        }

        return $set;
    }

    /**
     * Convert a Grimoire secret name or configuration key to an environment variable name.
     *
     * `:` becomes `__` (the .NET convention), any other character outside `[A-Za-z0-9_]`
     * becomes `_`, and the result is upper-cased.
     */
    public static function toEnvironmentName(string $key): string
    {
        $name = str_replace(':', '__', $key);
        $name = (string) preg_replace('/[^A-Za-z0-9_]/', '_', $name);

        return strtoupper($name);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Misc
    // ──────────────────────────────────────────────────────────────────────────

    /** True when the Grimoire API health endpoint reports healthy (HTTP 200). */
    public function isHealthy(): bool
    {
        $response = $this->request->get(self::ENDPOINT_HEALTH, [
            "User-Agent: {$this->userAgent}",
            'Accept: application/json',
        ]);

        return $response->getStatusCode() === 200;
    }

    /** Drop all values cached by this instance. */
    public function clearCache(): void
    {
        $this->secretCache        = [];
        $this->configurationCache = null;
        if ($this->snapshot !== null || $this->snapshotStore !== null) {
            $this->snapshot = null;
            $this->store()->delete($this->cacheKey);
        }
    }

    /** True when the variable exists in the process environment, $_ENV or $_SERVER. */
    private function isEnvironmentVariableSet(string $name): bool
    {
        return getenv($name) !== false
            || array_key_exists($name, $_ENV)
            || array_key_exists($name, $_SERVER);
    }

    /** @return string[] */
    private function headers(): array
    {
        return [
            "User-Agent: {$this->userAgent}",
            'Accept: application/json',
            "X-Api-Key: {$this->apiKey}",
        ];
    }

    /** @return array<string, mixed> */
    private function decode(Response $response, string $what): array
    {
        $status = $response->getStatusCode();

        if (!$response->isSuccess()) {
            throw new GrimoireException(
                "Could not reach Grimoire while reading {$what}: {$response->getMessage()}",
                $status,
            );
        }

        if ($status === 401 || $status === 403) {
            throw new GrimoireException("Grimoire rejected the API key while reading {$what} (HTTP {$status}).", $status);
        }

        if ($status < 200 || $status >= 300) {
            throw new GrimoireException("Grimoire returned HTTP {$status} while reading {$what}.", $status);
        }

        $data = json_decode((string) $response->getBody(), true);
        if (!is_array($data)) {
            throw new GrimoireException("Grimoire returned an invalid JSON body while reading {$what}.", $status);
        }

        return $data;
    }

    private function now(): int
    {
        return ($this->clock)();
    }
}
