FROM php:8.3-apache

# Install PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd zip bcmath \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure Apache
RUN echo '<Directory /var/www/html>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/custom.conf \
    && a2enconf custom

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . /var/www/html/

# Create upload directory
RUN mkdir -p /var/www/html/uploads/default \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/uploads

# Apache port
EXPOSE 80

# Ensure upload dir is writable and default banners exist before Apache starts.
CMD ["sh", "-c", "mkdir -p /var/www/html/uploads/default && chown -R www-data:www-data /var/www/html/uploads && php /var/www/html/scripts/generate_default_banners.php && chown -R www-data:www-data /var/www/html/uploads && apache2-foreground"]
