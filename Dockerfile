# ==============================================================================
# Stage 1: Frontend Asset Compilation (Node.js 22)
# ==============================================================================
FROM node:22-alpine AS frontend

WORKDIR /app

# Copy package manifests for dependency layer caching
COPY package.json package-lock.json ./

# Install frontend dependencies cleanly
RUN npm ci

# Copy frontend source files and Vite configuration
COPY resources/ ./resources/
COPY vite.config.js ./

# Compile production Vite assets (output: public/build)
RUN npm run build

# ==============================================================================
# Stage 2: Production Runtime (PHP 8.4-FPM + Nginx)
# ==============================================================================
FROM php:8.4-fpm-bookworm

# Set container working directory
WORKDIR /var/www/html

# Set non-interactive debian frontend and build environment
ENV DEBIAN_FRONTEND=noninteractive \
    APP_ENV=production \
    APP_DEBUG=false

# Install system dependencies, Nginx, and OpenSSL
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    curl \
    git \
    unzip \
    openssl \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Install official PHP extension installer helper
COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

# Install verified PHP production extensions
RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache \
    intl

# Install Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Configure production OPcache
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=0'; \
    echo 'opcache.memory_consumption=192'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.save_comments=1'; \
    echo 'opcache.fast_shutdown=1'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Generate default self-signed SSL certificate so Nginx starts out-of-the-box
# (Can be overwritten in production by mounting valid Let's Encrypt / CA certs)
RUN mkdir -p /etc/nginx/ssl && \
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/nginx/ssl/privkey.pem \
    -out /etc/nginx/ssl/fullchain.pem \
    -subj "/C=ID/ST=Jakarta/L=Jakarta/O=KampusLMS/CN=localhost"

# Copy project source files (excluding items filtered in .dockerignore)
COPY . /var/www/html

# Copy compiled Vite assets from Stage 1 into public/build
COPY --from=frontend /app/public/build /var/www/html/public/build

# Copy production Nginx web server configuration
COPY nginx.conf /etc/nginx/nginx.conf

# Install Composer production dependencies & optimize classmap
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Ensure required directory structure exists and set strict ownership & permissions
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose HTTP (80) and HTTPS (443) ports
EXPOSE 80 443

# Container Healthcheck targeting Laravel 12 health route (/up)
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/up || exit 1

# Start PHP-FPM daemon and launch Nginx in foreground
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]

