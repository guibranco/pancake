<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit;

use GuiBranco\Pancake\Color;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Throwable;

final class ColorTest extends TestCase
{
    public static function colorRGBProvider(): array
    {
        return [
            [["red" => 255, "green" => 87, "blue" => 51], 0.4710494117647058],
            [["red" => 0, "green" => 0, "blue" => 0], 0],
            [["red" => 255, "green" => 255, "blue" => 255], 0.9999999999999999],
            [["red" => 255, "green" => 255, "blue" => 0], 0.9277999999999998],
            [["red" => 0, "green" => 255, "blue" => 0], 0.7152],
            [["red" => 0, "green" => 0, "blue" => 255], 0.0722],
        ];
    }

    public static function hexProvider(): array
    {
        return [
            ["#BADA55", [186, 218, 85]],
            ["#000000", [0, 0, 0]],
            ["#FFFFFF", [255, 255, 255]],
            ["#FFFF00", [255, 255, 0]],
            ["#00FF00", [0, 255, 0]],
            ["#0000FF", [0, 0, 255]],
        ];
    }

    public static function invalidColorTextProvider(): array
    {
        return [
            [["text" => "blue", "minBrightness" => 256, "spec" => 5]], // Invalid maxBrightness
            [["text" => "green", "minBrightness" => -10, "spec" => 7]], // Invalid minBrightness
            [["text" => "yellow", "minBrightness" => 150, "spec" => 1]], // Invalid spec
            [["text" => "orange", "minBrightness" => 120, "spec" => 11]], // Invalid spec
        ];
    }

    public static function validColorTextProvider(): array
    {
        return [
            [["text" => "red", "minBrightness" => 100, "spec" => 10], "#bd6464"],
            [["text" => "purple", "minBrightness" => 100, "spec" => 5], "#bbdf64"],
            [["text" => "pink", "minBrightness" => 150, "spec" => 8], "#96dc96"],
            [["text" => "brown", "minBrightness" => 80, "spec" => 3], "#7050f9"],
            [["text" => "gray", "minBrightness" => 200, "spec" => 6], "#cdc8c8"],
        ];
    }

    #[DataProvider('colorRGBProvider')]
    public function testLuminance($rgbColor, $expectedLuminance): void
    {
        $color = new Color();
        $color->setRed((string) $rgbColor["red"]);
        $color->setGreen((string) $rgbColor["green"]);
        $color->setBlue((string) $rgbColor["blue"]);

        $this->assertEquals($expectedLuminance, $color->luminanceRGB());
    }


    #[DataProvider('hexProvider')]
    public function testHexProvider($hexColor, $expectedRGB): void
    {
        $color = new Color();
        $color->setHexColor($hexColor);

        $this->assertEquals($expectedRGB, $color->hexToRGB());
    }

    #[DataProvider('validColorTextProvider')]
    public function testGenerateColorFromTextWithValidProvider($textColor, $expectedHexColor): void
    {
        $color = new Color();

        $this->assertEquals($expectedHexColor, $color->generateColorFromText(
            text: $textColor["text"],
            minBrightness: $textColor["minBrightness"],
            spec: $textColor["spec"],
        )->getHexColor());
    }

    #[DataProvider("invalidColorTextProvider")]
    public function testGenerateColorFromTextWithInvalidProvider($textColor): void
    {
        $this->expectException(InvalidArgumentException::class);

        $color = new Color();
        $color->generateColorFromText(
            text: $textColor["text"],
            minBrightness: $textColor["minBrightness"],
            spec: $textColor["spec"],
        );
    }

    public function testValidateColorWithValidColor(): void
    {
        $color = new Color();
        $this->expectNotToPerformAssertions();

        try {
            $reflectionMethod = new ReflectionMethod(Color::class, 'validateColor');
            $reflectionMethod->setAccessible(true);
            $reflectionMethod->invoke($color, '100');
        } catch (Throwable $e) {
            $this->fail('Exception should not be thrown');
        }
    }

    public function testValidateColorWithInvalidColor(): void
    {
        $color = new Color();
        $this->expectException(InvalidArgumentException::class);

        $reflectionMethod = new ReflectionMethod(Color::class, 'validateColor');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($color, '-10');
    }

    public function testValidateHexColorWithValidHexColor(): void
    {
        $color = new Color();
        $this->expectNotToPerformAssertions();

        try {
            $reflectionMethod = new ReflectionMethod(Color::class, 'validateHexColor');
            $reflectionMethod->setAccessible(true);
            $reflectionMethod->invoke($color, '#FF0000');
        } catch (Throwable $e) {
            $this->fail('Exception should not be thrown');
        }
    }

    public function testValidateHexColorWithInvalidHexColor(): void
    {
        $color = new Color();
        $this->expectException(InvalidArgumentException::class);

        $reflectionMethod = new ReflectionMethod(Color::class, 'validateHexColor');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($color, '#ZZZZZZ');
    }

}
