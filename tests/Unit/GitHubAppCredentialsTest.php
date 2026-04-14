<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit;

use GuiBranco\Pancake\GitHubAppCredentials;
use PHPUnit\Framework\TestCase;

final class GitHubAppCredentialsTest extends TestCase
{
    public function testCanCreateWithAppIdAndToken(): void
    {
        $creds = new GitHubAppCredentials(123456, 'ghs_installationToken');
        $this->assertInstanceOf(GitHubAppCredentials::class, $creds);
    }

    public function testGetAppIdReturnsCorrectValue(): void
    {
        $creds = new GitHubAppCredentials(42, 'token');
        $this->assertSame(42, $creds->getAppId());
    }

    public function testGetInstallationTokenReturnsCorrectValue(): void
    {
        $creds = new GitHubAppCredentials(1, 'ghs_abc123');
        $this->assertSame('ghs_abc123', $creds->getInstallationToken());
    }

    public function testGetAuthorizationHeaderReturnsBearerHeader(): void
    {
        $creds = new GitHubAppCredentials(1, 'ghs_myToken');
        $this->assertSame('Authorization: Bearer ghs_myToken', $creds->getAuthorizationHeader());
    }

    public function testAuthorizationHeaderContainsInstallationToken(): void
    {
        $token = 'ghs_' . str_repeat('x', 32);
        $creds = new GitHubAppCredentials(99, $token);
        $this->assertStringContainsString($token, $creds->getAuthorizationHeader());
    }

    public function testDifferentAppIdsAreStoredIndependently(): void
    {
        $creds1 = new GitHubAppCredentials(100, 'token-a');
        $creds2 = new GitHubAppCredentials(200, 'token-b');

        $this->assertSame(100, $creds1->getAppId());
        $this->assertSame(200, $creds2->getAppId());
        $this->assertSame('token-a', $creds1->getInstallationToken());
        $this->assertSame('token-b', $creds2->getInstallationToken());
    }
}
