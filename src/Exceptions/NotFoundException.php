<?php

namespace GuiBranco\Pancake\Exceptions;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class NotFoundException
 *
 * Thrown by {@see \GuiBranco\Pancake\DIContainer::get()} when the requested
 * service identifier is neither registered nor a resolvable/instantiable class.
 *
 * @package GuiBranco\Pancake\Exceptions
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}
