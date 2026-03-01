FROM php:8.3-fpm-alpine

# Установка системных зависимостей
RUN apk update && apk add --no-cache \
    build-base \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    oniguruma-dev \
    libxml2-dev \
    autoconf \
    automake \
    msmtp \
    mailx \
    tzdata

# Установка временной зоны Moscow
RUN cp /usr/share/zoneinfo/Europe/Moscow /etc/localtime && echo "Europe/Moscow" > /etc/timezone

# Установка PHP расширений
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Установка Redis расширения
RUN pecl channel-update pecl.php.net
RUN apk add --no-cache pcre-dev
RUN pecl install redis && docker-php-ext-enable redis

# Установка Node.js
RUN apk add --no-cache nodejs npm

# Настройка msmtp для отправки почты
RUN echo "account default" > /etc/msmtprc && \
    echo "host mailhog" >> /etc/msmtprc && \
    echo "port 1025" >> /etc/msmtprc && \
    echo "auto_from on" >> /etc/msmtprc && \
    chmod 600 /etc/msmtprc

# Настройка PHP для использования msmtp
RUN echo "sendmail_path=/usr/bin/msmtp -t" >> /usr/local/etc/php/conf.d/sendmail.ini

# НАСТРОЙКА ВРЕМЕННОЙ ЗОНЫ ДЛЯ PHP
RUN echo "date.timezone = Europe/Moscow" > /usr/local/etc/php/conf.d/timezone.ini

# Создание рабочей директории
WORKDIR /var/www

# Копирование существующего приложения
COPY . .

# Настройка прав
RUN chown -R www-data:www-data /var/www

EXPOSE 9000
CMD ["php-fpm"]