<?php

namespace GuiBranco\Pancake;

/**
 * Generates Shields.io badge URLs with proper parameter encoding.
 *
 * Shields.io uses a custom encoding scheme for badge path segments where
 * underscores, hyphens, spaces and other special characters must be escaped
 * before being embedded in the URL path. This class handles that encoding
 * transparently and builds the final badge URL including any query-string
 * options such as style, label colour, logo and cache lifetime.
 *
 * @package GuiBranco\Pancake
 *
 * @example Basic usage
 * ```php
 * $shields = new ShieldsIo();
 * $url = $shields->generateBadgeUrl('build', 'passing', 'brightgreen', 'flat', '', 'github');
 * // https://img.shields.io/badge/build-passing-brightgreen?style=flat&logo=github&cacheSeconds=3600
 * ```
 *
 * @see https://shields.io/badges/endpoint-badge Shields.io documentation
 */
class ShieldsIo
{
    /**
     * Number of seconds the generated badge should be cached by Shields.io CDN.
     *
     * Defaults to 3600 (one hour). Pass a lower value for badges whose
     * underlying data changes frequently, or a higher value for static badges.
     *
     * @var int
     */
    private $cacheSeconds;

    /**
     * Initialise the ShieldsIo badge generator.
     *
     * @param int $cacheSeconds Number of seconds Shields.io should cache the
     *                          badge image. Must be a positive integer.
     *                          Defaults to 3600 (one hour).
     */
    public function __construct($cacheSeconds = 3600)
    {
        $this->cacheSeconds = $cacheSeconds;
    }

    /**
     * Encode a string according to the Shields.io path-segment encoding rules.
     *
     * Shields.io badge URLs embed label, message and colour as slash-separated
     * path segments. Because those segments may themselves contain characters
     * that carry special meaning in URLs or in Shields.io's own parser, the
     * following substitutions are applied in order:
     *
     * | Original | Encoded |
     * |----------|---------|
     * | `_`      | `__`    |
     * | ` `      | `_`     |
     * | `-`      | `--`    |
     * | `%`      | `%25`   |
     * | `/`      | `%2F`   |
     * | `#`      | `♯`     |
     *
     * @param string $input Raw string value to encode (label, message or colour).
     *
     * @return string The encoded string safe for inclusion in a Shields.io
     *                badge URL path segment.
     */
    private function encodeShieldsIoParameters($input)
    {
        $input = str_replace("_", "__", $input);
        $input = str_replace(" ", "_", $input);
        $input = str_replace("-", "--", $input);
        $input = str_replace("%", "%25", $input);
        $input = str_replace("/", "%2F", $input);
        $input = str_replace("#", "♯", $input);
        return $input;
    }

    /**
     * Encode a badge path-segment component and append it to the badge array.
     *
     * The component is skipped (not appended) when it is unset or empty,
     * with the sole exception of the integer literal {@see 0} which is
     * considered a valid non-empty value.
     *
     * @param mixed    $component The raw value to encode and append (label,
     *                            message or colour string).
     * @param string[] $badge     Reference to the ordered array of encoded
     *                            path segments being built for the badge URL.
     *
     * @return void
     */
    private function addComponent($component, &$badge)
    {
        if (isset($component) && (empty($component) === false || $component === 0)) {
            $value = $this->encodeShieldsIoParameters($component);
            $badge[] = $value;
        }
    }

    /**
     * Conditionally add a key/value pair to the badge query-string array.
     *
     * The entry is only added when {@see $component} is set and non-empty.
     * This prevents unnecessary parameters from appearing in the final URL.
     *
     * @param mixed    $component The value to add (e.g. a style name or logo identifier).
     * @param string   $key       The query-string parameter name (e.g. {@see "style"}, {@see "logo"}).
     * @param array    $queryString Reference to the associative array of query-string
     *                             parameters being built for the badge URL.
     *
     * @return void
     */
    private function addQueryString($component, $key, &$queryString)
    {
        if (isset($component) && empty($component) === false) {
            $queryString[$key] = $component;
        }
    }

    /**
     * Build a complete Shields.io badge URL from the supplied parameters.
     *
     * Path segments (label, content, color) are encoded with
     * {@see encodeShieldsIoParameters()} and joined with hyphens.
     * Query-string parameters (style, labelColor, logo, cacheSeconds) are
     * appended only when they are non-empty.
     *
     * @param string $label      Left-hand side text of the badge (e.g. "build",
     *                           "coverage"). May be an empty string to produce a
     *                           message-only badge.
     * @param string $content    Right-hand side text of the badge (e.g. "passing",
     *                           "92%").
     * @param string $color      Background colour of the right-hand side. Accepts
     *                           any value supported by Shields.io: named colours
     *                           (e.g. "brightgreen", "red") or hex codes without
     *                           the leading {@see #} (e.g. "4c1").
     * @param string $style      Badge style. One of: {@see "flat"},
     *                           {@see "flat-square"}, {@see "plastic"},
     *                           {@see "for-the-badge"}, {@see "social"}.
     *                           Pass an empty string to use the Shields.io default.
     * @param string $labelColor Background colour of the left-hand label. Accepts
     *                           the same values as {@see $color}. Pass an empty
     *                           string to use the Shields.io default (grey).
     * @param string $logo       Simple Icons slug or a base64-encoded data URI for
     *                           a custom logo (e.g. "github", "php"). Pass an empty
     *                           string to omit the logo.
     *
     * @return string Fully-qualified Shields.io badge URL, e.g.
     *                {@see https://img.shields.io/badge/build-passing-brightgreen?style=flat&cacheSeconds=3600}
     *
     * @example Minimal badge (no optional parameters)
     * ```php
     * $shields = new ShieldsIo(300);
     * echo $shields->generateBadgeUrl('version', '1.0.0', 'blue', '', '', '');
     * // https://img.shields.io/badge/version-1.0.0-blue?cacheSeconds=300
     * ```
     *
     * @example Full badge with style, label colour and logo
     * ```php
     * $shields = new ShieldsIo();
     * echo $shields->generateBadgeUrl('license', 'MIT', 'yellow', 'for-the-badge', '555', 'opensourceinitiative');
     * // https://img.shields.io/badge/license-MIT-yellow?style=for-the-badge&labelColor=555&logo=opensourceinitiative&cacheSeconds=3600
     * ```
     *
     * @example Badge with special characters in the label
     * ```php
     * $shields = new ShieldsIo();
     * echo $shields->generateBadgeUrl('C# coverage', '92%', 'brightgreen', 'flat', '', '');
     * // https://img.shields.io/badge/C♯_coverage-92%25-brightgreen?style=flat&cacheSeconds=3600
     * ```
     */
    public function generateBadgeUrl($label, $content, $color, $style, $labelColor, $logo)
    {
        $badge = [];
        $this->addComponent($label, $badge);
        $this->addComponent($content, $badge);
        $this->addComponent($color, $badge);

        $queryString = [];
        $this->addQueryString($style, "style", $queryString);
        $this->addQueryString($labelColor, "labelColor", $queryString);
        $this->addQueryString($logo, "logo", $queryString);
        $this->addQueryString($this->cacheSeconds, "cacheSeconds", $queryString);

        return "https://img.shields.io/badge/" . implode("-", $badge) . "?" . http_build_query($queryString);
    }
}
