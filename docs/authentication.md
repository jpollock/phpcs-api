# PHPCS API Authentication

This document describes the authentication system used by the PHPCS API.

## Overview

The PHPCS API uses API key authentication to secure access to protected endpoints. This ensures that only authorized clients can use the API and helps prevent abuse.

## API Keys

API keys are long, randomly generated strings that act as credentials for accessing the API. Each key has:

- A unique identifier (the key itself)
- Associated metadata (name, creation date, etc.)
- Scopes that define what the key can access
- Optional expiration date

## Authentication Methods

The API supports three methods for providing your API key:

### 1. Authorization Header (Recommended)

```
Authorization: Bearer YOUR_API_KEY
```

Example:
```
curl -X POST https://api.example.com/analyze \
  -H "Authorization: Bearer abc123def456ghi789" \
  -H "Content-Type: application/json" \
  -d '{"code":"<?php echo \"Hello World\"; ?>"}'
```

### 2. X-API-Key Header

```
X-API-Key: YOUR_API_KEY
```

Example:
```
curl -X POST https://api.example.com/analyze \
  -H "X-API-Key: abc123def456ghi789" \
  -H "Content-Type: application/json" \
  -d '{"code":"<?php echo \"Hello World\"; ?>"}'
```

### 3. Query Parameter

```
?api_key=YOUR_API_KEY
```

Example:
```
curl -X GET "https://api.example.com/standards?api_key=abc123def456ghi789"
```

**Note**: The query parameter method is less secure and should only be used for testing or when the other methods are not available.

## Protected Endpoints

By default, the following endpoints require authentication:

- `POST /analyze` - Requires the `analyze` scope
- `GET /standards` - Requires the `standards` scope
- `POST /keys/generate` - Requires the `admin` scope (only available in development mode)

Public endpoints that don't require authentication:

- `GET /health` - Health check endpoint
- `GET /metrics` - Metrics endpoint (if enabled)

## Scopes

Scopes define what actions an API key is authorized to perform:

- `analyze` - Allows analyzing code with PHPCS
- `standards` - Allows listing available PHPCS standards
- `admin` - Allows administrative actions like generating new API keys

## Managing API Keys

### Generating Keys

API keys can be generated using the command-line tool:

```bash
php bin/manage-keys.php generate --name="My API Key" --scopes=analyze,standards
```

Options:
- `--name`: A descriptive name for the key
- `--scopes`: Comma-separated list of scopes
- `--expires`: Expiration timestamp (optional)

### Listing Keys

```bash
php bin/manage-keys.php list
```

### Revoking Keys

```bash
php bin/manage-keys.php revoke YOUR_API_KEY
```

### Viewing Key Information

```bash
php bin/manage-keys.php info YOUR_API_KEY
```

## Security Considerations

1. **Store API keys securely**: Treat API keys like passwords. Don't hardcode them in your applications or commit them to version control.

2. **Use HTTPS**: Always use HTTPS when communicating with the API to prevent keys from being intercepted.

3. **Limit key permissions**: Only grant the scopes that are necessary for your application.

4. **Rotate keys regularly**: Generate new keys and revoke old ones periodically.

5. **Set expiration dates**: For temporary access, set an expiration date on the key.

## Troubleshooting

If you receive a `401 Unauthorized` response, check:
- That you're providing the API key correctly
- That the API key is valid and not expired
- That the API key has the required scope for the endpoint

If you receive a `403 Forbidden` response, check:
- That the API key has the required scope for the endpoint
- That the API key hasn't been revoked

## Disabling Authentication

For development or internal use, authentication can be disabled by setting `auth.enabled` to `false` in the configuration file.
