# phpMyAdmin - Gestor de Bases de Datos MySQL

## 🚀 Iniciar phpMyAdmin

```bash
# Desde la carpeta phpmyadmin/
docker compose up -d

# O desde la raíz SIS/
docker compose -f phpmyadmin/docker-compose.yml up -d
```

## 🌐 Acceder a phpMyAdmin

Abre tu navegador en: **http://localhost:8080**

## 🔐 Credenciales de acceso

### Opción 1: Seleccionar servidor del dropdown

1. En la pantalla de login verás un dropdown con:
   - Auth DB
   - Branches DB
   - Inventory DB
   - Sales DB
   - Reservations DB
   - HR DB
   - Config DB

2. Selecciona el servidor que quieras administrar

3. Ingresa:
   - **Usuario:** `root`
   - **Contraseña:** `3312`

### Opción 2: Conexión manual (servidor arbitrario)

1. En la pantalla de login, ingresa:
   - **Servidor:** Nombre del contenedor (ej: `sd_db_auth`)
   - **Usuario:** `root`
   - **Contraseña:** `3312`

2. Bases de datos disponibles:
   - `sd_db_auth` → Base de datos: `auth_db`
   - `sd_db_branches` → Base de datos: `branches_db`
   - `sd_db_inventory` → Base de datos: `inventory_db`
   - `sd_db_sales` → Base de datos: `sales_db`
   - `sd_db_reservations` → Base de datos: `reservations_db`
   - `sd_db_hr` → Base de datos: `hr_db`
   - `sd_db_config` → Base de datos: `config_db`

### Usuario adicional

También puedes usar:
- **Usuario:** `rmichelv`
- **Contraseña:** `usuario123`

## 📋 Funcionalidades

- ✅ Administrar todas las bases de datos desde una interfaz
- ✅ Ejecutar consultas SQL
- ✅ Importar/Exportar bases de datos
- ✅ Ver estructura de tablas
- ✅ Editar datos directamente
- ✅ Ver relaciones entre tablas
- ✅ Ejecutar scripts SQL masivos

## 🛠️ Comandos útiles

### Detener phpMyAdmin
```bash
docker stop phpmyadmin
```

### Iniciar phpMyAdmin
```bash
docker start phpmyadmin
```

### Ver logs
```bash
docker logs phpmyadmin
docker logs -f phpmyadmin  # Seguir logs en tiempo real
```

### Eliminar phpMyAdmin
```bash
docker compose -f phpmyadmin/docker-compose.yml down
```

## 💡 Tips

### Importar SQL desde el archivo init-all-tables.sql

1. Accede a phpMyAdmin
2. Selecciona el servidor correspondiente
3. Selecciona la base de datos
4. Ve a la pestaña "SQL"
5. Copia el contenido del archivo `database/init-all-tables.sql` (solo la sección correspondiente)
6. Pega y ejecuta

### Cambiar servidor sin cerrar sesión

1. Click en el logo de phpMyAdmin (esquina superior izquierda)
2. Selecciona otro servidor del menú

### Ejecutar consultas en múltiples bases de datos

phpMyAdmin solo permite trabajar con una base de datos a la vez. Para consultas entre servicios, usa las APIs.

## ⚠️ Notas de seguridad

- phpMyAdmin está expuesto en el puerto 8080 de tu máquina local
- **NO expongas este puerto en producción**
- Las contraseñas están en texto plano (solo para desarrollo)
- En producción, usa variables de entorno y secrets

## 🔧 Troubleshooting

### Error: "Cannot connect to MySQL server"

Verifica que las bases de datos estén corriendo:
```bash
docker ps | grep sd_db
```

Si no están corriendo, iníciаlas:
```bash
docker start sd_db_auth sd_db_branches sd_db_inventory sd_db_sales sd_db_reservations sd_db_hr sd_db_config
```

### Error: "Access denied"

Verifica las credenciales:
- Usuario: `root`
- Contraseña: `3312`

### phpMyAdmin muy lento

Reinicia el contenedor:
```bash
docker restart phpmyadmin
```

## 📚 Recursos

- [Documentación oficial de phpMyAdmin](https://www.phpmyadmin.net/docs/)
- [Docker Hub - phpMyAdmin](https://hub.docker.com/r/phpmyadmin/phpmyadmin/)
