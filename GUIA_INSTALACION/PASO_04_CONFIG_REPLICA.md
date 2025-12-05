# PASO 4: Archivos de Configuración REPLICA

## Objetivo
Crear archivos `replica.cnf` para configurar cada servidor MySQL como REPLICA (esclavo) de replicación.

## Diferencias con MASTER

| Aspecto | MASTER | REPLICA |
|---------|--------|---------|
| **server-id** | 1-7 | 11-17 (diferente del master) |
| **log_bin** | ✅ Habilitado | ❌ No necesario |
| **relay-log** | ❌ No necesario | ✅ Habilitado |
| **binlog_do_db** | ✅ Especifica BD | ❌ No necesario |
| **replicate-do-db** | ❌ No necesario | ✅ Especifica BD |
| **read_only** | ❌ NO (permite escritura) | ✅ SÍ (solo lectura) |

## ¿Qué hace este archivo?
Configura MySQL para:
1. **Habilitar el relay log**: Recibe los cambios del MASTER
2. **Asignar un server-id único**: Diferente del MASTER (usamos +10)
3. **Especificar qué BD replicar**: Solo cambios en una BD específica
4. **Modo solo lectura**: Se configura después vía SQL (no en este archivo)

## ¿Por qué NO ponemos read_only aquí?
Si `read_only=1` está en el archivo de configuración, MySQL no permite inicializar el contenedor. Por eso lo activamos DESPUÉS con el script `setup-replication.sh`.

## Estructura de Carpetas

```
SIS/
├── servicio_de_autenticacion_y_usuarios/
│   ├── master.cnf   ← Ya creado
│   └── replica.cnf  ← Crear aquí
├── servicio_de_sucursales/
│   ├── master.cnf
│   └── replica.cnf  ← Crear aquí
├── servicio_de_inventario/
│   ├── master.cnf
│   └── replica.cnf  ← Crear aquí
├── servicio_de_ventas/
│   ├── master.cnf
│   └── replica.cnf  ← Crear aquí
├── servicio_de_clientes_y_reservaciones/
│   ├── master.cnf
│   └── replica.cnf  ← Crear aquí
├── servicio_de_recursos_humanos/
│   ├── master.cnf
│   └── replica.cnf  ← Crear aquí
└── servicio_de_configuracion/
    ├── master.cnf
    └── replica.cnf  ← Crear aquí
```

## Archivos a Crear

### 1. Autenticación y Usuarios
**Ubicación**: `servicio_de_autenticacion_y_usuarios/replica.cnf`

```ini
[mysqld]
server-id = 11
relay-log = relay-bin
replicate-do-db = auth_db
```

**Explicación**:
- `server-id = 11`: ID único (MASTER usa 1, REPLICA usa 11)
- `relay-log = relay-bin`: Archivo donde se guardan los cambios recibidos del MASTER
- `replicate-do-db = auth_db`: Solo replica cambios en la BD `auth_db`

---

### 2. Sucursales
**Ubicación**: `servicio_de_sucursales/replica.cnf`

```ini
[mysqld]
server-id = 12
relay-log = relay-bin
replicate-do-db = branches_db
```

---

### 3. Inventario
**Ubicación**: `servicio_de_inventario/replica.cnf`

```ini
[mysqld]
server-id = 13
relay-log = relay-bin
replicate-do-db = inventory_db
```

---

### 4. Ventas
**Ubicación**: `servicio_de_ventas/replica.cnf`

```ini
[mysqld]
server-id = 14
relay-log = relay-bin
replicate-do-db = sales_db
```

---

### 5. Clientes y Reservaciones
**Ubicación**: `servicio_de_clientes_y_reservaciones/replica.cnf`

```ini
[mysqld]
server-id = 15
relay-log = relay-bin
replicate-do-db = reservations_db
```

---

### 6. Recursos Humanos
**Ubicación**: `servicio_de_recursos_humanos/replica.cnf`

```ini
[mysqld]
server-id = 16
relay-log = relay-bin
replicate-do-db = hr_db
```

---

### 7. Configuración
**Ubicación**: `servicio_de_configuracion/replica.cnf`

```ini
[mysqld]
server-id = 17
relay-log = relay-bin
replicate-do-db = config_db
```

## Tabla de Server IDs

| Servicio | MASTER server-id | REPLICA server-id | Base de Datos |
|----------|------------------|-------------------|---------------|
| Autenticación | 1 | 11 | auth_db |
| Sucursales | 2 | 12 | branches_db |
| Inventario | 3 | 13 | inventory_db |
| Ventas | 4 | 14 | sales_db |
| Reservaciones | 5 | 15 | reservations_db |
| RRHH | 6 | 16 | hr_db |
| Configuración | 7 | 17 | config_db |

**Regla**: NUNCA repetir un server-id entre MASTER y REPLICA.

## Explicación de Parámetros

### server-id
- **Propósito**: Identificador único del servidor en la topología de replicación
- **Valor MASTER**: 1-7
- **Valor REPLICA**: 11-17 (sumamos 10 para diferenciarlo)
- **CRÍTICO**: Si dos servidores tienen el mismo ID, la replicación falla

### relay-log
- **Propósito**: Archivo donde la REPLICA guarda los eventos del binlog del MASTER
- **Formato**: `relay-bin.000001`, `relay-bin.000002`, etc.
- **Proceso**:
  1. MASTER escribe cambios en `mysql-bin`
  2. REPLICA descarga esos cambios
  3. REPLICA los guarda en `relay-bin`
  4. REPLICA aplica los cambios a su BD

### replicate-do-db
- **Propósito**: Especifica qué base de datos replicar
- **Comportamiento**: Solo aplica eventos que afecten a esta BD
- **Ejemplo**: Si `replicate-do-db = auth_db`, cambios en `sales_db` se ignoran

## ¿Qué NO incluimos?

### read_only
**NO lo ponemos aquí** porque causa este error:
```
[ERROR] --read-only is set on a fresh install, cannot proceed
```

**Solución**: Lo activamos DESPUÉS con SQL:
```sql
SET GLOBAL read_only = 1;
SET GLOBAL super_read_only = 1;
```

Esto se hace automáticamente en el script `setup-replication.sh` (PASO 6).

### log_bin
Las REPLICAS **NO necesitan binlog** (a menos que sean MASTER de otra réplica, lo cual no aplica aquí).

## Creación Rápida con Script

```bash
#!/bin/bash

# Autenticación (server-id 11)
cat > servicio_de_autenticacion_y_usuarios/replica.cnf <<'EOF'
[mysqld]
server-id = 11
relay-log = relay-bin
replicate-do-db = auth_db
EOF

# Sucursales (server-id 12)
cat > servicio_de_sucursales/replica.cnf <<'EOF'
[mysqld]
server-id = 12
relay-log = relay-bin
replicate-do-db = branches_db
EOF

# Inventario (server-id 13)
cat > servicio_de_inventario/replica.cnf <<'EOF'
[mysqld]
server-id = 13
relay-log = relay-bin
replicate-do-db = inventory_db
EOF

# Ventas (server-id 14)
cat > servicio_de_ventas/replica.cnf <<'EOF'
[mysqld]
server-id = 14
relay-log = relay-bin
replicate-do-db = sales_db
EOF

# Reservaciones (server-id 15)
cat > servicio_de_clientes_y_reservaciones/replica.cnf <<'EOF'
[mysqld]
server-id = 15
relay-log = relay-bin
replicate-do-db = reservations_db
EOF

# RRHH (server-id 16)
cat > servicio_de_recursos_humanos/replica.cnf <<'EOF'
[mysqld]
server-id = 16
relay-log = relay-bin
replicate-do-db = hr_db
EOF

# Configuración (server-id 17)
cat > servicio_de_configuracion/replica.cnf <<'EOF'
[mysqld]
server-id = 17
relay-log = relay-bin
replicate-do-db = config_db
EOF

echo "✓ Archivos replica.cnf creados"
```

## Verificación

### Listar archivos creados
```bash
ls -la servicio_de_*/replica.cnf
```

**Salida esperada**:
```
servicio_de_autenticacion_y_usuarios/replica.cnf
servicio_de_sucursales/replica.cnf
servicio_de_inventario/replica.cnf
servicio_de_ventas/replica.cnf
servicio_de_clientes_y_reservaciones/replica.cnf
servicio_de_recursos_humanos/replica.cnf
servicio_de_configuracion/replica.cnf
```

### Verificar contenido
```bash
cat servicio_de_autenticacion_y_usuarios/replica.cnf
```

**Salida esperada**:
```ini
[mysqld]
server-id = 11
relay-log = relay-bin
replicate-do-db = auth_db
```

### Verificar todos los server-id
```bash
grep "server-id" servicio_de_*/replica.cnf
```

**Salida esperada**:
```
servicio_de_autenticacion_y_usuarios/replica.cnf:server-id = 11
servicio_de_sucursales/replica.cnf:server-id = 12
servicio_de_inventario/replica.cnf:server-id = 13
servicio_de_ventas/replica.cnf:server-id = 14
servicio_de_clientes_y_reservaciones/replica.cnf:server-id = 15
servicio_de_recursos_humanos/replica.cnf:server-id = 16
servicio_de_configuracion/replica.cnf:server-id = 17
```

**Verifica que**:
- Los IDs sean 11-17 (no 1-7)
- No haya IDs duplicados

## Comparación MASTER vs REPLICA

**MASTER** (`master.cnf`):
```ini
[mysqld]
server-id = 1
log_bin = mysql-bin          ← Genera binlog
binlog_do_db = auth_db       ← Especifica BD a logear
binlog_format = ROW
max_binlog_size = 100M
expire_logs_days = 7
```

**REPLICA** (`replica.cnf`):
```ini
[mysqld]
server-id = 11               ← ID diferente (+10)
relay-log = relay-bin        ← Recibe relay log
replicate-do-db = auth_db    ← Especifica BD a replicar
```

## ¿Qué sigue?
Una vez creados los archivos `replica.cnf`, continúa con el [PASO 5: Crear Contenedores REPLICA](PASO_05_CONTENEDORES_REPLICA.md).
