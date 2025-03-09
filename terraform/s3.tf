resource "aws_s3_bucket" "phpcs_api_backups" {
  bucket = "phpcs-api-backups-${var.environment}-${random_id.bucket_suffix.hex}"
  
  tags = {
    Name        = "PHPCS API Backups"
    Environment = var.environment
    Project     = "phpcs-api"
  }
}

resource "random_id" "bucket_suffix" {
  byte_length = 4
}

resource "aws_s3_bucket_lifecycle_configuration" "phpcs_api_backups" {
  bucket = aws_s3_bucket.phpcs_api_backups.id
  
  rule {
    id     = "api-keys-backup-lifecycle"
    status = "Enabled"
    
    prefix = "api-keys/"
    
    expiration {
      days = var.backup_retention_days
    }
  }
}

resource "aws_s3_bucket_versioning" "phpcs_api_backups" {
  bucket = aws_s3_bucket.phpcs_api_backups.id
  
  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "phpcs_api_backups" {
  bucket = aws_s3_bucket.phpcs_api_backups.id

  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}

resource "aws_s3_bucket_public_access_block" "phpcs_api_backups" {
  bucket = aws_s3_bucket.phpcs_api_backups.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}
