<?php
// FROM : https://webreference.com/php/web-development/routing/
class Router {
    private $routes = [];
    private $middleware = [];
    private $groupStack = [];

    private $notFoundHandler;
    private $errorHandler;
    
    public function group($prefix, $callback, $middleware = []) {
        $this->groupStack[] = [
            'prefix' => $prefix,
            'middleware' => $middleware
        ];
        
        call_user_func($callback, $this);
        
        array_pop($this->groupStack);
    }
    
    public function get($pattern, $handler, $middleware = []) {
        $this->addRoute('GET', $pattern, $handler, $middleware);
    }
    
    public function post($pattern, $handler, $middleware = []) {
        $this->addRoute('POST', $pattern, $handler, $middleware);
    }

    public function notFound($handler) {
        $this->notFoundHandler = $handler;
    }

    public function error($handler) {
        $this->errorHandler = $handler;
    }
    
    private function addRoute($method, $pattern, $handler, $middleware = []) {
        $fullPattern = $this->buildFullPattern($pattern);
        $fullMiddleware = $this->buildFullMiddleware($middleware);

        $compiledPattern = $this->compilePattern($fullPattern);
        
        $route = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'compiled' => $compiledPattern['pattern'],
            'params' => $compiledPattern['params'],
            'handler' => $handler
        ];
        
        $this->routes[] = [
            'method' => $method,
            'pattern' => $fullPattern,
            'handler' => $handler,
            'middleware' => $fullMiddleware,
            'compiled' => $compiledPattern['pattern'],
            'params' => $compiledPattern['params'],
        ];
    }
    
    private function buildFullPattern($pattern) {
        $prefix = '';
        
        foreach ($this->groupStack as $group) {
            $prefix .= $group['prefix'];
        }
        
        return $prefix . $pattern;
    }
    
    private function buildFullMiddleware($middleware) {
        $fullMiddleware = [];
        
        foreach ($this->groupStack as $group) {
            $fullMiddleware = array_merge($fullMiddleware, $group['middleware']);
        }
        
        return array_merge($fullMiddleware, $middleware);
    }

    private function compilePattern($pattern) {
        $params = [];
        
        // Replace named parameters {param} with regex
        $compiled = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            function($matches) use (&$params) {
                $params[] = $matches[1];
                return '([^/]+)';
            },
            $pattern
        );
        
        // Replace optional parameters {param?}
        $compiled = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\?\}/',
            function($matches) use (&$params) {
                $params[] = $matches[1];
                return '([^/]*)';
            },
            $compiled
        );
        
        return [
            'pattern' => '#^' . $compiled . '$#',
            'params' => $params
        ];
    }
    
    public function dispatch($requestUri, $requestMethod) {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            //$pattern = '#^' . $route['pattern'] . '$#';
            if (preg_match($route['compiled'], $requestUri, $matches)) {
                array_shift($matches);

                $params = [];
                foreach ($route['params'] as $index => $name) {
                    $params[$name] = $matches[$index] ?? null;
                }
                
                // Execute middleware
                foreach ($route['middleware'] as $middlewareClass) {
                    $middleware = new $middlewareClass();
                    if (!$middleware->handle($params)) {
                        return; // Middleware blocked the request
                    }
                }
                
                return $this->callHandler($route['handler'], $params);
            }
        }
        
        $this->handle404();
    }
    
    private function callHandler($handler, $params) {
        if (is_callable($handler)) {
            return call_user_func($handler, $params);
        }
        
        if (is_string($handler)) {
            list($controller, $method) = explode('@', $handler);
            if (class_exists($controller)) {
                $instance = new $controller();
                if (method_exists($instance, $method)) {
                    return call_user_func([$instance, $method], $params);
                }
            }
        }
        
        throw new Exception("Handler not found");
    }
    
    private function handle404() {
        http_response_code(404);
        
        if(is_callable($this->notFoundHandler)) {
            return call_user_func($this->notFoundHandler, []);
        } else {
            echo "404 - Page Not Found";
        }
    }

    private function handle500($e) {
        http_response_code(500);
        
        if(is_callable($this->errorHandler)) {
            return call_user_func($this->errorHandler, [$e]);
        } else {
            echo "500 - An error occurred";
        }
    }

}

function create_handler($file) {
    $handler = function ($params) use ($file) {
        extract($params);
        require __DIR__ . '/../' . $file;
    };

    return $handler;
}

function create_render_handle($file, $params = []) {
    $handler = function ($p2) use ($file, $params) {
        require_once __DIR__ . '/../template/layout.php';
        $params = array_merge($params, $p2);
        render_page($file, $params, !isset($_GET["noLayout"]));
    };

    return $handler;
}