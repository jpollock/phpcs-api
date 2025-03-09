<?php

namespace PhpcsApi;

/**
 * Cache service for PHPCS API.
 * 
 * Handles caching of PHPCS analysis results to improve performance.
 */
class CacheService
{
    /**
     * Cache directory.
     *
     * @var string
     */
    private $cacheDir;

    /**
     * Whether caching is enabled.
     *
     * @var bool
     */
    private $enabled;

    /**
     * Cache TTL in seconds.
     *
     * @var int
     */
    private $ttl;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Create a new CacheService instance.
     */
    public function __construct()
    {
        $this->cacheDir = __DIR__ . '/../cache';
        $this->enabled = Config::get('cache.enabled', true);
        $this->ttl = Config::get('cache.ttl', 3600); // 1 hour default
        $this->logger = new Logger();
        
        // Create cache directory if it doesn't exist
        if ($this->enabled && !is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Generate a cache key for the given code and options.
     *
     * @param string      $code       PHP code to analyze.
     * @param string      $standard   PHPCS standard to use.
     * @param string|null $phpVersion PHP version to test against (for PHPCompatibility).
     * @param array       $options    Additional PHPCS options.
     *
     * @return string
     */
    public function generateKey(string $code, string $standard, ?string $phpVersion = null, array $options = []): string
    {
        // Sort options to ensure consistent key generation
        ksort($options);
        
        // Create a data array with all parameters
        $data = [
            'code' => $code,
            'standard' => $standard,
            'phpVersion' => $phpVersion,
            'options' => $options,
        ];
        
        // Generate a hash of the data
        return hash('sha256', serialize($data));
    }

    /**
     * Get cached result for the given key.
     *
     * @param string $key Cache key.
     *
     * @return array|null Cached result or null if not found or expired.
     */
    public function get(string $key): ?array
    {
        if (!$this->enabled) {
            return null;
        }
        
        $cacheFile = $this->getCacheFilePath($key);
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        // Check if cache is expired
        $mtime = filemtime($cacheFile);
        if (time() - $mtime > $this->ttl) {
            // Cache is expired, delete the file
            unlink($cacheFile);
            return null;
        }
        
        // Read cache file
        $data = file_get_contents($cacheFile);
        $result = json_decode($data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Invalid JSON, delete the file
            unlink($cacheFile);
            return null;
        }
        
        // Log cache hit
        $this->logger->logRequest('CACHE', 'HIT', 200, 'cache', null, 0);
        
        return $result;
    }

    /**
     * Set cached result for the given key.
     *
     * @param string $key    Cache key.
     * @param array  $result Result to cache.
     *
     * @return bool True if cache was set, false otherwise.
     */
    public function set(string $key, array $result): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        $cacheFile = $this->getCacheFilePath($key);
        
        // Write cache file
        $data = json_encode($result);
        $success = file_put_contents($cacheFile, $data) !== false;
        
        if ($success) {
            // Log cache set
            $this->logger->logRequest('CACHE', 'SET', 200, 'cache', null, 0);
        }
        
        return $success;
    }

    /**
     * Clear all cached results.
     *
     * @return bool True if cache was cleared, false otherwise.
     */
    public function clear(): bool
    {
        if (!$this->enabled || !is_dir($this->cacheDir)) {
            return false;
        }
        
        $success = true;
        
        // Delete all files in cache directory
        $files = glob($this->cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $success = $success && unlink($file);
            }
        }
        
        if ($success) {
            // Log cache clear
            $this->logger->logRequest('CACHE', 'CLEAR', 200, 'cache', null, 0);
        }
        
        return $success;
    }

    /**
     * Get cache file path for the given key.
     *
     * @param string $key Cache key.
     *
     * @return string
     */
    private function getCacheFilePath(string $key): string
    {
        return $this->cacheDir . '/' . $key . '.json';
    }

    /**
     * Get cache statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        if (!$this->enabled || !is_dir($this->cacheDir)) {
            return [
                'enabled' => false,
                'count' => 0,
                'size' => 0,
                'oldest' => null,
                'newest' => null,
            ];
        }
        
        $count = 0;
        $size = 0;
        $oldest = PHP_INT_MAX;
        $newest = 0;
        
        // Get stats for all files in cache directory
        $files = glob($this->cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $count++;
                $size += filesize($file);
                $mtime = filemtime($file);
                $oldest = min($oldest, $mtime);
                $newest = max($newest, $mtime);
            }
        }
        
        return [
            'enabled' => $this->enabled,
            'count' => $count,
            'size' => $size,
            'oldest' => $count > 0 ? date('Y-m-d H:i:s', $oldest) : null,
            'newest' => $count > 0 ? date('Y-m-d H:i:s', $newest) : null,
        ];
    }
}
