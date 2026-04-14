<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit;

use GuiBranco\Pancake\GitHub;
use GuiBranco\Pancake\GitHubAppCredentials;
use GuiBranco\Pancake\ILogger;
use GuiBranco\Pancake\RequestException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the GitHub API client.
 *
 * Tests that require network calls use a deliberately invalid custom endpoint
 * (`https://nonexistent.example.invalid/`) so that cURL returns a connection
 * error (status code -1) rather than relying on GitHub's live API. This keeps
 * tests deterministic in offline environments while still exercising the full
 * error-handling path.
 */
final class GitHubTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Returns a GitHub client pointed at a non-routable endpoint so every
     * request fails with a cURL error (status -1).
     */
    private function clientWithFakeEndpoint(
        $userToken = null,
        $appCredentials = null,
        $logger = null,
        bool $throwOnError = false,
        array $ignoredStatusCodes = []
    ): GitHub {
        return new GitHub(
            $userToken,
            $appCredentials,
            $logger,
            $throwOnError,
            $ignoredStatusCodes,
            'https://nonexistent.example.invalid/'
        );
    }

    // -------------------------------------------------------------------------
    // Instantiation
    // -------------------------------------------------------------------------

    public function testCanCreateWithNoArguments(): void
    {
        $client = new GitHub();
        $this->assertInstanceOf(GitHub::class, $client);
    }

    public function testCanCreateWithUserToken(): void
    {
        $client = new GitHub('ghp_personalAccessToken');
        $this->assertInstanceOf(GitHub::class, $client);
    }

    public function testCanCreateWithAppCredentials(): void
    {
        $creds = new GitHubAppCredentials(12345, 'ghs_installationToken');
        $client = new GitHub(null, $creds);
        $this->assertInstanceOf(GitHub::class, $client);
    }

    public function testCanCreateWithBothAuthentications(): void
    {
        $creds = new GitHubAppCredentials(12345, 'ghs_installationToken');
        $client = new GitHub('ghp_userToken', $creds);
        $this->assertInstanceOf(GitHub::class, $client);
    }

    public function testCanCreateWithThrowOnError(): void
    {
        $client = new GitHub(null, null, null, true);
        $this->assertInstanceOf(GitHub::class, $client);
    }

    public function testCanCreateWithIgnoredStatusCodes(): void
    {
        $client = new GitHub(null, null, null, false, [404, 422]);
        $this->assertInstanceOf(GitHub::class, $client);
    }

    public function testCanCreateWithCustomEndpoint(): void
    {
        $client = new GitHub(null, null, null, false, [], 'https://github.example.internal/api/v3/');
        $this->assertInstanceOf(GitHub::class, $client);
    }

    public function testCanCreateWithCustomUserAgent(): void
    {
        $client = new GitHub(null, null, null, false, [], null, 'MyApp/2.0');
        $this->assertInstanceOf(GitHub::class, $client);
    }

    public function testCanCreateWithAllOptions(): void
    {
        $creds = new GitHubAppCredentials(42, 'ghs_token');
        $client = new GitHub(
            'ghp_token',
            $creds,
            null,
            true,
            [404, 410],
            'https://api.example.com/',
            'TestAgent/1.0'
        );
        $this->assertInstanceOf(GitHub::class, $client);
    }

    // -------------------------------------------------------------------------
    // Error handling — silent mode (throwOnError = false)
    // -------------------------------------------------------------------------

    public function testGetUserReturnsNullOnNetworkError(): void
    {
        $client = $this->clientWithFakeEndpoint();
        $this->assertNull($client->getUser('octocat'));
    }

    public function testGetAuthenticatedUserReturnsNullOnNetworkError(): void
    {
        $client = $this->clientWithFakeEndpoint();
        $this->assertNull($client->getAuthenticatedUser());
    }

    public function testGetRepositoryReturnsNullOnNetworkError(): void
    {
        $client = $this->clientWithFakeEndpoint();
        $this->assertNull($client->getRepository('owner', 'repo'));
    }

    public function testListUserRepositoriesReturnsNullOnNetworkError(): void
    {
        $client = $this->clientWithFakeEndpoint();
        $this->assertNull($client->listUserRepositories('octocat'));
    }

    public function testListAuthenticatedUserRepositoriesReturnsNullOnNetworkError(): void
    {
        $client = $this->clientWithFakeEndpoint();
        $this->assertNull($client->listAuthenticatedUserRepositories());
    }

    public function testGetIssueReturnsNullOnNetworkError(): void
    {
        $client = $this->clientWithFakeEndpoint();
        $this->assertNull($client->getIssue('owner', 'repo', 1));
    }

    public function testListIssuesReturnsNullOnNetworkError(): void
    {
        $client = $this->clientWithFakeEndpoint();
        $this->assertNull($client->listIssues('owner', 'repo'));
    }

    public function testCreateIssueReturnsNullOnNetworkError(): void
    {
        $client = $this->clientWithFakeEndpoint();
        $this->assertNull($client->createIssue('owner', 'repo', ['title' => 'Bug']));
    }

    public function testUpdateIssueReturnsNullOnNetworkError(): void
    {
        $client = $this->clientWithFakeEndpoint();
        $this->assertNull($client->updateIssue('owner', 'repo', 1, ['state' => 'closed']));
    }

    public function testGetPullRequestReturnsNullOnNetworkError(): void
    {
        $client = $this->clientWithFakeEndpoint();
        $this->assertNull($client->getPullRequest('owner', 'repo', 1));
    }

    public function testListPullRequestsReturnsNullOnNetworkError(): void
    {
        $client = $this->clientWithFakeEndpoint();
        $this->assertNull($client->listPullRequests('owner', 'repo'));
    }

    public function testListReleasesReturnsNullOnNetworkError(): void
    {
        $client = $this->clientWithFakeEndpoint();
        $this->assertNull($client->listReleases('owner', 'repo'));
    }

    public function testGetLatestReleaseReturnsNullOnNetworkError(): void
    {
        $client = $this->clientWithFakeEndpoint();
        $this->assertNull($client->getLatestRelease('owner', 'repo'));
    }

    public function testGetRateLimitReturnsNullOnNetworkError(): void
    {
        $client = $this->clientWithFakeEndpoint();
        $this->assertNull($client->getRateLimit());
    }

    public function testGetContentsReturnsNullOnNetworkError(): void
    {
        $client = $this->clientWithFakeEndpoint();
        $this->assertNull($client->getContents('owner', 'repo', 'README.md'));
    }

    // -------------------------------------------------------------------------
    // Error handling — throw mode (throwOnError = true)
    // -------------------------------------------------------------------------

    public function testGetUserThrowsRequestExceptionOnError(): void
    {
        $client = $this->clientWithFakeEndpoint(null, null, null, true);
        $this->expectException(RequestException::class);
        $client->getUser('octocat');
    }

    public function testGetRepositoryThrowsRequestExceptionOnError(): void
    {
        $client = $this->clientWithFakeEndpoint(null, null, null, true);
        $this->expectException(RequestException::class);
        $client->getRepository('owner', 'repo');
    }

    public function testGetIssueThrowsRequestExceptionOnError(): void
    {
        $client = $this->clientWithFakeEndpoint(null, null, null, true);
        $this->expectException(RequestException::class);
        $client->getIssue('owner', 'repo', 1);
    }

    public function testGetRateLimitThrowsRequestExceptionOnError(): void
    {
        $client = $this->clientWithFakeEndpoint(null, null, null, true);
        $this->expectException(RequestException::class);
        $client->getRateLimit();
    }

    public function testGetContentsThrowsRequestExceptionOnError(): void
    {
        $client = $this->clientWithFakeEndpoint(null, null, null, true);
        $this->expectException(RequestException::class);
        $client->getContents('owner', 'repo', 'README.md');
    }

    // -------------------------------------------------------------------------
    // Error handling — ignored status codes
    // -------------------------------------------------------------------------

    public function testGetUserReturnsNullWhenCurlErrorStatusIsIgnored(): void
    {
        // cURL connection failures produce status code -1, which we ignore here.
        $client = $this->clientWithFakeEndpoint(null, null, null, false, [-1]);
        $this->assertNull($client->getUser('octocat'));
    }

    public function testGetRepositoryReturnsNullWhenCurlErrorStatusIsIgnored(): void
    {
        $client = $this->clientWithFakeEndpoint(null, null, null, false, [-1]);
        $this->assertNull($client->getRepository('owner', 'repo'));
    }

    public function testIgnoredStatusCodePreventsThrowing(): void
    {
        // Even with throwOnError = true, an ignored code must NOT throw.
        $client = $this->clientWithFakeEndpoint(null, null, null, true, [-1]);
        // Should return null without throwing.
        $result = $client->getUser('octocat');
        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // Logger integration
    // -------------------------------------------------------------------------

    public function testLoggerIsCalledOnError(): void
    {
        $logData = new \stdClass();
        $logData->calls = 0;

        $logger = new class ($logData) implements ILogger {
            private \stdClass $data;
            public function __construct(\stdClass $data)
            {
                $this->data = $data;
            }
            public function log(string $message, object $details): bool
            {
                $this->data->calls++;
                return true;
            }
        };

        $client = $this->clientWithFakeEndpoint(null, null, $logger);
        $client->getUser('octocat');

        $this->assertGreaterThan(0, $logData->calls);
    }

    public function testLoggerIsNotCalledWhenStatusCodeIsIgnored(): void
    {
        $logData = new \stdClass();
        $logData->calls = 0;

        $logger = new class ($logData) implements ILogger {
            private \stdClass $data;
            public function __construct(\stdClass $data)
            {
                $this->data = $data;
            }
            public function log(string $message, object $details): bool
            {
                $this->data->calls++;
                return true;
            }
        };

        // cURL errors produce status -1; when it is ignored, the logger must not fire.
        $client = $this->clientWithFakeEndpoint(null, null, $logger, false, [-1]);
        $client->getUser('octocat');

        $this->assertSame(0, $logData->calls);
    }

    // -------------------------------------------------------------------------
    // Query parameters are forwarded
    // -------------------------------------------------------------------------

    public function testListIssuesWithParamsDoesNotThrowBeforeRequest(): void
    {
        $client = $this->clientWithFakeEndpoint();
        // The request will fail (network), but the method must accept params without error.
        $this->assertNull($client->listIssues('owner', 'repo', ['state' => 'closed', 'per_page' => 10]));
    }

    public function testListPullRequestsWithParamsDoesNotThrowBeforeRequest(): void
    {
        $client = $this->clientWithFakeEndpoint();
        $this->assertNull($client->listPullRequests('owner', 'repo', ['state' => 'open']));
    }

    public function testGetContentsWithRefDoesNotThrowBeforeRequest(): void
    {
        $client = $this->clientWithFakeEndpoint();
        $this->assertNull($client->getContents('owner', 'repo', 'src/file.php', 'main'));
    }

    public function testListReleasesWithParamsDoesNotThrowBeforeRequest(): void
    {
        $client = $this->clientWithFakeEndpoint();
        $this->assertNull($client->listReleases('owner', 'repo', ['per_page' => 5]));
    }
}
