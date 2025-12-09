# 🎤 Guion de Presentación: Sistema de Microservicios E-WTTO

**Duración estimada**: 21-24 minutos (3 min por persona)  
**Estructura**: 8 secciones + introducción + conclusión

---

## 🎯 INTRODUCCIÓN GENERAL (Todos - 2 minutos)

> **Portavoz del equipo:**

"Buenos días/tardes. El día de hoy presentaremos el desarrollo e implementación de un **sistema distribuido de microservicios** para la gestión de inventario, ventas, recursos humanos y operaciones de una empresa comercial.

Nuestro sistema está basado en una **arquitectura de microservicios**, donde cada componente funciona de manera autónoma pero coordinada. Utilizamos Docker para la orquestación de contenedores, Laravel como framework para las APIs REST, MySQL con replicación master-slave para alta disponibilidad, y un API Gateway como punto de entrada único.

El proyecto consta de:
- **7 microservicios API** independientes
- **14 bases de datos MySQL** (7 master + 7 replica)
- **1 API Gateway** que centraliza todas las peticiones
- **Comunicación** mediante APIs REST
- **Autenticación** mediante JWT (JSON Web Tokens)

Cada uno de nosotros explicará un componente del sistema. Comencemos con la infraestructura base."

---

## 📦 SECCIÓN 1: INFRAESTRUCTURA BASE - Red, Bases de Datos y Replicación  
**Persona 8** (3 minutos)

### 1.1 Introducción Personal

"Hola, mi nombre es [Nombre]. Estaré a cargo de explicar la **infraestructura base** del sistema: la red Docker, las bases de datos y el sistema de replicación master-slave.

Antes de poder tener microservicios funcionando, necesitamos establecer la base sobre la cual se construirá todo el sistema. Esto incluye tres componentes críticos:

1. Una red virtual para que los contenedores se comuniquen
2. Bases de datos para almacenar información
3. Replicación para alta disponibilidad y tolerancia a fallos"

---

### 1.2 Creación de la Red Docker

"Para empezar todo, necesitamos crear una **red Docker personalizada**. ¿Por qué? Porque por defecto, los contenedores Docker están aislados y no pueden comunicarse entre sí. Una red nos permite que contenedores se conecten por nombre en lugar de IP.

Para ello, creamos un archivo llamado `create-network.sh`, que contiene el siguiente script:

```bash
#!/bin/bash
echo "🌐 Creando red Docker: sd_network"

if docker network ls | grep -q sd_network; then
    echo "⚠️  La red 'sd_network' ya existe"
else
    docker network create sd_network
    echo "✅ Red 'sd_network' creada exitosamente"
fi
```

Este script verifica primero si la red ya existe para evitar errores, y luego la crea con el nombre `sd_network`. Esta red será del tipo **bridge**, que es perfecta para contenedores que necesitan comunicarse en el mismo host.

Para ejecutarlo, usamos:
```bash
chmod +x create-network.sh    # Le damos permisos de ejecución
./create-network.sh            # Lo ejecutamos
```

Una vez creada, podemos verificarla con:
```bash
docker network ls | grep sd_network
```

Y vemos que aparece nuestra red lista para conectar contenedores."

---

### 1.3 Bases de Datos Master

"Ahora que tenemos la red, necesitamos las bases de datos. Siguiendo el patrón de **Database per Service**, cada microservicio tendrá su propia base de datos. Esto garantiza el desacoplamiento y permite que cada servicio escale independientemente.

Creamos un script `start-all.sh` que inicia **7 bases de datos master**:

```bash
#!/bin/bash
echo "Iniciando contenedores MASTER..."

docker compose -f servicio_de_autenticacion_y_usuarios/sd_db_auth.yml up -d
docker compose -f servicio_de_sucursales/sd_db_branches.yml up -d
docker compose -f servicio_de_inventario/sd_db_inventory.yml up -d
docker compose -f servicio_de_ventas/sd_db_sales.yml up -d
docker compose -f servicio_de_clientes_y_reservaciones/sd_db_reservations.yml up -d
docker compose -f servicio_de_recursos_humanos/sd_db_hr.yml up -d
docker compose -f servicio_de_configuracion/sd_db_config.yml up -d
```

Cada archivo `.yml` define la configuración de una base de datos MySQL 8.0:
- Puerto específico (3306, 3308, 3310, etc.)
- Nombre de base de datos única
- Credenciales (usuario root, contraseña 3312)
- Archivo de configuración `master.cnf` para habilitar binlog

El flag `-d` ejecuta los contenedores en **modo detached** (segundo plano), lo que nos permite seguir usando la terminal mientras las bases de datos se inician.

Esperamos 15 segundos con `sleep 15` para que MySQL se inicialice completamente antes de continuar."

---

### 1.4 Bases de Datos Replica y Replicación

"Para garantizar **alta disponibilidad** y permitir **lectura distribuida**, implementamos replicación master-slave. Por cada base de datos master, tenemos una replica:

```bash
echo "Iniciando contenedores REPLICA..."

docker compose -f servicio_de_autenticacion_y_usuarios/sd_db_auth_replica.yml up -d
docker compose -f servicio_de_sucursales/sd_db_branches_replica.yml up -d
# ... (las otras 5 réplicas)
```

Las réplicas se configuran de forma similar, pero:
- Usan puertos diferentes (3307, 3309, 3311, etc.)
- Cargan el archivo `replica.cnf` en lugar de `master.cnf`
- Están configuradas en modo **solo lectura**

Ahora viene la parte crítica: **configurar la replicación**. Para esto creamos `setup-replication.sh`:

```bash
setup_replication() {
    local master_container=$1
    local replica_container=$2
    
    # 1. Crear usuario de replicación en el MASTER
    docker exec -i $master_container mysql -uroot -p3312 <<EOF
CREATE USER 'replicator'@'%' IDENTIFIED BY 'replicator_password';
GRANT REPLICATION SLAVE ON *.* TO 'replicator'@'%';
FLUSH PRIVILEGES;
EOF
    
    # 2. Obtener posición del binlog
    MASTER_STATUS=$(docker exec -i $master_container mysql -uroot -p3312 -e "SHOW MASTER STATUS\G")
    MASTER_LOG_FILE=$(echo "$MASTER_STATUS" | grep "File:" | awk '{print $2}')
    MASTER_LOG_POS=$(echo "$MASTER_STATUS" | grep "Position:" | awk '{print $2}')
    
    # 3. Configurar REPLICA
    docker exec -i $replica_container mysql -uroot -p3312 <<EOF
CHANGE MASTER TO
    MASTER_HOST='$master_container',
    MASTER_USER='replicator',
    MASTER_PASSWORD='replicator_password',
    MASTER_LOG_FILE='$MASTER_LOG_FILE',
    MASTER_LOG_POS=$MASTER_LOG_POS;
START SLAVE;
SET GLOBAL read_only = 1;
EOF
}
```

¿Qué hace esto?

1. **Crea un usuario especial** llamado 'replicator' en el master con permisos para leer el binlog
2. **Obtiene la posición actual del binlog** - esto es como marcar la página exacta de un libro desde donde la réplica debe empezar a copiar
3. **Configura la réplica** para que se conecte al master, sepa desde dónde leer, y empiece a copiar los cambios
4. **Activa modo solo lectura** para evitar que se escriba accidentalmente en la réplica

Ejecutamos esta función para cada par master-replica:
```bash
setup_replication "sd_db_auth" "sd_db_auth_replica" "auth_db"
setup_replication "sd_db_branches" "sd_db_branches_replica" "branches_db"
# ... (las otras 5 replicaciones)
```

Para verificar que funciona, consultamos el estado:
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "SHOW REPLICA STATUS\G" | grep -E "Replica_IO_Running|Replica_SQL_Running"
```

Debe mostrar:
```
Replica_IO_Running: Yes    ← Leyendo el binlog del master
Replica_SQL_Running: Yes   ← Ejecutando los cambios
```

Ambos en 'Yes' significa que la replicación está funcionando correctamente.

Con esto, tenemos una infraestructura robusta: 7 bases de datos con alta disponibilidad, listas para soportar nuestros microservicios."

---

## 🔐 SECCIÓN 2: SERVICIO DE AUTENTICACIÓN Y USUARIOS  
**Persona 1** (3 minutos)

### 2.1 Introducción Personal

"Hola, soy [Nombre] y voy a explicar el **servicio de autenticación y usuarios**, que es el corazón de la seguridad del sistema.

Este microservicio es responsable de:
- Autenticar usuarios (login/logout)
- Gestionar usuarios y roles
- Generar tokens JWT para acceso seguro
- Validar permisos

Es el primer servicio que debe estar operativo, ya que los demás dependen de él para autenticación."

---

### 2.2 Construcción del Contenedor

"Navegamos al directorio del servicio:
```bash
cd servicio_de_autenticacion_y_usuarios/api
```

Lo primero es construir la imagen Docker. Ejecutamos:
```bash
docker compose build
```

Este comando lee nuestro `Dockerfile`, que define cómo se construye el contenedor:

```dockerfile
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libxml2-dev zip unzip

RUN docker-php-ext-install pdo_mysql mbstring bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .
RUN composer install --prefer-dist
RUN chown -R www-data:www-data /var/www
```

¿Por qué elegimos **PHP 8.3 con Laravel 12**?

1. **Performance**: PHP 8.3 es hasta 3x más rápido que PHP 7.4
2. **Laravel 12**: Framework maduro y probado con:
   - Eloquent ORM para manejo intuitivo de BD
   - Middleware robusto para validación
   - Soporte nativo de APIs REST
   - Comunidad activa y documentación extensa
3. **Alternativas consideradas**:
   - **Node.js + Express**: Más rápido en I/O pero menos estructurado
   - **Python + FastAPI**: Excelente pero equipo más familiarizado con PHP
   - **Java Spring Boot**: Más verboso y curva de aprendizaje mayor

Laravel nos permite desarrollar rápido con código limpio y mantenible.

El build instala:
- PHP 8.3 con FastCGI Process Manager
- Extensiones necesarias (MySQL, mbstring, bcmath)
- Composer para gestión de dependencias
- Copia todo el código de la aplicación
- Instala dependencias de Laravel"

---

### 2.3 Levantamiento del Servicio

"Una vez construida la imagen, levantamos los contenedores:

```bash
docker compose up -d
```

Nuestro `docker-compose.yml` define **dos servicios**:

```yaml
services:
  auth_api:                    # PHP-FPM
    build: .
    container_name: auth_api
    networks:
      - sd_network
    environment:
      - DB_HOST=sd_db_auth
      - DB_DATABASE=auth_db
  
  auth_nginx:                  # Nginx
    image: nginx:alpine
    ports:
      - "8001:80"
    depends_on:
      - auth_api
```

¿Por qué Nginx + PHP-FPM?

- **Nginx**: Servidor web ultrarrápido para servir peticiones HTTP
- **PHP-FPM**: Procesa el código PHP de forma eficiente
- **Separación de responsabilidades**: Nginx maneja conexiones, PHP ejecuta lógica

El flag `-d` ejecuta en segundo plano, liberando nuestra terminal."

---

### 2.4 Configuración de Laravel

"Ahora instalamos las dependencias de PHP:
```bash
docker exec auth_api composer install --no-dev --optimize-autoloader
```

- `--no-dev`: No instala herramientas de desarrollo (reduce tamaño)
- `--optimize-autoloader`: Genera un mapa de clases optimizado para producción

Generamos la clave de aplicación:
```bash
docker exec auth_api php artisan key:generate
```

Esta clave cifra:
- Sesiones de usuario
- Cookies
- Tokens JWT
- Contraseñas

Verificamos que se creó:
```bash
docker exec auth_api grep "^APP_KEY=" .env
# Salida: APP_KEY=base64:Abc123...
```

Sin esta clave, Laravel no funciona."

---

### 2.5 Base de Datos

"Ahora creamos las tablas:
```bash
docker exec auth_api php artisan migrate --force
```

Las migraciones crean:
- Tabla `roles`: 
  ```sql
  CREATE TABLE roles (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
  );
  ```

- Tabla `users`:
  ```sql
  CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id BIGINT,
    branch_id BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
  );
  ```

- Tabla `cache`: Para optimización de consultas

Poblamos con datos de prueba:
```bash
docker exec auth_api php artisan db:seed --force
```

Esto inserta:
- 5 roles: Admin, Manager, Seller, Warehouse, HR
- 1 usuario admin: `admin@ewtto.com` / `admin123`

Regresamos al directorio raíz:
```bash
cd ../..
```"

---

### 2.6 Verificación

"Verificamos que el servicio responda:
```bash
curl http://localhost:8001/api/health
```

Respuesta esperada:
```json
{
  "success": true,
  "service": "auth-service",
  "status": "healthy"
}
```

Esto confirma:
✅ Nginx está corriendo  
✅ PHP-FPM está corriendo  
✅ Laravel responde  
✅ Base de datos conectada  

Nuestro servicio de autenticación está listo para recibir peticiones de login y validar usuarios."

---

## 🏢 SECCIÓN 3: SERVICIO DE SUCURSALES  
**Persona 2** (3 minutos)

### 3.1 Introducción Personal

"Hola, soy [Nombre] y presentaré el **servicio de sucursales**.

Este microservicio gestiona:
- Registro de sucursales de la empresa
- Información de ubicaciones
- Datos de contacto de cada sucursal

Es un servicio sencillo pero fundamental, ya que otros servicios (inventario, ventas, HR) necesitan saber a qué sucursal pertenecen los datos."

---

### 3.2 Despliegue del Servicio

"El proceso es similar al servicio anterior:

```bash
cd servicio_de_sucursales
docker compose build
docker compose up -d
docker exec branch_api composer install --no-dev --optimize-autoloader
docker exec branch_api php artisan key:generate
docker exec branch_api php artisan migrate --force
docker exec branch_api php artisan db:seed --force
cd ..
```

**Diferencias clave con el servicio de autenticación:**

1. **Contenedor**: Se llama `branch_api` en lugar de `auth_api`
2. **Puerto**: Escucha en `8002` en lugar de `8001`
3. **Base de datos**: Se conecta a `sd_db_branches` con la BD `branches_db`"

---

### 3.3 Estructura de Datos

"Las migraciones crean solo **una tabla**:

```sql
CREATE TABLE branches (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

Es una tabla simple porque este servicio tiene una responsabilidad única: gestionar sucursales.

Los seeders insertan 3 sucursales de ejemplo:
```php
DB::table('branches')->insert([
    ['name' => 'Sucursal Centro', 'address' => 'Av. Principal 123', 'phone' => '555-0001'],
    ['name' => 'Sucursal Norte', 'address' => 'Calle Norte 456', 'phone' => '555-0002'],
    ['name' => 'Sucursal Sur', 'address' => 'Av. Sur 789', 'phone' => '555-0003'],
]);
```"

---

### 3.4 API Endpoints

"El servicio expone estos endpoints:

- `GET /api/branches` - Lista todas las sucursales
- `GET /api/branches/{id}` - Detalle de una sucursal
- `POST /api/branches` - Crear nueva sucursal
- `PUT /api/branches/{id}` - Actualizar sucursal
- `DELETE /api/branches/{id}` - Eliminar sucursal

Todos siguen el patrón REST estándar.

Verificamos el servicio:
```bash
curl http://localhost:8002/api/health
```

Y vemos que responde correctamente."

---

## 📦 SECCIÓN 4: SERVICIO DE INVENTARIO  
**Persona 3** (3 minutos)

### 4.1 Introducción Personal

"Hola, soy [Nombre] y explicaré el **servicio de inventario**.

Este es uno de los servicios más complejos porque maneja:
- Catálogo de productos
- Stock en tienda y almacén
- Compras a proveedores
- Transferencias entre sucursales

Es el núcleo del sistema de gestión de inventario."

---

### 4.2 Despliegue

"Ejecutamos los comandos estándar:

```bash
cd servicio_de_inventario
docker compose build
docker compose up -d
docker exec inventory_api composer install --no-dev --optimize-autoloader
docker exec inventory_api php artisan key:generate
```

Agregamos una verificación de la APP_KEY:
```bash
docker exec inventory_api grep "^APP_KEY=" .env
```

Esto nos ayuda a confirmar visualmente que la clave se generó correctamente.

```bash
docker exec inventory_api php artisan migrate --force
docker exec inventory_api php artisan db:seed --force
cd ..
```

**Puerto**: `8003`  
**Base de datos**: `inventory_db`"

---

### 4.3 Estructura de Datos

"Las migraciones crean **7 tablas**:

1. **products** - Catálogo de productos
   ```sql
   CREATE TABLE products (
     id BIGINT PRIMARY KEY,
     code VARCHAR(50) UNIQUE,
     name VARCHAR(255),
     description TEXT,
     min_price DECIMAL(10,2),
     created_at TIMESTAMP
   );
   ```

2. **product_stores** - Stock en tienda
   ```sql
   CREATE TABLE product_stores (
     id BIGINT PRIMARY KEY,
     product_id BIGINT,
     branch_id BIGINT,
     quantity_in_store INT,
     quantity_in_warehouse INT
   );
   ```

3. **product_branches** - Relación producto-sucursal
4. **purchases** - Compras a proveedores
5. **roles** - Roles locales del servicio
6. **users** - Usuarios locales del servicio
7. **cache** - Caché de Laravel

**¿Por qué este servicio tiene `users` y `roles`?**

Siguiendo el patrón de **microservicios autónomos**, cada servicio puede funcionar independientemente. Si el servicio de autenticación falla, el servicio de inventario puede seguir autenticando a sus propios usuarios.

Esto es un trade-off:
- ✅ **Ventaja**: Alta disponibilidad, autonomía completa
- ❌ **Desventaja**: Duplicación de datos de usuarios

Es una decisión de arquitectura válida para sistemas distribuidos."

---

### 4.4 Datos de Prueba

"Los seeders insertan:
- 10 productos de ejemplo (laptops, mice, teclados, monitores)
- Registros de stock para cada producto
- Compras iniciales a proveedores
- Usuarios y roles

Esto nos permite probar el sistema inmediatamente sin tener que crear datos manualmente."

---

### 4.5 API Endpoints

"Endpoints principales:

- `GET /api/products` - Lista de productos
- `POST /api/products` - Crear producto
- `GET /api/products/code/{code}` - Buscar por código
- `GET /api/products/{id}/stock/{branchId}` - Stock por sucursal
- `POST /api/purchases` - Registrar compra
- `POST /api/inventory/transfer` - Transferir entre sucursales

Verificamos:
```bash
curl http://localhost:8003/api/health
```"

---

## 💰 SECCIÓN 5: SERVICIO DE VENTAS  
**Persona 4** (3 minutos)

### 5.1 Introducción Personal

"Hola, soy [Nombre] y presentaré el **servicio de ventas**.

Este servicio maneja:
- Registro de ventas
- Items de cada venta
- Devoluciones de productos
- Reportes de ventas por sucursal

Trabaja en conjunto con el servicio de inventario para descontar stock automáticamente."

---

### 5.2 Despliegue

"Comandos de inicialización:

```bash
cd servicio_de_ventas
docker compose build
docker compose up -d
docker exec sales_api composer install --no-dev --optimize-autoloader
docker exec sales_api php artisan key:generate
docker exec sales_api grep "^APP_KEY=" .env
docker exec sales_api php artisan migrate --force
docker exec sales_api php artisan db:seed --force
cd ..
```

**Puerto**: `8004`  
**Base de datos**: `sales_db`"

---

### 5.3 Estructura de Datos

"Migraciones crean **6 tablas**:

1. **sales** - Registro maestro de ventas
   ```sql
   CREATE TABLE sales (
     id BIGINT PRIMARY KEY,
     user_id BIGINT,
     branch_id BIGINT,
     total_amount DECIMAL(10,2),
     sale_date DATETIME,
     created_at TIMESTAMP
   );
   ```

2. **sale_items** - Detalle de productos vendidos
   ```sql
   CREATE TABLE sale_items (
     id BIGINT PRIMARY KEY,
     sale_id BIGINT,
     product_id BIGINT,
     quantity_products INT,
     unit_price DECIMAL(10,2),
     total_price DECIMAL(10,2)
   );
   ```

3. **devolutions** - Devoluciones de productos
4. **roles**, **users**, **cache**"

---

### 5.4 Lógica de Negocio

"Cuando se registra una venta:

1. Se crea el registro en `sales`
2. Se insertan los items en `sale_items`
3. Se hace una petición HTTP al servicio de inventario para descontar stock
4. Si el inventario confirma, la venta se completa
5. Si falla, se hace rollback

Esto es **comunicación entre microservicios** via APIs REST.

Cuando hay una devolución:

1. Se registra en `devolutions`
2. Se notifica al inventario para reponer stock
3. Se ajusta el total de la venta

Los seeders insertan ventas de ejemplo para demostración.

Verificamos:
```bash
curl http://localhost:8004/api/health
```"

---

## 👥 SECCIÓN 6: SERVICIO DE RESERVACIONES  
**Persona 5** (3 minutos)

### 6.1 Introducción Personal

"Hola, soy [Nombre] y presentaré el **servicio de clientes y reservaciones**.

Este servicio gestiona:
- Registro de clientes
- Creación de reservaciones
- Items reservados
- Pagos de reservas"

---

### 6.2 Despliegue

"Proceso estándar:

```bash
cd servicio_de_clientes_y_reservaciones
docker compose build
docker compose up -d
docker exec reservations_api composer install --no-dev --optimize-autoloader
docker exec reservations_api php artisan key:generate
docker exec reservations_api php artisan migrate --force
docker exec reservations_api php artisan db:seed --force
cd ..
```

**Puerto**: `8005`  
**Base de datos**: `reservations_db`"

---

### 6.3 Estructura de Datos

"Tablas principales:

1. **customers** - Clientes del sistema
   ```sql
   CREATE TABLE customers (
     id BIGINT PRIMARY KEY,
     name VARCHAR(255),
     email VARCHAR(255) UNIQUE,
     phone VARCHAR(20),
     address TEXT
   );
   ```

2. **reservations** - Reservas activas
   ```sql
   CREATE TABLE reservations (
     id BIGINT PRIMARY KEY,
     customer_id BIGINT,
     branch_id BIGINT,
     reservation_date DATETIME,
     total_amount DECIMAL(10,2),
     status ENUM('pending', 'paid', 'cancelled')
   );
   ```

3. **reservation_items** - Productos reservados

Los seeders insertan:
- 5 clientes de ejemplo
- Reservas activas
- Items reservados

Verificamos:
```bash
curl http://localhost:8005/api/health
```"

---

## 👷 SECCIÓN 7: SERVICIO DE RECURSOS HUMANOS  
**Persona 6** (3 minutos)

### 7.1 Introducción Personal

"Hola, soy [Nombre] y explicaré el **servicio de recursos humanos**.

Este servicio controla:
- Asistencias de empleados
- Salarios base
- Ajustes salariales (bonos, descuentos)
- Reportes de HR"

---

### 7.2 Despliegue

"Comandos de inicialización:

```bash
cd servicio_de_recursos_humanos
docker compose build
docker compose up -d
docker exec hr_api composer install --no-dev --optimize-autoloader
docker exec hr_api php artisan key:generate
docker exec hr_api php artisan migrate --force
docker exec hr_api php artisan db:seed --force
cd ..
```

**Puerto**: `8006`  
**Base de datos**: `hr_db`"

---

### 7.3 Estructura de Datos

"Tablas principales:

1. **attendance_records** - Asistencias
   ```sql
   CREATE TABLE attendance_records (
     id BIGINT PRIMARY KEY,
     user_id BIGINT,
     attendance_date DATE,
     check_in_time TIME,
     check_out_time TIME,
     status ENUM('present', 'absent', 'late')
   );
   ```

2. **salaries** - Salarios base
3. **salary_adjustments** - Ajustes salariales
   ```sql
   CREATE TABLE salary_adjustments (
     id BIGINT PRIMARY KEY,
     user_id BIGINT,
     adjustment_type ENUM('bonus', 'deduction'),
     amount DECIMAL(10,2),
     reason TEXT
   );
   ```

Los seeders insertan:
- 6 empleados
- Registros de asistencia
- Ajustes salariales

Verificamos:
```bash
curl http://localhost:8006/api/health
```"

---

## ⚙️ SECCIÓN 8: SERVICIO DE CONFIGURACIÓN + API GATEWAY  
**Persona 7** (3 minutos)

### 8.1 Introducción - Servicio de Configuración

"Hola, soy [Nombre] y cerraré con el **servicio de configuración** y el **API Gateway**.

El servicio de configuración gestiona:
- Tasas de cambio de dólar
- Configuraciones del sistema
- Parámetros generales"

---

### 8.2 Despliegue del Servicio de Configuración

"Proceso estándar:

```bash
cd servicio_de_configuracion
docker compose build
docker compose up -d
docker exec config_api composer install --no-dev --optimize-autoloader
docker exec config_api php artisan key:generate
docker exec config_api php artisan migrate --force
docker exec config_api php artisan db:seed --force
cd ..
```

**Puerto**: `8007`  
**Base de datos**: `config_db`

Tablas:
- `usd_exchange_rates`: Tasas de cambio USD
- `system_settings`: Configuraciones

Los seeders insertan:
- 7 tasas de cambio (histórico + actual)
- 26 configuraciones del sistema

Verificamos:
```bash
curl http://localhost:8007/api/health
```"

---

### 8.3 API Gateway - Introducción

"Ahora llegamos a un componente crucial: el **API Gateway**.

¿Qué problema resuelve?

Sin gateway:
- El cliente necesita conocer 7 URLs diferentes
- Si un servicio cambia de IP/puerto, el cliente se rompe
- No hay un punto centralizado de seguridad
- Difícil implementar rate limiting, logging, caché

Con gateway:
- ✅ Un solo punto de entrada: `http://localhost:8000`
- ✅ Enrutamiento inteligente a cada servicio
- ✅ Seguridad centralizada
- ✅ Logging y monitoreo unificado
- ✅ Los microservicios pueden moverse sin afectar clientes"

---

### 8.4 Despliegue del Gateway

"El gateway es diferente:

```bash
cd api_gateway
docker compose build
docker compose up -d
docker exec gateway_api composer install --no-dev --optimize-autoloader
docker exec gateway_api php artisan key:generate
docker exec gateway_api grep "^APP_KEY=" .env
cd ..
```

**Diferencias importantes:**
- ❌ NO tiene base de datos
- ❌ NO ejecuta migraciones
- ❌ NO ejecuta seeders
- ✅ Solo actúa como proxy inteligente

**Puerto**: `8000` (punto de entrada principal)"

---

### 8.5 Funcionamiento del Gateway

"El gateway tiene un archivo `GatewayService.php`:

```php
private array $services = [
    'auth' => 'AUTH_SERVICE_URL',
    'branches' => 'BRANCH_SERVICE_URL',
    'inventory' => 'INVENTORY_SERVICE_URL',
    // ... otros servicios
];

public function proxy($service, $method, $path, $data, $token)
{
    $serviceUrl = env($this->services[$service]);
    $url = $serviceUrl . '/' . $path;
    
    $request = Http::timeout(30)
        ->withHeaders(['Accept' => 'application/json']);
    
    if ($token) {
        $request->withToken($token);
    }
    
    return $request->$method($url, $data);
}
```

**Flujo de una petición:**

1. Cliente: `GET http://localhost:8000/api/products`
2. Gateway recibe la petición
3. Gateway lee las rutas: `/products` → servicio de inventario
4. Gateway hace proxy: `GET http://inventory_nginx:80/api/products`
5. Inventario procesa y responde
6. Gateway devuelve la respuesta al cliente

El cliente nunca sabe que hay 7 servicios detrás, solo ve uno.

**Configuración de servicios en `.env`:**
```env
AUTH_SERVICE_URL=http://auth_nginx:80
INVENTORY_SERVICE_URL=http://inventory_nginx:80
SALES_SERVICE_URL=http://sales_nginx:80
# ... otros
```

Verificamos todos los servicios:
```bash
curl http://localhost:8000/api/health
```

Respuesta:
```json
{
  "gateway": "healthy",
  "services": {
    "auth": "healthy",
    "branches": "healthy",
    "inventory": "healthy",
    "sales": "healthy",
    "reservations": "healthy",
    "hr": "healthy",
    "config": "healthy"
  }
}
```

Esto confirma que los 7 microservicios están respondiendo correctamente."

---

## ✅ VERIFICACIÓN FINAL Y DEMOSTRACIÓN (Todos - 2-3 minutos)

> **Portavoz del equipo:**

"Para finalizar, haremos una **demostración en vivo** del sistema funcionando.

Verificamos todos los contenedores:
```bash
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
```

Vemos aproximadamente **30 contenedores** corriendo:
- 7 servicios API (PHP-FPM)
- 7 servicios Nginx
- 7 bases de datos master
- 7 bases de datos replica
- 1 API Gateway (PHP-FPM + Nginx)

Ahora probamos el flujo completo de autenticación:

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{\"email\": \"admin@ewtto.com\", \"password\": \"admin123\"}'
```

Respuesta:
```json
{
  \"access_token\": \"eyJ0eXAiOiJKV1QiLCJhbGc...\",
  \"token_type\": \"bearer\",
  \"expires_in\": 3600
}
```

Copiamos el token y consultamos usuarios:
```bash
curl http://localhost:8000/api/users \
  -H \"Authorization: Bearer {TOKEN}\"
```

Y vemos la lista de usuarios, confirmando que:
- ✅ La autenticación funciona
- ✅ Los tokens JWT se generan correctamente
- ✅ El gateway enruta correctamente
- ✅ Los microservicios responden
- ✅ La comunicación a través de la red Docker funciona"

---

## 🎓 CONCLUSIÓN (Todos - 2 minutos)

> **Portavoz del equipo:**

"En resumen, hemos implementado un **sistema distribuido de microservicios** con las siguientes características:

### Arquitectura:
- ✅ 7 microservicios independientes
- ✅ Patrón de base de datos por servicio
- ✅ Comunicación mediante APIs REST
- ✅ API Gateway como punto de entrada único

### Tecnologías:
- ✅ Docker para orquestación de contenedores
- ✅ Laravel 12 + PHP 8.3 para las APIs
- ✅ MySQL 8.0 con replicación master-slave
- ✅ Nginx como servidor web
- ✅ JWT para autenticación

### Beneficios de esta arquitectura:
1. **Escalabilidad**: Cada servicio puede escalar independientemente
2. **Resiliencia**: Si un servicio falla, los demás siguen funcionando
3. **Mantenibilidad**: Código organizado y desacoplado
4. **Alta disponibilidad**: Replicación de bases de datos
5. **Seguridad**: Gateway centraliza autenticación

### Desafíos superados:
- Configuración de replicación MySQL
- Comunicación entre contenedores
- Sincronización de datos
- Manejo de errores distribuidos

Este sistema está listo para producción y puede manejar las operaciones de una empresa comercial con múltiples sucursales.

¿Tienen alguna pregunta?"

---

## 📚 Notas Adicionales para los Presentadores

### Consejos Generales:
- Mantén contacto visual con la audiencia
- Habla despacio y claro
- Usa ejemplos concretos
- Si hay preguntas, responde con confianza

### Preguntas Frecuentes Anticipadas:

**P: ¿Por qué Laravel y no otro framework?**  
R: Laravel ofrece balance entre velocidad de desarrollo, performance y mantenibilidad. El equipo tiene experiencia previa y la comunidad es activa.

**P: ¿Por qué cada servicio tiene su propia BD en lugar de una centralizada?**  
R: Patrón de microservicios - cada servicio es autónomo y puede escalar/desplegarse independientemente.

**P: ¿Qué pasa si el gateway falla?**  
R: Es el single point of failure. En producción, se desplegarían múltiples instancias del gateway con un load balancer.

**P: ¿Cómo manejan las transacciones entre servicios?**  
R: Usando el patrón Saga - si una operación falla, se ejecutan compensaciones para revertir cambios en otros servicios.

**P: ¿El sistema está listo para producción?**  
R: Sí, pero recomendamos agregar: HTTPS, monitoreo (Prometheus), logs centralizados (ELK), backup automatizado.

---

**Fin del Guion** 🎬  
**¡Buena suerte en la presentación!** 🚀
