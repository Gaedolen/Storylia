FROM php:8.2-apache

# Activer le module rewrite
RUN a2enmod rewrite

# Installer dépendances système et extensions PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libpq-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install intl pdo pdo_pgsql zip

# Installer MongoDB
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Copier Composer depuis l'image officielle
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier le projet
COPY . .

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Corriger le MPM Apache (important pour éviter le crash)
RUN a2dismod mpm_prefork mpm_worker && a2enmod mpm_event

# Permissions
RUN chown -R www-data:www-data /var/www/html

# Exposer le port 80
EXPOSE 80

# Lancer Apache
CMD ["apache2-foreground"]