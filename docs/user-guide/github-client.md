# GitHub Client

The GitHub Client provides a convenient interface for interacting with the GitHub API. It uses the Pancake Request class to handle HTTP requests and offers flexible error handling options.

## Basic Usage

To use the GitHub client, you need to create an instance with appropriate credentials:

```php
use GuiBranco\Pancake\GitHubClient;

// Using a personal access token
$client = new GitHubClient(['user_token' => 'your-personal-access-token']);

// Using GitHub App credentials
$client = new GitHubClient([
    'app_id' => 'your-app-id',
    'app_secret' => 'your-app-secret'
]);
```

## Authentication

The GitHub client supports two authentication methods:

1. **Personal Access Token**: Provide a user token for authenticated API requests.
2. **GitHub App**: Provide app_id and app_secret for app-based authentication.

The client will automatically use the appropriate authentication method based on the credentials provided.

## API Methods

### Get Repository Information

```php
$response = $client->getRepository('owner', 'repo-name');

if ($response->isSuccessStatusCode()) {
    $repoData = $response->getBodyAsArray();
    echo "Repository name: " . $repoData['name'];
    echo "Stars: " . $repoData['stargazers_count'];
}
```

### Create an Issue

```php
$response = $client->createIssue(
    'owner',
    'repo-name',
    'Issue Title',
    'Issue description and details',
    [
        'labels' => ['bug', 'help wanted'],
        'assignees' => ['username']
    ]
);

if ($response->isSuccessStatusCode()) {
    $issueData = $response->getBodyAsArray();
    echo "Issue created: #" . $issueData['number'];
}
```

### Get User Information

```php
$response = $client->getUser('username');

if ($response->isSuccessStatusCode()) {
    $userData = $response->getBodyAsArray();
    echo "User: " . $userData['login'];
    echo "Name: " . $userData['name'];
}
```

## Error Handling

The GitHub client provides flexible error handling options:

### Logging Errors

You can pass a Logger instance to log API errors:

```php
use GuiBranco\Pancake\Logger;

$logger = new Logger('log-url', 'api-key', 'api-token');
$client = new GitHubClient(['user_token' => 'token'], $logger);

// API errors will be logged using the provided Logger
$response = $client->getRepository('owner', 'non-existent-repo');
```

### Throwing Exceptions

You can configure the client to throw exceptions for API errors:

```php
// Third parameter enables exception throwing
$client = new GitHubClient(['user_token' => 'token'], null, true);

try {
    $response = $client->getRepository('owner', 'non-existent-repo');
} catch (GuiBranco\Pancake\RequestException $e) {
    echo "API Error: " . $e->getMessage();
    echo "Status Code: " . $e->getCode();
}
```

### Ignoring Specific Status Codes

You can specify HTTP status codes to ignore:

```php
// Fourth parameter is an array of status codes to ignore
$client = new GitHubClient(
    ['user_token' => 'token'],
    null,
    true,
    [404] // Ignore 404 Not Found errors
);

// This won't throw an exception even if the repo doesn't exist
$response = $client->getRepository('owner', 'non-existent-repo');
```

## Advanced Usage

### Combining Error Handling Strategies

You can combine different error handling strategies:

```php
$logger = new Logger('log-url', 'api-key', 'api-token');
$client = new GitHubClient(
    ['user_token' => 'token'],
    $logger,         // Log all errors
    true,            // Throw exceptions for unhandled errors
    [404, 422]       // But ignore 404 and 422 status codes
);
```

This configuration will:
1. Log all API errors using the provided Logger
2. Throw exceptions for error status codes except 404 and 422
3. Return the response object normally for 404 and 422 status codes