# PASO 1: Crear la Red Docker

## Objetivo
Crear una red Docker tipo bridge llamada `sd_network` que permita la comunicación entre todos los contenedores de bases de datos.

## ¿Qué es una red Docker?
Una red Docker es un canal de comunicación aislado donde los contenedores pueden hablar entre sí usando sus nombres como hostnames, sin necesidad de conocer IPs.

## ¿Por qué necesitamos esto?
- **Aislamiento**: Los contenedores solo pueden comunicarse dentro de esta red
- **DNS automático**: Los contenedores se pueden alcanzar por nombre (ej: `sd_db_auth`)
- **Seguridad**: No se exponen puertos al exterior innecesariamente
- **Replicación**: El MASTER y la REPLICA deben poder comunicarse

## Archivo: `create-network.sh`

```bash
#!/bin/bash

echo "========================================="
echo "Creando red Docker para microservicios"
echo "========================================="

# Verificar si la red ya existe
if docker network ls | grep -q sd_network; then
    echo "La red 'sd_network' ya existe"
    docker network inspect sd_network
else
    # Crear la red
    docker network create sd_network
    echo "✓ Red 'sd_network' creada exitosamente"
fi

echo ""
echo "Para ver la red creada:"
echo "  docker network ls | grep sd_network"
echo ""
echo "Para ver detalles de la red:"
echo "  docker network inspect sd_network"
echo ""
```

## Instrucciones de Uso

### 1. Crear el archivo
```bash
cd /tu/carpeta/SIS
nano create-network.sh
# Pega el contenido del script
# Guarda con Ctrl+O, Enter, Ctrl+X
```

### 2. Dar permisos de ejecución
```bash
chmod +x create-network.sh
```

### 3. Ejecutar
```bash
./create-network.sh
```

## Verificación

### Comando 1: Listar redes
```bash
docker network ls | grep sd_network
```

**Salida esperada**:
```
<ID>   sd_network   bridge    local
```

### Comando 2: Inspeccionar red
```bash
docker network inspect sd_network
```

**Salida esperada (fragmento)**:
```json
[
    {
        "Name": "sd_network",
        "Driver": "bridge",
        "Scope": "local",
        "Containers": {}
    }
]
```

## Parámetros de la Red

| Parámetro | Valor | Descripción |
|-----------|-------|-------------|
| **Nombre** | `sd_network` | Identificador de la red |
| **Driver** | `bridge` | Tipo de red (comunicación local) |
| **Scope** | `local` | Ámbito de la red (solo en esta máquina) |

## Solución de Problemas

### Error: "network with name sd_network already exists"
**Causa**: La red ya fue creada anteriormente.

**Solución**: No es un problema, puedes continuar. O si quieres recrearla:
```bash
docker network rm sd_network
./create-network.sh
```

### Error: "Cannot connect to Docker daemon"
**Causa**: Docker no está corriendo.

**Solución**: 
```bash
# En macOS
open -a Docker

# En Linux
sudo systemctl start docker
```

## ¿Qué sigue?
Una vez creada la red, puedes continuar con el [PASO 2: Configuración de MASTER](PASO_02_CONFIG_MASTER.md).

---

**Nota importante**: Esta red es externa para los contenedores. En cada archivo `docker-compose.yml` debes especificar:
```yaml
networks:
  sd_network:
    external: true
```

Esto indica que la red ya existe y no debe ser creada por Docker Compose.
