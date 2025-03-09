provider "aws" {
  region = var.aws_region
}

resource "aws_instance" "phpcs_api" {
  ami                    = var.ami_id
  instance_type          = "t4g.small"
  key_name               = var.key_name
  vpc_security_group_ids = [aws_security_group.phpcs_api.id]
  iam_instance_profile   = aws_iam_instance_profile.phpcs_api.name
  
  root_block_device {
    volume_size = 20
    volume_type = "gp3"
  }
  
  user_data = templatefile("${path.module}/userdata.sh", {
    domain_name      = var.domain_name,
    environment      = var.environment,
    aws_region       = var.aws_region,
    repo_url         = var.repo_url,
    php_version      = var.php_version,
    enable_https     = var.enable_https,
    admin_email      = var.admin_email,
    s3_backup_bucket = aws_s3_bucket.phpcs_api_backups.bucket,
    log_group        = aws_cloudwatch_log_group.phpcs_api.name
  })
  
  tags = {
    Name        = "phpcs-api-server"
    Environment = var.environment
    Project     = "phpcs-api"
  }
}

resource "aws_eip" "phpcs_api" {
  instance = aws_instance.phpcs_api.id
  domain   = "vpc"
  
  tags = {
    Name        = "phpcs-api-eip"
    Environment = var.environment
    Project     = "phpcs-api"
  }
}
