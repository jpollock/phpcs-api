# PHPCS API Documentation

Welcome to the PHPCS API documentation. This documentation provides comprehensive information about the PHPCS API, including its architecture, features, configuration, and usage.

## Table of Contents

### Core Documentation

- [Architecture](architecture.md) - Overview of the system architecture with diagrams
- [API Reference](api-reference.md) - Detailed API endpoint documentation
- [Authentication](authentication.md) - API key authentication system
- [Local Testing](local-testing.md) - Setting up authentication for local testing
- [Caching](caching.md) - Caching system for improved performance
- [Configuration](configuration.md) - Configuration options and best practices
- [Logging](logging.md) - Enhanced logging system with multiple log levels
- [Security](security.md) - Security features including rate limiting and input validation
- [Testing](testing.md) - Testing approach and best practices

## Overview

The PHPCS API is a robust API for PHP_CodeSniffer that allows you to analyze PHP code against various coding standards. It provides a RESTful interface to PHPCS functionality, making it easy to integrate code quality checks into your development workflow.

### Key Features

- **Code Analysis**: Analyze PHP code against various coding standards, including PSR1, PSR2, PSR12, and more.
- **Performance**: High-performance caching system for faster response times.
- **Security**: Robust security with API key authentication, rate limiting, and input validation.
- **Monitoring**: Comprehensive logging and monitoring capabilities with multiple log levels.
- **Flexibility**: Support for all installed PHPCS standards and custom standards.
- **Ease of Use**: Simple REST API with clear documentation.
- **Deployment**: Docker support for easy deployment.

## Getting Started

To get started with the PHPCS API, follow these steps:

1. **Installation**: See the [README.md](../README.md) for installation instructions.
2. **Configuration**: Configure the API using the [configuration guide](configuration.md).
3. **Authentication**: Set up API keys using the [authentication guide](authentication.md).
4. **Local Testing**: If you're testing locally, see the [local testing guide](local-testing.md).
5. **Usage**: Use the API endpoints as described in the [API reference](api-reference.md).

## Architecture

The PHPCS API follows a middleware-based architecture with a clear separation of concerns between components. The main components are:

- **Router**: Maps HTTP requests to controller methods.
- **Middleware**: Processes requests before they reach the controllers (security, authentication).
- **Controllers**: Handle the business logic for each endpoint.
- **Services**: Provide functionality to the controllers (PHPCS, authentication, caching).
- **Logger**: Records events and metrics for monitoring and debugging.

For more details, see the [architecture documentation](architecture.md).

## API Endpoints

The PHPCS API provides the following main endpoints:

- `POST /analyze`: Analyzes PHP code using PHPCS.
- `GET /standards`: Lists available PHPCS standards.
- `GET /health`: Checks if the service is running properly.
- `POST /cache/clear`: Clears the cache (admin only).
- `GET /cache/stats`: Shows cache statistics (admin only).
- `POST /keys/generate`: Generates API keys (admin/development only).

For detailed information about each endpoint, including request and response formats, see the [API reference](api-reference.md) or the [OpenAPI specification](openapi.html).

## Security

The PHPCS API implements multiple security measures:

- **Authentication**: API key authentication for protected endpoints.
- **Rate Limiting**: Prevents abuse and ensures fair usage.
- **Input Validation**: Validates and sanitizes all input to prevent injection attacks.
- **Security Headers**: Protects against common web vulnerabilities.
- **CORS**: Controls which domains can access the API from browser-based applications.

For more details, see the [security documentation](security.md).

## Performance

The PHPCS API includes several mechanisms to ensure good performance:

- **Caching**: Stores PHPCS analysis results for faster response times.
- **Rate Limiting**: Prevents abuse and ensures fair usage.
- **Performance Metrics**: Logs performance data for monitoring and optimization.
- **Configurable Thresholds**: Allows you to set thresholds for slow operations.

For more details, see the [caching documentation](caching.md) and [logging documentation](logging.md).

## Logging

The PHPCS API includes a comprehensive logging system that provides visibility into application behavior, performance, and security events. The logging system supports multiple log levels and writes to several log files, each focused on a specific type of information.

For more details, see the [logging documentation](logging.md).

## Testing

The PHPCS API includes a comprehensive testing suite to ensure the reliability, security, and performance of the API. The testing suite includes unit tests, integration tests, functional tests, performance tests, and security tests.

For more details, see the [testing documentation](testing.md).

## Contributing

Contributions to the PHPCS API are welcome! Please follow the guidelines in the [README.md](../README.md) file.

## License

The PHPCS API is open-source software licensed under the MIT license.
