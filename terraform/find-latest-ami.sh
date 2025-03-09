#!/bin/bash

# This script finds the latest Ubuntu 22.04 ARM AMI ID for a given AWS region.
# Usage: ./find-latest-ami.sh [region]
# Example: ./find-latest-ami.sh us-west-2

# Default to us-west-2 if no region is provided
REGION=${1:-us-west-2}

echo "Finding latest Ubuntu 22.04 ARM AMI in region: $REGION"

# Find the latest Ubuntu 22.04 ARM AMI
AMI_ID=$(aws ec2 describe-images \
  --owners 099720109477 \
  --filters "Name=name,Values=ubuntu/images/hvm-ssd/ubuntu-jammy-22.04-arm64-server-*" "Name=state,Values=available" \
  --query "sort_by(Images, &CreationDate)[-1].ImageId" \
  --output text \
  --region $REGION)

if [ -z "$AMI_ID" ]; then
  echo "Error: Could not find a matching AMI. Make sure you have the AWS CLI installed and configured correctly."
  exit 1
fi

echo "Latest Ubuntu 22.04 ARM AMI ID: $AMI_ID"
echo ""
echo "Add this to your terraform.tfvars file:"
echo "ami_id = \"$AMI_ID\""
