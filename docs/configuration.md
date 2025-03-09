# PHPCS API Configuration Guide

This document provides a comprehensive guide to all configuration options available in the PHPCS API.

## Overview

The PHPCS API is configured through a central configuration file (`config.php`), which contains settings for various aspects of the API, including authentication, security, PHPCS, caching, and logging.

## Configuration File

The configuration file is located at the root of the project:

```
/config.php
```

A sample configuration file is provided at:

```
/config.php.sample
```

To set up the configuration, copy the sample file and modify it according to your needs:

```bash
cp config.php.sample config.php
```

## Configuration Structure

The configuration file is structured as a PHP array with nested sections for different aspects of the API:

```php
<?php

return [
    'app' => [
        // Application settings
    ],
    'auth' => [
        // Authentication settings
    ],
    'security' => [
        // Security settings
    ],
    'phpcs' => [
        // PHPCS settings
    ],
    'cache' => [
        // Cache settings
    ],
    'logging' => [
        // Logging settings
    ],
];
```

## Application Settings

```php
'app' => [
    'name' => 'PHPCS API',
    'version' => '1.0.0',
    'environment' => 'production', // 'development', 'testing', 'production'
    'debug' => false,
    'timezone' => 'UTC',
    'base_url' => 'https://api.example.com',
],
```

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `name` | string | 'PHPCS API' | The name of the application |
| `version` | string | '1.0.0' | The version of the application |
| `environment` | string | 'production' | The environment the application is running in |
| `debug` | boolean | false | Whether debug mode is enabled |
| `timezone` | string | 'UTC' | The timezone for the application |
| `base_url` | string | null | The base URL for the API |

## Authentication Settings

```php
'auth' => [
    'enabled' => true,
    'key_file' => __DIR__ . '/data/keys.json',
    'default_ttl' => 31536000, // 1 year in seconds
    'hash_algo' => PASSWORD_BCRYPT,
    'hash_options' => [
        'cost' => 12,
    ],
    'public_paths' => [
        '/health',
        '/metrics',
    ],
],
```

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `enabled` | boolean | true | Whether authentication is enabled |
| `key_file` | string | __DIR__ . '/data/keys.json' | Path to the file storing API keys |
| `default_ttl` | integer | 31536000 | Default time-to-live for API keys (in seconds) |
| `hash_algo` | constant | PASSWORD_BCRYPT | Algorithm used for hashing API keys |
| `hash_options` | array | ['cost' => 12] | Options for the hash algorithm |
| `public_paths` | array | ['/health', '/metrics'] | Paths that don't require authentication |

## Security Settings

```php
'security' => [
    'rate_limit' => [
        'enabled' => true,
        'ip_minute_limit' => 60,
        'ip_hour_limit' => 1000,
        'key_minute_limit' => 30,
        'whitelist' => ['127.0.0.1', '::1'],
    ],
    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self'; object-src 'none'; frame-ancestors 'none'",
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ],
    'cors' => [
        'enabled' => true,
        'allow_origins' => ['*'], // In development
        'allow_methods' => ['GET', 'POST', 'OPTIONS'],
        'allow_headers' => ['Content-Type', 'Authorization', 'X-API-Key'],
        'expose_headers' => ['X-Request-ID', 'X-RateLimit-Limit', 'X-RateLimit-Remaining', 'X-RateLimit-Reset'],
        'max_age' => 86400, // 24 hours
    ],
    'max_request_size' => 2 * 1024 * 1024, // 2MB
    'ip_blacklist' => [],
],
```

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `rate_limit.enabled` | boolean | true | Whether rate limiting is enabled |
| `rate_limit.ip_minute_limit` | integer | 60 | Maximum requests per minute per IP |
| `rate_limit.ip_hour_limit` | integer | 1000 | Maximum requests per hour per IP |
| `rate_limit.key_minute_limit` | integer | 30 | Maximum requests per minute per API key |
| `rate_limit.whitelist` | array | ['127.0.0.1', '::1'] | IPs exempt from rate limiting |
| `headers` | array | See above | Security headers to include in responses |
| `cors.enabled` | boolean | true | Whether CORS is enabled |
| `cors.allow_origins` | array | ['*'] | Allowed origins for CORS |
| `cors.allow_methods` | array | ['GET', 'POST', 'OPTIONS'] | Allowed methods for CORS |
| `cors.allow_headers` | array | ['Content-Type', 'Authorization', 'X-API-Key'] | Allowed headers for CORS |
| `cors.expose_headers` | array | ['X-Request-ID', ...] | Headers to expose in CORS responses |
| `cors.max_age` | integer | 86400 | Max age for CORS preflight requests |
| `max_request_size` | integer | 2 * 1024 * 1024 | Maximum request size in bytes |
| `ip_blacklist` | array | [] | IPs to block from accessing the API |

## PHPCS Settings

```php
'phpcs' => [
    'binary' => 'phpcs',
    'standards_path' => null,
    'max_code_size' => 1024 * 1024, // 1MB
    'timeout' => 30, // seconds
    'default_standard' => 'PSR12',
    'default_options' => [
        'report' => 'json',
        'colors' => false,
    ],
    'allowed_standards' => null, // null means all installed standards
    'disallowed_standards' => [],
],
```

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `binary` | string | 'phpcs' | Path to the PHPCS binary |
| `standards_path` | string | null | Path to custom PHPCS standards |
| `max_code_size` | integer | 1024 * 1024 | Maximum code size in bytes |
| `timeout` | integer | 30 | Timeout for PHPCS execution in seconds |
| `default_standard` | string | 'PSR12' | Default coding standard |
| `default_options` | array | See above | Default PHPCS options |
| `allowed_standards` | array | null | Standards allowed to be used (null = all) |
| `disallowed_standards` | array | [] | Standards not allowed to be used |

## Cache Settings

```php
'cache' => [
    'enabled' => true,
    'path' => __DIR__ . '/cache',
    'ttl' => 86400, // 24 hours in seconds
    'ttl_by_standard' => [
        'PSR12' => 604800, // 1 week for PSR12 standard
        'PHPCompatibility' => 2592000, // 30 days for compatibility checks
    ],
    'max_size' => 100 * 1024 * 1024, // 100MB
    'max_entries' => 1000,
    'cleanup_probability' => 0.01, // 1% chance of cleanup on each request
],
```

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `enabled` | boolean | true | Whether caching is enabled |
| `path` | string | __DIR__ . '/cache' | Path to the cache directory |
| `ttl` | integer | 86400 | Default time-to-live for cache entries |
| `ttl_by_standard` | array | See above | TTL for specific standards |
| `max_size` | integer | 100 * 1024 * 1024 | Maximum cache size in bytes |
| `max_entries` | integer | 1000 | Maximum number of cache entries |
| `cleanup_probability` | float | 0.01 | Probability of cache cleanup |

## Logging Settings

```php
'logging' => [
    'enabled' => true,
    'path' => __DIR__ . '/logs',
    'log_level' => 'info', // debug, info, warning, error, critical
    'slow_threshold' => 1.0, // Threshold for slow requests (seconds)
    'performance_threshold' => [
        'analyze' => 0.5, // Threshold for PHPCS analysis
        'standards' => 0.2, // Threshold for standards listing
        'cache_lookup' => 0.05, // Threshold for cache lookups
    ],
    'max_log_size' => 10 * 1024 * 1024, // 10MB
    'max_log_files' => 10, // Keep 10 rotated log files
],
```

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `enabled` | boolean | true | Whether logging is enabled |
| `path` | string | __DIR__ . '/logs' | Path to the logs directory |
| `log_level` | string | 'info' | Minimum log level to record |
| `slow_threshold` | float | 1.0 | Threshold for slow requests in seconds |
| `performance_threshold` | array | See above | Thresholds for specific operations |
| `max_log_size` | integer | 10 * 1024 * 1024 | Maximum log file size in bytes |
| `max_log_files` | integer | 10 | Maximum number of rotated log files |

## Environment-Specific Configuration

You can use the `environment` setting to load different configurations for different environments:

```php
$config = [
    'app' => [
        'environment' => getenv('APP_ENV') ?: 'production',
        // ...
    ],
    // ...
];

// Environment-specific overrides
if ($config['app']['environment'] === 'development') {
    $config['app']['debug'] = true;
    $config['logging']['log_level'] = 'debug';
    // ...
}

return $config;
```

## Using Environment Variables

You can use environment variables to override configuration settings:

```php
'app' => [
    'name' => getenv('APP_NAME') ?: 'PHPCS API',
    'version' => getenv('APP_VERSION') ?: '1.0.0',
    // ...
],
```

## Accessing Configuration

The `Config` class provides methods for accessing configuration values:

```php
// Get a configuration value
$value = Config::get('app.name');

// Get a configuration value with a default
$value = Config::get('app.debug', false);

// Check if a configuration value exists
$exists = Config::has('app.name');

// Set a configuration value
Config::set('app.name', 'My PHPCS API');

// Get all configuration values
$config = Config::all();
```

## Configuration Best Practices

1. **Don't commit sensitive information**: Keep API keys, passwords, and other sensitive information out of the configuration file. Use environment variables instead.

2. **Use appropriate values for each environment**: Development, testing, and production environments should have different configurations.

3. **Document custom configuration**: If you add custom configuration options, document them in your project's README or other documentation.

4. **Validate configuration**: Validate configuration values to ensure they meet expected formats and constraints.

5. **Use sensible defaults**: Provide sensible default values for all configuration options.

## Example Configuration

Here's a complete example configuration file:

```php
<?php

return [
    'app' => [
        'name' => getenv('APP_NAME') ?: 'PHPCS API',
        'version' => getenv('APP_VERSION') ?: '1.0.0',
        'environment' => getenv('APP_ENV') ?: 'production',
        'debug' => getenv('APP_DEBUG') === 'true',
        'timezone' => getenv('APP_TIMEZONE') ?: 'UTC',
        'base_url' => getenv('APP_URL') ?: 'https://api.example.com',
    ],
    'auth' => [
        'enabled' => getenv('AUTH_ENABLED') !== 'false',
        'key_file' => getenv('AUTH_KEY_FILE') ?: __DIR__ . '/data/keys.json',
        'default_ttl' => (int) (getenv('AUTH_DEFAULT_TTL') ?: 31536000),
        'hash_algo' => PASSWORD_BCRYPT,
        'hash_options' => [
            'cost' => (int) (getenv('AUTH_HASH_COST') ?: 12),
        ],
        'public_paths' => [
            '/health',
            '/metrics',
        ],
    ],
    'security' => [
        'rate_limit' => [
            'enabled' => getenv('RATE_LIMIT_ENABLED') !== 'false',
            'ip_minute_limit' => (int) (getenv('RATE_LIMIT_IP_MINUTE') ?: 60),
            'ip_hour_limit' => (int) (getenv('RATE_LIMIT_IP_HOUR') ?: 1000),
            'key_minute_limit' => (int) (getenv('RATE_LIMIT_KEY_MINUTE') ?: 30),
            'whitelist' => array_filter(explode(',', getenv('RATE_LIMIT_WHITELIST') ?: '127.0.0.1,::1')),
        ],
        'headers' => [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self'; object-src 'none'; frame-ancestors 'none'",
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ],
        'cors' => [
            'enabled' => getenv('CORS_ENABLED') !== 'false',
            'allow_origins' => array_filter(explode(',', getenv('CORS_ALLOW_ORIGINS') ?: '*')),
            'allow_methods' => array_filter(explode(',', getenv('CORS_ALLOW_METHODS') ?: 'GET,POST,OPTIONS')),
            'allow_headers' => array_filter(explode(',', getenv('CORS_ALLOW_HEADERS') ?: 'Content-Type,Authorization,X-API-Key')),
            'expose_headers' => array_filter(explode(',', getenv('CORS_EXPOSE_HEADERS') ?: 'X-Request-ID,X-RateLimit-Limit,X-RateLimit-Remaining,X-RateLimit-Reset')),
            'max_age' => (int) (getenv('CORS_MAX_AGE') ?: 86400),
        ],
        'max_request_size' => (int) (getenv('MAX_REQUEST_SIZE') ?: 2 * 1024 * 1024),
        'ip_blacklist' => array_filter(explode(',', getenv('IP_BLACKLIST') ?: '')),
    ],
    'phpcs' => [
        'binary' => getenv('PHPCS_BINARY') ?: 'phpcs',
        'standards_path' => getenv('PHPCS_STANDARDS_PATH') ?: null,
        'max_code_size' => (int) (getenv('PHPCS_MAX_CODE_SIZE') ?: 1024 * 1024),
        'timeout' => (int) (getenv('PHPCS_TIMEOUT') ?: 30),
        'default_standard' => getenv('PHPCS_DEFAULT_STANDARD') ?: 'PSR12',
        'default_options' => [
            'report' => 'json',
            'colors' => false,
        ],
        'allowed_standards' => getenv('PHPCS_ALLOWED_STANDARDS') ? array_filter(explode(',', getenv('PHPCS_ALLOWED_STANDARDS'))) : null,
        'disallowed_standards' => array_filter(explode(',', getenv('PHPCS_DISALLOWED_STANDARDS') ?: '')),
    ],
    'cache' => [
        'enabled' => getenv('CACHE_ENABLED') !== 'false',
        'path' => getenv('CACHE_PATH') ?: __DIR__ . '/cache',
        'ttl' => (int) (getenv('CACHE_TTL') ?: 86400),
        'ttl_by_standard' => [
            'PSR12' => 604800,
            'PHPCompatibility' => 2592000,
        ],
        'max_size' => (int) (getenv('CACHE_MAX_SIZE') ?: 100 * 1024 * 1024),
        'max_entries' => (int) (getenv('CACHE_MAX_ENTRIES') ?: 1000),
        'cleanup_probability' => (float) (getenv('CACHE_CLEANUP_PROBABILITY') ?: 0.01),
    ],
    'logging' => [
        'enabled' => getenv('LOGGING_ENABLED') !== 'false',
        'path' => getenv('LOGGING_PATH') ?: __DIR__ . '/logs',
        'log_level' => getenv('LOGGING_LEVEL') ?: 'info',
        'slow_threshold' => (float) (getenv('LOGGING_SLOW_THRESHOLD') ?: 1.0),
        'performance_threshold' => [
            'analyze' => 0.5,
            'standards' => 0.2,
            'cache_lookup' => 0.05,
        ],
        'max_log_size' => (int) (getenv('LOGGING_MAX_SIZE') ?: 10 * 1024 * 1024),
        'max_log_files' => (int) (getenv('LOGGING_MAX_FILES') ?: 10),
    ],
];
