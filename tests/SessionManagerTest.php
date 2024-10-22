<?php

use GuiBranco\Pancake\SessionManager;
use PHPUnit\Framework\TestCase;

class SessionManagerTest extends TestCase
{
    protected function setUp(): void
    {
        // Make sure the session is destroyed before each test to avoid side effects.
        SessionManager::destroy();
    }

    public function testStartSessionWhenNotStarted()
    {
        $this->assertSame(PHP_SESSION_NONE, session_status());
        SessionManager::start();
        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
    }

    public function testStartSessionWhenHeadersAlreadySent()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Headers already sent. Cannot start the session.");

        // Simulate headers being sent
        $this->mockFunction('headers_sent', true);

        SessionManager::start();
    }

    public function testSetAndGetSessionValue()
    {
        SessionManager::set('key', 'value');
        $this->assertSame('value', SessionManager::get('key'));
    }

    public function testGetDefaultValueWhenKeyDoesNotExist()
    {
        $this->assertSame('default', SessionManager::get('non_existent_key', 'default'));
    }

    public function testHasKeyInSession()
    {
        SessionManager::set('key', 'value');
        $this->assertTrue(SessionManager::has('key'));
        $this->assertFalse(SessionManager::has('non_existent_key'));
    }

    public function testRemoveKeyFromSession()
    {
        SessionManager::set('key', 'value');
        SessionManager::remove('key');
        $this->assertFalse(SessionManager::has('key'));
    }

    public function testDestroySession()
    {
        SessionManager::set('key', 'value');
        SessionManager::destroy();
        $this->assertSame(PHP_SESSION_NONE, session_status());
        $this->assertNull(SessionManager::get('key'));
    }

    public function testRegenerateSessionId()
    {
        SessionManager::start();
        $oldSessionId = session_id();
        SessionManager::regenerate();
        $newSessionId = session_id();
        $this->assertNotSame($oldSessionId, $newSessionId);
    }

    public function testFlashAndGetFlashMessage()
    {
        SessionManager::flash('flash_key', 'flash_value');
        $this->assertSame('flash_value', SessionManager::getFlash('flash_key'));
        $this->assertNull(SessionManager::getFlash('flash_key'));
    }

    public function testGetFlashMessageReturnsDefaultIfNotSet()
    {
        $this->assertSame('default', SessionManager::getFlash('non_existent_key', 'default'));
    }

    public function testSetSessionExpiration()
    {
        SessionManager::set('key', 'value');
        SessionManager::setExpiration(1); // Set a very short expiration time
        sleep(2); // Wait for the session to expire
        $this->assertFalse(SessionManager::has('key')); // Session should be destroyed
    }

    protected function mockFunction($function, $returnValue)
    {
        // Mock a function to return a specified value
        $mock = $this->getMockBuilder(stdClass::class)
                     ->addMethods([$function])
                     ->getMock();

        $mock->method($function)->willReturn($returnValue);
        return $mock;
    }

    protected function tearDown(): void
    {
        // Ensure that each test cleans up session to avoid conflicts
        SessionManager::destroy();
    }
}
