FROM dunglas/frankenphp:php8.4-bookworm AS builder

ARG APP_ENV=production

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources/ ./resources/

RUN npm run build

FROM dunglas/frankenphp:php8.4-bookworm

ARG APP_ENV=production
ENV APP_ENV=${APP_ENV}

WORKDIR /var/www/html

COPY --from=builder /var/www/html/vendor ./vendor
COPY --from=builder /var/www/html/public/build ./public/build
COPY --from=builder /var/www/html/node_modules/.vite ./node_modules/.vite

COPY . .

RUN cp .env.example .env && \
    php artisan key:generate --force && \
    php artisan config:cache --force && \
    php artisan route:cache --force && \
    php artisan view:cache --force

EXPOSE 8080
ENTRYPOINT ["frankenphp", "entrance"]