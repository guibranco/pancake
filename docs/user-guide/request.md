# Request

## Table of content

- [Request](#request)
  - [Table of content](#table-of-content)
  - [About](#about)
  - [Requirements](#requirements)
  - [Available HTTP verbs](#available-http-verbs)
    - [Get](#get)
    - [Post](#post)
    - [Put](#put)
    - [Patch](#patch)
    - [Delete](#delete)
      - [Delete without payload](#delete-without-payload)
      - [Delete with payload](#delete-with-payload)
    - [Options](#options)
    - [Head](#head)
  - [Troubleshooting](#troubleshooting)

## About

This class is responsible for doing [cURL](https://www.php.net/manual/en/book.curl.php) request with custom headers.

## Requirements

This requires lib curl to be active with your PHP settings.

## Available HTTP verbs

### Get

Performs HTTP GET request with custom headers.

```php

$headers = array("User-Agent: test/1.0", "Accept: application/json");

$request = new Request();
$response = $request->get("https://example.com/", $headers);

if ($response->statusCode >= 200 && $response->statusCode < 300) {
    echo $response->body;
}

```

### Post

Performs HTTP POST request with custom headers.

```php

$headers = array("User-Agent: test/1.0", "Accept: application/json");
$payload = array("Foo" => "Bar");

$request = new Request();
$response = $request->post("http://example.com/", json_encode($payload), $headers);

if ($response->statusCode >= 200 && $response->statusCode < 300) {
    echo $response->body;
}

```

### Put

Performs HTTP PUT request with custom headers.

```php

$headers = array("User-Agent: test/1.0", "Accept: application/json");
$payload = array("Foo" => "Rab");

$request = new Request();
$response = $request->put("https://example.com/", json_encode($payload), $headers);

if ($response->statusCode >= 200 && $response->statusCode < 300) {
    echo $response->body;
}

```

### Patch

Performs HTTP PATCH request with custom headers.

```php


$headers = array("User-Agent: test/1.0", "Accept: application/json");
$payload = array("some" => "thing");

$request = new Request();
$response = $request->patch("https://example.com/", json_encode($payload), $headers);

if ($response->statusCode >= 200 && $response->statusCode < 300) {
    echo $response->body;
}

```

### Delete

#### Delete without payload

Performs HTTP DELETE request with custom headers.

```php

$headers = array("User-Agent: test/1.0", "Accept: application/json");

$request = new Request();
$response = $request->delete("https://example.com/", $headers);

if ($response->statusCode >= 200 && $response->statusCode < 300) {
    echo $response->statusCode;
}

```

#### Delete with payload

Performs HTTP DELETE request with custom headers and a payload.

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

Performs HTTP OPTIONS request with custom headers.

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

Performs HTTP HEAD request with custom headers.

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

## Troubleshooting

If for some reason the request could not be completed, the result status code will be `-1` and an error property will be set with the content of `curl_error` response.

```php

$request = new Request();
$response = $request->get("https://invalid-domain");

if ($response->statusCode === -1) {
  echo $response->error; // This is only populated when the status code equals to -1.
}

```
