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
ENV PHP_BROTLI_VERSION="7ae4fcd8b81a65d7521c298cae49af386d1ea4e3"
ENV PHP_SNAPPY_VERSION="bfefe4906e0abb1f6cc19005b35f9af5240d9025"
ENV PHP_LZ4_VERSION="2f006c3e4f1fb3a60d2656fc164f9ba26b71e995"
ENV PHP_XZ_VERSION=5.2.7
ENV PHP_EXT_XZ_VERSION=1.1.2

RUN apk add --no-cache \
  git \
  autoconf \
  make \
  g++  \
  zstd-dev \
  brotli-dev \
  lz4-dev

## Zstandard Extension
FROM compile AS zstd
RUN git clone --recursive --depth 1 --branch $PHP_ZSTD_VERSION https://github.com/kjdev/php-ext-zstd.git \
  && cd php-ext-zstd \
  && phpize \
  && ./configure --with-libzstd \
  && make && make install

## Brotli Extension
FROM compile as brotli
RUN git clone https://github.com/kjdev/php-ext-brotli.git \
  && cd php-ext-brotli \
  && git reset --hard $PHP_BROTLI_VERSION \
  && phpize \
  && ./configure --with-libbrotli \
  && make && make install

## LZ4 Extension
FROM compile AS lz4
RUN git clone --recursive https://github.com/kjdev/php-ext-lz4.git \
  && cd php-ext-lz4 \
  && git reset --hard $PHP_LZ4_VERSION \
  && phpize \
  && ./configure --with-lz4-includedir=/usr \ 
  && make && make install

## Snappy Extension
FROM compile AS snappy
RUN git clone --recursive https://github.com/kjdev/php-ext-snappy.git \
  && cd php-ext-snappy \
  && git reset --hard $PHP_SNAPPY_VERSION \
  && phpize \
  && ./configure \
  && make && make install

## Xz Extension
FROM compile as xz
RUN wget https://tukaani.org/xz/xz-${PHP_XZ_VERSION}.tar.xz -O xz.tar.xz \
  && tar -xJf xz.tar.xz \
  && rm xz.tar.xz \
  && ( \
  cd xz-${PHP_XZ_VERSION} \
  && ./configure \
  && make \
  && make install \
  ) \
  && rm -r xz-${PHP_XZ_VERSION}

RUN git clone https://github.com/codemasher/php-ext-xz.git --branch ${PHP_EXT_XZ_VERSION} \
  && cd php-ext-xz \
  && phpize \
  && ./configure \
  && make && make install

FROM compile as final

LABEL maintainer="team@appwrite.io"

WORKDIR /usr/src/code

RUN echo extension=zstd.so >> /usr/local/etc/php/conf.d/zstd.ini
RUN echo extension=brotli.so >> /usr/local/etc/php/conf.d/brotli.ini
RUN echo extension=lz4.so >> /usr/local/etc/php/conf.d/lz4.ini
RUN echo extension=snappy.so >> /usr/local/etc/php/conf.d/snappy.ini
RUN echo extension=xz.so >> /usr/local/etc/php/conf.d/xz.ini

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
  && echo "opcache.enable_cli=1" >> $PHP_INI_DIR/php.ini \
  && echo "memory_limit=1024M" >> $PHP_INI_DIR/php.ini

COPY --from=composer /usr/local/src/vendor /usr/src/code/vendor
COPY --from=zstd /usr/local/lib/php/extensions/no-debug-non-zts-20200930/zstd.so /usr/local/lib/php/extensions/no-debug-non-zts-20200930/
COPY --from=brotli /usr/local/lib/php/extensions/no-debug-non-zts-20200930/brotli.so /usr/local/lib/php/extensions/no-debug-non-zts-20200930/
COPY --from=lz4 /usr/local/lib/php/extensions/no-debug-non-zts-20200930/lz4.so /usr/local/lib/php/extensions/no-debug-non-zts-20200930/
COPY --from=snappy /usr/local/lib/php/extensions/no-debug-non-zts-20200930/snappy.so /usr/local/lib/php/extensions/no-debug-non-zts-20200930/
COPY --from=xz /usr/local/lib/php/extensions/no-debug-non-zts-20200930/xz.so /usr/local/lib/php/extensions/no-debug-non-zts-20200930/

# Add Source Code
COPY . /usr/src/code

CMD [ "tail", "-f", "/dev/null" ]
