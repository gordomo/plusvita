#!/bin/bash

# Script para configurar monitoreo de presentes en producción
# Este script debe ejecutarse dentro del contenedor de www

# Directorio de logs
LOG_DIR="/www/html/public/logs"
LOG_FILE="${LOG_DIR}/setup_monitoreo_$(date +%Y%m%d_%H%M%S).log"

# Asegurar que el directorio existe
mkdir -p $LOG_DIR

# Iniciar el log
echo "=== CONFIGURACIÓN DE MONITOREO DE PRESENTES ===" > $LOG_FILE
date >> $LOG_FILE
echo "" >> $LOG_FILE

# Verificar zona horaria
echo "INFORMACIÓN DE ZONA HORARIA:" >> $LOG_FILE
echo "Sistema: $(date +%Z) ($(date +%z))" >> $LOG_FILE
echo "PHP: $(php -r 'echo date_default_timezone_get();')" >> $LOG_FILE
echo "Hora sistema: $(date +'%Y-%m-%d %H:%M:%S')" >> $LOG_FILE
echo "Hora PHP: $(php -r 'echo (new DateTime())->format("Y-m-d H:i:s");')" >> $LOG_FILE
echo "" >> $LOG_FILE

# Verificar cron actual
echo "CRON ACTUAL:" >> $LOG_FILE
crontab -l >> $LOG_FILE 2>&1
echo "" >> $LOG_FILE

# Configurar nuevo cron con el monitor
echo "CONFIGURANDO CRON CON EL MONITOR:" >> $LOG_FILE
(crontab -l ; echo "*/5 * * * * cd /www/html && /local/bin/php bin/console monitor-presentes >> $LOG_DIR/cron-monitor.log 2>&1") | sort -u | crontab -
echo "Configuración completada." >> $LOG_FILE
echo "Nuevo cron:" >> $LOG_FILE
crontab -l >> $LOG_FILE
echo "" >> $LOG_FILE

# Ejecutar el monitor una vez para probar
echo "EJECUTANDO MONITOR DE PRUEBA:" >> $LOG_FILE
cd /www/html && /local/bin/php bin/console monitor-presentes >> $LOG_FILE 2>&1

echo "" >> $LOG_FILE
echo "MONITOREO CONFIGURADO CORRECTAMENTE" >> $LOG_FILE
echo "Los logs se guardarán en: $LOG_DIR" >> $LOG_FILE

echo "------------------------------------"
echo "Monitoreo configurado correctamente!"
echo "Los logs se guardarán en: $LOG_DIR"
echo "Consulta el log para más detalles: $LOG_FILE"
