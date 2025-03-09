resource "aws_cloudwatch_log_group" "phpcs_api" {
  name              = "/phpcs-api/${var.environment}"
  retention_in_days = 30
  
  tags = {
    Environment = var.environment
    Application = "phpcs-api"
    Project     = "phpcs-api"
  }
}

resource "aws_cloudwatch_metric_alarm" "cpu_high" {
  alarm_name          = "phpcs-api-cpu-utilization-high-${var.environment}"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = "2"
  metric_name         = "CPUUtilization"
  namespace           = "AWS/EC2"
  period              = "300"
  statistic           = "Average"
  threshold           = "80"
  alarm_description   = "This metric monitors EC2 CPU utilization"
  
  dimensions = {
    InstanceId = aws_instance.phpcs_api.id
  }
  
  alarm_actions = [aws_sns_topic.phpcs_api_alerts.arn]
  ok_actions    = [aws_sns_topic.phpcs_api_alerts.arn]
  
  tags = {
    Environment = var.environment
    Project     = "phpcs-api"
  }
}

resource "aws_cloudwatch_metric_alarm" "status_check_failed" {
  alarm_name          = "phpcs-api-status-check-failed-${var.environment}"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = "2"
  metric_name         = "StatusCheckFailed"
  namespace           = "AWS/EC2"
  period              = "300"
  statistic           = "Maximum"
  threshold           = "0"
  alarm_description   = "This metric monitors EC2 status check failures"
  
  dimensions = {
    InstanceId = aws_instance.phpcs_api.id
  }
  
  alarm_actions = [aws_sns_topic.phpcs_api_alerts.arn]
  ok_actions    = [aws_sns_topic.phpcs_api_alerts.arn]
  
  tags = {
    Environment = var.environment
    Project     = "phpcs-api"
  }
}

resource "aws_cloudwatch_metric_alarm" "memory_high" {
  alarm_name          = "phpcs-api-memory-utilization-high-${var.environment}"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = "2"
  metric_name         = "mem_used_percent"
  namespace           = "CWAgent"
  period              = "300"
  statistic           = "Average"
  threshold           = "80"
  alarm_description   = "This metric monitors EC2 memory utilization"
  
  dimensions = {
    InstanceId = aws_instance.phpcs_api.id
  }
  
  alarm_actions = [aws_sns_topic.phpcs_api_alerts.arn]
  ok_actions    = [aws_sns_topic.phpcs_api_alerts.arn]
  
  tags = {
    Environment = var.environment
    Project     = "phpcs-api"
  }
}

resource "aws_cloudwatch_metric_alarm" "disk_high" {
  alarm_name          = "phpcs-api-disk-utilization-high-${var.environment}"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = "2"
  metric_name         = "disk_used_percent"
  namespace           = "CWAgent"
  period              = "300"
  statistic           = "Average"
  threshold           = "80"
  alarm_description   = "This metric monitors EC2 disk utilization"
  
  dimensions = {
    InstanceId = aws_instance.phpcs_api.id
    path       = "/"
    fstype     = "ext4"
  }
  
  alarm_actions = [aws_sns_topic.phpcs_api_alerts.arn]
  ok_actions    = [aws_sns_topic.phpcs_api_alerts.arn]
  
  tags = {
    Environment = var.environment
    Project     = "phpcs-api"
  }
}

resource "aws_cloudwatch_dashboard" "phpcs_api" {
  dashboard_name = "phpcs-api-${var.environment}"
  
  dashboard_body = jsonencode({
    widgets = [
      {
        type   = "metric"
        x      = 0
        y      = 0
        width  = 12
        height = 6
        properties = {
          metrics = [
            ["AWS/EC2", "CPUUtilization", "InstanceId", aws_instance.phpcs_api.id]
          ]
          period = 300
          stat   = "Average"
          region = var.aws_region
          title  = "CPU Utilization"
        }
      },
      {
        type   = "metric"
        x      = 12
        y      = 0
        width  = 12
        height = 6
        properties = {
          metrics = [
            ["CWAgent", "mem_used_percent", "InstanceId", aws_instance.phpcs_api.id]
          ]
          period = 300
          stat   = "Average"
          region = var.aws_region
          title  = "Memory Utilization"
        }
      },
      {
        type   = "metric"
        x      = 0
        y      = 6
        width  = 12
        height = 6
        properties = {
          metrics = [
            ["CWAgent", "disk_used_percent", "InstanceId", aws_instance.phpcs_api.id, "path", "/", "fstype", "ext4"]
          ]
          period = 300
          stat   = "Average"
          region = var.aws_region
          title  = "Disk Utilization"
        }
      },
      {
        type   = "metric"
        x      = 12
        y      = 6
        width  = 12
        height = 6
        properties = {
          metrics = [
            ["AWS/EC2", "StatusCheckFailed", "InstanceId", aws_instance.phpcs_api.id]
          ]
          period = 300
          stat   = "Maximum"
          region = var.aws_region
          title  = "Status Check Failed"
        }
      },
      {
        type   = "log"
        x      = 0
        y      = 12
        width  = 24
        height = 6
        properties = {
          query   = "SOURCE '${aws_cloudwatch_log_group.phpcs_api.name}' | fields @timestamp, @message | sort @timestamp desc | limit 100"
          region  = var.aws_region
          title   = "PHPCS API Logs"
          view    = "table"
        }
      }
    ]
  })
}

resource "aws_sns_topic" "phpcs_api_alerts" {
  name = "phpcs-api-alerts-${var.environment}"
  
  tags = {
    Environment = var.environment
    Project     = "phpcs-api"
  }
}

resource "aws_sns_topic_subscription" "phpcs_api_alerts_email" {
  topic_arn = aws_sns_topic.phpcs_api_alerts.arn
  protocol  = "email"
  endpoint  = var.admin_email
}
