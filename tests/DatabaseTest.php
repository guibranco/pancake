<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests;

use GuiBranco\Pancake\Database;
use GuiBranco\Pancake\DatabaseException;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    private static string $host = '127.0.0.1';

    private static Database $db;

    public static function setUpBeforeClass(): void
    {
        self::$db = new Database(
            self::$host,
            'pancake',
            'test',
            'test'
        );

        self::$db->prepare("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100),
            email VARCHAR(100)
        )");
        self::$db->execute();
    }

    public static function tearDownAfterClass(): void
    {
        self::$db->prepare("DROP TABLE IF EXISTS users");
        self::$db->execute();
        self::$db->close();
    }

    public function testPrepare(): void
    {
        $this->expectNotToPerformAssertions();
        self::$db->prepare("SELECT * FROM users");
    }

    public function testExecute(): void
    {
        self::$db->prepare("INSERT INTO users (name, email) VALUES (:name, :email)");
        self::$db->bind(':name', 'John Doe');
        self::$db->bind(':email', 'john@example.com');
        $result = self::$db->execute();

        $this->assertTrue($result);
    }

    public function testFetch(): void
    {
        self::$db->prepare("SELECT * FROM users WHERE name = :name");
        self::$db->bind(':name', 'John Doe');
        $result = self::$db->fetch();

        $this->assertIsArray($result);
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function testFetchAll(): void
    {
        self::$db->prepare("SELECT * FROM users");
        $results = self::$db->fetchAll();

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results[0]['name']);
    }

    public function testRowCount(): void
    {
        self::$db->prepare("SELECT * FROM users");
        self::$db->fetchAll();
        $rowCount = self::$db->rowCount();

        $this->assertEquals(1, $rowCount);
    }

    public function testLastInsertId(): void
    {
        self::$db->prepare("INSERT INTO users (name, email) VALUES (:name, :email)");
        self::$db->bind(':name', 'Jane Doe');
        self::$db->bind(':email', 'jane@example.com');
        self::$db->execute();

        $lastInsertId = self::$db->lastInsertId();
        $this->assertIsString($lastInsertId);
    }

    public function testTransaction(): void
    {
        self::$db->beginTransaction();
        self::$db->prepare("INSERT INTO users (name, email) VALUES (:name, :email)");
        self::$db->bind(':name', 'Jack Doe');
        self::$db->bind(':email', 'jack@example.com');
        self::$db->execute();

        $this->assertTrue(self::$db->commit());
    }

    public function testRollBack(): void
    {
        self::$db->beginTransaction();
        self::$db->prepare("INSERT INTO users (name, email) VALUES (:name, :email)");
        self::$db->bind(':name', 'Failed User');
        self::$db->bind(':email', 'fail@example.com');
        self::$db->execute();

        $this->assertTrue(self::$db->rollBack());

        // Verify rollback by checking the row does not exist
        self::$db->prepare("SELECT * FROM users WHERE email = :email");
        self::$db->bind(':email', 'fail@example.com');
        $result = self::$db->fetch();
        $this->assertFalse($result);
    }

    public function testConnectionFailure(): void
    {
        $this->expectException(DatabaseException::class);

        new Database(self::$host, 'invalid_db', 'wrong_user', 'wrong_password');
    }

    public function testGetError(): void
    {
        try {
            $db = new Database(self::$host, 'invalid_db', 'wrong_user', 'wrong_password');
        } catch (DatabaseException $e) {
            $this->assertNotNull($e->getMessage());
        }
    }
}
