<?php

declare(strict_types=1);

namespace GuiBranco\Pancake;

/**
 * Where {@see Grimoire} keeps its configuration snapshot between requests when sentinel
 * refresh is on, so PHP-FPM workers share one copy instead of calling Grimoire on every
 * request.
 *
 * Only configuration values and sentinel state are stored here. Secrets never leave the
 * process: anything in the same PHP-FPM pool can read APCu, and a file store writes to disk.
 */
interface GrimoireSnapshotStore
{
    /** @return array<string, mixed>|null The stored snapshot, or null when there is none. */
    public function get(string $key): ?array;

    /** @param array<string, mixed> $snapshot */
    public function set(string $key, array $snapshot): void;

    public function delete(string $key): void;

    /**
     * Take the refresh lock for $key, held for at most $seconds. Returns false when another
     * worker holds it; that worker refreshes and the caller keeps serving the old snapshot.
     */
    public function lock(string $key, int $seconds): bool;

    public function unlock(string $key): void;
}
