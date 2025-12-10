# Guion de Presentación: Sistema Distribuido E-WTTO

Este guion está diseñado para ser leído o parafraseado mientras avanzas por las diapositivas de la presentación HTML.

---

## 0. Portada (Slide 0)
**(Inicio)**
"Buenos días/tardes. Hoy voy a presentar el proyecto **E-WTTO**, un sistema distribuido de microservicios diseñado para un entorno corporativo."

"El objetivo principal fue construir una arquitectura robusta, escalable y tolerante a fallos, utilizando tecnologías estándar de la industria como **Docker**, **Laravel** y **MySQL** en configuración de Alta Disponibilidad."

---

## SECCIÓN 1: DISEÑO Y ARQUITECTURA (Slide Sec-1)
**(Avanzar diapositiva)**
"Comencemos con el primer paso: El Diseño y la Arquitectura del sistema."

### 1.1 Diagrama de Microservicios (Slide 1-1)
**(Esperar a que se dibuje el diagrama en el Canvas)**
"Como pueden ver en el diagrama que se está generando, hemos dividido el dominio del problema en **7 servicios independientes**."

"Tenemos un **API Gateway** en el puerto 8000 que actúa como la única puerta de entrada. Detrás de él, servicios específicos manejan la lógica de negocio:
*   **Auth Service:** Para seguridad y usuarios.
*   **Branch, Inventory, Sales:** Para el núcleo operativo.
*   Cada servicio es autónomo y no comparte base de datos con los demás."

### 1.2 Comunicación entre Servicios (Slide 1-2)
**(Avanzar)**
"Para la comunicación entre estos servicios, hemos optado por un enfoque **Síncrono vía REST**."

"¿Por qué REST y no colas de mensajes asíncronas?
Para el alcance de este proyecto, estimado en unos 20 usuarios concurrentes, REST nos ofrece **simplicidad y facilidad de depuración**. Usamos JSON estándar y autenticación JWT. Esto reduce la complejidad operativa sin sacrificar el rendimiento necesario para este volumen de carga."

### 1.3 Diseño de Base de Datos (Slide 1-3)
**(Avanzar)**
"A nivel de datos, aplicamos el patrón **Database-per-Service**. Esto garantiza un aislamiento total."

"Pero lo más importante es la topología de **Alta Disponibilidad**:
Cada servicio tiene **dos contenedores de base de datos**:
1.  Un **Master** para escritura y lectura.
2.  Una **Réplica** de solo lectura que se sincroniza en tiempo real mediante Binlogs.
Esto nos da un total de 14 contenedores solo para la capa de persistencia."

### 1.4 Estrategia de Despliegue (Slide 1-4)
**(Avanzar)**
"Todo esto se despliega en un **VPS Hostinger Plan Business** con Ubuntu."

"Hemos configurado una red interna de Docker llamada `sd_network`. Es una red tipo *bridge* que aísla los servicios.
Si miramos la terminal simulada, verán que tenemos **31 contenedores** corriendo y volúmenes persistentes para asegurar que los datos no se pierdan si un contenedor se reinicia."

### 1.5 Escalabilidad y Tolerancia (Slide 1-5)
**(Avanzar)**
"Teóricamente, nuestra arquitectura soporta:
*   **Escalabilidad Horizontal:** Podemos añadir más instancias de los servicios sin afectar a los demás.
*   **Escalabilidad Vertical:** Podemos aumentar la RAM y CPU del VPS.
*   **Tolerancia a Fallos:** Gracias a las políticas de reinicio de Docker y la replicación de base de datos que acabamos de ver."

---

## SECCIÓN 2: IMPLEMENTACIÓN (Slide Sec-2)
**(Avanzar)**
"Pasemos ahora al Paso 2: Cómo implementamos esto técnicamente."

### 2.1 Código Fuente (Slide 2-1)
**(Avanzar)**
"Utilizamos una estructura de **Monorepo**. Dentro de la carpeta `SIS`, cada servicio tiene su propio directorio (`servicio_ventas`, `servicio_auth`, etc.)."

"El stack tecnológico elegido es **PHP 8.3 con Laravel 11**. Usamos **Nginx** como servidor web. Esta combinación es madura, estable y permite un desarrollo rápido."

### 2.2 Orquestación (Slide 2-2)
**(Avanzar)**
"Para la orquestación, no usamos un solo archivo gigante. En su lugar, cada microservicio tiene su propio `docker-compose.yml`."

"Esto nos da modularidad. Como ven en el ejemplo, definimos el servicio de API y su base de datos de forma encapsulada, inyectando las variables de entorno necesarias para que se conecten a la red `sd_network`."

### 2.3 Configuración de Réplicas (Slide 2-3)
**(Avanzar)**
"La replicación de MySQL no es mágica, requiere configuración.
*   En el **Master**, activamos el `log-bin` y le asignamos un ID único.
*   En la **Réplica**, la configuramos como `read-only` para evitar inconsistencias de datos y definimos el `relay-log`."

### 2.4 Automatización (Slide 2-4)
**(Avanzar)**
"Configurar 14 bases de datos manualmente sería un error humano garantizado. Por eso creamos scripts de automatización en Bash."

"Destaco el script `setup-replication.sh`. Este script recorre cada par de bases de datos, configura al Master, obtiene la posición del log binario y conecta a la Réplica automáticamente. Como ven en la terminal, el proceso es transparente y rápido."

### 2.5 Inicialización Local (Slide 2-5)
**(Avanzar)**
"Para levantar todo el entorno desde cero, solo necesitamos ejecutar una secuencia de comandos: crear la red, levantar las bases de datos, configurar la replicación y finalmente levantar los servicios y correr las migraciones. Todo está documentado para ser reproducible."

---

## SECCIÓN 3: ESCALABILIDAD Y HA (Slide Sec-3)
**(Avanzar)**
"Finalmente, el Paso 3: Pruebas de Escalabilidad y Alta Disponibilidad."

### 3.1 Demostración (Slide 3-1)
**(Avanzar)**
"Aquí es donde vemos la potencia del sistema. Tenemos **31 contenedores** activos."

"Quiero resaltar el patrón **Sidecar** que usamos: separamos **Nginx** (servidor web) de **PHP-FPM** (procesador).
*   Nginx maneja las conexiones y archivos estáticos de forma eficiente.
*   PHP se dedica solo a procesar lógica.
Esto mejora el rendimiento comparado con usar el servidor integrado de PHP."

### 3.2 Balanceador de Carga (Slide 3-2)
**(Avanzar)**
"Una decisión importante de diseño: **NO implementamos un balanceador de carga complejo**."

"¿Por qué? Para 20 usuarios, un balanceador añade un punto único de fallo y complejidad de configuración innecesaria.
Nuestra alternativa es usar el **API Gateway** como punto de entrada único y confiar en la escalabilidad vertical del VPS si fuera necesario. Es una decisión pragmática basada en el requerimiento."

### 3.3 Pruebas de Carga (Slide 3-3)
**(Avanzar)**
"Sometimos al sistema a pruebas de estrés con **Apache Bench**."
"Lanzamos 100 peticiones con concurrencia de 10.
El resultado: **0 fallos** y una capacidad de **23 peticiones por segundo**. Esto es más que suficiente para el entorno corporativo objetivo."

### 3.4 Failover Automático (Slide 3-4)
**(Avanzar)**
"Para la Alta Disponibilidad, desarrollamos un script llamado `watchdog.sh`."

"Este script actúa como un monitor constante.
1.  Hace ping al Master cada 5 segundos.
2.  Si detecta una caída, automáticamente detiene la replicación en el esclavo.
3.  Promueve al esclavo para que sea el nuevo Master (escritura).
Como ven en el log, el sistema se recupera solo sin intervención humana."

### 3.5 Recuperación de Fallos (Slide 3-5)
**(Avanzar)**
"Definimos dos niveles de recuperación:
1.  **Nivel 1 (Automático):** Lo que hace Docker (reiniciar contenedores) y el Watchdog.
2.  **Nivel 2 (Manual):** Si un nodo antiguo vuelve, debemos reconfigurarlo manualmente para que sea la nueva réplica, usando los comandos que ven en pantalla."

---

## DEFENSA Y Q&A (Slide Q&A)
**(Avanzar)**
"Para concluir, anticipo algunas preguntas comunes sobre las decisiones de arquitectura:"

*   **¿Por qué no RabbitMQ?** Como mencioné, la latencia de REST es baja (<500ms) y RabbitMQ añade complejidad operativa injustificada para este tamaño.
*   **¿El Watchdog es un punto de fallo?** Sí, en este entorno académico. En producción real usaríamos Kubernetes o herramientas distribuidas, pero para un VPS único, es una solución efectiva.
*   **¿Seguridad?** La red interna protege las bases de datos; solo el puerto 8000 está expuesto.

---

## CIERRE (Slide Fin)
**(Avanzar)**
"Muchas gracias. Este ha sido el resumen técnico del sistema E-WTTO. Quedo atento a sus preguntas."
