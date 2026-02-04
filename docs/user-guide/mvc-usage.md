# MVC Usage Guide

This document provides an overview of how to use the new MVC components in the Pancake toolkit.

## BaseController

The `BaseController` class provides common functionality for rendering views and redirecting URLs.

### Example

```php
use Pancake\MVC\BaseController;

$templateEngine = new YourTemplateEngine();
$controller = new BaseController($templateEngine);

$controller->render('home', ['key' => 'value']);
$controller->redirect('http://example.com');
```

## ApiController

The `ApiController` class extends `BaseController` and is designed for API responses, rendering JSON.

### Example

```php
use Pancake\MVC\ApiController;

$controller = new ApiController(null);
$controller->render(['key' => 'value']);
```

## Router

The `Router` class manages route registration and dispatching.

### Example

```php
use Pancake\MVC\Router;

$router = new Router();
$router->add('GET', '/home', 'HomeController', 'index');
$router->dispatch('GET', '/home');
```

## DIContainer Integration

The `DIContainer` is used to manage dependencies for controllers and the template engine.

### Example

```php
$container = new Pancake\DIContainer();
$controller = $container->resolve('BaseController');
$controller->render('home', ['key' => 'value']);
```

```