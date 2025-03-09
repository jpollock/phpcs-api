# PHPCS API Postman Collection

This directory contains a Postman collection for testing the PHPCS API. The collection includes requests for all API endpoints and tests to verify the API's functionality.

## Contents

- `phpcs-api-collection.json` - Postman collection file with requests and tests

## Setup

### Prerequisites

- [Postman](https://www.postman.com/downloads/) or [Newman](https://github.com/postmanlabs/newman) (CLI version of Postman)

### Importing the Collection

1. Open Postman
2. Click "Import" in the top left corner
3. Select "File" and choose the `phpcs-api-collection.json` file
4. The collection will be imported into Postman

### Setting Up Environment Variables

The collection uses environment variables for the base URL and API key. You can create a new environment in Postman with the following variables:

- `base_url` - The base URL of the API (default: `http://localhost:8080`)
- `api_key` - Your API key (if authentication is enabled)

To create a new environment:

1. Click the "Environments" tab in Postman
2. Click "Create Environment"
3. Name it (e.g., "PHPCS API Local")
4. Add the variables mentioned above
5. Save the environment
6. Select the environment from the dropdown in the top right corner

### Authentication

The API requires authentication for most endpoints. The Postman collection is pre-configured to use API keys for authentication.

The environment file already includes a valid API key for testing. If you need to generate a new key:

```bash
cd /path/to/phpcs-api
php bin/manage-keys.php generate --name="Test Key" --scopes=analyze,standards,admin
```

Then set the generated key as the `api_key` variable in your Postman environment.

For more detailed instructions, see the [Local Testing Guide](../docs/local-testing.md).

## Running Tests

### In Postman

1. Open the collection in Postman
2. Click the "..." (three dots) next to the collection name
3. Select "Run Collection"
4. Configure the run settings (you can run all requests or select specific ones)
5. Click "Run"

Postman will execute the requests and run the tests, showing you the results.

### Using Newman (CLI)

You can also run the collection using Newman, the command-line collection runner for Postman:

1. Install Newman:
   ```bash
   npm install -g newman
   ```

2. Run the collection:
   ```bash
   newman run phpcs-api-collection.json -e environment.json
   ```

   Where `environment.json` is a file containing your environment variables:
   ```json
   {
     "id": "your-env-id",
     "name": "PHPCS API Local",
     "values": [
       {
         "key": "base_url",
         "value": "http://localhost:8080",
         "enabled": true
       },
       {
         "key": "api_key",
         "value": "your-api-key",
         "enabled": true
       }
     ]
   }
   ```

## Tests Included

The collection includes tests for:

- Verifying successful responses
- Checking response headers (including API version)
- Validating response structure
- Testing error handling
- Verifying content of responses

Each request in the collection has its own set of tests tailored to that endpoint.

## Endpoints Covered

- `POST /v1/analyze` - Analyze PHP code
- `GET /v1/standards` - List available standards
- `GET /v1/health` - Health check
- `POST /v1/cache/clear` - Clear cache
- `GET /v1/cache/stats` - Get cache statistics
- `POST /v1/keys/generate` - Generate API key
- Error handling tests

## Continuous Integration

You can integrate these tests into your CI/CD pipeline using Newman. For example, in GitHub Actions:

```yaml
name: API Tests

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Install Newman
        run: npm install -g newman
      - name: Start API
        run: php -S localhost:8080 -t public &
      - name: Wait for API to start
        run: sleep 5
      - name: Run API tests
        run: newman run postman/phpcs-api-collection.json -e postman/environment.json
```

## Extending the Collection

You can extend this collection by:

1. Adding more requests for new endpoints
2. Adding more tests to existing requests
3. Creating additional environments for different deployment scenarios (e.g., staging, production)
