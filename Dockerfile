FROM php:8.5-fpm-bookworm AS app
RUN apt-get update && apt-get install -y --no-install-recommends git unzip libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev libicu-dev libzip-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd intl zip mbstring pcntl \
    && rm -rf /var/lib/apt/lists/*
RUN pecl install pcov-1.0.12 && docker-php-ext-enable pcov \
    && echo "pcov.enabled=0" > /usr/local/etc/php/conf.d/pcov-default.ini
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --optimize --no-scripts \
    && php artisan package:discover --ansi \
    && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs .phpunit.cache vendor/pestphp/pest-plugin-mutate/.temp vendor/pestphp/pest/.temp \
    && touch .env \
    && php artisan storage:link \
    && chown -R www-data:www-data storage bootstrap/cache .phpunit.cache vendor/pestphp/pest-plugin-mutate/.temp vendor/pestphp/pest/.temp
COPY docker/php.ini /usr/local/etc/php/conf.d/rightmo.ini
USER www-data
CMD ["php-fpm"]

FROM nginx:stable-alpine AS nginx
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/html/public
