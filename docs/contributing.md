# Contributing

Contributions are **welcome** and will be fully **credited**.

We accept contributions via Pull Requests on [Github](https://github.com/guibranco/pancake).

## Pull Requests

- **[PSR-12 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-12-extended-coding-style-guide.md)** - The easiest way to apply the conventions is to install [PHP Code Sniffer](http://pear.php.net/package/PHP_CodeSniffer).

- **Add tests!** - Your patch won't be accepted if it doesn't have tests.

- **Document any change in behaviour** - Make sure the `README.md` and any other relevant documentation are kept up-to-date.

- **Consider our release cycle** - We try to follow [SemVer v2.0.0](http://semver.org/). Randomly breaking public APIs is not an option.

- **Create feature branches** - Don't ask us to pull from your master branch.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please [squash them](http://www.git-scm.com/book/en/v2/Git-Tools-Rewriting-History#Changing-Multiple-Commit-Messages) before submitting.

## Running Tests

The project uses [WireMock](https://wiremock.org/) to mock HTTP requests in integration tests. This avoids making real HTTP requests to external services during testing, which can lead to rate limiting and flaky tests.

### Setting up WireMock

WireMock is configured to run in a Docker container. To start it:

```bash
docker compose up -d
```

This will start both the MySQL database and WireMock services. WireMock will be available at http://localhost:8080.

### Verifying WireMock is Running

You can check if WireMock is running by executing:

```bash
php tests/check-wiremock.php
```

This script will check if WireMock is available and ready to accept requests.

### Running Tests with WireMock

Once WireMock is running, you can run the tests:

```bash
vendor/bin/phpunit
```

The integration tests for the `Request` class will use WireMock instead of making real HTTP requests to external services.

### Adding New WireMock Stubs

If you need to add new HTTP request patterns for testing, you can add new stub mappings in the `tests/wiremock/mappings` directory. Each mapping is a JSON file that defines a request pattern and its corresponding response.

For example, to add a new endpoint that returns a specific JSON response:

```json
{
  "request": {
    "method": "GET",
    "url": "/my-endpoint"
  },
  "response": {
    "status": 200,
    "headers": {
      "Content-Type": "application/json"
    },
    "jsonBody": {
      "message": "Hello, World!"
    }
  }
}
```

For more information on WireMock stub mappings, see the [WireMock documentation](https://wiremock.org/docs/stubbing/).

## Running Code Sniffer

```bash
vendor/bin/phpcs --standard=PSR12 src/
```

**Happy coding**!