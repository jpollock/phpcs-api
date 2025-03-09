<?php

use PHPUnit\Framework\TestCase;
use PhpcsApi\AuthService;
use PhpcsApi\Request;

/**
 * Test case for the AuthService class.
 */
class AuthServiceTest extends TestCase
{
    /**
     * Temporary file for API keys.
     *
     * @var string
     */
    private $keysFile;

    /**
     * AuthService instance.
     *
     * @var AuthService
     */
    private $authService;

    /**
     * Set up the test case.
     */
    protected function setUp(): void
    {
        // Create a temporary file for API keys
        $this->keysFile = sys_get_temp_dir() . '/phpcs-api-test-keys-' . uniqid() . '.json';
        file_put_contents($this->keysFile, json_encode([]));

        // Create an AuthService instance
        $this->authService = new AuthService($this->keysFile);
    }

    /**
     * Tear down the test case.
     */
    protected function tearDown(): void
    {
        // Remove the temporary file
        if (file_exists($this->keysFile)) {
            unlink($this->keysFile);
        }
    }

    /**
     * Test generating an API key.
     */
    public function testGenerateKey(): void
    {
        // Generate a key
        $key = $this->authService->generateKey([
            'name' => 'Test Key',
            'scopes' => ['analyze', 'standards'],
        ]);

        // Check that the key is a string
        $this->assertIsString($key);
        $this->assertNotEmpty($key);

        // Check that the key is 48 characters long (24 bytes in hex)
        $this->assertEquals(48, strlen($key));

        // Check that the key is stored in the keys file
        $keys = json_decode(file_get_contents($this->keysFile), true);
        $this->assertArrayHasKey($key, $keys);

        // Check that the key data is correct
        $keyData = $keys[$key];
        $this->assertEquals('Test Key', $keyData['name']);
        $this->assertEquals(['analyze', 'standards'], $keyData['scopes']);
        $this->assertTrue($keyData['active']);
        $this->assertArrayHasKey('created', $keyData);
    }

    /**
     * Test validating an API key.
     */
    public function testValidateKey(): void
    {
        // Generate a key
        $key = $this->authService->generateKey([
            'name' => 'Test Key',
            'scopes' => ['analyze', 'standards'],
        ]);

        // Test valid key without scope
        $this->assertTrue($this->authService->validateKey($key));

        // Test valid key with valid scope
        $this->assertTrue($this->authService->validateKey($key, 'analyze'));
        $this->assertTrue($this->authService->validateKey($key, 'standards'));

        // Test valid key with invalid scope
        $this->assertFalse($this->authService->validateKey($key, 'admin'));

        // Test invalid key
        $this->assertFalse($this->authService->validateKey('invalid-key'));

        // Test empty key
        $this->assertFalse($this->authService->validateKey(''));
    }

    /**
     * Test revoking an API key.
     */
    public function testRevokeKey(): void
    {
        // Generate a key
        $key = $this->authService->generateKey([
            'name' => 'Test Key',
            'scopes' => ['analyze', 'standards'],
        ]);

        // Test that the key is valid
        $this->assertTrue($this->authService->validateKey($key));

        // Revoke the key
        $result = $this->authService->revokeKey($key);
        $this->assertTrue($result);

        // Test that the key is no longer valid
        $this->assertFalse($this->authService->validateKey($key));

        // Test revoking an invalid key
        $result = $this->authService->revokeKey('invalid-key');
        $this->assertFalse($result);
    }

    /**
     * Test extracting an API key from a request.
     */
    public function testExtractKeyFromRequest(): void
    {
        // Create a request with an Authorization header
        $request = new Request('GET', '/test');
        $request->headers['Authorization'] = 'Bearer test-key-1';
        $this->assertEquals('test-key-1', $this->authService->extractKeyFromRequest($request));

        // Create a request with an X-API-Key header
        $request = new Request('GET', '/test');
        $request->headers['X-Api-Key'] = 'test-key-2';
        $this->assertEquals('test-key-2', $this->authService->extractKeyFromRequest($request));

        // Create a request with an api_key query parameter
        $request = new Request('GET', '/test');
        $_GET['api_key'] = 'test-key-3';
        $this->assertEquals('test-key-3', $this->authService->extractKeyFromRequest($request));

        // Create a request without an API key
        $request = new Request('GET', '/test');
        unset($_GET['api_key']);
        $this->assertNull($this->authService->extractKeyFromRequest($request));
    }
}
