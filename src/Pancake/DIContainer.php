<?php

namespace Pancake;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

class DIContainer implements ContainerInterface
{
    private $services = [];
    private $sharedInstances = [];

    public function register(string $name, callable $resolver, bool $shared = false): void
    {
        $this->services[$name] = ['resolver' => $resolver, 'shared' => $shared];
    }

    public function registerSingleton(string $name, callable $resolver): void
    {
        $this->register($name, $resolver, true);
    }

    public function registerTransient(string $name, callable $resolver): void
    {
        $this->register($name, $resolver, false);
    }

    public function get(string $name)
    {
        if (!isset($this->services[$name])) {
            throw new class () extends \Exception implements NotFoundExceptionInterface {};
        }

        if ($this->services[$name]['shared']) {
            if (!isset($this->sharedInstances[$name])) {
                $this->sharedInstances[$name] = $this->resolve($name);
            }
            return $this->sharedInstances[$name];
        }

        return $this->resolve($name);
    }

    private function resolve(string $name)
    {
        try {
            return call_user_func($this->services[$name]['resolver'], $this);
        } catch (\Exception $e) {
            throw new class () extends \Exception implements ContainerExceptionInterface {};
        }
    }

    public function has(string $name): bool
    {
        return isset($this->services[$name]);
    }
}
