<?php

use PHPUnit\Framework\TestCase;
use PhpcsApi\AuthMiddleware;
use PhpcsApi\AuthService;
use PhpcsApi\Request;
use PhpcsApi\Response;
use PhpcsApi\Config;

/**
 * Test case for the AuthMiddleware class.
 */
class AuthMiddlewareTest extends TestCase
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
     * AuthMiddleware instance.
     *
     * @var AuthMiddleware
     */
    private $authMiddleware;

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

        // Generate a test API key
        $this->testKey = $this->authService->generateKey([
            'name' => 'Test Key',
            'scopes' => ['analyze', 'standards'],
        ]);

        // Create an AuthMiddleware instance
        $this->authMiddleware = new AuthMiddleware(
            $this->authService,
            ['/protected', '/analyze', '/standards'],
            [
                '/analyze' => 'analyze',
                '/standards' => 'standards',
            ]
        );

        // Mock Config class
        Config::load([
            'auth' => [
                'enabled' => true,
            ],
        ]);
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
     * Test processing a request for a public path.
     */
    public function testProcessPublicPath(): void
    {
        // Create a request for a public path
        $request = new Request('GET', '/public');

        // Create a next handler that returns a success response
        $next = function (Request $request) {
            return new Response(200, ['Content-Type' => 'application/json'], '{"success":true}');
        };

        // Process the request
        $response = $this->authMiddleware->process($request, $next);

        // Check that the response is a success
        $this->assertEquals(200, $response->statusCode);
        $this->assertEquals('{"success":true}', $response->body);
    }

    /**
     * Test processing a request for a protected path without an API key.
     */
    public function testProcessProtectedPathWithoutApiKey(): void
    {
        // Create a request for a protected path
        $request = new Request('GET', '/protected');

        // Create a next handler that should not be called
        $next = function (Request $request) {
            $this->fail('Next handler should not be called');
        };

        // Process the request
        $response = $this->authMiddleware->process($request, $next);

        // Check that the response is a 401 Unauthorized
        $this->assertEquals(401, $response->statusCode);
        $this->assertStringContainsString('Authentication required', $response->body);
    }

    /**
     * Test processing a request for a protected path with an invalid API key.
     */
    public function testProcessProtectedPathWithInvalidApiKey(): void
    {
        // Create a request for a protected path with an invalid API key
        $request = new Request('GET', '/protected');
        $request->headers['Authorization'] = 'Bearer invalid-key';

        // Create a next handler that should not be called
        $next = function (Request $request) {
            $this->fail('Next handler should not be called');
        };

        // Process the request
        $response = $this->authMiddleware->process($request, $next);

        // Check that the response is a 403 Forbidden
        $this->assertEquals(403, $response->statusCode);
        $this->assertStringContainsString('Invalid API key', $response->body);
    }

    /**
     * Test processing a request for a protected path with a valid API key.
     */
    public function testProcessProtectedPathWithValidApiKey(): void
    {
        // Create a request for a protected path with a valid API key
        $request = new Request('GET', '/protected');
        $request->headers['Authorization'] = 'Bearer ' . $this->testKey;

        // Create a next handler that returns a success response
        $next = function (Request $request) {
            // Check that the API key data is added to the request
            $this->assertEquals($this->testKey, $request->apiKey);
            $this->assertIsArray($request->apiKeyData);
            $this->assertEquals('Test Key', $request->apiKeyData['name']);

            return new Response(200, ['Content-Type' => 'application/json'], '{"success":true}');
        };

        // Process the request
        $response = $this->authMiddleware->process($request, $next);

        // Check that the response is a success
        $this->assertEquals(200, $response->statusCode);
        $this->assertEquals('{"success":true}', $response->body);
    }

    /**
     * Test processing a request for a path with a scope requirement.
     */
    public function testProcessPathWithScopeRequirement(): void
    {
        // Create a request for a path with a scope requirement
        $request = new Request('GET', '/analyze');
        $request->headers['Authorization'] = 'Bearer ' . $this->testKey;

        // Create a next handler that returns a success response
        $next = function (Request $request) {
            return new Response(200, ['Content-Type' => 'application/json'], '{"success":true}');
        };

        // Process the request
        $response = $this->authMiddleware->process($request, $next);

        // Check that the response is a success
        $this->assertEquals(200, $response->statusCode);
        $this->assertEquals('{"success":true}', $response->body);

        // Create a request for a path with a scope requirement that the key doesn't have
        $request = new Request('GET', '/admin');
        $request->headers['Authorization'] = 'Bearer ' . $this->testKey;

        // Add the path and scope to the middleware
        $this->authMiddleware->addProtectedPath('/admin', 'admin');

        // Process the request
        $response = $this->authMiddleware->process($request, $next);

        // Check that the response is a 403 Forbidden
        $this->assertEquals(403, $response->statusCode);
        $this->assertStringContainsString('Invalid API key', $response->body);
    }

    /**
     * Test processing a request when authentication is disabled.
     */
    public function testProcessWithAuthenticationDisabled(): void
    {
        // Disable authentication
        Config::load([
            'auth' => [
                'enabled' => false,
            ],
        ]);

        // Create a request for a protected path without an API key
        $request = new Request('GET', '/protected');

        // Create a next handler that returns a success response
        $next = function (Request $request) {
            return new Response(200, ['Content-Type' => 'application/json'], '{"success":true}');
        };

        // Process the request
        $response = $this->authMiddleware->process($request, $next);

        // Check that the response is a success
        $this->assertEquals(200, $response->statusCode);
        $this->assertEquals('{"success":true}', $response->body);
    }
}
