<?php

/**
 * PHPCS API - Entry point for all API requests.
 * 
 * This is a high-security, high-performance API for running PHP_CodeSniffer.
 */

// Set error reporting based on environment
$isProduction = getenv('APP_ENV') === 'production';
if (!$isProduction) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Import classes
use PhpcsApi\Config;
use PhpcsApi\Request;
use PhpcsApi\Response;
use PhpcsApi\Router;
use PhpcsApi\PhpcsService;
use PhpcsApi\AuthService;
use PhpcsApi\AuthMiddleware;
use PhpcsApi\SecurityMiddleware;

// Load configuration
if (file_exists(__DIR__ . '/../config.php')) {
    Config::load(__DIR__ . '/../config.php');
}

// Initialize services
$authService = new AuthService(Config::get('auth.keys_file'));
$phpcsService = new PhpcsService();

// Initialize middleware
$authMiddleware = new AuthMiddleware(
    $authService,
    Config::get('auth.protected_paths', []),
    Config::get('auth.path_scopes', [])
);
$securityMiddleware = new SecurityMiddleware(Config::get('security', []));

// Initialize router and request
$router = new Router();
$request = Request::fromGlobals();

// Create middleware pipeline
$pipeline = function (Request $request) use ($router, $authMiddleware, $securityMiddleware) {
    // Apply security middleware
    return $securityMiddleware->process($request, function (Request $request) use ($router, $authMiddleware) {
        // Apply authentication middleware
        return $authMiddleware->process($request, function (Request $request) use ($router) {
            // Dispatch to router
            return $router->dispatch($request);
        });
    });
};

// Define routes
$router->addRoute('POST', '/analyze', function (Request $request) use ($phpcsService) {
    // Validate request
    if (!isset($request->body['code'])) {
        return Response::badRequest('Missing code parameter');
    }

    // Validate code length to prevent DoS attacks
    if (strlen($request->body['code']) > 1000000) { // 1MB limit
        return Response::badRequest('Code exceeds maximum size limit (1MB)');
    }

    // Sanitize inputs
    $code = $request->body['code'];
    $standard = isset($request->body['standard']) ? 
        preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $request->body['standard']) : 'PSR12';
    $options = $request->body['options'] ?? [];
    
    // Validate options
    $allowedOptions = [
        'report', 'severity', 'error-severity', 'warning-severity', 
        'tab-width', 'encoding', 'extensions', 'sniffs', 'exclude'
    ];
    
    $filteredOptions = [];
    foreach ($options as $key => $value) {
        if (in_array($key, $allowedOptions)) {
            $filteredOptions[$key] = $value;
        }
    }

    // Analyze code
    $result = $phpcsService->analyze($code, $standard, $filteredOptions);

    return Response::json($result);
});

$router->addRoute('GET', '/standards', function (Request $request) use ($phpcsService) {
    $standards = $phpcsService->getStandards();
    return Response::json($standards);
});

$router->addRoute('GET', '/health', function (Request $request) use ($phpcsService) {
    return Response::json([
        'status' => 'ok',
        'version' => Config::get('app_version'),
        'phpcs_version' => $phpcsService->getVersion(),
        'timestamp' => time(),
    ]);
});

// API key management endpoints (admin only)
$router->addRoute('POST', '/keys/generate', function (Request $request) use ($authService) {
    // This endpoint should be protected by additional authentication
    // For now, we'll only allow it in development mode
    if (getenv('APP_ENV') === 'production') {
        return Response::notFound();
    }
    
    $name = $request->body['name'] ?? 'API Key ' . date('Y-m-d H:i:s');
    $scopes = $request->body['scopes'] ?? ['analyze', 'standards'];
    $expires = $request->body['expires'] ?? null;
    
    $data = [
        'name' => $name,
        'scopes' => $scopes,
    ];
    
    if ($expires) {
        $data['expires'] = (int) $expires;
    }
    
    $key = $authService->generateKey($data);
    
    return Response::json([
        'success' => true,
        'key' => $key,
        'data' => $authService->getKeyData($key),
    ]);
});

// Process the request through the middleware pipeline
$response = $pipeline($request);

// Send the response
$response->send();

// Log request (in production)
if ($isProduction) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'method' => $request->method,
        'path' => $request->path,
        'status' => $response->statusCode,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    ];
    
    // Don't log health checks
    if ($request->path !== '/health') {
        file_put_contents(
            __DIR__ . '/../logs/access.log',
            json_encode($logData) . "\n",
            FILE_APPEND
        );
    }
}
