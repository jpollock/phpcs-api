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
     * Create a new PhpcsService instance.
     * 
     * @param CacheService|null $cacheService Cache service instance.
     */
    public function __construct(?CacheService $cacheService = null)
    {
        $this->phpcsPath = __DIR__ . '/../vendor/bin/phpcs';
        $this->tempDir = sys_get_temp_dir() . '/phpcs-api';
        $this->cacheService = $cacheService ?? new CacheService();
        
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
        // Generate cache key
        $cacheKey = $this->cacheService->generateKey($code, $standard, $options);
        
        // Check cache
        $cachedResult = $this->cacheService->get($cacheKey);
        if ($cachedResult !== null) {
            return $cachedResult;
        }
        
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
            $output = shell_exec($command);
            $result = json_decode($output, true);

            // Check if PHPCS returned valid JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid PHPCS output: ' . $output);
            }

            // Cache the result
            $this->cacheService->set($cacheKey, $result);
            
            // Return the result
            return $result;
        } catch (\Exception $e) {
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
