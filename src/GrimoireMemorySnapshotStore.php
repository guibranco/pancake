<?php

declare(strict_types=1);

namespace GuiBranco\Pancake;

/**
 * Keeps the snapshot in this process only: right for long-running processes (workers,
 * ReactPHP), and the fallback when nothing shared is available.
 */
final class GrimoireMemorySnapshotStore implements GrimoireSnapshotStore
{
    /** @var array<string, array<string, mixed>> */
    private array $snapshots = [];

    /** @var array<string, int> lock expiry per key */
    private array $locks = [];

    public function __construct(private readonly ?\Closure $clock = null)
    {
    }

    public function get(string $key): ?array
    {
        return $this->snapshots[$key] ?? null;
    }

    public function set(string $key, array $snapshot): void
    {
        $this->snapshots[$key] = $snapshot;
    }

    public function delete(string $key): void
    {
        unset($this->snapshots[$key]);
    }

    public function lock(string $key, int $seconds): bool
    {
        $now = $this->clock !== null ? ($this->clock)() : time();
        if (($this->locks[$key] ?? 0) > $now) {
            return false;
        }
        $this->locks[$key] = $now + $seconds;

        return true;
    }

    public function unlock(string $key): void
    {
        unset($this->locks[$key]);
    }
}
