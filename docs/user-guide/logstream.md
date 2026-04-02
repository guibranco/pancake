# LogStream

## Table of content

* [LogStream](#logstream)
* [Table of content](#table-of-content)
* [About](#about)
* [Requirements](#requirements)
* [Authentication modes](#authentication-modes)
  + [Bearer (file storage mode)](#bearer-file-storage-mode)
  + [API key (MariaDB storage mode)](#api-key-mariadb-storage-mode)
* [Constructor](#constructor)
* [Configuration methods](#configuration-methods)
  + [Set default category](#set-default-category)
  + [Set user agent](#set-user-agent)
* [Available methods](#available-methods)
  + [Debug](#debug)
  + [Info](#info)
  + [Notice](#notice)
  + [Warning](#warning)
  + [Error](#error)
  + [Critical](#critical)
  + [Log](#log)
  + [Batch](#batch)
  + [Make entry](#make-entry)
  + [Health](#health)
* [Supporting classes](#supporting-classes)
  + [LogStreamEntry](#logstreamentry)
  + [LogStreamResponse](#logstreamresponse)
* [Examples](#examples)
  + [Single entry in bearer mode](#single-entry-in-bearer-mode)
  + [Single entry in API-key mode](#single-entry-in-api-key-mode)
  + [Entry with context and category](#entry-with-context-and-category)
  + [Batch ingestion](#batch-ingestion)
  + [Health check](#health-check)
  + [Try-catch with error logging](#try-catch-with-error-logging)

## About

This class is a PHP client for the [logstream-server](https://github.com/guibranco/logstream-server) — a real-time log ingestion and streaming service built with PHP 8.3 + ReactPHP + Ratchet.

It uses the [Request](https://guilherme.stracini.com.br/pancake/user-guide/request/) class to perform HTTP requests and supports both authentication modes provided by the server.

Log entries are modelled by `LogStreamEntry` and server responses are wrapped in `LogStreamResponse` for typed access to status codes, saved counts, and error details.

## Requirements

This requires `lib curl` to be active with your PHP settings.

This also requires a running [logstream-server](https://github.com/guibranco/logstream-server) instance and the appropriate credentials depending on the server's storage mode.

Minimum requirements:

* PHP >= 8.1
* lib curl >= 7.29.0

## Authentication modes

The logstream-server uses two separate keys: a **write key** for ingesting logs and a **read key** for the UI. This client handles the write key only.

The authentication scheme depends on the server's `STORAGE_TYPE` setting.

### Bearer (file storage mode)

When the server is configured with `STORAGE_TYPE=file`, all applications share a single API secret sent as a Bearer token:

```
Authorization: Bearer <API_SECRET>
```

Use `LogStream::AUTH_BEARER` as the `authMode` and supply the `apiSecret` parameter.

### API key (MariaDB storage mode)

When the server is configured with `STORAGE_TYPE=mariadb`, each application has its own row in the `clients` table with a unique `app_key` / `api_token` pair. Both headers must be present on every request:

```
X-Api-Key:   <app_key>
X-Api-Token: <api_token>
```

Use `LogStream::AUTH_API_KEY` as the `authMode` and supply the `apiKey` and `apiToken` parameters.

> In MariaDB mode the `app_key` is **enforced from the server's `clients` table** — a client cannot submit logs under a different application's identity.

## Constructor

```php
$client = new LogStream(
    baseUrl:   'https://logs.example.com',  // logstream-server base URL
    appKey:    'billing-api',               // application slug
    appId:     'production',                // deployment environment / instance
    authMode:  LogStream::AUTH_BEARER,      // LogStream::AUTH_BEARER or LogStream::AUTH_API_KEY
    apiSecret: 'your-api-secret',           // bearer mode only
    apiKey:    '',                          // API-key mode only
    apiToken:  '',                          // API-key mode only
);
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `baseUrl` | string | ✅ | logstream-server base URL, no trailing slash |
| `appKey` | string | ✅ | Application slug sent as `app_key` on every entry |
| `appId` | string | ✅ | Deployment environment or instance identifier |
| `authMode` | string | ✅ | `LogStream::AUTH_BEARER` or `LogStream::AUTH_API_KEY` |
| `apiSecret` | string | bearer only | Bearer token (`API_SECRET` from the server's `.env`) |
| `apiKey` | string | API-key only | Value for the `X-Api-Key` header |
| `apiToken` | string | API-key only | Value for the `X-Api-Token` header |
| `request` | Request\|null | ❌ | Custom `Request` instance; auto-created if omitted |
| `userAgent` | string | ❌ | Overrides the default `User-Agent` header value |

## Configuration methods

### Set default category

Override the default category applied to all entries sent through this client instance. Defaults to `"general"` if not set.

```php
$client = new LogStream('https://logs.example.com', 'my-app', 'production', LogStream::AUTH_BEARER, 'secret');
$client->setDefaultCategory('payments');
```

### Set user agent

Override the `User-Agent` header sent with every request.

```php
$client = new LogStream('https://logs.example.com', 'my-app', 'production', LogStream::AUTH_BEARER, 'secret');
$client->setUserAgent('BillingService/2.1.0 (PHP 8.3)');
```

## Available methods

### Debug

Send a `debug`-level log entry.

```php
$client->debug('Cache warmed up');
$client->debug('Cache warmed up', ['keys' => 42], 'cache');
```

### Info

Send an `info`-level log entry.

```php
$client->info('Service started');
$client->info('User registered', ['user_id' => 99], 'auth');
```

### Notice

Send a `notice`-level log entry.

```php
$client->notice('Deprecated endpoint called');
$client->notice('Config value missing, using default', ['key' => 'timeout'], 'config');
```

### Warning

Send a `warning`-level log entry.

```php
$client->warning('Retry attempt 2 of 3');
$client->warning('High memory usage', ['usage_mb' => 512], 'system');
```

### Error

Send an `error`-level log entry.

```php
$client->error('Charge failed');
$client->error('Charge failed', ['invoice_id' => 1234, 'code' => 'card_declined'], 'payments');
```

### Critical

Send a `critical`-level log entry.

```php
$client->critical('Payment processor unreachable');
$client->critical('Database connection lost', ['host' => 'db-01'], 'database');
```

All six level methods share the same signature:

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `$message` | string | ✅ | Human-readable event description |
| `$context` | array\|null | ❌ | Arbitrary structured metadata |
| `$category` | string | ❌ | Grouping tag; falls back to the client default |
| `$traceId` | string\|null | ❌ | Client-supplied correlation UUID |

Each method returns a `LogStreamResponse`.

### Log

Send a pre-built `LogStreamEntry` directly. Useful when you need full control over every field.

```php
$entry = new LogStreamEntry(
    appId:     'production',
    level:     'error',
    message:   'Charge failed',
    category:  'payments',
    context:   ['invoice_id' => 1234],
    traceId:   'your-trace-uuid',
    timestamp: '2025-01-15T14:22:00.000Z',
);

$response = $client->log($entry);
```

### Batch

Send multiple log entries in a single HTTP request. All entries automatically share a `batch_id` unless they already have one set.

```php
$entries = [
    $client->makeEntry('Charge initiated', 'info',  'payments', ['amount' => 99.00]),
    $client->makeEntry('Card authorised',  'info',  'payments'),
    $client->makeEntry('Charge captured',  'info',  'payments'),
];

$response = $client->batch($entries);

// Optionally supply your own batch ID
$response = $client->batch($entries, 'my-batch-uuid');
```

Returns a `LogStreamResponse` where `$response->saved` equals the number of entries successfully stored.

### Make entry

Build a `LogStreamEntry` pre-filled with the client's default `appKey`, `appId`, and category. Intended for constructing entries before passing them to `batch()`.

```php
$entry = $client->makeEntry('Charge failed', 'error', 'payments', ['code' => 'card_declined']);
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `$message` | string | ✅ | Human-readable event description |
| `$level` | string | ❌ | Severity level (default `info`) |
| `$category` | string | ❌ | Grouping tag; falls back to the client default |
| `$context` | array\|null | ❌ | Arbitrary structured metadata |
| `$traceId` | string\|null | ❌ | Client-supplied correlation UUID |

### Health

Ping the server's public health endpoint. Returns a parsed array on success, or `null` if the server is unreachable or returns a non-200 status.

```php
$health = $client->health();

if ($health !== null) {
    echo $health['status'];         // "ok"
    echo $health['ws_connections']; // number of connected UI clients
}
```

## Supporting classes

### LogStreamEntry

An immutable value object that models a single log entry.

```php
$entry = new LogStreamEntry(
    appId:     'production',          // required
    level:     'error',               // required — debug|info|notice|warning|error|critical
    message:   'Charge failed',       // required
    category:  'payments',            // optional, max 100 chars (default "general")
    context:   ['invoice_id' => 1234],// optional — arbitrary key/value pairs
    appKey:    'billing-api',         // optional in MariaDB mode; required in bearer mode
    traceId:   'your-uuid',           // optional — auto-generated by server if omitted
    batchId:   'batch-uuid',          // optional — set automatically by ::batch()
    timestamp: '2025-01-15T14:22:00Z',// optional — defaults to server receive time
    userAgent: 'BillingService/2.1.0',// optional — overrides the request User-Agent
);
```

| Field | Type | Notes |
|-------|------|-------|
| `appId` | string | Deployment environment or instance |
| `level` | string | Falls back to `info` if an unknown value is supplied |
| `message` | string | Human-readable event description |
| `category` | string | Truncated to 100 characters |
| `context` | array\|null | Serialised to JSON by the server |
| `appKey` | string\|null | Required in bearer mode; ignored in MariaDB mode |
| `traceId` | string\|null | UUID; auto-generated server-side if omitted |
| `batchId` | string\|null | UUID; set automatically when using `::batch()` |
| `timestamp` | string\|null | ISO 8601; defaults to server receive time |
| `userAgent` | string\|null | Overrides the `User-Agent` header for this entry |

### LogStreamResponse

A typed wrapper around the raw server response.

```php
$response = $client->info('Hello');

$response->success;        // bool   — true when HTTP 2xx
$response->statusCode;     // int    — HTTP status code
$response->saved;          // int    — number of entries successfully stored
$response->entries;        // array  — the stored log entries as returned by the server
$response->errors;         // array|null — per-entry errors for any that failed to save
$response->transportError; // string|null — set when the cURL request itself failed (statusCode === -1)
$response->rawBody;        // string — raw JSON response body
```

## Examples

### Single entry in bearer mode

```php
use GuiBranco\Pancake\LogStream;

$client = new LogStream(
    baseUrl:   'https://logs.example.com',
    appKey:    'billing-api',
    appId:     'production',
    authMode:  LogStream::AUTH_BEARER,
    apiSecret: 'your-api-secret',
);

$response = $client->info('Service started');

if ($response->success) {
    echo "Logged with ID: " . $response->entries[0]['id'];
}
```

### Single entry in API-key mode

```php
use GuiBranco\Pancake\LogStream;

$client = new LogStream(
    baseUrl:  'https://logs.example.com',
    appKey:   'billing-api',
    appId:    'production',
    authMode: LogStream::AUTH_API_KEY,
    apiKey:   'billing-api',
    apiToken: 'your-api-token',
);

$response = $client->error(
    'Charge failed',
    ['invoice_id' => 1234, 'code' => 'card_declined'],
    'payments',
);
```

### Entry with context and category

```php
$client->setDefaultCategory('payments');

$client->warning(
    'Retry attempt 2 of 3',
    ['invoice_id' => 1234, 'attempt' => 2, 'next_retry_in' => '30s'],
);

$client->error(
    'All retries exhausted',
    ['invoice_id' => 1234, 'final_code' => 'do_not_honor'],
);
```

### Batch ingestion

```php
$entries = [
    $client->makeEntry('Payment flow started',   'info',    'payments', ['invoice_id' => 1234]),
    $client->makeEntry('Card tokenised',          'debug',   'payments'),
    $client->makeEntry('Charge authorised',       'info',    'payments', ['auth_code' => 'XY9001']),
    $client->makeEntry('Capture successful',      'info',    'payments'),
];

$response = $client->batch($entries);

echo "Saved: " . $response->saved . " of " . count($entries);
```

### Health check

```php
$health = $client->health();

if ($health === null) {
    echo "Server is unreachable";
} else {
    echo "Status: "     . $health['status'];
    echo "Time: "       . $health['time'];
    echo "WS clients: " . $health['ws_connections'];
}
```

### Try-catch with error logging

```php
$client->info('Job started', ['job_id' => $jobId], 'jobs');

try {
    // ... do work ...
    $client->info('Job completed', ['job_id' => $jobId, 'duration_ms' => $elapsed], 'jobs');
} catch (\Throwable $e) {
    $client->critical('Job failed', [
        'job_id'    => $jobId,
        'exception' => $e->getMessage(),
        'file'      => $e->getFile(),
        'line'      => $e->getLine(),
    ], 'jobs');
}
```
