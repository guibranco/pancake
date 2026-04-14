# Queue

## Table of contents

- [Queue](#queue)
  - [Table of contents](#table-of-contents)
  - [About](#about)
  - [Requirements](#requirements)
  - [Installation](#installation)
  - [Retry topology](#retry-topology)
  - [Example usage](#example-usage)
    - [Basic publish and consume (no DLX)](#basic-publish-and-consume-no-dlx)
    - [Publish and consume with retry support](#publish-and-consume-with-retry-support)
    - [Multiple servers](#multiple-servers)
    - [Custom retry delays](#custom-retry-delays)
  - [Available methods](#available-methods)
    - [Constructor](#constructor)
    - [Publish](#publish)
    - [Consume](#consume)

## About

`Queue` is a thin AMQP wrapper built on top of [php-amqplib](https://github.com/php-amqplib/php-amqplib).
It covers the most common broker use-cases:

- **Durable publishing** – messages survive broker restarts.
- **Multi-server pool** – pass multiple connection strings; publish picks one at
  random (load balancing) while consume loops through every server (failover).
- **Dead-letter exchange (DLX) topology** – one call declares the main queue, a
  set of exponential-backoff retry queues, and a final failed queue.
- **Retry with exponential backoff** – when a callback returns `false` or throws,
  the message is routed to the next retry level automatically.

## Requirements

- PHP 8.4+
- The `php-amqplib/php-amqplib` package (`^3.7`)
- A running AMQP broker (RabbitMQ 3.x or later)

## Installation

Install the AMQP library alongside Pancake:

```bash
composer require guibranco/pancake php-amqplib/php-amqplib
```

## Retry topology

When `$withDlx = true`, the following queues are declared automatically:

| Queue name | Purpose |
|---|---|
| `<name>` | Main queue – messages land here first. |
| `<name>-retry-1` | First retry level (TTL = delay[0] ms). |
| `<name>-retry-2` | Second retry level (TTL = delay[1] ms). |
| … | Additional levels up to the configured number of delays. |
| `<name>-failed` | Sink for messages that exhausted every retry level. |

Each retry queue is configured with a dead-letter exchange that routes expired
messages back to the main queue, implementing the delay. The consumer tracks
how many times each message has been retried via the `x-pancake-retry-count`
header and routes it to the correct level when processing fails.

## Example usage

### Basic publish and consume (no DLX)

```php
<?php

use GuiBranco\Pancake\Queue\Queue;
use GuiBranco\Pancake\Queue\QueueException;

try {
    $queue = new Queue(['amqp://guest:guest@localhost:5672/']);

    // Publish a message.
    $queue->publish('orders', json_encode(['id' => 42, 'status' => 'new']), false);

    // Consume messages for up to 30 seconds.
    $queue->consume(
        30,
        'orders',
        function ($msg) {
            $data = json_decode($msg->body, true);
            echo "Processing order #{$data['id']}\n";
            // Return true (or nothing) to acknowledge; false to retry.
            return true;
        },
        false
    );
} catch (QueueException $e) {
    echo "Queue error [{$e->getOperation()}]: {$e->getMessage()}\n";
}
```

### Publish and consume with retry support

```php
<?php

use GuiBranco\Pancake\Queue\Queue;
use GuiBranco\Pancake\Queue\QueueException;

// Retry delays: 1 min → 5 min → 30 min → 1 h (defaults).
$queue = new Queue(['amqp://guest:guest@localhost:5672/']);

$queue->publish('notifications', json_encode(['type' => 'email', 'to' => 'user@example.com']));

$queue->consume(
    60,
    'notifications',
    function ($msg) {
        $data = json_decode($msg->body, true);

        $sent = sendEmail($data['to']); // hypothetical function

        // Returning false triggers the retry strategy.
        return $sent !== false;
    }
);
```

### Multiple servers

```php
<?php

use GuiBranco\Pancake\Queue\Queue;

$queue = new Queue([
    'amqp://guest:guest@broker1.internal:5672/',
    'amqp://guest:guest@broker2.internal:5672/',
    'amqp://guest:guest@broker3.internal:5672/',
]);

// Publish selects one server at random.
$queue->publish('tasks', json_encode(['task' => 'resize-image', 'id' => 7]));

// Consume iterates every server so no messages are missed.
$queue->consume(120, 'tasks', function ($msg) {
    processTask(json_decode($msg->body, true));
    return true;
});
```

### Custom retry delays

```php
<?php

use GuiBranco\Pancake\Queue\Queue;

// Retry after: 10 s, 1 min, 10 min.
$queue = new Queue(
    ['amqp://guest:guest@localhost:5672/'],
    [10_000, 60_000, 600_000]
);

$queue->publish('webhooks', json_encode(['url' => 'https://example.com/hook', 'payload' => []]));

$queue->consume(30, 'webhooks', function ($msg) {
    $data = json_decode($msg->body, true);
    return dispatchWebhook($data['url'], $data['payload']); // false on failure
});
```

## Available methods

### Constructor

Creates a `Queue` instance connected to one or more AMQP brokers.

Parameters:

- `$connectionStrings` (string[]): One or more AMQP URLs in the format
  `amqp://user:pass@host:port/vhost`.
- `$retryDelaysMs` (int[], optional): Millisecond delays for each retry level.
  Defaults to `[60 000, 300 000, 1 800 000, 3 600 000]` (1 min → 5 min → 30 min → 1 h).

```php
// Single broker, default retry delays.
$queue = new Queue(['amqp://guest:guest@localhost:5672/']);

// Multiple brokers, custom delays.
$queue = new Queue(
    ['amqp://guest:guest@host1/', 'amqp://guest:guest@host2/'],
    [30_000, 120_000, 600_000]
);
```

---

### Publish

Sends a message to the specified queue. A server is chosen at random from the
pool on every call.

Parameters:

- `$queueName` (string): Target queue name.
- `$message` (string): Message body. JSON is recommended.
- `$withDlx` (bool, optional): Declare the retry/failed topology. Default: `true`.

```php
$queue->publish('orders', json_encode(['id' => 99]));

// Without dead-letter queues:
$queue->publish('logs', $logLine, false);
```

---

### Consume

Starts a consumer loop that processes messages until `$timeout` seconds elapse.
Each configured server is tried in sequence; unreachable nodes are skipped.

Parameters:

- `$timeout` (int): Seconds before the loop exits.
- `$queueName` (string): Queue to consume from.
- `$callback` (callable): Receives the raw `AMQPMessage`.
  Return `false` (or throw) to trigger the retry strategy; return anything else
  to acknowledge the message.
- `$withDlx` (bool, optional): Declare the retry/failed topology. Default: `true`.
- `$resetTimeoutOnReceive` (bool, optional): Reset the timeout clock after every
  received message. Default: `false`.
- `$qosCount` (int, optional): QoS prefetch count. Default: `10`.

```php
$queue->consume(
    timeout: 60,
    queueName: 'orders',
    callback: function ($msg) {
        return processOrder(json_decode($msg->body, true));
    },
    withDlx: true,
    resetTimeoutOnReceive: true,
    qosCount: 5
);
```

---
