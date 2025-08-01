# Request

## Table of content

- [Request](#request)
  - [Table of content](#table-of-content)
  - [About](#about)
  - [Requirements](#requirements)
  - [Base URL Configuration](#base-url-configuration)
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

The `Request` class uses dependency injection for the `ResponseFactory` to create `Response` objects, making it more testable and adhering to SOLID principles.

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

## Base URL Configuration

The `Request` class now supports setting a base URL for all requests. This is useful when making multiple requests to the same API endpoint.

### Setting a base URL in the constructor

```php
// Create a request with a base URL
$request = new Request('https://api.example.com'); 

// Or with a custom ResponseFactory
$request = new Request('https://api.example.com', new CustomResponseFactory());

// Now you can make requests using relative paths
$response = $request->get('/users'); // This will request https://api.example.com/users
```

### Setting a base URL after instantiation

```php
$request = new Request();
$request->setBaseUrl('https://api.example.com');

// Now you can make requests using relative paths
$response = $request->get('/users'); // This will request https://api.example.com/users
```

### Getting the current base URL

```php
$request = new Request('https://api.example.com');
$baseUrl = $request->getBaseUrl(); // Returns 'https://api.example.com'
```

### Using absolute URLs with a base URL set

If you provide an absolute URL (starting with http:// or https://), the base URL will be ignored:

```php
$request = new Request('https://api.example.com');

// This will still request https://another-api.com/data
$response = $request->get('https://another-api.com/data');
```

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

The `Request` class supports batch requests through the `addRequest` and `executeBatch` methods. This allows for the execution of multiple HTTP requests in parallel using `curl_multi_init`, reducing the total execution time for large batches.

#### Example usage

```php
$batchRequests = [
    [
        'method' => 'GET',
        'key' => 'request1',
        'url' => 'https://example.com/api/users',
        'headers' => ["Accept: application/json"]
    ],
    [
        'method' => 'POST',
        'key' => 'request2',
        'url' => 'https://example.com/api/posts',
        'headers' => ["Content-Type: application/json"],
        'payload' => json_encode(['key' => 'value'])
    ]
];

$request = new Request();
$request->addRequest('request1', 'https://example.com/api/users', ["Accept: application/json"]);
$request->addRequest('request2', 'https://example.com/api/posts', ["Content-Type: application/json"], 'POST', json_encode(['key' => 'value']));

$responses = $request->executeBatch();
```

### Parameters for batch requests

Each batch request requires the following structure:

- **`method`** *(required)*: The HTTP method to use (e.g., GET, POST, PUT, PATCH, DELETE).
- **`url`** *(required)*: The full URL for the request.
- **`headers`** *(optional)*: An array of HTTP headers.
- **`payload`** *(optional)*: The request body, applicable for POST, PUT, PATCH, and DELETE methods.

### Notes

1. **Concurrency Limit**: The `executeBatch` method processes up to 10 requests concurrently (as defined by `MAX_CONCURRENT_REQUESTS` in the `Request.php` file).
2. **Error Handling**: If a request fails, its corresponding `Response` object will contain error details (e.g., `statusCode: -1` and an error message in `getMessage()`).
3. **Response Object**: Each response is wrapped in the `Response` class, providing structured access to the `statusCode`, `body`, and `headers`.

## Troubleshooting

If the request could not be completed for some reason, the result status code will be `-1`, and an error property will be set with the content of the `curl_error` response.

```php

$request = new Request();
$response = $request->get("https://invalid-domain");

if ($response->statusCode === -1) {
  echo $response->error; // This is only populated when the status code equals to -1.
}

```
