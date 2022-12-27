FROM composer:2.0 as composer

ARG TESTING=false
ENV TESTING=$TESTING

WORKDIR /usr/local/src/

COPY composer.lock /usr/local/src/
COPY composer.json /usr/local/src/

RUN composer update \
    --ignore-platform-reqs \
    --optimize-autoloader \
    --no-plugins  \
    --no-scripts \
    --prefer-dist

FROM php:8.0-cli-alpine as compile

ENV PHP_ZSTD_VERSION="master" \
    PHP_SNAPPY_VERSION=bfefe4906e0abb1f6cc19005b35f9af5240d9025

RUN apk add --no-cache \
    git \
    autoconf \
    make \
    g++  \
    zstd-dev

## Zstandard Extension
FROM compile AS zstd
RUN git clone --recursive --depth 1 --branch $PHP_ZSTD_VERSION https://github.com/kjdev/php-ext-zstd.git \
  && cd php-ext-zstd \
  && phpize \
  && ./configure --with-libzstd \
  && make && make install

## Snappy Extension
FROM compile AS snappy
RUN git clone --recursive --depth 1 https://github.com/kjdev/php-ext-snappy.git \
  && cd php-ext-snappy \
  && git checkout $PHP_SNAPPY_VERSION \
  && phpize \
  && ./configure \
  && make && make install

FROM compile as final

LABEL maintainer="team@appwrite.io"

WORKDIR /usr/src/code

RUN echo extension=zstd.so >> /usr/local/etc/php/conf.d/zstd.ini
RUN echo extension=snappy.so >> /usr/local/etc/php/conf.d/snappy.ini

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
  && echo "opcache.enable_cli=1" >> $PHP_INI_DIR/php.ini \
  && echo "memory_limit=1024M" >> $PHP_INI_DIR/php.ini

COPY --from=composer /usr/local/src/vendor /usr/src/code/vendor
COPY --from=zstd /usr/local/lib/php/extensions/no-debug-non-zts-20200930/zstd.so /usr/local/lib/php/extensions/no-debug-non-zts-20200930/
COPY --from=snappy /usr/local/lib/php/extensions/no-debug-non-zts-20200930/snappy.so /usr/local/lib/php/extensions/no-debug-non-zts-20200930/

# Add Source Code
COPY . /usr/src/code

CMD [ "tail", "-f", "/dev/null" ]
