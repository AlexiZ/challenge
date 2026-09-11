FROM dunglas/frankenphp:php8.3 AS frankenphp_base

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
		acl file gettext git openssh-client unzip nodejs npm \
	&& rm -rf /var/lib/apt/lists/*

RUN set -eux; \
	install-php-extensions \
		@composer \
		apcu \
		intl \
		opcache \
		zip \
		pdo_pgsql \
		gd \
		curl

# .spells execs as the host uid:gid; tools like ssh call getpwuid() and refuse to run
# for an unknown uid. Default to 1000 (the common single-user Linux uid) - override with
# --build-arg DEV_UID/DEV_GID if your host uid differs.
ARG DEV_UID=1000
ARG DEV_GID=1000
RUN echo "devuser:x:${DEV_UID}:${DEV_GID}::/home/devuser:/bin/sh" >> /etc/passwd \
	&& mkdir -p /home/devuser/.ssh \
	&& chown -R ${DEV_UID}:${DEV_GID} /home/devuser

COPY docker/frankenphp/conf.d/app.ini $PHP_INI_DIR/conf.d/
COPY docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]

# ---------- dev ----------
FROM frankenphp_base AS frankenphp_dev

ENV APP_ENV=dev XDEBUG_MODE=off
RUN install-php-extensions xdebug

# ---------- prod ----------
FROM frankenphp_base AS frankenphp_prod

ENV APP_ENV=prod

COPY . .
RUN composer install --no-dev --prefer-dist --optimize-autoloader \
	&& npm ci \
	&& php bin/console asset-map:compile \
	&& rm -rf node_modules \
	&& chmod +x bin/console
