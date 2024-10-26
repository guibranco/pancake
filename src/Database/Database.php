<?php

namespace GuiBranco\Pancake\Database;

use PDO;
use PDOStatement;
use PDOException;

/**
 * Class Database
 * Provides a database abstraction layer using PDO for MySQL connections.
 */
class Database implements IDatabase
{
    private ?PDO $pdo;
    private ?PDOStatement $stmt = null;
    private bool $autoCommit;

    private const PDO_OPTIONS = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    private const NO_ACTIVE_CONNECTION = 'No active connection to the database';

    /**
     * Database constructor.
     * Initializes a connection to the MySQL database with provided credentials.
     *
     * @param string $host           The database host.
     * @param string $dbname         The database name.
     * @param string $username       The database username.
     * @param string $password       The database password.
     * @param DatabaseOptions $options Optional database options.
     *
     * @throws DatabaseException If connection fails or parameters are invalid.
     */
    public function __construct(
        string $host,
        string $dbname,
        string $username,
        string $password,
        DatabaseOptions $options = new DatabaseOptions()
    ) {
        if (trim($host) === '' || trim($dbname) === '' || trim($username) === '') {
            throw new DatabaseException('Host, database name, and username cannot be empty');
        }

        if ($options->port < 1 || $options->port > 65535) {
            throw new DatabaseException('Invalid port number');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            str_replace(';', '', $host),
            $options->port,
            str_replace(';', '', $dbname),
            str_replace(';', '', $options->charset)
        );
        $pdoOptions = self::PDO_OPTIONS + [
            PDO::ATTR_TIMEOUT => $options->timeout,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$options->charset} COLLATE {$options->collation}",
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ];

        $this->autoCommit = $options->autoCommit;

        try {
            $this->pdo = new PDO($dsn, $username, $password, $pdoOptions);
        } catch (PDOException $e) {
            throw new DatabaseException(
                sprintf(
                    'Failed to connect to MySQL server at %s:%d. Error: %s',
                    $host,
                    $options->port,
                    $e->getMessage()
                ),
                'connection',
                0,
                $e
            );
        }
    }

    /**
     * Prepares an SQL statement for execution
     *
     * @param string $query The SQL query to prepare
     * @return self For method chaining
     * @throws DatabaseException If the query is invalid
     */
    public function prepare(string $query): self
    {
        if (!$this->isConnected()) {
            throw new DatabaseException(self::NO_ACTIVE_CONNECTION);
        }
        $this->stmt = $this->pdo->prepare($query);
        return $this;
    }

    /**
     * Binds a parameter to the specified variable in the prepared statement.
     *
     * @param int|string $param The parameter identifier.
     * @param mixed $value The value to bind to the parameter.
     * @param int|null $type The data type for the parameter (optional).
     *
     * @return self Returns the current instance for method chaining.
     *
     * @throws DatabaseException If no statement is available.
     */
    public function bind(int|string $param, mixed $value, ?int $type = null): self
    {
        $type ??= match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            $value === null => PDO::PARAM_NULL,
            default => PDO::PARAM_STR
        };

        if ($this->stmt === null) {
            throw new DatabaseException('No prepared statement available for execution');
        }

        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }

    /**
    * Execute the query
    * @return bool if the query execution was succeeded or false if not
    * @throws DatabaseException If the parameter is invalid
    */
    public function execute(): bool
    {
        if ($this->stmt === null) {
            throw new DatabaseException('No prepared statement available for execution');
        }
        return $this->stmt->execute();
    }

    /**
     * Fetches the next row from a result set
     *
     * @param int $fetchMode Optional fetch mode (PDO::FETCH_*)
     * @return mixed The fetched row or false on failure
     */
    public function fetch(int $fetchMode = PDO::FETCH_BOTH): mixed
    {
        $this->execute();
        return $this->stmt->fetch(mode: $fetchMode);
    }

    /**
     * Fetches all rows from the result set.
     *
     * @param int|null $fetchMode The fetch mode (optional).
     * @return array The fetched rows as an array of associative arrays.
     */
    public function fetchAll(int $fetchMode = PDO::FETCH_BOTH): array
    {
        $this->execute();
        return $this->stmt->fetchAll($fetchMode);
    }

    /**
     * Gets the number of rows affected by the last SQL statement.
     *
     * @return int The row count.
     *
     * @throws DatabaseException If no statement is available.
     */
    public function rowCount(): int
    {
        if ($this->stmt === null) {
            throw new DatabaseException('No statement available to get row count.');
        }
        return $this->stmt->rowCount();
    }

    /**
     * Retrieves the ID of the last inserted row.
     *
     * @return string The last inserted ID.
     */
    public function lastInsertId(): string
    {
        if (!$this->isConnected()) {
            throw new DatabaseException(self::NO_ACTIVE_CONNECTION);
        }
        return $this->pdo->lastInsertId();
    }

    /**
     * Checks if the database connection is active.
     *
     * @return bool True if connected, false otherwise.
     */
    public function isConnected(): bool
    {
        if ($this->pdo === null) {
            return false;
        }
        try {
            return $this->pdo?->query('SELECT 1') !== false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Starts a new transaction.
     *
     * @return bool True on success, false on failure.
     *
     * @throws DatabaseException If there is no active connection or a transaction is already in progress.
     */
    public function beginTransaction(): bool
    {
        if (!$this->isConnected()) {
            throw new DatabaseException(self::NO_ACTIVE_CONNECTION);
        }
        if ($this->pdo->inTransaction()) {
            throw new DatabaseException('Transaction already in progress');
        }
        return $this->pdo->beginTransaction();
    }

    /**
     * Commits the current transaction.
     *
     * @return bool True on success, false on failure.
     *
     * @throws DatabaseException If there is no active transaction.
     */
    public function commit(): bool
    {
        if (!$this->isConnected() || !$this->pdo->inTransaction()) {
            throw new DatabaseException('No active transaction to commit');
        }
        return $this->pdo->commit();
    }

    /**
     * Rolls back the current transaction.
     *
     * @return bool True on success, false on failure.
     *
     * @throws DatabaseException If there is no active transaction.
     */
    public function rollBack(): bool
    {
        if (!$this->isConnected() || !$this->pdo->inTransaction()) {
            throw new DatabaseException('No active transaction to roll back');
        }
        return $this->pdo->rollBack();
    }

    /**
     * Closes the current database connection and statement.
     */
    public function close(): void
    {
        if ($this->isConnected() && $this->pdo->inTransaction() && !$this->autoCommit) {
            $this->rollBack();
        }

        if ($this->pdo->inTransaction() && $this->autoCommit) {
            $this->commit();
        }

        $this->stmt = null;
        $this->pdo = null;
    }
}
