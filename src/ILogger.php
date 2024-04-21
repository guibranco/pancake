<?php

namespace GuiBranco\Pancake;

interface ILogger
{
    public function log(string $message, object $details): bool;
}