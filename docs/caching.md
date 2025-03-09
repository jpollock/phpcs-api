# Caching System

The PHPCS API includes a caching system to improve performance by storing the results of PHPCS analysis. This document explains how the caching system works and how to configure it.

## How Caching Works

When a request is made to analyze PHP code, the API first checks if the result is already in the cache. If it is, the cached result is returned immediately, which significantly improves response time. If the result is not in the cache, the API performs the analysis and stores the result in the cache for future requests.

The cache key is generated based on:
- The PHP code to analyze
- The PHPCS standard to use
- The PHP version to test against (for PHPCompatibility)
- Any additional PHPCS options

This ensures that the cache is only used when all parameters match exactly.

## Cache Configuration

The caching system is configured in the `config.php` file. Here's an example configuration:

```php
'cache' => [
    'enabled' => true,
    'ttl' => 3600, // 1 hour
    'max_size' => 100 * 1024 * 1024, // 100MB
    'cleanup_probability' => 0.01, // 1% chance of cleanup on each request
],
```

### Configuration Options

- `enabled`: Whether caching is enabled (true/false)
- `ttl`: Time-to-live in seconds (how long cache entries are valid)
- `max_size`: Maximum size of the cache in bytes
- `cleanup_probability`: Probability of running cache cleanup on each request (0-1)

## Cache Directory

The cache is stored in the `cache` directory in the root of the project. Each cache entry is stored as a JSON file with a filename based on the cache key.

## Permissions

The cache directory must be writable by the web server user (e.g., www-data on Ubuntu). If you encounter permission issues, you can fix them using the provided scripts:

### Local Development

```bash
./bin/fix-permissions.sh
```

### Production (EC2)

```bash
./bin/deploy.sh <ssh-key-path> [ec2-host]
```

Or manually:

```bash
ssh -i <ssh-key-path> ubuntu@<ec2-host>
sudo mkdir -p /var/www/phpcs-api/cache
sudo chown -R www-data:www-data /var/www/phpcs-api/cache
sudo chmod -R 755 /var/www/phpcs-api/cache
```

## Cache Management

### Clearing the Cache

The API includes an endpoint to clear the cache:

```bash
curl -X POST http://localhost:8080/v1/cache/clear \
  -H "Authorization: Bearer YOUR_API_KEY"
```

Note: This endpoint requires an API key with the `admin` scope.

### Cache Statistics

You can view cache statistics using the following endpoint:

```bash
curl http://localhost:8080/v1/cache/stats \
  -H "Authorization: Bearer YOUR_API_KEY"
```

Example response:

```json
{
  "success": true,
  "stats": {
    "enabled": true,
    "count": 42,
    "size": 1234567,
    "oldest": "2023-01-01 12:00:00",
    "newest": "2023-01-02 12:00:00"
  }
}
```

## Troubleshooting

### Permission Issues

If you see an error like this:

```
Warning: file_put_contents(/var/www/phpcs-api/cache/...): Failed to open stream: Permission denied
```

It means the web server user doesn't have write permissions to the cache directory. Use the scripts mentioned above to fix the permissions.

### Cache Not Working

If caching doesn't seem to be working, check the following:

1. Make sure caching is enabled in the `config.php` file.
2. Check if the cache directory exists and has the correct permissions.
3. Verify that the cache TTL is not set too low.
4. Check if the cache is being cleared by another process.

### Cache Size Issues

If the cache grows too large, you can:

1. Decrease the `max_size` setting in the `config.php` file.
2. Increase the `cleanup_probability` setting to clean up the cache more frequently.
3. Manually clear the cache using the API endpoint or by deleting the files in the cache directory.

## Performance Considerations

- The cache is stored on disk, so it's persistent across server restarts.
- The cache key is a SHA-256 hash of the request parameters, which ensures uniqueness and security.
- The cache TTL prevents stale results from being returned.
- The cache cleanup process removes expired entries to prevent the cache from growing too large.

## Implementation Details

The caching system is implemented in the `CacheService.php` file. It provides the following methods:

- `generateKey`: Generates a cache key based on the request parameters
- `get`: Gets a cached result for a given key
- `set`: Sets a cached result for a given key
- `clear`: Clears all cached results
- `getStats`: Gets cache statistics

The cache is used in the `PhpcsService.php` file, which handles PHPCS analysis requests.
