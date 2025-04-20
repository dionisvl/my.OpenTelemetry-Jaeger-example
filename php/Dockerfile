FROM php:8.3-cli-alpine

RUN apk add --no-cache \
      $PHPIZE_DEPS \
      curl \
      git \
      unzip \
      zip \
      libzip-dev \
      zlib-dev \
    && docker-php-ext-install zip \
    && apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY composer.json composer.lock* ./

COPY . .

EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]
