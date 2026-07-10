<?php

namespace GuiBranco\Pancake\Exceptions;

use Exception;
use Psr\Container\ContainerExceptionInterface;

/**
 * Class ContainerException
 *
 * Thrown by {@see \GuiBranco\Pancake\DIContainer::get()} when a registered
 * service or an autowired class fails to resolve, e.g. its resolver throws,
 * a constructor dependency cannot be determined, or a class is not instantiable.
 *
 * @package GuiBranco\Pancake\Exceptions
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{
}
