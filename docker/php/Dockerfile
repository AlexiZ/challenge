FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libcurl4-openssl-dev \
    nodejs \
    npm \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure intl \
 && docker-php-ext-install \
    intl \
    pdo_pgsql \
    zip \
    opcache \
    gd \
    curl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY --link --from=ghcr.io/symfony-cli/symfony-cli:latest /usr/local/bin/symfony /usr/local/bin/symfony

RUN a2enmod rewrite

COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
