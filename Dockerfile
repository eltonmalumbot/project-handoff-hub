# syntax=docker/dockerfile:1
FROM serversideup/php:8.4-fpm-nginx AS php-dependencies
USER root
WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
RUN --mount=type=secret,id=proxy_ca \
    if [ -f /run/secrets/proxy_ca ]; then export COMPOSER_CAFILE=/run/secrets/proxy_ca; fi; \
    composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

FROM node:24-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN --mount=type=secret,id=proxy_ca \
    if [ -f /run/secrets/proxy_ca ]; then export NODE_EXTRA_CA_CERTS=/run/secrets/proxy_ca; fi; \
    npm ci
COPY vite.config.js ./
COPY resources ./resources
COPY --from=php-dependencies /var/www/html/vendor ./vendor
RUN npm run build

FROM php-dependencies AS runtime
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build
# Laravel owns dynamic headers; retain nginx file access restrictions.
RUN sed -i '/^add_header /d' /etc/nginx/server-opts.d/security.conf \
 && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache
ENV APP_ENV=production APP_DEBUG=false SSL_MODE=off AUTORUN_ENABLED=false
USER www-data
EXPOSE 8080
