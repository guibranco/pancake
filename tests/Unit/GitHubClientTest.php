<?php

use PHPUnit\Framework\TestCase;
use GuiBranco\Pancake\GitHubClient;
use GuiBranco\Pancake\Response;
use GuiBranco\Pancake\RequestException;

// DummyRequest extends the original Request class and overrides HTTP methods
// to allow injection of a callback for testing purposes.
class DummyRequest /* extends GuiBranco\Pancake\Request */ {
    private $callback;

    public function __construct(callable $callback) {
        $this->callback = $callback;
    }

    public function get($url, $headers = []) {
        $cb = $this->callback;
        return $cb('GET', $url, $headers, null);
    }

    public function post($url, $headers = [], $data = null) {
        $cb = $this->callback;
        return $cb('POST', $url, $headers, $data);
    }
}

class GitHubClientTest extends TestCase {
    public function testGetRepository() {
        $dummyResponse = Response::success('{"name": "repo"}', 'https://api.github.com/repos/test/repo', ['Content-Type' => 'application/json'], 200);
        $dummyRequest = new DummyRequest(function($method, $url, $headers, $data) use ($dummyResponse) {
            $this->assertEquals('GET', $method);
            $this->assertStringContainsString('/repos/test/repo', $url);
            return $dummyResponse;
        });

        // Inject DummyRequest via the optional parameter in GitHubClient
        $client = new GitHubClient(['user_token' => 'dummy'], null, false, [], $dummyRequest);
        $response = $client->getRepository('test', 'repo');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"name": "repo"}', $response->getBody());
    }

    public function testCreateIssue() {
        $dummyResponse = Response::success('{"id": 1, "title": "Issue"}', 'https://api.github.com/repos/test/repo/issues', ['Content-Type' => 'application/json'], 201);
        $dummyRequest = new DummyRequest(function($method, $url, $headers, $data) use ($dummyResponse) {
            $this->assertEquals('POST', $method);
            $this->assertStringContainsString('/repos/test/repo/issues', $url);
            $decoded = json_decode($data, true);
            $this->assertEquals('Test Issue', $decoded['title']);
            $this->assertEquals('This is a test issue', $decoded['body']);
            return $dummyResponse;
        });

        $client = new GitHubClient(['user_token' => 'dummy'], null, false, [], $dummyRequest);
        $response = $client->createIssue('test', 'repo', 'Test Issue', 'This is a test issue');
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertStringContainsString('Issue', $response->getBody());
    }

    public function testGetUser() {
        $dummyResponse = Response::success('{"login": "testuser"}', 'https://api.github.com/users/testuser', ['Content-Type' => 'application/json'], 200);
        $dummyRequest = new DummyRequest(function($method, $url, $headers, $data) use ($dummyResponse) {
            $this->assertEquals('GET', $method);
            $this->assertStringContainsString('/users/testuser', $url);
            return $dummyResponse;
        });

        $client = new GitHubClient(['user_token' => 'dummy'], null, false, [], $dummyRequest);
        $response = $client->getUser('testuser');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('"login": "testuser"', $response->getBody());
    }

    public function testIgnoredStatusCode() {
        $dummyResponse = Response::error('Not Found', 'https://api.github.com/repos/test/repo', 404);
        $dummyRequest = new DummyRequest(function($method, $url, $headers, $data) use ($dummyResponse) {
            return $dummyResponse;
        });

        // Set ignored status code to 404 so error is not thrown
        $client = new GitHubClient(['user_token' => 'dummy'], null, true, [404], $dummyRequest);
        $response = $client->getRepository('test', 'repo');
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testThrowExceptionOnError() {
        $this->expectException(RequestException::class);
        $dummyResponse = Response::error('Bad Request', 'https://api.github.com/repos/test/repo', 400);
        $dummyRequest = new DummyRequest(function($method, $url, $headers, $data) use ($dummyResponse) {
            return $dummyResponse;
        });

        // throwExceptions true and no ignored status code, should throw exception
        $client = new GitHubClient(['user_token' => 'dummy'], null, true, [], $dummyRequest);
        $client->getRepository('test', 'repo');
    }
}
