<?php

namespace GuiBranco\Pancake;

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
     * Binds a value to a parameter
     *
     * @param int|string $param Parameter identifier
     * @param mixed $value The value to bind
     * @param int|null $type PDO parameter type (PDO::PARAM_*)
     * @return self For method chaining
     * @throws DatabaseException If the parameter is invalid
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
    public function fetch(int $fetchMode = null): mixed;

    /**
     * Fetches all rows from a result set
     *
     * @param int $fetchMode Optional fetch mode (PDO::FETCH_*)
     * @return array The result set rows
     */
    public function fetchAll(int $fetchMode = null): array;

    public function rowCount(): int;

    public function lastInsertId(): string;

    public function beginTransaction(): bool;

    public function commit(): bool;

    public function rollBack(): bool;

    public function close(): void;
}
