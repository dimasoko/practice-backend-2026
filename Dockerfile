FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    curl zip unzip git libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY src/ .

RUN composer install --no-dev --optimize-autoloader

RUN cp .env.example .env && php artisan key:generate

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]