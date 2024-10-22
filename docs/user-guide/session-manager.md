# SessionManager Class Documentation

This document provides an overview of the `SessionManager` class, which is designed to manage PHP session operations easily and effectively.

## Table of Contents
- [start()](#start)
- [set($key, $value)](#setkey-value)
- [get($key, $default = null)](#getkey-default--null)
- [has($key)](#haskey)
- [remove($key)](#removekey)
- [destroy()](#destroy)
- [regenerate()](#regenerate)
- [flash($key, $value)](#flashkey-value)
- [getFlash($key, $default = null)](#getflashkey-default--null)
- [setExpiration($lifetime = 1800)](#setexpirationlifetime--1800)

---

### `start()`
Initializes the session if it hasn't been started already.

#### Usage
```php
SessionManager::start();
```

#### Behavior
- Checks if a session already exists using `session_status()`.
- Starts a new session if no session is active.
- Throws an exception if headers have already been sent before starting the session.
- Logs errors when necessary.

---

### `set($key, $value)`
Stores a value in the session associated with a given key.

#### Parameters
- `$key` (string): The key to store the value under.
- `$value` (mixed): The value to store in the session.

#### Usage
```php
SessionManager::set('username', 'JohnDoe');
```

#### Behavior
- Calls `start()` to ensure the session is active.
- Stores the value in the session and then closes the session to release the session lock.
  
---

### `get($key, $default = null)`
Retrieves a value from the session for the specified key.

#### Parameters
- `$key` (string): The key of the session variable to retrieve.
- `$default` (mixed): Optional default value if the key doesn't exist.

#### Usage
```php
$username = SessionManager::get('username', 'Guest');
```

#### Behavior
- Calls `start()` to ensure the session is active.
- Returns the value associated with the key, or `$default` if the key doesn't exist.

---

### `has($key)`
Checks if a specific key exists in the session.

#### Parameters
- `$key` (string): The key to check in the session.

#### Usage
```php
if (SessionManager::has('username')) {
    // Key exists
}
```

#### Behavior
- Calls `start()` to ensure the session is active.
- Returns `true` if the key exists in the session, otherwise `false`.

---

### `remove($key)`
Removes a session variable for the specified key.

#### Parameters
- `$key` (string): The key to remove from the session.

#### Usage
```php
SessionManager::remove('username');
```

#### Behavior
- Calls `start()` to ensure the session is active.
- Unsets the session variable and releases the session lock.

---

### `destroy()`
Destroys the current session, removing all stored data.

#### Usage
```php
SessionManager::destroy();
```

#### Behavior
- Unsets all session variables and destroys the session data.
- If session cookies are used, it invalidates the session cookie by setting its expiration time in the past.

---

### `regenerate()`
Regenerates the session ID to enhance security by replacing the old ID with a new one.

#### Usage
```php
SessionManager::regenerate();
```

#### Behavior
- Regenerates the session ID if a session is active.
- Passes `true` to `session_regenerate_id()` to delete the old session data associated with the previous ID.

---

### `flash($key, $value)`
Stores a flash message, which is a temporary session value that persists only for the next request.

#### Parameters
- `$key` (string): The key to store the flash message under.
- `$value` (mixed): The value of the flash message.

#### Usage
```php
SessionManager::flash('success', 'Your changes were saved.');
```

#### Behavior
- Calls `start()` to ensure the session is active.
- Stores the flash message in the session and then closes the session to release the lock.

---

### `getFlash($key, $default = null)`
Retrieves a flash message for the specified key and removes it from the session.

#### Parameters
- `$key` (string): The key of the flash message to retrieve.
- `$default` (mixed): Optional default value if the key doesn't exist.

#### Usage
```php
$message = SessionManager::getFlash('success', 'No messages');
```

#### Behavior
- Calls `start()` to ensure the session is active.
- Retrieves and then removes the flash message for the given key.
- Returns the flash message or `$default` if the key doesn't exist.

---

### `setExpiration($lifetime = 1800)`
Sets the session expiration time (default 30 minutes). If the session is inactive for longer than the lifetime, the session will be destroyed.

#### Parameters
- `$lifetime` (int): The session lifetime in seconds. Defaults to 1800 seconds (30 minutes).

#### Usage
```php
SessionManager::setExpiration(3600); // Set expiration time to 1 hour
```

#### Behavior
- Calls `start()` to ensure the session is active.
- Destroys the session if the time since the last activity exceeds the session lifetime.
- Updates the last activity timestamp.

---
