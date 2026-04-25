FROM php:8.4-cli-bookworm AS builder

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    pkg-config \
    libsqlite3-dev \
    && pecl install zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite zip gd intl \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . .

RUN php artisan package:discover --ansi

RUN npm ci && npm run build

FROM dunglas/frankenphp:php8.4-bookworm

ENV APP_ENV=production

WORKDIR /var/www/html

COPY --from=builder /var/www/html/vendor ./vendor
COPY --from=builder /var/www/html/public/build ./public/build

COPY . .

RUN cp .env.example .env && \
    php artisan key:generate --force && \
    php artisan config:cache --force && \
    php artisan route:cache --force && \
    php artisan view:cache --force

EXPOSE 8080
ENTRYPOINT ["frankenphp", "entrance"]