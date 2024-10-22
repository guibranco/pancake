<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests;

use GuiBranco\Pancake\SessionManager;
use PHPUnit\Framework\TestCase;

class SessionManagerTest extends TestCase
{
    protected function setUp(): void
    {
        $this->reset();
    }

    public function testStartSessionWhenNotStarted()
    {
        $this->assertSame(PHP_SESSION_NONE, session_status());

        SessionManager::start();

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
    }

    public function testSetAndGetSessionValue()
    {
        SessionManager::set('key', 'value');
        $this->assertSame('value', SessionManager::get('key'));
    }

    public function testGetSessionWithDefault()
    {
        $this->assertSame('default', SessionManager::get('non_existing_key', 'default'));
    }

    public function testHasKeyInSession()
    {
        SessionManager::set('key', 'value');
        $this->assertTrue(SessionManager::has('key'));
        $this->assertFalse(SessionManager::has('non_existing_key'));
    }

    public function testRemoveKeyFromSession()
    {
        SessionManager::set('key', 'value');
        SessionManager::remove('key');
        $this->assertFalse(SessionManager::has('key'));
    }

    public function testRegenerateSessionId()
    {
        SessionManager::start();

        $originalSessionId = session_id();
        SessionManager::regenerate();
        $newSessionId = session_id();

        $this->assertNotSame($originalSessionId, $newSessionId);
    }

    public function testFlashAndGetFlashMessage()
    {
        SessionManager::flash('flash_key', 'flash_value');
        $this->assertSame('flash_value', SessionManager::getFlash('flash_key'));
        $this->assertNull(SessionManager::getFlash('flash_key'));
    }

    public function testSetSessionExpiration()
    {
        $_SESSION['key'] = 'value';
        $_SESSION['last_activity'] = time() - 3600;

        SessionManager::setExpiration(1800);

        $this->assertFalse(SessionManager::has('key'));
    }

    protected function tearDown(): void
    {
        $this->reset();
    }

    private function reset(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            session_destroy();
        }
        $_SESSION = [];
    }
}
