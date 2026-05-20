<?php
namespace Core;

class Router {
    private $routes = [];

    public function add($method, $path, $controllerAction) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'action' => $controllerAction
        ];
    }

    public function dispatch($method, $uri) {
        $uri = parse_url($uri, PHP_URL_PATH);
        
        $scriptName = $_SERVER['SCRIPT_NAME']; 
        $baseDir = dirname($scriptName); 
        
        if (strpos($uri, $baseDir) === 0) {
            $uri = substr($uri, strlen($baseDir));
        }
        if ($uri == '') $uri = '/';

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $uri) {
                $action = explode('@', $route['action']);
                $controllerName = "App\\Controllers\\" . $action[0];
                $methodName = $action[1];

                if (class_exists($controllerName)) {
                    $controller = new $controllerName();
                    if (method_exists($controller, $methodName)) {
                        return $controller->$methodName();
                    }
                }
            }
        }
        
        http_response_code(404);
        echo "404 Not Found";
    }
}
