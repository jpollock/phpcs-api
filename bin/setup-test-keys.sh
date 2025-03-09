#!/bin/bash

# PHPCS API - Setup Test Keys
# This script helps set up API keys for testing the PHPCS API.

# Set colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}PHPCS API - Setup Test Keys${NC}"
echo "This script will help you set up API keys for testing the PHPCS API."
echo

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo -e "${RED}Error: PHP is not installed or not in your PATH.${NC}"
    exit 1
fi

# Check if the manage-keys.php script exists
if [ ! -f "bin/manage-keys.php" ]; then
    echo -e "${RED}Error: bin/manage-keys.php not found.${NC}"
    echo "Make sure you're running this script from the root directory of the PHPCS API project."
    exit 1
fi

# Check if the data directory exists
if [ ! -d "data" ]; then
    echo -e "${YELLOW}Creating data directory...${NC}"
    mkdir -p data
    chmod 750 data
fi

# Check if the api_keys.json file exists
if [ ! -f "data/api_keys.json" ]; then
    echo -e "${YELLOW}Creating empty api_keys.json file...${NC}"
    echo "{}" > data/api_keys.json
    chmod 640 data/api_keys.json
fi

# Ensure authentication is enabled in config.php
AUTH_ENABLED=$(php -r "
    \$config = include 'config.php';
    echo isset(\$config['auth']['enabled']) && \$config['auth']['enabled'] ? 'true' : 'false';
")

if [ "$AUTH_ENABLED" == "false" ]; then
    echo -e "${YELLOW}Enabling authentication in config.php...${NC}"
    
    # Create a backup of the config file
    cp config.php config.php.bak
    
    # Enable authentication in config.php
    php -r "
        \$config = include 'config.php';
        \$config['auth']['enabled'] = true;
        file_put_contents('config.php', '<?php\n\nreturn ' . var_export(\$config, true) . ';');
    "
    
    echo -e "${GREEN}Authentication has been enabled in config.php.${NC}"
    echo "A backup of your original config has been saved as config.php.bak."
fi

# Generate API keys
echo
echo "Generating API keys for testing..."
echo

# Generate a key with all scopes
echo -e "${GREEN}Generating admin key with all scopes...${NC}"
ADMIN_KEY=$(php bin/manage-keys.php generate --name="Admin Test Key" --scopes=analyze,standards,admin)
echo "$ADMIN_KEY"
echo

# Generate a key with analyze scope only
echo -e "${GREEN}Generating key with analyze scope only...${NC}"
ANALYZE_KEY=$(php bin/manage-keys.php generate --name="Analyze Test Key" --scopes=analyze)
echo "$ANALYZE_KEY"
echo

# Generate a key with standards scope only
echo -e "${GREEN}Generating key with standards scope only...${NC}"
STANDARDS_KEY=$(php bin/manage-keys.php generate --name="Standards Test Key" --scopes=standards)
echo "$STANDARDS_KEY"
echo

# Extract the actual keys from the output
ADMIN_KEY_VALUE=$(echo "$ADMIN_KEY" | grep -A 1 "API key generated successfully:" | tail -n 1)
ANALYZE_KEY_VALUE=$(echo "$ANALYZE_KEY" | grep -A 1 "API key generated successfully:" | tail -n 1)
STANDARDS_KEY_VALUE=$(echo "$STANDARDS_KEY" | grep -A 1 "API key generated successfully:" | tail -n 1)

# Create a Postman environment file with the keys
echo -e "${GREEN}Creating Postman environment file with the generated keys...${NC}"
cat > postman/test-environment.json << EOL
{
  "id": "phpcs-api-test-env",
  "name": "PHPCS API Test",
  "values": [
    {
      "key": "base_url",
      "value": "http://localhost:8080",
      "type": "default",
      "enabled": true
    },
    {
      "key": "api_key",
      "value": "$ADMIN_KEY_VALUE",
      "type": "secret",
      "enabled": true
    },
    {
      "key": "admin_key",
      "value": "$ADMIN_KEY_VALUE",
      "type": "secret",
      "enabled": true
    },
    {
      "key": "analyze_key",
      "value": "$ANALYZE_KEY_VALUE",
      "type": "secret",
      "enabled": true
    },
    {
      "key": "standards_key",
      "value": "$STANDARDS_KEY_VALUE",
      "type": "secret",
      "enabled": true
    }
  ],
  "_postman_variable_scope": "environment",
  "_postman_exported_at": "$(date -u +"%Y-%m-%dT%H:%M:%S.000Z")",
  "_postman_exported_using": "PHPCS API Setup Script"
}
EOL

echo -e "${GREEN}API keys have been generated and saved to postman/test-environment.json.${NC}"
echo
echo "To use these keys in Postman:"
echo "1. Import postman/test-environment.json into Postman"
echo "2. Select the 'PHPCS API Test' environment from the dropdown"
echo "3. The 'api_key' variable is set to the admin key by default"
echo "4. You can also use the specific scope keys: admin_key, analyze_key, standards_key"
echo
echo -e "${GREEN}Setup complete!${NC}"
