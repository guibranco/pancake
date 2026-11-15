<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit;

use GuiBranco\Pancake\Grimoire;
use GuiBranco\Pancake\GrimoireException;
use GuiBranco\Pancake\GrimoireSecret;
use GuiBranco\Pancake\Request;
use GuiBranco\Pancake\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GrimoireTest extends TestCase
{
    private const SECRET_BODY = [
        'name'       => 'database-password',
        'value'      => 's3cr3t',
        'properties' => [
            'enabled'   => true,
            'expiresOn' => null,
            'notBefore' => null,
            'version'   => 2,
            'createdOn' => '2026-01-01T00:00:00Z',
            'updatedOn' => null,
        ],
    ];

    private const CONFIG_BODY = [
        'items' => [
            ['key' => 'Database:Host', 'value' => 'osasco', 'label' => 'production'],
            ['key' => 'Database:Port', 'value' => '3306', 'label' => 'production'],
            ['key' => 'Feature:DarkMode', 'value' => 'true', 'label' => 'production'],
        ],
    ];

    /** @var string[] */
    private array $envNames = [];

    protected function tearDown(): void
    {
        foreach ($this->envNames as $name) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }
    }

    private static function json(int $status, array $body): Response
    {
        return Response::success((string) json_encode($body), 'http://mock', [], $status);
    }

    private function mockRequest(Response ...$responses): Request
    {
        $mock = $this->createStub(Request::class);
        $mock->method('get')->willReturnOnConsecutiveCalls(...$responses);
        return $mock;
    }

    private function client(Request $request, int $ttl = 60, ?\Closure $clock = null): Grimoire
    {
        return new Grimoire(
            baseUrl:     'https://grimoire.example.com',
            apiKey:      'grm_key',
            environment: 'production',
            request:     $request,
            cacheTtl:    $ttl,
            clock:       $clock,
        );
    }

    // ── Construction ─────────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('invalidConstructorArguments')]
    public function constructorRejectsInvalidArguments(string $url, string $key, string $env, int $ttl): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Grimoire($url, $key, $env, $this->createStub(Request::class), $ttl);
    }

    public static function invalidConstructorArguments(): array
    {
        return [
            'empty url'    => ['', 'k', 'e', 60],
            'empty key'    => ['https://x', ' ', 'e', 60],
            'empty env'    => ['https://x', 'k', '', 60],
            'negative ttl' => ['https://x', 'k', 'e', -1],
        ];
    }

    #[Test]
    public function constructorRejectsZeroTimeout(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Grimoire('https://x', 'k', 'e', timeout: 0);
    }

    #[Test]
    public function ownRequestGetsTheTimeout(): void
    {
        $client   = new Grimoire('https://x', 'k', 'e', timeout: 7);
        $property = new \ReflectionProperty(Grimoire::class, 'request');

        $this->assertSame(7, $property->getValue($client)->getTimeout());
    }

    // ── Secrets ──────────────────────────────────────────────────────────────

    #[Test]
    public function getSecretSendsApiKeyAndEnvironment(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->with(
                '/consumer/secrets/my%20secret?environment=production',
                $this->callback(fn (array $h) => in_array('X-Api-Key: grm_key', $h, true)),
            )
            ->willReturn(self::json(200, self::SECRET_BODY));

        $secret = $this->client($request)->getSecret('my secret');

        $this->assertInstanceOf(GrimoireSecret::class, $secret);
        $this->assertSame('s3cr3t', $secret->value);
        $this->assertSame(2, $secret->version);
        $this->assertTrue($secret->enabled);
        $this->assertSame('2026-01-01', $secret->createdOn?->format('Y-m-d'));
        $this->assertNull($secret->expiresOn);
    }

    #[Test]
    public function missingSecretReturnsNullOrDefault(): void
    {
        $request = $this->mockRequest(self::json(404, ['title' => 'Secret not found.']), self::json(404, []));
        $client  = $this->client($request);

        $this->assertNull($client->getSecret('nope'));
        $this->assertSame('fallback', $client->getSecretValue('nope', 'fallback'));
    }

    #[Test]
    public function requireSecretThrowsWhenMissing(): void
    {
        $client = $this->client($this->mockRequest(self::json(404, [])));

        $this->expectException(GrimoireException::class);
        $this->expectExceptionCode(404);
        $client->requireSecret('nope');
    }

    #[Test]
    #[DataProvider('errorResponses')]
    public function errorsThrowWithStatusCode(Response $response, int $expectedStatus): void
    {
        $client = $this->client($this->mockRequest($response));

        try {
            $client->getSecret('database-password');
            $this->fail('Expected GrimoireException');
        } catch (GrimoireException $e) {
            $this->assertSame($expectedStatus, $e->getStatusCode());
        }
    }

    public static function errorResponses(): array
    {
        return [
            'unauthorized'  => [Response::success('{}', 'http://mock', [], 401), 401],
            'forbidden'     => [Response::success('{}', 'http://mock', [], 403), 403],
            'server error'  => [Response::success('oops', 'http://mock', [], 500), 500],
            'transport'     => [Response::error('Could not resolve host', 'http://mock', -1), -1],
            'invalid json'  => [Response::success('not json', 'http://mock', [], 200), 200],
            'missing value' => [Response::success('{"name":"x"}', 'http://mock', [], 200), 0],
        ];
    }

    #[Test]
    public function secretsAreCachedUntilTtlExpires(): void
    {
        $now     = 1000;
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))
            ->method('get')
            ->willReturn(self::json(200, self::SECRET_BODY));

        $client = $this->client($request, 60, function () use (&$now) {
            return $now;
        });

        $client->getSecret('database-password');
        $client->getSecret('database-password');
        $now += 61;
        $client->getSecret('database-password');
    }

    #[Test]
    public function cachedSecretIsNotServedPastItsExpiry(): void
    {
        $now                              = (new \DateTimeImmutable('2026-01-01T00:00:00Z'))->getTimestamp();
        $body                             = self::SECRET_BODY;
        $body['properties']['expiresOn'] = '2026-01-01T00:00:30Z';

        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(self::json(200, $body), self::json(404, []));

        $client = $this->client($request, 60, function () use (&$now) {
            return $now;
        });

        $this->assertNotNull($client->getSecret('database-password'));
        $now += 10;
        $this->assertNotNull($client->getSecret('database-password'));
        // Still inside the 60s TTL, but past expiresOn: Grimoire is asked again.
        $now += 30;
        $this->assertNull($client->getSecret('database-password'));
    }

    #[Test]
    public function zeroTtlDisablesCaching(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))->method('get')->willReturn(self::json(200, self::SECRET_BODY));

        $client = $this->client($request, 0);
        $client->getSecret('database-password');
        $client->getSecret('database-password');
    }

    #[Test]
    public function clearCacheForcesRefetch(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))->method('get')->willReturn(self::json(200, self::SECRET_BODY));

        $client = $this->client($request);
        $client->getSecret('database-password');
        $client->clearCache();
        $client->getSecret('database-password');
    }

    #[Test]
    public function secretValueIsHiddenFromDebugOutput(): void
    {
        $secret = GrimoireSecret::fromArray(self::SECRET_BODY);

        $this->assertStringNotContainsString('s3cr3t', print_r($secret, true));
    }

    // ── Configuration ────────────────────────────────────────────────────────

    #[Test]
    public function getConfigurationsReturnsKeyValueMap(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->with('/consumer/configurations?environment=production', $this->anything())
            ->willReturn(self::json(200, self::CONFIG_BODY));

        $client = $this->client($request);

        $this->assertSame(
            ['Database:Host' => 'osasco', 'Database:Port' => '3306', 'Feature:DarkMode' => 'true'],
            $client->getConfigurations(),
        );
        // Served from cache: the mock expects a single call.
        $this->assertSame('true', $client->getConfiguration('feature:darkmode'));
        $this->assertSame('x', $client->getConfiguration('Missing', 'x'));
        $this->assertSame(['Host' => 'osasco', 'Port' => '3306'], $client->getSection('Database'));
    }

    #[Test]
    public function numericConfigurationKeysWork(): void
    {
        $body   = ['items' => [['key' => '123', 'value' => 'numeric', 'label' => 'production']]];
        $client = $this->client($this->mockRequest(self::json(200, $body)));

        $this->assertSame('numeric', $client->getConfiguration('123'));
        $this->assertSame([], $client->getSection('Database'));
    }

    #[Test]
    public function unknownEnvironmentThrows(): void
    {
        $client = $this->client($this->mockRequest(self::json(404, [])));

        $this->expectException(GrimoireException::class);
        $this->expectExceptionCode(404);
        $client->getConfigurations();
    }

    #[Test]
    public function malformedConfigurationPayloadThrows(): void
    {
        $client = $this->client($this->mockRequest(self::json(200, ['nope' => []])));

        $this->expectException(GrimoireException::class);
        $client->getConfigurations();
    }

    // ── Environment loading ──────────────────────────────────────────────────

    #[Test]
    #[DataProvider('environmentNames')]
    public function toEnvironmentNameNormalisesKeys(string $key, string $expected): void
    {
        $this->assertSame($expected, Grimoire::toEnvironmentName($key));
    }

    public static function environmentNames(): array
    {
        return [
            ['database-password', 'DATABASE_PASSWORD'],
            ['Feature:DarkMode', 'FEATURE__DARKMODE'],
            ['ConnectionStrings:Main', 'CONNECTIONSTRINGS__MAIN'],
            ['a.b c', 'A_B_C'],
            ['ALREADY_OK', 'ALREADY_OK'],
        ];
    }

    #[Test]
    public function loadIntoEnvironmentSetsVariables(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $secretBody         = self::SECRET_BODY;
        $secretBody['name'] = "pk-{$suffix}";
        $configBody         = ['items' => [['key' => "Cfg:{$suffix}", 'value' => 'on', 'label' => 'production']]];

        $client = $this->client($this->mockRequest(self::json(200, $configBody), self::json(200, $secretBody)));

        $set = $client->loadIntoEnvironment(["pk-{$suffix}"]);
        $this->envNames = $set;

        $secretVar = 'PK_' . strtoupper($suffix);
        $configVar = 'CFG__' . strtoupper($suffix);
        $this->assertEqualsCanonicalizing([$secretVar, $configVar], $set);
        $this->assertSame('s3cr3t', getenv($secretVar));
        $this->assertSame('s3cr3t', $_ENV[$secretVar]);
        $this->assertSame('on', getenv($configVar));
    }

    #[Test]
    public function loadIntoEnvironmentKeepsExistingUnlessOverwrite(): void
    {
        $name = 'PK_EXISTING_' . strtoupper(bin2hex(random_bytes(4)));
        putenv("{$name}=original");
        $this->envNames = [$name];

        $body         = self::SECRET_BODY;
        $body['name'] = strtolower(str_replace('_', '-', $name));
        $request      = $this->createStub(Request::class);
        $request->method('get')->willReturn(self::json(200, $body));

        $client = $this->client($request);

        $this->assertSame([], $client->loadIntoEnvironment([$body['name']], false));
        $this->assertSame('original', getenv($name));

        $this->assertSame([$name], $client->loadIntoEnvironment([$body['name']], false, true));
        $this->assertSame('s3cr3t', getenv($name));
    }

    #[Test]
    public function loadIntoEnvironmentKeepsValuesOnlyInSuperglobals(): void
    {
        $name             = 'PK_SUPERGLOBAL_' . strtoupper(bin2hex(random_bytes(4)));
        $_SERVER[$name]   = 'from-server';
        $this->envNames   = [$name];
        $body             = ['items' => [['key' => $name, 'value' => 'from-grimoire', 'label' => 'production']]];

        $client = $this->client($this->mockRequest(self::json(200, $body)));

        $this->assertSame([], $client->loadIntoEnvironment([]));
        $this->assertFalse(getenv($name));
        $this->assertSame('from-server', $_SERVER[$name]);
    }

    #[Test]
    public function loadIntoEnvironmentFailsOnMissingSecretByDefault(): void
    {
        $client = $this->client($this->mockRequest(self::json(404, []), self::json(404, [])));

        try {
            $client->loadIntoEnvironment(['missing-one'], false);
            $this->fail('Expected GrimoireException');
        } catch (GrimoireException $e) {
            $this->assertStringContainsString('missing-one', $e->getMessage());
        }

        $this->assertSame([], $client->loadIntoEnvironment(['missing-one'], false, false, false));
    }

    // ── Health ───────────────────────────────────────────────────────────────

    #[Test]
    public function isHealthyReflectsStatusCode(): void
    {
        $client = $this->client($this->mockRequest(self::json(200, ['status' => 'ok']), self::json(503, [])));

        $this->assertTrue($client->isHealthy());
        $this->assertFalse($client->isHealthy());
    }
}
