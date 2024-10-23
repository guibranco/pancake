<?php

namespace GuiBranco\Pancake;

use PDO;
use PDOException;

class Database implements IDatabase
{
    private $pdo;
    private $stmt;

    public function __construct(
        string $host,
        string $dbname,
        string $username,
        string $password,
        int $port = 3306,
        string $charset = 'utf8mb4'
    ) {
        if ($port < 1 || $port > 65535) {
            throw new DatabaseException('Invalid port number');
        }

        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to connect to database. Please check your configuration.', 0, $e);
        }
    }

    public function prepare(string $query): void
    {
        $this->stmt = $this->pdo->prepare($query);
    }

    public function bind(string $param, $value, $type = null): void
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    public function execute(): bool
    {
        return $this->stmt->execute();
    }

    public function fetch()
    {
        $this->execute();
        return $this->stmt->fetch();
    }

    public function fetchAll()
    {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    public function rowCount(): int
    {
        if ($this->stmt === null) {
            throw new DatabaseException('No statement available to get row count.');
        }
        return $this->stmt->rowCount();
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Check if the database connection is active
     */
    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    public function beginTransaction(): bool
    {
        if (!$this->isConnected()) {
            throw new DatabaseException('No active database connection');
        }
        if ($this->pdo->inTransaction()) {
            throw new DatabaseException('Transaction already in progress');
        }
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        if (!$this->isConnected() || !$this->pdo->inTransaction()) {
            throw new DatabaseException('No active transaction to commit');
        }
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        if (!$this->isConnected() || !$this->pdo->inTransaction()) {
            throw new DatabaseException('No active transaction to roll back');
        }
        return $this->pdo->rollBack();
    }

    public function close(): void
    {
        $this->stmt = null;
        $this->pdo = null;
    }
}
