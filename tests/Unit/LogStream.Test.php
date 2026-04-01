<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit;

use GuiBranco\Pancake\LogStream;
use GuiBranco\Pancake\LogStreamEntry;
use GuiBranco\Pancake\LogStreamResponse;
use GuiBranco\Pancake\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LogStreamTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /** Build a pancake-style response stub object. */
    private function stubResponse(int $statusCode, string $body): object
    {
        return new readonly class ($statusCode, $body) {
            public function __construct(
                public int    $statusCode,
                public string $body,
            ) {}
        };
    }

    private function stubSuccess(int $saved = 1): object
    {
        $entries = [];
        for ($i = 0; $i < $saved; $i++) {
            $entries[] = [
                'id'         => '01HZ' . str_pad((string)$i, 22, '0'),
                'trace_id'   => 'trace-' . $i,
                'batch_id'   => null,
                'app_key'    => 'test-app',
                'app_id'     => 'test',
                'user_agent' => 'LogStream-PHP-Client/1.0',
                'level'      => 'info',
                'category'   => 'general',
                'message'    => 'Test',
                'context'    => null,
                'timestamp'  => '2025-01-01T00:00:00.000Z',
                'created_at' => '2025-01-01T00:00:00.000Z',
            ];
        }

        return $this->stubResponse(201, json_encode([
            'saved'   => $saved,
            'entries' => $entries,
            'errors'  => null,
        ]));
    }

    /** @return MockObject&Request */
    private function mockRequest(object $returnValue): MockObject
    {
        $mock = $this->createMock(Request::class);
        $mock->method('post')->willReturn($returnValue);
        $mock->method('get')->willReturn($returnValue);
        return $mock;
    }

    private function makeBearerClient(?Request $request = null): LogStream
    {
        return new LogStream(
            baseUrl:   'https://logs.example.com',
            appKey:    'test-app',
            appId:     'test',
            authMode:  LogStream::AUTH_BEARER,
            apiSecret: 'my-secret',
            request:   $request,
        );
    }

    private function makeApiKeyClient(?Request $request = null): LogStream
    {
        return new LogStream(
            baseUrl:  'https://logs.example.com',
            appKey:   'test-app',
            appId:    'test',
            authMode: LogStream::AUTH_API_KEY,
            apiKey:   'test-app',
            apiToken: 'my-token',
            request:  $request,
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LogStreamEntry
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function entry_serialises_required_fields(): void
    {
        $entry = new LogStreamEntry(appId: 'prod', level: 'error', message: 'Boom');
        $data  = $entry->toArray();

        self::assertSame('prod',    $data['app_id']);
        self::assertSame('error',   $data['level']);
        self::assertSame('Boom',    $data['message']);
        self::assertSame('general', $data['category']);
    }

    #[Test]
    public function entry_omits_null_optional_fields(): void
    {
        $entry = new LogStreamEntry(appId: 'prod', level: 'info', message: 'Hi');
        $data  = $entry->toArray();

        self::assertArrayNotHasKey('app_key',    $data);
        self::assertArrayNotHasKey('trace_id',   $data);
        self::assertArrayNotHasKey('batch_id',   $data);
        self::assertArrayNotHasKey('context',    $data);
        self::assertArrayNotHasKey('timestamp',  $data);
        self::assertArrayNotHasKey('user_agent', $data);
    }

    #[Test]
    public function entry_includes_optional_fields_when_set(): void
    {
        $entry = new LogStreamEntry(
            appId:     'prod',
            level:     'warning',
            message:   'Watch out',
            category:  'health',
            context:   ['key' => 'val'],
            appKey:    'my-app',
            traceId:   'trace-uuid',
            batchId:   'batch-uuid',
            timestamp: '2025-01-01T00:00:00Z',
            userAgent: 'MyService/1.0',
        );
        $data = $entry->toArray();

        self::assertSame('my-app',            $data['app_key']);
        self::assertSame('trace-uuid',        $data['trace_id']);
        self::assertSame('batch-uuid',        $data['batch_id']);
        self::assertSame(['key' => 'val'],    $data['context']);
        self::assertSame('2025-01-01T00:00:00Z', $data['timestamp']);
        self::assertSame('MyService/1.0',     $data['user_agent']);
    }

    #[Test]
    #[DataProvider('validLevels')]
    public function entry_accepts_all_valid_levels(string $level): void
    {
        $entry = new LogStreamEntry(appId: 'x', level: $level, message: 'msg');
        self::assertSame($level, $entry->level);
    }

    public static function validLevels(): array
    {
        return array_map(fn($l) => [$l], LogStreamEntry::LEVELS);
    }

    #[Test]
    public function entry_falls_back_to_info_for_unknown_level(): void
    {
        $entry = new LogStreamEntry(appId: 'x', level: 'nonsense', message: 'msg');
        self::assertSame('info', $entry->level);
    }

    #[Test]
    public function entry_truncates_category_to_100_chars(): void
    {
        $long  = str_repeat('a', 150);
        $entry = new LogStreamEntry(appId: 'x', level: 'info', message: 'msg', category: $long);
        self::assertSame(100, strlen($entry->category));
    }

    #[Test]
    public function entry_with_batch_id_clones_with_new_batch_id(): void
    {
        $entry   = new LogStreamEntry(appId: 'prod', level: 'info', message: 'Hi');
        $stamped = $entry->withBatchId('my-batch');

        self::assertNull($entry->batchId);
        self::assertSame('my-batch', $stamped->batchId);
        self::assertSame($entry->message, $stamped->message);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LogStreamResponse
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function response_is_successful_on_2xx(): void
    {
        $r = new LogStreamResponse(201, json_encode(['saved' => 1, 'entries' => [['id' => 'x']], 'errors' => null]));
        self::assertTrue($r->success);
        self::assertSame(201, $r->statusCode);
        self::assertSame(1, $r->saved);
    }

    #[Test]
    public function response_is_not_successful_on_4xx(): void
    {
        $r = new LogStreamResponse(401, json_encode(['error' => 'Unauthorized']));
        self::assertFalse($r->success);
        self::assertSame(401, $r->statusCode);
        self::assertSame(0, $r->saved);
    }

    #[Test]
    public function response_captures_transport_error(): void
    {
        $rawStub = new readonly class {
            public int    $statusCode = -1;
            public string $body       = '';
            public string $error      = 'Could not resolve host';
        };

        $r = LogStreamResponse::fromResponse($rawStub);

        self::assertSame(-1, $r->statusCode);
        self::assertFalse($r->success);
        self::assertSame('Could not resolve host', $r->transportError);
    }

    #[Test]
    public function response_errors_field_is_null_when_not_present(): void
    {
        $r = new LogStreamResponse(201, json_encode(['saved' => 1, 'entries' => []]));
        self::assertNull($r->errors);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LogStream — constructor
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function constructor_throws_on_invalid_auth_mode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid authMode');

        new LogStream(
            baseUrl:  'https://logs.example.com',
            appKey:   'app',
            appId:    'prod',
            authMode: 'nonsense',
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LogStream — makeEntry
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function make_entry_inherits_client_defaults(): void
    {
        $client = $this->makeBearerClient();
        $client->setDefaultCategory('startup');

        $entry = $client->makeEntry('Hello');

        self::assertSame('test',    $entry->appId);
        self::assertSame('test-app', $entry->appKey);
        self::assertSame('info',    $entry->level);
        self::assertSame('startup', $entry->category);
    }

    #[Test]
    public function make_entry_allows_per_call_overrides(): void
    {
        $client = $this->makeBearerClient();
        $entry  = $client->makeEntry('Hello', 'error', 'payments', ['k' => 'v']);

        self::assertSame('error',      $entry->level);
        self::assertSame('payments',   $entry->category);
        self::assertSame(['k' => 'v'], $entry->context);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LogStream — per-level methods
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('levelMethods')]
    public function per_level_method_posts_correct_level(string $method, string $expectedLevel): void
    {
        $captured = null;
        $mock     = $this->createMock(Request::class);
        $mock->method('post')
             ->willReturnCallback(function ($url, $headers, $payload) use (&$captured) {
                 $captured = json_decode($payload, true);
                 return $this->stubSuccess();
             });

        $client = $this->makeBearerClient($mock);
        $client->$method('Test message');

        self::assertSame($expectedLevel, $captured['level']);
        self::assertSame('Test message', $captured['message']);
    }

    public static function levelMethods(): array
    {
        return [
            ['debug',    'debug'],
            ['info',     'info'],
            ['notice',   'notice'],
            ['warning',  'warning'],
            ['error',    'error'],
            ['critical', 'critical'],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LogStream — bearer auth headers
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function bearer_mode_sends_authorization_header(): void
    {
        $capturedHeaders = null;
        $mock = $this->createMock(Request::class);
        $mock->method('post')
             ->willReturnCallback(function ($url, $headers) use (&$capturedHeaders) {
                 $capturedHeaders = $headers;
                 return $this->stubSuccess();
             });

        $this->makeBearerClient($mock)->info('Hello');

        $authHeader = array_values(array_filter($capturedHeaders, fn($h) => str_starts_with($h, 'Authorization:')));
        self::assertCount(1, $authHeader);
        self::assertSame('Authorization: Bearer my-secret', $authHeader[0]);
    }

    #[Test]
    public function bearer_mode_does_not_send_x_api_key_headers(): void
    {
        $capturedHeaders = null;
        $mock = $this->createMock(Request::class);
        $mock->method('post')
             ->willReturnCallback(function ($url, $headers) use (&$capturedHeaders) {
                 $capturedHeaders = $headers;
                 return $this->stubSuccess();
             });

        $this->makeBearerClient($mock)->info('Hello');

        $apiKeyHeaders = array_filter($capturedHeaders, fn($h) => str_starts_with($h, 'X-Api-Key:'));
        self::assertCount(0, $apiKeyHeaders);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LogStream — API-key auth headers
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function api_key_mode_sends_x_api_key_and_x_api_token_headers(): void
    {
        $capturedHeaders = null;
        $mock = $this->createMock(Request::class);
        $mock->method('post')
             ->willReturnCallback(function ($url, $headers) use (&$capturedHeaders) {
                 $capturedHeaders = $headers;
                 return $this->stubSuccess();
             });

        $this->makeApiKeyClient($mock)->info('Hello');

        $keyHeader   = array_values(array_filter($capturedHeaders, fn($h) => str_starts_with($h, 'X-Api-Key:')));
        $tokenHeader = array_values(array_filter($capturedHeaders, fn($h) => str_starts_with($h, 'X-Api-Token:')));

        self::assertCount(1, $keyHeader);
        self::assertCount(1, $tokenHeader);
        self::assertSame('X-Api-Key: test-app',  $keyHeader[0]);
        self::assertSame('X-Api-Token: my-token', $tokenHeader[0]);
    }

    #[Test]
    public function api_key_mode_does_not_send_authorization_header(): void
    {
        $capturedHeaders = null;
        $mock = $this->createMock(Request::class);
        $mock->method('post')
             ->willReturnCallback(function ($url, $headers) use (&$capturedHeaders) {
                 $capturedHeaders = $headers;
                 return $this->stubSuccess();
             });

        $this->makeApiKeyClient($mock)->info('Hello');

        $authHeaders = array_filter($capturedHeaders, fn($h) => str_starts_with($h, 'Authorization:'));
        self::assertCount(0, $authHeaders);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LogStream — batch
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function batch_throws_on_empty_entries(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->makeBearerClient()->batch([]);
    }

    #[Test]
    public function batch_stamps_batch_id_on_entries_that_lack_one(): void
    {
        $capturedPayload = null;
        $mock = $this->createMock(Request::class);
        $mock->method('post')
             ->willReturnCallback(function ($url, $headers, $payload) use (&$capturedPayload) {
                 $capturedPayload = json_decode($payload, true);
                 return $this->stubSuccess(3);
             });

        $client  = $this->makeBearerClient($mock);
        $entries = [
            $client->makeEntry('Entry 1', 'info'),
            $client->makeEntry('Entry 2', 'warning'),
            $client->makeEntry('Entry 3', 'error'),
        ];

        $client->batch($entries);

        self::assertNotNull($capturedPayload['batch_id']);
        foreach ($capturedPayload['logs'] as $log) {
            self::assertSame($capturedPayload['batch_id'], $log['batch_id']);
        }
    }

    #[Test]
    public function batch_uses_provided_batch_id(): void
    {
        $capturedPayload = null;
        $mock = $this->createMock(Request::class);
        $mock->method('post')
             ->willReturnCallback(function ($url, $headers, $payload) use (&$capturedPayload) {
                 $capturedPayload = json_decode($payload, true);
                 return $this->stubSuccess(1);
             });

        $client = $this->makeBearerClient($mock);
        $client->batch([$client->makeEntry('Hi')], 'explicit-batch-id');

        self::assertSame('explicit-batch-id', $capturedPayload['batch_id']);
    }

    #[Test]
    public function batch_preserves_existing_batch_id_on_entries(): void
    {
        $capturedPayload = null;
        $mock = $this->createMock(Request::class);
        $mock->method('post')
             ->willReturnCallback(function ($url, $headers, $payload) use (&$capturedPayload) {
                 $capturedPayload = json_decode($payload, true);
                 return $this->stubSuccess(1);
             });

        $client = $this->makeBearerClient($mock);
        $entry  = new LogStreamEntry(
            appId:   'test',
            level:   'info',
            message: 'Already has a batch',
            batchId: 'already-set',
        );

        $client->batch([$entry], 'new-batch-id');

        self::assertSame('already-set', $capturedPayload['logs'][0]['batch_id']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LogStream — health
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function health_returns_parsed_array_on_200(): void
    {
        $mock = $this->createMock(Request::class);
        $mock->method('get')->willReturn($this->stubResponse(200, json_encode([
            'status'         => 'ok',
            'time'           => '2025-01-01T00:00:00+00:00',
            'ws_connections' => 3,
        ])));

        $result = $this->makeBearerClient($mock)->health();

        self::assertIsArray($result);
        self::assertSame('ok', $result['status']);
        self::assertSame(3,    $result['ws_connections']);
    }

    #[Test]
    public function health_returns_null_on_non_200(): void
    {
        $mock = $this->createMock(Request::class);
        $mock->method('get')->willReturn($this->stubResponse(503, ''));

        $result = $this->makeBearerClient($mock)->health();

        self::assertNull($result);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LogStream — payload shape
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function log_includes_app_key_and_app_id_in_payload(): void
    {
        $capturedPayload = null;
        $mock = $this->createMock(Request::class);
        $mock->method('post')
             ->willReturnCallback(function ($url, $headers, $payload) use (&$capturedPayload) {
                 $capturedPayload = json_decode($payload, true);
                 return $this->stubSuccess();
             });

        $this->makeBearerClient($mock)->info('Hello');

        self::assertSame('test-app', $capturedPayload['app_key']);
        self::assertSame('test',     $capturedPayload['app_id']);
    }

    #[Test]
    public function log_includes_context_when_provided(): void
    {
        $capturedPayload = null;
        $mock = $this->createMock(Request::class);
        $mock->method('post')
             ->willReturnCallback(function ($url, $headers, $payload) use (&$capturedPayload) {
                 $capturedPayload = json_decode($payload, true);
                 return $this->stubSuccess();
             });

        $this->makeBearerClient($mock)->error(
            'Charge failed',
            ['invoice_id' => 42, 'code' => 'card_declined'],
            'payments',
        );

        self::assertSame(42,             $capturedPayload['context']['invoice_id']);
        self::assertSame('card_declined', $capturedPayload['context']['code']);
        self::assertSame('payments',      $capturedPayload['category']);
    }

    #[Test]
    public function set_default_category_applies_to_all_subsequent_entries(): void
    {
        $capturedPayload = null;
        $mock = $this->createMock(Request::class);
        $mock->method('post')
             ->willReturnCallback(function ($url, $headers, $payload) use (&$capturedPayload) {
                 $capturedPayload = json_decode($payload, true);
                 return $this->stubSuccess();
             });

        $client = $this->makeBearerClient($mock);
        $client->setDefaultCategory('my-system');
        $client->info('Hello');

        self::assertSame('my-system', $capturedPayload['category']);
    }
}
