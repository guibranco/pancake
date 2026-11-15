<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit;

use GuiBranco\Pancake\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RequestTimeoutTest extends TestCase
{
    #[Test]
    public function timeoutDefaultsToNoLimit(): void
    {
        $this->assertSame(0, (new Request())->getTimeout());
    }

    #[Test]
    public function timeoutCanBeSet(): void
    {
        $request = new Request();
        $request->setTimeout(15);

        $this->assertSame(15, $request->getTimeout());
    }

    #[Test]
    public function negativeTimeoutIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Request())->setTimeout(-1);
    }
}
