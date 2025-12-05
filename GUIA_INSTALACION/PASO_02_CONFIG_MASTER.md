# PASO 2: Archivos de Configuración MASTER

## Objetivo
Crear archivos `master.cnf` para configurar cada servidor MySQL como MASTER de replicación.

## ¿Qué hace este archivo?
Configura MySQL para:
1. **Habilitar el binary log (binlog)**: Registra todos los cambios en la BD
2. **Asignar un server-id único**: Identifica cada servidor en la replicación
3. **Especificar formato de replicación**: ROW es el más seguro y detallado

## ¿Por qué es necesario?
Sin el binlog, las réplicas no sabrían qué cambios aplicar. El binlog es como un "diario" de todas las modificaciones.

## Estructura de Carpetas

```
SIS/
├── servicio_de_autenticacion_y_usuarios/
│   └── master.cnf  ← Crear aquí
├── servicio_de_sucursales/
│   └── master.cnf  ← Crear aquí
├── servicio_de_inventario/
│   └── master.cnf  ← Crear aquí
├── servicio_de_ventas/
│   └── master.cnf  ← Crear aquí
├── servicio_de_clientes_y_reservaciones/
│   └── master.cnf  ← Crear aquí
├── servicio_de_recursos_humanos/
│   └── master.cnf  ← Crear aquí
└── servicio_de_configuracion/
    └── master.cnf  ← Crear aquí
```

## Archivos a Crear

### 1. Autenticación y Usuarios
**Ubicación**: `servicio_de_autenticacion_y_usuarios/master.cnf`

```ini
[mysqld]
server-id = 1
log_bin = mysql-bin
binlog_do_db = auth_db
binlog_format = ROW
max_binlog_size = 100M
expire_logs_days = 7
```

---

### 2. Sucursales
**Ubicación**: `servicio_de_sucursales/master.cnf`

```ini
[mysqld]
server-id = 2
log_bin = mysql-bin
binlog_do_db = branches_db
binlog_format = ROW
max_binlog_size = 100M
expire_logs_days = 7
```

---

### 3. Inventario
**Ubicación**: `servicio_de_inventario/master.cnf`

```ini
[mysqld]
server-id = 3
log_bin = mysql-bin
binlog_do_db = inventory_db
binlog_format = ROW
max_binlog_size = 100M
expire_logs_days = 7
```

---

### 4. Ventas
**Ubicación**: `servicio_de_ventas/master.cnf`

```ini
[mysqld]
server-id = 4
log_bin = mysql-bin
binlog_do_db = sales_db
binlog_format = ROW
max_binlog_size = 100M
expire_logs_days = 7
```

---

### 5. Clientes y Reservaciones
**Ubicación**: `servicio_de_clientes_y_reservaciones/master.cnf`

```ini
[mysqld]
server-id = 5
log_bin = mysql-bin
binlog_do_db = reservations_db
binlog_format = ROW
max_binlog_size = 100M
expire_logs_days = 7
```

---

### 6. Recursos Humanos
**Ubicación**: `servicio_de_recursos_humanos/master.cnf`

```ini
[mysqld]
server-id = 6
log_bin = mysql-bin
binlog_do_db = hr_db
binlog_format = ROW
max_binlog_size = 100M
expire_logs_days = 7
```

---

### 7. Configuración
**Ubicación**: `servicio_de_configuracion/master.cnf`

```ini
[mysqld]
server-id = 7
log_bin = mysql-bin
binlog_do_db = config_db
binlog_format = ROW
max_binlog_size = 100M
expire_logs_days = 7
```

## Explicación de Parámetros

| Parámetro | Valor | Descripción |
|-----------|-------|-------------|
| `server-id` | 1-7 | **ID único** de cada servidor. NUNCA debe repetirse. |
| `log_bin` | mysql-bin | **Nombre base** del archivo binlog. MySQL agregará números secuenciales (mysql-bin.000001, mysql-bin.000002, etc.). |
| `binlog_do_db` | nombre_db | **Base de datos** que se replicará. Solo los cambios en esta BD se registrarán en el binlog. |
| `binlog_format` | ROW | **Formato de replicación**. ROW replica los cambios fila por fila (más seguro que STATEMENT). |
| `max_binlog_size` | 100M | **Tamaño máximo** de cada archivo binlog antes de crear uno nuevo. |
| `expire_logs_days` | 7 | **Días de retención** de logs antiguos. Después se borran automáticamente. |

## Formatos de Binlog

### ROW (Recomendado) ✅
- Replica los **cambios exactos** en cada fila
- Más seguro, evita inconsistencias
- Tamaño de log mayor

### STATEMENT
- Replica las **consultas SQL** que se ejecutaron
- Tamaño de log menor
- Puede causar inconsistencias con funciones no determinísticas (RAND(), NOW())

### MIXED
- Combina ROW y STATEMENT automáticamente
- Usa STATEMENT cuando es seguro, ROW cuando es necesario

## Creación Rápida con Script

Si prefieres crear todos los archivos automáticamente:

```bash
#!/bin/bash

# Crear master.cnf para autenticación
cat > servicio_de_autenticacion_y_usuarios/master.cnf <<'EOF'
[mysqld]
server-id = 1
log_bin = mysql-bin
binlog_do_db = auth_db
binlog_format = ROW
max_binlog_size = 100M
expire_logs_days = 7
EOF

# Crear master.cnf para sucursales
cat > servicio_de_sucursales/master.cnf <<'EOF'
[mysqld]
server-id = 2
log_bin = mysql-bin
binlog_do_db = branches_db
binlog_format = ROW
max_binlog_size = 100M
expire_logs_days = 7
EOF

# ... repetir para los otros 5 servicios

echo "✓ Archivos master.cnf creados"
```

## Verificación

Verifica que los archivos se crearon correctamente:

```bash
ls -la servicio_de_*/master.cnf
```

**Salida esperada**:
```
servicio_de_autenticacion_y_usuarios/master.cnf
servicio_de_sucursales/master.cnf
servicio_de_inventario/master.cnf
servicio_de_ventas/master.cnf
servicio_de_clientes_y_reservaciones/master.cnf
servicio_de_recursos_humanos/master.cnf
servicio_de_configuracion/master.cnf
```

Verifica el contenido de uno:

```bash
cat servicio_de_autenticacion_y_usuarios/master.cnf
```

## ¿Qué sigue?
Continúa con el [PASO 3: Crear Contenedores MASTER](PASO_03_CONTENEDORES_MASTER.md).
