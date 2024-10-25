<?php

class DIContainer
{
    private $services = [];
    private $autoRegisterEnabled = true;

    public function __construct($autoRegisterEnabled = true)
    {
        $this->autoRegisterEnabled = $autoRegisterEnabled;
    }

    public function resolve($name)
    {
        if (isset($this->services[$name])) {
            return $this->services[$name];
        }

        if ($this->autoRegisterEnabled) {
            return $this->autoRegister($name);
        }

        throw new Exception("Service not found: $name");
    }

    private function autoRegister($name)
    {
        if (!class_exists($name)) {
            throw new Exception("Class not found: $name");
        }

        $reflectionClass = new ReflectionClass($name);
        $constructor = $reflectionClass->getConstructor();

        if (is_null($constructor)) {
            return new $name();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependencyClass = $parameter->getClass();
            if (is_null($dependencyClass)) {
                throw new Exception("Cannot resolve dependency: " . $parameter->getName());
            }
            $dependencies[] = $this->resolve($dependencyClass->getName());
        }

        return $reflectionClass->newInstanceArgs($dependencies);
    }

    public function setAutoRegisterEnabled(bool $enabled)
    {
        $this->autoRegisterEnabled = $enabled;
    }

    public function registerSingleton($name, $instance)
    {
        $this->services[$name] = $instance;
    }

    public function registerTransient($name, $closure)
    {
        $this->services[$name] = $closure();
    }
}
