<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit;

use GuiBranco\Pancake\LogStream;
use GuiBranco\Pancake\LogStreamEntry;
use GuiBranco\Pancake\LogStreamResponse;
use GuiBranco\Pancake\Request;
use GuiBranco\Pancake\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LogStreamTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /** Build a pancake Response stub for use in mocks. */
    private function stubResponse(int $statusCode, string $body): Response
    {
        // Use Response::success() for all status codes — it accepts any HTTP code.
        // This satisfies PHPUnit's mock return-type enforcement.
        return Response::success($body, 'http://mock', [], $statusCode);
    }

    private function stubSuccess(int $saved = 1): Response
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

        self::assertSame('prod', $data['app_id']);
        self::assertSame('error', $data['level']);
        self::assertSame('Boom', $data['message']);
        self::assertSame('general', $data['category']);
    }

    #[Test]
    public function entry_omits_null_optional_fields(): void
    {
        $entry = new LogStreamEntry(appId: 'prod', level: 'info', message: 'Hi');
        $data  = $entry->toArray();

        self::assertArrayNotHasKey('app_key', $data);
        self::assertArrayNotHasKey('trace_id', $data);
        self::assertArrayNotHasKey('batch_id', $data);
        self::assertArrayNotHasKey('context', $data);
        self::assertArrayNotHasKey('timestamp', $data);
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

        self::assertSame('my-app', $data['app_key']);
        self::assertSame('trace-uuid', $data['trace_id']);
        self::assertSame('batch-uuid', $data['batch_id']);
        self::assertSame(['key' => 'val'], $data['context']);
        self::assertSame('2025-01-01T00:00:00Z', $data['timestamp']);
        self::assertSame('MyService/1.0', $data['user_agent']);
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
        return array_map(fn ($l) => [$l], LogStreamEntry::LEVELS);
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
        // Response::error() sets statusCode = -1 and message = the error string,
        // which maps to what LogStreamResponse::fromResponse() reads via getMessage().
        $rawStub = Response::error('Could not resolve host', 'http://mock', -1);

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

        self::assertSame('test', $entry->appId);
        self::assertSame('test-app', $entry->appKey);
        self::assertSame('info', $entry->level);
        self::assertSame('startup', $entry->category);
    }

    #[Test]
    public function make_entry_allows_per_call_overrides(): void
    {
        $client = $this->makeBearerClient();
        $entry  = $client->makeEntry('Hello', 'error', 'payments', ['k' => 'v']);

        self::assertSame('error', $entry->level);
        self::assertSame('payments', $entry->category);
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

        $authHeader = array_values(array_filter($capturedHeaders, fn ($h) => str_starts_with($h, 'Authorization:')));
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

        $apiKeyHeaders = array_filter($capturedHeaders, fn ($h) => str_starts_with($h, 'X-Api-Key:'));
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

        $keyHeader   = array_values(array_filter($capturedHeaders, fn ($h) => str_starts_with($h, 'X-Api-Key:')));
        $tokenHeader = array_values(array_filter($capturedHeaders, fn ($h) => str_starts_with($h, 'X-Api-Token:')));

        self::assertCount(1, $keyHeader);
        self::assertCount(1, $tokenHeader);
        self::assertSame('X-Api-Key: test-app', $keyHeader[0]);
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

        $authHeaders = array_filter($capturedHeaders, fn ($h) => str_starts_with($h, 'Authorization:'));
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
        self::assertSame(3, $result['ws_connections']);
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
        self::assertSame('test', $capturedPayload['app_id']);
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

        self::assertSame(42, $capturedPayload['context']['invoice_id']);
        self::assertSame('card_declined', $capturedPayload['context']['code']);
        self::assertSame('payments', $capturedPayload['category']);
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

    // ──────────────────────────────────────────────────────────────────────────
    // UDP transport
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @param string[] $sent Receives every datagram
     */
    private function makeUdpClient(
        array &$sent,
        string $authMode = LogStream::AUTH_API_KEY,
        ?Request $request = null,
        int $maxPacketSize = LogStream::DEFAULT_UDP_MAX_PACKET_SIZE,
        bool $fallbackToHttp = true,
        ?string $sendError = null,
    ): LogStream {
        return new LogStream(
            baseUrl:           'https://logs.example.com',
            appKey:            'test-app',
            appId:             'test',
            authMode:          $authMode,
            apiSecret:         'my-secret',
            apiKey:            'client-key',
            apiToken:          'my-token',
            request:           $request ?? $this->createMock(Request::class),
            userAgent:         'UdpTest/1.0',
            transport:         LogStream::TRANSPORT_UDP,
            udpMaxPacketSize:  $maxPacketSize,
            udpFallbackToHttp: $fallbackToHttp,
            udpSender:         function (string $packet) use (&$sent, $sendError): ?string {
                $sent[] = $packet;
                return $sendError;
            },
        );
    }

    /** Recompute the signature the way the server does. */
    private static function assertValidSignature(string $packet, string $secret): void
    {
        $data = json_decode($packet, true);
        // The raw log text is everything after "log": up to the closing brace.
        $logJson = substr($packet, strpos($packet, '"log":') + 6, -1);

        self::assertSame(
            hash_hmac('sha256', "{$data['key']}:{$data['ts']}:{$data['nonce']}:{$logJson}", $secret),
            $data['sig'],
        );
    }

    #[Test]
    public function constructor_throws_on_invalid_transport(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new LogStream(baseUrl: 'https://logs.example.com', appKey: 'a', appId: 'b', apiSecret: 's', transport: 'tcp');
    }

    #[Test]
    public function constructor_throws_when_udp_has_no_signing_secret(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('apiKey and apiToken');

        new LogStream(
            baseUrl:   'https://logs.example.com',
            appKey:    'a',
            appId:     'b',
            authMode:  LogStream::AUTH_API_KEY,
            apiKey:    'a',
            transport: LogStream::TRANSPORT_UDP,
        );
    }

    #[Test]
    public function constructor_throws_on_invalid_udp_port(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new LogStream(
            baseUrl:   'https://logs.example.com',
            appKey:    'a',
            appId:     'b',
            apiSecret: 's',
            transport: LogStream::TRANSPORT_UDP,
            udpPort:   70000,
        );
    }

    #[Test]
    public function http_is_the_default_transport(): void
    {
        self::assertSame(LogStream::TRANSPORT_HTTP, $this->makeBearerClient()->getTransport());
    }

    #[Test]
    public function udp_log_sends_one_signed_packet_and_no_http_request(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::never())->method('post');
        $sent = [];

        $response = $this->makeUdpClient($sent, request: $request)
            ->error('Charge failed', ['invoice_id' => 42, 'note' => 'café / ünïcode'], 'payments', 'trace-1');

        self::assertCount(1, $sent);
        self::assertTrue($response->success);
        self::assertSame(202, $response->statusCode);
        self::assertSame(1, $response->sent);
        self::assertSame(0, $response->saved);
        self::assertNull($response->errors);

        $packet = json_decode($sent[0], true);
        self::assertSame(1, $packet['v']);
        self::assertSame('client-key', $packet['key']);
        self::assertIsInt($packet['ts']);
        self::assertEqualsWithDelta(microtime(true) * 1000, $packet['ts'], 5000);
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $packet['nonce']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $packet['sig']);

        self::assertSame('test', $packet['log']['app_id']);
        self::assertSame('error', $packet['log']['level']);
        self::assertSame('payments', $packet['log']['category']);
        self::assertSame('Charge failed', $packet['log']['message']);
        self::assertSame('trace-1', $packet['log']['trace_id']);
        self::assertSame(42, $packet['log']['context']['invoice_id']);
        self::assertSame('UdpTest/1.0', $packet['log']['user_agent']);
        self::assertArrayNotHasKey('app_key', $packet['log']);

        self::assertValidSignature($sent[0], 'my-token');
    }

    #[Test]
    public function udp_bearer_mode_signs_with_app_key_and_api_secret(): void
    {
        $sent = [];
        $this->makeUdpClient($sent, LogStream::AUTH_BEARER)->info('Hello');

        self::assertSame('test-app', json_decode($sent[0], true)['key']);
        self::assertValidSignature($sent[0], 'my-secret');
    }

    #[Test]
    public function udp_packets_use_unique_nonces(): void
    {
        $sent   = [];
        $client = $this->makeUdpClient($sent);
        $client->info('One');
        $client->info('Two');

        self::assertNotSame(json_decode($sent[0], true)['nonce'], json_decode($sent[1], true)['nonce']);
    }

    #[Test]
    public function udp_batch_sends_one_packet_per_entry_with_the_batch_id(): void
    {
        $sent   = [];
        $client = $this->makeUdpClient($sent);

        $response = $client->batch([
            $client->makeEntry('First'),
            $client->makeEntry('Second', 'warning'),
            $client->makeEntry('Third', 'error'),
        ], 'batch-1');

        self::assertCount(3, $sent);
        self::assertSame(3, $response->sent);
        foreach ($sent as $i => $raw) {
            self::assertSame('batch-1', json_decode($raw, true)['log']['batch_id']);
            self::assertValidSignature($raw, 'my-token');
        }
        self::assertSame('Second', json_decode($sent[1], true)['log']['message']);
    }

    #[Test]
    public function udp_build_packet_is_deterministic_and_matches_the_documented_format(): void
    {
        $packet = LogStream::buildUdpPacket(
            'billing-api',
            'secret',
            ['app_id' => 'production', 'message' => 'Charge processed'],
            1748000000000,
            '9f2c4e1ab37d5c60',
        );

        $log = '{"app_id":"production","message":"Charge processed"}';
        $sig = hash_hmac('sha256', "billing-api:1748000000000:9f2c4e1ab37d5c60:{$log}", 'secret');

        self::assertSame(
            '{"v":1,"key":"billing-api","ts":1748000000000,"nonce":"9f2c4e1ab37d5c60","sig":"' . $sig . '","log":' . $log . '}',
            $packet,
        );
    }

    #[Test]
    public function udp_oversized_entry_falls_back_to_http(): void
    {
        $capturedPayload = null;
        $request         = $this->createMock(Request::class);
        $request->expects(self::once())->method('post')
            ->willReturnCallback(function ($url, $headers, $payload) use (&$capturedPayload) {
                $capturedPayload = json_decode($payload, true);
                return $this->stubSuccess();
            });
        $sent = [];

        $response = $this->makeUdpClient($sent, request: $request, maxPacketSize: 512)
            ->info(str_repeat('x', 1000));

        self::assertSame([], $sent);
        self::assertSame(201, $response->statusCode);
        self::assertSame(1, $response->saved);
        self::assertSame(str_repeat('x', 1000), $capturedPayload['message']);
    }

    #[Test]
    public function udp_batch_sends_small_entries_by_udp_and_oversized_ones_by_http(): void
    {
        $capturedPayload = null;
        $request         = $this->createMock(Request::class);
        $request->expects(self::once())->method('post')
            ->willReturnCallback(function ($url, $headers, $payload) use (&$capturedPayload) {
                $capturedPayload = json_decode($payload, true);
                return $this->stubSuccess();
            });
        $sent   = [];
        $client = $this->makeUdpClient($sent, request: $request, maxPacketSize: 512);

        $response = $client->batch([
            $client->makeEntry('small'),
            $client->makeEntry(str_repeat('x', 1000)),
        ], 'batch-2');

        self::assertCount(1, $sent);
        self::assertSame(202, $response->statusCode);
        self::assertSame(1, $response->sent);
        self::assertSame(1, $response->saved);
        self::assertSame('batch-2', $capturedPayload['batch_id']);
        self::assertCount(1, $capturedPayload['logs']);
    }

    #[Test]
    public function udp_oversized_entry_is_an_error_without_fallback(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::never())->method('post');
        $sent = [];

        $response = $this->makeUdpClient($sent, request: $request, maxPacketSize: 512, fallbackToHttp: false)
            ->info(str_repeat('x', 1000));

        self::assertSame([], $sent);
        self::assertFalse($response->success);
        self::assertSame(-1, $response->statusCode);
        self::assertStringContainsString('over the 512-byte UDP limit', (string) $response->transportError);
        self::assertCount(1, $response->errors);
    }

    #[Test]
    public function udp_send_failure_is_reported(): void
    {
        $sent = [];

        $response = $this->makeUdpClient($sent, sendError: 'network down')->info('Hello');

        self::assertFalse($response->success);
        self::assertSame(-1, $response->statusCode);
        self::assertSame(0, $response->sent);
        self::assertSame('Entry #0: network down', $response->transportError);
    }

    #[Test]
    public function udp_default_sender_delivers_a_datagram_to_the_host_of_the_base_url(): void
    {
        $server = stream_socket_server('udp://127.0.0.1:0', $errno, $errstr, STREAM_SERVER_BIND);
        self::assertNotFalse($server, $errstr);
        $port = (int) substr((string) strrchr(stream_socket_get_name($server, false), ':'), 1);

        $client = new LogStream(
            baseUrl:   'http://127.0.0.1',
            appKey:    'test-app',
            appId:     'test',
            apiSecret: 'my-secret',
            request:   $this->createMock(Request::class),
            transport: LogStream::TRANSPORT_UDP,
            udpPort:   $port,
        );

        $response = $client->info('Over the wire');
        stream_set_timeout($server, 2);
        $packet = stream_socket_recvfrom($server, 65535);
        fclose($server);

        self::assertTrue($response->success);
        self::assertSame('Over the wire', json_decode($packet, true)['log']['message']);
        self::assertValidSignature($packet, 'my-secret');
    }
}
