#!/bin/bash

# Script de inicialización del Auth Service
# Este script debe ejecutarse DENTRO del contenedor después de construirlo

echo "=========================================="
echo "Inicializando Auth Service API"
echo "=========================================="

# 1. Instalar Laravel 12
echo "1. Instalando Laravel 12..."
cd /var/www && composer create-project laravel/laravel . "^12.0" --prefer-dist

# 2. Instalar JWT
echo "2. Instalando tymon/jwt-auth..."
composer require tymon/jwt-auth

# 3. Publicar configuración de JWT
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"

# 4. Generar secret key de JWT
php artisan jwt:secret

# 5. Crear estructura de carpetas adicionales
echo "3. Creando estructura de carpetas..."
mkdir -p app/Http/Requests/Auth
mkdir -p app/Http/Requests/Users
mkdir -p app/Services

# 6. Dar permisos
echo "4. Configurando permisos..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo "=========================================="
echo "✓ Inicialización completada"
echo "=========================================="
echo ""
echo "Próximos pasos:"
echo "1. Copiar archivos de configuración (.env)"
echo "2. Ejecutar migraciones: php artisan migrate"
echo "3. Crear seeders para roles"
echo ""
