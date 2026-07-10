<?php

namespace GuiBranco\Pancake\Exceptions;

use Exception;

/**
 * Class ViewNotFoundException
 *
 * Thrown by {@see \GuiBranco\Pancake\MVC\DefaultTemplateEngine::render()} when
 * the resolved view file does not exist on disk.
 *
 * @package GuiBranco\Pancake\Exceptions
 */
class ViewNotFoundException extends Exception
{
}
