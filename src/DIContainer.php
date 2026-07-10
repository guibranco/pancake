<?php

namespace GuiBranco\Pancake;

use GuiBranco\Pancake\Exceptions\ContainerException;
use GuiBranco\Pancake\Exceptions\NotFoundException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use Throwable;

/**
 * Class DIContainer
 *
 * A minimal PSR-11 compliant dependency injection container.
 *
 * Services can be registered explicitly with a resolver callable via
 * {@see register()}, {@see registerSingleton()}, or {@see registerTransient()}.
 * Singleton services are resolved once and the same instance is returned on
 * every subsequent {@see get()} call; transient services get a fresh instance
 * every time.
 *
 * Classes that were never registered are still resolvable through
 * auto-registration (enabled by default — see {@see $autoRegisterEnabled} /
 * {@see setAutoRegisterEnabled()}): {@see get()} reflects on the class
 * constructor and recursively resolves each typed, non-builtin parameter
 * through the container, falling back to the parameter's default value when
 * one is available.
 *
 * ### Example
 *
 * ```php
 * $container = new DIContainer();
 * $container->registerSingleton(Config::class, fn () => new Config(['env' => 'prod']));
 *
 * // Service has no explicit registration but is auto-registered because its
 * // constructor accepts a Config instance the container already knows about.
 * $service = $container->get(Service::class);
 *
 * // Auto-registration can be turned off, e.g. to force every dependency to be
 * // wired explicitly:
 * $strictContainer = new DIContainer(autoRegisterEnabled: false);
 * ```
 *
 * @package GuiBranco\Pancake
 */
class DIContainer implements ContainerInterface
{
    private array $services = [];
    private array $sharedInstances = [];
    private bool $autoRegisterEnabled;

    public function __construct(bool $autoRegisterEnabled = true)
    {
        $this->autoRegisterEnabled = $autoRegisterEnabled;
    }

    /**
     * Enables or disables auto-registration of classes that were never explicitly registered.
     */
    public function setAutoRegisterEnabled(bool $enabled): void
    {
        $this->autoRegisterEnabled = $enabled;
    }

    /**
     * Registers a service resolver under the given name.
     */
    public function register(string $name, callable $resolver, bool $shared = false): void
    {
        $this->services[$name] = [
            'resolver' => $resolver,
            'shared' => $shared,
        ];
    }

    /**
     * Registers a service that is resolved once and reused on every {@see get()} call.
     */
    public function registerSingleton(string $name, callable $resolver): void
    {
        $this->register($name, $resolver, true);
    }

    /**
     * Registers a service that is resolved anew on every {@see get()} call.
     */
    public function registerTransient(string $name, callable $resolver): void
    {
        $this->register($name, $resolver, false);
    }

    public function has(string $name): bool
    {
        return isset($this->services[$name]);
    }

    /**
     * @throws NotFoundException if $name is not registered and cannot (or must not) be auto-registered.
     * @throws ContainerException if resolving $name fails.
     */
    public function get(string $name)
    {
        if ($this->has($name)) {
            return $this->resolveRegistered($name);
        }

        if ($this->autoRegisterEnabled && class_exists($name)) {
            return $this->autoRegister($name);
        }

        throw new NotFoundException("Service '{$name}' not registered.");
    }

    private function resolveRegistered(string $name)
    {
        if (!$this->services[$name]['shared']) {
            return $this->callResolver($name);
        }

        if (!isset($this->sharedInstances[$name])) {
            $this->sharedInstances[$name] = $this->callResolver($name);
        }

        return $this->sharedInstances[$name];
    }

    private function callResolver(string $name)
    {
        try {
            return ($this->services[$name]['resolver'])($this);
        } catch (Throwable $e) {
            throw new ContainerException("Failed to resolve service '{$name}': {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Instantiates $className, recursively resolving its constructor dependencies
     * through the container (auto-registering them too, if needed).
     */
    private function autoRegister(string $className)
    {
        try {
            $reflection = new ReflectionClass($className);
        } catch (ReflectionException $e) {
            throw new ContainerException("Cannot reflect class '{$className}': {$e->getMessage()}", 0, $e);
        }

        if (!$reflection->isInstantiable()) {
            throw new ContainerException("Class '{$className}' is not instantiable.");
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return new $className();
        }

        $arguments = array_map(
            fn (ReflectionParameter $parameter) => $this->resolveParameter($className, $parameter),
            $constructor->getParameters()
        );

        return $reflection->newInstanceArgs($arguments);
    }

    private function resolveParameter(string $className, ReflectionParameter $parameter)
    {
        $type = $parameter->getType();

        if ($type !== null && !$type->isBuiltin()) {
            return $this->get($type->getName());
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new ContainerException(
            "Cannot resolve parameter '\${$parameter->getName()}' of class '{$className}'."
        );
    }
}
