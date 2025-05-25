FROM php:8.1-apache

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    git \
    curl \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo_mysql mysqli zip exif pcntl bcmath soap

# Activer le module rewrite d'Apache
RUN a2enmod rewrite

# Configurer PHP pour la production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/memory_limit = 128M/memory_limit = 256M/g' "$PHP_INI_DIR/php.ini" \
    && sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 20M/g' "$PHP_INI_DIR/php.ini" \
    && sed -i 's/post_max_size = 8M/post_max_size = 20M/g' "$PHP_INI_DIR/php.ini"

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier le code source de l'application
COPY . /var/www/html/

# Configurer les permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Script d'initialisation
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Exposer le port 80
EXPOSE 80

# Commande par défaut pour démarrer Apache avec le script d'initialisation
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]