<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Integration;

use GuiBranco\Pancake\DIContainer;
use PHPUnit\Framework\TestCase;
use stdClass;

final class DIContainerIntegrationTest extends TestCase
{
    public function testServiceResolvesDependenciesThroughTheContainer(): void
    {
        $container = new DIContainer();

        $container->registerSingleton('config', function () {
            return ['setting' => 'value'];
        });

        $container->registerSingleton('service', function ($c) {
            $service = new stdClass();
            $service->config = $c->get('config');
            return $service;
        });

        $service = $container->get('service');

        $this->assertSame('value', $service->config['setting']);
    }

    public function testComplexDependencyChainSharesSingletonInstances(): void
    {
        $container = new DIContainer();

        $container->registerSingleton('dependency1', function () {
            return new stdClass();
        });

        $container->registerSingleton('dependency2', function ($c) {
            $dep2 = new stdClass();
            $dep2->dep1 = $c->get('dependency1');
            return $dep2;
        });

        $container->registerSingleton('mainService', function ($c) {
            $mainService = new stdClass();
            $mainService->dep2 = $c->get('dependency2');
            return $mainService;
        });

        $mainService = $container->get('mainService');

        $this->assertSame($mainService->dep2->dep1, $container->get('dependency1'));
    }

    public function testAutowiredServiceCanDependOnAnExplicitlyRegisteredService(): void
    {
        $container = new DIContainer();
        $container->registerSingleton(RepositoryFixture::class, function () {
            return new RepositoryFixture('connected');
        });

        $controller = $container->get(ControllerFixture::class);

        $this->assertSame('connected', $controller->repository->status);
    }
}

class RepositoryFixture
{
    public string $status;

    public function __construct(string $status)
    {
        $this->status = $status;
    }
}

class ControllerFixture
{
    public RepositoryFixture $repository;

    public function __construct(RepositoryFixture $repository)
    {
        $this->repository = $repository;
    }
}
