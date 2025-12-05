#!/bin/bash

echo "========================================="
echo "Configurando replicación MySQL Master-Slave"
echo "========================================="

cd "$(dirname "$0")"

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Función para configurar replicación
setup_replication() {
    local master_container=$1
    local replica_container=$2
    local db_name=$3
    
    echo ""
    echo -e "${YELLOW}Configurando: $master_container -> $replica_container${NC}"
    
    # 1. Crear usuario de replicación en el MASTER
    echo "  1. Creando usuario de replicación en MASTER..."
    docker exec -i $master_container mysql -uroot -p3312 <<EOF
CREATE USER IF NOT EXISTS 'replicator'@'%' IDENTIFIED WITH mysql_native_password BY 'replicator_password';
GRANT REPLICATION SLAVE ON *.* TO 'replicator'@'%';
FLUSH PRIVILEGES;
EOF
    
    # 2. Obtener posición del binlog del MASTER
    echo "  2. Obteniendo posición del binlog..."
    MASTER_STATUS=$(docker exec -i $master_container mysql -uroot -p3312 -e "SHOW MASTER STATUS\G")
    MASTER_LOG_FILE=$(echo "$MASTER_STATUS" | grep "File:" | awk '{print $2}')
    MASTER_LOG_POS=$(echo "$MASTER_STATUS" | grep "Position:" | awk '{print $2}')
    
    echo "     - Archivo: $MASTER_LOG_FILE"
    echo "     - Posición: $MASTER_LOG_POS"
    
    # 3. Configurar REPLICA
    echo "  3. Configurando REPLICA..."
    docker exec -i $replica_container mysql -uroot -p3312 <<EOF
STOP SLAVE;
CHANGE MASTER TO
    MASTER_HOST='$master_container',
    MASTER_USER='replicator',
    MASTER_PASSWORD='replicator_password',
    MASTER_LOG_FILE='$MASTER_LOG_FILE',
    MASTER_LOG_POS=$MASTER_LOG_POS;
START SLAVE;
SET GLOBAL read_only = 1;
SET GLOBAL super_read_only = 1;
EOF
    
    # 4. Verificar estado de la replicación
    echo "  4. Verificando estado..."
    sleep 2
    SLAVE_STATUS=$(docker exec -i $replica_container mysql -uroot -p3312 -e "SHOW SLAVE STATUS\G")
    IO_RUNNING=$(echo "$SLAVE_STATUS" | grep "Slave_IO_Running:" | awk '{print $2}')
    SQL_RUNNING=$(echo "$SLAVE_STATUS" | grep "Slave_SQL_Running:" | awk '{print $2}')
    
    if [ "$IO_RUNNING" == "Yes" ] && [ "$SQL_RUNNING" == "Yes" ]; then
        echo -e "  ${GREEN}✓ Replicación configurada correctamente${NC}"
    else
        echo -e "  ${RED}✗ Error en la replicación${NC}"
        echo "     - IO Thread: $IO_RUNNING"
        echo "     - SQL Thread: $SQL_RUNNING"
    fi
}

echo ""
echo "Iniciando configuración de replicación para los 7 servicios..."
echo ""

# Configurar cada servicio
setup_replication "sd_db_auth" "sd_db_auth_replica" "auth_db"
setup_replication "sd_db_branches" "sd_db_branches_replica" "branches_db"
setup_replication "sd_db_inventory" "sd_db_inventory_replica" "inventory_db"
setup_replication "sd_db_sales" "sd_db_sales_replica" "sales_db"
setup_replication "sd_db_reservations" "sd_db_reservations_replica" "reservations_db"
setup_replication "sd_db_hr" "sd_db_hr_replica" "hr_db"
setup_replication "sd_db_config" "sd_db_config_replica" "config_db"

echo ""
echo "========================================="
echo -e "${GREEN}✓ Configuración completada${NC}"
echo "========================================="
echo ""
echo "Para verificar el estado de replicación:"
echo "  docker exec -it sd_db_auth_replica mysql -uroot -p3312 -e 'SHOW SLAVE STATUS\\G'"
echo ""
echo "Para probar la replicación:"
echo "  1. Inserta datos en el MASTER:"
echo "     docker exec -it sd_db_auth mysql -uroot -p3312 -e 'USE auth_db; INSERT INTO roles (name) VALUES (\"Test Role\");'"
echo ""
echo "  2. Verifica en la REPLICA:"
echo "     docker exec -it sd_db_auth_replica mysql -uroot -p3312 -e 'USE auth_db; SELECT * FROM roles;'"
echo ""
