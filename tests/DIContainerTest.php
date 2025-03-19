<?php

use PHPUnit\Framework\TestCase;

class DIContainerTest extends TestCase
{
    public function testAutoRegistration()
    {
        $container = new DIContainer();
        $service = $container->resolve(TestService::class);
        $this->assertInstanceOf(TestService::class, $service);
    }

    public function testDependencyResolution()
    {
        $container = new DIContainer();
        $service = $container->resolve(ServiceWithDependencies::class);
        $this->assertInstanceOf(ServiceWithDependencies::class, $service);
        $this->assertInstanceOf(Dependency::class, $service->getDependency());
    }

    public function testAutoRegistrationDisabled()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Service not found: UnregisteredService');

        $container = new DIContainer(false);
        $container->resolve(UnregisteredService::class);
    }

    public function testExceptionHandlingForUnresolvableDependency()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot resolve dependency: unresolvable');

        $container = new DIContainer();
        $container->resolve(ServiceWithUnresolvableDependency::class);
    }
}

class TestService
{
}

class Dependency
{
}

class ServiceWithDependencies
{
    private $dependency;

    public function __construct(Dependency $dependency)
    {
        $this->dependency = $dependency;
    }

    public function getDependency()
    {
        return $this->dependency;
    }
}

class UnregisteredService
{
}

class ServiceWithUnresolvableDependency
{
    public function __construct($unresolvable)
    {
    }
}
