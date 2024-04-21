<?php

namespace GuiBranco\Pancake;

interface ILogger
{
    function log(string $message, object $details): void;
}