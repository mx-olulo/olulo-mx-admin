#!/bin/bash
# Nginx 설정 배포 스크립트

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
NGINX_CONF_DIR="$PROJECT_ROOT/deployment/nginx"

# 환경 확인
ENV=${1:-staging}

if [ "$ENV" != "staging" ] && [ "$ENV" != "production" ]; then
    echo "Usage: $0 [staging|production]"
    exit 1
fi

# 설정 파일 경로
if [ "$ENV" = "staging" ]; then
    CONF_FILE="admin.stage.olulo.com.mx.conf"
    SITE_NAME="admin.stage.olulo.com.mx"
else
    CONF_FILE="admin.olulo.com.mx.conf"
    SITE_NAME="admin.olulo.com.mx"
fi

echo "🚀 Deploying nginx configuration for $ENV environment..."
echo ""

# 설정 파일 존재 확인
if [ ! -f "$NGINX_CONF_DIR/$CONF_FILE" ]; then
    echo "❌ Configuration file not found: $NGINX_CONF_DIR/$CONF_FILE"
    exit 1
fi

# 설정 파일 복사
echo "📋 Copying configuration file..."
sudo cp "$NGINX_CONF_DIR/$CONF_FILE" "/etc/nginx/sites-available/$SITE_NAME"

# 심볼릭 링크 생성 (이미 존재하면 스킵)
if [ ! -L "/etc/nginx/sites-enabled/$SITE_NAME" ]; then
    echo "🔗 Creating symbolic link..."
    sudo ln -sf "/etc/nginx/sites-available/$SITE_NAME" /etc/nginx/sites-enabled/
fi

# 설정 검증
echo "✅ Validating nginx configuration..."
sudo nginx -t

# nginx 재시작
echo "🔄 Reloading nginx..."
sudo systemctl reload nginx

echo ""
echo "✨ Nginx configuration deployed successfully!"
