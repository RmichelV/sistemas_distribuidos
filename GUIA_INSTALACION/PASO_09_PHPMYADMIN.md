# PASO 9: Configurar phpMyAdmin

## Objetivo
Configurar phpMyAdmin para administrar visualmente las 7 bases de datos MASTER desde un navegador web.

## ¿Qué es phpMyAdmin?
Herramienta web gratuita para administrar bases de datos MySQL/MariaDB. Permite:
- Ejecutar consultas SQL
- Crear/editar tablas y datos
- Importar/exportar bases de datos
- Gestionar usuarios y permisos

## Archivo de Configuración

**Ubicación**: `SIS/sd_phpmyadmin.yml` (ya existe)

```yaml
services:
  sd_phpmyadmin:
    image: phpmyadmin:latest
    container_name: sd_phpmyadmin
    restart: always
    ports:
      - "8080:80"
    environment:
      - PMA_ARBITRARY=1
    networks:
      - sd_network

networks:
  sd_network:
    external: true
```

## Explicación de la Configuración

### image: phpmyadmin:latest
- Usa la imagen oficial de phpMyAdmin más reciente

### container_name: sd_phpmyadmin
- Nombre del contenedor
- Hostname dentro de la red `sd_network`

### ports: "8080:80"
- **Puerto externo**: 8080 (accedes desde tu navegador)
- **Puerto interno**: 80 (puerto HTTP estándar)
- **URL de acceso**: `http://localhost:8080`

### PMA_ARBITRARY=1
- **Permite conexión a cualquier servidor MySQL**
- Sin esto, solo podrías conectarte a un servidor predefinido
- Te permite elegir el servidor en la pantalla de login

### networks: sd_network
- Conecta phpMyAdmin a la misma red que los contenedores de BD
- Puede acceder a `sd_db_auth`, `sd_db_branches`, etc. por nombre

## Iniciar phpMyAdmin

### Opción 1: Manualmente
```bash
docker-compose -f sd_phpmyadmin.yml up -d
```

### Opción 2: Con el script automatizado
```bash
./start-all.sh
```

El script `start-all.sh` ya incluye:
```bash
# Iniciar phpMyAdmin
echo "Iniciando phpMyAdmin..."
docker-compose -f sd_phpmyadmin.yml up -d
echo "✓ phpMyAdmin iniciado en http://localhost:8080"
```

## Verificar que está Corriendo

```bash
docker ps | grep sd_phpmyadmin
```

**Salida esperada**:
```
<ID>  phpmyadmin:latest  "docker-php-entrypoint"  Up 2 minutes  0.0.0.0:8080->80/tcp  sd_phpmyadmin
```

## Acceder a phpMyAdmin

### 1. Abrir en el navegador
```
http://localhost:8080
```

### 2. Pantalla de Login

Verás un formulario con 3 campos:
- **Servidor** (Server)
- **Usuario** (Username)
- **Contraseña** (Password)

## Conectarse a un Contenedor MASTER

### Servicio de Autenticación

**Servidor**: `sd_db_auth`
**Usuario**: `root`
**Contraseña**: `3312`

**O bien**:
**Usuario**: `rmichelv`
**Contraseña**: `usuario123`

---

### Servicio de Sucursales

**Servidor**: `sd_db_branches`
**Usuario**: `root`
**Contraseña**: `3312`

---

### Servicio de Inventario

**Servidor**: `sd_db_inventory`
**Usuario**: `root`
**Contraseña**: `3312`

---

### Servicio de Ventas

**Servidor**: `sd_db_sales`
**Usuario**: `root`
**Contraseña**: `3312`

---

### Servicio de Reservaciones

**Servidor**: `sd_db_reservations`
**Usuario**: `root`
**Contraseña**: `3312`

---

### Servicio de Recursos Humanos

**Servidor**: `sd_db_hr`
**Usuario**: `root`
**Contraseña**: `3312`

---

### Servicio de Configuración

**Servidor**: `sd_db_config`
**Usuario**: `root`
**Contraseña**: `3312`

## Conectarse a una REPLICA

### ⚠️ Las REPLICAS NO tienen puertos expuestos

Por defecto, las REPLICAS no son accesibles desde el exterior. Solo son accesibles dentro de la red `sd_network`.

**Pero phpMyAdmin está dentro de `sd_network`**, así que **SÍ puede conectarse a las REPLICAS**.

### Servicio de Autenticación (REPLICA)

**Servidor**: `sd_db_auth_replica`
**Usuario**: `root`
**Contraseña**: `3312`

**Nota**: Podrás ver los datos, pero cualquier intento de modificación fallará debido a `super_read_only`.

## Resumen de Servidores Disponibles

| Tipo | Servidor | Base de Datos | Puerto Externo |
|------|----------|---------------|----------------|
| MASTER | `sd_db_auth` | `auth_db` | 3307 |
| REPLICA | `sd_db_auth_replica` | `auth_db` | - |
| MASTER | `sd_db_branches` | `branches_db` | 3308 |
| REPLICA | `sd_db_branches_replica` | `branches_db` | - |
| MASTER | `sd_db_inventory` | `inventory_db` | 3309 |
| REPLICA | `sd_db_inventory_replica` | `inventory_db` | - |
| MASTER | `sd_db_sales` | `sales_db` | 3310 |
| REPLICA | `sd_db_sales_replica` | `sales_db` | - |
| MASTER | `sd_db_reservations` | `reservations_db` | 3311 |
| REPLICA | `sd_db_reservations_replica` | `reservations_db` | - |
| MASTER | `sd_db_hr` | `hr_db` | 3312 |
| REPLICA | `sd_db_hr_replica` | `hr_db` | - |
| MASTER | `sd_db_config` | `config_db` | 3313 |
| REPLICA | `sd_db_config_replica` | `config_db` | - |

## Operaciones Comunes en phpMyAdmin

### 1. Ver Tablas
1. Conéctate a un servidor (ej: `sd_db_auth`)
2. En el panel izquierdo, haz clic en la base de datos (ej: `auth_db`)
3. Verás la lista de tablas

### 2. Consultar Datos
1. Haz clic en una tabla (ej: `roles`)
2. Automáticamente verás los datos
3. O haz clic en la pestaña "SQL" para ejecutar consultas personalizadas

### 3. Insertar Datos
1. Haz clic en una tabla
2. Pestaña "Insertar"
3. Llena el formulario
4. Clic en "Continuar"

**⚠️ Esto SOLO funciona en MASTERS**. En REPLICAS dará error.

### 4. Ejecutar Consultas SQL
1. Pestaña "SQL" en la parte superior
2. Escribe tu consulta:
   ```sql
   SELECT * FROM roles WHERE name LIKE '%Admin%';
   ```
3. Clic en "Continuar"

### 5. Exportar Base de Datos
1. Selecciona la base de datos en el panel izquierdo
2. Pestaña "Exportar"
3. Elige formato (SQL recomendado)
4. Clic en "Continuar"

### 6. Importar Base de Datos
1. Selecciona la base de datos
2. Pestaña "Importar"
3. Selecciona archivo `.sql`
4. Clic en "Continuar"

## Verificar Replicación desde phpMyAdmin

### Probar INSERT en MASTER
1. Conéctate a `sd_db_auth` (MASTER)
2. Pestaña "SQL"
3. Ejecuta:
   ```sql
   INSERT INTO roles (name) VALUES ('Test from phpMyAdmin');
   ```
4. Verifica que aparece en la tabla

### Verificar en REPLICA
1. **Sin desconectarte**, abre otra pestaña del navegador
2. Ve a `http://localhost:8080`
3. Conéctate a `sd_db_auth_replica` (REPLICA)
4. Ve a la tabla `roles`
5. **Debe aparecer** el registro `Test from phpMyAdmin`

✅ **La replicación funciona correctamente**.

### Intentar INSERT en REPLICA (debe fallar)
1. Conéctate a `sd_db_auth_replica`
2. Pestaña "SQL"
3. Ejecuta:
   ```sql
   INSERT INTO roles (name) VALUES ('Should Fail');
   ```
4. **Error esperado**:
   ```
   #1290 - The MySQL server is running with the --super-read-only option so it cannot execute this statement
   ```

✅ **La REPLICA está protegida contra escrituras**.

## Cambiar Puerto de phpMyAdmin

Si el puerto `8080` está ocupado, puedes cambiarlo:

**Editar** `sd_phpmyadmin.yml`:
```yaml
ports:
  - "8081:80"  # Usa puerto 8081 en lugar de 8080
```

**Reiniciar**:
```bash
docker-compose -f sd_phpmyadmin.yml down
docker-compose -f sd_phpmyadmin.yml up -d
```

**Nueva URL**: `http://localhost:8081`

## Configuración Avanzada (Opcional)

### Conectar a un servidor MySQL externo

Si tienes un servidor MySQL fuera de Docker (ej: en tu máquina o en la nube):

1. En la pantalla de login de phpMyAdmin
2. **Servidor**: `host.docker.internal:3306` (para localhost fuera de Docker)
3. O la IP/hostname del servidor externo

### Predefinir servidores

Si quieres evitar escribir los nombres de los servidores cada vez:

**Crear** `config.user.inc.php`:
```php
<?php
$cfg['Servers'][1]['host'] = 'sd_db_auth';
$cfg['Servers'][1]['verbose'] = 'Autenticación (MASTER)';

$cfg['Servers'][2]['host'] = 'sd_db_branches';
$cfg['Servers'][2]['verbose'] = 'Sucursales (MASTER)';

// ... agregar los demás servidores
?>
```

**Montar** en el contenedor:
```yaml
volumes:
  - ./config.user.inc.php:/etc/phpmyadmin/config.user.inc.php
```

Ahora verás una lista desplegable con todos los servidores.

## Detener phpMyAdmin

```bash
docker-compose -f sd_phpmyadmin.yml down
```

O con el script:
```bash
./stop-all.sh
```

## Reiniciar phpMyAdmin

```bash
docker restart sd_phpmyadmin
```

## Ver Logs de phpMyAdmin

```bash
docker logs sd_phpmyadmin
```

**Para ver en tiempo real**:
```bash
docker logs sd_phpmyadmin -f
```

## Solución de Problemas

### Error: "Cannot connect to MySQL server"
**Causa**: El contenedor de BD no está corriendo o no está en la misma red.

**Solución**:
1. Verifica que el contenedor esté corriendo:
   ```bash
   docker ps | grep sd_db_auth
   ```
2. Verifica que ambos estén en `sd_network`:
   ```bash
   docker network inspect sd_network
   ```

---

### Error: "Access denied for user 'root'"
**Causa**: Contraseña incorrecta.

**Solución**: Usa la contraseña correcta: `3312`

---

### No puedo acceder a `http://localhost:8080`
**Causa**: El puerto está ocupado o el contenedor no está corriendo.

**Solución**:
1. Verifica que phpMyAdmin esté corriendo:
   ```bash
   docker ps | grep phpmyadmin
   ```
2. Verifica los logs:
   ```bash
   docker logs sd_phpmyadmin
   ```
3. Intenta otro puerto (ver "Cambiar Puerto" arriba)

---

### phpMyAdmin se ve mal / sin estilos
**Causa**: Problema de caché del navegador.

**Solución**:
1. Recarga la página con `Ctrl+Shift+R` (forzar recarga)
2. Limpia caché del navegador
3. Intenta en modo incógnito

## Alternativas a phpMyAdmin

Si prefieres otras herramientas:

### Adminer (más ligero)
```yaml
services:
  adminer:
    image: adminer:latest
    ports:
      - "8081:8080"
    networks:
      - sd_network
```

### MySQL Workbench (aplicación de escritorio)
- Descarga: https://www.mysql.com/products/workbench/
- Conecta usando `localhost:3307` (MASTER de Autenticación)

### DBeaver (aplicación de escritorio multiplataforma)
- Descarga: https://dbeaver.io/
- Soporta múltiples motores de BD

## Resumen

✅ **phpMyAdmin configurado correctamente** si:
- Puedes acceder a `http://localhost:8080`
- Puedes conectarte a `sd_db_auth` con root/3312
- Puedes ver las tablas de `auth_db`
- Puedes insertar datos en MASTER
- Los datos aparecen automáticamente en REPLICA
- No puedes insertar datos en REPLICA (error 1290)

---

## ¡Instalación Completa!

Has completado todos los pasos:

1. ✅ Red Docker creada
2. ✅ Configuración MASTER
3. ✅ Contenedores MASTER corriendo
4. ✅ Configuración REPLICA
5. ✅ Contenedores REPLICA corriendo
6. ✅ Replicación configurada
7. ✅ Tablas creadas
8. ✅ Replicación verificada
9. ✅ phpMyAdmin funcionando

**Sistema de Replicación MySQL Master-Slave con 7 Microservicios completamente operativo**.

Para consultar conceptos, troubleshooting o verificación, vuelve al [README principal](README.md).
