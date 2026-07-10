# Dependency Injection Container

## Table of content

- [Dependency Injection Container](#dependency-injection-container)
  - [Table of content](#table-of-content)
  - [About](#about)
  - [Registering services](#registering-services)
  - [Resolving services](#resolving-services)
  - [Autowiring](#autowiring)
  - [Exceptions](#exceptions)
  - [Testing](#testing)

## About

`GuiBranco\Pancake\DIContainer` is a minimal [PSR-11](https://www.php-fig.org/psr/psr-11/) compliant dependency injection container. It supports explicit service registration (singleton or transient) and autowiring for classes that were never registered.

## Registering services

```php
use GuiBranco\Pancake\DIContainer;

$container = new DIContainer();

// Resolved once; the same instance is returned on every get() call.
$container->registerSingleton('config', function () {
    return new Config(['env' => 'prod']);
});

// Resolved again on every get() call.
$container->registerTransient('requestId', function () {
    return uniqid('req_', true);
});

// register() defaults to transient; pass true as the third argument for a singleton.
$container->register('logger', function ($c) {
    return new Logger($c->get('config'));
}, true);
```

## Resolving services

```php
$config = $container->get('config');
$hasLogger = $container->has('logger');
```

Resolver callables receive the container itself as their only argument, so services can depend on other registered services:

```php
$container->registerSingleton('service', function ($c) {
    return new Service($c->get('config'));
});
```

## Autowiring

Calling `get()` with a class name that was never registered reflects on its constructor and recursively resolves each typed, non-builtin parameter through the container. Scalar parameters fall back to their default value, or `null` if the parameter is nullable.

```php
class Service
{
    public function __construct(private Config $config)
    {
    }
}

// Config is autowired too, since it has no constructor dependencies.
$service = $container->get(Service::class);
```

Mix explicit registration and autowiring freely — an autowired class can depend on a service you registered explicitly:

```php
$container->registerSingleton(Config::class, function () {
    return new Config(['env' => 'prod']);
});

// Config resolves to the singleton above; Service itself is autowired.
$service = $container->get(Service::class);
```

Interfaces and abstract classes are never autowirable — register a concrete implementation for them explicitly (this is how the [MVC layer](mvc-usage.md) wires up its template engine).

## Exceptions

`get()` throws:

- `GuiBranco\Pancake\Exceptions\NotFoundException` (implements `Psr\Container\NotFoundExceptionInterface`) when the identifier is neither registered nor a resolvable class.
- `GuiBranco\Pancake\Exceptions\ContainerException` (implements `Psr\Container\ContainerExceptionInterface`) when a resolver throws, a constructor dependency can't be determined, or the class isn't instantiable.

## Testing

See `tests/Unit/DIContainerTest.php` and `tests/Integration/DIContainerIntegrationTest.php`.
