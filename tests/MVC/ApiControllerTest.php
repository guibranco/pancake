<?php

namespace GuiBranco\Pancake\Tests\MVC;

use PHPUnit\Framework\TestCase;
use Pancake\MVC\ApiController;

class ApiControllerTest extends TestCase
{
    public function testRenderJson()
    {
        $controller = new ApiController(null);

        $this->expectOutputString('{"key":"value"}');
        $controller->render(['key' => 'value']);
    }
}
