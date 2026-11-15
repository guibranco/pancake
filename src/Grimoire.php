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
 */
final class Grimoire
{
    private const ENDPOINT_SECRETS        = '/consumer/secrets/';
    private const ENDPOINT_CONFIGURATIONS = '/consumer/configurations';
    private const ENDPOINT_HEALTH         = '/health';

    private readonly Request $request;

    private readonly \Closure $clock;

    /** @var array<string, array{expires: int, value: GrimoireSecret}> */
    private array $secretCache = [];

    /** @var array{expires: int, value: array<string, string>}|null */
    private ?array $configurationCache = null;

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

        if ($request === null) {
            $request = new Request(rtrim($baseUrl, '/'));
            $request->setTimeout($timeout);
        }

        $this->request = $request;
        $this->clock   = $clock ?? static fn (): int => time();
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
        if ($this->configurationCache !== null && $this->configurationCache['expires'] > $this->now()) {
            return $this->configurationCache['value'];
        }

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

        if ($this->cacheTtl > 0) {
            $this->configurationCache = ['expires' => $this->now() + $this->cacheTtl, 'value' => $configurations];
        }

        return $configurations;
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
