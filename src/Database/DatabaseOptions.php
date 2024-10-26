<?php

namespace GuiBranco\Pancake\Database;

class DatabaseOptions
{
    public int $port;
    public string $charset;
    public string $collation;
    public int $timeout;
    public bool $autoCommit;

    /**
     * DatabaseOptions constructor.
     *
     * @param int $port        The database port (default: 3306).
     * @param string $charset  The character set for the connection (default: 'utf8mb4').
     * @param string $collation The collation for the connection (default: 'utf8mb4_unicode_ci').
     * @param int $timeout     The connection timeout in seconds (default: 5).
     * @param bool $autoCommit Whether to enable auto-commit mode (default: false).
     */
    public function __construct(
        int $port = 3306,
        string $charset = 'utf8mb4',
        string $collation = 'utf8mb4_unicode_ci',
        int $timeout = 5,
        bool $autoCommit = false
    ) {
        $this->port = $port;
        $this->charset = $charset;
        $this->collation = $collation;
        $this->timeout = $timeout;
        $this->autoCommit = $autoCommit;
    }
}