# Memory Cache

The `MemoryCache` class provides a mechanism for caching data in shared memory using PHP's `shmop` functions. This is useful for performance optimization by reducing the need to repeatedly fetch data from slower storage mediums.

## Methods

### openMemory()
Opens a shared memory block and returns the memory identifier.

### writeJsonInMemory($data)
Writes JSON-encoded data to the shared memory.

### readJsonInMemory()
Reads data from the shared memory and decodes it from JSON.

### strToNts($value)
Converts a string to a null-terminated string for storage in memory.

### strFromMem(&$value)
Retrieves a string from memory, stopping at the null terminator.

## Usage

```php
$cache = new MemoryCache();
$data = ['key' => 'value'];
$cache->writeJsonInMemory($data);
$retrievedData = $cache->readJsonInMemory();
```

## Testing
Unit tests for the `MemoryCache` class are available in the `tests/MemoryCacheTest.php` file.
