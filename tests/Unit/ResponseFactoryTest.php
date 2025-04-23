<?php

use PHPUnit\Framework\TestCase;
use GuiBranco\Pancake\Response;
use GuiBranco\Pancake\ResponseFactory;

class ResponseFactoryTest extends TestCase
{
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        $this->responseFactory = new ResponseFactory();
    }

    public function testSuccessResponse()
    {
        $response = $this->responseFactory->success("Response body", "http://example.com", ["Content-Type" => "application/json"], 200);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals("Response body", $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testErrorResponse()
    {
        $response = $this->responseFactory->error("Error occurred", "http://example.com", 400);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals("Error occurred", $response->getMessage());
    }
}
