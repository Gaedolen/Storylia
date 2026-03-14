# === Image de base PHP avec Apache ===
FROM php:8.2-apache

# === Installation des dépendances système ===
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libpq-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install intl pdo pdo_pgsql zip \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

# === Composer ===
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# === Définir le répertoire de travail ===
WORKDIR /var/www/html

# === Copier le code source ===
COPY . .

# === Installer les dépendances PHP sans scripts post-install ===
RUN composer install --no-dev --optimize-autoloader --no-scripts

# === Permissions ===
RUN chown -R www-data:www-data /var/www/html

# === Modules Apache ===
RUN a2enmod rewrite

# === Désactiver les MPM conflictuels et activer mpm_prefork ===
RUN a2dismod mpm_event mpm_worker || true
RUN a2enmod mpm_prefork

# === Exposer le port 80 ===
EXPOSE 80

# === Commande pour démarrer Apache ===
CMD ["apache2-foreground"]