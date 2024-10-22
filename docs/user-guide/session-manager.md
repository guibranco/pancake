# Session Manager

## Table of Contents

- [Session Manager](#session-manager)
  - [Table of Contents](#table-of-contents)
  - [About](#about)
  - [Requirements](#requirements)
  - [Available Methods](#available-methods)
    - [Start](#start)
    - [Set](#set)
    - [Get](#get)
    - [Has](#has)
    - [Remove](#remove)
    - [Destroy](#destroy)
    - [Regenerate](#regenerate)
    - [Flash](#flash)
    - [Get Flash](#get-flash)
    - [Set Expiration](#set-expiration)

---

## About

The `SessionManager` class is a utility class for managing PHP sessions. It provides a simplified interface for starting sessions, setting and retrieving session data, managing flash messages, handling session expiration, and regenerating session IDs. It abstracts away some of the complexities of native PHP session management, ensuring smoother operations, error handling, and better session control.

This class is ideal for applications where session management is crucial but should remain simple and maintainable.

---

## Requirements

To use the `SessionManager` class, the following requirements must be met:

- **PHP Version**: 8.3 or later
- **Session Handling**: The class relies on PHP's built-in session handling. Ensure session support is enabled in your PHP configuration.
- **Exception Handling**: The class makes use of custom exceptions (`SessionException`). This requires proper error handling in your application.

---

## Available Methods

### Start

Starts a session if no session is currently active. It checks if headers have already been sent and throws a `SessionException` if session initialization fails.

```php
use GuiBranco\Pancake\SessionManager;

SessionManager::start();
```

---

### Set

Stores a value in the session under the specified key. This method ensures that a session is started before saving the value.

```php
use GuiBranco\Pancake\SessionManager;

SessionManager::set('user_id', 12345);
```

---

### Get

Retrieves the value associated with a given key from the session. If the key is not found, it returns the default value.

```php
use GuiBranco\Pancake\SessionManager;

$userId = SessionManager::get('user_id', 'guest');
```

---

### Has

Checks if a key exists in the session. Returns `true` if the key is present, `false` otherwise.

```php
use GuiBranco\Pancake\SessionManager;

if (SessionManager::has('user_id')) {
    // Key exists
}
```

---

### Remove

Removes a key from the session. If the key is not present, nothing happens.

```php
use GuiBranco\Pancake\SessionManager;

SessionManager::remove('user_id');
```

---

### Destroy

Destroys the current session and removes all session data. If session cookies are enabled, they will also be cleared.

```php
use GuiBranco\Pancake\SessionManager;

SessionManager::destroy();
```

---

### Regenerate

Regenerates the session ID to prevent session fixation attacks. This method does nothing if no session is active.

```php
use GuiBranco\Pancake\SessionManager;

SessionManager::regenerate();
```

---

### Flash

Stores a flash message in the session. Flash messages are meant to persist only for the next request and are automatically removed after being retrieved.

```php
use GuiBranco\Pancake\SessionManager;

SessionManager::flash('status', 'Operation successful');
```

---

### Get Flash

Retrieves a flash message from the session. Once retrieved, the flash message is automatically deleted. If the key doesn't exist, it returns the default value.

```php
use GuiBranco\Pancake\SessionManager;

$statusMessage = SessionManager::getFlash('status', 'No status message found');
```

---

### Set Expiration

Sets a session expiration time. If the session's last activity exceeds the specified lifetime, the session is destroyed and restarted.

```php
use GuiBranco\Pancake\SessionManager;

SessionManager::setExpiration(3600); // 1 hour expiration
```
