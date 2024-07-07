# Health Checks

## Table of content

- [Health Checks](#health-checks)
  - [Table of content](#table-of-content)
  - [About](#about)
  - [Requirements](#requirements)
  - [Available methods](#available-methods)
    - [Set headers](#set-headers)
    - [Heartbeat](#heartbeat)
    - [Start](#start)
    - [End](#end)
    - [Fail](#fail)
    - [Log](#log)
    - [Error](#error)
    - [Reset state](#reset-state)
  - [Examples](#examples)
    - [Simple heartbeat](#simple-heartbeat)
    - [Measuring time](#measuring-time)
    - [Try-Catch-Finally block](#try-catch-finally-block)
    - [Run identifier (RID)](#run-identifier-rid)

## About

This class is responsible for making requests to the [HealthChecks.io](https://healthchecks.io) service. It uses the [Request](request.md) class to perform the HTTP requests.

## Requirements

This requires lib curl to be active with your PHP settings.
This also requires a token from the Health Checks services.

## Available methods

### Set headers

Set the HTTP headers for the requests (useful for setting the HTTP user-agent).

```php
$headers = array("User-Agent: user-project/1.0");

$healthChecks = new HealthChecks("your-monitor-token");
$healthChecks->setHeaders($headers);
```

### Heartbeat

Performs an HTTP GET request to the service, logging a successful ping.

```php
$healthChecks = new HealthChecks("your-monitor-token");
$healthChecks->heartbeat();
```

### Start

Start a request and wait until a successful/failed call to measure process duration.

This will do an HTTP GET request to the `/start` endpoint.

```php
$healthChecks = new HealthChecks("your-monitor-token");
$healthChecks->start();
```

### End

End a request, measuring the process duration.
To this work properly, you should call [Start](#start) before.
If you call this directly without "starting" it prior, it will act the same way as [Heartbeat](#heartbeat).

This will perform an HTTP GET request to the `/` endpoint.
If the [Fail](#fail) or [Error](#error) has been called, the request will be made to the `/fail` endpoint.

```php
$healthChecks = new HealthChecks("your-monitor-token");
$healthChecks->end();
```

### Fail

Acknowledge a failure to the `/fail` endpoint.

```php
$healthChecks = new HealthChecks("your-monitor-token");
$healthChecks->fail();
```

### Log

Send a POST message to the `/log` endpoint.

```php
$healthChecks = new HealthChecks("your-monitor-token");
$healthChecks->log('Some message that does not change currents health check state.');
```


### Error

Like the [Fail](#fail) method, this one will also acknowledge a failure to the `/fail` endpoint. The difference is that this method will make an HTTP POST request with an error message (parameter).

```php
$healthChecks = new HealthChecks("your-monitor-token");
$healthChecks->error("error reason for the failure");
```

### Reset state

Reset the state of this instance so an `end` call will do a successful end request.

```php
$healthChecks = new HealthChecks("your-monitor-token");
$healthChecks->start();
$healthChecks->fail();
$healthChecks->resetState();
$healthChecks->end(); // this will be a call to / instead of /fail
```

## Examples

### Simple heartbeat

A simple example:

```php
$healthChecks = new HealthChecks("your-monitor-token");
$healthChecks->heartbeat();
```

### Measuring time

Measuring the time to run the operation:

```php
$healthChecks = new HealthChecks("your-monitor-token");
$healthChecks->start();
// time-consuming operation
// ....
$healthChecks->end();
```

### Try-Catch-Finally block

Using a try/catch block:

```php
$healthChecks = new HealthChecks("your-monitor-token");
$healthChecks->start();
try {
    // time-consuming operation
    // ....
} catch(Exception $e) {
    $healthChecks->error($e->getMessage());
} finally {
    $healthChecks->end();
}
```

### Run identifier (RID)

HealthChecks.io allows you to have parallel checks for the same monitor. To handle this, they allow you to send a custom UUID/GUID as the `run id`
To set the `run id`, just set a UUID as the second constructor parameter:

```php
$healthChecksA = new HealthChecks("your-monitor-token", "00000000-0000-0000-0000-000000000000");
$healthChecksB = new HealthChecks("your-monitor-token", "00000000-0000-0000-0000-000000000001");
$healthChecksC = new HealthChecks("your-monitor-token", "00000000-0000-0000-0000-000000000002");
$healthChecksD = new HealthChecks("your-monitor-token", "00000000-0000-0000-0000-000000000003");
```
