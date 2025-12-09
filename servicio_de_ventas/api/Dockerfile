# Dockerfile para Auth Service - Laravel 12
FROM php:8.3-fpm

# Argumentos de construcción
ARG user=authuser
ARG uid=1000

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP requeridas
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Crear usuario del sistema para ejecutar comandos Composer y Artisan
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Establecer directorio de trabajo
WORKDIR /var/www

# Copiar archivos de composer primero (para aprovechar cache de Docker)
COPY composer.json ./

# Instalar dependencias de PHP
RUN composer install --no-scripts --prefer-dist

# Copiar el resto de la aplicación
COPY . .

# Optimizar autoloader
RUN composer dump-autoload --optimize

# Cambiar permisos
RUN chown -R $user:www-data /var/www
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Cambiar al usuario creado
USER $user

# Exponer puerto 9000 para PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
