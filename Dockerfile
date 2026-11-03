# -------------------------------------------------------------
# Stage 1: Build Frontend Assets (Vite + Vue 3 + Tailwind)
# -------------------------------------------------------------
FROM node:20-alpine AS frontend
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources/ ./resources/
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY public/ ./public/

RUN npm run build

# -------------------------------------------------------------
# Stage 2: Install Composer Dependencies
# -------------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# -------------------------------------------------------------
# Stage 3: FrankenPHP Runtime
# -------------------------------------------------------------
FROM dunglas/frankenphp:1-php8.2

# Install necessary PHP extensions for Laravel & PDF export
RUN install-php-extensions \
    pdo_sqlite \
    pdo_mysql \
    pdo_pgsql \
    bcmath \
    intl \
    zip \
    gd \
    opcache

# Use production php.ini
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Copy composer binary from vendor stage
COPY --from=vendor /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy application files
COPY . /app

# Copy built vendor and frontend assets
COPY --from=vendor /app/vendor /app/vendor
COPY --from=frontend /app/public/build /app/public/build

# Copy Caddyfile and entrypoint script
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Run composer dump-autoload for production optimization
RUN composer dump-autoload --optimize --no-dev

# Setup directory permissions
RUN mkdir -p /app/storage/framework/cache/data \
             /app/storage/framework/sessions \
             /app/storage/framework/views \
             /app/storage/logs \
             /app/bootstrap/cache \
             /app/database \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/database \
    && chmod -R 775 /app/storage /app/bootstrap/cache /app/database

# Remove capability restriction for restricted container runners
RUN setcap -r /usr/local/bin/frankenphp || true

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
