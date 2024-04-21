<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests;

use GuiBranco\Pancake\OneSignal;
use PHPUnit\Framework\TestCase;

final class OneSignalTest extends TestCase
{
    public function testCanCreateOneSignal(): void
    {
        $oneSignal = new OneSignal('token', null, null, "https://custom-endpoint");
        $this->assertInstanceOf(OneSignal::class, $oneSignal);
    }

    public function testCanCreateOneSignalWithCustomUserAgent(): void
    {
        $oneSignal = new OneSignal('token', null, "Custom User Agent");
        $this->assertInstanceOf(OneSignal::class, $oneSignal);
    }

    public function testCanCreateOneSignalWithCustomEndpoint(): void
    {
        $oneSignal = new OneSignal('token', null, null, "https://custom-endpoint");
        $this->assertInstanceOf(OneSignal::class, $oneSignal);
    }

    public function testCanSendNotification(): void
    {
        $oneSignal = new OneSignal('token', null, null, "https://custom-endpoint");
        $this->assertFalse($oneSignal->sendNotification([]));
    }
}
