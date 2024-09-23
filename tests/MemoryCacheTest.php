<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests;

use GuiBranco\Pancake\MemoryCache;
use PHPUnit\Framework\TestCase;

final class MemoryCacheTest extends TestCase
{
    private $memoryCache;

    protected function setUp(): void
    {
        $this->memoryCache = new MemoryCache();
    }

    public function testWriteAndReadJsonInMemory()
    {
        $data = ['key' => 'value', 'number' => 123];
        $bytesWritten = $this->memoryCache->writeJsonInMemory($data);
        $this->assertGreaterThan(0, $bytesWritten, "Bytes written should be greater than 0");

        $readData = $this->memoryCache->readJsonInMemory();
        $this->assertEquals($data, $readData, "Data read from memory should match data written");
    }

    public function testReadEmptyMemory()
    {
        $readData = $this->memoryCache->readJsonInMemory();
        $this->assertNull($readData, "Reading from empty memory should return null");
    }

    public function testWriteEmptyData()
    {
        $data = [];
        $bytesWritten = $this->memoryCache->writeJsonInMemory($data);
        $this->assertGreaterThan(0, $bytesWritten, "Bytes written should be greater than 0 even for empty data");

        $readData = $this->memoryCache->readJsonInMemory();
        $this->assertEquals($data, $readData, "Data read from memory should match empty data written");
    }

    public function testOverwriteDataInMemory()
    {
        $data1 = ['first' => 'data'];
        $data2 = ['second' => 'data'];

        $this->memoryCache->writeJsonInMemory($data1);
        $this->memoryCache->writeJsonInMemory($data2);

        $readData = $this->memoryCache->readJsonInMemory();
        $this->assertEquals($data2, $readData, "Data read from memory should match the last data written");
    }
}
