FROM php:8.1-fpm

ENV USER=www
ENV GROUP=www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    supervisor \
    libzip-dev \
    libmagickwand-dev \
    cron --no-install-recommends

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install ImageMagick and PHP imagick extension
RUN pecl install imagick && \
    docker-php-ext-enable imagick

# Install Postgre PDO
RUN apt-get install -y libpq-dev && docker-php-ext-install pdo pdo_pgsql

# Install PHP extension - intl
RUN apt-get install -y libicu-dev && docker-php-ext-install intl

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Setup working directory
WORKDIR /var/www/

# Create User and Group
RUN groupadd -g 1000 ${GROUP} && useradd -u 1000 -ms /bin/bash -g ${GROUP} ${USER}

# Grant Permissions
RUN chown -R ${USER} /var/www

# Copy configuration files before switching user
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY ./laravel-worker.conf /etc/supervisor/conf.d/laravel-worker.conf
COPY crontab /etc/cron.d/laravel-cron

# Change permissions before switching user
RUN chmod 0644 /etc/cron.d/laravel-cron

# Select User
USER ${USER}

# Ensure the vendor directory is owned by the correct user
RUN mkdir -p /var/www/vendor && chown -R www:www /var/www

# Install cron job
USER root
RUN crontab /etc/cron.d/laravel-cron

USER ${USER}

EXPOSE 9000

# Ganti CMD ini dengan supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
