<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit\Queue;

use GuiBranco\Pancake\Queue\QueueOptions;
use PHPUnit\Framework\TestCase;

class QueueOptionsTest extends TestCase
{
    public function testDefaultValuesAreSetCorrectly(): void
    {
        $options = new QueueOptions();

        $this->assertSame(10.0, $options->connectionTimeout, 'Default connectionTimeout should be 10.0');
        $this->assertSame(10.0, $options->readWriteTimeout, 'Default readWriteTimeout should be 10.0');
        $this->assertSame(3_600_000, $options->dlxRetryTtlMs, 'Default dlxRetryTtlMs should be 3600000');
        $this->assertSame(5, $options->maxRetries, 'Default maxRetries should be 5');
        $this->assertSame(1000, $options->initialRetryDelayMs, 'Default initialRetryDelayMs should be 1000');
        $this->assertSame(2.0, $options->retryMultiplier, 'Default retryMultiplier should be 2.0');
        $this->assertSame('-retry', $options->retryQueueSuffix, 'Default retryQueueSuffix should be -retry');
        $this->assertSame('-failed', $options->deadQueueSuffix, 'Default deadQueueSuffix should be -failed');
    }

    public function testCustomConnectionTimeoutIsSetCorrectly(): void
    {
        $options = new QueueOptions(connectionTimeout: 5.5);

        $this->assertSame(5.5, $options->connectionTimeout, 'Custom connectionTimeout should be set correctly');
    }

    public function testCustomReadWriteTimeoutIsSetCorrectly(): void
    {
        $options = new QueueOptions(readWriteTimeout: 20.0);

        $this->assertSame(20.0, $options->readWriteTimeout, 'Custom readWriteTimeout should be set correctly');
    }

    public function testCustomDlxRetryTtlMsIsSetCorrectly(): void
    {
        $options = new QueueOptions(dlxRetryTtlMs: 60_000);

        $this->assertSame(60_000, $options->dlxRetryTtlMs, 'Custom dlxRetryTtlMs should be set correctly');
    }

    public function testCustomMaxRetriesIsSetCorrectly(): void
    {
        $options = new QueueOptions(maxRetries: 3);

        $this->assertSame(3, $options->maxRetries, 'Custom maxRetries should be set correctly');
    }

    public function testCustomInitialRetryDelayMsIsSetCorrectly(): void
    {
        $options = new QueueOptions(initialRetryDelayMs: 250);

        $this->assertSame(250, $options->initialRetryDelayMs, 'Custom initialRetryDelayMs should be set correctly');
    }

    public function testCustomRetryMultiplierIsSetCorrectly(): void
    {
        $options = new QueueOptions(retryMultiplier: 1.5);

        $this->assertSame(1.5, $options->retryMultiplier, 'Custom retryMultiplier should be set correctly');
    }

    public function testCustomRetryQueueSuffixIsSetCorrectly(): void
    {
        $options = new QueueOptions(retryQueueSuffix: '-attempt');

        $this->assertSame('-attempt', $options->retryQueueSuffix, 'Custom retryQueueSuffix should be set correctly');
    }

    public function testCustomDeadQueueSuffixIsSetCorrectly(): void
    {
        $options = new QueueOptions(deadQueueSuffix: '-dead');

        $this->assertSame('-dead', $options->deadQueueSuffix, 'Custom deadQueueSuffix should be set correctly');
    }
}
