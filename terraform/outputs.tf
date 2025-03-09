output "instance_id" {
  description = "ID of the EC2 instance"
  value       = aws_instance.phpcs_api.id
}

output "public_ip" {
  description = "Public IP address of the EC2 instance"
  value       = aws_eip.phpcs_api.public_ip
}

output "public_dns" {
  description = "Public DNS name of the EC2 instance"
  value       = aws_instance.phpcs_api.public_dns
}

output "s3_backup_bucket" {
  description = "Name of the S3 bucket used for backups"
  value       = aws_s3_bucket.phpcs_api_backups.bucket
}

output "cloudwatch_log_group" {
  description = "Name of the CloudWatch log group"
  value       = aws_cloudwatch_log_group.phpcs_api.name
}

output "ssh_connection_string" {
  description = "SSH connection string for the EC2 instance"
  value       = "ssh ubuntu@${aws_eip.phpcs_api.public_ip}"
}

output "api_url" {
  description = "URL of the PHPCS API"
  value       = "https://${var.domain_name}"
}

output "health_check_url" {
  description = "URL of the health check endpoint"
  value       = "https://${var.domain_name}/v1/health"
}
