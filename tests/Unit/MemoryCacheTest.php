<?php

namespace GuiBranco\Pancake\Tests\Unit;

use GuiBranco\Pancake\MemoryCache;
use PHPUnit\Framework\TestCase;

class MemoryCacheTest extends TestCase
{
    private MemoryCache $cache;

    protected function setUp(): void
    {
        $this->cache = new MemoryCache();
    }

    public function testWriteAndReadJsonInMemory(): void
    {
        $data = ['key' => 'value', 'number' => 42];

        $this->cache->writeJsonInMemory($data);  // void — no return to assert

        $result = $this->cache->readJsonInMemory();

        $this->assertSame($data, $result);
    }

    public function testWriteEmptyData(): void
    {
        $this->cache->writeJsonInMemory([]);

        $result = $this->cache->readJsonInMemory();

        $this->assertSame([], $result);
    }

    public function testOverwriteReplacesExistingData(): void
    {
        $this->cache->writeJsonInMemory(['first' => true]);
        $this->cache->writeJsonInMemory(['second' => true]);

        $result = $this->cache->readJsonInMemory();

        $this->assertArrayHasKey('second', $result);
        $this->assertArrayNotHasKey('first', $result);
    }

    public function testReadOnFreshMemoryReturnsEmptyArray(): void
    {
        // Write empty state so shared memory is initialised but blank
        $this->cache->writeJsonInMemory([]);

        $result = $this->cache->readJsonInMemory();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
