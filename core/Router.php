<?php
declare(strict_types=1);

class Router {
    private $routes = [];
    private $app;
    
    public function __construct(App $app) {
        $this->app = $app;
    }
    
    public function get(string $path, callable $callback): void {
        $this->routes['GET'][$path] = $callback;
    }
    
    public function post(string $path, callable $callback): void {
        $this->routes['POST'][$path] = $callback;
    }
    
    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = rtrim($path, '/') ?: '/';
        
        // Удаляем базовый путь если проект в поддиректории
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/') {
            $path = substr($path, strlen($basePath)) ?: '/';
        }
        
        if (isset($this->routes[$method][$path])) {
            call_user_func($this->routes[$method][$path], $this->app);
            return;
        }
        
        // Динамические маршруты (например, /product/slug)
        foreach ($this->routes[$method] as $route => $callback) {
            if (preg_match('#^' . preg_replace('/\{[^}]+\}/', '([^/]+)', $route) . '$#', $path, $matches)) {
                array_shift($matches);
                call_user_func_array($callback, array_merge([$this->app], $matches));
                return;
            }
        }
        
        // 404
        http_response_code(404);
        echo '404 Not Found';
    }
    
    public function url(string $path, array $params = []): string {
        $url = $path;
        if ($params) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }
}
?>