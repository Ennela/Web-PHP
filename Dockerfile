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

# Fix MPM conflict: ensure only prefork is loaded (required by mod_php)
RUN a2dismod mpm_event mpm_worker 2>/dev/null; \
    a2enmod mpm_prefork rewrite

# Configure Apache: allow .htaccess overrides & set ServerName
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Default PORT for Railway
ENV PORT=80

# Set working directory
WORKDIR /var/www/html

# Copy all application files
COPY . .

# Create upload directories and set permissions
RUN mkdir -p admin/uploads auth/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 admin/uploads auth/uploads

# Start Apache with dynamic PORT binding (Railway injects PORT at runtime)
CMD sed -i "s/80/${PORT}/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf && apache2-foreground
