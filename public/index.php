<?php

/**
 * PHPCS API - Entry point for all API requests.
 */

// Set error reporting in development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Import classes
use PhpcsApi\Config;
use PhpcsApi\Request;
use PhpcsApi\Response;
use PhpcsApi\Router;
use PhpcsApi\PhpcsService;

// Load configuration
if (file_exists(__DIR__ . '/../config.php')) {
    Config::load(__DIR__ . '/../config.php');
}

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 204 No Content');
    exit;
}

// Initialize router
$router = new Router();
$request = Request::fromGlobals();

// Define routes
$router->addRoute('POST', '/analyze', function (Request $request) {
    // Validate request
    if (!isset($request->body['code'])) {
        return Response::badRequest('Missing code parameter');
    }

    $code = $request->body['code'];
    $standard = $request->body['standard'] ?? 'PSR12';
    $options = $request->body['options'] ?? [];

    // Analyze code
    $phpcsService = new PhpcsService();
    $result = $phpcsService->analyze($code, $standard, $options);

    return Response::json($result);
});

$router->addRoute('GET', '/standards', function (Request $request) {
    $phpcsService = new PhpcsService();
    $standards = $phpcsService->getStandards();

    return Response::json($standards);
});

$router->addRoute('GET', '/health', function (Request $request) {
    $phpcsService = new PhpcsService();
    
    return Response::json([
        'status' => 'ok',
        'version' => Config::get('app_version'),
        'phpcs_version' => $phpcsService->getVersion(),
    ]);
});

// Handle the request
$response = $router->dispatch($request);

// Send the response
$response->send();
