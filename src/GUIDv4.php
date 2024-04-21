<?php

namespace GuiBranco\Pancake;

class GUIDv4 {

    private const EMPTY = '00000000-0000-0000-8000-000000000000';

    public static function empty(): string {
        return self::EMPTY;
    }

    public static function random(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff), random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
        );
    }
}