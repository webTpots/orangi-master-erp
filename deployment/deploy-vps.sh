#!/bin/bash
# Orangi Master ERP - VPS Deployment Script
# Run this on VPS: bash deploy-vps.sh

set -e

APP_DIR="/var/www/orangi-erp"
DOMAIN="erp.tpots.co"
REPO="https://github.com/webTpots/orangi-master-erp.git"

echo "=== Orangi Master ERP Deployment ==="

# 1. Install dependencies if missing
echo "[1/8] Checking system dependencies..."
apt-get update -qq
apt-get install -y -qq php8.3 php8.3-fpm php8.3-mbstring php8.3-xml php8.3-curl php8.3-sqlite3 php8.3-zip php8.3-bcmath php8.3-gd unzip git nginx 2>/dev/null || {
    echo "PHP 8.3 not available, trying 8.2..."
    apt-get install -y -qq php8.2 php8.2-fpm php8.2-mbstring php8.2-xml php8.2-curl php8.2-sqlite3 php8.2-zip php8.2-bcmath php8.2-gd unzip git nginx 2>/dev/null || {
        echo "PHP 8.2 not available, trying 8.1..."
        apt-get install -y -qq php8.1 php8.1-fpm php8.1-mbstring php8.1-xml php8.1-curl php8.1-sqlite3 php8.1-zip php8.1-bcmath php8.1-gd unzip git nginx
    }
}

# Detect PHP version
PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "   PHP version: $PHP_VER"

# Install composer if missing
if ! command -v composer &>/dev/null; then
    echo "   Installing Composer..."
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# 2. Clone or pull repo
echo "[2/8] Setting up application..."
if [ -d "$APP_DIR" ]; then
    cd "$APP_DIR"
    git pull origin main
else
    git clone "$REPO" "$APP_DIR"
    cd "$APP_DIR"
fi

# 3. Install PHP dependencies
echo "[3/8] Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Setup environment
echo "[4/8] Configuring environment..."
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate

    # Update .env for production
    sed -i 's/APP_NAME=Laravel/APP_NAME="Orangi ERP"/' .env
    sed -i 's/APP_ENV=local/APP_ENV=production/' .env
    sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
    sed -i "s|APP_URL=http://localhost:8000|APP_URL=https://$DOMAIN|" .env
    sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=sqlite/' .env

    echo "   .env created and configured"
else
    echo "   .env already exists, skipping"
fi

# 5. Setup database and storage
echo "[5/8] Setting up database and storage..."
touch database/database.sqlite
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link 2>/dev/null || true

# 6. Optimize for production
echo "[6/8] Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Set permissions
echo "[7/8] Setting permissions..."
chown -R www-data:www-data "$APP_DIR"
chmod -R 755 "$APP_DIR"
chmod -R 775 storage bootstrap/cache database

# 8. Configure Nginx
echo "[8/8] Configuring Nginx..."
PHP_FPM_SOCK="/run/php/php${PHP_VER}-fpm.sock"

cat > /etc/nginx/sites-available/orangi-erp << NGINX
server {
    listen 80;
    server_name $DOMAIN;
    root $APP_DIR/public;

    index index.php index.html;
    charset utf-8;

    client_max_body_size 20M;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:$PHP_FPM_SOCK;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

# Enable site
ln -sf /etc/nginx/sites-available/orangi-erp /etc/nginx/sites-enabled/
# Remove old tpots-erp config if it exists for same domain
if grep -q "$DOMAIN" /etc/nginx/sites-available/tpots-erp 2>/dev/null; then
    rm -f /etc/nginx/sites-enabled/tpots-erp
    echo "   Disabled old tpots-erp config"
fi

# Test and reload
nginx -t && systemctl reload nginx
systemctl restart php${PHP_VER}-fpm

echo ""
echo "=== Deployment Complete! ==="
echo "URL: http://$DOMAIN"
echo "Login: sunny@tpots.co / roy110394"
echo ""
echo "To enable HTTPS, run:"
echo "  apt install certbot python3-certbot-nginx"
echo "  certbot --nginx -d $DOMAIN"
echo ""
