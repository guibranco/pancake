<?php

namespace GuiBranco\Pancake;

class SessionManager
{
    public static function start()
    {
        if (session_status() == PHP_SESSION_NONE) {
            try {
                if (!headers_sent()) {
                    session_start();
                } else {
                    throw new \Exception("Headers already sent. Cannot start the session.");
                }
            } catch (\Exception $e) {
                error_log($e->getMessage());
            }
        }
    }

    public static function set($key, $value)
    {
        self::start();
        $_SESSION[$key] = $value;
        session_write_close();  // Release session lock
    }

    public static function get($key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has($key)
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove($key)
    {
        self::start();
        unset($_SESSION[$key]);
        session_write_close(); //Release Session Lock so that other scripts can work
    }

    public static function destroy()
    {
        if (session_status() != PHP_SESSION_NONE) {
            session_unset();
            session_destroy();
        }
    }

    public static function regenerate()
    {
        if (session_status() != PHP_SESSION_NONE) {
            session_regenerate_id(true);
        }
    }

    public static function flash($key, $value)
    {
        self::start();
        $_SESSION['flash'][$key] = $value;
        session_write_close();
    }

    public static function getFlash($key, $default = null)
    {
        self::start();
        $value = $_SESSION['flash'][$key] ?? $default;
        unset($_SESSION['flash'][$key]);  
        session_write_close(); 
        return $value;
    }

    public static function setExpiration($lifetime = 1800) //Default expiration time is 30 minutes(1800 sec)
    {
        self::start();
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $lifetime)) {
            self::destroy();
        }
        $_SESSION['last_activity'] = time(); 
        session_write_close(); 
    }
}
