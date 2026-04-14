# 🥞 Pancake

> A lightweight, composable toolkit for PHP 8.4+ projects.

[![CI](https://github.com/guibranco/pancake/actions/workflows/ci.yml/badge.svg)](https://github.com/guibranco/pancake/actions/workflows/ci.yml)
[![GitHub license](https://img.shields.io/github/license/guibranco/pancake)](https://github.com/guibranco/pancake/blob/main/LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/guibranco/pancake.svg)](https://packagist.org/packages/guibranco/pancake)
[![Packagist Downloads](https://img.shields.io/packagist/dt/guibranco/pancake)](https://packagist.org/packages/guibranco/pancake)
[![Time tracker](https://wakatime.com/badge/github/guibranco/pancake.svg)](https://wakatime.com/badge/github/guibranco/pancake)

![Pancake logo](https://raw.githubusercontent.com/guibranco/pancake/main/logo.png)

📖 **Documentation:** [guibranco.github.io/pancake](https://guibranco.github.io/pancake/)

---

## Table of contents

- [Features](#features)
- [CI/CD](#cicd)
- [Code quality](#code-quality)
- [Installation](#installation)
- [User guide](#user-guide)
- [Testing](#testing)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Support](#support)

---

## Features

Pancake provides a curated set of battle-tested utility classes, ready to drop into any PHP project:

| Class | Description |
|---|---|
| [`CircuitBreaker`](https://guibranco.github.io/pancake/user-guide/basic-usage/) | Prevents cascading failures with open/half-open/closed state management |
| [`Color`](https://guibranco.github.io/pancake/user-guide/color/) | Color conversion and manipulation utilities |
| [`Database`](https://guibranco.github.io/pancake/user-guide/database/) | Thin PDO wrapper with query building helpers |
| [`GUIDv4`](https://guibranco.github.io/pancake/user-guide/guid-v4/) | RFC 4122-compliant UUID v4 generator |
| [`GitHub`](https://guibranco.github.io/pancake/user-guide/github/) | Interact with the GitHub REST API |
| [`HealthChecks`](https://guibranco.github.io/pancake/user-guide/health-checks/) | Liveness and readiness probe endpoints |
| [`IpUtils`](https://guibranco.github.io/pancake/user-guide/ip-utils/) | IP address parsing, validation, and range checking |
| [`Logger`](https://guibranco.github.io/pancake/user-guide/logger/) | PSR-3 compatible structured logger |
| [`LogStream`](https://guibranco.github.io/pancake/user-guide/logstream/) | Real-time log ingestion and streaming client |
| [`MemoryCache`](https://guibranco.github.io/pancake/user-guide/memory-cache/) | Shared-memory key/value store backed by `shmop` |
| [`OneSignal`](https://guibranco.github.io/pancake/user-guide/one-signal/) | Push notification client for the OneSignal API |
| [`Request`](https://guibranco.github.io/pancake/user-guide/request/) | Fluent HTTP client with `curl_multi` support |
| [`SessionManager`](https://guibranco.github.io/pancake/user-guide/session-manager/) | Secure session handling with lifetime and flash support |
| [`ShieldsIo`](https://guibranco.github.io/pancake/user-guide/shieldsio/) | Shields.io badge builder with cache-control |

---

## CI/CD

| Build | Last commit | Coverage | Code smells | Lines of code |
|:---:|:---:|:---:|:---:|:---:|
| [![CI](https://github.com/guibranco/pancake/actions/workflows/ci.yml/badge.svg)](https://github.com/guibranco/pancake/actions/workflows/ci.yml) | [![GitHub last commit](https://img.shields.io/github/last-commit/guibranco/pancake/main)](https://github.com/guibranco/pancake) | [![Coverage](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=coverage)](https://sonarcloud.io/dashboard?id=guibranco_pancake) | [![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=code_smells)](https://sonarcloud.io/dashboard?id=guibranco_pancake) | [![Lines of Code](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=ncloc)](https://sonarcloud.io/dashboard?id=guibranco_pancake) |

---

## Code quality

[![Codacy Grade](https://app.codacy.com/project/badge/Grade/9a369e8dc1e74ba1b18c309935c7af4b)](https://app.codacy.com/gh/guibranco/pancake/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)
[![Codacy Coverage](https://app.codacy.com/project/badge/Coverage/9a369e8dc1e74ba1b18c309935c7af4b)](https://app.codacy.com/gh/guibranco/pancake/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_coverage)
[![Codecov](https://codecov.io/gh/guibranco/pancake/branch/main/graph/badge.svg)](https://codecov.io/gh/guibranco/pancake)
[![CodeFactor](https://www.codefactor.io/repository/github/guibranco/pancake/badge)](https://www.codefactor.io/repository/github/guibranco/pancake)

[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=alert_status)](https://sonarcloud.io/dashboard?id=guibranco_pancake)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=guibranco_pancake)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=guibranco_pancake)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=security_rating)](https://sonarcloud.io/dashboard?id=guibranco_pancake)

[![Technical Debt](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=sqale_index)](https://sonarcloud.io/dashboard?id=guibranco_pancake)
[![Duplicated Lines (%)](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=duplicated_lines_density)](https://sonarcloud.io/dashboard?id=guibranco_pancake)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=bugs)](https://sonarcloud.io/dashboard?id=guibranco_pancake)
[![Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=vulnerabilities)](https://sonarcloud.io/dashboard?id=guibranco_pancake)

[![Maintainability](https://qlty.sh/gh/guibranco/projects/Pancake/maintainability.svg)](https://qlty.sh/gh/guibranco/projects/Pancake)
[![Code Coverage](https://qlty.sh/gh/guibranco/projects/Pancake/coverage.svg)](https://qlty.sh/gh/guibranco/projects/Pancake)
[![DeepSource](https://app.deepsource.com/gh/guibranco/pancake.svg/?label=active+issues&show_trend=true&token=r3XGa8MQHGZERdIhKB5EZXfL)](https://app.deepsource.com/gh/guibranco/pancake/?ref=repository-badge)

---

## Installation

**Requirements:** PHP 8.4+, Composer

### Via Composer (recommended)

```bash
composer require guibranco/pancake
```

### Via GitHub Releases

Download the latest archive from the [Releases](https://github.com/GuiBranco/pancake/releases) page and include the autoloader manually.

[![GitHub release date](https://img.shields.io/github/release-date/guibranco/pancake.svg)](https://github.com/guibranco/pancake/releases)

---

## User guide

Full documentation, class references, and examples live at **[guibranco.github.io/pancake](https://guibranco.github.io/pancake/user-guide/basic-usage/)**.

### Quick start

```php
<?php

require 'vendor/autoload.php';

use GuiBranco\Pancake\CircuitBreaker;
use GuiBranco\Pancake\MemoryCache;
use GuiBranco\Pancake\Request;

// Protect an external API call with a circuit breaker
$cb = new CircuitBreaker(new MemoryCache(), failureThreshold: 3, resetTimeout: 60);

$result = $cb->execute(function () {
    $request = new Request();
    return $request->get('https://api.example.com/data');
});
```

---

## Testing

### Requirements

- PHP 8.4+
- Composer
- MySQL / MariaDB running locally with the following config:

| Setting | Value |
|---|---|
| Host | `localhost` |
| Port | `3306` |
| User | `root` |
| Password | `root` |
| Database | `pancake` |

- [WireMock](https://wiremock.org/) for integration tests (see [`docker-compose.yml`](docker-compose.yml))

### Running the test suite

```bash
# Install dependencies
composer install

# Start WireMock (required for integration tests)
docker compose up -d

# Run all tests
./vendor/bin/phpunit tests

# Run only unit tests
./vendor/bin/phpunit tests/Unit

# Run only integration tests
./vendor/bin/phpunit tests/Integration
```

---

## Changelog

See [CHANGELOG](https://guibranco.github.io/pancake/changelog/) for the full history of releases and changes.

---

## Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request.

### Contributors

<!-- readme: collaborators,contributors,snyk-bot/- -start -->
<table>
	<tbody>
		<tr>
            <td align="center">
                <a href="https://github.com/guibranco">
                    <img src="https://avatars.githubusercontent.com/u/3362854?v=4" width="100;" alt="guibranco"/>
                    <br />
                    <sub><b>Guilherme Branco Stracini</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/gvieiragoulart">
                    <img src="https://avatars.githubusercontent.com/u/116896794?v=4" width="100;" alt="gvieiragoulart"/>
                    <br />
                    <sub><b>Gabriel Goulart</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/Hero-Aviraj">
                    <img src="https://avatars.githubusercontent.com/u/178659748?v=4" width="100;" alt="Hero-Aviraj"/>
                    <br />
                    <sub><b>Haraprasad Mondal</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/Humayun-23">
                    <img src="https://avatars.githubusercontent.com/u/70696397?v=4" width="100;" alt="Humayun-23"/>
                    <br />
                    <sub><b>Sheikh Humayun Roshid</b></sub>
                </a>
            </td>
		</tr>
	</tbody>
</table>
<!-- readme: collaborators,contributors,snyk-bot/- -end -->

### Bots

<!-- readme: bots,snyk-bot -start -->
<table>
	<tbody>
		<tr>
            <td align="center">
                <a href="https://github.com/dependabot[bot]">
                    <img src="https://avatars.githubusercontent.com/in/29110?v=4" width="100;" alt="dependabot[bot]"/>
                    <br />
                    <sub><b>dependabot[bot]</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/github-actions[bot]">
                    <img src="https://avatars.githubusercontent.com/in/15368?v=4" width="100;" alt="github-actions[bot]"/>
                    <br />
                    <sub><b>github-actions[bot]</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/gitauto-ai[bot]">
                    <img src="https://avatars.githubusercontent.com/in/844909?v=4" width="100;" alt="gitauto-ai[bot]"/>
                    <br />
                    <sub><b>gitauto-ai[bot]</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/deepsource-autofix[bot]">
                    <img src="https://avatars.githubusercontent.com/in/57168?v=4" width="100;" alt="deepsource-autofix[bot]"/>
                    <br />
                    <sub><b>deepsource-autofix[bot]</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/penify-dev[bot]">
                    <img src="https://avatars.githubusercontent.com/in/399279?v=4" width="100;" alt="penify-dev[bot]"/>
                    <br />
                    <sub><b>penify-dev[bot]</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/snyk-bot">
                    <img src="https://avatars.githubusercontent.com/u/19733683?v=4" width="100;" alt="snyk-bot"/>
                    <br />
                    <sub><b>Snyk Bot</b></sub>
                </a>
            </td>
		</tr>
	</tbody>
</table>
<!-- readme: bots,snyk-bot -end -->

---

## Support

Please [open an issue](https://github.com/guibranco/pancake/issues/new/choose) for bug reports, feature requests, or questions.

---

<sub>Copyright © Guilherme Branco Stracini. Released under the [MIT License](LICENSE).</sub>
