#!/bin/bash
set -e

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
max_tries=30
count=0
while ! php -r "try { new PDO('mysql:host=db;port=3306', 'root', 'secret'); echo 'ok'; } catch(Exception \$e) { exit(1); }" 2>/dev/null; do
    count=$((count + 1))
    if [ $count -ge $max_tries ]; then
        echo "MySQL not ready after $max_tries attempts, starting anyway..."
        break
    fi
    echo "MySQL not ready yet... attempt $count/$max_tries"
    sleep 2
done
echo "MySQL is ready!"

# Update .env for Docker environment if DB_HOST is not already set to 'db'
if grep -q "^DB_HOST=localhost" /var/www/.env 2>/dev/null; then
    echo "Updating .env for Docker environment..."
    sed -i 's/^DB_HOST=.*/DB_HOST=db/' /var/www/.env
    sed -i 's/^DB_PORT=.*/DB_PORT=3306/' /var/www/.env
    sed -i 's/^DB_DATABASE=.*/DB_DATABASE=ocr/' /var/www/.env
    sed -i 's/^DB_USERNAME=.*/DB_USERNAME=root/' /var/www/.env
    sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=secret/' /var/www/.env
fi

# Cache config
php artisan config:clear
php artisan config:cache

# Run migrations
php artisan migrate --force 2>/dev/null || true

# Start PHP-FPM
exec php-fpm
