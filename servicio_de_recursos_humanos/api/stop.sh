#!/bin/bash

echo "🛑 Deteniendo Auth Service..."

# Detener contenedores
docker-compose down

echo "✅ Servicio detenido"
