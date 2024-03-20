<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests;

use GuiBranco\Pancake\Request;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

final class RequestTest extends TestCase
{
    public function testCanGetExampleUrl(): void
    {
        $request = new Request();
        $response = $request->get('https://httpbin.org/get');
        $this->assertEquals(200, $response->statusCode);
    }

    public function testCanPostExampleUrl(): void
    {
        $request = new Request();
        $response = $request->post('https://httpbin.org/post', ['name' => 'GuiBranco']);
        $this->assertEquals(200, $response->statusCode);
    }

    public function testCanPutExampleUrl(): void
    {
        $request = new Request();
        $response = $request->put('https://httpbin.org/put', ['name' => 'GuiBranco']);
        $this->assertEquals(200, $response->statusCode);
    }

    public function testCanPatchExampleUrl(): void
    {
        $request = new Request();
        $response = $request->patch('https://httpbin.org/patch', ['name' => 'GuiBranco']);
        $this->assertEquals(200, $response->statusCode);
    }

    public function testCanDeleteExampleUrl(): void
    {
        $request = new Request();
        $response = $request->delete('https://httpbin.org/delete');
        $this->assertEquals(200, $response->statusCode);
    }

    public function testCanGetWithHeaders(): void
    {
        $request = new Request();
        $response = $request->get('https://httpbin.org/headers', ['X-Test' => 'test']);
        $this->assertEquals(200, $response->statusCode);
    }

    public function testCanPostWithHeaders(): void
    {
        $request = new Request();
        $response = $request->post('https://httpbin.org/post', ['name' => 'GuiBranco'], ['X-Test' => 'test']);
        $this->assertEquals(200, $response->statusCode);
    }
}