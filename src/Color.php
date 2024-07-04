<?php

namespace GuiBranco\Pancake;

use InvalidArgumentException;

class Color
{
    private string $red;
    private string $green;
    private string $blue;
    private string $hexColor;

    /**
     * Constructor.
     *
     * @param string $red      The red value.
     * @param string $green    The green value.
     * @param string $blue     The blue value.
     * @param string $hexColor The hex color.
     */
    public function __construct(string $red = "", string $green = "", string $blue = "", string $hexColor = "")
    {
        if (!empty($red)) {
            $this->setRed($red);
        }
        if (!empty($green)) {
            $this->setGreen($green);
        }
        if (!empty($blue)) {
            $this->setBlue($blue);
        }
        if (!empty($hexColor)) {
            $this->setHexColor($hexColor);
        }
    }

    /**
     * Set the red value.
     *
     * @param string $hexColor The hex color.
     */
    public function setRed(string $red): void
    {
        self::validateColor($red);
        $this->red = $red;
    }

    public function getRed(): string
    {
        return $this->red;
    }

    /**
     * Set the green value.
     *
     * @param string $green The green value.
     */
    public function setGreen(string $green): void
    {
        self::validateColor($green);
        $this->green = $green;
    }

    public function getGreen(): string
    {
        return $this->green;
    }

    /**
     * Set the blue value.
     *
     * @param string $blue The blue value.
     */
    public function setBlue(string $blue): void
    {
        self::validateColor($blue);
        $this->blue = $blue;
    }

    public function getBlue(): string
    {
        return $this->blue;
    }

    /**
     * Set the hex color.
     *
     * @param string $hexColor The hex color.
     */
    public function setHexColor(string $hexColor): void
    {
        self::validateHexColor($hexColor);
        $this->hexColor = $hexColor;
    }

    public function getHexColor(): string
    {
        return $this->hexColor;
    }

    /**
     * Validate a color.
     *
     * @param  string $color The color to validate.
     * @return void
     * @throws InvalidArgumentException
     */
    private static function validateColor(string $color): void
    {
        if (!is_numeric($color) || $color < 0 || $color > 255) {
            throw new InvalidArgumentException("$color is not a valid color");
        }
    }

    /**
     * Validate a hex color.
     *
     * @param  string $hexColor The hex color to validate.
     * @return void
     * @throws InvalidArgumentException
     */
    private static function validateHexColor(string $hexColor): void
    {
        if (!preg_match("/^#[a-f0-9]{6}$/i", $hexColor)) {
            throw new InvalidArgumentException("$hexColor is not a valid hex color");
        }
    }

    /**
     * Calculate the luminance of a color.
     *
     * @return float      0-1
     */
    public function luminanceRGB(): float
    {
        return ($this->red * 0.2126 + $this->green * 0.7152 + $this->blue * 0.0722) / 255;
    }


    /**
     * Convert a hex color to RGB.
     *
     * @return array{int,int,int} [186, 218, 85]
     */
    public function hexToRGB(): array
    {
        return sscanf($this->hexColor, "#%02x%02x%02x");
    }

    /**
     * Generate a color from a text.
     *
     * @param  string $text          The text to generate the color from.
     * @param  int    $minBrightness The minimum brightness of the color.
     * @param  int    $spec          The spec of the color.
     * @return Color                 The generated color.
     * @throws InvalidArgumentException
     */
    public function generateColorFromText(string $text, int $minBrightness = 100, int $spec = 10): Color
    {
        if ($spec < 2 || $spec > 10) {
            throw new InvalidArgumentException("$spec is out of range");
        }
        if ($minBrightness < 0 || $minBrightness > 255) {
            throw new InvalidArgumentException("$minBrightness is out of range");
        }

        $hash = md5($text);
        $colors = array();
        for ($i = 0; $i < 3; $i++) {
            $current = round(((hexdec(substr($hash, $spec * $i, $spec))) / hexdec(str_pad("", $spec, "F"))) * 255);
            $colors[$i] = max(array($current, $minBrightness));
        }

        if ($minBrightness > 0) {
            while (array_sum($colors) / 3 < $minBrightness) {
                for ($i = 0; $i < 3; $i++) {
                    $colors[$i] += 10;
                }
            }
        }

        $output = "";

        for ($i = 0; $i < 3; $i++) {
            $output .= str_pad(dechex($colors[$i]), 2, 0, STR_PAD_LEFT);
        }

        return new Color(
            hexColor: "#" . $output
        );
    }


}
