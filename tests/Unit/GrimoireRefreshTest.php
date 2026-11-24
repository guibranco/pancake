<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit;

use GuiBranco\Pancake\Grimoire;
use GuiBranco\Pancake\GrimoireException;
use GuiBranco\Pancake\GrimoireFileSnapshotStore;
use GuiBranco\Pancake\GrimoireMemorySnapshotStore;
use GuiBranco\Pancake\Request;
use GuiBranco\Pancake\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Sentinel-based configuration refresh (guibranco/grimoire-api#95, guibranco/pancake#688). */
final class GrimoireRefreshTest extends TestCase
{
    /** @var array<string, string> what the fake Grimoire serves, sentinels included */
    private array $values = [];

    private bool $down = false;

    /** @var string[] request paths, in order */
    private array $calls = [];

    private int $now = 1_000_000;

    private ?string $tempDir = null;

    protected function tearDown(): void
    {
        if ($this->tempDir !== null) {
            foreach (glob($this->tempDir . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->tempDir);
        }
    }

    private function request(): Request
    {
        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnCallback(function (string $url, array $headers): Response {
            $this->calls[] = $url;
            if ($this->down) {
                return Response::error('Connection refused', $url, -1);
            }
            $path = (string) parse_url($url, PHP_URL_PATH);
            if ($path === '/consumer/configurations') {
                $items = [];
                foreach ($this->values as $key => $value) {
                    if (!in_array($key, Grimoire::SENTINEL_KEYS, true)) {
                        $items[] = ['key' => $key, 'value' => $value, 'label' => 'production'];
                    }
                }

                return Response::success((string) json_encode(['items' => $items]), $url, [], 200);
            }

            $key = rawurldecode(substr($path, strlen('/consumer/configurations/')));
            if (!array_key_exists($key, $this->values)) {
                return Response::success('{"title":"Not Found"}', $url, [], 404);
            }
            $etag = '"' . substr(hash('sha256', $key . '=' . $this->values[$key]), 0, 16) . '"';
            if (in_array("If-None-Match: {$etag}", $headers, true)) {
                return Response::success('', $url, ['ETag' => $etag], 304);
            }
            $body = ['key' => $key, 'value' => $this->values[$key], 'label' => 'production'];

            return Response::success((string) json_encode($body), $url, ['ETag' => $etag], 200);
        });

        return $request;
    }

    private function client(
        ?\GuiBranco\Pancake\GrimoireSnapshotStore $store = null,
        int $interval = 30,
        ?Request $request = null,
    ): Grimoire {
        return new Grimoire(
            baseUrl:         'https://grimoire.example.com',
            apiKey:          'grm_key',
            environment:     'production',
            request:         $request ?? $this->request(),
            clock:           fn (): int => $this->now,
            refreshInterval: $interval,
            snapshotStore:   $store ?? new GrimoireMemorySnapshotStore(fn (): int => $this->now),
        );
    }

    private function listCalls(): int
    {
        return count(array_filter($this->calls, static fn (string $u): bool => str_starts_with($u, '/consumer/configurations?')));
    }

    #[Test]
    public function firstReadLoadsEverythingAndRecordsTheSentinels(): void
    {
        $this->values = ['A' => '1', 'Sentinel' => '100'];
        $grimoire     = $this->client();

        $this->assertSame(['A' => '1'], $grimoire->getConfigurations());
        $this->assertSame(1, $this->listCalls());
        // List + both sentinels (Global:Sentinel is a 404).
        $this->assertCount(3, $this->calls);
    }

    #[Test]
    public function withinTheIntervalNothingIsRequested(): void
    {
        $this->values = ['A' => '1', 'Sentinel' => '100'];
        $grimoire     = $this->client();
        $grimoire->getConfigurations();
        $this->calls = [];

        $this->now += 20;
        $grimoire->getConfiguration('A');

        $this->assertSame([], $this->calls);
    }

    #[Test]
    public function stagedChangesWaitForTheSentinel(): void
    {
        $this->values = ['A' => '1', 'Sentinel' => '100'];
        $grimoire     = $this->client();
        $grimoire->getConfigurations();

        $this->values['A'] = '2'; // changed, not published
        $this->now += 40;
        $this->assertSame('1', $grimoire->getConfiguration('A'));
        $this->assertSame(1, $this->listCalls());

        $this->values['Sentinel'] = '101'; // published
        $this->now += 40;
        $this->assertSame('2', $grimoire->getConfiguration('A'));
        $this->assertSame(2, $this->listCalls());
    }

    #[Test]
    public function aGlobalSentinelAppearingReloads(): void
    {
        $this->values = ['A' => '1'];
        $grimoire     = $this->client();
        $grimoire->getConfigurations();

        $this->values['Region']          = 'eu';
        $this->values['Global:Sentinel'] = '5';

        $this->assertTrue($grimoire->refresh());
        $this->assertSame('eu', $grimoire->getConfiguration('Region'));
    }

    #[Test]
    public function refreshReportsNoChangeWhenTheSentinelsAreTheSame(): void
    {
        $this->values = ['A' => '1', 'Sentinel' => '100'];
        $grimoire     = $this->client();
        $grimoire->getConfigurations();

        $this->assertFalse($grimoire->refresh());
    }

    #[Test]
    public function failuresKeepTheLastValuesAndBackOff(): void
    {
        $this->values = ['A' => '1', 'Sentinel' => '100'];
        $grimoire     = $this->client();
        $grimoire->getConfigurations();

        $this->down = true;
        $this->now += 40;
        $this->assertSame('1', $grimoire->getConfiguration('A'));
        $this->calls = [];

        // Backoff after one failure: 2 × 30 s, so a check 40 s later is skipped...
        $this->now += 40;
        $this->assertSame('1', $grimoire->getConfiguration('A'));
        $this->assertSame([], $this->calls);

        // ...and one 61 s after the failure goes through.
        $this->down              = false;
        $this->values['Sentinel'] = '101';
        $this->values['A']        = '2';
        $this->now += 21;
        $this->assertSame('2', $grimoire->getConfiguration('A'));
    }

    #[Test]
    public function withoutASnapshotAFailureIsThrown(): void
    {
        $this->down = true;
        $this->expectException(GrimoireException::class);
        $this->client()->getConfigurations();
    }

    #[Test]
    public function workersShareOneSnapshot(): void
    {
        $this->values = ['A' => '1', 'Sentinel' => '100'];
        $store        = new GrimoireMemorySnapshotStore(fn (): int => $this->now);
        $this->client($store)->getConfigurations();
        $this->calls = [];

        // A second "request" (new instance) reuses the snapshot without calling Grimoire.
        $this->assertSame('1', $this->client($store)->getConfiguration('A'));
        $this->assertSame([], $this->calls);
    }

    #[Test]
    public function whileAnotherWorkerRefreshesTheSnapshotIsServed(): void
    {
        $this->values = ['A' => '1', 'Sentinel' => '100'];
        $store        = new GrimoireMemorySnapshotStore(fn (): int => $this->now);
        $grimoire     = $this->client($store);
        $grimoire->getConfigurations();
        $this->calls = [];

        $key = (new \ReflectionProperty(Grimoire::class, 'cacheKey'))->getValue($grimoire);
        $this->assertTrue($store->lock($key, 300));
        $this->now += 40;

        $this->assertSame('1', $grimoire->getConfiguration('A'));
        $this->assertSame([], $this->calls);
    }

    #[Test]
    public function aPublishClearsCachedSecrets(): void
    {
        $this->values = ['A' => '1', 'Sentinel' => '100'];
        $secretCalls  = 0;
        $request      = $this->request();
        $inner        = $request;
        $request      = $this->createStub(Request::class);
        $request->method('get')->willReturnCallback(function (string $url, array $headers) use ($inner, &$secretCalls): Response {
            if (str_starts_with($url, '/consumer/secrets/')) {
                $secretCalls++;
                $body = ['name' => 'db', 'value' => "v{$secretCalls}", 'properties' => ['enabled' => true, 'version' => $secretCalls]];

                return Response::success((string) json_encode($body), $url, [], 200);
            }

            return $inner->get($url, $headers);
        });
        $grimoire = $this->client(request: $request);
        $grimoire->getConfigurations();

        $this->assertSame('v1', $grimoire->getSecretValue('db'));
        $this->assertSame('v1', $grimoire->getSecretValue('db')); // cached

        $this->values['Sentinel'] = '101';
        $grimoire->refresh();

        $this->assertSame('v2', $grimoire->getSecretValue('db'));
    }

    #[Test]
    public function theFileStoreKeepsConfigurationOnly(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/pancake-grimoire-test-' . bin2hex(random_bytes(4));
        $store         = new GrimoireFileSnapshotStore($this->tempDir, fn (): int => $this->now);
        $this->values  = ['A' => '1', 'Sentinel' => '100'];
        $grimoire      = $this->client($store);
        $grimoire->getConfigurations();

        $files = glob($this->tempDir . '/*.json') ?: [];
        $this->assertCount(1, $files);
        $this->assertSame('0600', substr(sprintf('%o', fileperms($files[0])), -4));
        $this->assertSame(['A' => '1'], json_decode((string) file_get_contents($files[0]), true)['configurations']);

        // Another instance reads it back.
        $this->calls = [];
        $this->assertSame('1', $this->client(new GrimoireFileSnapshotStore($this->tempDir))->getConfiguration('A'));
        $this->assertSame([], $this->calls);
    }

    #[Test]
    public function theFileStoreLockIsExclusiveAndExpires(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/pancake-grimoire-test-' . bin2hex(random_bytes(4));
        $store         = new GrimoireFileSnapshotStore($this->tempDir, fn (): int => $this->now);

        $this->assertTrue($store->lock('k', 10));
        $this->assertFalse($store->lock('k', 10));
        $this->now = time() + 60; // the lock file's mtime is real time
        $this->assertTrue($store->lock('k', 10));
        $store->unlock('k');
        $this->assertTrue($store->lock('k', 10));
    }

    #[Test]
    public function withoutRefreshIntervalTheTtlCacheIsUsed(): void
    {
        $this->values = ['A' => '1', 'Sentinel' => '100'];
        $grimoire     = new Grimoire('https://g', 'k', 'production', $this->request(), 60, clock: fn (): int => $this->now);

        $grimoire->getConfigurations();

        $this->assertFalse($grimoire->refresh());
        $this->assertSame(['/consumer/configurations?environment=production'], $this->calls);
    }

    #[Test]
    public function rejectsANegativeInterval(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Grimoire('https://g', 'k', 'e', $this->createStub(Request::class), refreshInterval: -1);
    }
}
