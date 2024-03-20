# Request

Allow perform HTTP requests using cURL.

## Available verbs

- [Request](#request)
  - [Available verbs](#available-verbs)
    - [Get](#get)
    - [Post](#post)
    - [Put](#put)
    - [Patch](#patch)
    - [Delete](#delete)
    - [Options](#options)
    - [Head](#head)

### Get

Performs HTTP GET request with custom headers.

```php

$request = new Request();
$response = $request->get("https://example.com/", array("User-Agent: test/1.0"));
echo $response->statusCode; // HTTP Status Code. eg. 400, 200, 201, 302, ...
echo $response->body; // The response body

```

### Post

Performs HTTP POST request with custom headers.

```php

$headers = array("User-Agent: test/1.0", "Accept: application/json");
$payload = array("Foo" => "Bar");

$request = new Request();
$response = $request->post("http://example.com/", json_encode($payload), $headers);

echo $response->statusCode; // HTTP Status Code. eg. 400, 200, 201, 302, ...
echo $response->body; // The response body

```

### Put

```php
//TODO
```

### Patch

```php
//TODO
```

### Delete

```php
//TODO
```

### Options

```php
//TODO
```

### Head

```php
//TODO
```