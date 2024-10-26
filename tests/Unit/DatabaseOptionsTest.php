<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit;

use PHPUnit\Framework\TestCase;
use GuiBranco\Pancake\Database\DatabaseOptions;

class DatabaseOptionsTest extends TestCase
{
    public function testDefaultValuesAreSetCorrectly()
    {
        $options = new DatabaseOptions();

        $this->assertSame(3306, $options->port, 'Default port should be 3306');
        $this->assertSame('utf8mb4', $options->charset, 'Default charset should be utf8mb4');
        $this->assertSame('utf8mb4_unicode_ci', $options->collation, 'Default collation should be utf8mb4_unicode_ci');
        $this->assertSame(5, $options->timeout, 'Default timeout should be 5 seconds');
        $this->assertFalse($options->autoCommit, 'Default autoCommit should be false');
    }

    public function testCustomPortIsSetCorrectly()
    {
        $options = new DatabaseOptions(port: 5432);
        
        $this->assertSame(5432, $options->port, 'Custom port should be set correctly');
    }

    public function testCustomCharsetIsSetCorrectly()
    {
        $options = new DatabaseOptions(charset: 'latin1');
        
        $this->assertSame('latin1', $options->charset, 'Custom charset should be set correctly');
    }

    public function testCustomCollationIsSetCorrectly()
    {
        $options = new DatabaseOptions(collation: 'latin1_swedish_ci');
        
        $this->assertSame('latin1_swedish_ci', $options->collation, 'Custom collation should be set correctly');
    }

    public function testCustomTimeoutIsSetCorrectly()
    {
        $options = new DatabaseOptions(timeout: 10);
        
        $this->assertSame(10, $options->timeout, 'Custom timeout should be set correctly');
    }

    public function testCustomAutoCommitIsSetCorrectly()
    {
        $options = new DatabaseOptions(autoCommit: true);
        
        $this->assertTrue($options->autoCommit, 'Custom autoCommit should be set correctly');
    }
}
