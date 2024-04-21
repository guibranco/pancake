<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests;

use GuiBranco\Pancake\GUIDv4;
use PHPUnit\Framework\TestCase;

final class GUIDv4Test extends TestCase
{
    public function testCanBeEmptyGUID(): void
    {
        $this->assertSame('00000000-0000-0000-0000-000000000000', GUIDv4::empty());
    }

    public function testCanBeRandomGUID(): void
    {
        $guid = GUIDv4::random();
        $this->assertMatchesRegularExpression('/[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}/', $guid);
    }
}
