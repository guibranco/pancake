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
     * @param string $ip The IPv6 address to check
     * @param string $cidr The CIDR range (e.g., "2001:db8::/32")
     * @throws \InvalidArgumentException If CIDR format is invalid
     * @return bool
     * @link https://stackoverflow.com/questions/7951061/matching-ipv6-address-to-a-cidr-subnet
     * @see RFC 4291 for IPv6 addressing architecture
     */
    public static function isIPv6InRange(string $ip, string $cidr): bool
    {
        if (!self::isValidIPv6($ip)) {
            return false;
        }
        if (!preg_match('/^[0-9a-fA-F:]+\/([0-9]|[1-9][0-9]|1[0-1][0-9]|12[0-8])$/', $cidr)) {
            throw new \InvalidArgumentException('Invalid IPv6 CIDR format');
        }
        list($subnet, $mask) = explode('/', $cidr);
        if (!self::isValidIPv6($subnet)) {
            throw new \InvalidArgumentException('Invalid subnet in CIDR');
        }
        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);
        $maskBin = str_repeat("f", intval($mask / 4));
        switch ($mask % 4) {
            case 0:
                break;
            case 1:
                $maskBin .= "8";
                break;
            case 2:
                $maskBin .= "c";
                break;
            case 3:
                $maskBin .= "e";
                break;
        }
        $maskBin = str_pad($maskBin, 32, '0');
        $maskBin = pack("H*", $maskBin);
        return ($ipBin & $maskBin) == ($subnetBin & $maskBin);
    }

    /**
     * Convert an IP address to its long integer representation.
     *
     * @param string $ip The IP address to convert
     * @return string|null Returns null for invalid IP addresses
     * @note IPv6 addresses will return large numbers that may exceed PHP_INT_MAX
     * @example ipToLong('192.168.1.1') // returns "3232235777"
     */
    public static function ipToLong(string $ip): ?string
    {
        if (self::isValidIPv4($ip)) {
            return sprintf('%u', ip2long($ip));
        }

        if (self::isValidIPv6($ip)) {
            $bin = inet_pton($ip);
            $unpack = unpack('H*', $bin);
            return base_convert($unpack[1], 16, 10);
        }

        return null;
    }
}
