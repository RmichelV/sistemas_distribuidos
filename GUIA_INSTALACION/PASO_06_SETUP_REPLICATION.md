# PASO 6: Configurar Replicación con Script Automatizado

## Objetivo
Ejecutar el script `setup-replication.sh` que configura automáticamente la replicación MASTER → REPLICA para los 7 servicios.

## ¿Qué hace este script?

Para cada servicio, el script:
1. **Crea el usuario de replicación** en el MASTER
2. **Obtiene la posición del binlog** del MASTER
3. **Configura la REPLICA** para leer desde el MASTER
4. **Activa `super_read_only`** en la REPLICA
5. **Verifica el estado** de la replicación

## Prerrequisitos

✅ PASO 1 completado (red `sd_network` creada)
✅ PASO 2 completado (archivos `master.cnf`)
✅ PASO 3 completado (contenedores MASTER corriendo)
✅ PASO 4 completado (archivos `replica.cnf`)
✅ PASO 5 completado (contenedores REPLICA corriendo)

Verificación rápida:
```bash
docker ps | grep sd_db | wc -l
# Debe mostrar: 14 (7 MASTER + 7 REPLICA)
```

## Archivo: `setup-replication.sh`

**Ubicación**: `SIS/setup-replication.sh`

```bash
#!/bin/bash

echo "========================================="
echo "Configurando Replicación Master-Replica"
echo "========================================="
echo ""

# Colores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Array con información de los servicios
declare -a SERVICES=(
    "sd_db_auth:sd_db_auth_replica:auth_db"
    "sd_db_branches:sd_db_branches_replica:branches_db"
    "sd_db_inventory:sd_db_inventory_replica:inventory_db"
    "sd_db_sales:sd_db_sales_replica:sales_db"
    "sd_db_reservations:sd_db_reservations_replica:reservations_db"
    "sd_db_hr:sd_db_hr_replica:hr_db"
    "sd_db_config:sd_db_config_replica:config_db"
)

# Función para configurar replicación de un servicio
setup_service_replication() {
    local MASTER=$1
    local REPLICA=$2
    local DATABASE=$3
    
    echo -e "${YELLOW}Configurando: $MASTER → $REPLICA${NC}"
    
    # 1. Crear usuario de replicación en el MASTER
    echo "  [1/5] Creando usuario 'replicator' en MASTER..."
    docker exec $MASTER mysql -uroot -p3312 -e "
        CREATE USER IF NOT EXISTS 'replicator'@'%' IDENTIFIED WITH mysql_native_password BY 'replicator_password';
        GRANT REPLICATION SLAVE ON *.* TO 'replicator'@'%';
        FLUSH PRIVILEGES;
    " 2>/dev/null
    
    # 2. Obtener posición del binlog del MASTER
    echo "  [2/5] Obteniendo posición del binlog..."
    MASTER_STATUS=$(docker exec $MASTER mysql -uroot -p3312 -e "SHOW MASTER STATUS\G" 2>/dev/null)
    MASTER_LOG_FILE=$(echo "$MASTER_STATUS" | grep "File:" | awk '{print $2}')
    MASTER_LOG_POS=$(echo "$MASTER_STATUS" | grep "Position:" | awk '{print $2}')
    
    echo "      Binlog: $MASTER_LOG_FILE"
    echo "      Posición: $MASTER_LOG_POS"
    
    # 3. Configurar la REPLICA
    echo "  [3/5] Configurando REPLICA..."
    docker exec $REPLICA mysql -uroot -p3312 -e "
        STOP SLAVE;
        RESET SLAVE ALL;
        CHANGE MASTER TO
            MASTER_HOST='$MASTER',
            MASTER_USER='replicator',
            MASTER_PASSWORD='replicator_password',
            MASTER_LOG_FILE='$MASTER_LOG_FILE',
            MASTER_LOG_POS=$MASTER_LOG_POS;
        START SLAVE;
    " 2>/dev/null
    
    # Esperar un momento para que la replicación se inicie
    sleep 2
    
    # 4. Activar super_read_only en la REPLICA
    echo "  [4/5] Activando super_read_only..."
    docker exec $REPLICA mysql -uroot -p3312 -e "
        SET GLOBAL read_only = 1;
        SET GLOBAL super_read_only = 1;
    " 2>/dev/null
    
    # 5. Verificar estado de replicación
    echo "  [5/5] Verificando estado de replicación..."
    SLAVE_STATUS=$(docker exec $REPLICA mysql -uroot -p3312 -e "SHOW SLAVE STATUS\G" 2>/dev/null)
    IO_RUNNING=$(echo "$SLAVE_STATUS" | grep "Slave_IO_Running:" | awk '{print $2}')
    SQL_RUNNING=$(echo "$SLAVE_STATUS" | grep "Slave_SQL_Running:" | awk '{print $2}')
    
    if [ "$IO_RUNNING" == "Yes" ] && [ "$SQL_RUNNING" == "Yes" ]; then
        echo -e "  ${GREEN}✓ Replicación configurada correctamente${NC}"
    else
        echo -e "  ${RED}✗ Error en la replicación${NC}"
        echo "    Slave_IO_Running: $IO_RUNNING"
        echo "    Slave_SQL_Running: $SQL_RUNNING"
        
        # Mostrar último error si existe
        LAST_ERROR=$(echo "$SLAVE_STATUS" | grep "Last_Error:" | cut -d: -f2-)
        if [ ! -z "$LAST_ERROR" ]; then
            echo "    Error: $LAST_ERROR"
        fi
    fi
    
    echo ""
}

# Configurar replicación para todos los servicios
for SERVICE in "${SERVICES[@]}"; do
    IFS=':' read -r MASTER REPLICA DATABASE <<< "$SERVICE"
    setup_service_replication "$MASTER" "$REPLICA" "$DATABASE"
done

echo "========================================="
echo "Resumen de Configuración"
echo "========================================="
echo ""

# Mostrar estado final de todas las réplicas
for SERVICE in "${SERVICES[@]}"; do
    IFS=':' read -r MASTER REPLICA DATABASE <<< "$SERVICE"
    
    SLAVE_STATUS=$(docker exec $REPLICA mysql -uroot -p3312 -e "SHOW SLAVE STATUS\G" 2>/dev/null)
    IO_RUNNING=$(echo "$SLAVE_STATUS" | grep "Slave_IO_Running:" | awk '{print $2}')
    SQL_RUNNING=$(echo "$SLAVE_STATUS" | grep "Slave_SQL_Running:" | awk '{print $2}')
    
    printf "%-30s " "$REPLICA:"
    if [ "$IO_RUNNING" == "Yes" ] && [ "$SQL_RUNNING" == "Yes" ]; then
        echo -e "${GREEN}✓ OK${NC}"
    else
        echo -e "${RED}✗ ERROR${NC}"
    fi
done

echo ""
echo "========================================="
echo "Para verificar el estado de una réplica:"
echo "  docker exec <nombre_replica> mysql -uroot -p3312 -e 'SHOW SLAVE STATUS\G'"
echo ""
echo "Para ver si super_read_only está activo:"
echo "  docker exec <nombre_replica> mysql -uroot -p3312 -e 'SHOW VARIABLES LIKE \"%read_only%\";'"
echo "========================================="
```

## Explicación Detallada

### 1. Crear Usuario de Replicación

```sql
CREATE USER IF NOT EXISTS 'replicator'@'%' 
    IDENTIFIED WITH mysql_native_password BY 'replicator_password';
```

- **Usuario**: `replicator`
- **Host**: `%` (cualquier host en la red)
- **Plugin**: `mysql_native_password` (evita problemas de SSL)
- **Contraseña**: `replicator_password`

**¿Por qué `mysql_native_password`?**
- El plugin predeterminado `caching_sha2_password` requiere SSL
- `mysql_native_password` funciona sin SSL en redes internas seguras

```sql
GRANT REPLICATION SLAVE ON *.* TO 'replicator'@'%';
```

- **Privilegio**: `REPLICATION SLAVE` (permite leer binlog del MASTER)
- **Alcance**: `*.*` (todas las bases de datos)

---

### 2. Obtener Posición del Binlog

```bash
MASTER_STATUS=$(docker exec $MASTER mysql -uroot -p3312 -e "SHOW MASTER STATUS\G")
MASTER_LOG_FILE=$(echo "$MASTER_STATUS" | grep "File:" | awk '{print $2}')
MASTER_LOG_POS=$(echo "$MASTER_STATUS" | grep "Position:" | awk '{print $2}')
```

**Comando**: `SHOW MASTER STATUS`

**Salida ejemplo**:
```
*************************** 1. row ***************************
             File: mysql-bin.000003
         Position: 157
     Binlog_Do_DB: auth_db
 Binlog_Ignore_DB: 
```

**Extracción**:
- `MASTER_LOG_FILE`: `mysql-bin.000003`
- `MASTER_LOG_POS`: `157`

Estos valores indican **desde dónde** la REPLICA debe empezar a leer el binlog.

---

### 3. Configurar la REPLICA

```sql
STOP SLAVE;
RESET SLAVE ALL;
```

- **STOP SLAVE**: Detiene cualquier replicación en curso
- **RESET SLAVE ALL**: Limpia configuración de replicación anterior

```sql
CHANGE MASTER TO
    MASTER_HOST='sd_db_auth',
    MASTER_USER='replicator',
    MASTER_PASSWORD='replicator_password',
    MASTER_LOG_FILE='mysql-bin.000003',
    MASTER_LOG_POS=157;
```

Configura:
- **MASTER_HOST**: Hostname del MASTER (dentro de `sd_network`)
- **MASTER_USER**: Usuario con privilegios de replicación
- **MASTER_PASSWORD**: Contraseña del usuario
- **MASTER_LOG_FILE**: Archivo binlog inicial
- **MASTER_LOG_POS**: Posición inicial en el binlog

```sql
START SLAVE;
```

Inicia el proceso de replicación.

---

### 4. Activar `super_read_only`

```sql
SET GLOBAL read_only = 1;
SET GLOBAL super_read_only = 1;
```

**Diferencia entre `read_only` y `super_read_only`**:

| Modo | Bloquea usuarios normales | Bloquea SUPER usuarios |
|------|---------------------------|------------------------|
| `read_only = 1` | ✅ Sí | ❌ No |
| `super_read_only = 1` | ✅ Sí | ✅ Sí (incluso root) |

**¿Por qué `super_read_only`?**
Previene **cualquier** escritura en la REPLICA, incluso de usuarios con privilegios SUPER.

---

### 5. Verificar Estado

```sql
SHOW SLAVE STATUS\G
```

**Campos importantes**:

| Campo | Valor Esperado | Descripción |
|-------|----------------|-------------|
| `Slave_IO_Running` | **Yes** | Thread I/O está leyendo binlog del MASTER |
| `Slave_SQL_Running` | **Yes** | Thread SQL está aplicando cambios |
| `Seconds_Behind_Master` | 0 o bajo | Retraso en segundos respecto al MASTER |
| `Last_Error` | (vacío) | Último error encontrado |
| `Master_Host` | `sd_db_auth` | Hostname del MASTER |
| `Master_Log_File` | `mysql-bin.000003` | Archivo binlog actual |
| `Relay_Log_File` | `relay-bin.000002` | Archivo relay log actual |

**Estado OK**:
```
Slave_IO_Running: Yes
Slave_SQL_Running: Yes
Seconds_Behind_Master: 0
Last_Error: 
```

**Estado ERROR**:
```
Slave_IO_Running: Connecting
Slave_SQL_Running: Yes
Last_Error: error connecting to master 'replicator@sd_db_auth:3306'
```

## Ejecutar el Script

### 1. Dar permisos de ejecución
```bash
chmod +x setup-replication.sh
```

### 2. Ejecutar
```bash
./setup-replication.sh
```

### 3. Salida esperada

```
=========================================
Configurando Replicación Master-Replica
=========================================

Configurando: sd_db_auth → sd_db_auth_replica
  [1/5] Creando usuario 'replicator' en MASTER...
  [2/5] Obteniendo posición del binlog...
      Binlog: mysql-bin.000003
      Posición: 157
  [3/5] Configurando REPLICA...
  [4/5] Activando super_read_only...
  [5/5] Verificando estado de replicación...
  ✓ Replicación configurada correctamente

Configurando: sd_db_branches → sd_db_branches_replica
  ...
  ✓ Replicación configurada correctamente

... (repetir para los 7 servicios)

=========================================
Resumen de Configuración
=========================================

sd_db_auth_replica:            ✓ OK
sd_db_branches_replica:        ✓ OK
sd_db_inventory_replica:       ✓ OK
sd_db_sales_replica:           ✓ OK
sd_db_reservations_replica:    ✓ OK
sd_db_hr_replica:              ✓ OK
sd_db_config_replica:          ✓ OK

=========================================
Para verificar el estado de una réplica:
  docker exec <nombre_replica> mysql -uroot -p3312 -e 'SHOW SLAVE STATUS\G'
...
=========================================
```

## Verificación Manual

### Verificar estado de una REPLICA específica
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "SHOW SLAVE STATUS\G"
```

**Buscar estas líneas**:
```
Slave_IO_Running: Yes
Slave_SQL_Running: Yes
Seconds_Behind_Master: 0
```

---

### Verificar `super_read_only` activo
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "SHOW VARIABLES LIKE '%read_only%';"
```

**Salida esperada**:
```
+------------------+-------+
| Variable_name    | Value |
+------------------+-------+
| read_only        | ON    |
| super_read_only  | ON    |
+------------------+-------+
```

---

### Probar que la REPLICA rechaza escrituras
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 auth_db -e "INSERT INTO roles (name) VALUES ('test');"
```

**Salida esperada (ERROR)**:
```
ERROR 1290 (HY000): The MySQL server is running with the --super-read-only option so it cannot execute this statement
```

✅ **Esto es correcto**. La REPLICA debe rechazar escrituras.

## Solución de Problemas

### Error: "Access denied for user 'replicator'"
**Causa**: El usuario no fue creado correctamente o tiene mal la contraseña.

**Solución**:
```bash
docker exec sd_db_auth mysql -uroot -p3312 -e "
    DROP USER IF EXISTS 'replicator'@'%';
    CREATE USER 'replicator'@'%' IDENTIFIED WITH mysql_native_password BY 'replicator_password';
    GRANT REPLICATION SLAVE ON *.* TO 'replicator'@'%';
    FLUSH PRIVILEGES;
"
```

---

### Error: "Slave_IO_Running: Connecting" (nunca cambia a Yes)
**Causa**: La REPLICA no puede conectarse al MASTER.

**Verificar**:
1. ¿El MASTER está corriendo?
   ```bash
   docker ps | grep sd_db_auth
   ```

2. ¿Ambos están en la misma red?
   ```bash
   docker network inspect sd_network
   ```

3. ¿La REPLICA puede hacer ping al MASTER?
   ```bash
   docker exec sd_db_auth_replica ping -c 3 sd_db_auth
   ```

---

### Error: "Slave_SQL_Running: No"
**Causa**: Error al aplicar eventos del binlog.

**Ver el error**:
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "SHOW SLAVE STATUS\G" | grep -A 5 "Last_SQL_Error"
```

**Solución común**: Resetear la replicación
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "
    STOP SLAVE;
    RESET SLAVE ALL;
"
# Luego volver a ejecutar setup-replication.sh
```

---

### Replicación funcionaba, ahora está detenida
**Verificar segundos de retraso**:
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "SHOW SLAVE STATUS\G" | grep "Seconds_Behind_Master"
```

**Si es NULL**: Replicación detenida

**Reiniciar**:
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "START SLAVE;"
```

## ¿Qué sigue?
Ahora que la replicación está configurada, debes crear las tablas en los MASTER. Continúa con el [PASO 7: Crear Tablas](PASO_07_CREAR_TABLAS.md).
