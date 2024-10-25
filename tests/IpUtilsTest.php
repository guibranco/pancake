<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests;

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use GuiBranco\Pancake\IpUtils;

class IpUtilsTest extends TestCase
{
    public function testIsValidIPv4()
    {
        $this->assertTrue(IpUtils::isValidIPv4('192.168.1.1'));
        $this->assertFalse(IpUtils::isValidIPv4('999.999.999.999'));
        $this->assertFalse(IpUtils::isValidIPv4('::1'));
    }

    public function testIsValidIPv6()
    {
        $this->assertTrue(IpUtils::isValidIPv6('::1'));
        $this->assertTrue(IpUtils::isValidIPv6('2001:0db8:85a3:0000:0000:8a2e:0370:7334'));
        $this->assertFalse(IpUtils::isValidIPv6('192.168.1.1'));
    }

    public function testIsIPv4InRange()
    {
        $this->assertTrue(IpUtils::isIPv4InRange('192.168.1.1', '192.168.1.0/24'));
        $this->assertFalse(IpUtils::isIPv4InRange('192.168.2.1', '192.168.1.0/24'));
        $this->assertFalse(IpUtils::isIPv4InRange('999.999.999.999', '192.168.1.0/24'));
    }

    public function testIsIPv6InRange()
    {
        $this->assertTrue(IpUtils::isIPv6InRange('2001:0db8:85a3:0000:0000:8a2e:0370:7334', '2001:0db8:85a3::/64'));
        $this->assertFalse(IpUtils::isIPv6InRange('2001:0db8:85a3:0000:0000:8a2e:0370:7334', '2001:0db8:85a4::/64'));
        $this->assertFalse(IpUtils::isIPv6InRange('::1', '2001:0db8:85a3::/64'));
        $this->assertFalse(IpUtils::isIPv6InRange('invalid_ip', '2001:0db8:85a3::/64'));
    }

    public function testIpToLong()
    {
        $this->assertEquals('3232235777', IpUtils::ipToLong('192.168.1.1'));
        $this->assertEquals('42540766452641178846426886644622868426', IpUtils::ipToLong('2001:0db8:85a3:0000:0000:8a2e:0370:7334'));
        $this->assertFalse(IpUtils::ipToLong('999.999.999.999'));
        $this->assertFalse(IpUtils::ipToLong('invalid_ip'));
    }
}
