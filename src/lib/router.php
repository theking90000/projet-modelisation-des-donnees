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
        
        $this->routes[] = [
            'method' => $method,
            'pattern' => $fullPattern,
            'handler' => $handler,
            'middleware' => $fullMiddleware
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
    
    public function dispatch($requestUri, $requestMethod) {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }
            
            $pattern = '#^' . $route['pattern'] . '$#';
            if (preg_match($pattern, $requestUri, $matches)) {
                array_shift($matches);
                
                // Execute middleware
                foreach ($route['middleware'] as $middlewareClass) {
                    $middleware = new $middlewareClass();
                    if (!$middleware->handle()) {
                        return; // Middleware blocked the request
                    }
                }
                
                return $this->callHandler($route['handler'], $matches);
            }
        }
        
        $this->handle404();
    }
    
    private function callHandler($handler, $params) {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }
        
        if (is_string($handler)) {
            list($controller, $method) = explode('@', $handler);
            if (class_exists($controller)) {
                $instance = new $controller();
                if (method_exists($instance, $method)) {
                    return call_user_func_array([$instance, $method], $params);
                }
            }
        }
        
        throw new Exception("Handler not found");
    }
    
    private function handle404() {
        http_response_code(404);
        
        if(is_callable($this->notFoundHandler)) {
            return call_user_func_array($this->notFoundHandler, []);
        } else {
            echo "404 - Page Not Found";
        }
    }

    private function handle500($e) {
        http_response_code(500);
        
        if(is_callable($this->errorHandler)) {
            return call_user_func_array($this->errorHandler, [$e]);
        } else {
            echo "500 - An error occurred";
        }
    }

}

function create_handler($file) {
    $handler = function () use ($file) {
        require __DIR__ . '/..\/' . $file;
    };

    return $handler;
}

function create_render_handle($file, $params = []) {
    $handler = function () use ($file, $params) {
        require_once __DIR__ . '/../template/layout.php';
        
        render_page($file, $params);
    };

    return $handler;
}