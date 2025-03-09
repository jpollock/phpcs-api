# Manual Tests for PHPCS API

This directory contains manual test scripts for the PHPCS API. These scripts are designed to help you test specific features of the API manually.

## PHP Version Compatibility Testing

The `test-php-version.php` script demonstrates how to use the PHP version compatibility testing feature of the PHPCS API. This feature allows you to check if PHP code is compatible with specific PHP versions.

### Prerequisites

- PHP 7.0 or higher with cURL extension enabled
- A running instance of the PHPCS API
- An API key with the 'analyze' scope
- The PHPCompatibility standard installed in the PHPCS API

### Installation of PHPCompatibility Standard

If the PHPCompatibility standard is not already installed, you can install it using Composer:

```bash
cd /path/to/phpcs-api
composer require phpcompatibility/php-compatibility
```

### Usage

1. Edit the `test-php-version.php` file and update the following variables:
   - `$apiUrl`: The URL of your PHPCS API instance
   - `$apiKey`: Your API key with the 'analyze' scope

2. Run the script:
   ```bash
   php test-php-version.php
   ```

3. The script will run several test cases, each testing a different PHP feature against different PHP versions. For each test case, it will:
   - Send a request to the PHPCS API
   - Check if the response contains the expected number of issues
   - Display the results

### Test Cases

The script includes the following test cases:

1. **PHP 7.0 Type Declarations**: Tests scalar type declarations against PHP 5.6 (should find issues)
2. **PHP 7.0 Type Declarations (Compatible)**: Tests scalar type declarations against PHP 7.0 (should be compatible)
3. **PHP 7.4 Arrow Functions**: Tests arrow functions against PHP 7.3 (should find issues)
4. **PHP 7.4 Arrow Functions (Compatible)**: Tests arrow functions against PHP 7.4 (should be compatible)
5. **PHP 8.0 Named Arguments**: Tests named arguments against PHP 7.4 (should find issues)
6. **PHP 8.0 Named Arguments (Compatible)**: Tests named arguments against PHP 8.0 (should be compatible)

### Expected Output

For each test case, the script will output:
- The test case name
- The PHP version being tested against
- The code being tested
- Whether the test passed or failed
- The number of issues found vs. expected
- Details of any issues found

A successful test will show "PASSED" for each test case, indicating that the PHP version compatibility testing feature is working correctly.

### Troubleshooting

If you encounter issues:

1. **API Connection Issues**: Make sure the PHPCS API is running and accessible at the URL specified in `$apiUrl`.
2. **Authentication Issues**: Verify that the API key is valid and has the 'analyze' scope.
3. **Missing PHPCompatibility Standard**: Ensure the PHPCompatibility standard is installed and available in the PHPCS API.
4. **Unexpected Results**: The PHPCompatibility standard may have been updated with new rules. Check the latest version of the standard to see if the expected issues have changed.
