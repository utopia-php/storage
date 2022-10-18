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

ENV PHP_ZSTD_VERSION="master"
ENV PHP_BROTLI_VERSION="4504e4186e79b197cfcb75d4d09aa47ef7d92fe"

RUN apk add --no-cache \
    git \
    autoconf \
    make \
    g++  \
    zstd-dev \
    brotli-dev

## Zstandard Extension
FROM compile AS zstd
RUN git clone --recursive --depth 1 --branch $PHP_ZSTD_VERSION https://github.com/kjdev/php-ext-zstd.git \
  && cd php-ext-zstd \
  && phpize \
  && ./configure --with-libzstd \
  && make && make install

FROM compile as brotli
RUN git clone --branch $PHP_BROTLI_VERSION https://github.com/kjdev/php-ext-brotli.git \
 && cd php-ext-brotli \
 && phpize \
 && ./configure --with-libbrotli \
 && make && make install

FROM compile as final

LABEL maintainer="team@appwrite.io"

WORKDIR /usr/src/code

RUN echo extension=zstd.so >> /usr/local/etc/php/conf.d/zstd.ini
RUN echo extension=brotli.so >> /usr/local/etc/php/conf.d/brotli.ini

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
  && echo "opcache.enable_cli=1" >> $PHP_INI_DIR/php.ini \
  && echo "memory_limit=1024M" >> $PHP_INI_DIR/php.ini

COPY --from=composer /usr/local/src/vendor /usr/src/code/vendor
COPY --from=zstd /usr/local/lib/php/extensions/no-debug-non-zts-20200930/zstd.so /usr/local/lib/php/extensions/no-debug-non-zts-20200930/
COPY --from=brotli /usr/local/lib/php/extensions/no-debug-non-zts-20200930/brotli.so /usr/local/lib/php/extensions/no-debug-non-zts-20200930/

# Add Source Code
COPY . /usr/src/code

CMD [ "tail", "-f", "/dev/null" ]
