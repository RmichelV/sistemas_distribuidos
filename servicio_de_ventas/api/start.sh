#!/bin/bash

echo "🚀 Iniciando Auth Service..."

# Levantar contenedores
echo "📦 Levantando contenedores Docker..."
docker-compose up -d

# Esperar a que los contenedores estén listos
echo "⏳ Esperando a que los contenedores estén listos..."
sleep 5

# Verificar si composer install ya se ejecutó
if [ ! -d "vendor" ]; then
    echo "📥 Instalando dependencias de Composer..."
    docker exec -it auth_api composer install
else
    echo "✅ Dependencias de Composer ya instaladas"
fi

# Verificar si .env existe
if [ ! -f ".env" ]; then
    echo "📝 Copiando archivo .env..."
    cp .env.example .env
    echo "⚠️  Recuerda configurar las variables de entorno en .env"
fi

# Generar APP_KEY si no existe
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generando Application Key..."
    docker exec -it auth_api php artisan key:generate
fi

# Generar JWT Secret si no existe
if ! grep -q "JWT_SECRET=" .env; then
    echo "🔐 Generando JWT Secret..."
    docker exec -it auth_api php artisan jwt:secret
fi

# Ejecutar migraciones
echo "🗄️  Ejecutando migraciones..."
docker exec -it auth_api php artisan migrate

# Ejecutar seeders
echo "🌱 Ejecutando seeders..."
docker exec -it auth_api php artisan db:seed

echo ""
echo "✨ Auth Service iniciado exitosamente!"
echo ""
echo "📡 Servicio disponible en: http://localhost:8001"
echo "📚 Documentación API: http://localhost:8001/api"
echo "🏥 Health Check: http://localhost:8001/api/health"
echo ""
echo "👤 Usuario de prueba:"
echo "   Email: admin@ewtto.com"
echo "   Password: admin123"
echo ""
echo "📝 Logs:"
echo "   docker logs auth_api"
echo "   docker logs auth_nginx"
echo ""
