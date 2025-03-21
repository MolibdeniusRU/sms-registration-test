FROM unit:1.34.2-php8.4
RUN apt-get update && apt-get install -y \
    libpq-dev

RUN docker-php-ext-configure pdo_pgsql \
    && docker-php-ext-install pdo_pgsql

RUN pecl install redis \
    	&& pecl install xdebug \
    	&& docker-php-ext-enable redis xdebug pdo_pgsql

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY ./unit/config.json /docker-entrypoint.d/

WORKDIR /var/www/sms-registration
