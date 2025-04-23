# Pancake

🧰 🛠️ Pancake project - a toolkit for PHP projects.

[![GitHub license](https://img.shields.io/github/license/guibranco/pancake)](https://github.com/guibranco/pancake)
[![Time tracker](https://wakatime.com/badge/github/guibranco/pancake.svg)](https://wakatime.com/badge/github/guibranco/pancake)

![Pancake logo](https://raw.githubusercontent.com/guibranco/pancake/main/logo.png)

Documentation: [Read the Docs](https://guibranco.github.io/pancake/)

---

## Table of contents

- [CI/CD](#cicd): Current project status in the build pipeline (AppVeyor).
- [Code Quality](#code-quality): Metrics from some tools about code quality.
- [Installation](#installation): How to install/download this tool.
- [User guide](#user-guide): How to set up, configure, and use this tool.
- [Change log](#changelog): Changelog containing the changes done in this project.
- [Support](#support): How to get support.
- [Testing](#testing): How to test this library.
- [Contributing](#contributing): How to contribute.

---

## CI/CD

| Build status | Last commit | Coverage | Code Smells | LoC |
|--------------|-------------|----------|-------------|-----|
| [![CI](https://github.com/guibranco/pancake/actions/workflows/ci.yml/badge.svg)](https://github.com/guibranco/pancake/actions/workflows/ci.yml) | [![GitHub last commit](https://img.shields.io/github/last-commit/guibranco/pancake/main)](https://github.com/guibranco/pancake) | [![Coverage](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=coverage)](https://sonarcloud.io/dashboard?id=guibranco_pancake) | [![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=code_smells)](https://sonarcloud.io/dashboard?id=guibranco_pancake) | [![Lines of Code](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=ncloc)](https://sonarcloud.io/dashboard?id=guibranco_pancake) | 

---

## Code Quality

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/9a369e8dc1e74ba1b18c309935c7af4b)](https://app.codacy.com/gh/guibranco/pancake/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)
[![Codacy Badge](https://app.codacy.com/project/badge/Coverage/9a369e8dc1e74ba1b18c309935c7af4b)](https://app.codacy.com/gh/guibranco/pancake/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_coverage)

[![Codecov](https://codecov.io/gh/guibranco/pancake/branch/main/graph/badge.svg)](https://codecov.io/gh/guibranco/pancake)
[![CodeFactor](https://www.codefactor.io/repository/github/guibranco/pancake/badge)](https://www.codefactor.io/repository/github/guibranco/pancake)

[![Maintainability](https://api.codeclimate.com/v1/badges/ae6591111f27479fba12/maintainability)](https://codeclimate.com/github/guibranco/pancake/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/ae6591111f27479fba12/test_coverage)](https://codeclimate.com/github/guibranco/pancake/test_coverage)

[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=alert_status)](https://sonarcloud.io/dashboard?id=guibranco_pancake)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=guibranco_pancake)

[![Technical Debt](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=sqale_index)](https://sonarcloud.io/dashboard?id=guibranco_pancake)
[![Duplicated Lines (%)](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=duplicated_lines_density)](https://sonarcloud.io/dashboard?id=guibranco_pancake)

[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=guibranco_pancake)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=security_rating)](https://sonarcloud.io/dashboard?id=guibranco_pancake)

[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=bugs)](https://sonarcloud.io/dashboard?id=guibranco_pancake)
[![Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=guibranco_pancake&metric=vulnerabilities)](https://sonarcloud.io/dashboard?id=guibranco_pancake)

[![DeepSource](https://app.deepsource.com/gh/guibranco/pancake.svg/?label=active+issues&show_trend=true&token=r3XGa8MQHGZERdIhKB5EZXfL)](https://app.deepsource.com/gh/guibranco/pancake/?ref=repository-badge)

---

## Installation

### Github Releases

[![GitHub last release](https://img.shields.io/github/release-date/guibranco/pancake.svg?style=flat)](https://github.com/guibranco/pancake) [![Github All Releases](https://img.shields.io/github/downloads/guibranco/pancake/total.svg?style=flat)](https://github.com/guibranco/pancake)

Download the latest zip file from the [Release](https://github.com/GuiBranco/pancake/releases) page.

### Packagist package manager

| Package | Version | Downloads |
|------------------|:-------:|:-------:|
| **[pancake](https://packagist.org/packages/guibranco/pancake)** | [![pancake Packagist Version](https://img.shields.io/packagist/v/guibranco/pancake.svg?style=flat)](https://packagist.org/packages/guibranco/pancake) | [![pancake Packagist Downloads](https://img.shields.io/packagist/dt/guibranco/pancake?style=flat)](https://packagist.org/packages/guibranco/pancake/) |

More information is available [here](https://guibranco.github.io/pancake/installation/).

---

## User guide

The user guide is available [here](https://guibranco.github.io/pancake/user-guide/basic-usage/).

---

## Changelog

The changelog is available [here](https://guibranco.github.io/pancake/changelog/).

---

## Support

Please [open an issue](https://github.com/guibranco/pancake/issues/new) for support.

---

## Testing

Tests can be run through PHPUnit.

```bash
composer update
./vendor/bin/phpunit tests
```

### Test dependencies

Make sure you have the MySQL/MariaDB server running and listen with the following configuration:

- Host: localhost
- Port: 3306
- Username: root
- Password: root
- Database: pancake

---

## Contributing

Refer to [CONTRIBUTING.md](CONTRIBUTING.md) to learn how to contribute to this project!

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
                <a href="https://github.com/Humayun-23">
                    <img src="https://avatars.githubusercontent.com/u/70696397?v=4" width="100;" alt="Humayun-23"/>
                    <br />
                    <sub><b>Sheikh Humayun Roshid</b></sub>
                </a>
            </td>
		</tr>
	<tbody>
</table>
<!-- readme: collaborators,contributors,snyk-bot/- -end -->

### Bots

<!-- readme: bots,snyk-bot -start -->
<table>
	<tbody>
		<tr>
            <td align="center">
                <a href="https://github.com/github-actions[bot]">
                    <img src="https://avatars.githubusercontent.com/in/15368?v=4" width="100;" alt="github-actions[bot]"/>
                    <br />
                    <sub><b>github-actions[bot]</b></sub>
                </a>
            </td>
            <td align="center">
                <a href="https://github.com/dependabot[bot]">
                    <img src="https://avatars.githubusercontent.com/in/29110?v=4" width="100;" alt="dependabot[bot]"/>
                    <br />
                    <sub><b>dependabot[bot]</b></sub>
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
	<tbody>
</table>
<!-- readme: bots,snyk-bot -end -->
