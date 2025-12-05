# GUÍA COMPLETA: Configuración de Replicación MySQL Master-Slave

Esta guía te llevará paso a paso para crear una infraestructura de 7 microservicios con bases de datos MySQL replicadas (Master-Slave).

## 📋 Índice

1. [Paso 1: Crear la Red Docker](#paso-1)
2. [Paso 2: Crear Archivos de Configuración Master](#paso-2)
3. [Paso 3: Crear Contenedores Master](#paso-3)
4. [Paso 4: Crear Archivos de Configuración Replica](#paso-4)
5. [Paso 5: Crear Contenedores Replica](#paso-5)
6. [Paso 6: Configurar Replicación](#paso-6)
7. [Paso 7: Crear Tablas](#paso-7)
8. [Paso 8: Verificar Replicación](#paso-8)
9. [Paso 9: Instalar phpMyAdmin](#paso-9)

---

## Requisitos Previos

- Docker y Docker Compose instalados
- Puertos 8080 disponible (para phpMyAdmin)
- Conocimientos básicos de MySQL y Docker

## Credenciales por Defecto

- **Usuario root**: `root` / Contraseña: `3312`
- **Usuario aplicación**: `rmichelv` / Contraseña: `usuario123`
- **Usuario replicación**: `replicator` / Contraseña: `replicator_password`

---

<a name="paso-1"></a>
## Paso 1: Crear la Red Docker

### ¿Qué hace?
Crea una red Docker aislada llamada `sd_network` que permite la comunicación entre todos los contenedores de bases de datos.

### ¿Por qué es necesario?
Sin esta red, los contenedores no podrían comunicarse entre sí para realizar la replicación.

### Archivo: `create-network.sh`

Consulta el archivo detallado en: [PASO_01_RED.md](PASO_01_RED.md)

### Ejecutar

```bash
chmod +x create-network.sh
./create-network.sh
```

### Verificar

```bash
docker network ls | grep sd_network
```

**Resultado esperado**: Deberías ver una línea con `sd_network` y tipo `bridge`.

---

<a name="paso-2"></a>
## Paso 2: Crear Archivos de Configuración Master

### ¿Qué hace?
Crea archivos `master.cnf` para cada servicio que configuran MySQL como servidor MASTER de replicación.

### ¿Por qué es necesario?
Estos archivos habilitan el binary log (binlog) que registra todos los cambios en la base de datos, permitiendo que las réplicas los puedan replicar.

### Archivos a crear

Consulta los detalles completos en: [PASO_02_CONFIG_MASTER.md](PASO_02_CONFIG_MASTER.md)

**Ubicación**: Crear un archivo `master.cnf` en cada carpeta de servicio.

**Parámetros importantes**:
- `server-id`: ID único del servidor (1-7 para masters)
- `log_bin`: Habilita el binary log
- `binlog_do_db`: Especifica qué base de datos replicar
- `binlog_format`: Formato de replicación (ROW es el más seguro)

---

<a name="paso-3"></a>
## Paso 3: Crear Contenedores Master

### ¿Qué hace?
Crea 7 contenedores MySQL que funcionarán como servidores MASTER (lectura/escritura).

### ¿Por qué es necesario?
Los MASTER son los servidores principales donde se realizan todas las operaciones de escritura.

### Archivos Docker Compose

Consulta todos los archivos en: [PASO_03_CONTENEDORES_MASTER.md](PASO_03_CONTENEDORES_MASTER.md)

**Servicios**:
1. `sd_db_auth` - Autenticación y usuarios (auth_db)
2. `sd_db_branches` - Sucursales (branches_db)
3. `sd_db_inventory` - Inventario (inventory_db)
4. `sd_db_sales` - Ventas (sales_db)
5. `sd_db_reservations` - Reservaciones (reservations_db)
6. `sd_db_hr` - Recursos Humanos (hr_db)
7. `sd_db_config` - Configuración (config_db)

### Iniciar Masters

```bash
cd servicio_de_autenticacion_y_usuarios
docker compose -f sd_db_auth.yml up -d

cd ../servicio_de_sucursales
docker compose -f sd_db_branches.yml up -d

# ... repetir para los otros 5 servicios
```

### Verificar

```bash
docker ps | grep sd_db
```

**Resultado esperado**: Deberías ver 7 contenedores corriendo con estado "healthy".

---

<a name="paso-4"></a>
## Paso 4: Crear Archivos de Configuración Replica

### ¿Qué hace?
Crea archivos `replica.cnf` para cada servicio que configuran MySQL como servidor REPLICA (SLAVE).

### ¿Por qué es necesario?
Estos archivos configuran el servidor para recibir y aplicar los cambios del MASTER.

### Archivos a crear

Consulta los detalles en: [PASO_04_CONFIG_REPLICA.md](PASO_04_CONFIG_REPLICA.md)

**Parámetros importantes**:
- `server-id`: ID único del servidor (11-17 para replicas)
- `relay-log`: Log de relay para aplicar cambios
- `replicate-do-db`: Especifica qué base de datos replicar

**NOTA**: NO incluimos `read_only` en el archivo porque se activará después mediante comando SQL.

---

<a name="paso-5"></a>
## Paso 5: Crear Contenedores Replica

### ¿Qué hace?
Crea 7 contenedores MySQL que funcionarán como servidores REPLICA (solo lectura).

### ¿Por qué es necesario?
Las REPLICAS distribuyen la carga de lectura y sirven como backup en tiempo real.

### Archivos Docker Compose

Consulta todos los archivos en: [PASO_05_CONTENEDORES_REPLICA.md](PASO_05_CONTENEDORES_REPLICA.md)

**Servicios**:
1. `sd_db_auth_replica`
2. `sd_db_branches_replica`
3. `sd_db_inventory_replica`
4. `sd_db_sales_replica`
5. `sd_db_reservations_replica`
6. `sd_db_hr_replica`
7. `sd_db_config_replica`

### Iniciar Replicas

**IMPORTANTE**: Espera 15 segundos después de iniciar los MASTERS antes de iniciar las REPLICAS.

```bash
cd servicio_de_autenticacion_y_usuarios
docker compose -f sd_db_auth_replica.yml up -d

cd ../servicio_de_sucursales
docker compose -f sd_db_branches_replica.yml up -d

# ... repetir para los otros 5 servicios
```

---

<a name="paso-6"></a>
## Paso 6: Configurar Replicación

### ¿Qué hace?
Conecta cada REPLICA con su MASTER correspondiente y activa `super_read_only`.

### ¿Por qué es necesario?
Sin esta configuración, las réplicas no sincronizarán los datos del master.

### Script de Configuración

Consulta el script completo en: [PASO_06_SETUP_REPLICATION.md](PASO_06_SETUP_REPLICATION.md)

**El script hace**:
1. Crea usuario `replicator` en cada MASTER
2. Obtiene la posición actual del binlog del MASTER
3. Configura la REPLICA para conectarse al MASTER
4. Inicia la replicación
5. Activa `read_only` y `super_read_only` en las REPLICAS

### Ejecutar

```bash
chmod +x setup-replication.sh
./setup-replication.sh
```

### Verificar

```bash
docker exec -it sd_db_auth_replica mysql -uroot -p3312 -e "SHOW SLAVE STATUS\G" | grep -E "(Slave_IO_Running|Slave_SQL_Running)"
```

**Resultado esperado**:
```
Slave_IO_Running: Yes
Slave_SQL_Running: Yes
```

---

<a name="paso-7"></a>
## Paso 7: Crear Tablas

### ¿Qué hace?
Crea todas las tablas del sistema en los servidores MASTER.

### ¿Por qué es necesario?
Las tablas se crean SOLO en el MASTER, y la replicación las copiará automáticamente a las REPLICAS.

### Schema SQL

Consulta el schema completo en: [PASO_07_CREAR_TABLAS.md](PASO_07_CREAR_TABLAS.md)

**Bases de datos y tablas**:
- `auth_db`: roles, users, password_reset_tokens, sessions
- `branches_db`: branches
- `inventory_db`: products, product_branches, product_stores, purchases
- `sales_db`: sales, sale_items, devolutions
- `reservations_db`: customers, reservations, reservation_items
- `hr_db`: attendance_records, salary_adjustments, salaries
- `config_db`: usd_exchange_rates, cache, jobs, etc.

### Ejecutar

Conéctate a cada MASTER y ejecuta los comandos SQL correspondientes, O usa phpMyAdmin (Paso 9).

---

<a name="paso-8"></a>
## Paso 8: Verificar Replicación

### ¿Qué hace?
Prueba que los datos se replican correctamente del MASTER a la REPLICA.

### Prueba 1: Insertar en MASTER

```bash
docker exec -it sd_db_auth mysql -uroot -p3312 -e "USE auth_db; INSERT INTO roles (name) VALUES ('Administrador');"
```

### Prueba 2: Verificar en MASTER

```bash
docker exec -it sd_db_auth mysql -uroot -p3312 -e "USE auth_db; SELECT * FROM roles;"
```

### Prueba 3: Verificar en REPLICA

```bash
docker exec -it sd_db_auth_replica mysql -uroot -p3312 -e "USE auth_db; SELECT * FROM roles;"
```

**Resultado esperado**: Los datos deben ser IDÉNTICOS en ambos.

### Prueba 4: Intentar escribir en REPLICA (debe fallar)

```bash
docker exec -it sd_db_auth_replica mysql -uroot -p3312 -e "USE auth_db; INSERT INTO roles (name) VALUES ('Test');"
```

**Resultado esperado**:
```
ERROR 1290 (HY000): The MySQL server is running with the --super-read-only option
```

---

<a name="paso-9"></a>
## Paso 9: Instalar phpMyAdmin

### ¿Qué hace?
Instala phpMyAdmin para gestionar visualmente todas las bases de datos.

### Archivo Docker Compose

Consulta el archivo en: [PASO_09_PHPMYADMIN.md](PASO_09_PHPMYADMIN.md)

### Iniciar

```bash
cd phpmyadmin
docker compose up -d
```

### Acceder

Abre tu navegador en: **http://localhost:8080**

**Servidores disponibles**:
- Masters: `sd_db_auth`, `sd_db_branches`, `sd_db_inventory`, etc.
- Replicas: `sd_db_auth_replica`, `sd_db_branches_replica`, etc.

**Credenciales**: root / 3312

---

## 🎯 Resumen de Comandos Útiles

### Iniciar todo
```bash
./start-all.sh
```

### Detener todo
```bash
./stop-all.sh
```

### Ver estado de replicación
```bash
docker exec -it sd_db_auth_replica mysql -uroot -p3312 -e "SHOW SLAVE STATUS\G"
```

### Ver logs de un contenedor
```bash
docker logs sd_db_auth
```

### Reiniciar un contenedor
```bash
docker restart sd_db_auth
```

---

## ⚠️ Solución de Problemas

### Error: "Slave_IO_Running: Connecting"
**Causa**: El MASTER no está accesible o credenciales incorrectas.
**Solución**: Verifica que el MASTER esté corriendo y que el usuario `replicator` exista.

### Error: "Access denied for user 'root'@'localhost'"
**Causa**: Contraseña incorrecta o usuario no existe.
**Solución**: Elimina el volumen y vuelve a crear el contenedor.

### Las tablas no se replican
**Causa**: La replicación no está configurada correctamente.
**Solución**: Ejecuta nuevamente `./setup-replication.sh`.

---

## 📚 Conceptos Clave

**MASTER**: Servidor principal donde se realizan las escrituras (INSERT, UPDATE, DELETE).

**REPLICA/SLAVE**: Servidor que copia automáticamente los datos del MASTER. Solo lectura.

**Binary Log (binlog)**: Archivo que registra todos los cambios en el MASTER para que las réplicas los puedan aplicar.

**Relay Log**: Log en la REPLICA que almacena los cambios recibidos del MASTER antes de aplicarlos.

**read_only**: Modo que impide escrituras a usuarios normales.

**super_read_only**: Modo que impide escrituras incluso a usuarios con privilegio SUPER (como root).

**Replication Lag**: Retraso entre el MASTER y la REPLICA. Normalmente es de milisegundos.

---

## ✅ Checklist de Verificación

- [ ] Red `sd_network` creada
- [ ] 7 contenedores MASTER corriendo
- [ ] 7 contenedores REPLICA corriendo
- [ ] Replicación configurada (IO Thread: Yes, SQL Thread: Yes)
- [ ] `super_read_only` activado en todas las réplicas
- [ ] Tablas creadas en todos los MASTERS
- [ ] Tablas replicadas en todas las REPLICAS
- [ ] Prueba de inserción en MASTER funciona
- [ ] Prueba de inserción en REPLICA falla (ERROR 1290)
- [ ] phpMyAdmin accesible en puerto 8080

---

**¡Felicidades! Tu infraestructura de replicación MySQL está lista para producción.**
