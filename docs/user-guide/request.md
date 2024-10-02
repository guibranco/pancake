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
  - [Troubleshooting](#troubleshooting)

## About

This class is responsible for doing [cURL](https://www.php.net/manual/en/book.curl.php) requests with custom headers.

## Requirements

This requires lib curl to be active with your PHP settings.

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
$response = $request->post("https://example.com/", null, $headers);

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
$response = $request->post("https://example.com/", json_encode($payload), $headers);

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
$response = $request->put("https://example.com/", null, $headers);

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
$response = $request->put("https://example.com/", json_encode($payload), $headers);

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
$response = $request->patch("https://example.com/", null, $headers);

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
$response = $request->patch("https://example.com/", json_encode($payload), $headers);

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

## Troubleshooting

If the request could not be completed for some reason, the result status code will be `-1`, and an error property will be set with the content of the `curl_error` response.

```php

$request = new Request();
$response = $request->get("https://invalid-domain");

if ($response->statusCode === -1) {
  echo $response->error; // This is only populated when the status code equals to -1.
}

```
