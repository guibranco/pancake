# ShieldsIo

## Table of content

- [ShieldsIo](#shieldsio)
  - [Table of content](#table-of-content)
  - [About](#about)
  - [Requirements](#requirements)
  - [Available methods](#available-methods)
    - [Generate badge URL](#generate-badge-url)

## About

This class is a helper to generate [ShieldsIo](https://shields.io) badges.
It builds the URL with basic personalization.

## Requirements

No requirements.

## Available methods

### Generate badge URL

Generates the badge URL.

```php
$shieldsIo = new ShieldsIo();

// Generates a flat GitHub badge with owner/repo text.
$url = $shieldsIo->generateBadgeUrl(null, 'owner/repo', 'black', 'flat', null, 'github');
echo $url;
// Outputs: https://img.shields.io/badge/owner%2Frepo-black?style=flat&logo=github

// Generates a social GitHub badge with owner text.
$url = $shieldsIo->generateBadgeUrl(null, 'owner', 'black', 'social', null, 'github');
echo $url;
// Outputs: https://img.shields.io/badge/owner-black?style=social&logo=github
```
