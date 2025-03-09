<?php

namespace PhpcsApi;

/**
 * Authentication service for PHPCS API.
 * 
 * Handles API key authentication, validation, and management.
 */
class AuthService
{
    /**
     * API keys storage file path.
     *
     * @var string
     */
    private $keysFile;

    /**
     * API keys cache.
     *
     * @var array|null
     */
    private $keysCache = null;

    /**
     * Create a new AuthService instance.
     *
     * @param string|null $keysFile API keys storage file path.
     */
    public function __construct(?string $keysFile = null)
    {
        $this->keysFile = $keysFile ?? __DIR__ . '/../data/api_keys.json';
        
        // Ensure the data directory exists
        $dataDir = dirname($this->keysFile);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0750, true);
        }
        
        // Create keys file if it doesn't exist
        if (!file_exists($this->keysFile)) {
            file_put_contents($this->keysFile, json_encode([]));
            chmod($this->keysFile, 0640);
        }
    }

    /**
     * Validate an API key.
     *
     * @param string $apiKey API key to validate.
     * @param string $scope  Required scope (optional).
     *
     * @return bool True if the API key is valid, false otherwise.
     */
    public function validateKey(string $apiKey, string $scope = null): bool
    {
        if (empty($apiKey)) {
            return false;
        }
        
        $keys = $this->getKeys();
        
        if (!isset($keys[$apiKey])) {
            return false;
        }
        
        $keyData = $keys[$apiKey];
        
        // Check if key is expired
        if (isset($keyData['expires']) && $keyData['expires'] < time()) {
            return false;
        }
        
        // Check if key is active
        if (isset($keyData['active']) && $keyData['active'] === false) {
            return false;
        }
        
        // Check scope if provided
        if ($scope !== null && (!isset($keyData['scopes']) || !in_array($scope, $keyData['scopes']))) {
            return false;
        }
        
        return true;
    }

    /**
     * Get API key data.
     *
     * @param string $apiKey API key.
     *
     * @return array|null API key data or null if not found.
     */
    public function getKeyData(string $apiKey): ?array
    {
        $keys = $this->getKeys();
        
        return $keys[$apiKey] ?? null;
    }

    /**
     * Generate a new API key.
     *
     * @param array $data API key data.
     *
     * @return string Generated API key.
     */
    public function generateKey(array $data = []): string
    {
        // Generate a secure random API key
        $apiKey = bin2hex(random_bytes(24));
        
        // Set default values
        $data = array_merge([
            'created' => time(),
            'active' => true,
            'scopes' => ['analyze', 'standards'],
        ], $data);
        
        // Save the key
        $keys = $this->getKeys();
        $keys[$apiKey] = $data;
        $this->saveKeys($keys);
        
        return $apiKey;
    }

    /**
     * Revoke an API key.
     *
     * @param string $apiKey API key to revoke.
     *
     * @return bool True if the key was revoked, false otherwise.
     */
    public function revokeKey(string $apiKey): bool
    {
        $keys = $this->getKeys();
        
        if (!isset($keys[$apiKey])) {
            return false;
        }
        
        // Mark the key as inactive
        $keys[$apiKey]['active'] = false;
        $this->saveKeys($keys);
        
        return true;
    }

    /**
     * Get all API keys.
     *
     * @return array API keys.
     */
    public function getKeys(): array
    {
        if ($this->keysCache === null) {
            $content = file_get_contents($this->keysFile);
            $this->keysCache = json_decode($content, true) ?: [];
        }
        
        return $this->keysCache;
    }

    /**
     * Save API keys.
     *
     * @param array $keys API keys.
     *
     * @return bool True if the keys were saved, false otherwise.
     */
    private function saveKeys(array $keys): bool
    {
        $this->keysCache = $keys;
        
        // Use atomic file writing to prevent race conditions
        $tempFile = $this->keysFile . '.tmp';
        $result = file_put_contents($tempFile, json_encode($keys, JSON_PRETTY_PRINT));
        
        if ($result === false) {
            return false;
        }
        
        chmod($tempFile, 0640);
        rename($tempFile, $this->keysFile);
        
        return true;
    }

    /**
     * Extract API key from request.
     *
     * @param Request $request Request instance.
     *
     * @return string|null API key or null if not found.
     */
    public function extractKeyFromRequest(Request $request): ?string
    {
        // Check Authorization header (Bearer token)
        $authHeader = $request->headers['Authorization'] ?? null;
        if ($authHeader && preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        // Check X-API-Key header
        $apiKeyHeader = $request->headers['X-Api-Key'] ?? null;
        if ($apiKeyHeader) {
            return $apiKeyHeader;
        }
        
        // Check query parameter
        $apiKeyParam = $request->getQueryParam('api_key');
        if ($apiKeyParam) {
            return $apiKeyParam;
        }
        
        return null;
    }
}
