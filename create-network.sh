#!/bin/bash

# Script para crear la red Docker para el sistema de microservicios
# Autor: Sistema de Inventario - Equipo SD
# Fecha: 28 de noviembre de 2025

echo "🌐 Creando red Docker: sd_network"

# Verificar si la red ya existe
if docker network ls | grep -q sd_network; then
    echo "⚠️  La red 'sd_network' ya existe"
    echo "ℹ️  Puedes eliminarla con: docker network rm sd_network"
else
    # Crear la red
    docker network create sd_network
    echo "✅ Red 'sd_network' creada exitosamente"
fi

# Mostrar información de la red
echo ""
echo "📋 Información de la red:"
docker network inspect sd_network

echo ""
echo "✅ Red lista para usar en los contenedores"
