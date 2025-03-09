# Deployment Guide

This guide explains how to deploy the PHPCS API to an EC2 instance and how to update it when changes are made.

## Initial Deployment

The PHPCS API is designed to be deployed to an AWS EC2 instance using Terraform. The Terraform configuration is available in the `terraform` directory.

### Prerequisites

- AWS CLI configured with appropriate credentials
- Terraform installed
- SSH key pair for connecting to the EC2 instance

### Deployment Steps

1. Navigate to the terraform directory:

```bash
cd terraform
```

2. Initialize Terraform:

```bash
terraform init
```

3. Apply the Terraform configuration:

```bash
terraform apply
```

4. Follow the prompts to confirm the deployment.

## Updating the Deployment

When you make changes to the codebase, you need to deploy those changes to the EC2 instance. The PHPCS API includes a deployment script that makes this process easy.

### Using the Deployment Script

1. Make sure your changes are committed and pushed to the git repository.

2. Run the deployment script:

```bash
./bin/deploy.sh <ssh-key-path> [ec2-host]
```

Replace `<ssh-key-path>` with the path to your SSH key file, and optionally provide the EC2 hostname or IP address if it's different from the default.

Example:

```bash
./bin/deploy.sh ~/.ssh/phpcs-api-key.pem ec2-35-84-65-45.us-west-2.compute.amazonaws.com
```

### What the Deployment Script Does

The deployment script:

1. Connects to the EC2 instance via SSH
2. Pulls the latest changes from the git repository
3. Updates dependencies with Composer
4. Fixes cache directory permissions
5. Restarts PHP-FPM and Nginx services

### Manual Deployment

If you prefer to deploy manually, you can SSH into the EC2 instance and run the following commands:

```bash
# Connect to the EC2 instance
ssh -i <ssh-key-path> ubuntu@<ec2-host>

# Navigate to the application directory
cd /var/www/phpcs-api

# Pull the latest changes
sudo git pull

# Update dependencies
sudo composer install --no-dev --optimize-autoloader

# Fix cache directory permissions
sudo mkdir -p /var/www/phpcs-api/cache
sudo chown -R www-data:www-data /var/www/phpcs-api/cache
sudo chmod -R 755 /var/www/phpcs-api/cache

# Restart services
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx
```

## Troubleshooting

### Permission Issues

If you encounter permission issues with the cache directory, you can fix them by running:

```bash
ssh -i <ssh-key-path> ubuntu@<ec2-host>
sudo mkdir -p /var/www/phpcs-api/cache
sudo chown -R www-data:www-data /var/www/phpcs-api/cache
sudo chmod -R 755 /var/www/phpcs-api/cache
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx
```

### Cache Configuration

Make sure your `config.php` file includes the cache configuration:

```php
'cache' => [
    'enabled' => true,
    'ttl' => 3600, // 1 hour
    'max_size' => 100 * 1024 * 1024, // 100MB
    'cleanup_probability' => 0.01, // 1% chance of cleanup on each request
],
```

### Checking Logs

If you encounter issues, check the logs:

```bash
ssh -i <ssh-key-path> ubuntu@<ec2-host>
tail -f /var/www/phpcs-api/logs/error.log
tail -f /var/www/phpcs-api/logs/access.log
tail -f /var/log/nginx/error.log
```

## Monitoring

The EC2 instance is configured to send logs and metrics to CloudWatch. You can view them in the AWS CloudWatch console.

### CloudWatch Logs

The following logs are available in CloudWatch:

- Access logs: `/var/www/phpcs-api/logs/access.log`
- Error logs: `/var/www/phpcs-api/logs/error.log`
- Nginx error logs: `/var/log/nginx/error.log`

### CloudWatch Metrics

The following metrics are available in CloudWatch:

- CPU usage
- Memory usage
- Disk usage
- Health check status

## Backup and Restore

The API keys are automatically backed up to an S3 bucket every 6 hours. To restore from a backup:

```bash
# List available backups
aws s3 ls s3://<s3-backup-bucket>/api-keys/

# Download a specific backup
aws s3 cp s3://<s3-backup-bucket>/api-keys/api_keys-<timestamp>.json /tmp/api_keys.json

# Copy the backup to the EC2 instance
scp -i <ssh-key-path> /tmp/api_keys.json ubuntu@<ec2-host>:/tmp/

# SSH into the EC2 instance
ssh -i <ssh-key-path> ubuntu@<ec2-host>

# Replace the current API keys file
sudo cp /tmp/api_keys.json /var/www/phpcs-api/data/api_keys.json
sudo chown www-data:www-data /var/www/phpcs-api/data/api_keys.json
sudo chmod 644 /var/www/phpcs-api/data/api_keys.json
