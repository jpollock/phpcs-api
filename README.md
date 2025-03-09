# PHPCS API

A robust API for PHP_CodeSniffer that allows you to analyze PHP code against various coding standards with advanced features for performance, security, and monitoring.

## Features

- Analyze PHP code using PHPCS with support for all installed standards
- High-performance caching system for faster response times
- Comprehensive logging and monitoring capabilities
- Robust security with API key authentication and rate limiting
- Detailed documentation with Mermaid diagrams
- Docker support for easy deployment

## API Endpoints

### POST /analyze

Analyzes PHP code using PHPCS.

**Request:**

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

### GET /standards

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

### GET /health

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
# Analyze code
curl -X POST http://localhost:8080/analyze \
  -H "Content-Type: application/json" \
  -d '{"code":"<?php echo \"Hello World\"; ?>\n","standard":"PSR12"}'

# Get standards
curl http://localhost:8080/standards

# Check health
curl http://localhost:8080/health
```

### PHP

```php
<?php
$code = '<?php echo "Hello World"; ?>';
$data = json_encode(['code' => $code, 'standard' => 'PSR12']);

$ch = curl_init('http://localhost:8080/analyze');
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
print_r($result);
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
        try {
            URL url = new URL("http://localhost:8080/analyze");
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("POST");
            conn.setRequestProperty("Content-Type", "application/json");
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
            System.out.println("Response Code: " + responseCode);

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
- [Authentication](docs/authentication.md) - API key authentication system
- [Caching](docs/caching.md) - Caching system for improved performance
- [Configuration](docs/configuration.md) - Configuration options and best practices
- [Logging](docs/logging.md) - Enhanced logging system with multiple log levels
- [Security](docs/security.md) - Security features including rate limiting and input validation
- [Testing](docs/testing.md) - Testing approach and best practices

## License

MIT
