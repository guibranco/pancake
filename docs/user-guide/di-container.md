# Dependency Injection Container

## Table of content

- [Dependency Injection Container](#dependency-injection-container)
  - [Table of content](#table-of-content)
  - [About](#about)
  - [Registering services explicitly](#registering-services-explicitly)
  - [Resolving services](#resolving-services)
  - [Auto-registration](#auto-registration)
  - [Disabling auto-registration](#disabling-auto-registration)
  - [Exceptions](#exceptions)
  - [Testing](#testing)

## About

`GuiBranco\Pancake\DIContainer` is a minimal [PSR-11](https://www.php-fig.org/psr/psr-11/) compliant dependency injection container. It supports explicit service registration (singleton or transient) as well as auto-registration: resolving classes that were never registered by reflecting on their constructor and recursively wiring up their dependencies.

## Registering services explicitly

```php
use GuiBranco\Pancake\DIContainer;

$container = new DIContainer();

// Resolved once; the same instance is returned on every get() call.
$container->registerSingleton(Config::class, function () {
    return new Config(['env' => 'prod']);
});

// Resolved again on every get() call.
$container->registerTransient('requestId', function () {
    return uniqid('req_', true);
});

// register() defaults to transient; pass true as the third argument for a singleton.
$container->register('logger', function ($c) {
    return new Logger($c->get(Config::class));
}, true);
```

## Resolving services

```php
$config = $container->get(Config::class);
$hasLogger = $container->has('logger');
```

Resolver callables receive the container itself as their only argument, so services can depend on other registered services.

## Auto-registration

Auto-registration is **enabled by default**. Calling `get()` with a class name that was never registered reflects on its constructor and recursively resolves each typed, non-builtin parameter through the container — auto-registering those too, if needed. Scalar parameters fall back to their default value, or `null` if the parameter is nullable.

```php
class ServiceC
{
}

class ServiceB
{
    public function __construct(private ServiceC $serviceC)
    {
    }
}

class ServiceA
{
    public function __construct(private ServiceB $serviceB)
    {
    }
}

$container = new DIContainer();

// None of ServiceA, ServiceB, or ServiceC were registered — the whole chain is
// auto-registered and resolved recursively.
$serviceA = $container->get(ServiceA::class);
```

Mix explicit registration and auto-registration freely — an auto-registered class can depend on a service you registered explicitly:

```php
$container->registerSingleton(Config::class, function () {
    return new Config(['env' => 'prod']);
});

// Config resolves to the singleton above; Service itself is auto-registered.
$service = $container->get(Service::class);
```

Interfaces and abstract classes are never auto-registrable — register a concrete implementation for them explicitly.

## Disabling auto-registration

Pass `false` to the constructor, or call `setAutoRegisterEnabled()` at any point:

```php
// Off from the start: every dependency must be registered explicitly.
$container = new DIContainer(autoRegisterEnabled: false);

// Or toggle it at runtime.
$container = new DIContainer();
$container->setAutoRegisterEnabled(false);
```

With auto-registration disabled, `get()` still resolves anything registered explicitly — it only stops falling back to reflection for unregistered class names, throwing `NotFoundException` instead.

## Exceptions

`get()` throws:

- `GuiBranco\Pancake\Exceptions\NotFoundException` (implements `Psr\Container\NotFoundExceptionInterface`) when the identifier is neither registered nor auto-registrable — either because it isn't a class, or because auto-registration is disabled.
- `GuiBranco\Pancake\Exceptions\ContainerException` (implements `Psr\Container\ContainerExceptionInterface`) when a resolver throws, a constructor dependency can't be determined, or the class isn't instantiable.

## Testing

See `tests/Unit/DIContainerTest.php` and `tests/Integration/DIContainerIntegrationTest.php`.
