#!/bin/bash

# Exit on error
set -e

# Check if SSH key is provided
if [ -z "$1" ]; then
  echo "Usage: $0 <ssh-key-path> [ec2-host]"
  echo "Example: $0 ~/.ssh/phpcs-api-key.pem ec2-35-84-65-45.us-west-2.compute.amazonaws.com"
  exit 1
fi

SSH_KEY="$1"
EC2_HOST="${2:-ec2-35-84-65-45.us-west-2.compute.amazonaws.com}"
APP_DIR="/var/www/phpcs-api"

echo "Deploying to $EC2_HOST..."

# SSH into the EC2 instance and run commands
ssh -i "$SSH_KEY" ubuntu@"$EC2_HOST" << EOF
  echo "Connected to $EC2_HOST"
  
  # Navigate to the application directory
  cd $APP_DIR
  
  # Pull the latest changes
  echo "Pulling latest changes..."
  sudo git pull
  
  # Install/update dependencies
  echo "Updating dependencies..."
  sudo composer install --no-dev --optimize-autoloader
  
  # Fix cache directory permissions
  echo "Fixing cache directory permissions..."
  sudo mkdir -p $APP_DIR/cache
  sudo chown -R www-data:www-data $APP_DIR/cache
  sudo chmod -R 755 $APP_DIR/cache
  
  # Restart PHP-FPM and Nginx
  echo "Restarting services..."
  sudo systemctl restart php8.1-fpm
  sudo systemctl restart nginx
  
  echo "Deployment completed successfully!"
EOF

echo "Deployment script finished."
