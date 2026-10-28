FROM php:8.2-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libonig-dev libcurl4-openssl-dev libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" pdo_pgsql mbstring curl gd fileinfo \
 && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN a2enmod rewrite headers
WORKDIR /var/www/html
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html/uploads /var/www/html/pelanggan/uploads 2>/dev/null || true
# Environment variables are configured in the hosting dashboard, not baked into image.
