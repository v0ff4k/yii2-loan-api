#!/bin/bash

echo "setup.sh is started"

cd /app

mkdir -p vendor
composer install --optimize-autoloader --no-interaction --ignore-platform-reqs


echo "setup.sh completed"

# run infin fpm
php-fpm