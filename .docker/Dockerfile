FROM php:8.1-cli

RUN apt-get update -yq && \
    apt-get install -yq  software-properties-common curl ca-certificates \
    libmcrypt-dev libicu-dev gnupg unzip && \
    pecl channel-update pecl.php.net && \
    pecl install xdebug && \
    curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    pecl channel-update pecl.php.net && \
    pecl install mcrypt && \
    docker-php-ext-enable mcrypt && \
    docker-php-ext-enable xdebug && \
    docker-php-ext-configure intl  && \
    docker-php-ext-install intl && \
    rm -rf /var/lib/apt/lists/*

EXPOSE 9003

ENTRYPOINT ["docker-php-entrypoint"]
CMD ["php", "-a"]
