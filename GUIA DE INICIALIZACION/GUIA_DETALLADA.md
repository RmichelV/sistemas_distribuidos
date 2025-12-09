# 📖 Guía Detallada: Explicación Línea por Línea del Sistema de Microservicios E-WTTO

Esta guía explica cada comando ejecutado en la inicialización del sistema, qué hace exactamente y por qué es necesario.

---

## 🎯 Objetivo General

Desplegar un sistema de microservicios distribuido usando Docker, donde cada servicio tiene su propia base de datos con replicación master-slave, comunicándose a través de un API Gateway centralizado.

---

## 📚 Tabla de Contenidos

1. [Infraestructura Base](#infraestructura-base)
2. [Servicio de Autenticación](#servicio-de-autenticación)
3. [Servicio de Sucursales](#servicio-de-sucursales)
4. [Servicio de Inventario](#servicio-de-inventario)
5. [Servicio de Ventas](#servicio-de-ventas)
6. [Servicio de Reservaciones](#servicio-de-reservaciones)
7. [Servicio de Recursos Humanos](#servicio-de-recursos-humanos)
8. [Servicio de Configuración](#servicio-de-configuración)
9. [API Gateway](#api-gateway)

---

## 🏗️ INFRAESTRUCTURA BASE

### Paso 1: Crear la Red Docker

#### Comando:
```bash
cd /ruta/a/SIS
```

**¿Qué hace?**
- `cd`: Change Directory - cambia el directorio actual de trabajo
- `/ruta/a/SIS`: Ruta absoluta hacia la carpeta raíz del proyecto
- **Propósito**: Asegurarnos de estar en el directorio correcto donde están todos los scripts

---

#### Comando:
```bash
chmod +x create-network.sh
```

**¿Qué hace cada parte?**
- `chmod`: Change Mode - cambia permisos de archivos
- `+x`: Agrega permiso de ejecución
- `create-network.sh`: El archivo shell script al que le daremos permisos

**¿Por qué es necesario?**
- Por seguridad, los archivos `.sh` descargados no tienen permisos de ejecución
- Sin este comando, obtendríamos el error: "Permission denied"

---

#### Comando:
```bash
./create-network.sh
```

**¿Qué hace?**
- `./`: Ejecuta un archivo en el directorio actual
- `create-network.sh`: El script que creará nuestra red

**Dentro del script `create-network.sh`:**

```bash
#!/bin/bash
```
- Shebang: indica que el script debe ejecutarse con bash

```bash
docker network create sd_network
```
- `docker network create`: Comando de Docker para crear una red virtual
- `sd_network`: Nombre personalizado de nuestra red

**¿Por qué necesitamos una red Docker?**
- Por defecto, los contenedores están aislados y no pueden comunicarse
- Una red personalizada permite que contenedores se comuniquen por **nombre** en lugar de IP
- Ejemplo: `auth_api` puede conectarse a `sd_db_auth` directamente por nombre
- Es como crear una LAN virtual exclusiva para nuestros servicios

---

#### Verificación:
```bash
docker network ls | grep sd_network
```

**¿Qué hace cada parte?**
- `docker network ls`: Lista todas las redes Docker existentes
- `|`: Pipe - pasa la salida del comando anterior al siguiente
- `grep sd_network`: Filtra líneas que contengan "sd_network"

**Salida esperada:**
```
NETWORK ID     NAME         DRIVER    SCOPE
abc123def456   sd_network   bridge    local
```

---

### Paso 2: Iniciar Bases de Datos

#### Comando:
```bash
chmod +x start-all.sh
./start-all.sh
```

**Ya conocemos `chmod +x`, ahora veamos qué hace `start-all.sh`:**

**Dentro del script:**

```bash
cd "$(dirname "$0")"
```
- `dirname "$0"`: Obtiene el directorio donde está el script
- `cd`: Cambia a ese directorio
- **Propósito**: Asegura que el script funcione sin importar desde dónde se ejecute

---

```bash
./stop-all.sh 2>/dev/null || true
```
- `./stop-all.sh`: Ejecuta el script que detiene contenedores
- `2>/dev/null`: Redirige errores a /dev/null (los oculta)
- `||`: Operador OR - si el comando anterior falla...
- `true`: ...ejecuta `true` (evita que el script se detenga si stop-all.sh falla)
- **Propósito**: Limpiar contenedores previos sin causar errores si no existen

---

```bash
docker compose -f servicio_de_autenticacion_y_usuarios/sd_db_auth.yml up -d
```

**Desglose completo:**
- `docker compose`: Herramienta para definir y ejecutar aplicaciones multi-contenedor
- `-f`: Flag que especifica el archivo de configuración
- `servicio_de_autenticacion_y_usuarios/sd_db_auth.yml`: Ruta al archivo YAML de configuración
- `up`: Crea e inicia contenedores
- `-d`: Detached mode - ejecuta en segundo plano

**¿Qué contiene `sd_db_auth.yml`?**
Define:
- Imagen de MySQL 8.0
- Puerto 3306
- Variables de entorno (nombre de BD, contraseñas)
- Volúmenes para persistencia de datos
- Configuración de replicación (archivo master.cnf)

**¿Por qué `-d` (detached)?**
- Sin `-d`: La terminal quedaría bloqueada mostrando logs
- Con `-d`: El contenedor corre en background y recuperamos el control de la terminal

---

```bash
sleep 15
```
- `sleep`: Pausa la ejecución del script
- `15`: Número de segundos a esperar

**¿Por qué esperar?**
- MySQL necesita tiempo para:
  1. Inicializar el directorio de datos
  2. Crear las bases de datos
  3. Configurar usuarios
  4. Estar listo para aceptar conexiones
- Si ejecutamos replicación antes, fallaría porque el master no está listo

---

**Luego inicia las 7 réplicas con la misma estructura:**

```bash
docker compose -f servicio_de_autenticacion_y_usuarios/sd_db_auth_replica.yml up -d
```

**Diferencias entre master y replica:**
- **Puerto diferente**: 3307 en lugar de 3306
- **Archivo de configuración**: `replica.cnf` en lugar de `master.cnf`
- **Modo solo lectura**: Las réplicas no aceptan escrituras

---

### Paso 3: Configurar Replicación

#### Comando:
```bash
chmod +x setup-replication.sh
./setup-replication.sh
```

**Dentro del script `setup-replication.sh`:**

```bash
setup_replication() {
    local master_container=$1
    local replica_container=$2
    local db_name=$3
```
- `setup_replication()`: Define una función reutilizable
- `local`: Variables locales de la función
- `$1, $2, $3`: Parámetros recibidos (contenedor master, replica, nombre de BD)

---

**Paso 1 dentro de la función: Crear usuario de replicación**

```bash
docker exec -i $master_container mysql -uroot -p3312 <<EOF
CREATE USER IF NOT EXISTS 'replicator'@'%' IDENTIFIED WITH mysql_native_password BY 'replicator_password';
GRANT REPLICATION SLAVE ON *.* TO 'replicator'@'%';
FLUSH PRIVILEGES;
EOF
```

**Desglose:**
- `docker exec`: Ejecuta un comando dentro de un contenedor en ejecución
- `-i`: Interactive - mantiene STDIN abierto
- `$master_container`: Nombre del contenedor (variable)
- `mysql -uroot -p3312`: Cliente MySQL con usuario root y contraseña 3312
- `<<EOF ... EOF`: Here document - permite escribir múltiples líneas de SQL

**Comandos SQL ejecutados:**

1. `CREATE USER IF NOT EXISTS 'replicator'@'%'...`
   - Crea usuario `replicator` si no existe
   - `@'%'`: Puede conectarse desde cualquier host
   - `IDENTIFIED WITH mysql_native_password`: Método de autenticación
   - `BY 'replicator_password'`: Contraseña del usuario

2. `GRANT REPLICATION SLAVE ON *.* TO 'replicator'@'%'`
   - Otorga permisos de replicación
   - `ON *.*`: En todas las bases de datos y tablas
   - **Propósito**: Permite que la réplica lea el binlog del master

3. `FLUSH PRIVILEGES`
   - Recarga las tablas de permisos
   - **Propósito**: Aplica los cambios de permisos inmediatamente

---

**Paso 2: Obtener posición del binlog**

```bash
MASTER_STATUS=$(docker exec -i $master_container mysql -uroot -p3312 -e "SHOW MASTER STATUS\G")
```

- `SHOW MASTER STATUS\G`: Muestra el estado actual del binlog
- `\G`: Formato vertical (más legible)
- `$()`: Captura la salida del comando en la variable MASTER_STATUS

**Salida típica:**
```
*************************** 1. row ***************************
             File: mysql-bin.000003
         Position: 157
     Binlog_Do_DB: 
 Binlog_Ignore_DB: 
Executed_Gtid_Set:
```

---

```bash
MASTER_LOG_FILE=$(echo "$MASTER_STATUS" | grep "File:" | awk '{print $2}')
```

**Desglose:**
- `echo "$MASTER_STATUS"`: Imprime el contenido de la variable
- `grep "File:"`: Filtra la línea que contiene "File:"
- `awk '{print $2}'`: Imprime la segunda columna (el nombre del archivo)
- Resultado: `mysql-bin.000003`

```bash
MASTER_LOG_POS=$(echo "$MASTER_STATUS" | grep "Position:" | awk '{print $2}')
```
- Similar al anterior, pero extrae la posición numérica
- Resultado: `157`

**¿Por qué necesitamos estos valores?**
- El binlog (binary log) registra todos los cambios en la BD
- La réplica necesita saber **desde dónde** empezar a leer
- Es como decir: "empieza a copiar desde la página 157 del libro 3"

---

**Paso 3: Configurar la réplica**

```bash
docker exec -i $replica_container mysql -uroot -p3312 <<EOF
STOP SLAVE;
CHANGE MASTER TO
    MASTER_HOST='$master_container',
    MASTER_USER='replicator',
    MASTER_PASSWORD='replicator_password',
    MASTER_LOG_FILE='$MASTER_LOG_FILE',
    MASTER_LOG_POS=$MASTER_LOG_POS;
START SLAVE;
SET GLOBAL read_only = 1;
SET GLOBAL super_read_only = 1;
EOF
```

**Comandos SQL ejecutados:**

1. `STOP SLAVE`
   - Detiene la replicación si ya estaba corriendo
   - **Propósito**: Permite reconfigurar sin conflictos

2. `CHANGE MASTER TO ...`
   - `MASTER_HOST`: Nombre del contenedor master (se comunican por red Docker)
   - `MASTER_USER`: Usuario que creamos antes (replicator)
   - `MASTER_PASSWORD`: Contraseña del usuario
   - `MASTER_LOG_FILE`: Archivo binlog desde donde empezar
   - `MASTER_LOG_POS`: Posición exacta en el archivo
   - **Propósito**: Establece la conexión con el master

3. `START SLAVE`
   - Inicia el proceso de replicación
   - Dos hilos se ejecutan:
     - **IO Thread**: Lee el binlog del master
     - **SQL Thread**: Ejecuta los comandos en la réplica

4. `SET GLOBAL read_only = 1`
   - Hace que la réplica sea solo lectura
   - **Propósito**: Evita escrituras accidentales en la réplica

5. `SET GLOBAL super_read_only = 1`
   - Incluso usuarios con privilegio SUPER no pueden escribir
   - **Propósito**: Protección adicional

---

**Paso 4: Verificar replicación**

```bash
SLAVE_STATUS=$(docker exec -i $replica_container mysql -uroot -p3312 -e "SHOW SLAVE STATUS\G")
```
- Obtiene el estado de replicación de la réplica

```bash
IO_RUNNING=$(echo "$SLAVE_STATUS" | grep "Slave_IO_Running:" | awk '{print $2}')
SQL_RUNNING=$(echo "$SLAVE_STATUS" | grep "Slave_SQL_Running:" | awk '{print $2}')
```
- Extrae el estado de ambos hilos

```bash
if [ "$IO_RUNNING" == "Yes" ] && [ "$SQL_RUNNING" == "Yes" ]; then
    echo "✓ Replicación configurada correctamente"
else
    echo "✗ Error en la replicación"
fi
```
- `[ ... ]`: Condición de prueba en bash
- `==`: Comparación de cadenas
- `&&`: Operador AND lógico
- **Propósito**: Validar que ambos hilos estén corriendo

---

#### Verificación de replicación:

```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "SHOW REPLICA STATUS\G" 2>/dev/null | grep -E "Replica_IO_Running|Replica_SQL_Running"
```

**Desglose:**
- `docker exec`: Ejecuta comando en contenedor
- `sd_db_auth_replica`: Contenedor de la réplica
- `mysql -uroot -p3312`: Cliente MySQL
- `-e "SHOW REPLICA STATUS\G"`: Ejecuta un solo comando SQL
- `2>/dev/null`: Oculta el warning de contraseña en línea de comandos
- `grep -E`: Grep con expresiones regulares extendidas
- `"Replica_IO_Running|Replica_SQL_Running"`: Busca cualquiera de estas dos cadenas

**Salida esperada:**
```
           Replica_IO_Running: Yes
          Replica_SQL_Running: Yes
```

**¿Qué significa cada estado?**
- **Replica_IO_Running: Yes** → El hilo de IO está leyendo el binlog del master
- **Replica_SQL_Running: Yes** → El hilo SQL está ejecutando los comandos
- Si alguno es "No" → Hay un problema de replicación

---

## 🔐 SERVICIO 1: AUTENTICACIÓN Y USUARIOS

### Comandos de Inicialización:

```bash
cd servicio_de_autenticacion_y_usuarios/api
```
- Navega al directorio del servicio de autenticación
- Dentro hay un `docker-compose.yml` y código Laravel

---

```bash
docker compose build
```

**¿Qué hace `build`?**
- Lee el `Dockerfile` del servicio
- Construye una imagen Docker personalizada
- Instala PHP 8.3, extensiones, Composer
- Copia el código de la aplicación
- Crea un snapshot reutilizable (imagen)

**Dentro del Dockerfile:**

```dockerfile
FROM php:8.3-fpm
```
- Imagen base: PHP 8.3 con FastCGI Process Manager
- FPM se usa con Nginx para servir aplicaciones PHP

```dockerfile
RUN apt-get update && apt-get install -y \
    git curl libpng-dev ... \
    && apt-get clean
```
- `RUN`: Ejecuta comandos durante la construcción
- `apt-get update`: Actualiza lista de paquetes
- `apt-get install -y`: Instala dependencias del sistema
- `&& apt-get clean`: Limpia caché para reducir tamaño de imagen
- `\`: Continúa el comando en la siguiente línea

```dockerfile
RUN docker-php-ext-install pdo_mysql mbstring ...
```
- Instala extensiones PHP necesarias
- `pdo_mysql`: Para conectarse a MySQL
- `mbstring`: Manejo de cadenas multibyte
- `bcmath`: Matemáticas de precisión arbitraria

```dockerfile
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
```
- Multi-stage build: Copia Composer de otra imagen
- **Ventaja**: No necesitamos instalar Composer manualmente

```dockerfile
RUN useradd -G www-data,root -u $uid -d /home/$user $user
```
- Crea un usuario no-root para ejecutar la aplicación
- **Seguridad**: No ejecutar como root dentro del contenedor

```dockerfile
WORKDIR /var/www
```
- Establece el directorio de trabajo
- Todos los comandos siguientes se ejecutan aquí

```dockerfile
COPY composer.json ./
RUN composer install --no-scripts --prefer-dist
```
- Copia solo `composer.json` primero
- Instala dependencias
- **Optimización**: Aprovecha caché de capas Docker
- Si el código cambia pero composer.json no, usa caché

```dockerfile
COPY . .
```
- Copia todo el código de la aplicación

```dockerfile
RUN chown -R $user:www-data /var/www
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache
```
- `chown`: Cambia propietario de archivos
- `chmod 775`: Permisos de lectura/escritura/ejecución
- **Propósito**: Laravel necesita escribir en storage/ y cache/

---

```bash
docker compose up -d
```

**¿Qué hace `up`?**
- Lee `docker-compose.yml`
- Crea contenedores basados en las imágenes
- Inicia los contenedores
- `-d`: Modo detached (segundo plano)

**Dentro de `docker-compose.yml`:**

```yaml
version: '3.8'
```
- Versión de la sintaxis de Docker Compose

```yaml
services:
  auth_api:
    build:
      context: .
      dockerfile: Dockerfile
```
- Define un servicio llamado `auth_api`
- `context: .`: Directorio actual para el build
- `dockerfile: Dockerfile`: Archivo a usar

```yaml
    container_name: auth_api
```
- Nombre exacto del contenedor (en lugar de uno autogenerado)

```yaml
    restart: unless-stopped
```
- Política de reinicio:
  - Si el contenedor falla → se reinicia automáticamente
  - Si lo detenemos manualmente → no se reinicia
  - Al reiniciar Docker → se reinicia el contenedor

```yaml
    working_dir: /var/www
```
- Directorio donde se ejecutan los comandos

```yaml
    volumes:
      - ./:/var/www
```
- Monta el directorio local `./` en `/var/www` del contenedor
- **Beneficio**: Cambios en código local se reflejan inmediatamente
- **Desarrollo**: No necesitas reconstruir la imagen por cada cambio

```yaml
    networks:
      - sd_network
```
- Conecta el contenedor a nuestra red personalizada
- Puede comunicarse con `sd_db_auth`, `sd_db_auth_replica`, etc.

```yaml
    environment:
      - DB_HOST=sd_db_auth
      - DB_DATABASE=auth_db
      - DB_USERNAME=root
      - DB_PASSWORD=3312
```
- Variables de entorno que Laravel leerá
- `DB_HOST=sd_db_auth`: Nombre del contenedor de base de datos
- Estas sobrescriben valores del archivo `.env`

```yaml
  auth_nginx:
    image: nginx:alpine
```
- Segundo servicio: Nginx
- `alpine`: Versión ligera de Linux (5 MB vs 100+ MB)

```yaml
    ports:
      - "8001:80"
```
- Mapeo de puertos: `host:contenedor`
- Puerto 8001 en tu máquina → Puerto 80 del contenedor
- **Resultado**: `localhost:8001` accede al servicio

```yaml
    depends_on:
      - auth_api
```
- Nginx espera a que `auth_api` se inicie primero
- **Propósito**: Nginx necesita que PHP-FPM esté corriendo

---

```bash
docker exec auth_api composer install --no-dev --optimize-autoloader
```

**Desglose:**
- `docker exec auth_api`: Ejecuta comando en el contenedor `auth_api`
- `composer install`: Instala dependencias PHP del `composer.json`
- `--no-dev`: No instala dependencias de desarrollo (phpunit, debuggers)
- `--optimize-autoloader`: Genera autoloader optimizado para producción
  - Crea un mapa de clases para carga más rápida
  - **Beneficio**: Mejora performance en ~30%

---

```bash
docker exec auth_api php artisan key:generate
```

**¿Qué hace este comando?**
- `php artisan`: CLI de Laravel
- `key:generate`: Genera una clave de aplicación aleatoria

**¿Para qué sirve la APP_KEY?**
- Cifrado de sesiones
- Cifrado de cookies
- Tokens CSRF
- Hashing de contraseñas

**¿Dónde se guarda?**
- En el archivo `.env` del contenedor
- Formato: `APP_KEY=base64:randomstring...`

**Sin esta clave:**
- Laravel muestra error: "No application encryption key has been specified"
- No funcionan sesiones ni autenticación

---

```bash
docker exec auth_api grep "^APP_KEY=" .env
```

**Verificación de la clave:**
- `grep "^APP_KEY="`: Busca líneas que empiecen con "APP_KEY="
- `^`: Inicio de línea (regex)
- **Salida esperada**: `APP_KEY=base64:Abc123...`

---

```bash
docker exec auth_api php artisan migrate --force
```

**¿Qué hace `migrate`?**
- Lee archivos en `database/migrations/`
- Ejecuta SQL para crear/modificar tablas
- Registra qué migraciones se ejecutaron en tabla `migrations`

**Archivos de migración típicos:**
```php
// 2025_12_09_000001_create_roles_table.php
Schema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
});
```

**¿Qué hace `--force`?**
- Laravel pregunta confirmación en producción
- `--force`: Ejecuta sin preguntar
- **Uso**: Necesario en contenedores Docker

**Resultado:**
- Crea tablas: `roles`, `users`, `cache`
- Estructura de BD lista para usar

---

```bash
docker exec auth_api php artisan db:seed --force
```

**¿Qué hace `db:seed`?**
- Lee archivos en `database/seeders/`
- Inserta datos de prueba en las tablas

**Ejemplo de seeder:**
```php
// RoleSeeder.php
DB::table('roles')->insert([
    ['name' => 'admin'],
    ['name' => 'manager'],
    ['name' => 'employee'],
]);
```

**Resultado:**
- Usuario admin creado: `admin@ewtto.com` / `admin123`
- 5 roles insertados
- Datos listos para probar el sistema

---

```bash
cd ../..
```
- Regresa al directorio raíz `SIS/`
- **Propósito**: Estar listos para el siguiente servicio

---

### Verificación:

```bash
curl http://localhost:8001/api/health
```

**¿Qué hace `curl`?**
- Cliente HTTP de línea de comandos
- `http://localhost:8001`: URL del servicio
- `/api/health`: Endpoint de verificación

**Respuesta esperada:**
```json
{
  "success": true,
  "service": "auth-service",
  "status": "healthy",
  "timestamp": "2025-12-09T15:30:00.000000Z"
}
```

**¿Qué valida esto?**
- ✅ Nginx está corriendo
- ✅ PHP-FPM está corriendo
- ✅ Laravel responde
- ✅ Puerto 8001 está abierto
- ✅ Red Docker funciona

---

## 🏢 SERVICIO 2: SUCURSALES

### Comandos:

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

**Todos los comandos son idénticos al servicio 1, con diferencias:**

1. **Nombre del contenedor**: `branch_api` en lugar de `auth_api`
2. **Puerto**: `8002` en lugar de `8001`
3. **Base de datos**: `sd_db_branches` / `branches_db`
4. **Migraciones**: Solo crea tabla `branches`
5. **Seeders**: Inserta 3 sucursales de prueba

**Archivo de migración específico:**
```php
// create_branches_table.php
Schema::create('branches', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('address');
    $table->string('phone');
    $table->timestamps();
});
```

**Seeder específico:**
```php
// BranchSeeder.php
DB::table('branches')->insert([
    ['name' => 'Sucursal Centro', 'address' => 'Av. Principal 123'],
    ['name' => 'Sucursal Norte', 'address' => 'Calle Norte 456'],
    ['name' => 'Sucursal Sur', 'address' => 'Av. Sur 789'],
]);
```

---

## 📦 SERVICIO 3: INVENTARIO

### Comandos adicionales:

```bash
docker exec inventory_api php artisan key:generate
docker exec inventory_api grep "^APP_KEY=" .env
```

**¿Por qué agregamos `grep` aquí?**
- Para verificar visualmente que la clave se generó
- Útil para debugging si algo falla después
- Confirma que el archivo `.env` es escribible

**Diferencias en este servicio:**

1. **Puerto**: `8003`
2. **Base de datos**: `inventory_db`
3. **Migraciones**: Crea 7 tablas:
   - `products`: Catálogo de productos
   - `product_stores`: Stock en tienda
   - `product_branches`: Stock por sucursal
   - `purchases`: Compras a proveedores
   - `roles`: Roles locales del servicio
   - `users`: Usuarios locales del servicio
   - `cache`: Caché de Laravel

4. **Seeders**:
   - 10 productos de ejemplo
   - Registros de stock
   - Compras de prueba
   - Usuarios y roles

**¿Por qué este servicio tiene `users` y `roles`?**
- Arquitectura de microservicios autónomos
- Cada servicio puede autenticar independientemente
- Si el servicio de Auth cae, Inventory sigue funcionando
- **Trade-off**: Duplicación de datos vs autonomía

---

## 💰 SERVICIO 4: VENTAS

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

**Diferencias:**

1. **Puerto**: `8004`
2. **Base de datos**: `sales_db`
3. **Migraciones**: 6 tablas:
   - `sales`: Registro de ventas
   - `sale_items`: Items de cada venta
   - `devolutions`: Devoluciones
   - `roles`, `users`, `cache`

4. **Endpoints API**:
   - `GET /api/sales` - Lista de ventas
   - `POST /api/sales` - Registrar venta
   - `GET /api/sales/{id}` - Detalle de venta
   - `GET /api/devolutions` - Lista de devoluciones
   - `POST /api/devolutions` - Registrar devolución

---

## 👥 SERVICIO 5: RESERVACIONES

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

**Diferencias:**

1. **Puerto**: `8005`
2. **Base de datos**: `reservations_db`
3. **Migraciones**:
   - `customers`: Clientes del sistema
   - `reservations`: Reservas activas
   - `reservation_items`: Items de cada reserva
   - `roles`, `users`, `cache`

4. **Seeders**:
   - 5 clientes de prueba
   - Reservas activas
   - Items de reserva

---

## 👷 SERVICIO 6: RECURSOS HUMANOS

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

**Diferencias:**

1. **Puerto**: `8006`
2. **Base de datos**: `hr_db`
3. **Migraciones**:
   - `attendance_records`: Asistencias de empleados
   - `salaries`: Salarios base
   - `salary_adjustments`: Ajustes salariales
   - `roles`, `users`, `cache`

4. **Seeders**:
   - 6 empleados
   - Registros de asistencia
   - Ajustes salariales

---

## ⚙️ SERVICIO 7: CONFIGURACIÓN

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

**Diferencias:**

1. **Puerto**: `8007`
2. **Base de datos**: `config_db`
3. **Migraciones**:
   - `usd_exchange_rates`: Tasas de cambio USD
   - `system_settings`: Configuraciones del sistema
   - `roles`, `users`, `cache`

4. **Seeders**:
   - 7 tasas de cambio (histórico + actual)
   - 26 configuraciones del sistema
   - Parámetros generales

---

## 🌐 API GATEWAY

### Comandos:

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

1. **NO tiene base de datos**: El gateway no almacena datos
2. **NO ejecuta migraciones**: No hay tablas que crear
3. **NO ejecuta seeders**: No hay datos que insertar
4. **Puerto**: `8000` (punto de entrada principal)

**¿Qué hace el gateway?**

**Archivo: `GatewayService.php`**
```php
public function proxy(string $service, string $method, string $path, array $data = [], ?string $token = null)
{
    $serviceUrl = env('AUTH_SERVICE_URL'); // http://auth_api:8001
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
3. Gateway identifica que `/products` va a Inventory Service
4. Gateway reenvía: `GET http://inventory_api:8001/api/products`
5. Inventory responde al Gateway
6. Gateway devuelve la respuesta al cliente

**Variables de entorno del gateway:**
```env
AUTH_SERVICE_URL=http://auth_nginx:80
BRANCH_SERVICE_URL=http://branch_nginx:80
INVENTORY_SERVICE_URL=http://inventory_nginx:80
SALES_SERVICE_URL=http://sales_nginx:80
RESERVATIONS_SERVICE_URL=http://reservations_nginx:80
HR_SERVICE_URL=http://hr_nginx:80
CONFIG_SERVICE_URL=http://config_nginx:80
```

**¿Por qué `nginx` en lugar de `api`?**
- Nginx escucha en puerto 80
- Nginx pasa las peticiones a PHP-FPM
- **Arquitectura**: Cliente → Gateway → Nginx → PHP-FPM → Laravel

---

### Verificación del Gateway:

```bash
curl http://localhost:8000/api/health
```

**Respuesta esperada:**
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

**¿Qué hace este endpoint?**
```php
// GatewayController.php
public function health()
{
    $services = ['auth', 'branches', 'inventory', ...];
    
    foreach ($services as $name => $url) {
        try {
            $response = Http::timeout(2)->get($url . '/api/health');
            $status[$name] = $response->successful() ? 'healthy' : 'unhealthy';
        } catch (\Exception $e) {
            $status[$name] = 'unreachable';
        }
    }
    
    return response()->json(['gateway' => 'healthy', 'services' => $status]);
}
```

**Validaciones:**
- ✅ Gateway está respondiendo
- ✅ Gateway puede comunicarse con todos los servicios
- ✅ Todos los servicios están saludables
- ✅ Red Docker funciona correctamente

---

## ✅ VERIFICACIÓN FINAL

### 1. Verificar contenedores:

```bash
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
```

**¿Qué hace cada parte?**
- `docker ps`: Lista contenedores en ejecución
- `--format`: Formato personalizado de salida
- `"table {{.Names}}\t{{.Status}}\t{{.Ports}}"`: Plantilla Go
  - `{{.Names}}`: Nombre del contenedor
  - `\t`: Tabulación
  - `{{.Status}}`: Estado (Up X minutes, etc.)
  - `{{.Ports}}`: Mapeo de puertos

**Salida esperada:**
```
NAMES                   STATUS                  PORTS
gateway_api             Up 2 minutes            9000/tcp
gateway_nginx           Up 2 minutes            0.0.0.0:8000->80/tcp
auth_api                Up 10 minutes           9000/tcp
auth_nginx              Up 10 minutes           0.0.0.0:8001->80/tcp
...
sd_db_auth              Up 1 hour (healthy)     3306/tcp
sd_db_auth_replica      Up 1 hour (healthy)     3307/tcp
...
```

**Total esperado: ~30 contenedores**
- 7 servicios API (PHP-FPM): 7
- 7 servicios Nginx: 7
- 7 bases de datos master: 7
- 7 bases de datos replica: 7
- 1 API Gateway (PHP-FPM): 1
- 1 API Gateway (Nginx): 1
- **Total**: 30 contenedores

---

### 2. Prueba de autenticación:

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@ewtto.com", "password": "admin123"}'
```

**Desglose:**
- `curl`: Cliente HTTP
- `-X POST`: Método HTTP POST
- `http://localhost:8000/api/auth/login`: URL del endpoint
- `-H "Content-Type: application/json"`: Header que indica JSON
- `-d '{"email": ..., "password": ...}'`: Datos del cuerpo (body)

**Flujo:**
1. Gateway recibe POST `/api/auth/login`
2. Gateway identifica que va a Auth Service
3. Gateway reenvía a `http://auth_nginx:80/api/auth/login`
4. Auth Service valida credenciales
5. Auth Service genera JWT
6. Gateway devuelve respuesta

**Respuesta:**
```json
{
  "success": true,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

**Componentes del JWT:**
- `eyJ0eXAiOi...`: Token firmado con la APP_KEY
- Contiene: `user_id`, `email`, `role_id`, `exp` (expiración)
- **Duración**: 3600 segundos (1 hora)

---

### 3. Usar el token:

```bash
curl http://localhost:8000/api/users \
  -H "Authorization: Bearer {TU_TOKEN_AQUI}"
```

**¿Qué pasa internamente?**
1. Gateway recibe GET `/api/users` con header Authorization
2. Gateway extrae el token del header
3. Gateway reenvía a Auth Service **con el mismo token**
4. Auth Service valida el JWT:
   - Verifica firma con APP_KEY
   - Verifica que no haya expirado
   - Extrae user_id del payload
5. Auth Service busca el usuario en BD
6. Auth Service devuelve lista de usuarios
7. Gateway devuelve la respuesta

---

## 🎓 Conceptos Clave Resumidos

### Docker
- **Contenedor**: Proceso aislado con su propio sistema de archivos
- **Imagen**: Plantilla para crear contenedores
- **Volumen**: Persistencia de datos entre reinicios
- **Red**: Comunicación entre contenedores

### Laravel
- **Artisan**: CLI para tareas comunes
- **Migraciones**: Control de versiones de BD
- **Seeders**: Datos de prueba
- **APP_KEY**: Clave de cifrado de la aplicación

### MySQL Replication
- **Master**: Base de datos de escritura
- **Replica**: Copia de solo lectura
- **Binlog**: Registro de cambios para replicar
- **IO Thread**: Lee el binlog del master
- **SQL Thread**: Ejecuta comandos en la replica

### Microservicios
- **Autonomía**: Cada servicio funciona independientemente
- **Base de datos por servicio**: No comparten BD
- **API Gateway**: Punto de entrada único
- **Comunicación**: Via HTTP/REST

---

## 📌 Troubleshooting Común

### Error: "Cannot connect to Docker daemon"
```bash
sudo systemctl start docker  # Linux
open -a Docker  # macOS
```

### Error: "Port already in use"
```bash
lsof -i :8001  # Ver qué proceso usa el puerto
kill -9 <PID>  # Matar el proceso
```

### Error: "No application encryption key"
```bash
docker exec auth_api php artisan key:generate
```

### Error: "Access denied for user"
- Verificar credenciales en `.env`
- Verificar que la BD esté corriendo: `docker ps`

### Contenedor se detiene inmediatamente
```bash
docker logs auth_api  # Ver logs de error
```

---

**Fin de la Guía Detallada** 🎉
