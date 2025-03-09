# PHPCS API

A robust API for PHP_CodeSniffer that allows you to analyze PHP code against various coding standards with advanced features for performance, security, and monitoring.

## API Versioning

This API uses URL path versioning. All endpoints are prefixed with `/v1` to indicate the API version.
The current version is v1. Future versions will use different prefixes (e.g., `/v2`).

The API also returns the version in the `X-API-Version` response header.

## Features

- Analyze PHP code using PHPCS with support for all installed standards
- PHP version compatibility testing with the PHPCompatibility standard
- High-performance caching system for faster response times
- Comprehensive logging and monitoring capabilities
- Robust security with API key authentication and rate limiting
- Detailed documentation with Mermaid diagrams
- Docker support for easy deployment

## API Endpoints

### POST /v1/analyze

Analyzes PHP code using PHPCS.

**Request:**

Standard Analysis:
```json
{
  "code": "<?php echo \"Hello World\"; ?>\n",
  "standard": "PSR12",
  "options": {
    "report": "json",
    "showSources": true
  }
}
```

PHP Version Compatibility Testing:
```json
{
  "code": "<?php\nfunction test(string $param): string {\n  return $param;\n}\n",
  "standard": "PHPCompatibility",
  "phpVersion": "5.6-7.4",
  "options": {
    "report": "json"
  }
}
```

**Response:**

```json
{
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
```

### GET /v1/standards

Lists available PHPCS standards.

**Response:**

```json
{
  "success": true,
  "standards": [
    "PSR1",
    "PSR2",
    "PSR12",
    "PEAR",
    "Squiz",
    "Zend",
    "PHPCompatibility",
    "PHPCompatibilityWP"
  ]
}
```

### GET /v1/health

Checks if the service is running properly.

**Response:**

```json
{
  "status": "ok",
  "version": "1.0.0",
  "phpcs_version": "3.7.1"
}
```

## Installation

### Prerequisites

- PHP 7.4 or higher
- Composer
- Docker (optional)

### Local Installation

1. Clone the repository:

```bash
git clone https://github.com/yourusername/phpcs-api.git
cd phpcs-api
```

2. Install dependencies:

```bash
composer install
```

3. Start a local PHP server:

```bash
php -S localhost:8080 -t public
```

4. The API is now available at http://localhost:8080

### Docker Installation

1. Clone the repository:

```bash
git clone https://github.com/yourusername/phpcs-api.git
cd phpcs-api
```

2. Build and start the Docker container:

```bash
docker-compose up -d
```

3. The API is now available at http://localhost:8080

## Usage Examples

### cURL

```bash
# Analyze code with PSR12 standard
curl -X POST http://localhost:8080/v1/analyze \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{"code":"<?php echo \"Hello World\"; ?>\n","standard":"PSR12"}'

# PHP version compatibility testing
curl -X POST http://localhost:8080/v1/analyze \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{"code":"<?php\nfunction test(string $param): string {\n  return $param;\n}\n","standard":"PHPCompatibility","phpVersion":"5.6-7.4"}'

# Get standards
curl http://localhost:8080/v1/standards \
  -H "Authorization: Bearer YOUR_API_KEY"

# Check health (no authentication required)
curl http://localhost:8080/v1/health
```

### PHP

```php
<?php
// Standard analysis
$code = '<?php echo "Hello World"; ?>';
$data = json_encode([
    'code' => $code,
    'standard' => 'PSR12'
]);

$ch = curl_init('http://localhost:8080/v1/analyze');
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer YOUR_API_KEY'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
print_r($result);

// PHP version compatibility testing
$compatibilityCode = '<?php
function test(string $param): string {
    return $param;
}';

$compatibilityData = json_encode([
    'code' => $compatibilityCode,
    'standard' => 'PHPCompatibility',
    'phpVersion' => '5.6-7.4',
    'options' => [
        'report' => 'json'
    ]
]);

$ch = curl_init('http://localhost:8080/v1/analyze');
curl_setopt($ch, CURLOPT_POSTFIELDS, $compatibilityData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer YOUR_API_KEY'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$compatibilityResponse = curl_exec($ch);
curl_close($ch);

$compatibilityResult = json_decode($compatibilityResponse, true);
print_r($compatibilityResult);
```

### Java

```java
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.util.Scanner;

public class PhpcsApiExample {
    public static void main(String[] args) {
        // Standard analysis
        analyzeWithPsr12();
        
        // PHP version compatibility testing
        analyzeWithPhpCompatibility();
    }
    
    private static void analyzeWithPsr12() {
        try {
            URL url = new URL("http://localhost:8080/v1/analyze");
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("POST");
            conn.setRequestProperty("Content-Type", "application/json");
            conn.setRequestProperty("Authorization", "Bearer YOUR_API_KEY");
            conn.setDoOutput(true);

            String code = "<?php echo \\\"Hello World\\\"; ?>\\n";
            String requestBody = "{"
                + "\"code\": \"" + code + "\","
                + "\"standard\": \"PSR12\""
                + "}";

            try(OutputStream os = conn.getOutputStream()) {
                byte[] input = requestBody.getBytes(StandardCharsets.UTF_8);
                os.write(input, 0, input.length);
            }

            int responseCode = conn.getResponseCode();
            System.out.println("PSR12 Analysis - Response Code: " + responseCode);

            try(Scanner scanner = new Scanner(conn.getInputStream(), StandardCharsets.UTF_8.name())) {
                String response = scanner.useDelimiter("\\A").next();
                System.out.println(response);
            }
        } catch (Exception e) {
            e.printStackTrace();
        }
    }
    
    private static void analyzeWithPhpCompatibility() {
        try {
            URL url = new URL("http://localhost:8080/v1/analyze");
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("POST");
            conn.setRequestProperty("Content-Type", "application/json");
            conn.setRequestProperty("Authorization", "Bearer YOUR_API_KEY");
            conn.setDoOutput(true);

            String code = "<?php\\nfunction test(string $param): string {\\n  return $param;\\n}\\n";
            String requestBody = "{"
                + "\"code\": \"" + code + "\","
                + "\"standard\": \"PHPCompatibility\","
                + "\"phpVersion\": \"5.6-7.4\","
                + "\"options\": {"
                + "  \"report\": \"json\""
                + "}"
                + "}";

            try(OutputStream os = conn.getOutputStream()) {
                byte[] input = requestBody.getBytes(StandardCharsets.UTF_8);
                os.write(input, 0, input.length);
            }

            int responseCode = conn.getResponseCode();
            System.out.println("PHP Compatibility Analysis - Response Code: " + responseCode);

            try(Scanner scanner = new Scanner(conn.getInputStream(), StandardCharsets.UTF_8.name())) {
                String response = scanner.useDelimiter("\\A").next();
                System.out.println(response);
            }
        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}
```

## Documentation

Comprehensive documentation is available in the `docs` directory:

- [Architecture](docs/architecture.md) - Overview of the system architecture with diagrams
- [API Reference](docs/api-reference.md) - Detailed API endpoint documentation
- [OpenAPI Specification](docs/openapi.html) - Interactive API documentation using OpenAPI 3.0
- [Authentication](docs/authentication.md) - API key authentication system
- [Caching](docs/caching.md) - Caching system for improved performance
- [Configuration](docs/configuration.md) - Configuration options and best practices
- [Logging](docs/logging.md) - Enhanced logging system with multiple log levels
- [Security](docs/security.md) - Security features including rate limiting and input validation
- [Testing](docs/testing.md) - Testing approach and best practices

The OpenAPI specification is available in the root directory as `openapi.yml`. You can view it interactively using the [OpenAPI documentation viewer](docs/openapi.html).

## Testing

### Manual Tests

Manual test scripts are available in the `tests/manual` directory:

- [PHP Version Compatibility Testing](tests/manual/test-php-version.php) - Test script for PHP version compatibility testing
- [Manual Tests README](tests/manual/README.md) - Instructions for using the manual test scripts

These scripts help you verify that the API is working correctly, especially for features like PHP version compatibility testing.

### Testing with Postman

A Postman collection is available in the `postman` directory for testing the API:

- [Postman Collection](postman/phpcs-api-collection.json) - Collection of API requests with tests
- [Environment File](postman/environment.json) - Environment variables for the collection
- [Postman README](postman/README.md) - Instructions for using the collection

The collection includes requests for all API endpoints and tests to verify the API's functionality. You can run the tests using Postman or Newman (CLI version of Postman).

### Authentication for Local Testing

The API requires authentication for most endpoints. See the [Local Testing Guide](docs/local-testing.md) for instructions on how to:

1. Generate API keys for testing
2. Use API keys with cURL and Postman
3. Troubleshoot common authentication issues

### Running Tests with Newman

```bash
# Install Newman
npm install -g newman

# Run the tests
cd phpcs-api
newman run postman/phpcs-api-collection.json -e postman/environment.json
```

See the [Postman README](postman/README.md) for more details.

## License

MIT
