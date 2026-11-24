<?php

declare(strict_types=1);

namespace GuiBranco\Pancake;

/**
 * Shares the snapshot between requests through files (0600, written atomically), for hosts
 * without APCu. Only configuration values are written, never secrets.
 */
final class GrimoireFileSnapshotStore implements GrimoireSnapshotStore
{
    private readonly string $directory;

    public function __construct(?string $directory = null, private readonly ?\Closure $clock = null)
    {
        $directory ??= sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pancake-grimoire';
        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException("Could not create the Grimoire cache directory '{$directory}'.");
        }
        $this->directory = rtrim($directory, '/\\');
    }

    public function get(string $key): ?array
    {
        $path = $this->path($key);
        if (!is_file($path)) {
            return null;
        }
        $data = json_decode((string) @file_get_contents($path), true);

        return is_array($data) ? $data : null;
    }

    public function set(string $key, array $snapshot): void
    {
        $path = $this->path($key);
        $temp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($temp, json_encode($snapshot, JSON_THROW_ON_ERROR), LOCK_EX) === false) {
            return; // A cache that can't be written is just a cache miss next time.
        }
        @chmod($temp, 0600);
        if (!@rename($temp, $path)) {
            @unlink($temp);
        }
    }

    public function delete(string $key): void
    {
        @unlink($this->path($key));
    }

    public function lock(string $key, int $seconds): bool
    {
        $lock = $this->path($key) . '.lock';
        $handle = @fopen($lock, 'x');
        if ($handle === false) {
            // Someone holds it; take over a lock left behind by a crashed worker.
            $mtime = @filemtime($lock);
            if ($mtime === false || $mtime + max(1, $seconds) > $this->now()) {
                return false;
            }
            @unlink($lock);
            $handle = @fopen($lock, 'x');
            if ($handle === false) {
                return false;
            }
        }
        fclose($handle);

        return true;
    }

    public function unlock(string $key): void
    {
        @unlink($this->path($key) . '.lock');
    }

    private function path(string $key): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.json';
    }

    private function now(): int
    {
        return $this->clock !== null ? ($this->clock)() : time();
    }
}
