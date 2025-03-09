#!/bin/bash

# Exit on error
set -e

# Define the application directory
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CACHE_DIR="$APP_DIR/cache"

echo "Fixing permissions for PHPCS API cache directory..."

# Create cache directory if it doesn't exist
if [ ! -d "$CACHE_DIR" ]; then
  echo "Creating cache directory: $CACHE_DIR"
  mkdir -p "$CACHE_DIR"
fi

# Set appropriate permissions
echo "Setting permissions on: $CACHE_DIR"
chmod -R 755 "$CACHE_DIR"

# If running on macOS, set current user as owner
if [[ "$OSTYPE" == "darwin"* ]]; then
  echo "Running on macOS, setting current user as owner"
  chown -R "$(whoami)" "$CACHE_DIR"
else
  # On Linux, try to set www-data as owner if running as root
  if [ "$(id -u)" -eq 0 ]; then
    echo "Running as root, setting www-data as owner"
    chown -R www-data:www-data "$CACHE_DIR"
  else
    echo "Not running as root, setting current user as owner"
    chown -R "$(whoami)" "$CACHE_DIR"
  fi
fi

echo "Permissions fixed successfully!"
echo "Cache directory: $CACHE_DIR"
