# Request

Allow perform HTTP requests using cURL.

## Available verbs

- Get
- Post
- Put
- Patch
- Delete
- Options
- Head

### Get

Performs HTTP GET request with custom headers.

```php

$request = new Request();
$result = $request->get("https://example.com/", array("User-Agent: test/1.0"));
print_r($result);

```