# DIContainer Documentation

The `DIContainer` class provides a simple and flexible Dependency Injection (DI) Container with auto-registration and automatic dependency resolution features.

## Features

- **Auto-Registration**: Automatically registers services that are not explicitly registered.
- **Automatic Dependency Resolution**: Resolves all dependencies, including constructor dependencies, recursively.
- **Configurable**: Auto-registration can be enabled or disabled as needed.

## Usage

### Enabling Auto-Registration

By default, auto-registration is enabled. You can create a `DIContainer` instance and resolve services without explicit registration:

```php
$container = new DIContainer();
$service = $container->resolve(MyService::class);
```

### Disabling Auto-Registration

To disable auto-registration, pass `false` to the constructor:

```php
$container = new DIContainer(false);
```

### Registering Services Explicitly

You can still register services explicitly using `registerSingleton` or `registerTransient`:

```php
$container->registerSingleton(MyService::class, new MyService());
$container->registerTransient(MyOtherService::class, function() {
    return new MyOtherService();
});
```

### Handling Dependencies

The container automatically resolves dependencies for services with constructor arguments:

```php
class MyService {
    public function __construct(Dependency $dependency) {}
}

$service = $container->resolve(MyService::class);
```

## Exception Handling

If a service cannot be resolved, an exception is thrown with a clear message:

- **Service Not Found**: When auto-registration is disabled and a service is not registered.
- **Cannot Resolve Dependency**: When a dependency cannot be resolved due to missing type hints or non-existent classes.

## Considerations

- Explicitly registered services take precedence over auto-registered services.
- Ensure that all dependencies have appropriate type hints for successful resolution.
