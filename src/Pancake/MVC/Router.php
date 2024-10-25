<?php

namespace Pancake\MVC;

class Router {
    private $routes = [];

    public function add($method, $uri, $controller, $action) {
        $this->routes[] = compact('method', 'uri', 'controller', 'action');
    }

    public function dispatch($method, $uri) {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['uri'] === $uri) {
                $controller = new $route['controller'];
                $action = $route['action'];
                return $controller->$action();
            }
        }
        // Handle 404
        header("HTTP/1.0 404 Not Found");
        echo "404 Not Found";
    }
}

?>
