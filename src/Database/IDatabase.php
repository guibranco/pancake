<?php

namespace GuiBranco\Pancake\Database;

interface IDatabase
{
    /**
     * Prepares an SQL statement for execution
     *
     * @param string $query The SQL query to prepare
     * @return self For method chaining
     * @throws DatabaseException If the query is invalid
     */
    public function prepare(string $query): self;

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
    public function bind(int|string $param, mixed $value, ?int $type = null): self;

    /**
    * Execute the query
    * @return bool if the query execution was succeeded or false if not
    * @throws DatabaseException If the parameter is invalid
    */
    public function execute(): bool;

    /**
     * Fetches the next row from a result set
     *
     * @param int $fetchMode Optional fetch mode (PDO::FETCH_*)
     * @return mixed The fetched row or false on failure
     */
    public function fetch(int $fetchMode = PDO::FETCH_BOTH): mixed;

    /**
     * Fetches all rows from the result set.
     *
     * @param int|null $fetchMode The fetch mode (optional).
     * @return array The fetched rows as an array of associative arrays.
     */
    public function fetchAll(int $fetchMode = PDO::FETCH_BOTH): array;

    /**
     * Gets the number of rows affected by the last SQL statement.
     *
     * @return int The row count.
     *
     * @throws DatabaseException If no statement is available.
     */
    public function rowCount(): int;

    /**
     * Retrieves the ID of the last inserted row.
     *
     * @return string The last inserted ID.
     */
    public function lastInsertId(): string;

    /**
     * Checks if the database connection is active.
     *
     * @return bool True if connected, false otherwise.
     */
    public function isCOnnected(): bool;

    /**
     * Starts a new transaction.
     *
     * @return bool True on success, false on failure.
     *
     * @throws DatabaseException If there is no active connection or a transaction is already in progress.
     */
    public function beginTransaction(): bool;

    /**
     * Commits the current transaction.
     *
     * @return bool True on success, false on failure.
     *
     * @throws DatabaseException If there is no active transaction.
     */
    public function commit(): bool;

    /**
     * Rolls back the current transaction.
     *
     * @return bool True on success, false on failure.
     *
     * @throws DatabaseException If there is no active transaction.
     */
    public function rollBack(): bool;

    /**
     * Closes the current database connection and statement.
     */
    public function close(): void;
}
