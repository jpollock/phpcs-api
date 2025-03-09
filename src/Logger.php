<?php

namespace PhpcsApi;

/**
 * Logger class for PHPCS API.
 * 
 * Handles logging of various events, including authentication attempts and failures.
 */
class Logger
{
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
     * Create a new Logger instance.
     */
    public function __construct()
    {
        $this->logDir = __DIR__ . '/../logs';
        $this->enabled = Config::get('logging.enabled', false);
        $this->logLevel = Config::get('logging.log_level', 'info');
        
        // Create log directory if it doesn't exist
        if ($this->enabled && !is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    /**
     * Log an authentication attempt.
     *
     * @param string      $path       Request path.
     * @param string|null $apiKey     API key used (or null if none provided).
     * @param bool        $successful Whether the authentication was successful.
     * @param string|null $reason     Reason for failure (if applicable).
     * @param string      $ip         IP address of the client.
     */
    public function logAuthAttempt(string $path, ?string $apiKey, bool $successful, ?string $reason = null, string $ip = 'unknown'): void
    {
        if (!$this->enabled) {
            return;
        }

        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'auth',
            'path' => $path,
            'ip' => $ip,
            'api_key' => $apiKey ? substr($apiKey, 0, 8) . '...' : 'none',
            'successful' => $successful,
        ];

        if (!$successful && $reason) {
            $logData['reason'] = $reason;
        }

        $this->writeToLog('auth.log', $logData);
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
     */
    public function logRequest(string $method, string $path, int $statusCode, string $ip = 'unknown', ?string $userAgent = null, float $duration = 0): void
    {
        if (!$this->enabled) {
            return;
        }

        // Don't log health checks
        if ($path === '/health' && $this->logLevel !== 'debug') {
            return;
        }

        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
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

        $this->writeToLog('access.log', $logData);
    }

    /**
     * Log an error.
     *
     * @param string      $message Error message.
     * @param string      $path    Request path.
     * @param string|null $trace   Stack trace.
     */
    public function logError(string $message, string $path = 'unknown', ?string $trace = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'error',
            'path' => $path,
            'message' => $message,
        ];

        if ($trace && $this->logLevel === 'debug') {
            $logData['trace'] = $trace;
        }

        $this->writeToLog('error.log', $logData);
    }

    /**
     * Write data to a log file.
     *
     * @param string $filename Log filename.
     * @param array  $data     Data to log.
     */
    private function writeToLog(string $filename, array $data): void
    {
        $logFile = $this->logDir . '/' . $filename;
        $logLine = json_encode($data) . "\n";
        
        file_put_contents($logFile, $logLine, FILE_APPEND);
    }
}
