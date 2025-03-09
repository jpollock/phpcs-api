# Setting Up Authentication for Local Testing

This guide explains how to set up authentication for local testing of the PHPCS API.

## Generating API Keys for Testing

The PHPCS API uses API keys for authentication. You can generate API keys using the provided command-line tool:

1. Generate an API key with the necessary scopes:

```bash
cd /path/to/phpcs-api
php bin/manage-keys.php generate --name="Test Key" --scopes=analyze,standards,admin
```

This will output a new API key, for example:

```
API key generated successfully:
abc123def456ghi789jkl012mno345pqr678stu901

Key details:
Name: Test Key
Scopes: analyze, standards, admin
Expires: Never

Store this key securely. It will not be shown again.
```

3. Use this API key in your requests:

### Using cURL

```bash
curl -X POST http://localhost:8080/v1/analyze \
  -H "Authorization: Bearer abc123def456ghi789jkl012mno345pqr678stu901" \
  -H "Content-Type: application/json" \
  -d '{"code":"<?php echo \"Hello World\"; ?>\n","standard":"PSR12"}'
```

### Using Postman

1. Open the Postman collection
2. Go to the "Variables" tab in the collection settings
3. Set the `api_key` variable to your generated key
4. Enable the Authorization header in each request by unchecking the "disabled" checkbox

## Troubleshooting Authentication Issues

If you're still experiencing authentication issues, try the following:

### Check the API Keys File

The API keys are stored in `data/api_keys.json`. Make sure this file exists and contains valid keys:

```bash
cat data/api_keys.json
```

If the file is empty or corrupted, you can reset it:

```bash
echo '{"keys":{}}' > data/api_keys.json
```

### Check Permissions

Make sure the data directory and api_keys.json file have the correct permissions:

```bash
chmod 750 data
chmod 640 data/api_keys.json
```

### Debug Authentication

You can add debug logging to see what's happening during authentication:

1. Set the log level to debug in `config.php`:

```php
'logging' => [
    'enabled' => true,
    'log_level' => 'debug',
    // ...
],
```

2. Check the logs after making a request:

```bash
tail -f logs/access.log
tail -f logs/error.log
```

### Test with the /v1/health Endpoint

The `/v1/health` endpoint should be accessible without authentication. Try accessing it to verify that the API is running correctly:

```bash
curl http://localhost:8080/v1/health
```

If this endpoint works but others don't, it confirms that the issue is related to authentication.

## Using API Keys in the Postman Collection

The Postman collection is already set up to use API keys. To configure it:

1. Open the Postman collection
2. Go to the "Variables" tab in the collection settings
3. Set the `api_key` variable to your generated key
4. For each request that requires authentication, make sure the Authorization header is enabled (unchecked "disabled" checkbox)

Alternatively, you can set the API key in the environment:

1. Open the environment file in Postman
2. Set the `api_key` variable to your generated key
3. Save the environment

This will apply the API key to all requests in the collection that use the environment variable.
