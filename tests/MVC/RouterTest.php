<?php

namespace GuiBranco\Pancake\Tests\MVC;

use PHPUnit\Framework\TestCase;
use Pancake\MVC\Router;

class RouterTest extends TestCase {
    public function testAddAndDispatch() {
        $router = new Router();

        $mockController = $this->createMock(\stdClass::class);
        $mockController->expects($this->once())
                       ->method('testAction')
                       ->willReturn('action executed');

        $router->add('GET', '/test', get_class($mockController), 'testAction');

        $this->expectOutputString('action executed');
        $router->dispatch('GET', '/test');
    }

    public function testDispatchNotFound() {
        $router = new Router();

        $this->expectOutputString('404 Not Found');
        $router->dispatch('GET', '/non-existent');
    }
}

?>
