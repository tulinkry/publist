# syntax=docker/dockerfile:1

FROM php:8.4-cli-bookworm AS base

# Keep this extension list in sync with flake.nix's withExtensions list.
# imap was dropped from PHP core as of 8.4 (upstream php-imap moved to PECL),
# so it's built via pecl instead of docker-php-ext-configure/-install here.
RUN apt-get update && apt-get install -y --no-install-recommends \
		git unzip libicu-dev libcurl4-openssl-dev libpng-dev libjpeg62-turbo-dev \
		libfreetype6-dev libc-client-dev libkrb5-dev libzip-dev libonig-dev \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg \
	&& docker-php-ext-install -j"$(nproc)" \
		curl mbstring intl pdo_mysql gd bcmath zip \
	&& pecl install imap \
	&& docker-php-ext-enable imap \
	&& rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .


FROM base AS test

RUN composer install --no-interaction --prefer-dist
RUN vendor/bin/php-cs-fixer fix --dry-run --diff
RUN vendor/bin/tester tests/ && touch /app/tests/.tests-passed


FROM base AS runtime

COPY --from=test /app/tests/.tests-passed /app/tests/.tests-passed
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev \
	&& mkdir -p log temp/cache temp/sessions www/tmp \
	&& chown -R www-data:www-data log temp www/tmp

EXPOSE 8080
USER www-data
CMD ["php", "-S", "0.0.0.0:8080", "-t", "www", "bin/router.php"]
