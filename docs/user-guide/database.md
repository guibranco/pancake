# Database

## Table of content

- [Database](#database)
  - [Table of content](#table-of-content)
  - [About](#about)
  - [Requirements](#requirements)
  - [Example usage](#example-usage)
  - [Available methods](#available-methods)
    - [Constructor](#constructor)
    - [Prepare](#prepare)
    - [Bind](#bind)
    - [Execute](#execute)
    - [Fetch](#fetch)
    - [FetchAll](#fetchall)
    - [RowCount](#rowcount)
    - [LastInsertId](#lastinsertid)
    - [BeginTransaction](#begintransaction)
    - [Commit](#commit)
    - [RollBack](#rollback)
    - [Close](#close)
    - [GetError](#geterror)

## About

This class is responsible for database operations using PDO to connect to a MySQL/MariaDB server.

## Requirements

This requires `pdo` and `pdo_mysql` to be active with your PHP settings.

## Example usage

A simple example usage can be as following:

```php
<?php

use GuiBranco\Pancake\Database;
use GuiBranco\Pancake\DatabaseException;

try {
    // Initialize the Database
    $db = new Database('localhost', 'mydb', 'username', 'password');

    // Example Insert
    $db->prepare("INSERT INTO users (name, email) VALUES (:name, :email)");
    $db->bind(':name', 'John Doe');
    $db->bind(':email', 'john@example.com');
    $db->execute();
    echo "Last Insert ID: " . $db->lastInsertId();

    // Example Select
    $db->prepare("SELECT * FROM users WHERE id = :id");
    $db->bind(':id', 1);
    $user = $db->fetch();
    print_r($user);

    // Example Fetch All
    $db->prepare("SELECT * FROM users");
    $users = $db->fetchAll();
    print_r($users);

} catch (DatabaseException $e) {
    echo 'Error: ' . $e->getMessage();
}
```

## Available methods

### Constructor

The constructor establishes a connection to the database using the provided credentials and initializes the PDO object.

Parameters:

- `$host` (string): Database host, e.g., `localhost`.
- `$dbname` (string): The name of the database.
- `$username` (string): Username for the database.
- `$password` (string): Password for the database.
- `$charset` (string, optional): Character set to use, defaults to `utf8mb4`.

```php
$db = new Database('localhost', 'my-db', 'username', 'password');
```

### Prepare

Prepares a SQL query for execution. This method is necessary before binding values and executing the query.

```php
$db->prepare('SELECT * FROM users WHERE id = :id');
```

---

### Bind

Binds a value to a parameter in the prepared SQL statement. If the type is not provided, it is determined based on the value's type.

Parameters:

- `$param` (string): The parameter to bind (e.g., `:id`).
- `$value` (mixed): The value to bind to the parameter.
- `$type` (optional): The data type of the parameter (e.g., `PDO::PARAM_INT`).

```php
$db->bind(':id', 1, PDO::PARAM_INT);
```

---

### Execute

Executes the prepared SQL statement. It returns `true` on success or `false` on failure.

```php
$db->execute();
```

---

### Fetch

Executes the prepared statement and fetches a single row from the result set.

```php
$result = $db->fetch();
```

---

### FetchAll

Executes the prepared statement and fetches all rows from the result set.

```php
$results = $db->fetchAll();
```

---

### RowCount

Returns the number of rows affected by the last SQL statement.

```php
$count = $db->rowCount();
```

---

### LastInsertId

Returns the ID of the last inserted row.

```php
$lastId = $db->lastInsertId();
```

---

### BeginTransaction

Starts a new database transaction.

```php
$db->beginTransaction();
```

---

### Commit

Commits the current database transaction.

```php
$db->commit();
```

---

### RollBack

Rolls back the current database transaction.

```php
$db->rollBack();
```

---

### Close

Closes the database connection and cleans up resources.

```php
$db->close();
```

---

### GetError

Returns the last error message encountered by the database, or `null` if no errors have occurred.

```php
$error = $db->getError();
```

---
