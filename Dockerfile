FROM php:8.2-fpm
LABEL authors="Andrew Murray"

ARG BUILD_APP_VERSION
ENV APP_VERSION=${BUILD_APP_VERSION}
ENV NODE_MAJOR=20

RUN apt-get -qq update  \
    && mkdir -p /etc/apt/keyrings \
    && apt-get -qq -y --no-install-recommends install \
    openssl \
    gnupg \
    mtr \
    traceroute \
    mariadb-client \
    bash \
    curl \
    git \
    nginx \
    supervisor \
    nano \
    wget \
    iputils-ping \
    redis-tools \
    cron \
    netcat-traditional \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_MAJOR.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list \
    && apt-get -qq update \
    && apt-get install nodejs -y \
    && rm -f /etc/nginx/conf.d/default.conf \
    && pecl install redis-5.3.7 \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo_mysql mysqli sockets bcmath pcntl opcache \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && wget -O /etc/ssl/certs/ca-certificates.crt --no-check-certificate https://curl.se/ca/cacert.pem \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get autoremove \
    && apt-get clean

# Copy nginx config
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Copy PHP-FPM config
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php.ini "$PHP_INI_DIR/conf.d/custom.ini"

# Configure supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh

# Set working directory.
WORKDIR /var/www

# Copy existing application directory contents.
#COPY ./src /var/www

# Set permissions, install dependencies and generate autoload files.
#RUN $(which composer) install --no-interaction --prefer-dist --optimize-autoloader --no-cache \
#    && cp .env.docker.example .env \
#    && npm install --quiet \
#    && npm run build

HEALTHCHECK --interval=5s --timeout=3s --start-period=5s --retries=3 CMD curl --fail http://localhost:8081 || exit 1

CMD ["/bin/bash", "/entrypoint.sh"]
