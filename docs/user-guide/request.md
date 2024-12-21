# Request

## Table of content

- [Request](#request)
  - [Table of content](#table-of-content)
  - [About](#about)
  - [Requirements](#requirements)
  - [Available HTTP verbs](#available-http-verbs)
    - [Get](#get)
    - [Post](#post)
      - [Post without payload](#post-without-payload)
      - [Post with payload](#post-with-payload)
    - [Put](#put)
      - [Put without payload](#put-without-payload)
      - [Put with payload](#put-with-payload)
    - [Patch](#patch)
      - [Patch without payload](#patch-without-payload)
      - [Patch with payload](#patch-with-payload)
    - [Delete](#delete)
      - [Delete without payload](#delete-without-payload)
      - [Delete with payload](#delete-with-payload)
    - [Options](#options)
    - [Head](#head)
  - [Batch requests](#batch-requests)
    - [Executing batch requests](#executing-batch-requests)
  - [Troubleshooting](#troubleshooting)

## About

This class is responsible for executing HTTP requests using [cURL](https://www.php.net/manual/en/book.curl.php) with support for custom headers. Responses are encapsulated in the `Response` class, providing properties like `statusCode`, `headers`, and `body` for better usability.

## Requirements

This requires `lib curl` to be active with your PHP settings. You can install it via:  
  
```bash  
# For Debian/Ubuntu  
sudo apt-get install php-curl  
  
# For CentOS/RHEL  
sudo yum install php-curl  
```  
  
Minimum requirements:  

- PHP >= 7.4  
- lib curl >= 7.29.0  
  
For more information, see the [PHP cURL documentation](https://www.php.net/manual/en/book.curl.php).  

## Available HTTP verbs

### Get

Performs HTTP GET requests with custom headers.

```php
$headers = array("User-Agent: test/1.0", "Accept: application/json");

$request = new Request();
$response = $request->get("https://example.com/", $headers);

if ($response->statusCode >= 200 && $response->statusCode < 300) {
    echo $response->body;
}
```

### Post

#### Post without payload

Performs HTTP POST requests with custom headers.

```php
$headers = array("User-Agent: test/1.0", "Accept: application/json");

$request = new Request();
$response = $request->post("https://example.com/", $headers);

if ($response->statusCode >= 200 && $response->statusCode < 300) {
    echo $response->body;
}
```

#### Post with payload

Performs HTTP POST requests with custom headers and a payload.

```php
$headers = array("User-Agent: test/1.0", "Accept: application/json");
$payload = array("some" => "thing");

$request = new Request();
$response = $request->post("https://example.com/", $headers, json_encode($payload));

if ($response->statusCode >= 200 && $response->statusCode < 300) {
    echo $response->body;
}
```

### Put

#### Put without payload

Performs HTTP PUT requests with custom headers.

```php
$headers = array("User-Agent: test/1.0", "Accept: application/json");

$request = new Request();
$response = $request->put("https://example.com/", $headers);

if ($response->statusCode >= 200 && $response->statusCode < 300) {
    echo $response->body;
}
```

#### Put with payload

Performs HTTP PUT requests with custom headers and a payload.

```php
$headers = array("User-Agent: test/1.0", "Accept: application/json");
$payload = array("some" => "thing");

$request = new Request();
$response = $request->put("https://example.com/", $headers, json_encode($payload));

if ($response->statusCode >= 200 && $response->statusCode < 300) {
    echo $response->body;
}
```

### Patch

#### Patch without payload

Performs HTTP PATCH requests with custom headers.

```php
$headers = array("User-Agent: test/1.0", "Accept: application/json");

$request = new Request();
$response = $request->patch("https://example.com/", $headers);

if ($response->statusCode >= 200 && $response->statusCode < 300) {
    echo $response->body;
}
```

#### Patch with payload

Performs HTTP PATCH requests with custom headers and a payload.

```php
$headers = array("User-Agent: test/1.0", "Accept: application/json");
$payload = array("some" => "thing");

$request = new Request();
$response = $request->patch("https://example.com/", $headers, json_encode($payload));

if ($response->statusCode >= 200 && $response->statusCode < 300) {
    echo $response->body;
}
```

### Delete

#### Delete without payload

Performs HTTP DELETE requests with custom headers.

```php
$headers = array("User-Agent: test/1.0", "Accept: application/json");

$request = new Request();
$response = $request->delete("https://example.com/", $headers);

if ($response->statusCode >= 200 && $response->statusCode < 300) {
    echo $response->statusCode;
}
```

#### Delete with payload

Performs HTTP DELETE requests with custom headers and a payload.

```php
$headers = array("User-Agent: test/1.0", "Accept: application/json");
$payload = array("some" => "thing");

$request = new Request();
$response = $request->delete("https://example.com/", $headers, json_encode($payload));

if ($response->statusCode >= 200 && $response->statusCode < 300) {
    echo $response->statusCode;
}
```

### Options

Performs HTTP OPTIONS requests with custom headers.

```php
$headers = array("User-Agent: test/1.0", "Accept: application/json");

$request = new Request();
$response = $request->options("https://example.com/", $headers);

if ($response->statusCode >= 200 && $response->statusCode < 300) {
    foreach ($response->headers as $header) {
      echo $header;
    }
}
```

### Head

Performs HTTP HEAD requests with custom headers.

```php
$headers = array("User-Agent: test/1.0", "Accept: application/json");

$request = new Request();
$response = $request->head("https://example.com/", $headers);

if ($response->statusCode >= 200 && $response->statusCode < 300) {
    foreach ($response->headers as $header) {
      echo $header;
    }
}
```

## Batch requests

### Executing batch requests

Batch support allows for the execution of multiple HTTP requests in a single call. This is especially useful for optimizing network usage when several requests need to be sent to the same or different endpoints.

The requests are executed concurrently using PHP's multi-cURL interface (`curl_multi_init`), which means they are processed in parallel rather than sequentially. This can significantly reduce the total execution time when making multiple requests. However, be mindful that concurrent execution may increase memory usage and server load.
```php
$batchRequests = [
    [
        'method' => 'GET',
        'url' => 'https://example.com/endpoint1',
        'headers' => ["Accept: application/json"]
    ],
    [
        'method' => 'POST',
        'url' => 'https://example.com/endpoint2',
        'headers' => ["Accept: application/json"],
        'payload' => json_encode(['key' => 'value'])
    ]
];

$request = new Request();
$responses = $request->executeBatch($batchRequests);

foreach ($responses as $response) {
    if ($response->statusCode >= 200 && $response->statusCode < 300) {
        echo $response->body;
    } else {
        echo "Error with status code: " . $response->statusCode;
    }
}
```

### Parameters for batch requests

Each batch request must include the following parameters:

- **`method`** *(required)*: The HTTP method (GET, POST, PUT, PATCH, DELETE, OPTIONS, or HEAD).
- **`url`** *(required)*: The URL for the request.
- **`headers`** *(optional)*: An array of HTTP headers for the request.
- **`payload`** *(optional)*: The request body for methods that support payloads (e.g., POST, PUT, PATCH, DELETE).

### Example usage

Here are additional examples of batch requests for specific scenarios:

#### Sending mixed HTTP methods

```php
$batchRequests = [
    [
        'method' => 'HEAD',
        'url' => 'https://example.com/healthcheck',
        'headers' => ["Accept: application/json"]
    ],
    [
        'method' => 'DELETE',
        'url' => 'https://example.com/delete-endpoint',
        'headers' => ["Accept: application/json"],
        'payload' => json_encode(['id' => 123])
    ]
];

$request = new Request();
$responses = $request->executeBatch($batchRequests);

foreach ($responses as $response) {
    echo $response->statusCode . "\n";
}
```

#### Error handling in batch requests

If one or more requests fail, you can handle errors individually:

```php
$request = new Request();
$responses = $request->executeBatch($batchRequests);

foreach ($responses as $index => $response) {
    if ($response->statusCode >= 200 && $response->statusCode < 300) {
        echo "Request $index succeeded: " . $response->body;
    } else {
        echo "Request $index failed with status: " . $response->statusCode . "\n";
        echo "Error details: " . $response->body;
    }
}
```

#### Limiting batch size

For large numbers of requests, consider splitting the batches to avoid overwhelming the server:

```php
$allRequests = [...]; // Array of all requests to execute
$batchSize = 10;
$batches = array_chunk($allRequests, $batchSize);

$request = new Request();
foreach ($batches as $batch) {
    $responses = $request->executeBatch($batch);
    // Handle responses for this batch
}
```

## Troubleshooting

If the request could not be completed for some reason, the result status code will be `-1`, and an error property will be set with the content of the `curl_error` response.

```php

$request = new Request();
$response = $request->get("https://invalid-domain");

if ($response->statusCode === -1) {
  echo $response->error; // This is only populated when the status code equals to -1.
}

```
