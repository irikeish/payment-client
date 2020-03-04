FROM php:7.3.3-apache
RUN apt-get update -y && apt-get install -y openssl zip unzip git libzip-dev

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN docker-php-ext-install pdo mbstring zip mysqli  pdo_mysql
RUN docker-php-ext-configure zip --with-libzip

# replace shell with bash so we can source files
RUN rm /bin/sh && ln -s /bin/bash /bin/sh

# update the repository sources list
# and install dependencies
RUN apt-get update \
    && apt-get install -y curl \
    && apt-get -y autoclean

WORKDIR /var/www/html/
COPY . /var/www/html/
COPY ./.docker/deps/apache2/000-default.conf /etc/apache2/sites-available/

# Run composer
#RUN composer install --optimize-autoloader

# Enable mod_rewrite to enable URL matching in apache
RUN a2enmod rewrite

WORKDIR /var/www/html/

RUN chown www-data:www-data -R ./

#RUN composer dump-autoload

EXPOSE 80
