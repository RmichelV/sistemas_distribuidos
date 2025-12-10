#!/bin/bash

# Script de prueba de carga simple usando Apache Bench (ab)
# Fuente original conceptual: Documentación de Apache HTTP Server Benchmarking Tool
# Adaptación: Se configura para atacar el endpoint de health del API Gateway y simular concurrencia.

URL="http://localhost:8000/api/health"
CONCURRENCY=10
REQUESTS=100
OUTPUT_FILE="resultado_carga.txt"

echo "==================================================="
echo " INICIANDO PRUEBA DE CARGA (Simulación de Tráfico)"
echo "==================================================="
echo "Target: $URL"
echo "Usuarios concurrentes simulados: $CONCURRENCY"
echo "Total de peticiones: $REQUESTS"
echo "---------------------------------------------------"

# Verificar si ab está instalado
if ! command -v ab &> /dev/null; then
    echo "Error: 'ab' (Apache Bench) no está instalado."
    echo "En macOS puedes instalarlo con: brew install httpd"
    echo "En Linux (Debian/Ubuntu): sudo apt-get install apache2-utils"
    exit 1
fi

# Ejecutar prueba
ab -n $REQUESTS -c $CONCURRENCY $URL > $OUTPUT_FILE

echo "Prueba finalizada. Resultados guardados en $OUTPUT_FILE"
echo "---------------------------------------------------"
echo "Resumen de resultados:"
grep "Requests per second" $OUTPUT_FILE
grep "Time per request" $OUTPUT_FILE | head -n 1
grep "Failed requests" $OUTPUT_FILE
echo "==================================================="
