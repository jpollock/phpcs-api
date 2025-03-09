<?php

namespace PhpcsApi;

/**
 * Security middleware for PHPCS API.
 * 
 * Handles security headers, CORS, and rate limiting.
 */
class SecurityMiddleware
{
    /**
     * Security configuration.
     *
     * @var array
     */
    private $config;

    /**
     * Rate limit storage.
     *
     * @var array
     */
    private $rateLimits = [];

    /**
     * Create a new SecurityMiddleware instance.
     *
     * @param array $config Security configuration.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config ?: Config::get('security', []);
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
        // Handle CORS preflight requests
        if ($this->config['cors']['enabled'] && $request->method === 'OPTIONS') {
            return $this->handleCorsPreflightRequest($request);
        }
        
        // Check rate limits
        if ($this->config['rate_limit']['enabled']) {
            $rateLimitResult = $this->checkRateLimit($request);
            if ($rateLimitResult !== true) {
                return $rateLimitResult;
            }
        }
        
        // Process the request
        $response = $next($request);
        
        // Add security headers
        $response = $this->addSecurityHeaders($response);
        
        // Add CORS headers to response
        if ($this->config['cors']['enabled']) {
            $response = $this->addCorsHeaders($request, $response);
        }
        
        return $response;
    }

    /**
     * Handle CORS preflight request.
     *
     * @param Request $request Request instance.
     *
     * @return Response
     */
    private function handleCorsPreflightRequest(Request $request): Response
    {
        $response = new Response(204);
        return $this->addCorsHeaders($request, $response);
    }

    /**
     * Add CORS headers to response.
     *
     * @param Request  $request  Request instance.
     * @param Response $response Response instance.
     *
     * @return Response
     */
    private function addCorsHeaders(Request $request, Response $response): Response
    {
        $corsConfig = $this->config['cors'];
        
        // Access-Control-Allow-Origin
        $origin = $request->headers['Origin'] ?? '*';
        if ($origin !== '*' && !in_array('*', $corsConfig['allowed_origins']) && !in_array($origin, $corsConfig['allowed_origins'])) {
            $origin = $corsConfig['allowed_origins'][0] ?? null;
        }
        
        if ($origin) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        }
        
        // Access-Control-Allow-Methods
        if (!empty($corsConfig['allowed_methods'])) {
            $response = $response->withHeader('Access-Control-Allow-Methods', implode(', ', $corsConfig['allowed_methods']));
        }
        
        // Access-Control-Allow-Headers
        if (!empty($corsConfig['allowed_headers'])) {
            $response = $response->withHeader('Access-Control-Allow-Headers', implode(', ', $corsConfig['allowed_headers']));
        }
        
        // Access-Control-Expose-Headers
        if (!empty($corsConfig['expose_headers'])) {
            $response = $response->withHeader('Access-Control-Expose-Headers', implode(', ', $corsConfig['expose_headers']));
        }
        
        // Access-Control-Max-Age
        if (isset($corsConfig['max_age'])) {
            $response = $response->withHeader('Access-Control-Max-Age', (string) $corsConfig['max_age']);
        }
        
        // Access-Control-Allow-Credentials
        if (isset($corsConfig['allow_credentials']) && $corsConfig['allow_credentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }
        
        return $response;
    }

    /**
     * Add security headers to response.
     *
     * @param Response $response Response instance.
     *
     * @return Response
     */
    private function addSecurityHeaders(Response $response): Response
    {
        foreach ($this->config['headers'] as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        
        return $response;
    }

    /**
     * Check rate limits.
     *
     * @param Request $request Request instance.
     *
     * @return Response|true Response if rate limit exceeded, true otherwise.
     */
    private function checkRateLimit(Request $request)
    {
        // Get client identifier (IP address or API key)
        $clientId = $request->apiKey ?? $this->getClientIp($request);
        
        if (empty($clientId)) {
            return true;
        }
        
        // Initialize rate limit data for client
        if (!isset($this->rateLimits[$clientId])) {
            $this->rateLimits[$clientId] = [
                'minute' => [
                    'count' => 0,
                    'reset' => time() + 60,
                ],
                'hour' => [
                    'count' => 0,
                    'reset' => time() + 3600,
                ],
            ];
        }
        
        // Check and update minute limit
        $minuteLimit = $this->config['rate_limit']['requests_per_minute'];
        if ($minuteLimit > 0) {
            // Reset if expired
            if (time() > $this->rateLimits[$clientId]['minute']['reset']) {
                $this->rateLimits[$clientId]['minute'] = [
                    'count' => 1,
                    'reset' => time() + 60,
                ];
            } else {
                // Increment count
                $this->rateLimits[$clientId]['minute']['count']++;
                
                // Check if exceeded
                if ($this->rateLimits[$clientId]['minute']['count'] > $minuteLimit) {
                    return $this->createRateLimitResponse(
                        $this->rateLimits[$clientId]['minute']['reset'] - time(),
                        $minuteLimit,
                        'minute'
                    );
                }
            }
        }
        
        // Check and update hour limit
        $hourLimit = $this->config['rate_limit']['requests_per_hour'];
        if ($hourLimit > 0) {
            // Reset if expired
            if (time() > $this->rateLimits[$clientId]['hour']['reset']) {
                $this->rateLimits[$clientId]['hour'] = [
                    'count' => 1,
                    'reset' => time() + 3600,
                ];
            } else {
                // Increment count
                $this->rateLimits[$clientId]['hour']['count']++;
                
                // Check if exceeded
                if ($this->rateLimits[$clientId]['hour']['count'] > $hourLimit) {
                    return $this->createRateLimitResponse(
                        $this->rateLimits[$clientId]['hour']['reset'] - time(),
                        $hourLimit,
                        'hour'
                    );
                }
            }
        }
        
        return true;
    }

    /**
     * Create rate limit response.
     *
     * @param int    $retryAfter Seconds until retry is allowed.
     * @param int    $limit      Rate limit.
     * @param string $period     Rate limit period.
     *
     * @return Response
     */
    private function createRateLimitResponse(int $retryAfter, int $limit, string $period): Response
    {
        return Response::json([
            'error' => 'Rate limit exceeded',
            'message' => "You have exceeded the {$limit} requests per {$period} rate limit.",
            'retry_after' => $retryAfter,
        ], 429)->withHeader('Retry-After', (string) $retryAfter);
    }

    /**
     * Get client IP address.
     *
     * @param Request $request Request instance.
     *
     * @return string|null
     */
    private function getClientIp(Request $request): ?string
    {
        // Check for proxy headers
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];
        
        foreach ($headers as $header) {
            $ip = $_SERVER[$header] ?? null;
            
            if ($ip) {
                // Handle comma-separated list (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                return $ip;
            }
        }
        
        return null;
    }
}
