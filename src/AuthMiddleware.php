<?php

namespace PhpcsApi;

/**
 * Authentication middleware for PHPCS API.
 * 
 * Intercepts requests and validates API keys before allowing access to protected endpoints.
 */
class AuthMiddleware
{
    /**
     * Auth service instance.
     *
     * @var AuthService
     */
    private $authService;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Protected paths that require authentication.
     *
     * @var array
     */
    private $protectedPaths = [];

    /**
     * Path to scope mapping.
     *
     * @var array
     */
    private $pathScopes = [];

    /**
     * Create a new AuthMiddleware instance.
     *
     * @param AuthService $authService    Auth service instance.
     * @param array       $protectedPaths Protected paths that require authentication.
     * @param array       $pathScopes     Path to scope mapping.
     */
    public function __construct(AuthService $authService, array $protectedPaths = [], array $pathScopes = [])
    {
        $this->authService = $authService;
        $this->logger = new Logger();
        $this->protectedPaths = $protectedPaths;
        $this->pathScopes = $pathScopes;
    }

    /**
     * Process a request through the middleware.
     *
     * @param Request  $request Request instance.
     * @param callable $next    Next middleware handler.
     *
     * @return Response
     */
    public function process(Request $request, callable $next): Response
    {
        // Skip authentication if disabled or for non-protected paths
        if (!Config::get('auth.enabled', true) || !$this->isProtectedPath($request->path)) {
            return $next($request);
        }

        // Extract API key from request
        $apiKey = $this->authService->extractKeyFromRequest($request);
        
        // Get client IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Check if API key is provided
        if (empty($apiKey)) {
            // Log failed authentication attempt (no API key)
            $this->logger->logAuthAttempt(
                $request->path,
                null,
                false,
                'No API key provided',
                $ip
            );
            
            return Response::json([
                'error' => 'Authentication required',
                'message' => 'API key is required for this endpoint',
            ], 401)->withHeader('WWW-Authenticate', 'Bearer');
        }
        
        // Get required scope for the path
        $scope = $this->getPathScope($request->path);
        
        // Validate API key
        if (!$this->authService->validateKey($apiKey, $scope)) {
            // Log failed authentication attempt (invalid API key or scope)
            $this->logger->logAuthAttempt(
                $request->path,
                $apiKey,
                false,
                $scope ? "Invalid API key or missing '$scope' scope" : 'Invalid API key',
                $ip
            );
            
            return Response::json([
                'error' => 'Invalid API key',
                'message' => 'The provided API key is invalid or does not have the required permissions',
            ], 403);
        }
        
        // Log successful authentication
        $this->logger->logAuthAttempt(
            $request->path,
            $apiKey,
            true,
            null,
            $ip
        );
        
        // Add API key data to request for downstream handlers
        $request->apiKey = $apiKey;
        $request->apiKeyData = $this->authService->getKeyData($apiKey);
        
        // Continue to next middleware
        return $next($request);
    }

    /**
     * Check if a path is protected.
     *
     * @param string $path Path to check.
     *
     * @return bool True if the path is protected, false otherwise.
     */
    private function isProtectedPath(string $path): bool
    {
        foreach ($this->protectedPaths as $protectedPath) {
            // Exact match
            if ($protectedPath === $path) {
                return true;
            }
            
            // Wildcard match (e.g., /api/*)
            if (substr($protectedPath, -1) === '*' && strpos($path, rtrim($protectedPath, '*')) === 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get the required scope for a path.
     *
     * @param string $path Path to get scope for.
     *
     * @return string|null Required scope or null if not specified.
     */
    private function getPathScope(string $path): ?string
    {
        // Check exact path match
        if (isset($this->pathScopes[$path])) {
            return $this->pathScopes[$path];
        }
        
        // Check wildcard matches
        foreach ($this->pathScopes as $pathPattern => $scope) {
            if (substr($pathPattern, -1) === '*' && strpos($path, rtrim($pathPattern, '*')) === 0) {
                return $scope;
            }
        }
        
        return null;
    }

    /**
     * Add a protected path.
     *
     * @param string      $path  Path to protect.
     * @param string|null $scope Required scope for the path.
     *
     * @return self
     */
    public function addProtectedPath(string $path, ?string $scope = null): self
    {
        $this->protectedPaths[] = $path;
        
        if ($scope !== null) {
            $this->pathScopes[$path] = $scope;
        }
        
        return $this;
    }
}
