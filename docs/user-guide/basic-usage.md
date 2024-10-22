# Basic usage

This document provides a quick introduction to the `GuiBranco\Pancake` toolkit. It includes links to each feature's documentation and a basic example of how to use the toolkit effectively.
The main namespace is `GuiBranco\Pancake`.

---

## Features

- [Color](color.md)
- [GUID v4](guid-v4.md)
- [Health Checks](health-checks.md)
- [HTTP Requests](request.md)
- [Logger](logger.md)
- [Memory Cache](memory-cache.md)
- [One Signal](one-signal.md)
- [Session Manager](session-manager.md)
- [ShieldsIo](shieldsio.md)

---

## Introduction

The `GuiBranco\Pancake` toolkit is designed to simplify common operations in PHP applications, such as session management, handling HTTP requests, and parsing TOML files. This toolkit provides a set of utility classes with a simple API to help developers focus on building their applications without worrying about these core tasks.

---

## Basic Usage Example

Here is a simple example demonstrating how you can use the `GuiBranco\Pancake` toolkit in a real-world PHP application:

```php
<?php

require 'vendor/autoload.php'; // Autoloading using Composer

use GuiBranco\Pancake\SessionManager;
use GuiBranco\Pancake\Request;

// Session Management Example
SessionManager::start();
SessionManager::set('username', 'john_doe');
echo "Session value for username: " . SessionManager::get('username');

// Flash message
SessionManager::flash('success', 'Your changes were saved!');
echo "Flash message: " . SessionManager::getFlash('success');

// HTTP Request Example
$request = new Request();
$response = $request->get('https://api.example.com/data');
echo "API Response: " . $response->getBody();
```
