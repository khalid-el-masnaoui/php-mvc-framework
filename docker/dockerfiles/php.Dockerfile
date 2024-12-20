FROM php:8.1-fpm

SHELL ["/bin/bash", "-c"]

RUN chmod 1777 /tmp
# RUN  apt-get update -y && apt-get install -y procps 
 
#add user and group
RUN groupadd -f www-data && \
    (id -u www-data &> /dev/null || useradd -G www-data www-data -D)

#assign the created user same UID AND GUID OF the host for the mounted dir owner
ARG UID
ARG GID
 
RUN usermod -u $UID www-data
RUN groupmod -g $GID www-data

# Install PHP extensions deps
RUN apt-get update \
    && apt-get install -y \
        libicu-dev \
	    curl \
        vim \
        git \
        zip \
        unzip

# Install PHP extensions
RUN  docker-php-ext-configure intl \
    && docker-php-ext-install \
            intl \
            mysqli \
            pdo_mysql \
    && docker-php-ext-enable \
            opcache


#using multistage 
RUN install -d -m 0755 -o www-data -g www-data ~/.composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN chown -R www-data:www-data ~/.composer

#add apcu
RUN pecl install apcu \
  && docker-php-ext-enable apcu

EXPOSE 9000

# PID directory
RUN install -d -m 0755 -o www-data -g www-data /run/php-fpm

#custom php configs & source code
COPY --chown=www-data:www-data ../ /var/www/html/
COPY ./configurations/php/ /usr/local/etc/
COPY ./configurations/php/php/php.ini  /usr/local/etc/php/
COPY ./configurations/php/php/mods-available/opcache.ini  /usr/local/etc/php/conf.d/

#disable the default opcache.ini
RUN mv /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini.disabled

# Default sessions directory
RUN install -d -m 0755 -o www-data -g www-data /var/lib/php/sessions

#php logs
RUN install -o www-data -g www-data -d /var/log/php && \
    install -o www-data -g www-data /dev/null /var/log/php/error.log && \ 
    install -o www-data -g www-data /dev/null /var/log/php/php-fpm.log && \
    chown -R www-data:www-data /var/www

#clean dirs
RUN  apt-get clean && \
    rm -rf var/lib/apt/lists/* /tmp/* /var/tmp/*

WORKDIR /var/www/html    

USER www-data

# RUN composer install \
#     --no-interaction \
#     --no-plugins \
#     --no-scripts \
#     --no-dev \
#     --prefer-dist

# RUN composer dump-autoload --optimize
