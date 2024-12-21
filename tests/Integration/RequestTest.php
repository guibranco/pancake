<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Integration;

use GuiBranco\Pancake\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testCanGet(): void
    {
        $request = new Request();
        $response = $request->get('https://httpbin.org/get');
        $this->assertEquals(200, $response->getgetStatusCode()());
    }

    public function testCanPost(): void
    {
        $request = new Request();
        $response = $request->post('https://httpbin.org/post');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanPostWithPayload(): void
    {
        $request = new Request();
        $response = $request->post('https://httpbin.org/post', array(), ['name' => 'GuiBranco']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanPut(): void
    {
        $request = new Request();
        $response = $request->put('https://httpbin.org/put');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanPutWithPayload(): void
    {
        $request = new Request();
        $response = $request->put('https://httpbin.org/put', array(), ['name' => 'GuiBranco']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanPatch(): void
    {
        $request = new Request();
        $response = $request->patch('https://httpbin.org/patch');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanPatchWithPayload(): void
    {
        $request = new Request();
        $response = $request->patch('https://httpbin.org/patch', array(), ['name' => 'GuiBranco']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanDelete(): void
    {
        $request = new Request();
        $response = $request->delete('https://httpbin.org/delete');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanDeleteWithPayload(): void
    {
        $request = new Request();
        $response = $request->delete('https://httpbin.org/delete', array(), ['name' => 'GuiBranco']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanOptions(): void
    {
        $request = new Request();
        $response = $request->options('https://httpbin.org/get');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanHead(): void
    {
        $request = new Request();
        $response = $request->head('https://httpbin.org/get', ['Host: httpbin.org']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanGetWithHeaders(): void
    {
        $request = new Request();
        $response = $request->get('https://httpbin.org/headers', ['X-Test: test']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanPostWithHeaders(): void
    {
        $request = new Request();
        $response = $request->post('https://httpbin.org/post', ['X-Test: test'], json_encode(['name' => 'GuiBranco']));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanPutWithHeaders(): void
    {
        $request = new Request();
        $response = $request->put('https://httpbin.org/put', ['X-Test: test'], json_encode(['name' => 'GuiBranco']));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanPatchWithHeaders(): void
    {
        $request = new Request();
        $response = $request->patch('https://httpbin.org/patch', ['X-Test: test'], json_encode(['name' => 'GuiBranco']));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanDeleteWithHeaders(): void
    {
        $request = new Request();
        $response = $request->delete('https://httpbin.org/delete', ['X-Test: test'], json_encode(['name' => 'GuiBranco']));
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
        $request = new Request();
        $request->addRequest('test', 'https://httpbin.org/get');
        $responses = $request->executeBatch();
        $this->assertArrayHasKey('test', $responses);
        $this->assertEquals(200, $responses['test']->getStatusCode());
    }

    public function testCanAddMultipleRequests(): void
    {
        $request = new Request();
        $request->addRequest('test1', 'https://httpbin.org/get');
        $request->addRequest('test2', 'https://httpbin.org/post');
        $responses = $request->executeBatch();
        $this->assertArrayHasKey('test1', $responses);
        $this->assertArrayHasKey('test2', $responses);
        $this->assertEquals(200, $responses['test1']->getStatusCode());
        $this->assertEquals(200, $responses['test2']->getStatusCode());
    }

    public function testCanAddRequestWithHeaders(): void
    {
        $request = new Request();
        $request->addRequest('test', 'https://httpbin.org/get', ['X-Test: test']);
        $responses = $request->executeBatch();
        $this->assertArrayHasKey('test', $responses);
        $this->assertEquals(200, $responses['test']->getStatusCode());
    }

    public function testCanAddRequestWithPayload(): void
    {
        $request = new Request();
        $request->addRequest('test', 'https://httpbin.org/post', [], ['name' => 'GuiBranco']);
        $responses = $request->executeBatch();
        $this->assertArrayHasKey('test', $responses);
        $this->assertEquals(200, $responses['test']->getStatusCode());
    }
}