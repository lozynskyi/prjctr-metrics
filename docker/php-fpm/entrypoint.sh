#!/bin/sh

set -e
echo "composer config.............."

mkdir -p /root/.composer
chmod 777 /root/.composer

echo "install --no-interaction........."
composer install --no-interaction --optimize-autoloader -vvv

php-fpm