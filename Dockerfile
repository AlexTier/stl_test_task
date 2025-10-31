FROM php:8.2-fpm

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev

# Очистка кеша
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Установка расширений PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install -j$(nproc) pdo_mysql mbstring zip exif pcntl gd

# Установка Redis расширения
RUN pecl install redis && docker-php-ext-enable redis

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Настройка рабочей директории
WORKDIR /var/www

# Копируем файлы composer
COPY composer.json composer.lock* ./

# Устанавливаем зависимости
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Копируем весь проект
COPY . .

# Создаем необходимые директории ПЕРЕД composer dump-autoload
RUN mkdir -p storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Устанавливаем права
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Генерируем автозагрузку
RUN composer dump-autoload --optimize

# Expose порт 9000 для PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
