FROM php:8.2-cli

# Install dependencies yang dibutuhkan Laravel
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

WORKDIR /app

# Copy seluruh file project
COPY . .

# Install Composer & Dependencies PHP
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

# Buat database SQLite jika belum ada
RUN touch database/database.sqlite

# Run Artisan Serve
ENV PORT=8000
EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=$PORT
