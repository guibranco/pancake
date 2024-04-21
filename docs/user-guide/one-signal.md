# One Signal

## Table of content

- [One Signal](#one-signal)
  - [Table of content](#table-of-content)
  - [About](#about)
  - [Requirements](#requirements)
  - [Available methods](#available-methods)
    - [Send notification](#send-notification)

## About

This class enable the ability to send [One Signal](https://onesignal.com/) notifications. It uses [Request](request.md) class to perform HTTP requests.

## Requirements

This requires lib curl to be active with your PHP settings.
This also requires a token from One Signal services.

## Available methods

### Send notification

Send a push notification using the One Signal V1 API endpoint.

```php
$oneSignal = new OneSignal("your-OneSignal-api-token");
$oneSignalAppId = "00000000-0000-0000-8000-000000000000";
$heading = "My push notification";
$content = "Hi!\n\nSome content of the notification";

$fields = array(
    'app_id' => $oneSignalAppId,
    'included_segments' => array('All'),
    'headings' => array('en' => $heading),
    'contents' => array('en' => $content),
    'web_push_topic' => 'unique-topic-identification',
    'chrome_web_icon' => 'https://example.com/images/logo.png'
);
$oneSignal->sendNotification($fields);
```
