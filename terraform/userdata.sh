#!/bin/bash

# Exit on error
set -e

# Update system packages
apt update && apt upgrade -y

# Install required packages
apt install -y git nginx php${php_version}-fpm php${php_version}-cli php${php_version}-xml php${php_version}-mbstring php${php_version}-zip php${php_version}-curl php${php_version}-gd php${php_version}-intl php${php_version}-mysql awscli certbot python3-certbot-nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configure PHP-FPM
systemctl enable php${php_version}-fpm
systemctl start php${php_version}-fpm

# Install PHP_CodeSniffer globally
composer global require squizlabs/php_codesniffer

# Add to PATH
echo 'export PATH="$PATH:$HOME/.composer/vendor/bin"' >> /etc/profile.d/composer.sh
source /etc/profile.d/composer.sh

# Create application directory
mkdir -p /var/www/phpcs-api
chown ubuntu:ubuntu /var/www/phpcs-api

# Clone repository
git clone ${repo_url} /var/www/phpcs-api
cd /var/www/phpcs-api

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set permissions
mkdir -p /var/www/phpcs-api/data
mkdir -p /var/www/phpcs-api/logs
touch /var/www/phpcs-api/logs/access.log
touch /var/www/phpcs-api/logs/error.log
chown -R www-data:www-data /var/www/phpcs-api/data
chown -R www-data:www-data /var/www/phpcs-api/logs

# Configure NGINX
cat > /etc/nginx/sites-available/phpcs-api << 'EOF'
server {
    listen 80;
    server_name ${domain_name};
    root /var/www/phpcs-api/public;

    index index.php;

    access_log /var/www/phpcs-api/logs/access.log;
    error_log /var/www/phpcs-api/logs/error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php${php_version}-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

ln -s /etc/nginx/sites-available/phpcs-api /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Configure application
cp config.php.sample config.php
sed -i 's/auth.enabled = false/auth.enabled = true/' config.php

# Set up HTTPS with Let's Encrypt
if [ "${enable_https}" = "true" ]; then
    certbot --nginx -d ${domain_name} --non-interactive --agree-tos --email ${admin_email}
    systemctl enable certbot.timer
    systemctl start certbot.timer
fi

# Configure firewall
ufw allow 'Nginx Full'
ufw allow ssh
ufw --force enable

# Set up S3 backup script
cat > /home/ubuntu/backup-api-keys.sh << 'EOF'
#!/bin/bash
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
aws s3 cp /var/www/phpcs-api/data/api_keys.json s3://${s3_backup_bucket}/api-keys/api_keys-$TIMESTAMP.json
EOF

chmod +x /home/ubuntu/backup-api-keys.sh

# Add to crontab
(crontab -l 2>/dev/null || echo "") | { cat; echo "0 */6 * * * /home/ubuntu/backup-api-keys.sh"; } | crontab -

# Install CloudWatch agent
wget https://amazoncloudwatch-agent.s3.amazonaws.com/ubuntu/arm64/latest/amazon-cloudwatch-agent.deb
dpkg -i amazon-cloudwatch-agent.deb
rm amazon-cloudwatch-agent.deb

# Configure CloudWatch agent
cat > /opt/aws/amazon-cloudwatch-agent/etc/amazon-cloudwatch-agent.json << 'EOF'
{
  "metrics": {
    "metrics_collected": {
      "cpu": {
        "measurement": ["cpu_usage_idle", "cpu_usage_user", "cpu_usage_system"]
      },
      "mem": {
        "measurement": ["mem_used_percent"]
      },
      "disk": {
        "measurement": ["disk_used_percent"],
        "resources": ["/"]
      }
    },
    "append_dimensions": {
      "InstanceId": "$${aws:InstanceId}"
    }
  },
  "logs": {
    "logs_collected": {
      "files": {
        "collect_list": [
          {
            "file_path": "/var/www/phpcs-api/logs/access.log",
            "log_group_name": "${log_group}",
            "log_stream_name": "access-logs"
          },
          {
            "file_path": "/var/www/phpcs-api/logs/error.log",
            "log_group_name": "${log_group}",
            "log_stream_name": "error-logs"
          },
          {
            "file_path": "/var/log/nginx/error.log",
            "log_group_name": "${log_group}",
            "log_stream_name": "nginx-error-logs"
          }
        ]
      }
    }
  }
}
EOF

# Start CloudWatch agent
systemctl enable amazon-cloudwatch-agent
systemctl start amazon-cloudwatch-agent

# Create health check script
cat > /home/ubuntu/health-check.sh << 'EOF'
#!/bin/bash
HEALTH_ENDPOINT="https://${domain_name}/v1/health"
RESPONSE=$(curl -s -o /dev/null -w "%%{http_code}" $HEALTH_ENDPOINT)

if [ $RESPONSE -ne 200 ]; then
  aws cloudwatch put-metric-data --namespace "PHPCS-API" --metric-name "HealthCheckFailed" --value 1 --region ${aws_region}
else
  aws cloudwatch put-metric-data --namespace "PHPCS-API" --metric-name "HealthCheckFailed" --value 0 --region ${aws_region}
fi
EOF

chmod +x /home/ubuntu/health-check.sh

# Add to crontab
(crontab -l 2>/dev/null || echo "") | { cat; echo "*/5 * * * * /home/ubuntu/health-check.sh"; } | crontab -

# Optimize PHP
cat > /etc/php/${php_version}/fpm/conf.d/99-phpcs-api.ini << 'EOF'
; Optimized PHP settings for PHPCS API
memory_limit = 256M
max_execution_time = 60
post_max_size = 10M
upload_max_filesize = 10M
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
EOF

# Restart services
systemctl restart php${php_version}-fpm
systemctl restart nginx

echo "PHPCS API server setup completed!"
