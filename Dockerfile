# DariPets — backend Symfony 7.4 pour Render (Docker)
FROM php:8.3-apache

# Extensions requises : Postgres (Supabase), intl, opcache, zip
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev libpq-dev libzip-dev unzip git \
    && docker-php-ext-install intl pdo_pgsql zip opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Apache : docroot sur public/ + AllowOverride pour le routage Symfony
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n  AllowOverride None\n  Require all granted\n  FallbackResource /index.php\n</Directory>\n' \
        > /etc/apache2/conf-available/symfony.conf \
    && a2enconf symfony

# Render fournit le port via $PORT
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:${PORT}>/' /etc/apache2/sites-available/000-default.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

ENV APP_ENV=prod
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && php bin/console cache:clear --no-warmup || true

# Au démarrage : génère les clés JWT si absentes, applique le schéma, warmup, puis Apache
CMD php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction \
    && php bin/console doctrine:schema:update --force --no-interaction \
    && (php bin/console doctrine:fixtures:load --no-interaction --append || echo "Fixtures skipped") \
    && php bin/console cache:warmup \
    && apache2-foreground
