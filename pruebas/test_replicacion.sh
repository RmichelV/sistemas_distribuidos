#!/bin/bash

# Script de validación de replicación MySQL
# Fuente original conceptual: Manual de referencia de MySQL 8.0 (Replication Status)
# Adaptación: Se conecta a los contenedores Docker específicos del proyecto SIS para verificar el estado del esclavo.

MASTER_CONTAINER="sd_db_auth"
REPLICA_CONTAINER="sd_db_auth_replica"
DB_USER="root"
DB_PASS="3312"
DB_NAME="auth_db"

echo "==================================================="
echo " VALIDACIÓN DE ALTA DISPONIBILIDAD (Replicación)"
echo "==================================================="
echo "Master: $MASTER_CONTAINER"
echo "Replica: $REPLICA_CONTAINER"
echo "---------------------------------------------------"

# 1. Insertar dato en Master
TEST_VALUE="Test_$(date +%s)"
echo "[1/3] Insertando dato de prueba en MASTER ($TEST_VALUE)..."
docker exec $MASTER_CONTAINER mysql -u$DB_USER -p$DB_PASS -e "USE $DB_NAME; CREATE TABLE IF NOT EXISTS replication_test (id INT AUTO_INCREMENT PRIMARY KEY, val VARCHAR(255)); INSERT INTO replication_test (val) VALUES ('$TEST_VALUE');" 2>/dev/null

if [ $? -eq 0 ]; then
    echo "✅ Inserción exitosa en Master."
else
    echo "❌ Error al insertar en Master."
    exit 1
fi

# 2. Esperar propagación
echo "[2/3] Esperando replicación (2 segundos)..."
sleep 2

# 3. Leer dato en Replica
echo "[3/3] Verificando dato en REPLICA..."
RESULT=$(docker exec $REPLICA_CONTAINER mysql -u$DB_USER -p$DB_PASS -N -e "USE $DB_NAME; SELECT val FROM replication_test WHERE val='$TEST_VALUE';" 2>/dev/null)

if [ "$RESULT" == "$TEST_VALUE" ]; then
    echo "✅ ÉXITO: El dato '$TEST_VALUE' fue encontrado en la réplica."
    echo "   La replicación está funcionando correctamente."
else
    echo "❌ FALLO: El dato no se encontró en la réplica."
fi

echo "---------------------------------------------------"
echo "Estado detallado del proceso de replicación:"
docker exec $REPLICA_CONTAINER mysql -u$DB_USER -p$DB_PASS -e "SHOW REPLICA STATUS\G" 2>/dev/null | grep -E "Replica_IO_Running|Replica_SQL_Running|Seconds_Behind_Master|Last_Error"
echo "==================================================="
