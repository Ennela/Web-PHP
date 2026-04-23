# ─────────────────────────────────────────────────
# Dockerfile for WEB-PHP (PHP + Apache + MySQL ext)
# Optimized for Railway deployment
# ─────────────────────────────────────────────────
FROM php:8.2-apache

# Install system dependencies + PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        mysqli \
        gd \
        zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable rewrite module
RUN a2enmod rewrite

# Configure Apache: allow .htaccess overrides & set ServerName
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Default PORT for Railway
ENV PORT=80

# Set working directory
WORKDIR /var/www/html

# Copy all application files
COPY . .

# Make entrypoint executable
RUN chmod +x docker-entrypoint.sh

# Create upload directories and set permissions
RUN mkdir -p admin/uploads auth/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 admin/uploads auth/uploads

# Use entrypoint script to fix MPM + PORT at runtime
ENTRYPOINT ["./docker-entrypoint.sh"]
