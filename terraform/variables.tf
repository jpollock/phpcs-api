variable "aws_region" {
  description = "AWS region to deploy to"
  default     = "us-west-2"
  type        = string
}

variable "ami_id" {
  description = "AMI ID for the EC2 instance (Amazon Linux 2023 or Ubuntu 22.04 ARM)"
  type        = string
}

variable "key_name" {
  description = "Name of the SSH key pair"
  type        = string
}

variable "environment" {
  description = "Deployment environment (e.g., production, staging)"
  default     = "production"
  type        = string
}

variable "domain_name" {
  description = "Domain name for the PHPCS API"
  type        = string
}

variable "admin_ip" {
  description = "IP address allowed to SSH to the instance"
  type        = string
}

variable "backup_retention_days" {
  description = "Number of days to retain backups"
  default     = 30
  type        = number
}

variable "repo_url" {
  description = "URL of the PHPCS API repository"
  type        = string
  default     = "https://github.com/yourusername/phpcs-api.git"
}

variable "instance_type" {
  description = "EC2 instance type"
  type        = string
  default     = "t4g.small"
}

variable "root_volume_size" {
  description = "Size of the root volume in GB"
  type        = number
  default     = 20
}

variable "root_volume_type" {
  description = "Type of the root volume"
  type        = string
  default     = "gp3"
}

variable "php_version" {
  description = "PHP version to install"
  type        = string
  default     = "8.2"
}

variable "enable_https" {
  description = "Whether to enable HTTPS with Let's Encrypt"
  type        = bool
  default     = true
}

variable "admin_email" {
  description = "Email address for Let's Encrypt certificate notifications"
  type        = string
}
