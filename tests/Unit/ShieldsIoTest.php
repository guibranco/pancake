<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit;

use GuiBranco\Pancake\ShieldsIo;
use PHPUnit\Framework\TestCase;

final class ShieldsIoTest extends TestCase
{
    public function testCanGenerateBadgeUrl(): void
    {
        $shieldsIo = new ShieldsIo();
        $badgeUrl = $shieldsIo->generateBadgeUrl('label', 'content', 'color', 'style', 'labelColor', 'logo');
        $this->assertEquals('https://img.shields.io/badge/label-content-color?style=style&labelColor=labelColor&logo=logo&cacheSeconds=3600', $badgeUrl);
    }

    public function testCanGenerateBadgeUrlWithCustomCacheSeconds(): void
    {
        $shieldsIo = new ShieldsIo(7200);
        $badgeUrl = $shieldsIo->generateBadgeUrl('label', 'content', 'color', 'style', 'labelColor', 'logo');
        $this->assertEquals('https://img.shields.io/badge/label-content-color?style=style&labelColor=labelColor&logo=logo&cacheSeconds=7200', $badgeUrl);
    }

    public function testCanGenerateBadgeUrlWithoutLabel(): void
    {
        $shieldsIo = new ShieldsIo();
        $badgeUrl = $shieldsIo->generateBadgeUrl('', 'content', 'color', 'style', 'labelColor', 'logo');
        $this->assertEquals('https://img.shields.io/badge/content-color?style=style&labelColor=labelColor&logo=logo&cacheSeconds=3600', $badgeUrl);
    }

    public function testCanGenerateBadgeUrlWithoutContent(): void
    {
        $shieldsIo = new ShieldsIo();
        $badgeUrl = $shieldsIo->generateBadgeUrl('label', '', 'color', 'style', 'labelColor', 'logo');
        $this->assertEquals('https://img.shields.io/badge/label-color?style=style&labelColor=labelColor&logo=logo&cacheSeconds=3600', $badgeUrl);
    }

    public function testCanGenerateBadgeUrlWithoutColor(): void
    {
        $shieldsIo = new ShieldsIo();
        $badgeUrl = $shieldsIo->generateBadgeUrl('label', 'content', '', 'style', 'labelColor', 'logo');
        $this->assertEquals('https://img.shields.io/badge/label-content?style=style&labelColor=labelColor&logo=logo&cacheSeconds=3600', $badgeUrl);
    }

    public function testCanGenerateBadgeUrlWithoutStyle(): void
    {
        $shieldsIo = new ShieldsIo();
        $badgeUrl = $shieldsIo->generateBadgeUrl('label', 'content', 'color', '', 'labelColor', 'logo');
        $this->assertEquals('https://img.shields.io/badge/label-content-color?labelColor=labelColor&logo=logo&cacheSeconds=3600', $badgeUrl);
    }

    public function testCanGenerateBadgeUrlWithoutLabelColor(): void
    {
        $shieldsIo = new ShieldsIo();
        $badgeUrl = $shieldsIo->generateBadgeUrl('label', 'content', 'color', 'style', '', 'logo');
        $this->assertEquals('https://img.shields.io/badge/label-content-color?style=style&logo=logo&cacheSeconds=3600', $badgeUrl);
    }

    public function testCanGenerateBadgeUrlWithoutLogo(): void
    {
        $shieldsIo = new ShieldsIo();
        $badgeUrl = $shieldsIo->generateBadgeUrl('label', 'content', 'color', 'style', 'labelColor', '');
        $this->assertEquals('https://img.shields.io/badge/label-content-color?style=style&labelColor=labelColor&cacheSeconds=3600', $badgeUrl);
    }

    public function testCanGenerateBadgeUrlWithoutLabelAndContent(): void
    {
        $shieldsIo = new ShieldsIo();
        $badgeUrl = $shieldsIo->generateBadgeUrl('', '', 'color', 'style', 'labelColor', 'logo');
        $this->assertEquals('https://img.shields.io/badge/color?style=style&labelColor=labelColor&logo=logo&cacheSeconds=3600', $badgeUrl);
    }

    public function testCanGenerateBadgeUrlWithoutColorAndStyle(): void
    {
        $shieldsIo = new ShieldsIo();
        $badgeUrl = $shieldsIo->generateBadgeUrl('label', 'content', '', '', 'labelColor', 'logo');
        $this->assertEquals('https://img.shields.io/badge/label-content?labelColor=labelColor&logo=logo&cacheSeconds=3600', $badgeUrl);
    }

    public function testCanGenerateBadgeUrlWithoutLabelColorAndLogo(): void
    {
        $shieldsIo = new ShieldsIo();
        $badgeUrl = $shieldsIo->generateBadgeUrl('label', 'content', 'color', 'style', '', '');
        $this->assertEquals('https://img.shields.io/badge/label-content-color?style=style&cacheSeconds=3600', $badgeUrl);
    }
}
