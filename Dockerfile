# ---- Build frontend assets ----
FROM node:24-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

# ---- Application image ----
FROM php:8.4-fpm AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        libonig-dev \
        unzip \
        git \
    && docker-php-ext-install pdo pdo_mysql mbstring bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache

COPY conf/nginx/nginx-site.conf /etc/nginx/sites-enabled/default
COPY scripts/start.sh /start.sh
RUN chmod +x /start.sh

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

EXPOSE 80
CMD ["/start.sh"]
