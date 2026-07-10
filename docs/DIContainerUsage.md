# Dependency Inversion Container Usage

The `GuiBranco\Pancake\DIContainer` class provides a robust way to manage dependencies in your application. It implements the PSR-11 `Psr\Container\ContainerInterface` standard, ensuring compatibility with other libraries and frameworks.

`get()` throws `GuiBranco\Pancake\Exceptions\NotFoundException` (implements `Psr\Container\NotFoundExceptionInterface`) when an identifier is neither registered nor a resolvable class, and `GuiBranco\Pancake\Exceptions\ContainerException` (implements `Psr\Container\ContainerExceptionInterface`) when resolution fails.

## Registering Services

You can register services with the container using the `register`, `registerSingleton`, and `registerTransient` methods.

```php
use GuiBranco\Pancake\DIContainer;

$container = new DIContainer();

// Register a singleton service
$container->registerSingleton('service', function() {
    return new Service();
});

// Register a transient service
$container->registerTransient('transientService', function() {
    return new TransientService();
});

// General registration
$container->register('generalService', function() {
    return new GeneralService();
}, true); // true for singleton
```

## Resolving Services

To resolve a service, use the `get` method.

```php
$service = $container->get('service');
$transientService = $container->get('transientService');
```

## Automatic Dependency Resolution (Autowiring)

If `get()` is called with a class name that was never registered, the container reflects on
its constructor and recursively resolves each typed, non-builtin parameter through itself.
Scalar parameters fall back to their default value (or `null`, if the parameter is nullable).

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

Mix explicit registration and autowiring freely — an autowired class can depend on a
service you registered explicitly, and vice versa:

```php
$container->registerSingleton(Config::class, function () {
    return new Config(['setting' => 'value']);
});

// Config resolves to the singleton registered above; Service itself is autowired.
$service = $container->get(Service::class);
```

## Best Practices

- **Use Singleton for Shared Instances**: Use `registerSingleton` for services that should maintain state or are expensive to create.
- **Use Transient for Stateless Services**: Use `registerTransient` for services that do not maintain state and can be recreated easily.
- **Leverage Autowiring for Concrete Classes**: Skip explicit registration for classes whose dependencies the container can resolve on its own.

## Example

```php
use GuiBranco\Pancake\DIContainer;

$container = new DIContainer();

$container->registerSingleton('config', function () {
    return new Config(['setting' => 'value']);
});

$container->registerSingleton('service', function ($c) {
    $config = $c->get('config');
    return new Service($config);
});

$service = $container->get('service');
```
