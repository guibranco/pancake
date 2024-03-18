<?php

declare(strict_types=1);

use GuiBranco\Pancake\Email;
use PHPUnit\Framework\TestCase;

/**
 * Class EmailTest
 * @group email
 */
final class EmailTest extends TestCase
{
    /**
     * @throws InvalidArgumentException
     * @return void
     * @test
     */
    public function testCanBeCreatedFromValidEmail(): void
    {
        $string = 'user@example.com';

        $email = Email::fromString($string);

        $this->assertSame($string, $email->asString());
    }

    /**
     * @throws InvalidArgumentException
     * @return void
     * @test
     */
    public function testCannotBeCreatedFromInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Email::fromString('invalid');
    }
}
