# PHPCS API Deployment Scripts

This directory contains scripts for deploying and managing the PHPCS API application.

## fix-permissions.sh

This script fixes permissions for the cache directory in your local development environment.

### Usage

```bash
./fix-permissions.sh
```

### What it does

1. Creates the cache directory if it doesn't exist
2. Sets appropriate permissions (755) on the cache directory
3. Sets the correct owner based on the operating system:
   - On macOS: Sets the current user as owner
   - On Linux: Sets www-data as owner if running as root, otherwise sets the current user

Run this script if you encounter permission issues with the cache directory in your local development environment.

## deploy.sh

This script deploys the latest changes to the EC2 instance and fixes any permission issues.

### Usage

```bash
./deploy.sh <ssh-key-path> [ec2-host]
```

### Parameters

- `<ssh-key-path>`: Path to the SSH key file for connecting to the EC2 instance (required)
- `[ec2-host]`: Hostname or IP address of the EC2 instance (optional, defaults to ec2-35-84-65-45.us-west-2.compute.amazonaws.com)

### Example

```bash
./deploy.sh ~/.ssh/phpcs-api-key.pem ec2-35-84-65-45.us-west-2.compute.amazonaws.com
```

### What it does

1. Connects to the EC2 instance via SSH
2. Pulls the latest changes from the git repository
3. Updates dependencies with Composer
4. Fixes cache directory permissions
5. Restarts PHP-FPM and Nginx services

## Troubleshooting

If you encounter permission issues with the cache directory after deployment, you can manually fix them by running:

```bash
ssh -i <ssh-key-path> ubuntu@<ec2-host>
sudo mkdir -p /var/www/phpcs-api/cache
sudo chown -R www-data:www-data /var/www/phpcs-api/cache
sudo chmod -R 755 /var/www/phpcs-api/cache
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx
