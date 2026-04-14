# GitHub

## Table of contents

- [GitHub](#github)
  - [Table of contents](#table-of-contents)
  - [About](#about)
  - [Requirements](#requirements)
  - [Authentication](#authentication)
    - [Unauthenticated (public endpoints)](#unauthenticated-public-endpoints)
    - [User token](#user-token)
    - [GitHub App installation token](#github-app-installation-token)
    - [Both credentials at once](#both-credentials-at-once)
  - [Error handling](#error-handling)
    - [Silent mode (default)](#silent-mode-default)
    - [Throw mode](#throw-mode)
    - [Ignored status codes](#ignored-status-codes)
    - [Logging errors](#logging-errors)
  - [Available methods](#available-methods)
    - [Rate limit](#rate-limit)
    - [Users](#users)
    - [Repositories](#repositories)
    - [Issues](#issues)
    - [Pull requests](#pull-requests)
    - [Releases](#releases)
    - [Repository contents](#repository-contents)

## About

The `GitHub` class provides a typed, error-aware interface for the most common
[GitHub REST API v3](https://docs.github.com/en/rest) operations. It uses the
[`Request`](request.md) class internally and follows the same conventions as
the other Pancake API clients.

A companion `GitHubAppCredentials` value object carries GitHub App installation
credentials when app-based authentication is required.

## Requirements

- `libcurl` must be enabled in your PHP configuration.
- An internet connection or access to a GitHub Enterprise Server instance.
- A personal access token or GitHub App installation token for endpoints that
  require authentication.

## Authentication

### Unauthenticated (public endpoints)

```php
use GuiBranco\Pancake\GitHub;

$client = new GitHub();
$response = $client->getRateLimit();
echo $response->getBody();
```

### User token

Pass a personal access token (classic or fine-grained) or OAuth token as the
first constructor argument.

```php
$client = new GitHub('ghp_yourPersonalAccessToken');
$response = $client->getAuthenticatedUser();
$user = $response->getBodyAsJson();
echo $user->login;
```

### GitHub App installation token

Use `GitHubAppCredentials` to wrap the numeric app ID and the installation
access token you obtained from the GitHub Apps API.

```php
use GuiBranco\Pancake\GitHub;
use GuiBranco\Pancake\GitHubAppCredentials;

$creds  = new GitHubAppCredentials(123456, 'ghs_yourInstallationToken');
$client = new GitHub(null, $creds);

$response = $client->getRepository('my-org', 'my-repo');
$repo = $response->getBodyAsJson();
echo $repo->full_name;
```

### Both credentials at once

When both a user token and app credentials are supplied, the client uses the
**user token** by default. The app token acts as a fallback when no user token
is present.

```php
$creds  = new GitHubAppCredentials(123456, 'ghs_installationToken');
$client = new GitHub('ghp_userToken', $creds);
```

## Error handling

All methods return `?Response`. The precise behaviour when a request fails is
controlled by constructor options.

### Silent mode (default)

Failed requests return `null`. No exception is thrown.

```php
$client = new GitHub('ghp_token');
$response = $client->getIssue('owner', 'repo', 99999);

if ($response === null) {
    echo 'Issue not found or request failed.';
}
```

### Throw mode

Pass `true` as the fourth constructor argument (`$throwOnError`) to throw a
`RequestException` whenever a request returns a non-2xx status code or a
network error occurs.

```php
use GuiBranco\Pancake\RequestException;

$client = new GitHub('ghp_token', null, null, true);

try {
    $response = $client->getIssue('owner', 'repo', 99999);
} catch (RequestException $e) {
    echo 'Error ' . $e->getCode() . ': ' . $e->getMessage();
}
```

### Ignored status codes

Pass an array of HTTP status codes (or `-1` for cURL errors) as the fifth
argument. Matching responses return `null` silently — no log entry, no
exception — even when throw mode is active.

```php
// Treat "not found" responses as an expected absence, not an error.
$client = new GitHub('ghp_token', null, null, false, [404]);
$response = $client->getIssue('owner', 'repo', 99999);
// $response is null; no exception, no log entry.
```

### Logging errors

Supply any object implementing `ILogger` as the third argument. The logger
receives a structured object with `statusCode`, `message`, `url`, and `body`
fields whenever a non-ignored error occurs (and throw mode is off).

```php
use GuiBranco\Pancake\Logger;

$logger = new Logger($loggerUrl, $apiKey, $apiToken);
$client = new GitHub('ghp_token', null, $logger);

// Errors are forwarded to the logger automatically.
$client->getRepository('owner', 'nonexistent-repo');
```

## Available methods

### Rate limit

```php
$response = $client->getRateLimit();
$data = $response->getBodyAsJson();
echo $data->resources->core->remaining;
```

### Users

```php
// Public user profile
$response = $client->getUser('octocat');
$user = $response->getBodyAsJson();
echo $user->name;

// Authenticated user (requires token)
$response = $client->getAuthenticatedUser();
$user = $response->getBodyAsJson();
echo $user->login;
```

### Repositories

```php
// Single repository
$response = $client->getRepository('octocat', 'Hello-World');

// Public repositories for a user
$response = $client->listUserRepositories('octocat', ['per_page' => 10]);

// Repositories accessible to the authenticated user
$response = $client->listAuthenticatedUserRepositories(['visibility' => 'private']);
```

### Issues

```php
// Fetch a single issue
$response = $client->getIssue('owner', 'repo', 42);

// List issues with optional filters
$response = $client->listIssues('owner', 'repo', [
    'state'    => 'open',
    'per_page' => 50,
]);

// Create an issue
$response = $client->createIssue('owner', 'repo', [
    'title'     => 'Something is broken',
    'body'      => 'Steps to reproduce ...',
    'labels'    => ['bug'],
    'assignees' => ['octocat'],
]);

// Update an issue (e.g. close it)
$response = $client->updateIssue('owner', 'repo', 42, [
    'state' => 'closed',
]);
```

### Pull requests

```php
// Fetch a single pull request
$response = $client->getPullRequest('owner', 'repo', 7);

// List pull requests
$response = $client->listPullRequests('owner', 'repo', [
    'state'    => 'open',
    'per_page' => 20,
]);
```

### Releases

```php
// List releases
$response = $client->listReleases('owner', 'repo', ['per_page' => 5]);

// Latest published release
$response = $client->getLatestRelease('owner', 'repo');
$release  = $response->getBodyAsJson();
echo $release->tag_name;
```

### Repository contents

```php
// File or directory at the default branch
$response = $client->getContents('owner', 'repo', 'src/GitHub.php');

// File at a specific branch or tag
$response = $client->getContents('owner', 'repo', 'README.md', 'develop');

$file    = $response->getBodyAsJson();
$decoded = base64_decode($file->content);
```
