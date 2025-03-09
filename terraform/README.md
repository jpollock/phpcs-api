# PHPCS API Terraform Deployment

This directory contains Terraform configuration files for deploying the PHPCS API to AWS on a single EC2 instance. The deployment includes all necessary infrastructure components such as security groups, IAM roles, S3 bucket for backups, and CloudWatch monitoring.

## Prerequisites

Before you can deploy the PHPCS API using this Terraform configuration, you need to have the following:

1. [Terraform](https://www.terraform.io/downloads.html) installed (version 1.0.0 or later)
2. [AWS CLI](https://aws.amazon.com/cli/) installed and configured with appropriate credentials
3. An SSH key pair for connecting to the EC2 instance
4. A domain name that you can point to the EC2 instance's IP address

## Configuration

1. Copy the sample Terraform variables file to create your own configuration:

```bash
cp terraform.tfvars.sample terraform.tfvars
```

2. Edit the `terraform.tfvars` file to customize the deployment:

```hcl
# AWS Region to deploy to
aws_region = "us-west-2"

# AMI ID for the EC2 instance (Ubuntu 22.04 ARM)
ami_id = "ami-0123456789abcdef0"

# Name of the SSH key pair to use for the EC2 instance
key_name = "your-ssh-key"

# Deployment environment (e.g., production, staging)
environment = "production"

# Domain name for the PHPCS API
domain_name = "api.example.com"

# IP address allowed to SSH to the instance
admin_ip = "203.0.113.1"

# Number of days to retain backups
backup_retention_days = 30

# URL of the PHPCS API repository
repo_url = "https://github.com/yourusername/phpcs-api.git"

# PHP version to install
php_version = "8.2"

# Whether to enable HTTPS with Let's Encrypt
enable_https = true

# Email address for Let's Encrypt certificate notifications
admin_email = "admin@example.com"
```

### Finding the Latest Ubuntu ARM AMI

To find the latest Ubuntu 22.04 ARM AMI for your region, you can use the following AWS CLI command:

```bash
aws ec2 describe-images --owners 099720109477 --filters "Name=name,Values=ubuntu/images/hvm-ssd/ubuntu-jammy-22.04-arm64-server-*" "Name=state,Values=available" --query "sort_by(Images, &CreationDate)[-1].ImageId" --output text
```

## Deployment

1. Initialize Terraform:

```bash
terraform init
```

2. Plan the deployment to see what resources will be created:

```bash
terraform plan
```

3. Apply the configuration to create the resources:

```bash
terraform apply
```

4. After the deployment is complete, Terraform will output the following information:

- `instance_id`: The ID of the EC2 instance
- `public_ip`: The public IP address of the EC2 instance
- `public_dns`: The public DNS name of the EC2 instance
- `s3_backup_bucket`: The name of the S3 bucket used for backups
- `cloudwatch_log_group`: The name of the CloudWatch log group
- `ssh_connection_string`: The SSH connection string for the EC2 instance
- `api_url`: The URL of the PHPCS API
- `health_check_url`: The URL of the health check endpoint

## DNS Configuration

After the deployment is complete, you need to configure your domain's DNS settings to point to the EC2 instance's IP address. Create an A record for your domain (e.g., `api.example.com`) that points to the `public_ip` output value.

## CI/CD Setup

The deployment includes a GitHub Actions workflow for continuous integration and deployment. To set it up:

1. Add the following secrets to your GitHub repository:

- `EC2_HOST`: The public IP address of the EC2 instance
- `EC2_USERNAME`: The username for SSH (ubuntu)
- `EC2_SSH_KEY`: The private SSH key for connecting to the instance

2. Push changes to the `main` branch to trigger the deployment.

## Monitoring

The deployment includes CloudWatch monitoring for the EC2 instance. You can access the CloudWatch dashboard at:

```
https://console.aws.amazon.com/cloudwatch/home?region=YOUR_REGION#dashboards:name=phpcs-api-ENVIRONMENT
```

Replace `YOUR_REGION` with your AWS region and `ENVIRONMENT` with the environment name you specified in the `terraform.tfvars` file.

## Backup and Recovery

API keys are automatically backed up to the S3 bucket every 6 hours. To restore from a backup:

1. List the available backups:

```bash
aws s3 ls s3://BUCKET_NAME/api-keys/
```

2. Download the backup you want to restore:

```bash
aws s3 cp s3://BUCKET_NAME/api-keys/api_keys-TIMESTAMP.json /tmp/api_keys.json
```

3. Copy the backup to the EC2 instance:

```bash
scp /tmp/api_keys.json ubuntu@EC2_IP:/tmp/
```

4. SSH into the EC2 instance:

```bash
ssh ubuntu@EC2_IP
```

5. Replace the current API keys file:

```bash
sudo cp /tmp/api_keys.json /var/www/phpcs-api/data/api_keys.json
sudo chown www-data:www-data /var/www/phpcs-api/data/api_keys.json
```

## Cleanup

To destroy all resources created by Terraform:

```bash
terraform destroy
```

**Note**: This will delete all resources, including the EC2 instance, S3 bucket, and all data. Make sure you have backups of any important data before running this command.
