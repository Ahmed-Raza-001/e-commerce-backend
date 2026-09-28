FROM php:8.4-apache

# Install system dependencies & PostgreSQL PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    libicu-dev \
    && docker-php-ext-install pdo pdo_pgsql intl opcache

# Enable Apache mod_rewrite for Symfony routing
RUN a2enmod rewrite

# Set Apache DocumentRoot to /var/www/html/public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configure Symfony Fallback Resource for clean API URLs
RUN echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
    FallbackResource /index.php\n\
</Directory>' > /etc/apache2/conf-available/symfony.conf \
    && a2enconf symfony

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Set environment for debugging build
ENV APP_ENV=dev
ENV APP_DEBUG=1

# Install composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions for Symfony var directory & entrypoint
RUN mkdir -p var/cache var/log config/jwt \
    && chmod +x docker-entrypoint.sh \
    && chown -R www-data:www-data var config/jwt

EXPOSE 80

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
