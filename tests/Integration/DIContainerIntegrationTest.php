<?php

use PHPUnit\Framework\TestCase;
use Pancake\DIContainer;

class DIContainerIntegrationTest extends TestCase
{
    public function testIntegrationWithPancakeFramework()
    {
        $container = new DIContainer();

        // Simulate a service with dependencies
        $container->registerSingleton('config', function () {
            return ['setting' => 'value'];
        });

        $container->registerSingleton('service', function ($c) {
            $config = $c->get('config');
            $service = new stdClass();
            $service->config = $config;
            return $service;
        });

        $service = $container->get('service');

        $this->assertEquals('value', $service->config['setting']);
    }

    public function testComplexDependencyChain()
    {
        $container = new DIContainer();

        $container->registerSingleton('dependency1', function () {
            return new stdClass();
        });

        $container->registerSingleton('dependency2', function ($c) {
            $dep1 = $c->get('dependency1');
            $dep2 = new stdClass();
            $dep2->dep1 = $dep1;
            return $dep2;
        });

        $container->registerSingleton('mainService', function ($c) {
            $dep2 = $c->get('dependency2');
            $mainService = new stdClass();
            $mainService->dep2 = $dep2;
            return $mainService;
        });

        $mainService = $container->get('mainService');

        $this->assertSame($mainService->dep2->dep1, $container->get('dependency1'));
    }
}
