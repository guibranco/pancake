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
        if (trim($host) === '' || trim($dbname) === '' || trim($username) === '') {
            throw new DatabaseException('Host, database name, and username cannot be empty');
        }

        if ($port < 1 || $port > 65535) {
            throw new DatabaseException('Invalid port number');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            str_replace(';', '', $host),
            $port,
            str_replace(';', '', $dbname),
            str_replace(';', '', $charset)
        );
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to connect to database. Please check your configuration.', 'connection', 0, $e);
        }
    }

    public function prepare(string $query): self
    {
        $this->stmt = $this->pdo->prepare($query);
        return $this;
    }

    public function bind(string $param, mixed $value, ?int $type = null): self
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
        return $this;
    }

    public function execute(): bool
    {
        return $this->stmt->execute();
    }

    public function fetch(int $fetchMode = null): mixed
    {
        $this->execute();
        return $this->stmt->fetch($fetchMode);
    }

    public function fetchAll(int $fetchMode = null): array
    {
        $this->execute();
        return $this->stmt->fetchAll($fetchMode);
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
        if ($this->pdo === null) {
            return false;
        }
        try {
            $this->pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
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
