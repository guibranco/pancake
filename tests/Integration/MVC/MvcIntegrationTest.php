<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Integration\MVC;

use GuiBranco\Pancake\DIContainer;
use GuiBranco\Pancake\MVC\ApiController;
use GuiBranco\Pancake\MVC\BaseController;
use GuiBranco\Pancake\MVC\DefaultTemplateEngine;
use GuiBranco\Pancake\MVC\Router;
use GuiBranco\Pancake\MVC\TemplateEngineInterface;
use PHPUnit\Framework\TestCase;

final class MvcIntegrationTest extends TestCase
{
    private function buildContainer(): DIContainer
    {
        $container = new DIContainer();

        $container->registerSingleton(TemplateEngineInterface::class, function () {
            return new DefaultTemplateEngine(__DIR__ . '/../../Unit/MVC/fixtures');
        });

        // BaseController/ApiController are not explicitly registered: the
        // container autowires them, resolving TemplateEngineInterface above.

        return $container;
    }

    public function testRouterDispatchesToAnAutowiredWebController(): void
    {
        $container = $this->buildContainer();
        $container->registerTransient(GreetingController::class, function ($c) {
            return new GreetingController($c->get(TemplateEngineInterface::class));
        });

        $router = new Router();
        $router->add('GET', '/greet', GreetingController::class, 'greet');

        $this->expectOutputString("<p>Hello, World!</p>\n");
        $router->dispatch('GET', '/greet', $container);
    }

    public function testRouterDispatchesToAnAutowiredApiController(): void
    {
        $container = $this->buildContainer();

        $router = new Router();
        $router->add('GET', '/api/data', DataApiController::class, 'data');

        $this->expectOutputString('{"key":"value"}');
        $router->dispatch('GET', '/api/data', $container);
    }

    public function testUnmatchedRouteReturns404WithoutTouchingTheContainer(): void
    {
        $container = $this->buildContainer();
        $router = new Router();

        $this->expectOutputString('Page not found');
        $router->dispatch('GET', '/missing', $container);

        $this->assertSame(404, http_response_code());
    }
}

class GreetingController extends BaseController
{
    public function greet(): void
    {
        $this->render('greeting', ['name' => 'World']);
    }
}

class DataApiController extends ApiController
{
    public function data(): void
    {
        $this->render('ignored-view', ['key' => 'value']);
    }
}
