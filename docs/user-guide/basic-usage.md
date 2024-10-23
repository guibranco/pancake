# Basic usage

This document provides a quick introduction to the `GuiBranco\Pancake` toolkit. It includes links to each feature's documentation and a basic example of how to use the toolkit effectively.
The main namespace is `GuiBranco\Pancake`.

---

## Features

- **[Color](color.md)**: Utility for managing colors based on text.
- **[Database](database.md)**: Interface for managing database connections and queries.
- **[GUID v4](guid-v4.md)**: Generate unique GUIDs for your data entities.
- **[Health Checks](health-checks.md)**: Monitor the health status of your applications and services.
- **[HTTP Requests](request.md)**: Easily send and manage HTTP requests, supporting multiple methods such as GET, POST, PUT, and DELETE.
- **[Logger](logger.md)**: Robust logging capabilities to track application events and errors.
- **[Memory Cache](memory-cache.md)**: Implement caching strategies to improve application performance.
- **[One Signal](one-signal.md)**: Integrate One Signal for push notifications in your applications.
- **[Session Manager](session-manager.md)**: Simplify PHP session handling with methods for setting, getting, and managing session data, including flash messages.
- **[ShieldsIo](shieldsio.md)**: Create custom badges for your projects using Shields.io.

---

## Introduction

The `GuiBranco\Pancake` toolkit is designed to simplify common operations in PHP applications, such as session management, handling HTTP requests, and parsing TOML files. This toolkit provides a set of utility classes with a simple API to help developers focus on building their applications without worrying about these core tasks.

---

## Basic Usage Example

Here is a simple example demonstrating how you can use the `GuiBranco\Pancake` toolkit in a real-world PHP application:

```php
<?php

require 'vendor/autoload.php'; // Autoloading using Composer

use GuiBranco\Pancake\SessionManager;
use GuiBranco\Pancake\Request;
use GuiBranco\Pancake\Database;

// Session Management Example
SessionManager::start();
SessionManager::set('username', 'john_doe');
echo "Session value for username: " . SessionManager::get('username');

// Flash message
SessionManager::flash('success', 'Your changes were saved!');
echo "Flash message: " . SessionManager::getFlash('success');

// HTTP Request Example
$request = new Request();
$response = $request->get('https://api.example.com/data');
echo "API Response: " . $response->getBody();

// Database Example
try {
    // Load configuration from environment or config file
    $db = new Database(
        getenv('DB_HOST'),
        getenv('DB_NAME'),
        getenv('DB_USER'),
        getenv('DB_PASS')
    );
    
    // Transaction example
    $db->beginTransaction();
    
    // Insert example
    $db->prepare("INSERT INTO users (name, email) VALUES (:name, :email)");
    $db->bind(':name', 'John Doe');
    $db->bind(':email', 'johndoe@example.com');
    $db->execute();
    $userId = $db->lastInsertId();
    echo "Last inserted ID: " . $userId;
    
    // Fetch single record
    $db->prepare("SELECT * FROM users WHERE id = :id");
    $db->bind(':id', 1);
    $user = $db->fetch();
    echo "User: " . json_encode($user, JSON_PRETTY_PRINT);
    
    // Fetch all records
    $db->prepare("SELECT * FROM users");
    $users = $db->fetchAll();
    echo "All users: " . json_encode($users, JSON_PRETTY_PRINT);
    
    $db->commit();

} catch (DatabaseException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Database error: " . $e->getMessage();
} finally {
    // Ensure connection is closed
    $db = null;
}
```
