#!/bin/bash

# watchdog.sh - Demonio de Failover Automático para MySQL
# Monitoriza el estado del contenedor Master y promueve la Réplica si falla.

MASTER_CONTAINER="sd_db_auth"
REPLICA_CONTAINER="sd_db_auth_replica"
CHECK_INTERVAL=5
MAX_RETRIES=3

echo "[Watchdog] Iniciando monitor de alta disponibilidad..."

while true; do
    # Verificar si el Master responde al ping de mysqladmin
    if docker exec $MASTER_CONTAINER mysqladmin ping -uroot -p3312 --silent; then
        echo "[$(date +%T)] [Watchdog] Status Master: ONLINE"
    else
        echo "[$(date +%T)] [Watchdog] WARNING: Master no responde."
        
        # Lógica de reintentos
        for i in $(seq 1 $MAX_RETRIES); do
            sleep 1
            if docker exec $MASTER_CONTAINER mysqladmin ping -uroot -p3312 --silent; then
                echo "[$(date +%T)] [Watchdog] Master recuperado."
                continue 2
            fi
        done

        echo "[$(date +%T)] [Watchdog] CRITICAL: Master confirmado DOWN."
        echo "[$(date +%T)] [Watchdog] --- INICIANDO FAILOVER AUTOMÁTICO ---"

        # 1. Detener replicación en la réplica
        echo "[Failover] Deteniendo slave thread..."
        docker exec $REPLICA_CONTAINER mysql -uroot -p3312 -e "STOP REPLICA;"

        # 2. Resetear configuración de master (desvincular)
        echo "[Failover] Desvinculando de Master caído..."
        docker exec $REPLICA_CONTAINER mysql -uroot -p3312 -e "RESET REPLICA ALL;"

        # 3. Desactivar modo solo lectura
        echo "[Failover] Promoviendo a PRIMARY (Read-Write)..."
        docker exec $REPLICA_CONTAINER mysql -uroot -p3312 -e "SET GLOBAL read_only = 0;"

        # 4. (Opcional) Actualizar DNS o Discovery Service
        # En este entorno Docker, podríamos actualizar un alias de red o reiniciar el API con nueva config
        echo "[Failover] Notificando a servicios dependientes..."
        
        echo "[$(date +%T)] [Watchdog] SISTEMA RECUPERADO. Nuevo Master activo."
        exit 0
    fi
    sleep $CHECK_INTERVAL
done