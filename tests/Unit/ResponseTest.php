<?php

use PHPUnit\Framework\TestCase;
use GuiBranco\Pancake\Response;
use GuiBranco\Pancake\RequestException;

class ResponseTest extends TestCase
{
    public function testErrorResponse()
    {
        $response = Response::error("Error occurred", "http://example.com", 400);

        $this->assertFalse($response->isSuccess());
        $this->assertNull($response->getBody());
        $this->assertEquals("Error occurred", $response->getMessage());
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals("http://example.com", $response->getUrl());
        $this->assertNull($response->getHeaders());
    }

    public function testSuccessResponse()
    {
        $response = new Response(true, "Response body", "Success", 200, "http://example.com", ["Content-Type" => "application/json"]);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals("Response body", $response->getBody());
        $this->assertEquals("Success", $response->getMessage());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("http://example.com", $response->getUrl());
        $this->assertEquals(["Content-Type" => "application/json"], $response->getHeaders());
    }

    public function testEnsureSuccessStatus()
    {
        $response = new Response(true, "Response body", "Success", 200, "http://example.com", null);

        $this->expectNotToPerformAssertions();
        $response->ensureSuccessStatus();
    }

    public function testEnsureSuccessStatusThrowsException()
    {
        $response = Response::error("Error occurred", "http://example.com", 400);

        $this->expectException(RequestException::class);
        $response->ensureSuccessStatus();
    }

    public function testValidateStatusCode()
    {
        $response = new Response(true, "Response body", "Success", 200, "http://example.com", null);

        $this->expectNotToPerformAssertions();
        $response->validateStatusCode();
    }

    public function testValidateStatusCodeThrowsException()
    {
        $response = Response::error("Error occurred", "http://example.com", 400);

        $this->expectException(RequestException::class);
        $response->validateStatusCode();
    }

    public function testToArray()
    {
        $response = new Response(true, "Response body", "Success", 200, "http://example.com", ["Content-Type" => "application/json"]);

        $expectedArray = [
            'success' => true,
            'statusCode' => 200,
            'body' => "Response body",
            'message' => "Success",
            'url' => "http://example.com",
            'headers' => ["Content-Type" => "application/json"],
        ];

        $this->assertEquals($expectedArray, $response->toArray());
    }

    public function testToJson()
    {
        $response = new Response(true, "Response body", "Success", 200, "http://example.com", ["Content-Type" => "application/json"]);

        $expectedJson = json_encode([
            'success' => true,
            'statusCode' => 200,
            'body' => "Response body",
            'message' => "Success",
            'url' => "http://example.com",
            'headers' => ["Content-Type" => "application/json"],
        ]);

        $this->assertJsonStringEqualsJsonString($expectedJson, $response->toJson());
    }
}
