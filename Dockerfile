FROM php:7.4-alpine

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin  --filename=composer

# Inject application code
COPY . /srv/

WORKDIR /srv
ENV CONTRACTS_DIR=data
RUN composer install --optimize-autoloader

ARG DOCKER_TAG=test
RUN VERSION=`echo ${DOCKER_TAG}| egrep "^([0-9.]+)$" && \
    if [ -n "$VERSION" ]; then \
        echo "VERSION $VERSION"; \
        composer require bigfoot90/phpacto:${VERSION}; \
    fi

RUN ./vendor/bin/phpacto validate

# Start
USER www-data
CMD php -S 0.0.0.0:8000 bin/recorder.php

EXPOSE 8000
