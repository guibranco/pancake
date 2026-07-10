<?php

namespace GuiBranco\Pancake\Exceptions;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class NotFoundException
 *
 * Thrown by {@see \GuiBranco\Pancake\DIContainer::get()} when the requested
 * service identifier is neither registered nor a resolvable/instantiable class,
 * or when it would be resolvable only through auto-registration and that
 * feature is disabled.
 *
 * @package GuiBranco\Pancake\Exceptions
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}
