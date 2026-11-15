# Grimoire

## Table of content

* [About](#about)
* [Requirements](#requirements)
* [Constructor](#constructor)
* [Secrets](#secrets)
* [Configuration](#configuration)
* [Loading into the environment](#loading-into-the-environment)
* [Caching](#caching)
* [Errors](#errors)
* [Examples](#examples)

## About

`Grimoire` is a PHP client for the [Grimoire API](https://github.com/guibranco/grimoire-api) Consumer API, the self-hosted vault and configuration manager.

It reads **effective** values for one application and environment: the application's own value, or a global value when the application inherits globals. The Grimoire server resolves the precedence, so the client doesn't have to.

It uses the [Request](request.md) class for HTTP.

## Requirements

* PHP >= 8.1 with `ext-curl`
* A Grimoire API instance and an application API key (`grm_...`)

## Constructor

```php
use GuiBranco\Pancake\Grimoire;

$grimoire = new Grimoire(
    baseUrl:     'https://api.grimoire.example.com',
    apiKey:      getenv('GRIMOIRE_API_KEY'),
    environment: 'production',
    cacheTtl:    60,            // optional, seconds; 0 disables caching
);
```

| Parameter | Description |
| :-- | :-- |
| `baseUrl` | Grimoire API base URL |
| `apiKey` | Application API key, sent as `X-Api-Key` |
| `environment` | Environment slug, e.g. `local`, `staging`, `production` |
| `request` | Optional `Request` instance (for tests) |
| `cacheTtl` | In-memory cache lifetime for this instance, default `60` |
| `userAgent` | Optional `User-Agent` |

Keep the API key itself out of source control: read it from the server environment (or a file outside the web root).

## Secrets

```php
$secret = $grimoire->getSecret('database-password');   // ?GrimoireSecret
$secret->value;      // string
$secret->version;    // int
$secret->expiresOn;  // ?DateTimeImmutable

$value = $grimoire->getSecretValue('smtp-password', 'default');  // ?string
$value = $grimoire->requireSecret('database-password');          // string, throws if missing
```

A secret that does not exist, or has no active version (disabled, expired, not yet valid), returns `null`. `GrimoireSecret` hides its value from `var_dump()` and `print_r()`.

## Configuration

```php
$all     = $grimoire->getConfigurations();                 // ['Database:Host' => 'osasco', ...]
$retries = (int) $grimoire->getConfiguration('MaxRetries', '3');
$db      = $grimoire->getSection('Database');              // ['Host' => 'osasco', 'Port' => '3306']
```

Keys are matched case-insensitively, as on the server.

## Loading into the environment

For existing apps that read `getenv()` / `$_ENV`, load values once at bootstrap:

```php
$grimoire->loadIntoEnvironment(
    secretNames:           ['database-password', 'smtp-password'],
    includeConfigurations: true,   // also load every configuration entry
    overwrite:             false,  // keep variables that are already set
    failOnMissing:         true,   // throw if a listed secret is unavailable
);

getenv('DATABASE_PASSWORD');
getenv('DATABASE__HOST');
```

Names are converted with `Grimoire::toEnvironmentName()`:

| Grimoire | Environment variable |
| :-- | :-- |
| `database-password` | `DATABASE_PASSWORD` |
| `Feature:DarkMode` | `FEATURE__DARKMODE` |
| `ConnectionStrings:Main` | `CONNECTIONSTRINGS__MAIN` |

The Consumer API can't list secrets yet, so secret names must be passed explicitly.

## Caching

Each instance caches secrets and the configuration list in memory for `cacheTtl` seconds. In a classic PHP request this means one API call per value per request; in long-running workers (queues, ReactPHP) values refresh after the TTL. Call `clearCache()` to force a refresh.

## Errors

`GrimoireException` (with `getStatusCode()`) is thrown for:

* `401` / `403`: invalid or unauthorised API key
* other non-2xx responses, invalid JSON
* transport errors (`-1`), e.g. DNS or TLS failures
* an unknown environment on `getConfigurations()` (`404`)
* `requireSecret()` / `loadIntoEnvironment()` when a secret is missing (`404`)

```php
use GuiBranco\Pancake\GrimoireException;

try {
    $password = $grimoire->requireSecret('database-password');
} catch (GrimoireException $e) {
    error_log("Grimoire unavailable ({$e->getStatusCode()}): {$e->getMessage()}");
    throw $e;
}
```

`isHealthy()` returns `true` when `GET /health` answers `200`.

## Examples

### Database connection

```php
use GuiBranco\Pancake\Database;
use GuiBranco\Pancake\Grimoire;

$grimoire = new Grimoire('https://api.grimoire.example.com', getenv('GRIMOIRE_API_KEY'), 'production');
$db       = $grimoire->getSection('Database');

$pdo = new PDO(
    "mysql:host={$db['Host']};port={$db['Port']};dbname={$db['Name']}",
    $db['User'],
    $grimoire->requireSecret('database-password'),
);
```

### Feature flag

```php
if ($grimoire->getConfiguration('Feature:NewDashboard', 'false') === 'true') {
    renderNewDashboard();
}
```
