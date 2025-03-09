#!/usr/bin/env php
<?php

/**
 * PHPCS API - API Key Management Tool
 * 
 * This script provides a command-line interface for managing API keys.
 * 
 * Usage:
 *   php bin/manage-keys.php [command] [options]
 * 
 * Commands:
 *   list                     List all API keys
 *   generate [--name=NAME] [--scopes=SCOPES] [--expires=TIMESTAMP]
 *                           Generate a new API key
 *   revoke KEY              Revoke an API key
 *   info KEY                Show information about an API key
 * 
 * Options:
 *   --name=NAME             Name or description for the API key
 *   --scopes=SCOPES         Comma-separated list of scopes (default: analyze,standards)
 *   --expires=TIMESTAMP     Expiration timestamp (default: never)
 */

// Ensure this script is being run from the command line
if (PHP_SAPI !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

// Load Composer autoloader
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
];

foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        break;
    }
}

use PhpcsApi\AuthService;

// Parse command line arguments
$options = getopt('', ['name:', 'scopes:', 'expires:']);
$args = array_slice($argv, 1);
$command = $args[0] ?? 'help';

// Initialize AuthService
$authService = new AuthService();

// Process command
switch ($command) {
    case 'list':
        listKeys($authService);
        break;
        
    case 'generate':
        generateKey($authService, $options);
        break;
        
    case 'revoke':
        revokeKey($authService, $args[1] ?? null);
        break;
        
    case 'info':
        showKeyInfo($authService, $args[1] ?? null);
        break;
        
    case 'help':
    default:
        showHelp();
        break;
}

/**
 * List all API keys.
 *
 * @param AuthService $authService Auth service instance.
 *
 * @return void
 */
function listKeys(AuthService $authService): void
{
    $keys = $authService->getKeys();
    
    if (empty($keys)) {
        echo "No API keys found.\n";
        return;
    }
    
    echo "API Keys:\n";
    echo str_repeat('-', 80) . "\n";
    echo sprintf("%-48s %-20s %-10s\n", 'Key', 'Name', 'Status');
    echo str_repeat('-', 80) . "\n";
    
    foreach ($keys as $key => $data) {
        $name = $data['name'] ?? 'Unnamed';
        $status = isset($data['active']) && $data['active'] === false ? 'Revoked' : 'Active';
        
        if (isset($data['expires']) && $data['expires'] < time()) {
            $status = 'Expired';
        }
        
        echo sprintf("%-48s %-20s %-10s\n", $key, $name, $status);
    }
}

/**
 * Generate a new API key.
 *
 * @param AuthService $authService Auth service instance.
 * @param array       $options     Command options.
 *
 * @return void
 */
function generateKey(AuthService $authService, array $options): void
{
    $data = [];
    
    // Set name if provided
    if (isset($options['name'])) {
        $data['name'] = $options['name'];
    }
    
    // Set scopes if provided
    if (isset($options['scopes'])) {
        $data['scopes'] = explode(',', $options['scopes']);
    }
    
    // Set expiration if provided
    if (isset($options['expires'])) {
        $data['expires'] = (int) $options['expires'];
    }
    
    // Generate key
    $key = $authService->generateKey($data);
    
    echo "API key generated successfully:\n";
    echo $key . "\n";
    
    // Show additional info
    echo "\nKey details:\n";
    $keyData = $authService->getKeyData($key);
    
    echo "Name: " . ($keyData['name'] ?? 'Unnamed') . "\n";
    echo "Scopes: " . implode(', ', $keyData['scopes'] ?? []) . "\n";
    
    if (isset($keyData['expires'])) {
        echo "Expires: " . date('Y-m-d H:i:s', $keyData['expires']) . "\n";
    } else {
        echo "Expires: Never\n";
    }
    
    echo "\nStore this key securely. It will not be shown again.\n";
}

/**
 * Revoke an API key.
 *
 * @param AuthService $authService Auth service instance.
 * @param string|null $key         API key to revoke.
 *
 * @return void
 */
function revokeKey(AuthService $authService, ?string $key): void
{
    if (empty($key)) {
        echo "Error: API key is required.\n";
        exit(1);
    }
    
    if ($authService->revokeKey($key)) {
        echo "API key revoked successfully.\n";
    } else {
        echo "Error: API key not found or already revoked.\n";
        exit(1);
    }
}

/**
 * Show information about an API key.
 *
 * @param AuthService $authService Auth service instance.
 * @param string|null $key         API key to show information for.
 *
 * @return void
 */
function showKeyInfo(AuthService $authService, ?string $key): void
{
    if (empty($key)) {
        echo "Error: API key is required.\n";
        exit(1);
    }
    
    $keyData = $authService->getKeyData($key);
    
    if (empty($keyData)) {
        echo "Error: API key not found.\n";
        exit(1);
    }
    
    echo "API Key: $key\n";
    echo "Name: " . ($keyData['name'] ?? 'Unnamed') . "\n";
    echo "Created: " . date('Y-m-d H:i:s', $keyData['created'] ?? time()) . "\n";
    
    if (isset($keyData['expires'])) {
        echo "Expires: " . date('Y-m-d H:i:s', $keyData['expires']) . "\n";
    } else {
        echo "Expires: Never\n";
    }
    
    echo "Status: " . (isset($keyData['active']) && $keyData['active'] === false ? 'Revoked' : 'Active') . "\n";
    echo "Scopes: " . implode(', ', $keyData['scopes'] ?? []) . "\n";
    
    if (!empty($keyData['metadata'])) {
        echo "Metadata:\n";
        
        foreach ($keyData['metadata'] as $key => $value) {
            echo "  $key: $value\n";
        }
    }
}

/**
 * Show help information.
 *
 * @return void
 */
function showHelp(): void
{
    echo "PHPCS API - API Key Management Tool\n\n";
    echo "Usage:\n";
    echo "  php bin/manage-keys.php [command] [options]\n\n";
    echo "Commands:\n";
    echo "  list                     List all API keys\n";
    echo "  generate [--name=NAME] [--scopes=SCOPES] [--expires=TIMESTAMP]\n";
    echo "                           Generate a new API key\n";
    echo "  revoke KEY              Revoke an API key\n";
    echo "  info KEY                Show information about an API key\n\n";
    echo "Options:\n";
    echo "  --name=NAME             Name or description for the API key\n";
    echo "  --scopes=SCOPES         Comma-separated list of scopes (default: analyze,standards)\n";
    echo "  --expires=TIMESTAMP     Expiration timestamp (default: never)\n";
}
