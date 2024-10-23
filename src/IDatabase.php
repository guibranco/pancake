<?php

namespace GuiBranco\Pancake;

interface IDatabase
{
    public function prepare(string $query): void;

    public function bind(string $param, $value, $type = null): void;

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
