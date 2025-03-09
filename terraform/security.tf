resource "aws_security_group" "phpcs_api" {
  name        = "phpcs-api-sg-${var.environment}"
  description = "Security group for PHPCS API server"
  
  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
    description = "HTTP"
  }
  
  ingress {
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
    description = "HTTPS"
  }
  
  ingress {
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = ["${var.admin_ip}/32"]
    description = "SSH from admin IP"
  }
  
  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
    description = "Allow all outbound traffic"
  }
  
  tags = {
    Name        = "phpcs-api-sg"
    Environment = var.environment
    Project     = "phpcs-api"
  }
}

resource "aws_iam_role" "phpcs_api" {
  name = "phpcs-api-role-${var.environment}"
  
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Action = "sts:AssumeRole"
        Effect = "Allow"
        Principal = {
          Service = "ec2.amazonaws.com"
        }
      }
    ]
  })
  
  tags = {
    Environment = var.environment
    Project     = "phpcs-api"
  }
}

resource "aws_iam_policy" "phpcs_api_s3" {
  name        = "phpcs-api-s3-policy-${var.environment}"
  description = "Policy for PHPCS API server to access S3"
  
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Action = [
          "s3:PutObject",
          "s3:GetObject",
          "s3:ListBucket",
          "s3:DeleteObject"
        ]
        Effect   = "Allow"
        Resource = [
          "${aws_s3_bucket.phpcs_api_backups.arn}",
          "${aws_s3_bucket.phpcs_api_backups.arn}/*"
        ]
      }
    ]
  })
}

resource "aws_iam_policy" "phpcs_api_cloudwatch" {
  name        = "phpcs-api-cloudwatch-policy-${var.environment}"
  description = "Policy for PHPCS API server to access CloudWatch"
  
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Action = [
          "logs:CreateLogGroup",
          "logs:CreateLogStream",
          "logs:PutLogEvents",
          "logs:DescribeLogStreams"
        ]
        Effect   = "Allow"
        Resource = "arn:aws:logs:*:*:*"
      },
      {
        Action = [
          "cloudwatch:PutMetricData"
        ]
        Effect   = "Allow"
        Resource = "*"
      }
    ]
  })
}

resource "aws_iam_role_policy_attachment" "phpcs_api_s3" {
  role       = aws_iam_role.phpcs_api.name
  policy_arn = aws_iam_policy.phpcs_api_s3.arn
}

resource "aws_iam_role_policy_attachment" "phpcs_api_cloudwatch" {
  role       = aws_iam_role.phpcs_api.name
  policy_arn = aws_iam_policy.phpcs_api_cloudwatch.arn
}

resource "aws_iam_role_policy_attachment" "phpcs_api_ssm" {
  role       = aws_iam_role.phpcs_api.name
  policy_arn = "arn:aws:iam::aws:policy/AmazonSSMManagedInstanceCore"
}

resource "aws_iam_instance_profile" "phpcs_api" {
  name = "phpcs-api-profile-${var.environment}"
  role = aws_iam_role.phpcs_api.name
}
