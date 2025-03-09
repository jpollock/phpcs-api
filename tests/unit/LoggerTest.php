<?php

namespace PhpcsApi\Tests;

use PHPUnit\Framework\TestCase;
use PhpcsApi\Logger;
use PhpcsApi\Config;

/**
 * Test case for the enhanced Logger class.
 */
class LoggerTest extends TestCase
{
    /**
     * @var string
     */
    private $logDir;

    /**
     * @var array
     */
    private $originalConfig;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Save original config
        $this->originalConfig = Config::all();

        // Create a temporary log directory
        $this->logDir = sys_get_temp_dir() . '/phpcs-api-test-logs-' . uniqid();
        mkdir($this->logDir, 0755, true);

        // Set up test configuration
        Config::set('logging.enabled', true);
        Config::set('logging.log_level', Logger::LEVEL_DEBUG);
    }

    /**
     * Tear down the test environment.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        // Restore original config
        foreach ($this->originalConfig as $key => $value) {
            Config::set($key, $value);
        }

        // Clean up log files
        if (is_dir($this->logDir)) {
            $files = glob($this->logDir . '/*');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->logDir);
        }
    }

    /**
     * Test log level priorities.
     */
    public function testLogLevelPriorities(): void
    {
        $this->assertGreaterThan(
            Logger::LEVEL_PRIORITIES[Logger::LEVEL_DEBUG],
            Logger::LEVEL_PRIORITIES[Logger::LEVEL_INFO]
        );
        $this->assertGreaterThan(
            Logger::LEVEL_PRIORITIES[Logger::LEVEL_INFO],
            Logger::LEVEL_PRIORITIES[Logger::LEVEL_WARNING]
        );
        $this->assertGreaterThan(
            Logger::LEVEL_PRIORITIES[Logger::LEVEL_WARNING],
            Logger::LEVEL_PRIORITIES[Logger::LEVEL_ERROR]
        );
        $this->assertGreaterThan(
            Logger::LEVEL_PRIORITIES[Logger::LEVEL_ERROR],
            Logger::LEVEL_PRIORITIES[Logger::LEVEL_CRITICAL]
        );
    }

    /**
     * Test request ID generation.
     */
    public function testRequestIdGeneration(): void
    {
        $logger = new Logger();
        $requestId = $logger->getRequestId();

        $this->assertNotEmpty($requestId);
        $this->assertStringStartsWith('req_', $requestId);
    }

    /**
     * Test debug logging.
     */
    public function testDebugLogging(): void
    {
        // Create a mock logger with a custom log directory
        $logger = $this->getMockBuilder(Logger::class)
            ->onlyMethods(['writeToLog'])
            ->getMock();

        // Set expectations for the writeToLog method
        $logger->expects($this->once())
            ->method('writeToLog')
            ->with(
                $this->equalTo('application.log'),
                $this->callback(function ($data) {
                    return $data['level'] === Logger::LEVEL_DEBUG
                        && $data['message'] === 'Debug message'
                        && isset($data['context']['test'])
                        && $data['context']['test'] === 'value';
                })
            );

        // Call the debug method
        $logger->debug('Debug message', ['test' => 'value']);
    }

    /**
     * Test info logging.
     */
    public function testInfoLogging(): void
    {
        // Create a mock logger with a custom log directory
        $logger = $this->getMockBuilder(Logger::class)
            ->onlyMethods(['writeToLog'])
            ->getMock();

        // Set expectations for the writeToLog method
        $logger->expects($this->once())
            ->method('writeToLog')
            ->with(
                $this->equalTo('application.log'),
                $this->callback(function ($data) {
                    return $data['level'] === Logger::LEVEL_INFO
                        && $data['message'] === 'Info message';
                })
            );

        // Call the info method
        $logger->info('Info message');
    }

    /**
     * Test warning logging.
     */
    public function testWarningLogging(): void
    {
        // Create a mock logger with a custom log directory
        $logger = $this->getMockBuilder(Logger::class)
            ->onlyMethods(['writeToLog'])
            ->getMock();

        // Set expectations for the writeToLog method
        $logger->expects($this->once())
            ->method('writeToLog')
            ->with(
                $this->equalTo('application.log'),
                $this->callback(function ($data) {
                    return $data['level'] === Logger::LEVEL_WARNING
                        && $data['message'] === 'Warning message';
                })
            );

        // Call the warning method
        $logger->warning('Warning message');
    }

    /**
     * Test error logging.
     */
    public function testErrorLogging(): void
    {
        // Create a mock logger with a custom log directory
        $logger = $this->getMockBuilder(Logger::class)
            ->onlyMethods(['writeToLog'])
            ->getMock();

        // Set expectations for the writeToLog method
        $logger->expects($this->once())
            ->method('writeToLog')
            ->with(
                $this->equalTo('application.log'),
                $this->callback(function ($data) {
                    return $data['level'] === Logger::LEVEL_ERROR
                        && $data['message'] === 'Error message';
                })
            );

        // Call the error method
        $logger->error('Error message');
    }

    /**
     * Test critical logging.
     */
    public function testCriticalLogging(): void
    {
        // Create a mock logger with a custom log directory
        $logger = $this->getMockBuilder(Logger::class)
            ->onlyMethods(['writeToLog'])
            ->getMock();

        // Set expectations for the writeToLog method
        $logger->expects($this->once())
            ->method('writeToLog')
            ->with(
                $this->equalTo('application.log'),
                $this->callback(function ($data) {
                    return $data['level'] === Logger::LEVEL_CRITICAL
                        && $data['message'] === 'Critical message';
                })
            );

        // Call the critical method
        $logger->critical('Critical message');
    }

    /**
     * Test log level filtering.
     */
    public function testLogLevelFiltering(): void
    {
        // Set log level to WARNING
        Config::set('logging.log_level', Logger::LEVEL_WARNING);

        // Create a mock logger with a custom log directory
        $logger = $this->getMockBuilder(Logger::class)
            ->onlyMethods(['writeToLog'])
            ->getMock();

        // Set up a callback to verify log levels
        $logger->expects($this->exactly(3))
            ->method('writeToLog')
            ->with(
                $this->equalTo('application.log'),
                $this->callback(function ($data) {
                    // Only WARNING, ERROR, and CRITICAL levels should be logged
                    return in_array($data['level'], [
                        Logger::LEVEL_WARNING,
                        Logger::LEVEL_ERROR,
                        Logger::LEVEL_CRITICAL
                    ]);
                })
            );

        // Call all log methods
        $logger->debug('Debug message');
        $logger->info('Info message');
        $logger->warning('Warning message');
        $logger->error('Error message');
        $logger->critical('Critical message');
    }

    /**
     * Test performance logging.
     */
    public function testPerformanceLogging(): void
    {
        // Create a mock logger with a custom log directory
        $logger = $this->getMockBuilder(Logger::class)
            ->onlyMethods(['writeToLog'])
            ->getMock();

        // Set expectations for the writeToLog method
        $logger->expects($this->once())
            ->method('writeToLog')
            ->with(
                $this->equalTo('performance.log'),
                $this->callback(function ($data) {
                    return $data['type'] === 'performance'
                        && $data['operation'] === 'test_operation'
                        && $data['duration'] > 0
                        && isset($data['metrics']['test'])
                        && $data['metrics']['test'] === 'value';
                })
            );

        // Call the logPerformance method
        $logger->logPerformance('test_operation', 0.123, ['test' => 'value']);
    }

    /**
     * Test security event logging.
     */
    public function testSecurityEventLogging(): void
    {
        // Create a mock logger with a custom log directory
        $logger = $this->getMockBuilder(Logger::class)
            ->onlyMethods(['writeToLog', 'log'])
            ->getMock();

        // Set expectations for the writeToLog method
        $logger->expects($this->once())
            ->method('writeToLog')
            ->with(
                $this->equalTo('security.log'),
                $this->callback(function ($data) {
                    return $data['type'] === 'security'
                        && $data['event'] === 'test_event'
                        && $data['message'] === 'Security event message'
                        && isset($data['context']['test'])
                        && $data['context']['test'] === 'value';
                })
            );

        // Set expectations for the log method
        $logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo(Logger::LEVEL_WARNING),
                $this->equalTo('Security event: test_event - Security event message'),
                $this->equalTo(['test' => 'value'])
            );

        // Call the logSecurityEvent method
        $logger->logSecurityEvent('test_event', 'Security event message', ['test' => 'value']);
    }

    /**
     * Test request logging.
     */
    public function testRequestLogging(): void
    {
        // Create a mock logger with a custom log directory
        $logger = $this->getMockBuilder(Logger::class)
            ->onlyMethods(['writeToLog'])
            ->getMock();

        // Set expectations for the writeToLog method
        $logger->expects($this->once())
            ->method('writeToLog')
            ->with(
                $this->equalTo('access.log'),
                $this->callback(function ($data) {
                    return $data['type'] === 'request'
                        && $data['method'] === 'GET'
                        && $data['path'] === '/test'
                        && $data['status'] === 200
                        && $data['ip'] === '127.0.0.1'
                        && $data['user_agent'] === 'Test Agent'
                        && isset($data['performance'])
                        && isset($data['headers'])
                        && isset($data['headers']['User-Agent'])
                        && $data['headers']['User-Agent'] === 'Test Agent';
                })
            );

        // Call the logRequest method
        $logger->logRequest(
            'GET',
            '/test',
            200,
            '127.0.0.1',
            'Test Agent',
            0.123,
            ['User-Agent' => 'Test Agent'],
            ['param' => 'value']
        );
    }

    /**
     * Test sanitization of sensitive data.
     */
    public function testSanitizeSensitiveData(): void
    {
        // Create a mock logger with a custom log directory
        $logger = $this->getMockBuilder(Logger::class)
            ->onlyMethods(['writeToLog'])
            ->getMock();

        // Set expectations for the writeToLog method
        $logger->expects($this->once())
            ->method('writeToLog')
            ->with(
                $this->equalTo('access.log'),
                $this->callback(function ($data) {
                    return isset($data['headers']['Authorization'])
                        && $data['headers']['Authorization'] === '[REDACTED]'
                        && isset($data['headers']['X-Api-Key'])
                        && $data['headers']['X-Api-Key'] === '[REDACTED]'
                        && isset($data['headers']['User-Agent'])
                        && $data['headers']['User-Agent'] === 'Test Agent';
                })
            );

        // Call the logRequest method with sensitive headers
        $logger->logRequest(
            'GET',
            '/test',
            200,
            '127.0.0.1',
            'Test Agent',
            0.123,
            [
                'Authorization' => 'Bearer token123',
                'X-Api-Key' => 'secret-key',
                'User-Agent' => 'Test Agent',
            ],
            []
        );
    }
}
