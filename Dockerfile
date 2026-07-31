FROM php:8.2-cli

# Install dependencies yang dibutuhkan Laravel & SQLite
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

WORKDIR /app

# Copy seluruh file project
COPY . .

# Install Composer Dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

# Siapkan folder storage, cache, & database SQLite dengan permission penuh
RUN mkdir -p storage/framework/views storage/framework/sessions storage/framework/cache storage/logs bootstrap/cache database
RUN touch database/database.sqlite
RUN chmod -R 777 storage bootstrap/cache database

# Set default port
ENV PORT=8080
EXPOSE 8080

# Jalankan migrasi database SQLite lalu start server
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
