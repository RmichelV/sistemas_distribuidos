# PASO 5: Crear Contenedores REPLICA con Docker Compose

## Objetivo
Crear archivos `docker-compose.yml` para los contenedores REPLICA (esclavos) que replicarán los datos de los MASTER.

## Diferencias con Contenedores MASTER

| Aspecto | MASTER | REPLICA |
|---------|--------|---------|
| **Nombre contenedor** | `sd_db_auth` | `sd_db_auth_replica` |
| **Puertos expuestos** | ✅ Sí (3307-3313) | ❌ No (solo acceso interno) |
| **Archivo config** | `master.cnf` | `replica.cnf` |
| **Volumen** | `sd_vol_auth` | `sd_vol_auth_replica` |
| **Usuario replicación** | Crea el usuario | No necesita crearlo |

## Estructura de Archivos

```
SIS/
├── servicio_de_autenticacion_y_usuarios/
│   ├── sd_db_auth.yml          ← MASTER (ya existe)
│   ├── sd_db_auth_replica.yml  ← REPLICA (crear)
│   ├── master.cnf
│   └── replica.cnf
├── servicio_de_sucursales/
│   ├── sd_db_branches.yml
│   ├── sd_db_branches_replica.yml  ← Crear
│   ├── master.cnf
│   └── replica.cnf
... (igual para los otros 5 servicios)
```

## Archivos a Crear

### 1. Autenticación y Usuarios
**Ubicación**: `servicio_de_autenticacion_y_usuarios/sd_db_auth_replica.yml`

```yaml
services:
  sd_db_auth_replica:
    image: mysql:8.0
    container_name: sd_db_auth_replica
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: "3312"
      MYSQL_DATABASE: "auth_db"
      MYSQL_USER: "rmichelv"
      MYSQL_PASSWORD: "usuario123"
    volumes:
      - sd_vol_auth_replica:/var/lib/mysql
      - ./replica.cnf:/etc/mysql/conf.d/replica.cnf
    networks:
      - sd_network

volumes:
  sd_vol_auth_replica:

networks:
  sd_network:
    external: true
```

**Notas importantes**:
- ❌ **NO tiene sección `ports`** (no se expone al exterior)
- ✅ Monta `replica.cnf` en lugar de `master.cnf`
- ✅ Usa volumen diferente: `sd_vol_auth_replica`

---

### 2. Sucursales
**Ubicación**: `servicio_de_sucursales/sd_db_branches_replica.yml`

```yaml
services:
  sd_db_branches_replica:
    image: mysql:8.0
    container_name: sd_db_branches_replica
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: "3312"
      MYSQL_DATABASE: "branches_db"
      MYSQL_USER: "rmichelv"
      MYSQL_PASSWORD: "usuario123"
    volumes:
      - sd_vol_branches_replica:/var/lib/mysql
      - ./replica.cnf:/etc/mysql/conf.d/replica.cnf
    networks:
      - sd_network

volumes:
  sd_vol_branches_replica:

networks:
  sd_network:
    external: true
```

---

### 3. Inventario
**Ubicación**: `servicio_de_inventario/sd_db_inventory_replica.yml`

```yaml
services:
  sd_db_inventory_replica:
    image: mysql:8.0
    container_name: sd_db_inventory_replica
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: "3312"
      MYSQL_DATABASE: "inventory_db"
      MYSQL_USER: "rmichelv"
      MYSQL_PASSWORD: "usuario123"
    volumes:
      - sd_vol_inventory_replica:/var/lib/mysql
      - ./replica.cnf:/etc/mysql/conf.d/replica.cnf
    networks:
      - sd_network

volumes:
  sd_vol_inventory_replica:

networks:
  sd_network:
    external: true
```

---

### 4. Ventas
**Ubicación**: `servicio_de_ventas/sd_db_sales_replica.yml`

```yaml
services:
  sd_db_sales_replica:
    image: mysql:8.0
    container_name: sd_db_sales_replica
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: "3312"
      MYSQL_DATABASE: "sales_db"
      MYSQL_USER: "rmichelv"
      MYSQL_PASSWORD: "usuario123"
    volumes:
      - sd_vol_sales_replica:/var/lib/mysql
      - ./replica.cnf:/etc/mysql/conf.d/replica.cnf
    networks:
      - sd_network

volumes:
  sd_vol_sales_replica:

networks:
  sd_network:
    external: true
```

---

### 5. Clientes y Reservaciones
**Ubicación**: `servicio_de_clientes_y_reservaciones/sd_db_reservations_replica.yml`

```yaml
services:
  sd_db_reservations_replica:
    image: mysql:8.0
    container_name: sd_db_reservations_replica
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: "3312"
      MYSQL_DATABASE: "reservations_db"
      MYSQL_USER: "rmichelv"
      MYSQL_PASSWORD: "usuario123"
    volumes:
      - sd_vol_reservations_replica:/var/lib/mysql
      - ./replica.cnf:/etc/mysql/conf.d/replica.cnf
    networks:
      - sd_network

volumes:
  sd_vol_reservations_replica:

networks:
  sd_network:
    external: true
```

---

### 6. Recursos Humanos
**Ubicación**: `servicio_de_recursos_humanos/sd_db_hr_replica.yml`

```yaml
services:
  sd_db_hr_replica:
    image: mysql:8.0
    container_name: sd_db_hr_replica
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: "3312"
      MYSQL_DATABASE: "hr_db"
      MYSQL_USER: "rmichelv"
      MYSQL_PASSWORD: "usuario123"
    volumes:
      - sd_vol_hr_replica:/var/lib/mysql
      - ./replica.cnf:/etc/mysql/conf.d/replica.cnf
    networks:
      - sd_network

volumes:
  sd_vol_hr_replica:

networks:
  sd_network:
    external: true
```

---

### 7. Configuración
**Ubicación**: `servicio_de_configuracion/sd_db_config_replica.yml`

```yaml
services:
  sd_db_config_replica:
    image: mysql:8.0
    container_name: sd_db_config_replica
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: "3312"
      MYSQL_DATABASE: "config_db"
      MYSQL_USER: "rmichelv"
      MYSQL_PASSWORD: "usuario123"
    volumes:
      - sd_vol_config_replica:/var/lib/mysql
      - ./replica.cnf:/etc/mysql/conf.d/replica.cnf
    networks:
      - sd_network

volumes:
  sd_vol_config_replica:

networks:
  sd_network:
    external: true
```

## Tabla Comparativa de Volúmenes

| Servicio | Volumen MASTER | Volumen REPLICA |
|----------|----------------|-----------------|
| Autenticación | `sd_vol_auth` | `sd_vol_auth_replica` |
| Sucursales | `sd_vol_branches` | `sd_vol_branches_replica` |
| Inventario | `sd_vol_inventory` | `sd_vol_inventory_replica` |
| Ventas | `sd_vol_sales` | `sd_vol_sales_replica` |
| Reservaciones | `sd_vol_reservations` | `sd_vol_reservations_replica` |
| RRHH | `sd_vol_hr` | `sd_vol_hr_replica` |
| Configuración | `sd_vol_config` | `sd_vol_config_replica` |

**CRÍTICO**: Cada contenedor debe tener su propio volumen. Si MASTER y REPLICA usan el mismo volumen, habrá corrupción de datos.

## ¿Por qué NO exponemos puertos en REPLICA?

### Seguridad
- Las REPLICAS son solo para **lectura interna** desde otros servicios
- No deberían ser accesibles directamente desde el exterior

### Acceso interno
Los servicios dentro de `sd_network` pueden acceder usando el hostname:
```bash
# Desde otro contenedor en sd_network
mysql -h sd_db_auth_replica -u root -p
```

### Si necesitas acceso externo (desarrollo)
Puedes agregar temporalmente:
```yaml
ports:
  - "33070:3306"  # Puerto diferente del MASTER
```

## Iniciar Contenedores REPLICA

### ⚠️ IMPORTANTE: Orden de inicio

**SIEMPRE** inicia MASTERS primero, espera unos segundos, y luego REPLICAS:

```bash
# 1. Iniciar MASTERS
docker-compose -f servicio_de_autenticacion_y_usuarios/sd_db_auth.yml up -d
docker-compose -f servicio_de_sucursales/sd_db_branches.yml up -d
docker-compose -f servicio_de_inventario/sd_db_inventory.yml up -d
docker-compose -f servicio_de_ventas/sd_db_sales.yml up -d
docker-compose -f servicio_de_clientes_y_reservaciones/sd_db_reservations.yml up -d
docker-compose -f servicio_de_recursos_humanos/sd_db_hr.yml up -d
docker-compose -f servicio_de_configuracion/sd_db_config.yml up -d

# 2. Esperar 15 segundos (importante)
sleep 15

# 3. Iniciar REPLICAS
docker-compose -f servicio_de_autenticacion_y_usuarios/sd_db_auth_replica.yml up -d
docker-compose -f servicio_de_sucursales/sd_db_branches_replica.yml up -d
docker-compose -f servicio_de_inventario/sd_db_inventory_replica.yml up -d
docker-compose -f servicio_de_ventas/sd_db_sales_replica.yml up -d
docker-compose -f servicio_de_clientes_y_reservaciones/sd_db_reservations_replica.yml up -d
docker-compose -f servicio_de_recursos_humanos/sd_db_hr_replica.yml up -d
docker-compose -f servicio_de_configuracion/sd_db_config_replica.yml up -d
```

### Script automatizado

Ya existe `start-all.sh` que hace esto automáticamente:
```bash
./start-all.sh
```

## Verificación

### 1. Verificar que todos los contenedores estén corriendo
```bash
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
```

**Salida esperada** (14 contenedores):
```
NAMES                        STATUS          PORTS
sd_db_auth                   Up 2 minutes    0.0.0.0:3307->3306/tcp
sd_db_auth_replica           Up 1 minute     3306/tcp
sd_db_branches               Up 2 minutes    0.0.0.0:3308->3306/tcp
sd_db_branches_replica       Up 1 minute     3306/tcp
sd_db_inventory              Up 2 minutes    0.0.0.0:3309->3306/tcp
sd_db_inventory_replica      Up 1 minute     3306/tcp
sd_db_sales                  Up 2 minutes    0.0.0.0:3310->3306/tcp
sd_db_sales_replica          Up 1 minute     3306/tcp
sd_db_reservations           Up 2 minutes    0.0.0.0:3311->3306/tcp
sd_db_reservations_replica   Up 1 minute     3306/tcp
sd_db_hr                     Up 2 minutes    0.0.0.0:3312->3306/tcp
sd_db_hr_replica             Up 1 minute     3306/tcp
sd_db_config                 Up 2 minutes    0.0.0.0:3313->3306/tcp
sd_db_config_replica         Up 1 minute     3306/tcp
```

**Notas**:
- Los MASTER tienen puertos mapeados (3307-3313)
- Las REPLICAS solo muestran `3306/tcp` (sin mapeo externo)

---

### 2. Verificar configuración REPLICA
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "SHOW VARIABLES LIKE 'server_id';"
```

**Salida esperada**:
```
+---------------+-------+
| Variable_name | Value |
+---------------+-------+
| server_id     | 11    |
+---------------+-------+
```

---

### 3. Verificar relay log habilitado
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "SHOW VARIABLES LIKE 'relay_log';"
```

**Salida esperada**:
```
+---------------+-----------+
| Variable_name | Value     |
+---------------+-----------+
| relay_log     | relay-bin |
+---------------+-----------+
```

---

### 4. Verificar que la BD se creó
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "SHOW DATABASES;"
```

**Salida esperada**:
```
+--------------------+
| Database           |
+--------------------+
| auth_db            |
| information_schema |
| mysql              |
| performance_schema |
| sys                |
+--------------------+
```

---

### 5. Conectarse a REPLICA desde el MASTER
```bash
docker exec sd_db_auth mysql -h sd_db_auth_replica -uroot -p3312 -e "SELECT 1;"
```

**Salida esperada**:
```
+---+
| 1 |
+---+
| 1 |
+---+
```

Esto confirma que MASTER y REPLICA pueden comunicarse dentro de `sd_network`.

## Solución de Problemas

### Error: "Cannot start service: Conflict. The container name '/sd_db_auth_replica' is already in use"
**Causa**: El contenedor ya existe.

**Solución**:
```bash
docker rm -f sd_db_auth_replica
docker-compose -f servicio_de_autenticacion_y_usuarios/sd_db_auth_replica.yml up -d
```

---

### Error: "network sd_network not found"
**Causa**: No se creó la red en PASO 1.

**Solución**:
```bash
./create-network.sh
```

---

### Error: "no such file or directory: ./replica.cnf"
**Causa**: El archivo `replica.cnf` no existe.

**Solución**: Ejecuta el PASO 4.

---

### Contenedor REPLICA se reinicia constantemente
**Verificar logs**:
```bash
docker logs sd_db_auth_replica --tail 50
```

**Causas comunes**:
1. Error en `replica.cnf` (sintaxis)
2. Volumen corrupto (solución: `docker volume rm sd_vol_auth_replica`)
3. Falta la red `sd_network`

---

### No puedo conectarme desde el host a la REPLICA
**Esto es ESPERADO** porque no tiene puertos expuestos.

**Opciones**:
1. Conectarte desde otro contenedor en la red
2. Agregar temporalmente `ports` al docker-compose (solo desarrollo)

## Recrear REPLICA (si algo sale mal)

```bash
# Detener y eliminar contenedor
docker-compose -f servicio_de_autenticacion_y_usuarios/sd_db_auth_replica.yml down -v

# Eliminar volumen (CUIDADO: borra todos los datos)
docker volume rm sd_vol_auth_replica

# Reiniciar
docker-compose -f servicio_de_autenticacion_y_usuarios/sd_db_auth_replica.yml up -d

# Esperar 10 segundos
sleep 10

# Verificar logs
docker logs sd_db_auth_replica
```

## ¿Qué sigue?
Los contenedores REPLICA están corriendo, pero **AÚN NO están replicando datos**. 

Para activar la replicación, continúa con el [PASO 6: Configurar Replicación](PASO_06_SETUP_REPLICATION.md).
