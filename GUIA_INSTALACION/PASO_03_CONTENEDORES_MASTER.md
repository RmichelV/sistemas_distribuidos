# PASO 3: Crear Contenedores MASTER con Docker Compose

## Objetivo
Los 7 archivos `docker-compose.yml` para los contenedores MASTER ya están creados. Este paso explica cómo funcionan y cómo levantarlos.

## ¿Qué es Docker Compose?
Herramienta para definir y ejecutar aplicaciones Docker multi-contenedor usando archivos YAML.

## Estructura Actual

```
SIS/
├── servicio_de_autenticacion_y_usuarios/
│   ├── sd_db_auth.yml          ← Ya existe
│   └── master.cnf               ← Creado en PASO 2
├── servicio_de_sucursales/
│   ├── sd_db_branches.yml      ← Ya existe
│   └── master.cnf
├── servicio_de_inventario/
│   ├── sd_db_inventory.yml     ← Ya existe
│   └── master.cnf
├── servicio_de_ventas/
│   ├── sd_db_sales.yml         ← Ya existe
│   └── master.cnf
├── servicio_de_clientes_y_reservaciones/
│   ├── sd_db_reservations.yml  ← Ya existe
│   └── master.cnf
├── servicio_de_recursos_humanos/
│   ├── sd_db_hr.yml            ← Ya existe
│   └── master.cnf
└── servicio_de_configuracion/
    ├── sd_db_config.yml        ← Ya existe
    └── master.cnf
```

## Anatomía de un Docker Compose MASTER

Veamos el ejemplo de **Autenticación** (`sd_db_auth.yml`):

```yaml
services:
  sd_db_auth:  # Nombre del servicio
    image: mysql:8.0  # Imagen MySQL versión 8.0
    container_name: sd_db_auth  # Nombre del contenedor
    restart: always  # Reiniciar automáticamente si falla
    ports:
      - "3307:3306"  # Puerto externo:interno
    environment:
      MYSQL_ROOT_PASSWORD: "3312"  # Contraseña root
      MYSQL_DATABASE: "auth_db"  # BD que se crea automáticamente
      MYSQL_USER: "rmichelv"  # Usuario adicional
      MYSQL_PASSWORD: "usuario123"  # Contraseña del usuario
    volumes:
      - sd_vol_auth:/var/lib/mysql  # Persistencia de datos
      - ./master.cnf:/etc/mysql/conf.d/master.cnf  # Configuración MASTER
    networks:
      - sd_network  # Red compartida

volumes:
  sd_vol_auth:  # Volumen nombrado

networks:
  sd_network:
    external: true  # Red ya creada en PASO 1
```

## Explicación de Secciones

### 1. Services (Servicios)
Define los contenedores a crear.

#### image
- **Propósito**: Especifica la imagen Docker base
- **Valor**: `mysql:8.0` (MySQL versión 8.0 oficial)

#### container_name
- **Propósito**: Nombre del contenedor en Docker
- **Importante**: También es el hostname en la red `sd_network`

#### restart
- **always**: Reinicia el contenedor si falla o si reinicia Docker
- **on-failure**: Solo reinicia si falla
- **no**: No reinicia automáticamente

#### ports
- **Formato**: `"puerto_host:puerto_contenedor"`
- **Ejemplo**: `"3307:3306"` significa:
  - Puerto 3307 en tu máquina → Puerto 3306 dentro del contenedor
  - Te conectas: `mysql -h localhost -P 3307 -u root -p`

#### environment
Variables de entorno para configurar MySQL:

| Variable | Descripción |
|----------|-------------|
| `MYSQL_ROOT_PASSWORD` | Contraseña del usuario root |
| `MYSQL_DATABASE` | Base de datos creada al inicializar |
| `MYSQL_USER` | Usuario adicional (opcional) |
| `MYSQL_PASSWORD` | Contraseña del usuario adicional |

#### volumes
Mapeos entre el host y el contenedor:

```yaml
volumes:
  - sd_vol_auth:/var/lib/mysql  # Datos de MySQL (persistentes)
  - ./master.cnf:/etc/mysql/conf.d/master.cnf  # Config MASTER
```

- **Volumen nombrado**: `sd_vol_auth` es manejado por Docker
- **Bind mount**: `./master.cnf` monta el archivo local dentro del contenedor

#### networks
```yaml
networks:
  - sd_network
```
Conecta el contenedor a la red `sd_network` creada en PASO 1.

---

### 2. Volumes
```yaml
volumes:
  sd_vol_auth:
```
Declara el volumen para que Docker lo gestione.

---

### 3. Networks
```yaml
networks:
  sd_network:
    external: true
```
Indica que la red ya existe (fue creada externamente en PASO 1).

## Mapeo de Puertos

| Servicio | Puerto Host | Base de Datos | Contenedor |
|----------|-------------|---------------|------------|
| Autenticación | 3307 | `auth_db` | `sd_db_auth` |
| Sucursales | 3308 | `branches_db` | `sd_db_branches` |
| Inventario | 3309 | `inventory_db` | `sd_db_inventory` |
| Ventas | 3310 | `sales_db` | `sd_db_sales` |
| Reservaciones | 3311 | `reservations_db` | `sd_db_reservations` |
| Recursos Humanos | 3312 | `hr_db` | `sd_db_hr` |
| Configuración | 3313 | `config_db` | `sd_db_config` |

## Iniciar los Contenedores MASTER

### Opción 1: Uno por uno

```bash
# Autenticación
docker-compose -f servicio_de_autenticacion_y_usuarios/sd_db_auth.yml up -d

# Sucursales
docker-compose -f servicio_de_sucursales/sd_db_branches.yml up -d

# Inventario
docker-compose -f servicio_de_inventario/sd_db_inventory.yml up -d

# Ventas
docker-compose -f servicio_de_ventas/sd_db_sales.yml up -d

# Reservaciones
docker-compose -f servicio_de_clientes_y_reservaciones/sd_db_reservations.yml up -d

# RRHH
docker-compose -f servicio_de_recursos_humanos/sd_db_hr.yml up -d

# Configuración
docker-compose -f servicio_de_configuracion/sd_db_config.yml up -d
```

**Parámetros**:
- `-f`: Especifica el archivo docker-compose
- `up`: Levanta los contenedores
- `-d`: Modo detached (segundo plano)

---

### Opción 2: Script automatizado

Ya existe el script `start-all.sh` que inicia todos los contenedores:

```bash
./start-all.sh
```

**Fragmento relevante del script**:
```bash
# Iniciar contenedores MASTER
docker-compose -f servicio_de_autenticacion_y_usuarios/sd_db_auth.yml up -d
docker-compose -f servicio_de_sucursales/sd_db_branches.yml up -d
docker-compose -f servicio_de_inventario/sd_db_inventory.yml up -d
docker-compose -f servicio_de_ventas/sd_db_sales.yml up -d
docker-compose -f servicio_de_clientes_y_reservaciones/sd_db_reservations.yml up -d
docker-compose -f servicio_de_recursos_humanos/sd_db_hr.yml up -d
docker-compose -f servicio_de_configuracion/sd_db_config.yml up -d
```

## Verificación

### 1. Verificar que los contenedores estén corriendo
```bash
docker ps | grep sd_db
```

**Salida esperada**:
```
CONTAINER ID   IMAGE        COMMAND                  STATUS        PORTS                    NAMES
abc123...      mysql:8.0    "docker-entrypoint.s…"   Up 2 mins     0.0.0.0:3307->3306/tcp   sd_db_auth
def456...      mysql:8.0    "docker-entrypoint.s…"   Up 2 mins     0.0.0.0:3308->3306/tcp   sd_db_branches
...
```

**Debes ver 7 contenedores** con nombres:
- sd_db_auth
- sd_db_branches
- sd_db_inventory
- sd_db_sales
- sd_db_reservations
- sd_db_hr
- sd_db_config

---

### 2. Verificar logs de un contenedor
```bash
docker logs sd_db_auth
```

**Buscar líneas importantes**:
```
[Server] /usr/sbin/mysqld: ready for connections. Version: '8.0.x'
```

---

### 3. Verificar configuración MASTER
```bash
docker exec sd_db_auth mysql -uroot -p3312 -e "SHOW VARIABLES LIKE 'server_id';"
```

**Salida esperada**:
```
+---------------+-------+
| Variable_name | Value |
+---------------+-------+
| server_id     | 1     |
+---------------+-------+
```

---

### 4. Verificar binlog habilitado
```bash
docker exec sd_db_auth mysql -uroot -p3312 -e "SHOW VARIABLES LIKE 'log_bin';"
```

**Salida esperada**:
```
+---------------+-------+
| Variable_name | Value |
+---------------+-------+
| log_bin       | ON    |
+---------------+-------+
```

---

### 5. Conectarse desde el host
```bash
mysql -h localhost -P 3307 -u root -p
# Contraseña: 3312
```

**Si conecta correctamente**:
```
Welcome to the MySQL monitor.
mysql> SHOW DATABASES;
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

## Detener Contenedores

### Detener uno específico
```bash
docker-compose -f servicio_de_autenticacion_y_usuarios/sd_db_auth.yml down
```

### Detener todos
```bash
./stop-all.sh
```

## Reiniciar Contenedores

### Reiniciar uno específico
```bash
docker restart sd_db_auth
```

### Reiniciar todos los MASTER
```bash
docker restart sd_db_auth sd_db_branches sd_db_inventory sd_db_sales sd_db_reservations sd_db_hr sd_db_config
```

## Solución de Problemas

### Error: "Bind for 0.0.0.0:3307 failed: port is already allocated"
**Causa**: El puerto ya está en uso.

**Solución**:
```bash
# Ver qué proceso usa el puerto
lsof -i :3307

# Cambiar el puerto en el docker-compose.yml
ports:
  - "3317:3306"  # Usa otro puerto
```

---

### Error: "network sd_network declared as external, but could not be found"
**Causa**: No se ejecutó el PASO 1.

**Solución**:
```bash
./create-network.sh
```

---

### Error: "Cannot start service: open ./master.cnf: no such file or directory"
**Causa**: El archivo `master.cnf` no existe.

**Solución**: Ejecuta el PASO 2.

---

### Contenedor se reinicia constantemente
**Verificar logs**:
```bash
docker logs sd_db_auth --tail 50
```

**Causas comunes**:
- Error en `master.cnf` (sintaxis incorrecta)
- Volumen corrupto (solución: `docker volume rm sd_vol_auth`)

## ¿Qué sigue?
Una vez que los 7 contenedores MASTER estén corriendo y verificados, continúa con el [PASO 4: Configuración de REPLICA](PASO_04_CONFIG_REPLICA.md).
