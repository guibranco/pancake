<?php

declare(strict_types=1);

namespace GuiBranco\Pancake;

/**
 * Shares the snapshot between the PHP-FPM workers of one pool through APCu. Needs the apcu
 * extension (and `apc.enable_cli=1` to work from the CLI).
 */
final class GrimoireApcuSnapshotStore implements GrimoireSnapshotStore
{
    /** Snapshots are dropped after a day unused, so a removed application doesn't linger. */
    private const TTL = 86400;

    public static function isAvailable(): bool
    {
        return function_exists('apcu_enabled') && apcu_enabled();
    }

    public function __construct()
    {
        if (!self::isAvailable()) {
            throw new \RuntimeException('APCu is not available (install the apcu extension and enable it).');
        }
    }

    public function get(string $key): ?array
    {
        $value = apcu_fetch($key, $found);

        return $found && is_array($value) ? $value : null;
    }

    public function set(string $key, array $snapshot): void
    {
        apcu_store($key, $snapshot, self::TTL);
    }

    public function delete(string $key): void
    {
        apcu_delete($key);
    }

    public function lock(string $key, int $seconds): bool
    {
        return apcu_add($key . ':lock', 1, max(1, $seconds));
    }

    public function unlock(string $key): void
    {
        apcu_delete($key . ':lock');
    }
}
