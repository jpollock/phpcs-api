# PHPCS API Reference

This document provides a reference for all endpoints available in the PHPCS API.

## API Versioning

This API uses URL path versioning. All endpoints are prefixed with `/v1` to indicate the API version.
The current version is v1. Future versions will use different prefixes (e.g., `/v2`).

The API also returns the version in the `X-API-Version` response header.

## Base URL

```
https://your-api-domain.com
```

For local development:

```
http://localhost:8080
```

## Authentication

Most endpoints require authentication. See [Authentication](authentication.md) for details.

## Endpoints

### Analyze Code

Analyzes PHP code using PHP_CodeSniffer.

```
POST /v1/analyze
```

#### Request

**Standard Analysis Example:**

```json
{
  "code": "<?php echo \"Hello World\"; ?>",
  "standard": "PSR12",
  "options": {
    "report": "json",
    "severity": 5,
    "error-severity": 5,
    "warning-severity": 5,
    "tab-width": 4,
    "encoding": "utf-8",
    "extensions": "php",
    "sniffs": ["Generic.PHP.DisallowShortOpenTag"],
    "exclude": ["PSR12.Files.FileHeader"]
  }
}
```

**PHP Compatibility Example:**

```json
{
  "code": "<?php\nfunction test($param) {\n  return $param;\n}\n",
  "standard": "PHPCompatibility",
  "phpVersion": "5.6-7.4",
  "options": {
    "report": "json"
  }
}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `code` | string | Yes | PHP code to analyze |
| `standard` | string | No | PHPCS standard to use (default: PSR12) |
| `phpVersion` | string | No | PHP version(s) to test against when using PHPCompatibility standard (e.g., "7.0", "5.6-7.4") |
| `options` | object | No | Additional PHPCS options |

**Options:**

| Name | Type | Description |
|------|------|-------------|
| `report` | string | Report format (default: json) |
| `severity` | number | Minimum severity level (0-10) |
| `error-severity` | number | Minimum error severity level (0-10) |
| `warning-severity` | number | Minimum warning severity level (0-10) |
| `tab-width` | number | Number of spaces per tab |
| `encoding` | string | File encoding |
| `extensions` | string | File extensions to check |
| `sniffs` | array | Specific sniffs to include |
| `exclude` | array | Specific sniffs to exclude |

**PHP Version Compatibility Testing:**

When using the `PHPCompatibility` standard, you can specify which PHP version(s) to test against using the `phpVersion` parameter. This allows you to check if your code is compatible with specific PHP versions.

The `phpVersion` parameter accepts the following formats:
- Single version: `"7.0"` (checks compatibility with PHP 7.0)
- Version range: `"5.6-7.4"` (checks compatibility with PHP 5.6 through 7.4)
- Multiple versions: `"5.6,7.0,7.4"` (checks compatibility with specific versions)

Example request for checking compatibility with PHP 5.6 through 7.4:

```json
{
  "code": "<?php\nfunction test($param) {\n  return $param;\n}\n",
  "standard": "PHPCompatibility",
  "phpVersion": "5.6-7.4",
  "options": {
    "report": "json"
  }
}
```

This would identify that the function parameter `$param` is missing a type declaration, which is available in PHP 7.0+.

#### Response

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

#### Error Responses

**400 Bad Request**

```json
{
  "error": "Missing code parameter"
}
```

**401 Unauthorized**

```json
{
  "error": "Authentication required",
  "message": "API key is required for this endpoint"
}
```

**403 Forbidden**

```json
{
  "error": "Invalid API key",
  "message": "The provided API key is invalid or does not have the required permissions"
}
```

**429 Too Many Requests**

```json
{
  "error": "Rate limit exceeded",
  "message": "You have exceeded the 60 requests per minute rate limit.",
  "retry_after": 45
}
```

### List Standards

Lists available PHPCS standards.

```
GET /v1/standards
```

#### Response

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

### Health Check

Checks if the API is running properly.

```
GET /v1/health
```

#### Response

```json
{
  "status": "ok",
  "version": "1.0.0",
  "phpcs_version": "3.7.1",
  "timestamp": 1709942400
}
```

### Generate API Key (Development Only)

Generates a new API key.

```
POST /v1/keys/generate
```

#### Request

```json
{
  "name": "My API Key",
  "scopes": ["analyze", "standards"],
  "expires": 1741478400
}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `name` | string | No | Name or description for the key |
| `scopes` | array | No | Scopes to grant to the key |
| `expires` | number | No | Expiration timestamp |

#### Response

```json
{
  "success": true,
  "key": "abc123def456ghi789",
  "data": {
    "name": "My API Key",
    "created": 1709942400,
    "scopes": ["analyze", "standards"],
    "expires": 1741478400,
    "active": true
  }
}
```

## Error Codes

| Status Code | Description |
|-------------|-------------|
| 400 | Bad Request - The request was malformed or missing required parameters |
| 401 | Unauthorized - Authentication is required |
| 403 | Forbidden - The API key doesn't have the required permissions |
| 404 | Not Found - The requested resource doesn't exist |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error - Something went wrong on the server |

## Rate Limiting

The API implements rate limiting to prevent abuse. By default, the limits are:

- 60 requests per minute
- 1000 requests per hour

When a rate limit is exceeded, the API will respond with a 429 status code and include a `Retry-After` header indicating how many seconds to wait before retrying.

## CORS

The API supports Cross-Origin Resource Sharing (CORS) to allow browser-based applications to access it from different domains. By default, all origins are allowed, but this can be restricted in production.

## Security Headers

The API includes several security headers to protect against common web vulnerabilities:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `X-XSS-Protection: 1; mode=block`
- `X-API-Version: v1` - Indicates the current API version
- `Strict-Transport-Security: max-age=31536000; includeSubDomains`
- `Content-Security-Policy: default-src 'self'; script-src 'self'; object-src 'none'; frame-ancestors 'none'`
