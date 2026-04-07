<?php

namespace GuiBranco\Pancake\Tests\Integration;

use GuiBranco\Pancake\CircuitBreaker;
use GuiBranco\Pancake\Exceptions\CircuitBreakerOpenException;
use GuiBranco\Pancake\MemoryCacheInterface;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for {@see CircuitBreaker} against a real HTTP endpoint.
 *
 * These tests rely on WireMock being available at the address defined by the
 * `WIREMOCK_URL` environment variable (default: `http://localhost:8080`).
 *
 * WireMock must be started before the test suite, for example via Docker:
 *
 * ```bash
 * docker run -d --rm -p 8080:8080 wiremock/wiremock:latest
 * ```
 *
 * Or via the project's existing docker-compose setup.
 *
 * ### What is tested
 *
 * Each test scenario stubs a specific URL path in WireMock and exercises the
 * circuit breaker end-to-end using real `curl`/stream HTTP calls, validating
 * that the circuit transitions correctly when a real I/O operation fails or
 * succeeds.
 *
 * The MemoryCache is still replaced by a fast in-memory stub (no filesystem I/O)
 * so state resets cleanly between tests.
 */
class CircuitBreakerHttpIntegrationTest extends TestCase
{
    private const WIREMOCK_URL = 'http://localhost:8080';

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Returns the WireMock base URL from the environment, or the local default.
     */
    private function wireMockUrl(): string
    {
        return rtrim(getenv('WIREMOCK_URL') ?: self::WIREMOCK_URL, '/');
    }

    /**
     * Returns true when WireMock is reachable.
     */
    private function wireMockAvailable(): bool
    {
        $ctx = stream_context_create(['http' => ['timeout' => 2]]);
        return @file_get_contents($this->wireMockUrl() . '/__admin/health', false, $ctx) !== false;
    }

    /**
     * Registers a WireMock stub via its REST admin API.
     *
     * @param array $mapping A WireMock stub mapping as an array (will be JSON-encoded).
     */
    private function stubWireMock(array $mapping): void
    {
        $json = json_encode($mapping, JSON_THROW_ON_ERROR);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nContent-Length: " . strlen($json),
                'content' => $json,
                'timeout' => 5,
            ],
        ]);
        file_get_contents($this->wireMockUrl() . '/__admin/mappings', false, $ctx);
    }

    /**
     * Removes all WireMock stubs (called in tearDown).
     */
    private function resetWireMock(): void
    {
        $ctx = stream_context_create(['http' => ['method' => 'DELETE', 'timeout' => 5]]);
        @file_get_contents($this->wireMockUrl() . '/__admin/mappings', false, $ctx);
    }

    /**
     * Performs a simple GET request and returns the HTTP status code.
     *
     * @throws \RuntimeException on non-2xx responses.
     */
    private function httpGet(string $url): string
    {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);
        $body = file_get_contents($url, false, $ctx);
        $status = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0], $m);
            $status = (int) ($m[1] ?? 0);
        }

        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException("HTTP {$status} from {$url}");
        }

        return (string) $body;
    }

    /**
     * Builds a simple in-memory MemoryCache stub shared across all test methods.
     */
    private function makeCache(array $initial = []): object
    {
        return new class ($initial) implements MemoryCacheInterface {
            private array $data;

            public function __construct(array $data)
            {
                $this->data = $data;
            }

            public function readJsonInMemory(): array
            {
                return $this->data;
            }

            public function writeJsonInMemory(array $data): void
            {
                $this->data = $data;
            }
        };
    }

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        if (!$this->wireMockAvailable()) {
            $this->markTestSkipped(
                'WireMock is not available at ' . $this->wireMockUrl() . '. '
                . 'Start it before running integration tests.'
            );
        }
    }

    protected function tearDown(): void
    {
        $this->resetWireMock();
    }

    // -------------------------------------------------------------------------
    // Scenarios
    // -------------------------------------------------------------------------

    /**
     * @test
     * A healthy endpoint returns 200 → circuit stays closed after multiple calls.
     */
    public function testCircuitRemainsClosedOnSuccessfulHttpCalls(): void
    {
        $this->stubWireMock([
            'request' => ['method' => 'GET', 'url' => '/api/healthy'],
            'response' => ['status' => 200, 'body' => 'OK'],
        ]);

        $cb = new CircuitBreaker($this->makeCache(), 3, 60);
        $url = $this->wireMockUrl() . '/api/healthy';

        for ($i = 0; $i < 5; $i++) {
            $result = $cb->execute(fn () => $this->httpGet($url));
            $this->assertSame('OK', $result);
        }

        $this->assertSame(CircuitBreaker::STATE_CLOSED, $cb->getState());
        $this->assertSame(0, $cb->getFailureCount());
    }

    /**
     * @test
     * Repeated 500 responses should accumulate failures and open the circuit.
     */
    public function testCircuitOpensAfterRepeatedHttpFailures(): void
    {
        $this->stubWireMock([
            'request' => ['method' => 'GET', 'url' => '/api/broken'],
            'response' => ['status' => 500, 'body' => 'Internal Server Error'],
        ]);

        $cb = new CircuitBreaker($this->makeCache(), 3, 60);
        $url = $this->wireMockUrl() . '/api/broken';

        for ($i = 0; $i < 3; $i++) {
            try {
                $cb->execute(fn () => $this->httpGet($url));
            } catch (\RuntimeException) {
                // Expected — each failure increments the counter.
            }
        }

        $this->assertSame(CircuitBreaker::STATE_OPEN, $cb->getState());
    }

    /**
     * @test
     * Once open, subsequent calls must throw CircuitBreakerOpenException even if
     * the endpoint recovers (WireMock now returns 200), because the timeout has
     * not yet elapsed.
     */
    public function testOpenCircuitBlocksCallsEvenAfterEndpointRecovery(): void
    {
        // Phase 1 — failing stubs trip the circuit
        $this->stubWireMock([
            'request' => ['method' => 'GET', 'url' => '/api/flaky'],
            'response' => ['status' => 503, 'body' => 'Service Unavailable'],
        ]);

        $cb = new CircuitBreaker($this->makeCache(), 2, 9999); // very long timeout
        $url = $this->wireMockUrl() . '/api/flaky';

        for ($i = 0; $i < 2; $i++) {
            try {
                $cb->execute(fn () => $this->httpGet($url));
            } catch (\RuntimeException) {
            }
        }

        $this->assertSame(CircuitBreaker::STATE_OPEN, $cb->getState());

        // Phase 2 — endpoint now responds 200, but circuit is still open
        $this->resetWireMock();
        $this->stubWireMock([
            'request' => ['method' => 'GET', 'url' => '/api/flaky'],
            'response' => ['status' => 200, 'body' => 'OK'],
        ]);

        $this->expectException(CircuitBreakerOpenException::class);
        $cb->execute(fn () => $this->httpGet($url));
    }

    /**
     * @test
     * After the timeout elapses, the circuit transitions to half-open and a
     * successful probe call resets it to closed.
     */
    public function testCircuitRecoversThroughHalfOpenAfterTimeout(): void
    {
        $this->stubWireMock([
            'request' => ['method' => 'GET', 'url' => '/api/recover'],
            'response' => ['status' => 200, 'body' => 'Recovered'],
        ]);

        // Seed an open circuit whose timeout has already expired
        $cache = $this->makeCache([
            'state' => 'open',
            'failureCount' => 3,
            'lastFailureTime' => time() - 120, // well past any timeout
        ]);
        $cb = new CircuitBreaker($cache, 3, 60);
        $url = $this->wireMockUrl() . '/api/recover';

        $result = $cb->execute(fn () => $this->httpGet($url));

        $this->assertSame('Recovered', $result);
        $this->assertSame(CircuitBreaker::STATE_CLOSED, $cb->getState());
        $this->assertSame(0, $cb->getFailureCount());
    }

    /**
     * @test
     * A failed probe during half-open returns the circuit to open.
     */
    public function testHalfOpenProbeFailureReturnsCircuitToOpen(): void
    {
        $this->stubWireMock([
            'request' => ['method' => 'GET', 'url' => '/api/probe-fail'],
            'response' => ['status' => 500, 'body' => 'Still broken'],
        ]);

        $cache = $this->makeCache([
            'state' => 'open',
            'failureCount' => 3,
            'lastFailureTime' => time() - 120,
        ]);
        $cb = new CircuitBreaker($cache, 3, 60);
        $url = $this->wireMockUrl() . '/api/probe-fail';

        try {
            $cb->execute(fn () => $this->httpGet($url));
        } catch (\RuntimeException) {
            // Probe failed as expected.
        }

        $this->assertSame(CircuitBreaker::STATE_OPEN, $cb->getState());
    }

    /**
     * @test
     * forceReset() allows the circuit to continue operating after a forced reset,
     * confirmed against a live WireMock endpoint.
     */
    public function testForceResetAllowsCallsAfterOpenState(): void
    {
        $this->stubWireMock([
            'request' => ['method' => 'GET', 'url' => '/api/reset-test'],
            'response' => ['status' => 200, 'body' => 'Fine'],
        ]);

        $cache = $this->makeCache([
            'state' => 'open',
            'failureCount' => 5,
            'lastFailureTime' => time(),
        ]);
        $cb = new CircuitBreaker($cache, 3, 9999);
        $url = $this->wireMockUrl() . '/api/reset-test';

        $cb->forceReset();
        $result = $cb->execute(fn () => $this->httpGet($url));

        $this->assertSame('Fine', $result);
        $this->assertSame(CircuitBreaker::STATE_CLOSED, $cb->getState());
    }

    /**
     * @test
     * Validates the exact failure count matches the number of failed HTTP calls.
     */
    public function testFailureCountMatchesActualFailedHttpCalls(): void
    {
        $this->stubWireMock([
            'request' => ['method' => 'GET', 'url' => '/api/count-test'],
            'response' => ['status' => 503, 'body' => 'Down'],
        ]);

        $cb = new CircuitBreaker($this->makeCache(), 10, 60); // high threshold so it stays open-able
        $url = $this->wireMockUrl() . '/api/count-test';

        for ($i = 0; $i < 4; $i++) {
            try {
                $cb->execute(fn () => $this->httpGet($url));
            } catch (\RuntimeException) {
            }
        }

        $this->assertSame(4, $cb->getFailureCount());
    }

    /**
     * @test
     * A mix of success and failure calls: success in between should reset the counter.
     */
    public function testSuccessBetweenFailuresResetsCounter(): void
    {
        $failUrl = $this->wireMockUrl() . '/api/mix-fail';
        $okUrl = $this->wireMockUrl() . '/api/mix-ok';

        $this->stubWireMock([
            'request' => ['method' => 'GET', 'url' => '/api/mix-fail'],
            'response' => ['status' => 500, 'body' => 'Error'],
        ]);
        $this->stubWireMock([
            'request' => ['method' => 'GET', 'url' => '/api/mix-ok'],
            'response' => ['status' => 200, 'body' => 'OK'],
        ]);

        $cb = new CircuitBreaker($this->makeCache(), 5, 60);

        // Two failures
        for ($i = 0; $i < 2; $i++) {
            try {
                $cb->execute(fn () => $this->httpGet($failUrl));
            } catch (\RuntimeException) {
            }
        }
        $this->assertSame(2, $cb->getFailureCount());

        // One success — failure counter must reset
        $cb->execute(fn () => $this->httpGet($okUrl));
        $this->assertSame(0, $cb->getFailureCount());
        $this->assertSame(CircuitBreaker::STATE_CLOSED, $cb->getState());
    }
}
