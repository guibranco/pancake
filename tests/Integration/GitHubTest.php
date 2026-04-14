<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Integration;

use GuiBranco\Pancake\GitHub;
use GuiBranco\Pancake\GitHubAppCredentials;
use GuiBranco\Pancake\RequestException;
use GuiBranco\Pancake\Response;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the GitHub API client.
 *
 * These tests exercise the client against the real GitHub REST API and require
 * outbound internet access. Authenticated tests are skipped when the
 * GITHUB_TOKEN environment variable is not set.
 *
 * Run only the integration suite:
 *   ./vendor/bin/phpunit --configuration tests/phpunit.xml --testsuite integration
 */
final class GitHubTest extends TestCase
{
    private GitHub $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new GitHub();
    }

    // -------------------------------------------------------------------------
    // Public / unauthenticated endpoints
    // -------------------------------------------------------------------------

    public function testGetRateLimitReturnsSuccessfulResponse(): void
    {
        $response = $this->client->getRateLimit();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetRateLimitBodyContainsResourcesKey(): void
    {
        $response = $this->client->getRateLimit();
        $this->assertInstanceOf(Response::class, $response);

        $data = $response->getBodyAsJson();
        $this->assertIsObject($data);
        $this->assertObjectHasProperty('resources', $data);
    }

    public function testGetPublicUserReturnsSuccessfulResponse(): void
    {
        $response = $this->client->getUser('octocat');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetPublicUserBodyContainsLogin(): void
    {
        $response = $this->client->getUser('octocat');
        $this->assertInstanceOf(Response::class, $response);

        $user = $response->getBodyAsJson();
        $this->assertIsObject($user);
        $this->assertSame('octocat', $user->login);
    }

    public function testGetPublicRepositoryReturnsSuccessfulResponse(): void
    {
        $response = $this->client->getRepository('octocat', 'Hello-World');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetPublicRepositoryBodyContainsName(): void
    {
        $response = $this->client->getRepository('octocat', 'Hello-World');
        $this->assertInstanceOf(Response::class, $response);

        $repo = $response->getBodyAsJson();
        $this->assertIsObject($repo);
        $this->assertSame('Hello-World', $repo->name);
    }

    public function testListPublicUserRepositoriesReturnsSuccessfulResponse(): void
    {
        $response = $this->client->listUserRepositories('octocat', ['per_page' => 5]);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testListPublicUserRepositoriesBodyIsArray(): void
    {
        $response = $this->client->listUserRepositories('octocat', ['per_page' => 3]);
        $this->assertInstanceOf(Response::class, $response);

        $repos = $response->getBodyAsArray();
        $this->assertIsArray($repos);
    }

    // -------------------------------------------------------------------------
    // Error-handling integration
    // -------------------------------------------------------------------------

    public function testGetNonExistentUserReturnsNull(): void
    {
        $client = new GitHub(null, null, null, false, [404]);
        $result = $client->getUser('this-user-should-not-exist-xyzxyz123456789');
        $this->assertNull($result);
    }

    public function testGetNonExistentUserThrowsWhenThrowOnErrorEnabled(): void
    {
        $client = new GitHub(null, null, null, true);
        $this->expectException(RequestException::class);
        $client->getUser('this-user-should-not-exist-xyzxyz123456789');
    }

    public function testGetNonExistentRepositoryReturnsNull(): void
    {
        $client = new GitHub(null, null, null, false, [404]);
        $result = $client->getRepository('octocat', 'this-repo-does-not-exist-xyzxyz');
        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // Authenticated endpoints (require GITHUB_TOKEN env var)
    // -------------------------------------------------------------------------

    public function testGetAuthenticatedUserWithValidToken(): void
    {
        $token = getenv('GITHUB_TOKEN');
        if ($token === false || $token === '') {
            $this->markTestSkipped('GITHUB_TOKEN environment variable is not set.');
        }

        $client = new GitHub($token);
        $response = $client->getAuthenticatedUser();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $user = $response->getBodyAsJson();
        $this->assertIsObject($user);
        $this->assertNotEmpty($user->login);
    }

    public function testListAuthenticatedUserRepositoriesWithValidToken(): void
    {
        $token = getenv('GITHUB_TOKEN');
        if ($token === false || $token === '') {
            $this->markTestSkipped('GITHUB_TOKEN environment variable is not set.');
        }

        $client = new GitHub($token);
        $response = $client->listAuthenticatedUserRepositories(['per_page' => 5]);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetAuthenticatedUserWithInvalidTokenReturnsNull(): void
    {
        $client = new GitHub('ghp_invalidTokenForTesting', null, null, false, [401]);
        $result = $client->getAuthenticatedUser();
        $this->assertNull($result);
    }

    public function testGetAuthenticatedUserWithInvalidTokenThrowsWhenConfigured(): void
    {
        $client = new GitHub('ghp_invalidTokenForTesting', null, null, true);
        $this->expectException(RequestException::class);
        $client->getAuthenticatedUser();
    }

    // -------------------------------------------------------------------------
    // GitHub App credentials
    // -------------------------------------------------------------------------

    public function testClientWithAppCredentialsFallsBackToAppTokenWhenNoUserToken(): void
    {
        // An obviously invalid installation token produces a 401.
        $creds = new GitHubAppCredentials(99999, 'ghs_invalidInstallationToken');
        $client = new GitHub(null, $creds, null, false, [401]);
        $result = $client->getAuthenticatedUser();
        $this->assertNull($result);
    }

    public function testClientPrefersUserTokenOverAppCredentials(): void
    {
        $creds = new GitHubAppCredentials(99999, 'ghs_invalidInstallationToken');
        // Both tokens are invalid, but the user token takes precedence;
        // we just verify the request completes without a PHP-level error.
        $client = new GitHub('ghp_invalidUserToken', $creds, null, false, [401]);
        $result = $client->getAuthenticatedUser();
        $this->assertNull($result);
    }
}
