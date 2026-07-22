<?php

namespace App\Core;

class Router
{
    protected $routes = [];

    public function get($uri, $action, $middleware = [])
    {
        $this->addRoute('GET', $uri, $action, $middleware);
    }

    public function post($uri, $action, $middleware = [])
    {
        $this->addRoute('POST', $uri, $action, $middleware);
    }

    protected function addRoute($method, $uri, $action, $middleware = [])
    {
        // Convert route params like {id} into regex capturing groups
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $uri);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'action' => $action,
            'middleware' => $middleware
        ];
    }

    public function dispatch($uri, $method)
    {
        $parsedUri = parse_url($uri, PHP_URL_PATH);
        // Remove trailing slashes for matching
        $parsedUri = rtrim($parsedUri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $parsedUri, $matches)) {
                // Execute Middleware
                foreach ($route['middleware'] as $middleware) {
                    (new $middleware)->handle();
                }

                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return $this->executeAction($route['action'], $params);
            }
        }

        // 404 Not Found
        http_response_code(404);
        echo "404 - Not Found";
    }

    protected function executeAction($action, $params)
    {
        if (is_callable($action)) {
            return call_user_func_array($action, array_values($params));
        }

        if (is_array($action)) {
            $controllerName = $action[0];
            $method = $action[1];
            
            if (class_exists($controllerName)) {
                $controller = new $controllerName();
                if (method_exists($controller, $method)) {
                    return call_user_func_array([$controller, $method], array_values($params));
                }
            }
        }
        
        throw new \Exception("Action not properly configured for route");
    }
}
