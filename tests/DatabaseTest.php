<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests;

use GuiBranco\Pancake\Database;
use PHPUnit\Framework\TestCase;

final class DatabaseTest extends TestCase
{
    public function testCanConnect(): void
    {
        $database = new Database('127.0.0.1', 'pancake', 'root', 'root');
        $this->assertInstanceOf(Database::class, $database);
        $this->assertNull($database->getError());
        $database->close();
    }

    public function testCanNotConnect(): void
    {
        $database = new Database('127.0.0.1', 'pancake', 'root', 'root123');
        $this->assertInstanceOf(Database::class, $database);
        $this->assertNotNull($database->getError());
        $database->close();
    }

    public function testCanQuery(): void
    {
        $database = new Database('127.0.0.1', 'pancake', 'root', 'root');
        $this->assertInstanceOf(Database::class, $database);
        $this->assertNull($database->getError());
        $result = $database->query('SELECT * FROM users');
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $database->close();
    }

}
