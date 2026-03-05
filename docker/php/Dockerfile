FROM php:8.2-fpm-bullseye

# Системные зависимости
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install -j$(nproc) pdo pdo_pgsql zip

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Копируем только composer.json (lock не обязателен)
COPY composer.json ./
COPY phpunit.xml.dist ./

# Установка зависимостей (создаст composer.lock внутри образа)
# ❌ УБИРАЕМ composer install из Dockerfile! vendor будет монтироваться с хоста
RUN composer install --optimize-autoloader --no-interaction --no-scripts || true

# Копируем исходный код
COPY src/ ./src/

# Копируем тесты (чтобы можно было запускать в контейнере)
COPY tests/ ./tests/

# Создаем директории и выставляем права
RUN mkdir -p /app/src/runtime /app/src/web/assets \
    && chown -R www-data:www-data /app/src/runtime /app/src/web/assets \
    && chmod -R 775 /app/src/runtime /app/src/web/assets \
    && chown -R www-data:www-data /app/tests \
    && chmod -R 775 /app/tests

# PHP настройки
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

EXPOSE 9000
# CMD ["php-fpm"] # wil run from setup.sh