FROM php:8.1-fpm

RUN apt-get update \
  && apt-get install -y \ 
  libpq-dev \ 
  zip \ 
  unzip \
  && docker-php-ext-configure pgsql --with-pgsql=/usr/local/pgsql \
  && docker-php-ext-install pgsql pdo pdo_pgsql

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
