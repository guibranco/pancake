<?php

namespace GuiBranco\Pancake;

/**
 * Interface MemoryCacheInterface
 *
 * Defines the minimal contract required by {@see CircuitBreaker} (and any other
 * consumer) to persist and retrieve JSON-serialisable state in memory.
 *
 * The concrete {@see MemoryCache} class implements this interface. Test doubles
 * and alternative implementations (e.g. APCu-backed, Redis-backed) must also
 * implement it to be accepted by the circuit breaker constructor.
 *
 * @package GuiBranco\Pancake
 */
interface MemoryCacheInterface
{
    /**
     * Reads the previously written JSON payload and returns it as an associative array.
     *
     * Returns an empty array when no data has been written yet.
     *
     * @return array<string, mixed>
     */
    public function readJsonInMemory(): array;

    /**
     * Serialises $data as JSON and writes it to the in-memory store,
     * replacing any previously stored value.
     *
     * @param array<string, mixed> $data
     * @return void
     */
    public function writeJsonInMemory(array $data): void;
}
