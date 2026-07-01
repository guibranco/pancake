# Dependency Inversion Container Usage

The `DIContainer` class in the Pancake framework provides a robust way to manage dependencies in your application. It adheres to the PSR-11 `ContainerInterface` standard, ensuring compatibility with other libraries and frameworks.

## Registering Services

You can register services with the container using the `register`, `registerSingleton`, and `registerTransient` methods.

```php
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

To resolve a service, use the `get` method. The container will automatically handle dependencies.

```php
$service = $container->get('service');
$transientService = $container->get('transientService');
```

## Best Practices

- **Use Singleton for Shared Instances**: Use `registerSingleton` for services that should maintain state or are expensive to create.
- **Use Transient for Stateless Services**: Use `registerTransient` for services that do not maintain state and can be recreated easily.
- **Leverage Automatic Dependency Resolution**: Define dependencies in service constructors and let the container resolve them automatically.

## Example

```php
$container->registerSingleton('config', function() {
    return new Config(['setting' => 'value']);
});

$container->registerSingleton('service', function($c) {
    $config = $c->get('config');
    return new Service($config);
});

$service = $container->get('service');
```
