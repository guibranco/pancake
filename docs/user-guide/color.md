# Color

## Table of content

- [Color](#color)
  - [Table of content](#table-of-content)
  - [About](#about)
  - [Requirements](#requirements)
  - [Avaiable methods](#avaiable-methods)
  - [Constructor](#constructor)
  - [Setters and Getters](#setters-and-getters)
  - [Color Validation](#color-validation)
  - [Luminance Calculation](#luminance-calculation)
  - [Hex to RGB Conversion](#hex-to-rgb-conversion)
  - [Color Generation from Text](#color-generation-from-text)


## About

Utility for managing and manipulating color values in various formats, including RGB and hexadecimal. It provides functionalities for setting and getting individual color components (red, green, blue), validating color values, calculating luminance, converting between hex and RGB, and generating colors from text strings.

## Requirements

None.

## Avaiable methods

## Constructor

Allows initialization of a Color object with RGB values and a hexadecimal color code.

```php
use GuiBranco\Pancake\Color;

// Create a new Color object
$color = new Color('255', '0', '0', '#FF0000'); // Red color
```

## Setters and Getters

Methods for setting and retrieving the red, green, blue, and hex color values, with validation to ensure they are within the appropriate range.

```php
$color->setRed('128');
$color->setGreen('128');
$color->setBlue('0');

echo $color->getRed();   // Outputs: 128
echo $color->getGreen(); // Outputs: 128
echo $color->getBlue();  // Outputs: 0
```

## Color Validation

Ensures that RGB values are between 0 and 255 and that hexadecimal color codes are valid.

```php
try {
    $color->setRed('300'); // Invalid, throws InvalidArgumentException
} catch (InvalidArgumentException $e) {
    echo $e->getMessage(); // Outputs: 300 is not a valid color
}

try {
    $color->setHexColor('#ZZZZZZ'); // Invalid, throws InvalidArgumentException
} catch (InvalidArgumentException $e) {
    echo $e->getMessage(); // Outputs: #ZZZZZZ is not a valid hex color
}
```

## Luminance Calculation

Computes the luminance of the color using the standard luminance formula for RGB values.

```php
$luminance = $color->luminanceRGB();
echo $luminance; // Outputs a value between 0 and 1
```

## Hex to RGB Conversion

Converts a hexadecimal color code to its RGB components.

```php
$color = new Color(hexColor: '#BAA355');
list($red, $green, $blue) = $color->hexToRGB();
echo "Red: $red, Green: $green, Blue: $blue"; // Outputs: Red: 186, Green: 163, Blue: 85
```

## Color Generation from Text

Generates a color based on a given text string, with options to specify minimum brightness and color specification.

```php
$textColor = $color->generateColorFromText('example text', 100, 10);
echo $textColor->getHexColor(); // Outputs a generated hex color code,
```
