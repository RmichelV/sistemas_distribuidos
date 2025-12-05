# PASO 8: Verificar Replicación

## Objetivo
Comprobar que la replicación Master-Slave funciona correctamente en los 7 servicios.

## Tipos de Verificación

1. **Estado de Replicación**: Threads I/O y SQL activos
2. **Creación de Tablas**: `CREATE TABLE` se replica
3. **Inserción de Datos**: `INSERT` se replica
4. **Actualización**: `UPDATE` se replica
5. **Eliminación**: `DELETE` se replica
6. **Modo Lectura**: La REPLICA rechaza escrituras

## 1. Verificar Estado de Replicación

### Comando General
```bash
docker exec <nombre_replica> mysql -uroot -p3312 -e "SHOW SLAVE STATUS\G"
```

### Campos Críticos

```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "SHOW SLAVE STATUS\G" | grep -E "(Slave_IO_Running|Slave_SQL_Running|Seconds_Behind|Last_Error)"
```

**Salida esperada**:
```
             Slave_IO_Running: Yes
            Slave_SQL_Running: Yes
          Seconds_Behind_Master: 0
                   Last_Error: 
```

✅ **Estado OK**:
- `Slave_IO_Running: Yes` → Thread I/O leyendo binlog del MASTER
- `Slave_SQL_Running: Yes` → Thread SQL aplicando cambios
- `Seconds_Behind_Master: 0` → Sin retraso
- `Last_Error:` (vacío) → Sin errores

❌ **Estados ERROR**:

| Estado | Causa | Solución |
|--------|-------|----------|
| `Slave_IO_Running: Connecting` | No puede conectar al MASTER | Verificar red, firewall, credenciales |
| `Slave_SQL_Running: No` | Error aplicando cambios | Ver `Last_SQL_Error`, revisar logs |
| `Seconds_Behind_Master: NULL` | Replicación detenida | `START SLAVE;` |
| `Last_Error: ...` | Error específico | Leer el mensaje, corregir el problema |

---

### Verificar Estado de Todos los Servicios

**Script rápido**:
```bash
#!/bin/bash

echo "Estado de Replicación"
echo "====================="

for REPLICA in sd_db_auth_replica sd_db_branches_replica sd_db_inventory_replica sd_db_sales_replica sd_db_reservations_replica sd_db_hr_replica sd_db_config_replica; do
    echo -n "$REPLICA: "
    STATUS=$(docker exec $REPLICA mysql -uroot -p3312 -e "SHOW SLAVE STATUS\G" 2>/dev/null)
    IO=$(echo "$STATUS" | grep "Slave_IO_Running:" | awk '{print $2}')
    SQL=$(echo "$STATUS" | grep "Slave_SQL_Running:" | awk '{print $2}')
    
    if [ "$IO" == "Yes" ] && [ "$SQL" == "Yes" ]; then
        echo "✓ OK"
    else
        echo "✗ ERROR (IO: $IO, SQL: $SQL)"
    fi
done
```

**Salida esperada**:
```
Estado de Replicación
=====================
sd_db_auth_replica: ✓ OK
sd_db_branches_replica: ✓ OK
sd_db_inventory_replica: ✓ OK
sd_db_sales_replica: ✓ OK
sd_db_reservations_replica: ✓ OK
sd_db_hr_replica: ✓ OK
sd_db_config_replica: ✓ OK
```

## 2. Verificar Replicación de CREATE TABLE

### Prueba: Crear tabla en MASTER
```bash
docker exec sd_db_auth mysql -uroot -p3312 auth_db <<'EOF'
CREATE TABLE IF NOT EXISTS test_table (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
EOF
```

### Verificar en MASTER
```bash
docker exec sd_db_auth mysql -uroot -p3312 auth_db -e "SHOW TABLES LIKE 'test_table';"
```

**Salida esperada**:
```
+-------------------------------+
| Tables_in_auth_db (test_table)|
+-------------------------------+
| test_table                    |
+-------------------------------+
```

### Verificar en REPLICA
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 auth_db -e "SHOW TABLES LIKE 'test_table';"
```

**Debe mostrar lo mismo**:
```
+-------------------------------+
| Tables_in_auth_db (test_table)|
+-------------------------------+
| test_table                    |
+-------------------------------+
```

✅ **La tabla se replicó correctamente**.

### Limpiar
```bash
docker exec sd_db_auth mysql -uroot -p3312 auth_db -e "DROP TABLE IF EXISTS test_table;"
```

Verifica que también se eliminó en la REPLICA:
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 auth_db -e "SHOW TABLES LIKE 'test_table';"
# Debe mostrar: Empty set
```

## 3. Verificar Replicación de INSERT

### Insertar datos en MASTER
```bash
docker exec sd_db_auth mysql -uroot -p3312 auth_db <<'EOF'
INSERT INTO roles (name) VALUES ('Test Role 1'), ('Test Role 2'), ('Test Role 3');
EOF
```

### Verificar en MASTER
```bash
docker exec sd_db_auth mysql -uroot -p3312 auth_db -e "SELECT * FROM roles WHERE name LIKE 'Test%';"
```

**Salida esperada**:
```
+----+-------------+---------------------+---------------------+
| id | name        | created_at          | updated_at          |
+----+-------------+---------------------+---------------------+
|  6 | Test Role 1 | 2024-01-15 10:00:00 | 2024-01-15 10:00:00 |
|  7 | Test Role 2 | 2024-01-15 10:00:00 | 2024-01-15 10:00:00 |
|  8 | Test Role 3 | 2024-01-15 10:00:00 | 2024-01-15 10:00:00 |
+----+-------------+---------------------+---------------------+
```

### Verificar en REPLICA
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 auth_db -e "SELECT * FROM roles WHERE name LIKE 'Test%';"
```

**Debe mostrar los mismos datos con los mismos IDs**.

✅ **Los datos se replicaron correctamente**.

### Limpiar
```bash
docker exec sd_db_auth mysql -uroot -p3312 auth_db -e "DELETE FROM roles WHERE name LIKE 'Test%';"
```

Verifica que también se eliminaron en la REPLICA.

## 4. Verificar Replicación de UPDATE

### Actualizar datos en MASTER
```bash
# Primero insertar un registro de prueba
docker exec sd_db_auth mysql -uroot -p3312 auth_db -e "INSERT INTO roles (name) VALUES ('Role to Update');"

# Obtener el ID del registro insertado
ROLE_ID=$(docker exec sd_db_auth mysql -uroot -p3312 auth_db -Ne "SELECT id FROM roles WHERE name='Role to Update';")

# Actualizar el registro
docker exec sd_db_auth mysql -uroot -p3312 auth_db -e "UPDATE roles SET name='Updated Role' WHERE id=$ROLE_ID;"
```

### Verificar en MASTER
```bash
docker exec sd_db_auth mysql -uroot -p3312 auth_db -e "SELECT * FROM roles WHERE id=$ROLE_ID;"
```

**Salida esperada**:
```
+----+--------------+---------------------+---------------------+
| id | name         | created_at          | updated_at          |
+----+--------------+---------------------+---------------------+
|  X | Updated Role | 2024-01-15 10:00:00 | 2024-01-15 10:05:00 |
+----+--------------+---------------------+---------------------+
```

### Verificar en REPLICA
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 auth_db -e "SELECT * FROM roles WHERE id=$ROLE_ID;"
```

**Debe mostrar el mismo nombre actualizado**: `Updated Role`.

✅ **La actualización se replicó correctamente**.

### Limpiar
```bash
docker exec sd_db_auth mysql -uroot -p3312 auth_db -e "DELETE FROM roles WHERE id=$ROLE_ID;"
```

## 5. Verificar Replicación de DELETE

### Eliminar datos en MASTER
```bash
# Insertar registros de prueba
docker exec sd_db_auth mysql -uroot -p3312 auth_db <<'EOF'
INSERT INTO roles (name) VALUES ('Delete Test 1'), ('Delete Test 2');
EOF

# Eliminar uno de ellos
docker exec sd_db_auth mysql -uroot -p3312 auth_db -e "DELETE FROM roles WHERE name='Delete Test 1';"
```

### Verificar en MASTER
```bash
docker exec sd_db_auth mysql -uroot -p3312 auth_db -e "SELECT * FROM roles WHERE name LIKE 'Delete%';"
```

**Salida esperada**: Solo `Delete Test 2` debe aparecer.

### Verificar en REPLICA
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 auth_db -e "SELECT * FROM roles WHERE name LIKE 'Delete%';"
```

**Debe mostrar solo** `Delete Test 2`.

✅ **La eliminación se replicó correctamente**.

### Limpiar
```bash
docker exec sd_db_auth mysql -uroot -p3312 auth_db -e "DELETE FROM roles WHERE name LIKE 'Delete%';"
```

## 6. Verificar Modo Solo Lectura en REPLICA

### Intentar INSERT en REPLICA (debe fallar)
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 auth_db -e "INSERT INTO roles (name) VALUES ('Should Fail');"
```

**Salida esperada (ERROR)**:
```
ERROR 1290 (HY000): The MySQL server is running with the --super-read-only option so it cannot execute this statement
```

✅ **Esto es CORRECTO**. La REPLICA rechaza escrituras.

---

### Intentar UPDATE en REPLICA (debe fallar)
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 auth_db -e "UPDATE roles SET name='Fail' WHERE id=1;"
```

**Salida esperada (ERROR)**:
```
ERROR 1290 (HY000): The MySQL server is running with the --super-read-only option so it cannot execute this statement
```

✅ **Correcto**. La REPLICA rechaza actualizaciones.

---

### Intentar DELETE en REPLICA (debe fallar)
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 auth_db -e "DELETE FROM roles WHERE id=1;"
```

**Salida esperada (ERROR)**:
```
ERROR 1290 (HY000): The MySQL server is running with the --super-read-only option so it cannot execute this statement
```

✅ **Correcto**. La REPLICA rechaza eliminaciones.

---

### Verificar que `super_read_only` está activo
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

## 7. Verificar Retraso de Replicación

```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "SHOW SLAVE STATUS\G" | grep "Seconds_Behind_Master"
```

**Valores posibles**:

| Valor | Significado |
|-------|-------------|
| `0` | ✅ Sin retraso (ideal) |
| `1-5` | ⚠️ Retraso leve (normal en alta carga) |
| `> 10` | 🔴 Retraso significativo (investigar) |
| `NULL` | ❌ Replicación detenida |

**Si hay retraso alto**:
- Verificar carga del MASTER
- Verificar conexión de red
- Considerar aumentar recursos de la REPLICA

## 8. Prueba de Estrés (Opcional)

### Insertar muchos registros rápidamente
```bash
docker exec sd_db_auth mysql -uroot -p3312 auth_db <<'EOF'
INSERT INTO roles (name) 
SELECT CONCAT('Stress Test ', n) FROM (
    SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
) numbers;
EOF
```

### Verificar inmediatamente en REPLICA
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 auth_db -e "SELECT COUNT(*) FROM roles WHERE name LIKE 'Stress%';"
```

**Debe mostrar**: `10` (todos los registros replicados).

### Verificar retraso
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "SHOW SLAVE STATUS\G" | grep "Seconds_Behind_Master"
```

Si es `0`, la replicación es muy rápida.

### Limpiar
```bash
docker exec sd_db_auth mysql -uroot -p3312 auth_db -e "DELETE FROM roles WHERE name LIKE 'Stress%';"
```

## Script de Verificación Completo

```bash
#!/bin/bash

echo "========================================="
echo "Verificación Completa de Replicación"
echo "========================================="

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

# Función para verificar un servicio
verify_service() {
    local MASTER=$1
    local REPLICA=$2
    local DATABASE=$3
    
    echo ""
    echo "Verificando: $MASTER → $REPLICA"
    echo "--------------------------------------"
    
    # 1. Estado de replicación
    STATUS=$(docker exec $REPLICA mysql -uroot -p3312 -e "SHOW SLAVE STATUS\G" 2>/dev/null)
    IO=$(echo "$STATUS" | grep "Slave_IO_Running:" | awk '{print $2}')
    SQL=$(echo "$STATUS" | grep "Slave_SQL_Running:" | awk '{print $2}')
    DELAY=$(echo "$STATUS" | grep "Seconds_Behind_Master:" | awk '{print $2}')
    
    echo -n "  Estado: "
    if [ "$IO" == "Yes" ] && [ "$SQL" == "Yes" ]; then
        echo -e "${GREEN}✓ OK${NC} (Retraso: ${DELAY}s)"
    else
        echo -e "${RED}✗ ERROR${NC} (IO: $IO, SQL: $SQL)"
        return
    fi
    
    # 2. Probar INSERT
    echo -n "  INSERT: "
    docker exec $MASTER mysql -uroot -p3312 $DATABASE -e "INSERT INTO roles (name) VALUES ('Verify Test');" 2>/dev/null
    sleep 1
    RESULT=$(docker exec $REPLICA mysql -uroot -p3312 $DATABASE -Ne "SELECT COUNT(*) FROM roles WHERE name='Verify Test';" 2>/dev/null)
    if [ "$RESULT" == "1" ]; then
        echo -e "${GREEN}✓ Replicado${NC}"
        docker exec $MASTER mysql -uroot -p3312 $DATABASE -e "DELETE FROM roles WHERE name='Verify Test';" 2>/dev/null
    else
        echo -e "${RED}✗ No replicado${NC}"
    fi
    
    # 3. Probar READ-ONLY
    echo -n "  Read-Only: "
    ERROR=$(docker exec $REPLICA mysql -uroot -p3312 $DATABASE -e "INSERT INTO roles (name) VALUES ('Should Fail');" 2>&1)
    if echo "$ERROR" | grep -q "super-read-only"; then
        echo -e "${GREEN}✓ Activo${NC}"
    else
        echo -e "${RED}✗ NO activo${NC}"
    fi
}

# Verificar solo auth (puedes agregar los otros)
verify_service "sd_db_auth" "sd_db_auth_replica" "auth_db"

echo ""
echo "========================================="
echo "Verificación completada"
echo "========================================="
```

## Solución de Problemas

### Replicación no funciona después de reiniciar contenedores
```bash
# Reiniciar replicación
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "STOP SLAVE; START SLAVE;"

# Verificar estado
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "SHOW SLAVE STATUS\G" | grep -E "(Running|Error)"
```

---

### Datos inconsistentes entre MASTER y REPLICA
```bash
# Detener replicación
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "STOP SLAVE;"

# Limpiar REPLICA
docker exec sd_db_auth_replica mysql -uroot -p3312 auth_db -e "DROP DATABASE auth_db; CREATE DATABASE auth_db;"

# Reconfigurar replicación
./setup-replication.sh
```

---

### Error: "Duplicate entry" en la REPLICA
**Causa**: La REPLICA tiene datos que el MASTER está intentando replicar.

**Solución**: Limpiar la REPLICA y reconfigurar.

## ¿Qué sigue?
Si todas las verificaciones pasaron, tu sistema de replicación está funcionando correctamente. Continúa con el [PASO 9: Configurar phpMyAdmin](PASO_09_PHPMYADMIN.md).
