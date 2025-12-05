# PASO 7: Crear Tablas en los MASTER

## Objetivo
Crear todas las tablas necesarias en los contenedores MASTER. Las tablas se replicarán automáticamente a las REPLICAS.

## Bases de Datos y Tablas

| Base de Datos | Tablas | Total |
|---------------|--------|-------|
| `auth_db` | roles, users, password_reset_tokens, sessions | 4 |
| `branches_db` | branches | 1 |
| `inventory_db` | products, product_branches, product_stores, purchases | 4 |
| `sales_db` | sales, sale_items, devolutions | 3 |
| `reservations_db` | customers, reservations, reservation_items | 3 |
| `hr_db` | attendance_records, salary_adjustments, salaries | 3 |
| `config_db` | usd_exchange_rates, cache, jobs, failed_jobs, job_batches, sessions | 6 |
| **TOTAL** | | **24 tablas** |

## Método 1: Ejecutar SQL Manualmente

### 1. Autenticación (`auth_db`)

```bash
docker exec sd_db_auth mysql -uroot -p3312 auth_db <<'EOF'
-- Tabla: roles
CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla: users
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    role_id BIGINT UNSIGNED,
    branch_id BIGINT UNSIGNED,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
);

-- Tabla: password_reset_tokens
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: sessions
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    INDEX (user_id),
    INDEX (last_activity)
);
EOF
```

**Verificar**:
```bash
docker exec sd_db_auth mysql -uroot -p3312 -e "SHOW TABLES FROM auth_db;"
```

---

### 2. Sucursales (`branches_db`)

```bash
docker exec sd_db_branches mysql -uroot -p3312 branches_db <<'EOF'
-- Tabla: branches
CREATE TABLE IF NOT EXISTS branches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(500) NULL,
    phone VARCHAR(20) NULL,
    manager_user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
EOF
```

---

### 3. Inventario (`inventory_db`)

```bash
docker exec sd_db_inventory mysql -uroot -p3312 inventory_db <<'EOF'
-- Tabla: products
CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(100) NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla: product_branches
CREATE TABLE IF NOT EXISTS product_branches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla: product_stores
CREATE TABLE IF NOT EXISTS product_stores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla: purchases
CREATE TABLE IF NOT EXISTS purchases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    supplier VARCHAR(255) NULL,
    purchase_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
EOF
```

---

### 4. Ventas (`sales_db`)

```bash
docker exec sd_db_sales mysql -uroot -p3312 sales_db <<'EOF'
-- Tabla: sales
CREATE TABLE IF NOT EXISTS sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id BIGINT UNSIGNED NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    seller_user_id BIGINT UNSIGNED NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NULL,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla: sale_items
CREATE TABLE IF NOT EXISTS sale_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
);

-- Tabla: devolutions
CREATE TABLE IF NOT EXISTS devolutions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    reason TEXT NULL,
    devolution_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
);
EOF
```

---

### 5. Reservaciones (`reservations_db`)

```bash
docker exec sd_db_reservations mysql -uroot -p3312 reservations_db <<'EOF'
-- Tabla: customers
CREATE TABLE IF NOT EXISTS customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    address VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla: reservations
CREATE TABLE IF NOT EXISTS reservations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    reservation_date DATE NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    total_amount DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Tabla: reservation_items
CREATE TABLE IF NOT EXISTS reservation_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
);
EOF
```

---

### 6. Recursos Humanos (`hr_db`)

```bash
docker exec sd_db_hr mysql -uroot -p3312 hr_db <<'EOF'
-- Tabla: attendance_records
CREATE TABLE IF NOT EXISTS attendance_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    check_in TIME NULL,
    check_out TIME NULL,
    hours_worked DECIMAL(4,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla: salary_adjustments
CREATE TABLE IF NOT EXISTS salary_adjustments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    adjustment_type VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reason TEXT NULL,
    effective_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla: salaries
CREATE TABLE IF NOT EXISTS salaries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    base_salary DECIMAL(10,2) NOT NULL,
    bonuses DECIMAL(10,2) DEFAULT 0.00,
    deductions DECIMAL(10,2) DEFAULT 0.00,
    net_salary DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
EOF
```

---

### 7. Configuración (`config_db`)

```bash
docker exec sd_db_config mysql -uroot -p3312 config_db <<'EOF'
-- Tabla: usd_exchange_rates
CREATE TABLE IF NOT EXISTS usd_exchange_rates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rate DECIMAL(10,4) NOT NULL,
    effective_date DATE NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla: cache
CREATE TABLE IF NOT EXISTS cache (
    `key` VARCHAR(255) PRIMARY KEY,
    value MEDIUMTEXT NOT NULL,
    expiration INT NOT NULL
);

-- Tabla: cache_locks
CREATE TABLE IF NOT EXISTS cache_locks (
    `key` VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INT NOT NULL
);

-- Tabla: jobs
CREATE TABLE IF NOT EXISTS jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INT UNSIGNED NULL,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    INDEX (queue)
);

-- Tabla: failed_jobs
CREATE TABLE IF NOT EXISTS failed_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(255) UNIQUE NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: job_batches
CREATE TABLE IF NOT EXISTS job_batches (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    total_jobs INT NOT NULL,
    pending_jobs INT NOT NULL,
    failed_jobs INT NOT NULL,
    failed_job_ids LONGTEXT NOT NULL,
    options MEDIUMTEXT NULL,
    cancelled_at INT NULL,
    created_at INT NOT NULL,
    finished_at INT NULL
);
EOF
```

## Método 2: Script Automatizado

Crea un archivo `create-tables.sh`:

```bash
#!/bin/bash

echo "========================================="
echo "Creando tablas en contenedores MASTER"
echo "========================================="

# Autenticación
echo "Creando tablas en auth_db..."
docker exec sd_db_auth mysql -uroot -p3312 auth_db < sql/auth_db.sql
echo "✓ Tablas creadas en auth_db"

# Sucursales
echo "Creando tablas en branches_db..."
docker exec sd_db_branches mysql -uroot -p3312 branches_db < sql/branches_db.sql
echo "✓ Tablas creadas en branches_db"

# Inventario
echo "Creando tablas en inventory_db..."
docker exec sd_db_inventory mysql -uroot -p3312 inventory_db < sql/inventory_db.sql
echo "✓ Tablas creadas en inventory_db"

# Ventas
echo "Creando tablas en sales_db..."
docker exec sd_db_sales mysql -uroot -p3312 sales_db < sql/sales_db.sql
echo "✓ Tablas creadas en sales_db"

# Reservaciones
echo "Creando tablas en reservations_db..."
docker exec sd_db_reservations mysql -uroot -p3312 reservations_db < sql/reservations_db.sql
echo "✓ Tablas creadas en reservations_db"

# Recursos Humanos
echo "Creando tablas en hr_db..."
docker exec sd_db_hr mysql -uroot -p3312 hr_db < sql/hr_db.sql
echo "✓ Tablas creadas en hr_db"

# Configuración
echo "Creando tablas en config_db..."
docker exec sd_db_config mysql -uroot -p3312 config_db < sql/config_db.sql
echo "✓ Tablas creadas en config_db"

echo ""
echo "========================================="
echo "Verificando replicación en REPLICAS"
echo "========================================="

# Verificar que las tablas se replicaron
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "SELECT COUNT(*) AS tables FROM information_schema.tables WHERE table_schema='auth_db';"
docker exec sd_db_branches_replica mysql -uroot -p3312 -e "SELECT COUNT(*) AS tables FROM information_schema.tables WHERE table_schema='branches_db';"
docker exec sd_db_inventory_replica mysql -uroot -p3312 -e "SELECT COUNT(*) AS tables FROM information_schema.tables WHERE table_schema='inventory_db';"
docker exec sd_db_sales_replica mysql -uroot -p3312 -e "SELECT COUNT(*) AS tables FROM information_schema.tables WHERE table_schema='sales_db';"
docker exec sd_db_reservations_replica mysql -uroot -p3312 -e "SELECT COUNT(*) AS tables FROM information_schema.tables WHERE table_schema='reservations_db';"
docker exec sd_db_hr_replica mysql -uroot -p3312 -e "SELECT COUNT(*) AS tables FROM information_schema.tables WHERE table_schema='hr_db';"
docker exec sd_db_config_replica mysql -uroot -p3312 -e "SELECT COUNT(*) AS tables FROM information_schema.tables WHERE table_schema='config_db';"

echo "========================================="
echo "✓ Proceso completado"
echo "========================================="
```

## Insertar Datos de Prueba

### Roles (Autenticación)
```bash
docker exec sd_db_auth mysql -uroot -p3312 auth_db <<'EOF'
INSERT INTO roles (name) VALUES
    ('Administrador'),
    ('Gerente'),
    ('Vendedor'),
    ('Bodeguero'),
    ('Contador');
EOF
```

**Verificar en MASTER**:
```bash
docker exec sd_db_auth mysql -uroot -p3312 auth_db -e "SELECT * FROM roles;"
```

**Verificar en REPLICA** (debe tener los mismos datos):
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 auth_db -e "SELECT * FROM roles;"
```

## Verificación

### 1. Contar tablas en cada BD (MASTER)
```bash
docker exec sd_db_auth mysql -uroot -p3312 -e "SHOW TABLES FROM auth_db;" | wc -l
# Esperado: 5 (4 tablas + 1 línea de encabezado)

docker exec sd_db_branches mysql -uroot -p3312 -e "SHOW TABLES FROM branches_db;" | wc -l
# Esperado: 2

docker exec sd_db_inventory mysql -uroot -p3312 -e "SHOW TABLES FROM inventory_db;" | wc -l
# Esperado: 5

docker exec sd_db_sales mysql -uroot -p3312 -e "SHOW TABLES FROM sales_db;" | wc -l
# Esperado: 4

docker exec sd_db_reservations mysql -uroot -p3312 -e "SHOW TABLES FROM reservations_db;" | wc -l
# Esperado: 4

docker exec sd_db_hr mysql -uroot -p3312 -e "SHOW TABLES FROM hr_db;" | wc -l
# Esperado: 4

docker exec sd_db_config mysql -uroot -p3312 -e "SHOW TABLES FROM config_db;" | wc -l
# Esperado: 7
```

---

### 2. Verificar replicación en REPLICA
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "SHOW TABLES FROM auth_db;"
```

**Debe mostrar las mismas 4 tablas que en el MASTER**:
```
+----------------------------+
| Tables_in_auth_db          |
+----------------------------+
| password_reset_tokens      |
| roles                      |
| sessions                   |
| users                      |
+----------------------------+
```

---

### 3. Verificar estructura de una tabla
```bash
docker exec sd_db_auth mysql -uroot -p3312 auth_db -e "DESCRIBE roles;"
```

**Salida esperada**:
```
+------------+---------------------+------+-----+-------------------+
| Field      | Type                | Null | Key | Default           |
+------------+---------------------+------+-----+-------------------+
| id         | bigint unsigned     | NO   | PRI | NULL              |
| name       | varchar(100)        | NO   | UNI | NULL              |
| created_at | timestamp           | YES  |     | CURRENT_TIMESTAMP |
| updated_at | timestamp           | YES  |     | CURRENT_TIMESTAMP |
+------------+---------------------+------+-----+-------------------+
```

## Solución de Problemas

### Las tablas no aparecen en la REPLICA
**Verificar estado de replicación**:
```bash
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "SHOW SLAVE STATUS\G" | grep -E "(Slave_IO_Running|Slave_SQL_Running|Seconds_Behind)"
```

**Si `Slave_SQL_Running: No`**:
```bash
# Ver el error
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "SHOW SLAVE STATUS\G" | grep "Last_SQL_Error"

# Reiniciar replicación
docker exec sd_db_auth_replica mysql -uroot -p3312 -e "STOP SLAVE; START SLAVE;"
```

---

### Error: "Table already exists"
**Si intentas crear una tabla que ya existe**:

**Solución 1**: Usar `IF NOT EXISTS` (ya incluido en los scripts)

**Solución 2**: Borrar y recrear
```bash
docker exec sd_db_auth mysql -uroot -p3312 auth_db -e "DROP TABLE IF EXISTS roles;"
docker exec sd_db_auth mysql -uroot -p3312 auth_db -e "CREATE TABLE roles (...);"
```

---

### Las tablas están en el MASTER pero no en la REPLICA
**Posibles causas**:
1. La replicación no estaba configurada cuando creaste las tablas
2. El `binlog_do_db` no incluye esa BD

**Solución**: Volver a crear las tablas (se replicarán automáticamente si la replicación está activa)

## ¿Qué sigue?
Una vez creadas las tablas, verifica que la replicación funcione correctamente. Continúa con el [PASO 8: Verificar Replicación](PASO_08_VERIFICAR_REPLICATION.md).
