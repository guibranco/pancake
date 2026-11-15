<?php

declare(strict_types=1);

namespace GuiBranco\Pancake;

/**
 * A secret returned by the Grimoire Consumer API.
 *
 * Mirrors the Azure Key Vault-compatible response shape:
 * `{ "name": "...", "value": "...", "properties": { "enabled": true, "version": 2, ... } }`.
 */
final class GrimoireSecret
{
    public function __construct(
        public readonly string $name,
        public readonly string $value,
        public readonly bool $enabled,
        public readonly int $version,
        public readonly ?\DateTimeImmutable $createdOn = null,
        public readonly ?\DateTimeImmutable $updatedOn = null,
        public readonly ?\DateTimeImmutable $expiresOn = null,
        public readonly ?\DateTimeImmutable $notBefore = null,
    ) {
    }

    /**
     * Build a secret from the decoded JSON body of `GET /consumer/secrets/{name}`.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['name'], $data['value']) || !is_string($data['name']) || !is_string($data['value'])) {
            throw new GrimoireException('Invalid secret payload: "name" and "value" are required.');
        }

        $properties = is_array($data['properties'] ?? null) ? $data['properties'] : [];

        return new self(
            name:      $data['name'],
            value:     $data['value'],
            enabled:   (bool) ($properties['enabled'] ?? true),
            version:   (int) ($properties['version'] ?? 0),
            createdOn: self::parseDate($properties['createdOn'] ?? null),
            updatedOn: self::parseDate($properties['updatedOn'] ?? null),
            expiresOn: self::parseDate($properties['expiresOn'] ?? null),
            notBefore: self::parseDate($properties['notBefore'] ?? null),
        );
    }

    /** Hide the value from var_dump()/print_r() so secrets don't end up in logs by accident. */
    public function __debugInfo(): array
    {
        return [
            'name'      => $this->name,
            'value'     => '***',
            'enabled'   => $this->enabled,
            'version'   => $this->version,
            'createdOn' => $this->createdOn?->format(DATE_ATOM),
            'updatedOn' => $this->updatedOn?->format(DATE_ATOM),
            'expiresOn' => $this->expiresOn?->format(DATE_ATOM),
            'notBefore' => $this->notBefore?->format(DATE_ATOM),
        ];
    }

    private static function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
