<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Integration;

use GuiBranco\Pancake\LogStream;
use GuiBranco\Pancake\LogStreamEntry;
use GuiBranco\Pancake\Request;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for LogStream against a WireMock server.
 *
 * Prerequisites:
 *   1. WireMock must be running on http://localhost:8080
 *      docker run --rm -d -p 8080:8080 \
 *          -v $PWD/tests/wiremock/mappings:/home/wiremock/mappings \
 *          wiremock/wiremock:latest
 *
 *   2. The mappings at tests/wiremock/mappings/logstream.json must be loaded.
 *
 * Run only these tests with:
 *   vendor/bin/phpunit --group integration
 */
#[Group('integration')]
final class LogStreamTest extends TestCase
{
    private const BASE_URL = 'http://localhost:8080';

    private function bearerClient(): LogStream
    {
        return new LogStream(
            baseUrl:   self::BASE_URL,
            appKey:    'test-app',
            appId:     'integration-test',
            authMode:  LogStream::AUTH_BEARER,
            apiSecret: 'test-secret',
        );
    }

    private function apiKeyClient(): LogStream
    {
        return new LogStream(
            baseUrl:  self::BASE_URL,
            appKey:   'test-app',
            appId:    'integration-test',
            authMode: LogStream::AUTH_API_KEY,
            apiKey:   'test-app',
            apiToken: 'test-token',
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Health
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function health_check_returns_ok_status(): void
    {
        $result = $this->bearerClient()->health();

        self::assertIsArray($result);
        self::assertSame('ok', $result['status']);
        self::assertArrayHasKey('time',           $result);
        self::assertArrayHasKey('ws_connections', $result);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Bearer auth
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function bearer_info_returns_201_and_saved_count(): void
    {
        $response = $this->bearerClient()->info('Integration test entry');

        self::assertTrue($response->success);
        self::assertSame(201, $response->statusCode);
        self::assertSame(1,   $response->saved);
        self::assertCount(1,  $response->entries);
        self::assertNull($response->errors);
    }

    #[Test]
    public function bearer_debug_is_accepted(): void
    {
        $response = $this->bearerClient()->debug('Debug entry');
        self::assertTrue($response->success);
    }

    #[Test]
    public function bearer_notice_is_accepted(): void
    {
        $response = $this->bearerClient()->notice('Notice entry');
        self::assertTrue($response->success);
    }

    #[Test]
    public function bearer_warning_is_accepted(): void
    {
        $response = $this->bearerClient()->warning('Warning entry');
        self::assertTrue($response->success);
    }

    #[Test]
    public function bearer_error_is_accepted(): void
    {
        $response = $this->bearerClient()->error('Error entry');
        self::assertTrue($response->success);
    }

    #[Test]
    public function bearer_critical_is_accepted(): void
    {
        $response = $this->bearerClient()->critical('Critical entry');
        self::assertTrue($response->success);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // API-key auth
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function api_key_error_with_context_is_accepted(): void
    {
        $response = $this->apiKeyClient()->error(
            'Charge failed',
            ['invoice_id' => 42],
            'payments',
        );

        self::assertTrue($response->success);
        self::assertSame(201, $response->statusCode);
        self::assertSame(1,   $response->saved);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Batch
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function batch_of_three_entries_returns_saved_three(): void
    {
        $client  = $this->bearerClient();
        $entries = [
            $client->makeEntry('Entry 1', 'info',    'ci'),
            $client->makeEntry('Entry 2', 'warning', 'ci'),
            $client->makeEntry('Entry 3', 'error',   'ci'),
        ];

        $response = $client->batch($entries, 'batch-001');

        self::assertTrue($response->success);
        self::assertSame(201, $response->statusCode);
        self::assertSame(3,   $response->saved);
        self::assertCount(3,  $response->entries);
    }

    #[Test]
    public function batch_entries_all_share_the_same_batch_id(): void
    {
        $client  = $this->bearerClient();
        $entries = [
            $client->makeEntry('A'),
            $client->makeEntry('B'),
            $client->makeEntry('C'),
        ];

        $response = $client->batch($entries, 'batch-001');

        self::assertTrue($response->success);
        $batchIds = array_unique(array_column($response->entries, 'batch_id'));
        self::assertCount(1, $batchIds);
        self::assertSame('batch-001', $batchIds[0]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Auth failures
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function missing_credentials_returns_401(): void
    {
        // Create a client that sends no auth headers by injecting a custom request
        // that strips the Authorization header — simulates a misconfigured client.
        $bareRequest = new Request(self::BASE_URL);

        // Post directly using the pancake Request to bypass LogStream's header logic
        $response = $bareRequest->post('/api/logs', [
            'Content-Type: application/json',
        ], json_encode([
            'app_key' => 'test-app',
            'app_id'  => 'integration-test',
            'level'   => 'info',
            'message' => 'No auth',
        ]));

        self::assertSame(401, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        self::assertSame('Unauthorized', $body['error']);
    }

    #[Test]
    public function wrong_bearer_token_returns_401(): void
    {
        $client = new LogStream(
            baseUrl:   self::BASE_URL,
            appKey:    'test-app',
            appId:     'integration-test',
            authMode:  LogStream::AUTH_BEARER,
            apiSecret: 'wrong-secret',
        );

        $response = $client->info('Hello');

        self::assertFalse($response->success);
        self::assertSame(401, $response->statusCode);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LogStreamEntry round-trip
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function entry_with_all_fields_is_accepted_by_bearer_mode(): void
    {
        $entry = new LogStreamEntry(
            appId:     'integration-test',
            level:     'warning',
            message:   'Full entry test',
            category:  'test-category',
            context:   ['key' => 'value'],
            appKey:    'test-app',
            traceId:   'trace-' . bin2hex(random_bytes(8)),
            timestamp: (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339_EXTENDED),
        );

        $response = $this->bearerClient()->log($entry);

        self::assertTrue($response->success);
    }
}
