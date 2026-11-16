FROM php:8.3-cli
RUN apt-get update && apt-get install -y git unzip libpq-dev libzip-dev \
 && docker-php-ext-install pdo_pgsql zip \
 && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ARG SERVICE
WORKDIR /app
ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_SECURITY_BLOCKING=1
RUN composer create-project laravel/laravel:^12.0 . --no-interaction
COPY services/${SERVICE}/overlay/ /app/
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
EXPOSE 8000
ENTRYPOINT ["/entrypoint.sh"]
