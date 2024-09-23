<?php

namespace GuiBranco\Pancake;

class MemoryCache
{
    private $memorySize = 16 * 1024;

    public function openMemory()
    {
        $key = ftok(__FILE__, 't');
        return shmop_open($key, "c", 0600, $this->memorySize);
    }

    public function writeJsonInMemory($data)
    {
        $memory = $this->openMemory();
        $serialized = json_encode($data);
        $raw = $this->strToNts($serialized);
        $bytes = shmop_write($memory, $raw, 0);
        return $bytes;
    }

    public function readJsonInMemory()
    {
        $memory = $this->openMemory();
        $result = shmop_read($memory, 0, 0);
        $raw = rtrim($this->strFromMem($result));
        if (strlen($raw) > 0 && $raw != null) {
            return json_decode($raw, true);
        }
        return null;
    }

    private function strFromMem(&$value)
    {
        $index = strpos($value, "\0");
        if ($index === false) {
            return $value;
        }
        $result = substr($value, 0, $index);
        return $result;
    }

    private function strToNts($value)
    {
        return "$value\0";
    }
}
