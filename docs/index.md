# Pancake Project

A versatile and easy-to-use toolkit for PHP projects, designed to simplify common tasks such as session management, HTTP requests, and configuration parsing. Pancake provides developers with a clean, well-organized API to improve productivity and code quality.

![Pancake logo](https://raw.githubusercontent.com/guibranco/pancake/main/logo.png)

This project is proudly maintained by [@guibranco ![GitHub followers](https://img.shields.io/github/followers/guibranco?style=social)](https://github.com/guibranco).

Source code is available on GitHub: [Pancake Repository ![GitHub stars](https://img.shields.io/github/stars/guibranco/pancake?style=social)](https://github.com/guibranco/pancake)

---

## Table of Contents

- [Pancake Project](#pancake-project)
  - [Table of Contents](#table-of-contents)
  - [About Pancake](#about-pancake)
  - [Key Features](#key-features)
  - [Project Status](#project-status)
  - [Getting Started](#getting-started)
    - [Installation](#installation)
    - [Basic Usage Example](#basic-usage-example)
  - [Contributing](#contributing)
  - [License](#license)

---

## About Pancake

Pancake is a lightweight PHP toolkit focused on streamlining everyday project requirements. It includes several modules that handle common operations such as session management, HTTP request handling, and parsing TOML configuration files. The goal of Pancake is to make these operations easier and more intuitive, allowing developers to concentrate on building features rather than boilerplate code.

Whether you're working on small projects or large-scale applications, Pancake can help you maintain clean, readable, and maintainable code.

---

## Key Features

Pancake offers a variety of features to enhance your PHP projects:

- **[Color](color.md)**: Utility for managing colors based on text.
- **[GUID v4](guid-v4.md)**: Generate unique GUIDs for your data entities.
- **[Health Checks](health-checks.md)**: Monitor the health status of your applications and services.
- **[HTTP Requests](request.md)**: Easily send and manage HTTP requests, supporting multiple methods such as GET, POST, PUT, and DELETE.
- **[IP Utils](ip-utils.md)**: Utility functions for validating, checking ranges, and converting IP addresses in both IPv4 and IPv6 formats.
- **[Logger](logger.md)**: Robust logging capabilities to track application events and errors.
- **[Memory Cache](memory-cache.md)**: Implement caching strategies to improve application performance.
- **[One Signal](one-signal.md)**: Integrate One Signal for push notifications in your applications.
- **[Session Manager](session-manager.md)**: Simplify PHP session handling with methods for setting, getting, and managing session data, including flash messages.
- **[ShieldsIo](shieldsio.md)**: Create custom badges for your projects using Shields.io.

For more detailed documentation on each feature, check out the links above.

---

## Project Status

Pancake is actively maintained, and new features or bug fixes are added frequently. Below is the current status of the project:

| Build Status | Last Commit | Coverage | Code Smells | LoC |
|--------------|-------------|----------|-------------|-----|
| [![CI](https://github.com/guibranco/pancake/actions/workflows/ci.yml/badge.svg)](https://github.com/guibranco/pancake/actions/workflows/ci.yml) | [![GitHub last commit](https://img.shields.io/github/last-commit/guibranco/pancake/main)](https://github.com/guibranco/pancake) | [![Coverage](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=coverage)](https://sonarcloud.io/dashboard?id=guibranco_pancake) | [![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=code_smells)](https://sonarcloud.io/dashboard?id=guibranco_pancake) | [![Lines of Code](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=ncloc)](https://sonarcloud.io/dashboard?id=guibranco_pancake) |

- **Open Issues**: [![GitHub issues](https://img.shields.io/github/issues/guibranco/pancake)](https://github.com/guibranco/pancake/issues)

---

## Getting Started

To get started with Pancake in your project, follow these steps:

### Installation

Install Pancake via Composer:

```bash
composer require guibranco/pancake
```

### Basic Usage Example

```php
<?php

use GuiBranco\Pancake\SessionManager;
use GuiBranco\Pancake\Request;

// Start session
SessionManager::start();
SessionManager::set('user', 'john_doe');

// HTTP Request
$response = (new Request())->get('https://api.example.com/data');
echo $response->getBody();
```

For more detailed guides on specific features, refer to the [Basic Usage Documentation](basic-usage.md).

---

## Contributing

We welcome contributions! If you'd like to contribute to Pancake, please check the [contributing guide](CONTRIBUTING.md) and browse the [open issues](https://github.com/guibranco/pancake/issues).

---

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE.md) file for details.
