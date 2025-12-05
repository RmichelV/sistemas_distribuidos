# Sistema de Inventario - Arquitectura de Microservicios

## 📋 Estructura del Proyecto

```
SIS/
├── create-network.sh                           # Script para crear la red Docker
├── database/
│   └── init-all-tables.sql                     # Sentencias SQL para todas las BD
├── servicio_de_autenticacion_y_usuarios/
│   └── sd_db_auth.yml                          # BD de usuarios y autenticación
├── servicio_de_sucursales/
│   └── sd_db_branches.yml                      # BD de sucursales
├── servicio_de_inventario/
│   └── sd_db_inventory.yml                     # BD de inventario y productos
├── servicio_de_ventas/
│   └── sd_db_sales.yml                         # BD de ventas
├── servicio_de_clientes_y_reservaciones/
│   └── sd_db_reservations.yml                  # BD de clientes y reservaciones
├── servicio_de_recursos_humanos/
│   └── sd_db_hr.yml                            # BD de RRHH
└── servicio_de_configuracion/
    └── sd_db_config.yml                        # BD de configuración
```

## 🚀 Guía de Instalación

### 1. Crear la red Docker

```bash
# Dar permisos de ejecución al script
chmod +x create-network.sh

# Ejecutar el script
./create-network.sh
```

### 2. Iniciar las bases de datos

Puedes iniciar todos los contenedores a la vez o individualmente:

#### Opción A: Iniciar todos los contenedores

```bash
# Desde la raíz del proyecto SIS/
docker-compose -f servicio_de_autenticacion_y_usuarios/sd_db_auth.yml up -d
docker-compose -f servicio_de_sucursales/sd_db_branches.yml up -d
docker-compose -f servicio_de_inventario/sd_db_inventory.yml up -d
docker-compose -f servicio_de_ventas/sd_db_sales.yml up -d
docker-compose -f servicio_de_clientes_y_reservaciones/sd_db_reservations.yml up -d
docker-compose -f servicio_de_recursos_humanos/sd_db_hr.yml up -d
docker-compose -f servicio_de_configuracion/sd_db_config.yml up -d
```

#### Opción B: Iniciar contenedores individuales

```bash
# Por ejemplo, solo autenticación
cd servicio_de_autenticacion_y_usuarios
docker-compose -f sd_db_auth.yml up -d
```

### 3. Verificar que los contenedores estén corriendo

```bash
docker ps
```

Deberías ver 7 contenedores:
- `sd_db_auth`
- `sd_db_branches`
- `sd_db_inventory`
- `sd_db_sales`
- `sd_db_reservations`
- `sd_db_hr`
- `sd_db_config`

### 4. Crear las tablas manualmente

El archivo `database/init-all-tables.sql` contiene todas las sentencias SQL organizadas por contenedor.

#### Opción A: Copiar el archivo SQL a cada contenedor y ejecutar

```bash
# Ejemplo para sd_db_auth
docker exec -i sd_db_auth mysql -uroot -p3312 auth_db < database/init-all-tables.sql

# Repetir para cada contenedor cambiando el nombre de la BD
docker exec -i sd_db_branches mysql -uroot -p3312 branches_db < database/init-all-tables.sql
docker exec -i sd_db_inventory mysql -uroot -p3312 inventory_db < database/init-all-tables.sql
docker exec -i sd_db_sales mysql -uroot -p3312 sales_db < database/init-all-tables.sql
docker exec -i sd_db_reservations mysql -uroot -p3312 reservations_db < database/init-all-tables.sql
docker exec -i sd_db_hr mysql -uroot -p3312 hr_db < database/init-all-tables.sql
docker exec -i sd_db_config mysql -uroot -p3312 config_db < database/init-all-tables.sql
```

#### Opción B: Conectarse manualmente y ejecutar las sentencias

```bash
# Conectarse a un contenedor específico
docker exec -it sd_db_auth mysql -uroot -p3312

# Luego copiar y pegar las sentencias SQL correspondientes
```

## 📊 Bases de Datos y Tablas

### sd_db_auth (auth_db)
- `roles`
- `users`
- `password_reset_tokens`
- `sessions`

### sd_db_branches (branches_db)
- `branches`

### sd_db_inventory (inventory_db)
- `products`
- `product_branches`
- `product_stores`
- `purchases`

### sd_db_sales (sales_db)
- `sales`
- `sale_items`
- `devolutions`

### sd_db_reservations (reservations_db)
- `customers`
- `reservations`
- `reservation_items`

### sd_db_hr (hr_db)
- `attendance_records`
- `salary_adjustments`
- `salaries`

### sd_db_config (config_db)
- `usd_exchange_rates`
- `cache`
- `cache_locks`
- `jobs`
- `job_batches`
- `failed_jobs`

## 🔐 Credenciales

**Usuario root:**
- Usuario: `root`
- Contraseña: `3312`

**Usuario adicional:**
- Usuario: `rmichelv`
- Contraseña: `usuario123`

## 🌐 Red Docker

Todos los contenedores están conectados a la red `sd_network`, lo que permite:
- Comunicación entre contenedores
- Uso de nombres de contenedor como hostnames
- Aislamiento de la red externa

### Conectarse entre contenedores

Desde cualquier API o servicio en la misma red, puedes conectarte usando:

```
Host: sd_db_auth
Port: 3306 (puerto interno, NO expuesto al host)
Database: auth_db
Username: root
Password: 3312
```

## 🛠️ Comandos Útiles

### Ver logs de un contenedor
```bash
docker logs sd_db_auth
docker logs -f sd_db_auth  # Seguir logs en tiempo real
```

### Verificar salud del contenedor
```bash
docker inspect sd_db_auth | grep -A 10 Health
```

### Conectarse a MySQL dentro del contenedor
```bash
docker exec -it sd_db_auth mysql -uroot -p3312
```

### Detener todos los contenedores
```bash
docker stop sd_db_auth sd_db_branches sd_db_inventory sd_db_sales sd_db_reservations sd_db_hr sd_db_config
```

### Eliminar todos los contenedores (sin borrar datos)
```bash
docker rm sd_db_auth sd_db_branches sd_db_inventory sd_db_sales sd_db_reservations sd_db_hr sd_db_config
```

### Eliminar volúmenes (⚠️ ESTO BORRA TODOS LOS DATOS)
```bash
docker volume rm sd_db_auth_data sd_db_branches_data sd_db_inventory_data sd_db_sales_data sd_db_reservations_data sd_db_hr_data sd_db_config_data
```

### Backup de una base de datos
```bash
docker exec sd_db_auth mysqldump -uroot -p3312 auth_db > backup_auth_db.sql
```

### Restaurar desde backup
```bash
docker exec -i sd_db_auth mysql -uroot -p3312 auth_db < backup_auth_db.sql
```

## 📝 Notas Importantes

1. **Sin puertos expuestos**: Las bases de datos NO están expuestas en tu máquina local. Solo son accesibles desde contenedores en la red `sd_network`.

2. **Persistencia de datos**: Los volúmenes Docker (`sd_db_*_data`) persisten los datos aunque borres los contenedores.

3. **Foreign Keys entre servicios**: Como cada BD está en un contenedor diferente, NO se pueden crear Foreign Keys entre servicios. La integridad referencial debe manejarse a nivel de aplicación.

4. **Healthchecks**: Cada contenedor tiene un healthcheck que verifica que MySQL esté respondiendo correctamente.

## 🤝 Compartir con el Equipo

Para que tus compañeros trabajen con el mismo entorno:

1. Subir todo el directorio `SIS/` a GitHub
2. Ellos clonan el repositorio
3. Ejecutan `./create-network.sh`
4. Levantan los contenedores
5. Ejecutan las sentencias SQL

## 📚 Próximos Pasos

- [ ] Crear las APIs de Laravel para cada microservicio
- [ ] Configurar API Gateway
- [ ] Implementar RabbitMQ para comunicación asíncrona
- [ ] Configurar Redis para cache compartido
- [ ] Implementar autenticación JWT
- [ ] Configurar CI/CD

---

**Creado por:** Equipo de Sistemas Distribuidos  
**Fecha:** 28 de noviembre de 2025
