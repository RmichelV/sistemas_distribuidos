-- ============================================================================
-- SCRIPT DE INICIALIZACIÓN DE BASES DE DATOS
-- Sistema de Inventario - Microservicios
-- Fecha: 28 de noviembre de 2025
-- ============================================================================
-- IMPORTANTE: Este script contiene las sentencias SQL para crear todas las
-- tablas de cada microservicio. Debes ejecutarlas manualmente en cada
-- contenedor correspondiente.
-- ============================================================================

-- ============================================================================
-- CONTENEDOR: sd_db_auth (Base de datos: auth_db)
-- SERVICIO: Autenticación y Usuarios
-- ============================================================================

USE auth_db;

-- Tabla: roles
CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: users
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL COMMENT 'FK a branches (en sd_db_branches)',
    role_id BIGINT UNSIGNED NOT NULL,
    base_salary INT NOT NULL,
    hire_date DATE NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: password_reset_tokens
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: sessions
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    INDEX sessions_user_id_index (user_id),
    INDEX sessions_last_activity_index (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices adicionales para optimización
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_branch_id ON users(branch_id);
CREATE INDEX idx_users_role_id ON users(role_id);


-- ============================================================================
-- CONTENEDOR: sd_db_branches (Base de datos: branches_db)
-- SERVICIO: Sucursales
-- ============================================================================

USE branches_db;

-- Tabla: branches
CREATE TABLE IF NOT EXISTS branches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices
CREATE INDEX idx_branches_name ON branches(name);


-- ============================================================================
-- CONTENEDOR: sd_db_inventory (Base de datos: inventory_db)
-- SERVICIO: Inventario (Productos, Compras, Stocks)
-- ============================================================================

USE inventory_db;

-- Tabla: products
CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NULL,
    code VARCHAR(255) NOT NULL UNIQUE,
    img_product VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: product_branches (inventario en bodega por sucursal)
CREATE TABLE IF NOT EXISTS product_branches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL COMMENT 'FK a branches (en sd_db_branches)',
    product_id BIGINT UNSIGNED NOT NULL,
    quantity_in_stock INT NOT NULL DEFAULT 0,
    unit_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    units_per_box INT NULL,
    last_update DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_branch_product (branch_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: product_stores (inventario en tienda)
CREATE TABLE IF NOT EXISTS product_stores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NULL COMMENT 'FK a branches (en sd_db_branches)',
    quantity INT NOT NULL DEFAULT 0,
    unit_price DECIMAL(10, 2) NOT NULL,
    price_multiplier DECIMAL(8, 4) NOT NULL DEFAULT 1.0000,
    last_update DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: purchases
CREATE TABLE IF NOT EXISTS purchases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    purchase_quantity INT NOT NULL,
    purchase_date DATE NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL COMMENT 'FK a branches (en sd_db_branches)',
    unit_price DECIMAL(10, 2) NULL,
    total_price DECIMAL(10, 2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices
CREATE INDEX idx_products_code ON products(code);
CREATE INDEX idx_product_branches_branch_id ON product_branches(branch_id);
CREATE INDEX idx_product_branches_product_id ON product_branches(product_id);
CREATE INDEX idx_product_stores_product_id ON product_stores(product_id);
CREATE INDEX idx_product_stores_branch_id ON product_stores(branch_id);
CREATE INDEX idx_purchases_product_id ON purchases(product_id);
CREATE INDEX idx_purchases_branch_id ON purchases(branch_id);
CREATE INDEX idx_purchases_date ON purchases(purchase_date);


-- ============================================================================
-- CONTENEDOR: sd_db_sales (Base de datos: sales_db)
-- SERVICIO: Ventas
-- ============================================================================

USE sales_db;

-- Tabla: sales
CREATE TABLE IF NOT EXISTS sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_code VARCHAR(255) NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    sale_date DATE NOT NULL,
    pay_type VARCHAR(50) NOT NULL,
    final_price DECIMAL(10, 2) NOT NULL,
    exchange_rate DECIMAL(10, 2) NOT NULL,
    notes TEXT NULL,
    branch_id BIGINT UNSIGNED NOT NULL COMMENT 'FK a branches (en sd_db_branches)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: sale_items
CREATE TABLE IF NOT EXISTS sale_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL COMMENT 'FK a products (en sd_db_inventory)',
    quantity_products INT NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    exchange_rate DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: devolutions
CREATE TABLE IF NOT EXISTS devolutions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL COMMENT 'FK a sales',
    product_id BIGINT UNSIGNED NOT NULL COMMENT 'FK a products (en sd_db_inventory)',
    quantity_returned INT NOT NULL,
    reason TEXT NULL,
    devolution_date DATE NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL COMMENT 'FK a branches (en sd_db_branches)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices
CREATE INDEX idx_sales_code ON sales(sale_code);
CREATE INDEX idx_sales_date ON sales(sale_date);
CREATE INDEX idx_sales_branch_id ON sales(branch_id);
CREATE INDEX idx_sale_items_sale_id ON sale_items(sale_id);
CREATE INDEX idx_sale_items_product_id ON sale_items(product_id);
CREATE INDEX idx_devolutions_sale_id ON devolutions(sale_id);
CREATE INDEX idx_devolutions_product_id ON devolutions(product_id);


-- ============================================================================
-- CONTENEDOR: sd_db_reservations (Base de datos: reservations_db)
-- SERVICIO: Clientes y Reservaciones
-- ============================================================================

USE reservations_db;

-- Tabla: customers
CREATE TABLE IF NOT EXISTS customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    notes TEXT NULL,
    last_update DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: reservations
CREATE TABLE IF NOT EXISTS reservations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    advance_amount DECIMAL(10, 2) NOT NULL,
    rest_amount DECIMAL(10, 2) NOT NULL,
    exchange_rate DECIMAL(10, 2) NOT NULL,
    pay_type VARCHAR(50) NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL COMMENT 'FK a branches (en sd_db_branches)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: reservation_items
CREATE TABLE IF NOT EXISTS reservation_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL COMMENT 'FK a products (en sd_db_inventory)',
    quantity_products INT NOT NULL,
    quantity_from_warehouse INT NOT NULL DEFAULT 0,
    quantity_from_store INT NOT NULL DEFAULT 0,
    total_price DECIMAL(10, 2) NOT NULL,
    exchange_rate DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices
CREATE INDEX idx_customers_email ON customers(email);
CREATE INDEX idx_reservations_customer_id ON reservations(customer_id);
CREATE INDEX idx_reservations_branch_id ON reservations(branch_id);
CREATE INDEX idx_reservation_items_reservation_id ON reservation_items(reservation_id);
CREATE INDEX idx_reservation_items_product_id ON reservation_items(product_id);


-- ============================================================================
-- CONTENEDOR: sd_db_hr (Base de datos: hr_db)
-- SERVICIO: Recursos Humanos (Asistencias, Salarios, Ajustes)
-- ============================================================================

USE hr_db;

-- Tabla: attendance_records
CREATE TABLE IF NOT EXISTS attendance_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL COMMENT 'FK a users (en sd_db_auth)',
    attendance_status VARCHAR(50) NOT NULL,
    attendance_date DATE NOT NULL,
    check_in_at TIME NULL,
    check_out_at TIME NULL,
    minutes_worked INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_date (user_id, attendance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: salary_adjustments
CREATE TABLE IF NOT EXISTS salary_adjustments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL COMMENT 'FK a users (en sd_db_auth)',
    adjustment_type VARCHAR(100) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    reason TEXT NULL,
    adjustment_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: salaries
CREATE TABLE IF NOT EXISTS salaries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL COMMENT 'FK a users (en sd_db_auth)',
    base_salary DECIMAL(10, 2) NOT NULL,
    salary_adjustment DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    discounts DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total_salary DECIMAL(10, 2) NOT NULL,
    paydate DATE NOT NULL,
    user_id_m BIGINT UNSIGNED NOT NULL COMMENT 'Usuario que registró el pago',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices
CREATE INDEX idx_attendance_records_user_id ON attendance_records(user_id);
CREATE INDEX idx_attendance_records_date ON attendance_records(attendance_date);
CREATE INDEX idx_salary_adjustments_user_id ON salary_adjustments(user_id);
CREATE INDEX idx_salary_adjustments_date ON salary_adjustments(adjustment_date);
CREATE INDEX idx_salaries_user_id ON salaries(user_id);
CREATE INDEX idx_salaries_paydate ON salaries(paydate);


-- ============================================================================
-- CONTENEDOR: sd_db_config (Base de datos: config_db)
-- SERVICIO: Configuración (Tasas de cambio, Settings)
-- ============================================================================

USE config_db;

-- Tabla: usd_exchange_rates
CREATE TABLE IF NOT EXISTS usd_exchange_rates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exchange_rate DECIMAL(10, 4) NOT NULL,
    effective_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: cache (opcional, para Laravel)
CREATE TABLE IF NOT EXISTS cache (
    `key` VARCHAR(255) PRIMARY KEY,
    value MEDIUMTEXT NOT NULL,
    expiration INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cache_locks (
    `key` VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: jobs (colas de Laravel)
CREATE TABLE IF NOT EXISTS jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INT UNSIGNED NULL,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    INDEX jobs_queue_index (queue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS failed_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(255) UNIQUE NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices
CREATE INDEX idx_usd_exchange_rates_date ON usd_exchange_rates(effective_date);


-- ============================================================================
-- FIN DEL SCRIPT
-- ============================================================================
-- NOTAS IMPORTANTES:
-- 1. Las Foreign Keys entre servicios están comentadas porque cada BD
--    está en un contenedor diferente. La integridad referencial entre
--    servicios debe manejarse a nivel de aplicación.
-- 
-- 2. Para ejecutar este script en cada contenedor:
--    docker exec -i sd_db_auth mysql -uroot -p3312 < init-all-tables.sql
--
-- 3. Los índices están optimizados para las consultas más comunes del sistema.
--
-- 4. Todos los campos TIMESTAMP usan zona horaria del servidor MySQL.
-- ============================================================================
