<?php

namespace GuiBranco\Pancake;

class IpUtils
{
    /**
     * Validate if the given IP is a valid IPv4 address.
     *
     * @param string $ip
     * @return bool
     */
    public static function isValidIPv4(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Validate if the given IP is a valid IPv6 address.
     *
     * @param string $ip
     * @return bool
     */
    public static function isValidIPv6(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Check if an IPv4 address is within a specified CIDR range.
     *
     * @param string $ip
     * @param string $cidr
     * @return bool
     */
    public static function isIPv4InRange(string $ip, string $cidr): bool
    {
        if (!self::isValidIPv4($ip)) {
            return false;
        }

        list($subnet, $mask) = explode('/', $cidr);
        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
    }

    /**
     * Check if an IPv6 address is within a specified CIDR range.
     *
     * @param string $ip
     * @param string $cidr
     * @return bool
     */
    public static function isIPv6InRange(string $ip, string $cidr): bool
    {
        if (!self::isValidIPv6($ip)) {
            return false;
        }

        list($subnet, $mask) = explode('/', $cidr);

        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);
        $maskBin = str_repeat("f", $mask / 4) . str_repeat("0", (128 - $mask) / 4);

        return ($ipBin & $maskBin) == ($subnetBin & $maskBin);
    }

    /**
     * Convert an IP address to its long integer representation.
     *
     * @param string $ip
     * @return string|false
     */
    public static function ipToLong(string $ip)
    {
        if (self::isValidIPv4($ip)) {
            return sprintf('%u', ip2long($ip));
        }

        if (self::isValidIPv6($ip)) {
            $bin = inet_pton($ip);
            $unpack = unpack('H*', $bin);
            return base_convert($unpack[1], 16, 10);
        }

        return false;
    }
}
