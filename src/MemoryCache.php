<?php

namespace GuiBranco\Pancake;

class MemoryCache implements MemoryCacheInterface
{
    private int $memorySize = 16 * 1024;

    public function openMemory(): \Shmop
    {
        $key = ftok(__FILE__, 't');
        return shmop_open($key, "c", 0600, $this->memorySize);
    }

    public function writeJsonInMemory(array $data): void
    {
        $memory = $this->openMemory();
        $serialized = json_encode($data);
        $raw = $this->strToNts($serialized);
        shmop_write($memory, $raw, 0);
    }

    public function readJsonInMemory(): array
    {
        $memory = $this->openMemory();
        $result = shmop_read($memory, 0, 0);
        $raw = rtrim($this->strFromMem($result));

        if (strlen($raw) > 0) {
            return json_decode($raw, true) ?? [];
        }

        return [];
    }

    private function strFromMem(string &$value): string
    {
        $index = strpos($value, "\0");
        if ($index === false) {
            return $value;
        }
        return substr($value, 0, $index);
    }

    private function strToNts(string $value): string
    {
        return "$value\0";
    }
}
