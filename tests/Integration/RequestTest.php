<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Integration;

use GuiBranco\Pancake\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    private const WIREMOCK_URL = 'http://localhost:8080';
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new Request(self::WIREMOCK_URL);
    }

    public function testCanGet(): void
    {
        $response = $this->request->get('/get');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanPost(): void
    {
        $response = $this->request->post('/post');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanPostWithPayload(): void
    {
        $response = $this->request->post('/post', array(), ['name' => 'GuiBranco']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanPut(): void
    {
        $response = $this->request->put('/put');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanPutWithPayload(): void
    {
        $response = $this->request->put('/put', array(), ['name' => 'GuiBranco']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanPatch(): void
    {
        $response = $this->request->patch('/patch');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanPatchWithPayload(): void
    {
        $response = $this->request->patch('/patch', array(), ['name' => 'GuiBranco']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanDelete(): void
    {
        $response = $this->request->delete('/delete');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanDeleteWithPayload(): void
    {
        $response = $this->request->delete('/delete', array(), ['name' => 'GuiBranco']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanOptions(): void
    {
        $response = $this->request->options('/get');
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testCanHead(): void
    {
        $response = $this->request->head('/head', ['Host: wiremock:8080']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanGetWithHeaders(): void
    {
        $response = $this->request->get('/headers', ['X-Test: test']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanPostWithHeaders(): void
    {
        $response = $this->request->post('/post', ['X-Test: test'], json_encode(['name' => 'GuiBranco']));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanPutWithHeaders(): void
    {
        $response = $this->request->put('/put', ['X-Test: test'], json_encode(['name' => 'GuiBranco']));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanPatchWithHeaders(): void
    {
        $response = $this->request->patch('/patch', ['X-Test: test'], json_encode(['name' => 'GuiBranco']));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanDeleteWithHeaders(): void
    {
        $response = $this->request->delete('/delete', ['X-Test: test'], json_encode(['name' => 'GuiBranco']));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCannotGet(): void
    {
        $request = new Request();
        $response = $request->get('https://non-existing-url');
        $this->assertEquals(-1, $response->getStatusCode());
    }

    public function testCanAddRequest(): void
    {
        $this->request->addRequest('test', '/get');
        $responses = $this->request->executeBatch();
        $this->assertArrayHasKey('test', $responses);
        $this->assertEquals(200, $responses['test']->getStatusCode());
    }

    public function testCanAddMultipleRequests(): void
    {
        $this->request->addRequest('test1', '/get');
        $this->request->addRequest('test2', '/post', [], 'POST', ['name' => 'GuiBranco']);
        $responses = $this->request->executeBatch();
        $this->assertArrayHasKey('test1', $responses);
        $this->assertArrayHasKey('test2', $responses);
        $this->assertEquals(200, $responses['test1']->getStatusCode());
        $this->assertEquals(200, $responses['test2']->getStatusCode());
    }

    public function testCanAddRequestWithHeaders(): void
    {
        $this->request->addRequest('test', '/get', ['X-Test: test']);
        $responses = $this->request->executeBatch();
        $this->assertArrayHasKey('test', $responses);
        $this->assertEquals(200, $responses['test']->getStatusCode());
    }

    public function testCanAddRequestWithPayload(): void
    {
        $this->request->addRequest('test', '/post', [], 'POST', ['name' => 'GuiBranco']);
        $responses = $this->request->executeBatch();
        $this->assertArrayHasKey('test', $responses);
        $this->assertEquals(200, $responses['test']->getStatusCode());
    }

    public function testCanSetSSLVerification(): void
    {
        $this->request->setSSLVerification(false);
        $response = $this->request->get('/get');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanSetBaseUrl(): void
    {
        $request = new Request();
        $request->setBaseUrl(self::WIREMOCK_URL);
        $response = $request->get('/get');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanGetBaseUrl(): void
    {
        $this->assertEquals(self::WIREMOCK_URL, $this->request->getBaseUrl());
    }
}
