# Auth Service API - Microservicio de Autenticación y Usuarios

Servicio REST API construido con Laravel 12 para gestión de autenticación, usuarios y roles usando arquitectura de microservicios.

## 🚀 Stack Tecnológico

- **PHP 8.3** con FPM
- **Laravel 12**
- **MySQL 8.0** (MASTER/REPLICA)
- **JWT Authentication** (tymon/jwt-auth)
- **Docker** & Docker Compose
- **Nginx**

## 📁 Estructura del Proyecto

```
api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php    # Autenticación JWT
│   │   │   ├── UserController.php    # Gestión de usuarios
│   │   │   └── RoleController.php    # Gestión de roles
│   │   └── Requests/
│   │       ├── Auth/LoginRequest.php
│   │       └── Users/UserRequest.php
│   └── Models/
│       ├── User.php                  # Usuario (con JWT)
│       └── Role.php                  # Rol
├── database/
│   ├── migrations/
│   │   ├── 2025_12_09_000001_create_roles_table.php
│   │   └── 2025_12_09_000002_create_users_table.php
│   └── seeders/
│       ├── RoleSeeder.php
│       └── UserSeeder.php
├── routes/
│   └── api.php                       # Rutas de la API
├── docker-compose.yml
├── Dockerfile
└── nginx.conf
```

## 🛠️ Instalación y Configuración

### 1. Prerrequisitos

- Docker y Docker Compose instalados
- Red Docker `sd_network` creada
- Contenedores MySQL corriendo:
  - `sd_db_auth` (MASTER - puerto 3312)
  - `sd_db_auth_replica` (REPLICA - puerto 3313)

### 2. Configurar Variables de Entorno

```bash
cd api
cp .env.example .env
```

Editar `.env` con tus credenciales:

```env
DB_CONNECTION=mysql
DB_HOST=sd_db_auth
DB_PORT=3306
DB_DATABASE=ewtto_auth
DB_USERNAME=root
DB_PASSWORD=3312

DB_READ_HOST=sd_db_auth_replica
DB_READ_PORT=3306
```

### 3. Levantar Contenedores

```bash
docker-compose up -d
```

Esto iniciará:
- `auth_api` - PHP-FPM (puerto interno 9000)
- `auth_nginx` - Nginx (puerto **8001**)

### 4. Instalar Dependencias de Laravel

```bash
docker exec -it auth_api bash
composer install
php artisan key:generate
php artisan jwt:secret
```

### 5. Ejecutar Migraciones y Seeders

```bash
php artisan migrate
php artisan db:seed
```

Esto creará:
- Tablas: `roles`, `users`, `password_reset_tokens`, `sessions`
- Roles: Administrador, Gerente, Vendedor, Cajero, Almacenero
- Usuario admin: `admin@ewtto.com` / `admin123`

## 📡 Endpoints de la API

Base URL: `http://localhost:8001/api`

### 🔓 Autenticación (Público)

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@ewtto.com",
  "password": "admin123"
}
```

**Respuesta:**
```json
{
  "success": true,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "name": "Administrador",
    "email": "admin@ewtto.com",
    "role": {
      "id": 1,
      "name": "Administrador"
    }
  }
}
```

#### Registro
```http
POST /api/auth/register
Content-Type: application/json

{
  "name": "Juan Pérez",
  "email": "juan@ewtto.com",
  "password": "password123",
  "password_confirmation": "password123",
  "address": "Av. Principal 123",
  "phone": "12345678",
  "branch_id": 1,
  "role_id": 3,
  "base_salary": 3000,
  "hire_date": "2025-01-10"
}
```

### 🔒 Endpoints Protegidos

**Agregar en headers:**
```
Authorization: Bearer {token}
```

#### Perfil del Usuario Autenticado
```http
GET /api/auth/me
```

#### Logout
```http
POST /api/auth/logout
```

#### Refresh Token
```http
POST /api/auth/refresh
```

### 👥 Gestión de Usuarios

#### Listar Usuarios (con filtros)
```http
GET /api/users
GET /api/users?branch_id=1
GET /api/users?role_id=2
GET /api/users?search=juan
GET /api/users?per_page=20
```

#### Ver Usuario
```http
GET /api/users/{id}
```

#### Crear Usuario
```http
POST /api/users
Content-Type: application/json

{
  "name": "María López",
  "email": "maria@ewtto.com",
  "password": "password123",
  "password_confirmation": "password123",
  "address": "Calle 456",
  "phone": "87654321",
  "branch_id": 2,
  "role_id": 2,
  "base_salary": 4500,
  "hire_date": "2025-02-01"
}
```

#### Actualizar Usuario
```http
PUT /api/users/{id}
Content-Type: application/json

{
  "name": "María López Actualizada",
  "email": "maria@ewtto.com",
  "address": "Nueva Dirección",
  "phone": "11111111",
  "branch_id": 1,
  "role_id": 2,
  "base_salary": 5000,
  "hire_date": "2025-02-01"
}
```

*Nota: El campo `password` es opcional en la actualización*

#### Eliminar Usuario
```http
DELETE /api/users/{id}
```

#### Cambiar Sucursal del Usuario Actual
```http
POST /api/users/switch-branch
Content-Type: application/json

{
  "branch_id": 3
}
```

### 🎭 Gestión de Roles

#### Listar Roles
```http
GET /api/roles
```

#### Ver Rol
```http
GET /api/roles/{id}
```

#### Crear Rol
```http
POST /api/roles
Content-Type: application/json

{
  "name": "Supervisor"
}
```

#### Actualizar Rol
```http
PUT /api/roles/{id}
Content-Type: application/json

{
  "name": "Supervisor Senior"
}
```

#### Eliminar Rol
```http
DELETE /api/roles/{id}
```

### 🏥 Health Check
```http
GET /api/health
```

## 🔐 Autenticación JWT

El servicio utiliza JSON Web Tokens (JWT) para autenticación. Los tokens:

- Expiran en **1 hora** (3600 segundos)
- Incluyen claims personalizados: `email`, `name`, `role_id`, `role_name`, `branch_id`
- Se pueden refrescar con el endpoint `/api/auth/refresh`

## 🌐 Microservicios: Validación de Foreign Keys

Este servicio valida `branch_id` mediante **HTTP request** al servicio de sucursales (branch-service) ya que las branches están en otro microservicio.

La validación se realiza en `UserRequest::after()`:

```php
$response = Http::timeout(3)->get("http://branch-service/api/branches/{$branchId}");
```

Si el servicio de branches no está disponible, se registra un warning pero no falla la validación (modo desarrollo).

## 📊 Base de Datos

### Configuración MASTER/REPLICA

- **MASTER** (`sd_db_auth`): Usado para operaciones de escritura (INSERT, UPDATE, DELETE)
- **REPLICA** (`sd_db_auth_replica`): Usado para operaciones de lectura (SELECT)

Laravel maneja automáticamente esta separación según el tipo de query.

### Tablas

#### `roles`
- `id` - bigint (PK)
- `name` - varchar(255)
- `created_at`, `updated_at`

#### `users`
- `id` - bigint (PK)
- `name` - varchar(255)
- `email` - varchar(255) (unique, @ewtto.com)
- `password` - varchar(255) (hashed)
- `address` - varchar(255)
- `phone` - varchar(8)
- `role_id` - foreignId → roles
- `branch_id` - foreignId (NO constraint - microservicio externo)
- `base_salary` - decimal(10,2) (min: 500)
- `hire_date` - date
- `created_at`, `updated_at`

## 🐛 Troubleshooting

### Error de conexión a base de datos

Verificar que los contenedores MySQL estén corriendo:
```bash
docker ps | grep sd_db_auth
```

Verificar conectividad desde el contenedor:
```bash
docker exec -it auth_api ping sd_db_auth
```

### JWT Secret no configurado

```bash
docker exec -it auth_api php artisan jwt:secret
```

### Permisos en storage/

```bash
docker exec -it auth_api chmod -R 775 storage bootstrap/cache
docker exec -it auth_api chown -R authuser:authuser storage bootstrap/cache
```

### Ver logs

```bash
docker logs auth_nginx
docker logs auth_api
docker exec -it auth_api tail -f storage/logs/laravel.log
```

## 🧪 Testing

```bash
docker exec -it auth_api bash
php artisan test
```

## 📝 Notas Importantes

1. **Dominio de email**: Solo se permiten correos con dominio `@ewtto.com`
2. **Salario mínimo**: Bs. 500
3. **Teléfono**: Exactamente 8 dígitos
4. **Auto-eliminación**: Un usuario no puede eliminarse a sí mismo
5. **Protección de roles**: No se puede eliminar un rol si tiene usuarios asignados

## 🔗 Servicios Relacionados

Este microservicio se comunica con:
- **branch-service** (validación de `branch_id`)

## 📄 Licencia

Proyecto académico - Universidad - Sistemas Distribuidos

---

**Puerto de servicio**: 8001  
**Usuario de prueba**: admin@ewtto.com / admin123
