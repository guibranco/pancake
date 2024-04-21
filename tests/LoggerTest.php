<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests;

use GuiBranco\Pancake\Logger;
use PHPUnit\Framework\TestCase;

final class LoggerTest extends TestCase
{
    public function testCanCreateLogger(): void
    {
        $logger = new Logger('http://localhost:8080/', 'api-key', 'api-token');
        $this->assertInstanceOf(Logger::class, $logger);
    }

    public function testCanCreateLoggerWithCustomUserAgent(): void
    {
        $logger = new Logger('http://localhost:8080/', 'api-key', 'api-token', "Custom User Agent");
        $this->assertInstanceOf(Logger::class, $logger);
    }

    public function testCanLog(): void
    {
        $logger = new Logger('http://localhost:8080/', 'api-key', 'api-token');
        $logger->log('test', 'test');
    }
}
