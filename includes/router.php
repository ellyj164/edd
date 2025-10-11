<?php
/**
 * Central Router for EPD Platform
 * Implements front controller pattern with clean URLs
 */

declare(strict_types=1);

class Router {
    private array $routes = [];
    private array $middleware = [];
    
    public function __construct() {
        $this->setupRoutes();
    }
    
    private function setupRoutes(): void {
        // Clean routes for seller functionality
        $this->get('/seller/register', 'seller-register.php');
        $this->get('/seller/onboarding', 'seller-onboarding.php');
        $this->get('/seller/center', 'seller-center.php');
        $this->get('/seller/*', 'seller-center.php'); // Catch-all for seller routes
        
        // Admin routes
        $this->get('/admin', 'admin/index.php');
        $this->get('/admin/*', 'admin/index.php');
        
        // Account routes
        $this->get('/account', 'account.php');
        $this->get('/account/*', 'account.php');
        
        // Health endpoints
        $this->get('/healthz', 'healthz.php');
        $this->get('/readyz', 'readyz.php');
        
        // Auth routes
        $this->get('/login', 'login.php');
        $this->get('/register', 'register.php');
        $this->get('/logout', 'logout.php');
        
        // Product routes
        $this->get('/product/{id}', 'product.php');
        $this->get('/product/{slug}', 'product.php');
        $this->get('/products', 'products.php');
        $this->get('/category/{slug}', 'category.php');
        
        // Shopping cart and wishlist
        $this->get('/cart', 'cart.php');
        $this->get('/wishlist', 'wishlist.php');
        $this->get('/saved', 'saved.php');
        
        // Legacy redirects (301)
        $this->redirect('/vendor/register.php', '/seller/register');
        $this->redirect('/vendor-onboarding.php', '/seller/onboarding');
        $this->redirect('/vendor-center.php', '/seller/center');
    }
    
    public function get(string $pattern, string $handler): void {
        $this->routes['GET'][$pattern] = $handler;
    }
    
    public function post(string $pattern, string $handler): void {
        $this->routes['POST'][$pattern] = $handler;
    }
    
    public function redirect(string $from, string $to): void {
        $this->routes['REDIRECT'][$from] = $to;
    }
    
    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';
        
        // Handle redirects first
        if (isset($this->routes['REDIRECT'][$uri])) {
            $this->sendRedirect($this->routes['REDIRECT'][$uri]);
            return;
        }
        
        // Find matching route
        $handler = $this->findRoute($method, $uri);
        
        if ($handler) {
            $this->executeHandler($handler, $uri);
        } else {
            $this->show404();
        }
    }
    
    private function findRoute(string $method, string $uri): ?string {
        if (!isset($this->routes[$method])) {
            return null;
        }
        
        foreach ($this->routes[$method] as $pattern => $handler) {
            if ($this->matchPattern($pattern, $uri)) {
                return $handler;
            }
        }
        
        return null;
    }
    
    private function matchPattern(string $pattern, string $uri): bool {
        // Exact match
        if ($pattern === $uri) {
            return true;
        }
        
        // Wildcard match (ending with /*)
        if (str_ends_with($pattern, '/*')) {
            $prefix = rtrim($pattern, '/*');
            if ($prefix === '' || str_starts_with($uri, $prefix)) {
                return true;
            }
        }
        
        // Parameter match (with {param})
        if (str_contains($pattern, '{')) {
            $regexPattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
            $regexPattern = '#^' . $regexPattern . '$#';
            
            if (preg_match($regexPattern, $uri, $matches)) {
                // Store parameters for later use
                $_GET['route_params'] = array_slice($matches, 1);
                return true;
            }
        }
        
        return false;
    }
    
    private function executeHandler(string $handler, string $uri): void {
        $handlerPath = $this->resolveHandlerPath($handler);
        
        if (!file_exists($handlerPath)) {
            error_log("Handler file not found: {$handlerPath}");
            $this->show404();
            return;
        }
        
        // Set route info for handlers to use
        $_SERVER['ROUTE_URI'] = $uri;
        $_SERVER['ROUTE_HANDLER'] = $handler;
        
        // Include the handler
        require $handlerPath;
    }
    
    private function resolveHandlerPath(string $handler): string {
        // If absolute path, use as-is
        if (str_starts_with($handler, '/')) {
            return __DIR__ . $handler;
        }
        
        // Relative to document root
        return __DIR__ . '/' . ltrim($handler, './');
    }
    
    private function sendRedirect(string $to): void {
        header("Location: {$to}", true, 301);
        exit;
    }
    
    private function show404(): void {
        if (file_exists(__DIR__ . '/404.php')) {
            http_response_code(404);
            require __DIR__ . '/404.php';
        } else {
            http_response_code(404);
            echo "404 - Page Not Found";
        }
        exit;
    }
}

// URL helper functions
if (!function_exists('url')) {
    function url(string $path = ''): string {
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost';
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string {
        // Simple route helper - could be expanded later
        $routes = [
            'home' => '/',
            'login' => '/login',
            'register' => '/register',
            'admin' => '/admin',
            'seller.register' => '/seller/register',
            'seller.onboarding' => '/seller/onboarding',
            'seller.center' => '/seller/center',
            'account' => '/account',
            'products' => '/products',
            'cart' => '/cart',
            'wishlist' => '/wishlist'
        ];
        
        $path = $routes[$name] ?? $name;
        
        // Replace parameters
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', (string)$value, $path);
        }
        
        return url($path);
    }
}

// Initialize and dispatch
if (!defined('ROUTER_INITIALIZED')) {
    define('ROUTER_INITIALIZED', true);
    $router = new Router();
    $router->dispatch();
}