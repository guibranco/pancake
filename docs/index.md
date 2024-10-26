# Pancake Project

Pancake is a versatile and easy-to-use toolkit for PHP projects designed to simplify common tasks such as session management, HTTP requests, and configuration parsing. It provides developers with a clean, well-organized API to improve productivity and code quality.

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
  - [Testing](#testing)
    - [Setup for Testing](#setup-for-testing)
      - [Enabling Xdebug](#enabling-xdebug)
    - [Running Tests](#running-tests)
      - [Running Tests Without Coverage](#running-tests-without-coverage)
      - [Running Tests With Coverage](#running-tests-with-coverage)
    - [Viewing Code Coverage in VSCode](#viewing-code-coverage-in-vscode)
    - [Debugging with Xdebug](#debugging-with-xdebug)
  - [Contributing](#contributing)
  - [License](#license)

---

## About Pancake

Pancake is a lightweight PHP toolkit focused on streamlining everyday project requirements. It includes several modules that handle common operations such as session management, HTTP request handling, and parsing TOML configuration files. Pancake's goal is to make these operations more accessible and intuitive, allowing developers to concentrate on building features rather than boilerplate code.

Whether working on small projects or large-scale applications, Pancake can help you maintain clean, readable, and maintainable code.

---

## Key Features

Pancake offers a variety of features to enhance your PHP projects:

- **[Color](color.md)**: Utility for managing colors based on text.
- **[Database](database.md)**: Interface for managing database connections and queries.
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

For more detailed guides on specific features, please see the [Basic Usage Documentation](user-guide/basic-usage.md).

---

## Testing

### Setup for Testing

To prepare for testing, please ensure you have **PHP**, **Composer**, **PHPUnit**, and **Xdebug** installed on your system. Also, install **VSCode** and the **Gutter Coverage** extension to view code coverage directly in your editor.

#### Enabling Xdebug

1. **Install Xdebug** if it's not already installed:
   ```bash
   pecl install xdebug
   ```

2. **Enable Xdebug**: Open your PHP configuration file (`php.ini`) and add or modify the following lines:
   ```ini
   [xdebug]
   zend_extension=xdebug.so
   xdebug.mode=coverage
   xdebug.start_with_request=yes
   ```

3. **Verify Xdebug Installation**: Restart your PHP environment and run:
   ```bash
   php -v
   ```
   Confirm that Xdebug is listed in the output.

---

### Running Tests

#### Running Tests Without Coverage

To execute tests without coverage reports, run:
```bash
./vendor/bin/phpunit --configuration tests/phpunit.xml
```

#### Running Tests With Coverage

To generate a coverage report with Xdebug enabled, use:
```bash
XDEBUG_MODE=coverage ./vendor/bin/phpunit --configuration tests/phpunit.xml --coverage-clover test-reports/cov.xml
```

The coverage report will be saved in `test-reports/cov.xml`. You can use this file with VSCode to visualize coverage.

---

### Viewing Code Coverage in VSCode

1. Install the **Gutter Coverage** extension in VSCode.
2. In the Gutter Coverage settings, configure it to point to `test-reports/cov.xml`.
3. After running tests with coverage, open your source files to see coverage indicators for each line.

---

### Debugging with Xdebug

1. Set breakpoints in VSCode.
2. Open the **Run and Debug** panel in VSCode, select **Listen for Xdebug**, or set up a new configuration if needed.
3. Run your tests from the command line or start a debugging session in VSCode.

---

## Contributing

We welcome contributions! If you'd like to contribute to Pancake, please check the [contributing guide](CONTRIBUTING.md) and browse the [open issues](https://github.com/guibranco/pancake/issues).

---

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE.md) file for details.
