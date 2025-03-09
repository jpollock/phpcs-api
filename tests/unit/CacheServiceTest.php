<?php

use PHPUnit\Framework\TestCase;
use PhpcsApi\CacheService;
use PhpcsApi\Config;

/**
 * Test case for the CacheService class.
 */
class CacheServiceTest extends TestCase
{
    /**
     * Cache service instance.
     *
     * @var CacheService
     */
    private $cacheService;

    /**
     * Set up the test case.
     */
    protected function setUp(): void
    {
        // Mock Config class
        Config::load([
            'cache' => [
                'enabled' => true,
                'ttl' => 60, // 1 minute for testing
            ],
        ]);

        // Create a CacheService instance
        $this->cacheService = new CacheService();
        
        // Clear cache before each test
        $this->cacheService->clear();
    }

    /**
     * Tear down the test case.
     */
    protected function tearDown(): void
    {
        // Clear cache after each test
        $this->cacheService->clear();
    }

    /**
     * Test generating a cache key.
     */
    public function testGenerateKey(): void
    {
        // Generate a key
        $key1 = $this->cacheService->generateKey('<?php echo "test"; ?>', 'PSR12', []);
        $key2 = $this->cacheService->generateKey('<?php echo "test"; ?>', 'PSR12', []);
        $key3 = $this->cacheService->generateKey('<?php echo "test2"; ?>', 'PSR12', []);
        
        // Check that the keys are strings
        $this->assertIsString($key1);
        $this->assertNotEmpty($key1);
        
        // Check that the same input produces the same key
        $this->assertEquals($key1, $key2);
        
        // Check that different input produces different keys
        $this->assertNotEquals($key1, $key3);
        
        // Check that options affect the key
        $key4 = $this->cacheService->generateKey('<?php echo "test"; ?>', 'PSR12', ['severity' => 5]);
        $this->assertNotEquals($key1, $key4);
        
        // Check that option order doesn't affect the key
        $key5 = $this->cacheService->generateKey('<?php echo "test"; ?>', 'PSR12', ['severity' => 5, 'report' => 'json']);
        $key6 = $this->cacheService->generateKey('<?php echo "test"; ?>', 'PSR12', ['report' => 'json', 'severity' => 5]);
        $this->assertEquals($key5, $key6);
    }

    /**
     * Test setting and getting cached results.
     */
    public function testSetAndGet(): void
    {
        // Generate a key
        $key = $this->cacheService->generateKey('<?php echo "test"; ?>', 'PSR12', []);
        
        // Create a test result
        $result = [
            'totals' => [
                'errors' => 0,
                'warnings' => 1,
            ],
            'files' => [
                'test.php' => [
                    'errors' => 0,
                    'warnings' => 1,
                    'messages' => [
                        [
                            'message' => 'Test warning',
                            'source' => 'Test.Source',
                            'severity' => 5,
                            'type' => 'WARNING',
                            'line' => 1,
                            'column' => 1,
                        ],
                    ],
                ],
            ],
        ];
        
        // Set the cache
        $this->assertTrue($this->cacheService->set($key, $result));
        
        // Get the cache
        $cachedResult = $this->cacheService->get($key);
        
        // Check that the cached result matches the original
        $this->assertEquals($result, $cachedResult);
        
        // Check that a non-existent key returns null
        $this->assertNull($this->cacheService->get('non-existent-key'));
    }

    /**
     * Test cache expiration.
     */
    public function testExpiration(): void
    {
        // Generate a key
        $key = $this->cacheService->generateKey('<?php echo "test"; ?>', 'PSR12', []);
        
        // Create a test result
        $result = ['test' => 'value'];
        
        // Set the cache
        $this->assertTrue($this->cacheService->set($key, $result));
        
        // Get the cache immediately (should be a hit)
        $this->assertEquals($result, $this->cacheService->get($key));
        
        // Mock the TTL by modifying the file time
        $cacheDir = new \ReflectionProperty(CacheService::class, 'cacheDir');
        $cacheDir->setAccessible(true);
        $cacheFilePath = $cacheDir->getValue($this->cacheService) . '/' . $key . '.json';
        
        // Set the file time to 2 minutes ago (beyond our 1 minute TTL)
        touch($cacheFilePath, time() - 120);
        
        // Get the cache after expiration (should be null)
        $this->assertNull($this->cacheService->get($key));
        
        // Check that the expired cache file was deleted
        $this->assertFileDoesNotExist($cacheFilePath);
    }

    /**
     * Test clearing the cache.
     */
    public function testClear(): void
    {
        // Generate keys
        $key1 = $this->cacheService->generateKey('<?php echo "test1"; ?>', 'PSR12', []);
        $key2 = $this->cacheService->generateKey('<?php echo "test2"; ?>', 'PSR12', []);
        
        // Set the cache
        $this->cacheService->set($key1, ['test' => 'value1']);
        $this->cacheService->set($key2, ['test' => 'value2']);
        
        // Check that the cache entries exist
        $this->assertNotNull($this->cacheService->get($key1));
        $this->assertNotNull($this->cacheService->get($key2));
        
        // Clear the cache
        $this->assertTrue($this->cacheService->clear());
        
        // Check that the cache entries are gone
        $this->assertNull($this->cacheService->get($key1));
        $this->assertNull($this->cacheService->get($key2));
    }

    /**
     * Test cache statistics.
     */
    public function testGetStats(): void
    {
        // Check initial stats (empty cache)
        $stats = $this->cacheService->getStats();
        $this->assertTrue($stats['enabled']);
        $this->assertEquals(0, $stats['count']);
        $this->assertEquals(0, $stats['size']);
        $this->assertNull($stats['oldest']);
        $this->assertNull($stats['newest']);
        
        // Add some cache entries
        $key1 = $this->cacheService->generateKey('<?php echo "test1"; ?>', 'PSR12', []);
        $key2 = $this->cacheService->generateKey('<?php echo "test2"; ?>', 'PSR12', []);
        $this->cacheService->set($key1, ['test' => 'value1']);
        $this->cacheService->set($key2, ['test' => 'value2']);
        
        // Check updated stats
        $stats = $this->cacheService->getStats();
        $this->assertTrue($stats['enabled']);
        $this->assertEquals(2, $stats['count']);
        $this->assertGreaterThan(0, $stats['size']);
        $this->assertNotNull($stats['oldest']);
        $this->assertNotNull($stats['newest']);
    }
}
