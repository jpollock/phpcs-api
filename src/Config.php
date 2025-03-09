<?php

namespace PhpcsApi;

/**
 * Config class to handle configuration settings.
 */
class Config
{
    /**
     * Configuration values.
     *
     * @var array
     */
    private static $config = [
        'app_name' => 'PHPCS API',
        'app_version' => '1.0.0',
        'debug' => false,
        
        // Authentication settings
        'auth' => [
            'enabled' => false,
            'keys_file' => __DIR__ . '/../data/api_keys.json',
            'protected_paths' => [
                '/analyze',
            ],
            'path_scopes' => [
                '/analyze' => 'analyze',
                '/standards' => 'standards',
            ],
            'public_paths' => [
                '/health',
                '/metrics',
            ],
        ],
        
        // Security settings
        'security' => [
            'rate_limit' => [
                'enabled' => true,
                'requests_per_minute' => 60,
                'requests_per_hour' => 1000,
            ],
            'cors' => [
                'enabled' => true,
                'allowed_origins' => ['*'],
                'allowed_methods' => ['GET', 'POST', 'OPTIONS'],
                'allowed_headers' => ['Content-Type', 'Authorization', 'X-API-Key'],
                'expose_headers' => [],
                'max_age' => 86400,
                'allow_credentials' => false,
            ],
            'headers' => [
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'DENY',
                'X-XSS-Protection' => '1; mode=block',
                'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
                'Content-Security-Policy' => "default-src 'self'; script-src 'self'; object-src 'none'; frame-ancestors 'none'",
            ],
        ],
    ];

    /**
     * Get a configuration value.
     *
     * @param string $key     Configuration key.
     * @param mixed  $default Default value.
     *
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return self::$config[$key] ?? $default;
    }

    /**
     * Set a configuration value.
     *
     * @param string $key   Configuration key.
     * @param mixed  $value Configuration value.
     *
     * @return void
     */
    public static function set(string $key, $value): void
    {
        self::$config[$key] = $value;
    }

    /**
     * Load configuration from a file.
     *
     * @param string $file Configuration file path.
     *
     * @return void
     */
    public static function load(string $file): void
    {
        if (file_exists($file)) {
            $config = require $file;
            if (is_array($config)) {
                self::$config = array_merge(self::$config, $config);
            }
        }
    }

    /**
     * Get all configuration values.
     *
     * @return array
     */
    public static function all(): array
    {
        return self::$config;
    }
}
