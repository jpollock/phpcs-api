<?php

namespace PhpcsApi;

/**
 * Enhanced Logger class for PHPCS API.
 * 
 * Handles logging of various events, including authentication attempts, failures,
 * performance metrics, and security events.
 */
class Logger
{
    /**
     * Log levels.
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';

    /**
     * Log level priorities (lower number = higher priority).
     */
    const LEVEL_PRIORITIES = [
        self::LEVEL_DEBUG => 100,
        self::LEVEL_INFO => 200,
        self::LEVEL_WARNING => 300,
        self::LEVEL_ERROR => 400,
        self::LEVEL_CRITICAL => 500,
    ];

    /**
     * Log directory.
     *
     * @var string
     */
    private $logDir;

    /**
     * Whether logging is enabled.
     *
     * @var bool
     */
    private $enabled;

    /**
     * Log level.
     *
     * @var string
     */
    private $logLevel;

    /**
     * Current request ID.
     *
     * @var string
     */
    private $requestId;

    /**
     * Create a new Logger instance.
     */
    public function __construct()
    {
        $this->logDir = __DIR__ . '/../logs';
        $this->enabled = Config::get('logging.enabled', false);
        $this->logLevel = Config::get('logging.log_level', self::LEVEL_INFO);
        $this->requestId = $this->generateRequestId();
        
        // Create log directory if it doesn't exist
        if ($this->enabled && !is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    /**
     * Generate a unique request ID.
     *
     * @return string
     */
    private function generateRequestId(): string
    {
        return uniqid('req_', true);
    }

    /**
     * Check if the given log level is enabled.
     *
     * @param string $level Log level to check.
     *
     * @return bool
     */
    private function isLevelEnabled(string $level): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $configuredPriority = self::LEVEL_PRIORITIES[$this->logLevel] ?? self::LEVEL_PRIORITIES[self::LEVEL_INFO];
        $levelPriority = self::LEVEL_PRIORITIES[$level] ?? self::LEVEL_PRIORITIES[self::LEVEL_INFO];

        return $levelPriority >= $configuredPriority;
    }

    /**
     * Log a debug message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context data.
     */
    public function debug(string $message, array $context = []): void
    {
        if (!$this->isLevelEnabled(self::LEVEL_DEBUG)) {
            return;
        }

        $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Log an info message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context data.
     */
    public function info(string $message, array $context = []): void
    {
        if (!$this->isLevelEnabled(self::LEVEL_INFO)) {
            return;
        }

        $this->log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Log a warning message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context data.
     */
    public function warning(string $message, array $context = []): void
    {
        if (!$this->isLevelEnabled(self::LEVEL_WARNING)) {
            return;
        }

        $this->log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Log an error message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context data.
     */
    public function error(string $message, array $context = []): void
    {
        if (!$this->isLevelEnabled(self::LEVEL_ERROR)) {
            return;
        }

        $this->log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Log a critical message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context data.
     */
    public function critical(string $message, array $context = []): void
    {
        if (!$this->isLevelEnabled(self::LEVEL_CRITICAL)) {
            return;
        }

        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }

    /**
     * Log a message with the specified level.
     *
     * @param string $level   Log level.
     * @param string $message Message to log.
     * @param array  $context Additional context data.
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'request_id' => $this->requestId,
            'level' => $level,
            'message' => $message,
        ];

        if (!empty($context)) {
            $logData['context'] = $context;
        }

        // Add memory usage if debug level
        if ($level === self::LEVEL_DEBUG) {
            $logData['memory_usage'] = $this->getMemoryUsage();
        }

        $this->writeToLog('application.log', $logData);
    }

    /**
     * Log an authentication attempt.
     *
     * @param string      $path       Request path.
     * @param string|null $apiKey     API key used (or null if none provided).
     * @param bool        $successful Whether the authentication was successful.
     * @param string|null $reason     Reason for failure (if applicable).
     * @param string      $ip         IP address of the client.
     * @param string|null $userAgent  User agent of the client.
     */
    public function logAuthAttempt(string $path, ?string $apiKey, bool $successful, ?string $reason = null, string $ip = 'unknown', ?string $userAgent = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'request_id' => $this->requestId,
            'type' => 'auth',
            'path' => $path,
            'ip' => $ip,
            'api_key' => $apiKey ? substr($apiKey, 0, 8) . '...' : 'none',
            'successful' => $successful,
        ];

        if (!$successful && $reason) {
            $logData['reason'] = $reason;
        }

        if ($userAgent) {
            $logData['user_agent'] = $userAgent;
        }

        // Log to auth.log
        $this->writeToLog('auth.log', $logData);

        // Also log to security.log if authentication failed
        if (!$successful) {
            $logData['type'] = 'security';
            $logData['event'] = 'auth_failure';
            $this->writeToLog('security.log', $logData);

            // Log as warning or error depending on the number of failures
            $level = $this->isRepeatedFailure($ip) ? self::LEVEL_ERROR : self::LEVEL_WARNING;
            $this->log($level, "Authentication failure: $reason", [
                'path' => $path,
                'ip' => $ip,
            ]);
        }
    }

    /**
     * Check if there are repeated authentication failures from the same IP.
     *
     * @param string $ip IP address to check.
     *
     * @return bool
     */
    private function isRepeatedFailure(string $ip): bool
    {
        // This is a simplified implementation
        // In a real-world scenario, you would check a database or cache
        // for recent failures from the same IP
        return false;
    }

    /**
     * Log an API request.
     *
     * @param string      $method     HTTP method.
     * @param string      $path       Request path.
     * @param int         $statusCode Response status code.
     * @param string      $ip         IP address of the client.
     * @param string|null $userAgent  User agent of the client.
     * @param float       $duration   Request duration in seconds.
     * @param array       $headers    Request headers.
     * @param array       $params     Request parameters.
     */
    public function logRequest(
        string $method, 
        string $path, 
        int $statusCode, 
        string $ip = 'unknown', 
        ?string $userAgent = null, 
        float $duration = 0,
        array $headers = [],
        array $params = []
    ): void {
        if (!$this->enabled) {
            return;
        }

        // Don't log health checks unless in debug mode
        if ($path === '/health' && !$this->isLevelEnabled(self::LEVEL_DEBUG)) {
            return;
        }

        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'request_id' => $this->requestId,
            'type' => 'request',
            'method' => $method,
            'path' => $path,
            'status' => $statusCode,
            'ip' => $ip,
            'duration' => round($duration, 4),
        ];

        if ($userAgent) {
            $logData['user_agent'] = $userAgent;
        }

        // Add performance metrics
        $logData['performance'] = [
            'duration_ms' => round($duration * 1000, 2),
            'memory_usage' => $this->getMemoryUsage(),
        ];

        // Add request headers (sanitized)
        if (!empty($headers) && $this->isLevelEnabled(self::LEVEL_DEBUG)) {
            $sanitizedHeaders = $this->sanitizeHeaders($headers);
            $logData['headers'] = $sanitizedHeaders;
        }

        // Add request parameters (sanitized)
        if (!empty($params) && $this->isLevelEnabled(self::LEVEL_DEBUG)) {
            $sanitizedParams = $this->sanitizeParams($params);
            $logData['params'] = $sanitizedParams;
        }

        // Write to access log
        $this->writeToLog('access.log', $logData);

        // Log slow requests as warnings
        if ($duration > Config::get('logging.slow_threshold', 1.0)) {
            $this->warning("Slow request: $method $path", [
                'duration' => $duration,
                'status' => $statusCode,
            ]);
        }

        // Log error responses
        if ($statusCode >= 400) {
            $level = $statusCode >= 500 ? self::LEVEL_ERROR : self::LEVEL_WARNING;
            $this->log($level, "Error response: $statusCode for $method $path", [
                'duration' => $duration,
                'ip' => $ip,
            ]);
        }

        // Log potential security issues
        if ($statusCode === 403 || $statusCode === 401) {
            $logData['type'] = 'security';
            $logData['event'] = $statusCode === 403 ? 'forbidden_access' : 'unauthorized_access';
            $this->writeToLog('security.log', $logData);
        }
    }

    /**
     * Sanitize request headers for logging.
     *
     * @param array $headers Headers to sanitize.
     *
     * @return array
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sanitized = [];
        $sensitiveHeaders = ['authorization', 'x-api-key', 'cookie'];

        foreach ($headers as $name => $value) {
            $lowerName = strtolower($name);
            if (in_array($lowerName, $sensitiveHeaders)) {
                $sanitized[$name] = '[REDACTED]';
            } else {
                $sanitized[$name] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize request parameters for logging.
     *
     * @param array $params Parameters to sanitize.
     *
     * @return array
     */
    private function sanitizeParams(array $params): array
    {
        $sanitized = [];
        $sensitiveParams = ['password', 'token', 'key', 'secret', 'api_key'];

        foreach ($params as $name => $value) {
            $lowerName = strtolower($name);
            if (in_array($lowerName, $sensitiveParams)) {
                $sanitized[$name] = '[REDACTED]';
            } else if (is_array($value)) {
                $sanitized[$name] = $this->sanitizeParams($value);
            } else {
                $sanitized[$name] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Log an error.
     *
     * @param string      $message Error message.
     * @param string      $path    Request path.
     * @param string|null $trace   Stack trace.
     * @param array       $context Additional context data.
     */
    public function logError(string $message, string $path = 'unknown', ?string $trace = null, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'request_id' => $this->requestId,
            'type' => 'error',
            'path' => $path,
            'message' => $message,
        ];

        if (!empty($context)) {
            $logData['context'] = $context;
        }

        if ($trace && $this->isLevelEnabled(self::LEVEL_DEBUG)) {
            $logData['trace'] = $trace;
        }

        // Add memory usage
        $logData['memory_usage'] = $this->getMemoryUsage();

        // Write to error log
        $this->writeToLog('error.log', $logData);

        // Also log using the error method
        $this->error($message, array_merge(['path' => $path], $context));
    }

    /**
     * Log a security event.
     *
     * @param string $event   Security event type.
     * @param string $message Event description.
     * @param array  $context Additional context data.
     */
    public function logSecurityEvent(string $event, string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'request_id' => $this->requestId,
            'type' => 'security',
            'event' => $event,
            'message' => $message,
        ];

        if (!empty($context)) {
            $logData['context'] = $context;
        }

        // Write to security log
        $this->writeToLog('security.log', $logData);

        // Also log as warning or error depending on the event severity
        $level = $this->getSecurityEventLevel($event);
        $this->log($level, "Security event: $event - $message", $context);
    }

    /**
     * Get the log level for a security event.
     *
     * @param string $event Security event type.
     *
     * @return string
     */
    private function getSecurityEventLevel(string $event): string
    {
        $criticalEvents = ['brute_force', 'injection_attempt', 'xss_attempt'];
        $errorEvents = ['rate_limit_exceeded', 'invalid_token', 'forbidden_access'];

        if (in_array($event, $criticalEvents)) {
            return self::LEVEL_CRITICAL;
        } else if (in_array($event, $errorEvents)) {
            return self::LEVEL_ERROR;
        }

        return self::LEVEL_WARNING;
    }

    /**
     * Log performance metrics.
     *
     * @param string $operation Operation being measured.
     * @param float  $duration  Duration in seconds.
     * @param array  $metrics   Additional metrics.
     */
    public function logPerformance(string $operation, float $duration, array $metrics = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'request_id' => $this->requestId,
            'type' => 'performance',
            'operation' => $operation,
            'duration' => round($duration, 4),
            'duration_ms' => round($duration * 1000, 2),
            'memory_usage' => $this->getMemoryUsage(),
        ];

        if (!empty($metrics)) {
            $logData['metrics'] = $metrics;
        }

        // Write to performance log
        $this->writeToLog('performance.log', $logData);

        // Log slow operations as warnings
        $threshold = Config::get('logging.performance_threshold.' . $operation, 1.0);
        if ($duration > $threshold) {
            $this->warning("Slow operation: $operation", [
                'duration' => $duration,
                'threshold' => $threshold,
                'metrics' => $metrics,
            ]);
        }
    }

    /**
     * Get current memory usage in a human-readable format.
     *
     * @return string
     */
    private function getMemoryUsage(): string
    {
        $bytes = memory_get_usage(true);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Write data to a log file.
     *
     * @param string $filename Log filename.
     * @param array  $data     Data to log.
     */
    protected function writeToLog(string $filename, array $data): void
    {
        $logFile = $this->logDir . '/' . $filename;
        $logLine = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        
        file_put_contents($logFile, $logLine, FILE_APPEND);
    }

    /**
     * Get the current request ID.
     *
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }
}
