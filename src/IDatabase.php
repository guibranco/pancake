<?php

namespace GuiBranco\Pancake;

interface IDatabase
{
    public function prepare(string $query): void;

    public function bind(string $param, $value, $type = null): void;

    public function execute(): bool;

    public function fetch();

    public function fetchAll();

    public function rowCount(): int;

    public function lastInsertId(): string;

    public function beginTransaction(): bool;

    public function commit(): bool;

    public function rollBack(): bool;

    public function close(): void;
}
