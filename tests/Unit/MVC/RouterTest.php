<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit\MVC;

use GuiBranco\Pancake\MVC\Router;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class RouterTest extends TestCase
{
    public function testDispatchesToTheMatchingControllerAction(): void
    {
        $controller = new RouterTestController();
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(RouterTestController::class)
            ->willReturn($controller);

        $router = new Router();
        $router->add('GET', '/test', RouterTestController::class, 'testAction');

        $this->assertSame('action executed', $router->dispatch('GET', '/test', $container));
    }

    public function testMatchesRegardlessOfMethodCaseAndTrailingSlash(): void
    {
        $controller = new RouterTestController();
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturn($controller);

        $router = new Router();
        $router->add('get', '/test', RouterTestController::class, 'testAction');

        $this->assertSame('action executed', $router->dispatch('GET', '/test/', $container));
    }

    public function testIgnoresQueryStringWhenMatchingTheRoute(): void
    {
        $controller = new RouterTestController();
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturn($controller);

        $router = new Router();
        $router->add('GET', '/test', RouterTestController::class, 'testAction');

        $this->assertSame('action executed', $router->dispatch('GET', '/test?foo=bar', $container));
    }

    public function testDispatchReturns404ForAnUnmatchedRoute(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');

        $router = new Router();

        $this->expectOutputString('Page not found');
        $router->dispatch('GET', '/non-existent', $container);

        $this->assertSame(404, http_response_code());
    }
}

final class RouterTestController
{
    public function testAction(): string
    {
        return 'action executed';
    }
}
