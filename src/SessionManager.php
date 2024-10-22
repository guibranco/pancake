<?php

namespace GuiBranco\Pancake;

use Exception;

class SessionManager
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        if (headers_sent()) {
            throw new SessionException("Headers already sent. Cannot start the session.");
        }

        session_start();
    }

    public static function set($key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
        session_write_close();
    }

    public static function get($key, $default = null): mixed
    {
        self::start();
        $value = $_SESSION[$key] ?? $default;
        session_write_close();
        return $value;
    }

    public static function has($key): bool
    {
        self::start();
        $isset = isset($_SESSION[$key]);
        session_write_close();
        return $isset;
    }

    public static function remove($key): void
    {
        self::start();
        unset($_SESSION[$key]);
        session_write_close();
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            return;
        }

        session_unset();
        session_destroy();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
    }

    public static function regenerate(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            session_regenerate_id(true);
        }
    }

    public static function flash($key, $value): void
    {
        self::start();
        $_SESSION['flash'][$key] = $value;
        session_write_close();
    }

    public static function getFlash($key, $default = null): mixed
    {
        self::start();
        $value = $_SESSION['flash'][$key] ?? $default;
        unset($_SESSION['flash'][$key]);
        session_write_close();
        return $value;
    }

    public static function setExpiration($lifetime = 1800): void
    {
        self::start();
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $lifetime)) {
            self::destroy();
            self::start();
        }
        $_SESSION['last_activity'] = time();
        session_write_close();
    }
}

class SessionException extends Exception
{
}
