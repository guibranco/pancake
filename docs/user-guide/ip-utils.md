# Ip Utils

## Table of Contents

- [Ip Utils](#ip-utils)
  - [About](#about)
  - [Requirements](#requirements)
  - [Available Methods](#available-methods)
    - [isValidIPv4](#isvalidipv4)
    - [isValidIPv6](#isvalidipv6)
    - [isIPv4InRange](#isipv4inrange)
    - [isIPv6InRange](#isipv6inrange)
    - [ipToLong](#iptolong)

## About
The `IpUtils` class provides a set of utilities for validating, checking, and converting IP addresses in both IPv4 and IPv6 formats. This class is particularly useful for handling IP-related tasks such as validating addresses, verifying if an IP is within a specific CIDR range, and converting IP addresses to their long integer representation for storage or comparison.

## Requirements
- PHP 7.4 or higher
- Network-related extensions enabled in PHP (e.g., `inet_pton`)

## Available Methods

### `isValidIPv4`

Validate if a given IP address is a valid IPv4 address.

- **Parameters:**
  - `string $ip`: The IP address to validate.
- **Returns:** `bool` - `true` if the IP is a valid IPv4 address, `false` otherwise.

**Example:**
```php
use GuiBranco\Pancake\IpUtils;

$ip = '192.168.1.1';
if (IpUtils::isValidIPv4($ip)) {
    echo "$ip is a valid IPv4 address.";
} else {
    echo "$ip is not a valid IPv4 address.";
}
// Output: 192.168.1.1 is a valid IPv4 address.
```

### `isValidIPv6`

Validate if a given IP address is a valid IPv6 address.

- **Parameters:**
  - `string $ip`: The IP address to validate.
- **Returns:** `bool` - `true` if the IP is a valid IPv6 address, `false` otherwise.

**Example:**
```php
use GuiBranco\Pancake\IpUtils;

$ip = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
if (IpUtils::isValidIPv6($ip)) {
    echo "$ip is a valid IPv6 address.";
} else {
    echo "$ip is not a valid IPv6 address.";
}
// Output: 2001:0db8:85a3:0000:0000:8a2e:0370:7334 is a valid IPv6 address.
```

### `isIPv4InRange`

Check if a given IPv4 address is within a specified CIDR range.

- **Parameters:**
  - `string $ip`: The IPv4 address to check.
  - `string $cidr`: The CIDR range in the format "subnet/mask".
- **Returns:** `bool` - `true` if the IP address is within the range, `false` otherwise.

**Example:**
```php
use GuiBranco\Pancake\IpUtils;

$ip = '192.168.1.10';
$cidr = '192.168.1.0/24';
if (IpUtils::isIPv4InRange($ip, $cidr)) {
    echo "$ip is within the range $cidr.";
} else {
    echo "$ip is not within the range $cidr.";
}
// Output: 192.168.1.10 is within the range 192.168.1.0/24.
```

### `isIPv6InRange`

Check if a given IPv6 address is within a specified CIDR range.

- **Parameters:**
  - `string $ip`: The IPv6 address to check.
  - `string $cidr`: The CIDR range in the format "subnet/mask".
- **Returns:** `bool` - `true` if the IP address is within the range, `false` otherwise.

**Example:**
```php
use GuiBranco\Pancake\IpUtils;

$ip = '2001:0db8:85a3::8a2e:0370:7334';
$cidr = '2001:0db8:85a3::/64';
if (IpUtils::isIPv6InRange($ip, $cidr)) {
    echo "$ip is within the range $cidr.";
} else {
    echo "$ip is not within the range $cidr.";
}
// Output: 2001:0db8:85a3::8a2e:0370:7334 is within the range 2001:0db8:85a3::/64.
```

### `ipToLong`

Convert an IP address to its long integer representation.

- **Parameters:**
  - `string $ip`: The IP address to convert.
- **Returns:** `string|false` - The long integer representation of the IP address, or `false` if the IP address is invalid.

**Example:**
```php
use GuiBranco\Pancake\IpUtils;

$ip = '192.168.1.1';
$longIp = IpUtils::ipToLong($ip);
echo "The long integer representation of $ip is $longIp.";
// Output: The long integer representation of 192.168.1.1 is 3232235777.

$ipv6 = '2001:0db8:85a3::8a2e:0370:7334';
$longIpv6 = IpUtils::ipToLong($ipv6);
echo "The long integer representation of $ipv6 is $longIpv6.";
// Output: The long integer representation of 2001:0db8:85a3::8a2e:0370:7334 is a large integer.
```
