# GUID (v4)

## Table of content

- [GUID (v4)](#guid-v4)
  - [Table of content](#table-of-content)
  - [About](#about)
  - [Requirements](#requirements)
  - [Available methods](#available-methods)
    - [Empty](#empty)
    - [Random](#random)

## About

This class is responsible for generating a [GUID/UUID](https://en.wikipedia.org/wiki/Universally_unique_identifier) string.

## Requirements

None.

## Available methods

### Empty

Generates an empty GUID/UUID full of zeros.
Example: 00000000-0000-4000-8000-000000000000

```php
$emptyUUID = GUIDv4::empty();
```

### Random

Generate a random GUID/UUID.
Example: d7263b5c-66b7-428e-8e6d-3df2ec9a92b9

```php
$uuid = GUIDv4::random();
```