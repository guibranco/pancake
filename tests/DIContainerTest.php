<?php

use PHPUnit\Framework\TestCase;
use Pancake\DIContainer;

class DIContainerTest extends TestCase
{
    public function testRegisterAndResolveSingleton()
    {
        $container = new DIContainer();
        $container->registerSingleton('service', function () {
            return new stdClass();
        });

        $service1 = $container->get('service');
        $service2 = $container->get('service');

        $this->assertSame($service1, $service2);
    }

    public function testRegisterAndResolveTransient()
    {
        $container = new DIContainer();
        $container->registerTransient('service', function () {
            return new stdClass();
        });

        $service1 = $container->get('service');
        $service2 = $container->get('service');

        $this->assertNotSame($service1, $service2);
    }

    public function testServiceNotFound()
    {
        $this->expectException(Psr\Container\NotFoundExceptionInterface::class);

        $container = new DIContainer();
        $container->get('non_existent_service');
    }

    public function testHasMethod()
    {
        $container = new DIContainer();
        $container->registerSingleton('service', function () {
            return new stdClass();
        });

        $this->assertTrue($container->has('service'));
        $this->assertFalse($container->has('non_existent_service'));
    }

    public function testExceptionOnResolutionFailure()
    {
        $this->expectException(Psr\Container\ContainerExceptionInterface::class);

        $container = new DIContainer();
        $container->register('service', function () {
            throw new Exception('Resolution failed');
        });

        $container->get('service');
    }
}
