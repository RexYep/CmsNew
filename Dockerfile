# ============================================
# DOCKERFILE
# complaint-management-system/Dockerfile
# ============================================
# PHP 8.2 with Apache - optimized for Render
# ============================================

FROM php:8.2-apache

# -----------------------------------------------
# Install PHP Extensions needed by your app
# -----------------------------------------------
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libssl-dev \
    unzip \
    curl \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# -----------------------------------------------
# Install Composer
# -----------------------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# -----------------------------------------------
# PHP Configuration
# -----------------------------------------------
RUN echo "upload_max_filesize = 50M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size = 55M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time = 120" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "display_errors = Off" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/custom.ini

# -----------------------------------------------
# Apache Configuration
# Enable mod_rewrite for clean URLs
# -----------------------------------------------
RUN a2enmod rewrite

# Apache config - allow .htaccess overrides
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/allow-override.conf \
    && a2enconf allow-override

# -----------------------------------------------
# Set Working Directory
# -----------------------------------------------
WORKDIR /var/www/html

# -----------------------------------------------
# Copy Project Files
# (vendor/ and .env are excluded via .dockerignore)
# -----------------------------------------------
COPY . .

# -----------------------------------------------
# Install Composer Dependencies
# -----------------------------------------------
RUN composer install --no-dev --optimize-autoloader --no-interaction

# -----------------------------------------------
# Create uploads directories
# (actual files will go to Cloudinary, these are just fallback)
# -----------------------------------------------
RUN mkdir -p uploads/complaints uploads/avatars \
    && chmod -R 755 uploads/

# -----------------------------------------------
# Fix file permissions
# -----------------------------------------------
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# -----------------------------------------------
# Expose port (Render sets $PORT automatically)
# -----------------------------------------------
EXPOSE 80

# -----------------------------------------------
# Start Apache
# -----------------------------------------------
CMD ["apache2-foreground"]