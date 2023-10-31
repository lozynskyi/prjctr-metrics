FROM php:8.2-fpm

RUN apt-get -y update \
    && apt-get install -y libssl-dev pkg-config libzip-dev unzip git

RUN pecl install zlib zip mongodb \
    && docker-php-ext-enable zip \
    && docker-php-ext-enable mongodb
RUN apt install wget -y

WORKDIR /app

COPY ./docker/php-fpm/entrypoint.sh /usr/share/entrypoint
RUN chmod +x /usr/share/entrypoint
ENTRYPOINT ["/usr/share/entrypoint"]

RUN wget https://getcomposer.org/installer && \
    php installer --install-dir=/usr/local/bin/ --filename=composer && \
    rm installer