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
use PhpcsApi\Logger;
use PhpcsApi\CacheService;

// Load configuration
if (file_exists(__DIR__ . '/../config.php')) {
    Config::load(__DIR__ . '/../config.php');
}

// Initialize services
$logger = new Logger();
$authService = new AuthService(Config::get('auth.keys_file'));
$cacheService = new CacheService();
$phpcsService = new PhpcsService($cacheService, $logger);

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
$router->addRoute('POST', '/v1/analyze', function (Request $request) use ($phpcsService, $authService, $logger) {
    // Check if authentication is enabled
    if (Config::get('auth.enabled', true)) {
        // Extract API key from request
        $apiKey = $authService->extractKeyFromRequest($request);
        
        // Check if API key is provided
        if (empty($apiKey)) {
            return Response::json([
                'error' => 'Authentication required',
                'message' => 'API key is required for this endpoint',
            ], 401)->withHeader('WWW-Authenticate', 'Bearer');
        }
        
        // Validate API key with analyze scope
        if (!$authService->validateKey($apiKey, 'analyze')) {
            $logger->logSecurityEvent('unauthorized_access', 'Invalid API key for analyze endpoint', [
                'key_prefix' => substr($apiKey, 0, 8) . '...',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
            
            return Response::json([
                'error' => 'Invalid API key',
                'message' => 'The provided API key is invalid or does not have the required permissions',
            ], 403);
        }
    }
    
    // Validate request
    if (!isset($request->body['code'])) {
        return Response::badRequest('Missing code parameter');
    }

    // Validate code length to prevent DoS attacks
    if (strlen($request->body['code']) > 1000000) { // 1MB limit
        $logger->logSecurityEvent('dos_attempt', 'Code exceeds maximum size limit', [
            'size' => strlen($request->body['code']),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
        
        return Response::badRequest('Code exceeds maximum size limit (1MB)');
    }

    // Sanitize inputs
    $code = $request->body['code'];
    $standard = isset($request->body['standard']) ? 
        preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $request->body['standard']) : 'PSR12';
    $phpVersion = isset($request->body['phpVersion']) ? 
        preg_replace('/[^0-9\.\-,]/', '', $request->body['phpVersion']) : null;
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

    // Log PHP version testing if applicable
    if ($phpVersion !== null && 
        (strpos(strtolower($standard), 'phpcompatibility') !== false || 
         strpos(strtolower($standard), 'php-compatibility') !== false)) {
        $logger->info('PHP version compatibility testing requested', [
            'standard' => $standard,
            'php_version' => $phpVersion,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
    }

    // Analyze code
    $result = $phpcsService->analyze($code, $standard, $phpVersion, $filteredOptions);

    return Response::json([
        'success' => true,
        'results' => $result
    ]);
});

$router->addRoute('GET', '/v1/standards', function (Request $request) use ($phpcsService, $authService, $logger) {
    // Check if authentication is enabled
    if (Config::get('auth.enabled', true)) {
        // Extract API key from request
        $apiKey = $authService->extractKeyFromRequest($request);
        
        // Check if API key is provided
        if (empty($apiKey)) {
            return Response::json([
                'error' => 'Authentication required',
                'message' => 'API key is required for this endpoint',
            ], 401)->withHeader('WWW-Authenticate', 'Bearer');
        }
        
        // Validate API key with standards scope
        if (!$authService->validateKey($apiKey, 'standards')) {
            $logger->logSecurityEvent('unauthorized_access', 'Invalid API key for standards endpoint', [
                'key_prefix' => substr($apiKey, 0, 8) . '...',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
            
            return Response::json([
                'error' => 'Invalid API key',
                'message' => 'The provided API key is invalid or does not have the required permissions',
            ], 403);
        }
    }
    
    $startTime = microtime(true);
    $standards = $phpcsService->getStandards();
    $duration = microtime(true) - $startTime;
    
    // Log performance
    $logger->logPerformance('standards', $duration, [
        'count' => count($standards),
    ]);
    
    return Response::json([
        'success' => true,
        'standards' => $standards
    ]);
});

$router->addRoute('GET', '/v1/health', function (Request $request) use ($phpcsService, $cacheService) {
    return Response::json([
        'status' => 'ok',
        'version' => Config::get('app_version'),
        'phpcs_version' => $phpcsService->getVersion(),
        'timestamp' => time(),
        'cache' => $cacheService->getStats(),
    ]);
});

// Cache management endpoint (admin only)
$router->addRoute('POST', '/v1/cache/clear', function (Request $request) use ($authService, $cacheService, $logger) {
    // Check if authentication is enabled
    if (Config::get('auth.enabled', true)) {
        // Extract API key from request
        $apiKey = $authService->extractKeyFromRequest($request);
        
        // Check if API key is provided
        if (empty($apiKey)) {
            return Response::json([
                'error' => 'Authentication required',
                'message' => 'API key is required for this endpoint',
            ], 401)->withHeader('WWW-Authenticate', 'Bearer');
        }
        
        // Validate API key with admin scope
        if (!$authService->validateKey($apiKey, 'admin')) {
            $logger->logSecurityEvent('unauthorized_access', 'Invalid API key for cache clear endpoint', [
                'key_prefix' => substr($apiKey, 0, 8) . '...',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
            
            return Response::json([
                'error' => 'Invalid API key',
                'message' => 'The provided API key is invalid or does not have the required permissions',
            ], 403);
        }
    }
    
    // Get cache stats before clearing
    $statsBefore = $cacheService->getStats();
    
    // Clear cache
    $startTime = microtime(true);
    $success = $cacheService->clear();
    $duration = microtime(true) - $startTime;
    
    // Log performance
    $logger->logPerformance('cache_clear', $duration, [
        'success' => $success,
        'items_cleared' => $statsBefore['count'] ?? 0,
        'size_cleared' => $statsBefore['size'] ?? 0,
    ]);
    
    // Log admin action
    $logger->info('Cache cleared by admin', [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'items_cleared' => $statsBefore['count'] ?? 0,
    ]);
    
    return Response::json([
        'success' => $success,
        'message' => $success ? 'Cache cleared successfully' : 'Failed to clear cache',
    ]);
});

// Cache stats endpoint (admin only)
$router->addRoute('GET', '/v1/cache/stats', function (Request $request) use ($authService, $cacheService, $logger) {
    // Check if authentication is enabled
    if (Config::get('auth.enabled', true)) {
        // Extract API key from request
        $apiKey = $authService->extractKeyFromRequest($request);
        
        // Check if API key is provided
        if (empty($apiKey)) {
            return Response::json([
                'error' => 'Authentication required',
                'message' => 'API key is required for this endpoint',
            ], 401)->withHeader('WWW-Authenticate', 'Bearer');
        }
        
        // Validate API key with admin scope
        if (!$authService->validateKey($apiKey, 'admin')) {
            $logger->logSecurityEvent('unauthorized_access', 'Invalid API key for cache stats endpoint', [
                'key_prefix' => substr($apiKey, 0, 8) . '...',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
            
            return Response::json([
                'error' => 'Invalid API key',
                'message' => 'The provided API key is invalid or does not have the required permissions',
            ], 403);
        }
    }
    
    // Get cache stats
    $startTime = microtime(true);
    $stats = $cacheService->getStats();
    $duration = microtime(true) - $startTime;
    
    // Log performance
    $logger->logPerformance('cache_stats', $duration, [
        'cache_size' => $stats['size'] ?? 0,
        'cache_count' => $stats['count'] ?? 0,
    ]);
    
    return Response::json([
        'success' => true,
        'stats' => $stats,
    ]);
});

// API key management endpoints (admin only)
$router->addRoute('POST', '/v1/keys/generate', function (Request $request) use ($authService, $logger) {
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
    
    // Log admin action
    $logger->info('API key generated', [
        'key_prefix' => substr($key, 0, 8) . '...',
        'name' => $name,
        'scopes' => implode(',', $scopes),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    ]);
    
    return Response::json([
        'success' => true,
        'key' => $key,
        'data' => $authService->getKeyData($key),
    ]);
});

// Record start time for request duration calculation
$startTime = microtime(true);

// Process the request through the middleware pipeline
$response = $pipeline($request);

// Send the response
$response->send();

// Extract request headers
$headers = [];
foreach ($_SERVER as $name => $value) {
    if (strpos($name, 'HTTP_') === 0) {
        $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
        $headers[$headerName] = $value;
    }
}

// Log the request with enhanced information
$logger->logRequest(
    $request->method,
    $request->path,
    $response->statusCode,
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    $_SERVER['HTTP_USER_AGENT'] ?? null,
    microtime(true) - $startTime,
    $headers,
    $request->body ?? [] // Ensure we always pass an array, even for GET requests
);

// Add request ID to response headers for debugging
$requestId = $logger->getRequestId();
header('X-Request-ID: ' . $requestId);
