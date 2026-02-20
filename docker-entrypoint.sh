#!/bin/bash
set -e

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-ocr}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-secret}"
export DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
max_tries=30
count=0
while ! php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); echo 'ok'; } catch(Exception \$e) { exit(1); }" 2>/dev/null; do
    count=$((count + 1))
    if [ $count -ge $max_tries ]; then
        echo "MySQL not ready after $max_tries attempts, starting anyway..."
        break
    fi
    echo "MySQL not ready yet... attempt $count/$max_tries"
    sleep 2
done
echo "MySQL is ready!"

# Cache config
php artisan config:clear
php artisan config:cache

# Run migrations
php artisan migrate --force 2>/dev/null || true

# Start PHP-FPM
exec php-fpm
