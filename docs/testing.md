# PHPCS API Testing Guide

This document describes the testing approach for the PHPCS API.

## Overview

The PHPCS API includes a comprehensive testing suite to ensure the reliability, security, and performance of the API. This guide covers the different types of tests, how to run them, and best practices for writing new tests.

## Test Types

The testing suite includes several types of tests:

### Unit Tests

Unit tests focus on testing individual components in isolation. They verify that each class and method behaves as expected.

### Integration Tests

Integration tests verify that different components work together correctly. They test the interactions between classes and modules.

### Functional Tests

Functional tests verify that the API endpoints behave as expected. They simulate HTTP requests and verify the responses.

### Performance Tests

Performance tests measure the performance of the API under various conditions. They help identify bottlenecks and ensure that the API meets performance requirements.

### Security Tests

Security tests verify that the API is secure against various types of attacks. They help identify vulnerabilities and ensure that security measures are effective.

## Test Directory Structure

The tests are organized in the following directory structure:

```
/tests
  /unit           # Unit tests
    /controllers  # Tests for controllers
    /services     # Tests for services
    /middleware   # Tests for middleware
    /util         # Tests for utility classes
  /integration    # Integration tests
  /functional     # Functional tests
  /performance    # Performance tests
  /security       # Security tests
  /fixtures       # Test fixtures
  /mocks          # Mock classes
```

## Running Tests

### Running All Tests

To run all tests:

```bash
./vendor/bin/phpunit
```

### Running Specific Test Suites

To run a specific test suite:

```bash
./vendor/bin/phpunit --testsuite unit
./vendor/bin/phpunit --testsuite integration
./vendor/bin/phpunit --testsuite functional
```

### Running Specific Test Files

To run a specific test file:

```bash
./vendor/bin/phpunit tests/unit/controllers/SomeControllerTest.php
```

### Running Specific Test Methods

To run a specific test method:

```bash
./vendor/bin/phpunit --filter testMethodName
```

## PHPUnit Configuration

The PHPUnit configuration is defined in `phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.5/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         verbose="true">
    <testsuites>
        <testsuite name="unit">
            <directory>tests/unit</directory>
        </testsuite>
        <testsuite name="integration">
            <directory>tests/integration</directory>
        </testsuite>
        <testsuite name="functional">
            <directory>tests/functional</directory>
        </testsuite>
    </testsuites>
    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">src</directory>
        </include>
        <report>
            <html outputDirectory="coverage"/>
            <text outputFile="php://stdout" showUncoveredFiles="true"/>
        </report>
    </coverage>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="AUTH_ENABLED" value="true"/>
        <env name="CACHE_ENABLED" value="true"/>
        <env name="LOGGING_ENABLED" value="true"/>
        <env name="LOGGING_LEVEL" value="debug"/>
    </php>
</phpunit>
```

## Test Environment

The tests run in a dedicated testing environment, which is configured in `phpunit.xml`. The testing environment uses:

- A separate configuration file (`config.testing.php`)
- In-memory caching
- File-based logging (with a separate log directory)
- Mock API keys

## Writing Tests

### Unit Tests

Unit tests should follow these guidelines:

1. Test one thing at a time
2. Use descriptive test method names
3. Use assertions to verify expected behavior
4. Mock dependencies to isolate the component being tested
5. Test both success and failure cases

Example of a unit test:

```php
<?php

namespace PhpcsApi\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PhpcsApi\Services\CacheService;

class CacheServiceTest extends TestCase
{
    private $cacheService;

    protected function setUp(): void
    {
        $this->cacheService = new CacheService([
            'enabled' => true,
            'path' => sys_get_temp_dir() . '/phpcs-api-test-cache',
            'ttl' => 3600,
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test cache
        $this->cacheService->clear();
    }

    public function testSetAndGet(): void
    {
        $key = 'test-key';
        $value = ['foo' => 'bar'];

        // Set a value in the cache
        $this->cacheService->set($key, $value);

        // Get the value from the cache
        $result = $this->cacheService->get($key);

        // Verify that the value was retrieved correctly
        $this->assertEquals($value, $result);
    }

    public function testGetNonExistentKey(): void
    {
        $key = 'non-existent-key';

        // Get a non-existent key
        $result = $this->cacheService->get($key);

        // Verify that null is returned
        $this->assertNull($result);
    }

    public function testHas(): void
    {
        $key = 'test-key';
        $value = ['foo' => 'bar'];

        // Set a value in the cache
        $this->cacheService->set($key, $value);

        // Check if the key exists
        $exists = $this->cacheService->has($key);

        // Verify that the key exists
        $this->assertTrue($exists);

        // Check if a non-existent key exists
        $exists = $this->cacheService->has('non-existent-key');

        // Verify that the key doesn't exist
        $this->assertFalse($exists);
    }

    public function testDelete(): void
    {
        $key = 'test-key';
        $value = ['foo' => 'bar'];

        // Set a value in the cache
        $this->cacheService->set($key, $value);

        // Delete the key
        $this->cacheService->delete($key);

        // Check if the key exists
        $exists = $this->cacheService->has($key);

        // Verify that the key doesn't exist
        $this->assertFalse($exists);
    }

    public function testClear(): void
    {
        $key1 = 'test-key-1';
        $key2 = 'test-key-2';
        $value = ['foo' => 'bar'];

        // Set values in the cache
        $this->cacheService->set($key1, $value);
        $this->cacheService->set($key2, $value);

        // Clear the cache
        $this->cacheService->clear();

        // Check if the keys exist
        $exists1 = $this->cacheService->has($key1);
        $exists2 = $this->cacheService->has($key2);

        // Verify that the keys don't exist
        $this->assertFalse($exists1);
        $this->assertFalse($exists2);
    }
}
```

### Mocking

The testing suite uses PHPUnit's mocking framework to create mock objects for dependencies. This allows you to isolate the component being tested and control the behavior of its dependencies.

Example of mocking:

```php
<?php

namespace PhpcsApi\Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use PhpcsApi\Controllers\AnalyzeController;
use PhpcsApi\Services\PhpcsService;
use PhpcsApi\Services\CacheService;
use PhpcsApi\Request;
use PhpcsApi\Response;

class AnalyzeControllerTest extends TestCase
{
    public function testAnalyze(): void
    {
        // Create mock objects
        $phpcsService = $this->createMock(PhpcsService::class);
        $cacheService = $this->createMock(CacheService::class);

        // Configure the mock objects
        $phpcsService->expects($this->once())
            ->method('analyze')
            ->with('<?php echo "Hello World"; ?>', 'PSR12', [])
            ->willReturn([
                'success' => true,
                'results' => [
                    'totals' => [
                        'errors' => 1,
                        'warnings' => 0,
                        'fixable' => 1,
                    ],
                    'files' => [
                        'code.php' => [
                            'errors' => 1,
                            'warnings' => 0,
                            'messages' => [
                                [
                                    'message' => 'Double quoted string contains a single quote',
                                    'source' => 'Squiz.Strings.DoubleQuoteUsage.ContainsSingleQuote',
                                    'severity' => 5,
                                    'type' => 'ERROR',
                                    'line' => 1,
                                    'column' => 12,
                                    'fixable' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $cacheService->expects($this->once())
            ->method('has')
            ->willReturn(false);

        $cacheService->expects($this->once())
            ->method('set')
            ->willReturn(true);

        // Create the controller with the mock objects
        $controller = new AnalyzeController($phpcsService, $cacheService);

        // Create a request
        $request = new Request('POST', '/analyze', [], [], [
            'code' => '<?php echo "Hello World"; ?>',
            'standard' => 'PSR12',
        ]);

        // Call the controller method
        $response = $controller->analyze($request);

        // Verify the response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));

        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals(1, $body['results']['totals']['errors']);
    }
}
```

### Test Fixtures

Test fixtures are used to provide test data. They are stored in the `tests/fixtures` directory.

Example of a test fixture:

```json
{
  "code": "<?php echo \"Hello World\"; ?>",
  "standard": "PSR12",
  "options": {
    "report": "json",
    "showSources": true
  },
  "expected": {
    "success": true,
    "results": {
      "totals": {
        "errors": 1,
        "warnings": 0,
        "fixable": 1
      },
      "files": {
        "code.php": {
          "errors": 1,
          "warnings": 0,
          "messages": [
            {
              "message": "Double quoted string contains a single quote",
              "source": "Squiz.Strings.DoubleQuoteUsage.ContainsSingleQuote",
              "severity": 5,
              "type": "ERROR",
              "line": 1,
              "column": 12,
              "fixable": true
            }
          ]
        }
      }
    }
  }
}
```

## Code Coverage

The testing suite includes code coverage reporting to measure how much of the codebase is covered by tests. The code coverage report is generated in HTML format in the `coverage` directory.

To generate a code coverage report:

```bash
./vendor/bin/phpunit --coverage-html coverage
```

## Continuous Integration

The testing suite is integrated with a continuous integration (CI) system to run tests automatically when changes are pushed to the repository. The CI system runs all tests and generates code coverage reports.

## Performance Testing

Performance tests measure the performance of the API under various conditions. They help identify bottlenecks and ensure that the API meets performance requirements.

Example of a performance test:

```php
<?php

namespace PhpcsApi\Tests\Performance;

use PHPUnit\Framework\TestCase;
use PhpcsApi\Services\PhpcsService;

class PhpcsServicePerformanceTest extends TestCase
{
    private $phpcsService;

    protected function setUp(): void
    {
        $this->phpcsService = new PhpcsService([
            'binary' => 'phpcs',
            'standards_path' => null,
            'max_code_size' => 1024 * 1024,
            'timeout' => 30,
            'default_standard' => 'PSR12',
            'default_options' => [
                'report' => 'json',
                'colors' => false,
            ],
        ]);
    }

    public function testAnalyzePerformance(): void
    {
        $code = file_get_contents(__DIR__ . '/../fixtures/large-file.php');
        $standard = 'PSR12';
        $options = [];

        $startTime = microtime(true);
        $result = $this->phpcsService->analyze($code, $standard, $options);
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->assertLessThan(1.0, $duration, 'PHPCS analysis took too long');
        $this->assertTrue($result['success']);
    }
}
```

## Security Testing

Security tests verify that the API is secure against various types of attacks. They help identify vulnerabilities and ensure that security measures are effective.

Example of a security test:

```php
<?php

namespace PhpcsApi\Tests\Security;

use PHPUnit\Framework\TestCase;
use PhpcsApi\Services\AuthService;

class AuthServiceSecurityTest extends TestCase
{
    private $authService;

    protected function setUp(): void
    {
        $this->authService = new AuthService([
            'enabled' => true,
            'key_file' => sys_get_temp_dir() . '/phpcs-api-test-keys.json',
            'default_ttl' => 3600,
            'hash_algo' => PASSWORD_BCRYPT,
            'hash_options' => [
                'cost' => 12,
            ],
        ]);

        // Create a test key
        $this->authService->generateKey('test-key', ['analyze'], null);
    }

    protected function tearDown(): void
    {
        // Clean up test keys
        @unlink(sys_get_temp_dir() . '/phpcs-api-test-keys.json');
    }

    public function testBruteForceProtection(): void
    {
        // Try to validate an invalid key multiple times
        for ($i = 0; $i < 10; $i++) {
            $result = $this->authService->validateKey('invalid-key', 'analyze');
            $this->assertFalse($result);
        }

        // Verify that the service is now blocking requests
        $this->expectException(\PhpcsApi\Exceptions\RateLimitExceededException::class);
        $this->authService->validateKey('invalid-key', 'analyze');
    }

    public function testTimingAttackProtection(): void
    {
        // Measure the time it takes to validate a valid key
        $startTime = microtime(true);
        $this->authService->validateKey('test-key', 'analyze');
        $validKeyTime = microtime(true) - $startTime;

        // Measure the time it takes to validate an invalid key
        $startTime = microtime(true);
        $this->authService->validateKey('invalid-key', 'analyze');
        $invalidKeyTime = microtime(true) - $startTime;

        // Verify that the times are similar (within 10%)
        $this->assertLessThan($validKeyTime * 1.1, $invalidKeyTime);
        $this->assertGreaterThan($validKeyTime * 0.9, $invalidKeyTime);
    }
}
```

## Test-Driven Development

The PHPCS API follows a test-driven development (TDD) approach, where tests are written before the implementation. This ensures that the implementation meets the requirements and that all code is covered by tests.

The TDD workflow is:

1. Write a failing test
2. Implement the code to make the test pass
3. Refactor the code while keeping the test passing
4. Repeat

## Best Practices

### General Best Practices

1. Write tests for all new code
2. Keep tests simple and focused
3. Use descriptive test method names
4. Test both success and failure cases
5. Use assertions to verify expected behavior
6. Mock dependencies to isolate the component being tested
7. Use test fixtures for test data
8. Run tests regularly during development

### Unit Test Best Practices

1. Test one thing at a time
2. Mock all dependencies
3. Test all public methods
4. Test edge cases and error conditions
5. Keep tests fast and independent

### Integration Test Best Practices

1. Test interactions between components
2. Use real dependencies when possible
3. Test common workflows
4. Test error handling and edge cases

### Functional Test Best Practices

1. Test API endpoints
2. Test request validation
3. Test response formatting
4. Test error handling
5. Test authentication and authorization

## Troubleshooting

### Common Issues

1. **Tests are failing**: Check the error messages and stack traces to identify the issue. Make sure that the test environment is set up correctly.

2. **Tests are slow**: Identify slow tests and optimize them. Use profiling tools to identify bottlenecks.

3. **Tests are flaky**: Identify tests that fail intermittently and fix them. Common causes include race conditions, timing issues, and external dependencies.

4. **Low code coverage**: Identify areas with low code coverage and write additional tests.

### Debugging Tests

1. Use `var_dump()` or `print_r()` to inspect variables
2. Use `$this->expectException()` to test exceptions
3. Use `$this->markTestSkipped()` to skip tests that are not ready
4. Use `$this->markTestIncomplete()` to mark tests that are not complete

## Conclusion

Testing is a critical part of the PHPCS API development process. By following the guidelines in this document, you can ensure that the API is reliable, secure, and performant.
