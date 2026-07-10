# MVC

## Table of content

- [MVC](#mvc)
  - [Table of content](#table-of-content)
  - [About](#about)
  - [Template engines](#template-engines)
  - [BaseController](#basecontroller)
  - [ApiController](#apicontroller)
  - [Router](#router)
  - [Wiring it together with the DI container](#wiring-it-together-with-the-di-container)
  - [Testing](#testing)

## About

`GuiBranco\Pancake\MVC` adds a small MVC layer to the toolkit: controllers that render views or JSON, and a `Router` that dispatches requests to them. Controllers are resolved through [`DIContainer`](di-container.md), so their dependencies (starting with the template engine) are wired automatically.

## Template engines

`GuiBranco\Pancake\MVC\TemplateEngineInterface` is the contract controllers render through:

```php
interface TemplateEngineInterface
{
    public function render(string $view, array $data = []): string;
}
```

`DefaultTemplateEngine` is the built-in implementation: it resolves a dot-separated view name (e.g. `"users.profile"`) to a plain PHP file (`{$viewsPath}/users/profile.php`), extracts `$data` as local variables, and captures the file's output.

```php
use GuiBranco\Pancake\MVC\DefaultTemplateEngine;

$engine = new DefaultTemplateEngine(__DIR__ . '/views');
echo $engine->render('greeting', ['name' => 'World']); // views/greeting.php
echo $engine->render('users.profile', ['username' => 'gui']); // views/users/profile.php
```

To use Twig, Mustache, Plates, or any other engine instead, implement `TemplateEngineInterface` around it and register that implementation in the container (see below) — no other code needs to change.

## BaseController

`BaseController` renders views through the injected template engine and offers a `redirect()` helper.

```php
use GuiBranco\Pancake\MVC\BaseController;

class HomeController extends BaseController
{
    public function index(): void
    {
        $this->render('home', ['title' => 'Welcome']);
    }
}
```

## ApiController

`ApiController` extends `BaseController` and overrides `render()` to always emit JSON instead of a templated view (the `$view` argument is accepted only to keep the same method signature and is otherwise ignored):

```php
use GuiBranco\Pancake\MVC\ApiController;

class DataController extends ApiController
{
    public function index(): void
    {
        $this->render('unused', ['status' => 'ok']);
        // Content-Type: application/json
        // {"status":"ok"}
    }
}
```

## Router

`Router` maps an HTTP method + path to a controller/action pair, and dispatches by resolving the controller through a [PSR-11](https://www.php-fig.org/psr/psr-11/) container — `DIContainer` or any other compatible implementation.

```php
use GuiBranco\Pancake\MVC\Router;

$router = new Router();
$router->add('GET', '/home', HomeController::class, 'index');
$router->add('GET', '/api/data', DataController::class, 'index');

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $container);
```

Matching ignores the request's query string and a trailing slash, and treats the HTTP method case-insensitively. Unmatched routes get a `404` response with a `Page not found` body.

## Wiring it together with the DI container

Register the template engine once; controllers that depend on `TemplateEngineInterface` are autowired without any registration of their own.

```php
use GuiBranco\Pancake\DIContainer;
use GuiBranco\Pancake\MVC\DefaultTemplateEngine;
use GuiBranco\Pancake\MVC\Router;
use GuiBranco\Pancake\MVC\TemplateEngineInterface;

$container = new DIContainer();
$container->registerSingleton(TemplateEngineInterface::class, function () {
    return new DefaultTemplateEngine(__DIR__ . '/views');
});

$router = new Router();
$router->add('GET', '/home', HomeController::class, 'index');
$router->add('GET', '/api/data', DataController::class, 'index');

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $container);
```

Register a controller explicitly instead when it needs something beyond autowiring — a specific constructor argument, an interface the container can't guess an implementation for, etc.:

```php
$container->registerTransient(DataController::class, function ($c) {
    return new DataController($c->get(TemplateEngineInterface::class), $c->get('config'));
});
```

## Testing

See `tests/Unit/MVC/` and `tests/Integration/MVC/MvcIntegrationTest.php`.
