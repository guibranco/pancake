<?php

use GuiBranco\Pancake\Request;
use GuiBranco\Pancake\Response;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testBinaryResponseHandling()
    {
        $request = new Request();

        // Mock a binary response
        $url = 'https://example.com/file.jpg';
        $headers = ['Content-Type' => 'image/jpeg'];
        $body = file_get_contents(__DIR__ . '/fixtures/sample.jpg');

        // Simulate a successful binary response
        $response = Response::success($body, $url, $headers, 200, true);

        $this->assertTrue($response->isBinary());
        $this->assertEquals($body, $response->getBody());
    }

    public function testNonBinaryResponseHandling()
    {
        $request = new Request();

        // Mock a text response
        $url = 'https://example.com/data.json';
        $headers = ['Content-Type' => 'application/json'];
        $body = json_encode(['key' => 'value']);

        // Simulate a successful text response
        $response = Response::success($body, $url, $headers, 200);

        $this->assertFalse($response->isBinary());
        $this->assertEquals($body, $response->getBody());
    }
}
