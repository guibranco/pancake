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
        $response = Response::success("Response body", "http://example.com", ["Content-Type" => "application/json"], 200);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals("Response body", $response->getBody());
        $this->assertEquals("", $response->getMessage());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("http://example.com", $response->getUrl());
        $this->assertEquals(["Content-Type" => "application/json"], $response->getHeaders());
    }

    public function testConstructor()
    {
        $response = new Response(true, "Response body", "", 200, "http://example.com", ["Content-Type" => "application/json"]);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals("Response body", $response->getBody());
        $this->assertEquals("", $response->getMessage());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("http://example.com", $response->getUrl());
        $this->assertEquals(["Content-Type" => "application/json"], $response->getHeaders());
    }

    public function testEnsureSuccessStatus()
    {
        $response = Response::success("Response body", "http://example.com", [], 200);

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
        $response = Response::success("Response body", "http://example.com", [], 200);

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
        $response = Response::success("Response body", "http://example.com", ["Content-Type" => "application/json"], 200);

        $expectedArray = [
            'success' => true,
            'statusCode' => 200,
            'body' => "Response body",
            'message' => "",
            'url' => "http://example.com",
            'headers' => ["Content-Type" => "application/json"],
        ];

        $this->assertEquals($expectedArray, $response->toArray());
    }

    public function testToJson()
    {
        $response = Response::success("Response body", "http://example.com", ["Content-Type" => "application/json"], 200);

        $expectedJson = json_encode([
            'success' => true,
            'statusCode' => 200,
            'body' => "Response body",
            'message' => "",
            'url' => "http://example.com",
            'headers' => ["Content-Type" => "application/json"],
        ]);

        $this->assertJsonStringEqualsJsonString($expectedJson, $response->toJson());
    }

    public function testGetBodyAsJson()
    {
        $response = Response::success(json_encode(['key' => 'value']), "http://example.com", ["Content-Type" => "application/json"], 200);

        $this->assertEquals((object) ['key' => 'value'], $response->getBodyAsJson());
    }

    public function testGetBodyAsJsonReturnsNull()
    {
        $response = Response::error("Error occurred", "http://example.com", 400);

        $this->assertEquals(null, $response->getBodyAsJson());
    }

    public function testGetBodyAsJsonThrowsException()
    {
        $response = Response::success("Invalid JSON", "http://example.com", ["Content-Type" => "application/json"], 200);

        $this->expectException(JsonException::class);
        $response->getBodyAsJson();
    }

    public function testGetBodyAsJsonWithEmptyObject()
    {
        $response = Response::success('{}', "http://example.com", ["Content-Type" => "application/json"], 200);

        $this->assertEquals(new \stdClass(), $response->getBodyAsJson());
    }

    public function testGetBodyAsArray()
    {
        $response = Response::success(json_encode(['key' => 'value']), "http://example.com", ["Content-Type" => "application/json"], 200);

        $this->assertEquals(['key' => 'value'], $response->getBodyAsArray());
    }

    public function testGetBodyAsArrayReturnsNull()
    {
        $response = Response::error("Error occurred", "http://example.com", 400);

        $this->assertEquals(null, $response->getBodyAsArray());
    }

    public function testGetBodyAsArrayThrowsException()
    {
        $response = Response::success("Invalid JSON", "http://example.com", ["Content-Type" => "application/json"], 200);

        $this->expectException(JsonException::class);
        $response->getBodyAsArray();
    }
}
