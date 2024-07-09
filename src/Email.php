<?php

declare(strict_types=1);

namespace GuiBranco\Pancake;

require_once 'Queue.php';

use InvalidArgumentException;

/**
 * Class Email
 * @package GuiBranco\Pancake
 */
final class Email
{
    private string $email;

    private function __construct(string $email)
    {
        $this->ensureIsValidEmail($email);

        $this->email = $email;
    }

    /**
     * @param string $email
     * @return Email
     * @throws InvalidArgumentException
     */
    public static function fromString(string $email): self
    {
        return new self($email);
    }

    /**
     * @return string
     */
    public function asString(): string
    {
        return $this->email;
    }

    private function ensureIsValidEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                sprintf(
                    '"%s" is not a valid email address',
                    $email
                )
            );
        }
    }
}
