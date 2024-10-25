<?php

namespace Pancake;

class DIContainer {
    private $bindings = [];

    public function registerSingleton($name, $resolver) {
        $this->bindings[$name] = ['resolver' => $resolver, 'shared' => true, 'instance' => null];
    }

    public function registerTransient($name, $resolver) {
        $this->bindings[$name] = ['resolver' => $resolver, 'shared' => false];
    }

    public function resolve($name) {
        if (!isset($this->bindings[$name])) {
            throw new \Exception("No binding registered for {$name}");
        }

        $binding = $this->bindings[$name];

        if ($binding['shared']) {
            if ($binding['instance'] === null) {
                $binding['instance'] = $binding['resolver']();
            }
            return $binding['instance'];
        }

        return $binding['resolver']();
    }
}

// Registering components
$container = new DIContainer();
$container->registerSingleton('templateEngine', function() {
    return new DefaultTemplateEngine(); // Assume DefaultTemplateEngine is defined elsewhere
});
$container->registerTransient('BaseController', function() use ($container) {
    return new MVC\BaseController($container->resolve('templateEngine'));
});
$container->registerTransient('ApiController', function() use ($container) {
    return new MVC\ApiController($container->resolve('templateEngine'));
});

?>
