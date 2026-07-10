<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit;

use GuiBranco\Pancake\DIContainer;
use GuiBranco\Pancake\Exceptions\ContainerException;
use GuiBranco\Pancake\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

final class DIContainerTest extends TestCase
{
    public function testImplementsPsr11ContainerInterface(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, new DIContainer());
    }

    public function testRegisterAndResolveSingletonReturnsSameInstance(): void
    {
        $container = new DIContainer();
        $container->registerSingleton('service', function () {
            return new stdClass();
        });

        $this->assertSame($container->get('service'), $container->get('service'));
    }

    public function testRegisterAndResolveTransientReturnsNewInstanceEachTime(): void
    {
        $container = new DIContainer();
        $container->registerTransient('service', function () {
            return new stdClass();
        });

        $this->assertNotSame($container->get('service'), $container->get('service'));
    }

    public function testRegisterDefaultsToTransient(): void
    {
        $container = new DIContainer();
        $container->register('service', function () {
            return new stdClass();
        });

        $this->assertNotSame($container->get('service'), $container->get('service'));
    }

    public function testHasReturnsTrueOnlyForRegisteredServices(): void
    {
        $container = new DIContainer();
        $container->registerSingleton('service', function () {
            return new stdClass();
        });

        $this->assertTrue($container->has('service'));
        $this->assertFalse($container->has('non_existent_service'));
    }

    public function testGetThrowsNotFoundExceptionForUnregisteredNonClassName(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectException(NotFoundException::class);

        (new DIContainer())->get('non_existent_service');
    }

    public function testGetThrowsContainerExceptionWhenResolverFails(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectException(ContainerException::class);

        $container = new DIContainer();
        $container->register('service', function () {
            throw new \RuntimeException('Resolution failed');
        });

        $container->get('service');
    }

    public function testResolverReceivesContainerInstance(): void
    {
        $container = new DIContainer();
        $container->registerSingleton('config', function () {
            return ['setting' => 'value'];
        });
        $container->registerSingleton('service', function (ContainerInterface $c) {
            $service = new stdClass();
            $service->config = $c->get('config');
            return $service;
        });

        $this->assertSame('value', $container->get('service')->config['setting']);
    }

    public function testAutoRegistersClassWithoutConstructor(): void
    {
        $instance = (new DIContainer())->get(stdClass::class);

        $this->assertInstanceOf(stdClass::class, $instance);
    }

    public function testAutoRegisterIsEnabledByDefault(): void
    {
        $container = new DIContainer();

        $this->assertInstanceOf(FixtureDependency::class, $container->get(FixtureDependency::class));
    }

    public function testAutoRegisterCanBeDisabledViaConstructor(): void
    {
        $this->expectException(NotFoundException::class);

        (new DIContainer(false))->get(FixtureDependency::class);
    }

    public function testAutoRegisterCanBeDisabledViaSetter(): void
    {
        $container = new DIContainer();
        $container->setAutoRegisterEnabled(false);

        $this->expectException(NotFoundException::class);

        $container->get(FixtureDependency::class);
    }

    public function testAutoRegisterCanBeReEnabledViaSetter(): void
    {
        $container = new DIContainer(false);
        $container->setAutoRegisterEnabled(true);

        $this->assertInstanceOf(FixtureDependency::class, $container->get(FixtureDependency::class));
    }

    public function testExplicitlyRegisteredServicesResolveEvenWhenAutoRegisterIsDisabled(): void
    {
        $container = new DIContainer(false);
        $container->registerSingleton(FixtureDependency::class, function () {
            return new FixtureDependency();
        });

        $this->assertInstanceOf(FixtureDependency::class, $container->get(FixtureDependency::class));
    }

    public function testAutoRegisterResolvesScalarDefaultValue(): void
    {
        $instance = (new DIContainer())->get(FixtureWithDefaultValue::class);

        $this->assertSame('default', $instance->label);
    }

    public function testAutoRegisterResolvesNestedClassDependenciesRecursively(): void
    {
        $instance = (new DIContainer())->get(FixtureWithDependency::class);

        $this->assertInstanceOf(FixtureDependency::class, $instance->dependency);
    }

    public function testAutoRegisterThrowsContainerExceptionForUnresolvableScalarParameter(): void
    {
        $this->expectException(ContainerException::class);

        (new DIContainer())->get(FixtureWithRequiredScalar::class);
    }

    public function testAutoRegisterThrowsContainerExceptionForNonInstantiableClass(): void
    {
        $this->expectException(ContainerException::class);

        (new DIContainer())->get(FixtureAbstract::class);
    }
}

class FixtureDependency
{
}

class FixtureWithDependency
{
    public FixtureDependency $dependency;

    public function __construct(FixtureDependency $dependency)
    {
        $this->dependency = $dependency;
    }
}

class FixtureWithDefaultValue
{
    public string $label;

    public function __construct(string $label = 'default')
    {
        $this->label = $label;
    }
}

class FixtureWithRequiredScalar
{
    public function __construct(string $label)
    {
    }
}

abstract class FixtureAbstract
{
}
