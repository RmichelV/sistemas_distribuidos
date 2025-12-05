#!/bin/bash

echo "========================================="
echo "Deteniendo infraestructura MySQL"
echo "========================================="

cd "$(dirname "$0")"

echo ""
echo "1. Deteniendo contenedores REPLICA..."
docker compose -f servicio_de_autenticacion_y_usuarios/sd_db_auth_replica.yml down
docker compose -f servicio_de_sucursales/sd_db_branches_replica.yml down
docker compose -f servicio_de_inventario/sd_db_inventory_replica.yml down
docker compose -f servicio_de_ventas/sd_db_sales_replica.yml down
docker compose -f servicio_de_clientes_y_reservaciones/sd_db_reservations_replica.yml down
docker compose -f servicio_de_recursos_humanos/sd_db_hr_replica.yml down
docker compose -f servicio_de_configuracion/sd_db_config_replica.yml down

echo ""
echo "2. Deteniendo contenedores MASTER..."
docker compose -f servicio_de_autenticacion_y_usuarios/sd_db_auth.yml down
docker compose -f servicio_de_sucursales/sd_db_branches.yml down
docker compose -f servicio_de_inventario/sd_db_inventory.yml down
docker compose -f servicio_de_ventas/sd_db_sales.yml down
docker compose -f servicio_de_clientes_y_reservaciones/sd_db_reservations.yml down
docker compose -f servicio_de_recursos_humanos/sd_db_hr.yml down
docker compose -f servicio_de_configuracion/sd_db_config.yml down

echo ""
echo "3. Deteniendo phpMyAdmin..."
docker compose -f phpmyadmin/docker-compose.yml down

echo ""
echo "========================================="
echo "✓ Todos los contenedores detenidos"
echo "========================================="
echo ""
echo "Nota: Los datos persisten en los volúmenes Docker"
echo "Para eliminar también los volúmenes usa: ./stop-all.sh --volumes"
echo ""
