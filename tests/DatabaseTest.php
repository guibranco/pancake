<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests;

use GuiBranco\Pancake\Database;
use GuiBranco\Pancake\DatabaseException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \GuiBranco\Pancake\Database
 */
class DatabaseTest extends TestCase
{
    private static string $host;
    private static string $databaseName;
    private static string $username;
    private static string $password;
    private static Database $database;

    private static function loadConfig(): void
    {
        self::$host = getenv('DB_HOST') ?: '127.0.0.1';
        self::$databaseName = getenv('DB_NAME') ?: 'pancake';
        self::$username = getenv('DB_USER') ?: 'test';
        self::$password = getenv('DB_PASS') ?: 'test';
    }

    public static function setUpBeforeClass(): void
    {
        self::loadConfig();
        self::$database = new Database(
            self::$host,
            self::$databaseName,
            self::$username,
            self::$password
        );

        self::$database->prepare("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100),
            email VARCHAR(100)
        )");
        self::$database->execute();
    }

    public static function tearDownAfterClass(): void
    {
        self::$database->prepare("DROP TABLE IF EXISTS users");
        self::$database->execute();
        self::$database->close();
    }

    public function testPrepare(): void
    {
        $this->expectNotToPerformAssertions();
        self::$database->prepare("SELECT * FROM users");
    }

    public function testExecute(): void
    {
        self::$database->prepare("INSERT INTO users (name, email) VALUES (:name, :email)");
        self::$database->bind(':name', 'John Doe');
        self::$database->bind(':email', 'john@example.com');
        $result = self::$database->execute();

        $this->assertTrue($result);
    }

    public function testFetch(): void
    {
        self::$database->prepare("SELECT * FROM users WHERE name = :name");
        self::$database->bind(':name', 'John Doe');
        $result = self::$database->fetch();

        $this->assertIsArray($result);
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function testFetchAll(): void
    {
        self::$database->prepare("SELECT * FROM users");
        $results = self::$database->fetchAll();

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results[0]['name']);
    }

    public function testRowCount(): void
    {
        self::$database->prepare("SELECT * FROM users");
        self::$database->fetchAll();
        $rowCount = self::$database->rowCount();

        $this->assertEquals(1, $rowCount);
    }

    public function testRowCountWithoutStatement(): void
    {
        $this->expectException(DatabaseException::class);

        $database = new Database(
            self::$host,
            self::$databaseName,
            self::$username,
            self::$password
        );
        $database->rowCount();
    }

    public function testLastInsertId(): void
    {
        self::$database->prepare("INSERT INTO users (name, email) VALUES (:name, :email)");
        self::$database->bind(':name', 'Jane Doe');
        self::$database->bind(':email', 'jane@example.com');
        self::$database->execute();

        $lastInsertId = self::$database->lastInsertId();
        $this->assertIsString($lastInsertId);
    }

    public function testTransaction(): void
    {
        self::$database->beginTransaction();
        self::$database->prepare("INSERT INTO users (name, email) VALUES (:name, :email)");
        self::$database->bind(':name', 'Jack Doe');
        self::$database->bind(':email', 'jack@example.com');
        self::$database->execute();

        $this->assertTrue(self::$database->commit());
    }

    public function testCommitWithoutTransaction(): void
    {
        $this->expectException(DatabaseException::class);

        $database = new Database(
            self::$host,
            self::$databaseName,
            self::$username,
            self::$password
        );
        $database->close();
        $database->commit();
    }

    public function testBeginTransactionInsideATransaction(): void
    {
        $database = new Database(
            self::$host,
            self::$databaseName,
            self::$username,
            self::$password
        );

        try {
            $database->beginTransaction();
            $database->beginTransaction();
        } catch (DatabaseException $e) {
            $this->assertNotNull($e->getMessage());
            $this->assertEquals('Transaction already in progress', $e->getMessage());
        }
    }

    public function testRollBack(): void
    {
        self::$database->beginTransaction();
        self::$database->prepare("INSERT INTO users (name, email) VALUES (:name, :email)");
        self::$database->bind(':name', 'Failed User');
        self::$database->bind(':email', 'fail@example.com');
        self::$database->execute();

        $this->assertTrue(self::$database->rollBack());

        // Verify rollback by checking the row does not exist
        self::$database->prepare("SELECT * FROM users WHERE email = :email");
        self::$database->bind(':email', 'fail@example.com');
        $result = self::$database->fetch();
        $this->assertFalse($result);
    }

    public function testRollBackWithoutTransaction(): void
    {
        $this->expectException(DatabaseException::class);

        $database = new Database(
            self::$host,
            self::$databaseName,
            self::$username,
            self::$password
        );
        $database->close();
        $database->rollBack();
    }

    public function testIsConnected(): void
    {
        $isConnected = self::$database->isConnected();

        $this->assertTrue($isConnected);
    }

    public function testIsDisconnected(): void
    {
        $database = new Database(
            self::$host,
            self::$databaseName,
            self::$username,
            self::$password
        );
        $database->close();

        $isConnected = $database->isConnected();
        $this->assertFalse($isConnected);
    }

    public function testConnectionFailure(): void
    {
        $this->expectException(DatabaseException::class);

        new Database(self::$host, 'invalid_db', 'wrong_user', 'wrong_password');
    }

    /**
     * @dataProvider connectionFailureProvider
     */
    public function testConnectionFailures(
        string $dbName,
        string $user,
        string $pass,
        ?int $port,
        string $expectedMessage,
        string $expectedOperation
    ): void {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage($expectedMessage);

        new Database(self::$host, $dbName, $user, $pass, $port);
    }

    public function connectionFailureProvider(): array
    {
        return [
            'invalid_database' => [
                'invalid_db',
                'test',
                'test',
                null,
                'Unknown database',
                'connection'
            ],
            'invalid_credentials' => [
                'pancake',
                'wrong_user',
                'wrong_pass',
                null,
                'Access denied',
                'connection'
            ],
            'invalid_port_negative' => [
                'pancake',
                'test',
                'test',
                -1,
                'Invalid port number',
                'connection'
            ],
            'invalid_port_zero' => [
                'pancake',
                'test',
                'test',
                0,
                'Invalid port number',
                'connection'
            ],
            'invalid_port_too_high' => [
                'pancake',
                'test',
                'test',
                65536,
                'Invalid port number',
                'connection'
            ]
        ];
    }
}
