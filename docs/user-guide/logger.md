# Logger

## Table of content

- [Logger](#logger)
  - [Table of content](#table-of-content)
  - [About](#about)
  - [Requirements](#requirements)
  - [Available methods](#available-methods)
    - [Log](#log)
  - [Remarks](#remarks)

## About

This class send detailed information about the caller method to a endpoint that expects a predefined JSON structure. It uses [Requet](request.md) class to perform HTTP requests.

## Requirements

This requires `lib curl` to be active with your PHP settings.
This also requires a key/token pair from the logger services.

## Available methods

### Log

Send the log with a custom message (string) and details (anything) to the `log-message` endpoint.

```php
$logger = new Logger("https://logger-service.endpoint.com/api/v1/", "key", "token");

$logger->log("Sample message", array("test"=>"test", "foo"=>"bar"));
$logger->log("Another message", "Details as string");
```

## Remarks

This class is intended to be used by my own and internal services, to my own logger service.
This is usefull just to centralize my logs without any market software (SEQ, Kibana, GrayLog, New Relic, etc)
