<?php

namespace GuiBranco\Pancake\MVC;

use Psr\Container\ContainerInterface;

/**
 * Class Router
 *
 * Maps an HTTP method + path to a controller/action pair and dispatches
 * requests by resolving the controller through a PSR-11
 * {@see ContainerInterface} — so registered singletons/transients and
 * autowired controllers both work as dispatch targets.
 *
 * ### Example
 *
 * ```php
 * $router->add('GET', '/api/data', ApiController::class, 'renderData');
 * $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $container);
 * ```
 *
 * @package GuiBranco\Pancake\MVC
 */
class Router
{
    private array $routes = [];

    public function add(string $method, string $route, string $controller, string $action): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'route' => $this->normalizePath($route),
            'controller' => $controller,
            'action' => $action,
        ];
    }

    public function dispatch(string $method, string $uri, ContainerInterface $container)
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($uri);

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['route'] === $path) {
                $controller = $container->get($route['controller']);
                return $controller->{$route['action']}();
            }
        }

        return $this->notFound();
    }

    private function normalizePath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? $uri;

        if ($path === '' || $path === '/') {
            return '/';
        }

        return rtrim($path, '/');
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo 'Page not found';
    }
}
