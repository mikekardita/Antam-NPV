FROM php:8.2-cli

# Install dependencies yang dibutuhkan Laravel & SQLite
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

WORKDIR /app

# Copy seluruh file project
COPY . .

# Install Composer Dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

# Siapkan folder storage & database SQLite dengan permission penuh
RUN mkdir -p storage/framework/views storage/framework/sessions storage/framework/cache storage/logs bootstrap/cache database
RUN touch database/database.sqlite
RUN chmod -R 777 storage bootstrap/cache database

# Set default port & environment
ENV PORT=8080
ENV DB_CONNECTION=sqlite
ENV DB_DATABASE=/app/database/database.sqlite
EXPOSE 8080

# Jalankan server langsung
CMD php artisan db:seed --force || true; php artisan serve --host=0.0.0.0 --port=$PORT
