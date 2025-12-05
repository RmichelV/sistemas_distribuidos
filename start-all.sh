#!/bin/bash

echo "========================================="
echo "Iniciando infraestructura MySQL con replicación"
echo "========================================="

cd "$(dirname "$0")"

# Detener todos los contenedores si están corriendo
echo ""
echo "1. Deteniendo contenedores existentes..."
./stop-all.sh 2>/dev/null || true

echo ""
echo "2. Iniciando contenedores MASTER..."
echo ""

docker compose -f servicio_de_autenticacion_y_usuarios/sd_db_auth.yml up -d
echo "  ✓ sd_db_auth (MASTER) iniciado"

docker compose -f servicio_de_sucursales/sd_db_branches.yml up -d
echo "  ✓ sd_db_branches (MASTER) iniciado"

docker compose -f servicio_de_inventario/sd_db_inventory.yml up -d
echo "  ✓ sd_db_inventory (MASTER) iniciado"

docker compose -f servicio_de_ventas/sd_db_sales.yml up -d
echo "  ✓ sd_db_sales (MASTER) iniciado"

docker compose -f servicio_de_clientes_y_reservaciones/sd_db_reservations.yml up -d
echo "  ✓ sd_db_reservations (MASTER) iniciado"

docker compose -f servicio_de_recursos_humanos/sd_db_hr.yml up -d
echo "  ✓ sd_db_hr (MASTER) iniciado"

docker compose -f servicio_de_configuracion/sd_db_config.yml up -d
echo "  ✓ sd_db_config (MASTER) iniciado"

echo ""
echo "3. Esperando 15 segundos a que los MASTERS estén listos..."
sleep 15

echo ""
echo "4. Iniciando contenedores REPLICA..."
echo ""

docker compose -f servicio_de_autenticacion_y_usuarios/sd_db_auth_replica.yml up -d
echo "  ✓ sd_db_auth_replica (SLAVE) iniciado"

docker compose -f servicio_de_sucursales/sd_db_branches_replica.yml up -d
echo "  ✓ sd_db_branches_replica (SLAVE) iniciado"

docker compose -f servicio_de_inventario/sd_db_inventory_replica.yml up -d
echo "  ✓ sd_db_inventory_replica (SLAVE) iniciado"

docker compose -f servicio_de_ventas/sd_db_sales_replica.yml up -d
echo "  ✓ sd_db_sales_replica (SLAVE) iniciado"

docker compose -f servicio_de_clientes_y_reservaciones/sd_db_reservations_replica.yml up -d
echo "  ✓ sd_db_reservations_replica (SLAVE) iniciado"

docker compose -f servicio_de_recursos_humanos/sd_db_hr_replica.yml up -d
echo "  ✓ sd_db_hr_replica (SLAVE) iniciado"

docker compose -f servicio_de_configuracion/sd_db_config_replica.yml up -d
echo "  ✓ sd_db_config_replica (SLAVE) iniciado"

echo ""
echo "5. Iniciando phpMyAdmin..."
docker compose -f phpmyadmin/docker-compose.yml up -d
echo "  ✓ phpMyAdmin iniciado en http://localhost:8080"

echo ""
echo "========================================="
echo "✓ Infraestructura iniciada correctamente"
echo "========================================="
echo ""
echo "Contenedores MASTER (escritura/lectura):"
echo "  - sd_db_auth"
echo "  - sd_db_branches"
echo "  - sd_db_inventory"
echo "  - sd_db_sales"
echo "  - sd_db_reservations"
echo "  - sd_db_hr"
echo "  - sd_db_config"
echo ""
echo "Contenedores REPLICA (solo lectura):"
echo "  - sd_db_auth_replica"
echo "  - sd_db_branches_replica"
echo "  - sd_db_inventory_replica"
echo "  - sd_db_sales_replica"
echo "  - sd_db_reservations_replica"
echo "  - sd_db_hr_replica"
echo "  - sd_db_config_replica"
echo ""
echo "Herramientas:"
echo "  - phpMyAdmin: http://localhost:8080"
echo ""
echo "Credenciales:"
echo "  - Usuario root: root / 3312"
echo "  - Usuario app: rmichelv / usuario123"
echo ""
echo "Para configurar la replicación, ejecuta:"
echo "  ./setup-replication.sh"
echo ""
