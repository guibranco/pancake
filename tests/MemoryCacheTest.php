<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests;

use GuiBranco\Pancake\MemoryCache;
use PHPUnit\Framework\TestCase;

final class MemoryCacheTest extends TestCase
{
    public function testWriteAndReadJsonInMemory()
    {
        $memoryCache = new MemoryCache();
        $data = ['key' => 'value', 'number' => 123];
        $bytesWritten = $memoryCache->writeJsonInMemory($data);
        $this->assertGreaterThan(0, $bytesWritten, "Bytes written should be greater than 0");

        $readData = $memoryCache->readJsonInMemory();
        $this->assertEquals($data, $readData, "Data read from memory should match data written");
    }

    public function testWriteEmptyData()
    {
        $memoryCache = new MemoryCache();
        $data = [];
        $bytesWritten = $memoryCache->writeJsonInMemory($data);
        $this->assertGreaterThan(0, $bytesWritten, "Bytes written should be greater than 0 even for empty data");

        $readData = $memoryCache->readJsonInMemory();
        $this->assertEquals($data, $readData, "Data read from memory should match empty data written");
    }

    public function testOverwriteDataInMemory()
    {
        $memoryCache = new MemoryCache();
        $data1 = ['first' => 'data'];
        $data2 = ['second' => 'data'];

        $memoryCache->writeJsonInMemory($data1);
        $memoryCache->writeJsonInMemory($data2);

        $readData = $memoryCache->readJsonInMemory();
        $this->assertEquals($data2, $readData, "Data read from memory should match the last data written");
    }
}
