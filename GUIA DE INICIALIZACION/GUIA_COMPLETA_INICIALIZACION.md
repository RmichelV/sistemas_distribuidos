# 🚀 Guía de Inicialización del Sistema de Microservicios E-WTTO

Esta guía detalla el proceso completo para inicializar todo el sistema de microservicios desde cero.

## 📋 Requisitos Previos

- Docker instalado y en ejecución
- Docker Compose instalado
- Puertos disponibles: 8000-8007, 3306-3320, 8080
- Terminal bash/zsh

---

## 🗂️ Arquitectura del Sistema

El sistema consta de:
- **7 Microservicios API** (puertos 8001-8007)
- **7 Bases de datos MySQL Master** (puertos 3306, 3308, 3310, 3312, 3314, 3316, 3318)
- **7 Bases de datos MySQL Réplica** (puertos 3307, 3309, 3311, 3313, 3315, 3317, 3319)
- **1 API Gateway** (puerto 8000)
- **1 phpMyAdmin** (puerto 8080)

---

## 📝 Pasos de Inicialización

### Paso 1️⃣: Crear la Red Docker

```bash
cd /ruta/a/SIS
chmod +x create-network.sh
./create-network.sh
```

**Qué hace**: Crea la red `sd_network` que conecta todos los contenedores.

**Verificación**:
```bash
docker network ls | grep sd_network
```

---

### Paso 2️⃣: Iniciar Contenedores de Bases de Datos

```bash
chmod +x start-all.sh
./start-all.sh
```

**Qué hace**: 
- Inicia 7 bases de datos MASTER
- Inicia 7 bases de datos REPLICA
- Espera 30 segundos para que las bases se inicialicen completamente

**Verificación**:
```bash
docker ps | grep sd_db
```

Deberías ver 14 contenedores corriendo (7 master + 7 réplica).

---

### Paso 3️⃣: Configurar Replicación MySQL Master-Slave

```bash
chmod +x setup-replication.sh
./setup-replication.sh
```

**Qué hace**: Configura la replicación entre cada base de datos master y su réplica.

**Verificación**:
```bash
# Verificar estado de replicación en una réplica
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "SHOW REPLICA STATUS\G" 2>/dev/null | grep -E "Replica_IO_Running|Replica_SQL_Running"
```

Deberías ver:
```
           Replica_IO_Running: Yes
          Replica_SQL_Running: Yes
```

**Nota**: El `2>/dev/null` oculta el warning de seguridad de MySQL al usar contraseña en la línea de comandos.

---

### Paso 4️⃣: Construir e Iniciar Microservicios API

#### 4.1. Servicio de Autenticación (Puerto 8001)

```bash
cd servicio_de_autenticacion_y_usuarios/api
docker compose build
docker compose up -d
docker exec auth_api composer install --no-dev --optimize-autoloader
docker exec auth_api php artisan key:generate
docker exec auth_api php artisan migrate --force
docker exec auth_api php artisan db:seed --force
cd ../..
```

**Verificación**:
```bash
curl http://localhost:8001/api/health
```

---

#### 4.2. Servicio de Sucursales (Puerto 8002)

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

**Verificación**:
```bash
curl http://localhost:8002/api/health
```

---

#### 4.3. Servicio de Inventario (Puerto 8003)

```bash
cd servicio_de_inventario
docker compose build
docker compose up -d
docker exec inventory_api composer install --no-dev --optimize-autoloader
docker exec inventory_api php artisan key:generate

docker exec inventory_api grep "^APP_KEY=" .env

docker exec inventory_api php artisan migrate --force
docker exec inventory_api php artisan db:seed --force
cd ..
```

**Verificación**:
```bash
curl http://localhost:8003/api/health
```

---

#### 4.4. Servicio de Ventas (Puerto 8004)

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

**Verificación**:
```bash
curl http://localhost:8004/api/health
```

---

#### 4.5. Servicio de Clientes y Reservaciones (Puerto 8005)

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

**Verificación**:
```bash
curl http://localhost:8005/api/health
```

---

#### 4.6. Servicio de Recursos Humanos (Puerto 8006)

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

**Verificación**:
```bash
curl http://localhost:8006/api/health
```

---

#### 4.7. Servicio de Configuración (Puerto 8007)

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

**Verificación**:
```bash
curl http://localhost:8007/api/health
```

---

### Paso 5️⃣: Iniciar API Gateway (Puerto 8000)

```bash
cd api_gateway
docker compose build
docker compose up -d
docker exec gateway_api composer install --no-dev --optimize-autoloader
docker exec gateway_api php artisan key:generate
cd ..
```

**Verificación**:
```bash
curl http://localhost:8000/api/health
```

Deberías ver el estado de todos los servicios como `healthy`.

---

## ✅ Verificación Final del Sistema

### 1. Verificar todos los contenedores

```bash
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
```

Deberías ver aproximadamente 30 contenedores corriendo.

### 2. Probar el API Gateway

#### Login (obtener token):
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@ewtto.com", "password": "admin123"}'
```

Copia el `access_token` de la respuesta.

#### Probar endpoint protegido:
```bash
curl http://localhost:8000/api/users \
  -H "Authorization: Bearer {TU_TOKEN_AQUI}"
```

#### Probar endpoint público:
```bash
curl http://localhost:8000/api/exchange-rates/current
```

---

## 🌐 Acceso a los Servicios

### API Gateway (Punto de entrada único)
- **URL**: `http://localhost:8000`
- **Health Check**: `http://localhost:8000/api/health`

### Microservicios Individuales
- **Auth**: `http://localhost:8001`
- **Branches**: `http://localhost:8002`
- **Inventory**: `http://localhost:8003`
- **Sales**: `http://localhost:8004`
- **Reservations**: `http://localhost:8005`
- **HR**: `http://localhost:8006`
- **Config**: `http://localhost:8007`

### phpMyAdmin
- **URL**: `http://localhost:8080`
- **Servidor**: Nombre del contenedor (ej: `sd_db_auth`)
- **Usuario**: `root`
- **Contraseña**: `3312`

---

## 🔑 Credenciales del Sistema

### Usuario Administrador
- **Email**: `admin@ewtto.com`
- **Password**: `admin123`

### Base de Datos (todas)
- **Usuario**: `root`
- **Contraseña**: `3312`

### Usuario de Replicación
- **Usuario**: `replicator`
- **Contraseña**: `replicator_password`

---

## 📊 Datos de Prueba Cargados

Cada servicio tiene datos de prueba cargados automáticamente:

- **Auth**: 1 usuario admin, 5 roles
- **Branches**: 3 sucursales
- **Inventory**: 10 productos, registros de stock, compras
- **Sales**: Ventas de ejemplo con items
- **Reservations**: 5 clientes, reservas activas
- **HR**: 6 empleados, registros de asistencia, ajustes salariales
- **Config**: 7 tasas de cambio (histórico + actual), 26 configuraciones del sistema

---

## 🛑 Detener el Sistema

### Detener todos los servicios
```bash
./stop-all.sh
```

### Detener servicios API
```bash
cd servicio_de_autenticacion_y_usuarios && docker compose down && cd ..
cd servicio_de_sucursales && docker compose down && cd ..
cd servicio_de_inventario && docker compose down && cd ..
cd servicio_de_ventas && docker compose down && cd ..
cd servicio_de_clientes_y_reservaciones && docker compose down && cd ..
cd servicio_de_recursos_humanos && docker compose down && cd ..
cd servicio_de_configuracion && docker compose down && cd ..
cd api_gateway && docker compose down && cd ..
```

---

## 🗑️ Limpieza Completa del Sistema

**⚠️ ADVERTENCIA**: Esto eliminará TODOS los contenedores, imágenes y datos.

```bash
# Detener todos los contenedores
./stop-all.sh
docker stop $(docker ps -aq) 2>/dev/null

# Eliminar contenedores de servicios API
cd servicio_de_autenticacion_y_usuarios && docker compose down -v && cd ..
cd servicio_de_sucursales && docker compose down -v && cd ..
cd servicio_de_inventario && docker compose down -v && cd ..
cd servicio_de_ventas && docker compose down -v && cd ..
cd servicio_de_clientes_y_reservaciones && docker compose down -v && cd ..
cd servicio_de_recursos_humanos && docker compose down -v && cd ..
cd servicio_de_configuracion && docker compose down -v && cd ..
cd api_gateway && docker compose down -v && cd ..

# Eliminar todos los contenedores
docker rm -f $(docker ps -aq) 2>/dev/null

# Eliminar la red
docker network rm sd_network 2>/dev/null

# Eliminar volúmenes huérfanos
docker volume prune -f

# Opcional: Eliminar todas las imágenes del proyecto
docker rmi $(docker images | grep -E 'servicio|gateway' | awk '{print $3}') 2>/dev/null
```

---

## 📝 Notas Importantes

1. **Orden de Inicio**: Es importante iniciar los servicios en el orden especificado (bases de datos → APIs → Gateway).

2. **Tiempos de Espera**: Las bases de datos necesitan ~30 segundos para inicializarse completamente antes de configurar la replicación.

3. **Composer Install**: Cada servicio API requiere instalar dependencias de PHP con Composer después de construir la imagen.

4. **Migraciones y Seeders**: Deben ejecutarse en cada servicio para crear las tablas y poblar datos de prueba.

5. **Permisos**: Asegúrate de que los scripts `.sh` tengan permisos de ejecución (`chmod +x`).

6. **Puertos**: Verifica que los puertos 8000-8007, 3306-3319 y 8080 estén disponibles antes de iniciar.

---

## 🎯 Uso con Thunder Client / Postman

### Configuración Recomendada

1. **Base URL**: `http://localhost:8000/api`

2. **Headers Globales**:
   - `Accept`: `application/json`
   - `Content-Type`: `application/json`

3. **Autenticación**:
   - Tipo: Bearer Token
   - Hacer login en `/auth/login` para obtener el token
   - Usar el token en todas las peticiones protegidas

### Ejemplo de Flujo

1. **Login**:
   ```
   POST /auth/login
   Body: {"email": "admin@ewtto.com", "password": "admin123"}
   ```

2. **Guardar token** de la respuesta

3. **Usar endpoints protegidos**:
   ```
   GET /users
   GET /products
   GET /sales
   POST /sales
   ```
   (Todos con `Authorization: Bearer {token}`)

---

## 📚 Documentación Adicional

Para más detalles sobre cada servicio, consulta:
- `/GUIA_INSTALACION/` - Instalación y configuración detallada
- `/README.md` - Visión general del proyecto
- Cada carpeta de servicio tiene su propia documentación

---

**Versión**: 1.0.0  
**Fecha**: Diciembre 2025  
**Sistema**: E-WTTO Microservicios
