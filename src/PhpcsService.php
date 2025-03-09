<?php

namespace PhpcsApi;

/**
 * PhpcsService class to handle PHPCS operations.
 */
class PhpcsService
{
    /**
     * Path to PHPCS binary.
     *
     * @var string
     */
    private $phpcsPath;

    /**
     * Temporary directory for code files.
     *
     * @var string
     */
    private $tempDir;

    /**
     * Cache service instance.
     *
     * @var CacheService
     */
    private $cacheService;
    
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Create a new PhpcsService instance.
     * 
     * @param CacheService|null $cacheService Cache service instance.
     * @param Logger|null       $logger       Logger instance.
     */
    public function __construct(?CacheService $cacheService = null, ?Logger $logger = null)
    {
        $this->phpcsPath = __DIR__ . '/../vendor/bin/phpcs';
        $this->tempDir = sys_get_temp_dir() . '/phpcs-api';
        $this->cacheService = $cacheService ?? new CacheService();
        $this->logger = $logger ?? new Logger();
        
        // Create temp directory if it doesn't exist
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    /**
     * Analyze PHP code using PHPCS.
     *
     * @param string $code     PHP code to analyze.
     * @param string $standard PHPCS standard to use.
     * @param array  $options  Additional PHPCS options.
     *
     * @return array
     */
    public function analyze(string $code, string $standard = 'PSR12', array $options = []): array
    {
        $startTime = microtime(true);
        $codeSize = strlen($code);
        $cacheHit = false;
        
        // Generate cache key
        $cacheKey = $this->cacheService->generateKey($code, $standard, $options);
        
        // Check cache
        $cacheCheckStart = microtime(true);
        $cachedResult = $this->cacheService->get($cacheKey);
        $cacheCheckDuration = microtime(true) - $cacheCheckStart;
        
        // Log cache lookup performance
        $this->logger->logPerformance('cache_lookup', $cacheCheckDuration, [
            'hit' => $cachedResult !== null,
            'key' => substr($cacheKey, 0, 8) . '...',
        ]);
        
        if ($cachedResult !== null) {
            $this->logger->info('Cache hit for PHPCS analysis', [
                'standard' => $standard,
                'code_size' => $codeSize,
                'key' => substr($cacheKey, 0, 8) . '...',
            ]);
            
            // Log overall performance with cache hit
            $totalDuration = microtime(true) - $startTime;
            $this->logger->logPerformance('analyze', $totalDuration, [
                'cache_hit' => true,
                'standard' => $standard,
                'code_size' => $codeSize,
            ]);
            
            return $cachedResult;
        }
        
        $this->logger->debug('Cache miss for PHPCS analysis', [
            'standard' => $standard,
            'code_size' => $codeSize,
        ]);
        
        // Create a temporary file with the code
        $filename = $this->tempDir . '/' . uniqid('phpcs_') . '.php';
        file_put_contents($filename, $code);

        try {
            // Build PHPCS command
            $command = sprintf(
                '%s -q --report=json --standard=%s %s',
                escapeshellcmd($this->phpcsPath),
                escapeshellarg($standard),
                escapeshellarg($filename)
            );

            // Add additional options
            foreach ($options as $key => $value) {
                if (is_bool($value)) {
                    if ($value) {
                        $command .= ' --' . escapeshellarg($key);
                    }
                } else {
                    $command .= ' --' . escapeshellarg($key) . '=' . escapeshellarg($value);
                }
            }

            // Execute PHPCS
            $phpcsStart = microtime(true);
            $output = shell_exec($command);
            $phpcsExecutionTime = microtime(true) - $phpcsStart;
            
            // Log PHPCS execution performance
            $this->logger->logPerformance('phpcs_execution', $phpcsExecutionTime, [
                'standard' => $standard,
                'code_size' => $codeSize,
            ]);
            
            $result = json_decode($output, true);

            // Check if PHPCS returned valid JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Invalid PHPCS output', [
                    'output' => substr($output, 0, 1000), // Limit output size in logs
                    'error' => json_last_error_msg(),
                ]);
                throw new \Exception('Invalid PHPCS output: ' . $output);
            }

            // Cache the result
            $cacheSetStart = microtime(true);
            $this->cacheService->set($cacheKey, $result);
            $cacheSetDuration = microtime(true) - $cacheSetStart;
            
            // Log cache set performance
            $this->logger->logPerformance('cache_set', $cacheSetDuration, [
                'key' => substr($cacheKey, 0, 8) . '...',
                'result_size' => strlen(json_encode($result)),
            ]);
            
            // Log overall performance
            $totalDuration = microtime(true) - $startTime;
            $this->logger->logPerformance('analyze', $totalDuration, [
                'cache_hit' => false,
                'standard' => $standard,
                'code_size' => $codeSize,
                'phpcs_time' => $phpcsExecutionTime,
                'cache_check_time' => $cacheCheckDuration,
                'cache_set_time' => $cacheSetDuration,
            ]);
            
            // Return the result
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('PHPCS analysis failed', [
                'message' => $e->getMessage(),
                'standard' => $standard,
                'code_size' => $codeSize,
            ]);
            throw new \Exception('PHPCS analysis failed: ' . $e->getMessage());
        } finally {
            // Clean up temporary file
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
    }

    /**
     * Get available PHPCS standards.
     *
     * @return array
     */
    public function getStandards(): array
    {
        try {
            // Execute PHPCS to get available standards
            $command = sprintf('%s -i', escapeshellcmd($this->phpcsPath));
            $output = shell_exec($command);

            // Parse the output
            $standards = [];
            if (preg_match('/The installed coding standards are (.+)$/m', $output, $matches)) {
                $standardsList = $matches[1];
                $standards = array_map('trim', explode(',', $standardsList));
            }

            return $standards;
        } catch (\Exception $e) {
            throw new \Exception('Failed to get PHPCS standards: ' . $e->getMessage());
        }
    }

    /**
     * Get PHPCS version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        $command = sprintf('%s --version', escapeshellcmd($this->phpcsPath));
        $output = shell_exec($command);
        
        if (preg_match('/version (\d+\.\d+\.\d+)/', $output, $matches)) {
            return $matches[1];
        }
        
        return 'unknown';
    }
}
