# Servicio de Autenticación y Usuarios

Microservicio REST API para gestión de autenticación JWT y usuarios del sistema EWTTO.

## 🏗️ Arquitectura

- **Framework**: Laravel 12
- **PHP**: 8.3-FPM
- **Base de datos**: MySQL 8.0 (MASTER/REPLICA)
- **Autenticación**: JWT (tymon/jwt-auth)
- **Contenedores**: Docker + Nginx
- **Puerto**: 8001

## 📋 Requisitos

- Docker y Docker Compose
- Red Docker: `sd_network` (externa)
- Bases de datos MySQL:
  - `sd_db_auth` (MASTER - puerto 3312)
  - `sd_db_auth_replica` (REPLICA - puerto 3313)

## 🚀 Instalación y Configuración

### 1. Construir contenedores

```bash
cd servicio_de_autenticacion_y_usuarios
docker-compose build
docker-compose up -d
```

### 2. Instalar dependencias

```bash
docker exec -it auth_api composer install
```

### 3. Configurar variables de entorno

```bash
docker exec -it auth_api cp .env.example .env
docker exec -it auth_api php artisan key:generate
docker exec -it auth_api php artisan jwt:secret
```

### 4. Ejecutar migraciones

```bash
docker exec -it auth_api php artisan migrate --force
```

### 5. Ejecutar seeders (opcional)

```bash
docker exec -it auth_api php artisan db:seed
```

Esto creará:
- 5 roles predefinidos (Administrador, Gerente, Vendedor, Cajero, Almacenero)
- 1 usuario administrador (admin@ewtto.com / admin123)

## 📡 Endpoints API

### Autenticación

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@ewtto.com",
  "password": "admin123"
}
```

**Respuesta exitosa**:
```json
{
  "success": true,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGci...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "name": "Administrador",
    "email": "admin@ewtto.com",
    "branch_id": 1,
    "role": {
      "id": 1,
      "name": "Administrador"
    }
  }
}
```

#### Obtener usuario autenticado
```http
GET /api/auth/me
Authorization: Bearer {token}
```

#### Refrescar token
```http
POST /api/auth/refresh
Authorization: Bearer {token}
```

#### Logout
```http
POST /api/auth/logout
Authorization: Bearer {token}
```

### Usuarios

#### Listar usuarios (paginado)
```http
GET /api/users
Authorization: Bearer {token}
```

**Parámetros opcionales**:
- `?page=1` - Número de página
- Filtro automático por branch_id del usuario autenticado

#### Obtener usuario específico
```http
GET /api/users/{id}
Authorization: Bearer {token}
```

#### Crear usuario
```http
POST /api/users
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Juan Pérez",
  "email": "juan.perez@ewtto.com",
  "password": "password123",
  "password_confirmation": "password123",
  "address": "Av. Principal 123",
  "phone": "87654321",
  "branch_id": 1,
  "role_id": 2,
  "base_salary": 3000,
  "hire_date": "2025-12-09"
}
```

**Validaciones**:
- Email debe terminar en `@ewtto.com`
- Password mínimo 8 caracteres
- branch_id se valida contra el servicio de sucursales vía HTTP
- role_id debe existir en la tabla roles

#### Actualizar usuario (parcial)
```http
PUT /api/users/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Juan Carlos Pérez",
  "phone": "99887766"
}
```

Solo se actualizan los campos enviados.

#### Eliminar usuario
```http
DELETE /api/users/{id}
Authorization: Bearer {token}
```

### Roles

#### Listar roles
```http
GET /api/roles
Authorization: Bearer {token}
```

Incluye contador de usuarios asignados (`users_count`).

#### Crear rol
```http
POST /api/roles
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Supervisor"
}
```

#### Actualizar rol
```http
PUT /api/roles/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Supervisor General"
}
```

#### Eliminar rol
```http
DELETE /api/roles/{id}
Authorization: Bearer {token}
```

Valida que no existan usuarios con este rol antes de eliminar.

## 🔧 Comandos útiles

### Ver logs en tiempo real
```bash
docker logs -f auth_api
docker logs -f auth_nginx
```

### Acceder al contenedor
```bash
docker exec -it auth_api bash
```

### Limpiar caché
```bash
docker exec -it auth_api php artisan config:cache
docker exec -it auth_api php artisan route:cache
```

### Reiniciar contenedores
```bash
docker restart auth_api auth_nginx
```

### Verificar salud del servicio
```http
GET /api/health
```

Respuesta:
```json
{
  "success": true,
  "service": "auth-service",
  "status": "healthy",
  "timestamp": "2025-12-09T07:12:53.172945Z"
}
```

## 🗄️ Estructura de base de datos

### Tabla: users
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | BIGINT | PK |
| name | VARCHAR(255) | Nombre completo |
| address | VARCHAR(255) | Dirección |
| phone | VARCHAR(20) | Teléfono |
| branch_id | BIGINT | FK a sucursales (sin constraint) |
| role_id | BIGINT | FK a roles |
| base_salary | DECIMAL(10,2) | Salario base |
| hire_date | DATE | Fecha de contratación |
| email | VARCHAR(255) | Email único (@ewtto.com) |
| password | VARCHAR(255) | Hash bcrypt |

### Tabla: roles
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | BIGINT | PK |
| name | VARCHAR(100) | Nombre del rol (único) |

### Tabla: cache
| Campo | Tipo | Descripción |
|-------|------|-------------|
| key | VARCHAR(255) | PK - Clave de caché |
| value | MEDIUMTEXT | Valor serializado |
| expiration | INTEGER | Timestamp de expiración |

## 🔐 Seguridad

- **JWT TTL**: 60 minutos (configurable en .env)
- **JWT Refresh TTL**: 20160 minutos (14 días)
- **Passwords**: Hash bcrypt con factor de costo 12
- **Middleware**: Autenticación requerida en todos los endpoints (excepto /login y /health)
- **CORS**: Configurado en Nginx

## 🧪 Testing

### Test manual con curl

```bash
# Login
TOKEN=$(curl -s -X POST http://localhost:8001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@ewtto.com","password":"admin123"}' \
  | jq -r '.access_token')

# Usar el token
curl -X GET http://localhost:8001/api/users \
  -H "Authorization: Bearer $TOKEN"
```

## 📦 Dependencias principales

```json
{
  "php": "^8.3",
  "laravel/framework": "^12.0",
  "tymon/jwt-auth": "^2.2.1",
  "guzzlehttp/guzzle": "^7.8"
}
```

## 🐛 Troubleshooting

### Error: "Table 'auth_db.cache' doesn't exist"
```bash
docker exec -it auth_api php artisan migrate --force
```

### Error: JWT TTL type error
Verificar que `config/jwt.php` tenga:
```php
'ttl' => (int) env('JWT_TTL', 60),
'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 20160),
```

### Error: Cannot connect to MySQL
Verificar que los contenedores de base de datos estén ejecutándose:
```bash
docker ps | grep sd_db_auth
```

## 📝 Notas importantes

1. **branch_id sin constraint**: Se valida contra el microservicio de sucursales vía HTTP, no hay foreign key en la base de datos
2. **Emails únicos**: Todos los emails deben terminar en `@ewtto.com`
3. **Replicación**: La configuración incluye MASTER/REPLICA pero actualmente solo se usa MASTER
4. **Network**: Requiere red Docker `sd_network` compartida con otros microservicios

## 📄 Licencia

MIT License
