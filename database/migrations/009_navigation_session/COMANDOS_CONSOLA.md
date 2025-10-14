# ⚡ Comandos Rápidos - Sistema de Persistencia de Navegación

## 🚀 INSTALACIÓN COMPLETA (Copiar y Pegar)

### PowerShell - Instalación Automatizada

```powershell
# Navegar al proyecto
cd C:\laragon\www\angelow

# Opción 1: Script automatizado (RECOMENDADO)
.\database\migrations\009_navigation_session\install.ps1

# Opción 2: Manual
# Paso 1: Verificar
Get-Content "database\migrations\009_navigation_session\002_verify_migration.sql" | mysql -u root -p angelow

# Paso 2: Backup
New-Item -ItemType Directory -Force -Path "database\backups"
$fecha = Get-Date -Format "yyyyMMdd_HHmmss"
mysqldump -u root -p angelow > "database\backups\backup_navegacion_$fecha.sql"

# Paso 3: Migración
Get-Content "database\migrations\009_navigation_session\001_create_navigation_session.sql" | mysql -u root -p angelow

# Paso 4: Tests
php tests\delivery\test_navigation_session.php

# Paso 5: Verificar
mysql -u root -p angelow -e "SHOW TABLES LIKE 'delivery_navigation%';"
```

---

## 🔍 CONSULTAS DE VERIFICACIÓN

### Ver tablas creadas

```powershell
mysql -u root -p angelow -e "SHOW TABLES LIKE 'delivery_navigation%';"
```

### Ver procedimientos almacenados

```powershell
mysql -u root -p angelow -e "SHOW PROCEDURE STATUS WHERE Db = 'angelow' AND Name LIKE '%Navigation%';"
```

### Ver triggers

```powershell
mysql -u root -p angelow -e "SHOW TRIGGERS WHERE \`Table\` = 'delivery_navigation_sessions';"
```

### Ver vistas

```powershell
mysql -u root -p angelow -e "SELECT * FROM v_active_navigation_sessions;"
```

---

## 📊 CONSULTAS DE MONITOREO

### Ver sesiones activas

```sql
-- Desde MySQL
mysql -u root -p angelow

USE angelow;
SELECT * FROM v_active_navigation_sessions;
```

```powershell
# Desde PowerShell (una línea)
mysql -u root -p angelow -e "SELECT * FROM v_active_navigation_sessions;"
```

### Ver estado de un delivery específico

```sql
-- Cambiar 1 por el delivery_id real
CALL GetNavigationState(1, 'DRV001');
```

```powershell
# Desde PowerShell
mysql -u root -p angelow -e "CALL GetNavigationState(1, 'DRV001');"
```

### Ver estadísticas del día

```sql
SELECT 
    COUNT(*) as sesiones_hoy,
    SUM(CASE WHEN session_status = 'navigating' THEN 1 ELSE 0 END) as navegando,
    SUM(CASE WHEN session_status = 'paused' THEN 1 ELSE 0 END) as pausadas,
    SUM(CASE WHEN session_status = 'completed' THEN 1 ELSE 0 END) as completadas
FROM delivery_navigation_sessions
WHERE DATE(created_at) = CURDATE();
```

### Ver últimas actualizaciones

```sql
SELECT 
    o.order_number,
    u.name as driver,
    dns.session_status,
    dns.last_update_at,
    TIMESTAMPDIFF(SECOND, dns.last_update_at, NOW()) as segundos_sin_actualizar
FROM delivery_navigation_sessions dns
INNER JOIN order_deliveries od ON dns.delivery_id = od.id
INNER JOIN orders o ON od.order_id = o.id
INNER JOIN users u ON dns.driver_id = u.id
WHERE dns.session_status IN ('navigating', 'paused')
ORDER BY dns.last_update_at DESC
LIMIT 10;
```

---

## 🧪 TESTS

### Ejecutar todos los tests

```powershell
cd C:\laragon\www\angelow
php tests\delivery\test_navigation_session.php
```

### Resultado esperado

```
✅ PASS: Verificar existencia de tablas
✅ PASS: Verificar procedimientos almacenados
✅ PASS: Iniciar navegación
✅ PASS: Actualizar ubicación
✅ PASS: Pausar navegación
✅ PASS: Reanudar navegación
✅ PASS: Guardar datos de ruta
✅ PASS: Completar navegación
✅ PASS: Registrar eventos de navegación
✅ PASS: Verificar triggers automáticos
✅ PASS: Verificar vistas

Total de tests ejecutados: 11
Tests exitosos: 11
Tests fallidos: 0

🎉 ¡Todos los tests pasaron correctamente!
```

---

## 🔧 COMANDOS DE DIAGNÓSTICO

### Verificar conexión MySQL

```powershell
mysql -u root -p -e "SELECT VERSION();"
```

### Verificar base de datos

```powershell
mysql -u root -p -e "SHOW DATABASES LIKE 'angelow';"
```

### Ver tamaño de la BD

```powershell
mysql -u root -p angelow -e "SELECT table_name AS 'Table', ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)' FROM information_schema.TABLES WHERE table_schema = 'angelow' ORDER BY (data_length + index_length) DESC;"
```

### Ver estructura de tabla

```powershell
mysql -u root -p angelow -e "DESCRIBE delivery_navigation_sessions;"
```

### Contar registros

```powershell
mysql -u root -p angelow -e "SELECT COUNT(*) as total FROM delivery_navigation_sessions;"
```

---

## 💾 BACKUP Y RESTORE

### Crear backup

```powershell
# Con fecha automática
$fecha = Get-Date -Format "yyyyMMdd_HHmmss"
mysqldump -u root -p angelow > "backup_angelow_$fecha.sql"

# Ubicación específica
mysqldump -u root -p angelow > "C:\laragon\www\angelow\database\backups\backup_$fecha.sql"
```

### Restaurar backup

```powershell
mysql -u root -p angelow < "backup_angelow_20251013_123000.sql"
```

---

## 🔄 ROLLBACK (Deshacer cambios)

### Eliminar todo el sistema

```sql
USE angelow;

-- Eliminar triggers
DROP TRIGGER IF EXISTS create_navigation_session_on_accept;
DROP TRIGGER IF EXISTS log_navigation_session_changes;

-- Eliminar procedimientos
DROP PROCEDURE IF EXISTS StartNavigation;
DROP PROCEDURE IF EXISTS PauseNavigation;
DROP PROCEDURE IF EXISTS UpdateNavigationLocation;
DROP PROCEDURE IF EXISTS GetNavigationState;
DROP PROCEDURE IF EXISTS CompleteNavigation;
DROP PROCEDURE IF EXISTS SaveRouteData;

-- Eliminar vistas
DROP VIEW IF EXISTS v_active_navigation_sessions;

-- Eliminar tablas (¡CUIDADO! Borra los datos)
DROP TABLE IF EXISTS delivery_navigation_events;
DROP TABLE IF EXISTS delivery_navigation_sessions;
```

```powershell
# Desde PowerShell
mysql -u root -p angelow -e "DROP TABLE IF EXISTS delivery_navigation_events, delivery_navigation_sessions;"
```

---

## 📝 CONSULTAS ÚTILES DEL DÍA A DÍA

### Dashboard rápido

```sql
-- Copiar todo y ejecutar
SELECT 'ESTADO ACTUAL DEL SISTEMA' as '═══════════════════════════════';

SELECT 
    '🚗 NAVEGANDO AHORA' as Estado,
    COUNT(*) as Cantidad
FROM delivery_navigation_sessions 
WHERE session_status = 'navigating'

UNION ALL

SELECT 
    '⏸️ PAUSADAS' as Estado,
    COUNT(*) as Cantidad
FROM delivery_navigation_sessions 
WHERE session_status = 'paused'

UNION ALL

SELECT 
    '✅ COMPLETADAS HOY' as Estado,
    COUNT(*) as Cantidad
FROM delivery_navigation_sessions 
WHERE session_status = 'completed' 
AND DATE(navigation_completed_at) = CURDATE();
```

### Ver sesiones con problemas

```sql
-- Sesiones sin actualizar en más de 5 minutos
SELECT 
    o.order_number as 'Orden',
    u.name as 'Driver',
    u.phone as 'Teléfono',
    dns.session_status as 'Estado',
    TIMESTAMPDIFF(MINUTE, dns.last_update_at, NOW()) as 'Minutos_Sin_Actualizar',
    CASE 
        WHEN TIMESTAMPDIFF(MINUTE, dns.last_update_at, NOW()) > 10 THEN '🔴 CRÍTICO'
        WHEN TIMESTAMPDIFF(MINUTE, dns.last_update_at, NOW()) > 5 THEN '🟡 ADVERTENCIA'
        ELSE '🟢 OK'
    END as 'Alerta'
FROM delivery_navigation_sessions dns
INNER JOIN order_deliveries od ON dns.delivery_id = od.id
INNER JOIN orders o ON od.order_id = o.id
INNER JOIN users u ON dns.driver_id = u.id
WHERE dns.session_status IN ('navigating', 'paused')
AND TIMESTAMPDIFF(MINUTE, dns.last_update_at, NOW()) > 5
ORDER BY dns.last_update_at ASC;
```

### Estadísticas por driver

```sql
SELECT 
    u.name as 'Driver',
    COUNT(*) as 'Total_Sesiones',
    SUM(CASE WHEN dns.session_status = 'completed' THEN 1 ELSE 0 END) as 'Completadas',
    ROUND(AVG(dns.total_distance_km), 2) as 'Distancia_Promedio_KM',
    ROUND(AVG(dns.average_speed_kmh), 1) as 'Velocidad_Promedio',
    TIME_FORMAT(SEC_TO_TIME(AVG(dns.total_navigation_time_seconds)), '%H:%i:%s') as 'Tiempo_Promedio'
FROM delivery_navigation_sessions dns
INNER JOIN users u ON dns.driver_id = u.id
WHERE DATE(dns.created_at) = CURDATE()
GROUP BY u.id, u.name
ORDER BY COUNT(*) DESC;
```

---

## 🎯 COMANDOS DE PRUEBA MANUAL

### Crear sesión de prueba

```sql
-- Simular inicio de navegación
CALL StartNavigation(
    1,                              -- delivery_id
    'DRV001',                       -- driver_id
    -34.6037,                       -- lat
    -58.3816,                       -- lng
    '{"device": "test"}'           -- device_info
);
```

### Actualizar ubicación

```sql
CALL UpdateNavigationLocation(
    1,          -- delivery_id
    'DRV001',   -- driver_id
    -34.6040,   -- lat
    -58.3820,   -- lng
    35.5,       -- speed
    5.2,        -- distance_remaining
    900,        -- eta_seconds
    85          -- battery_level
);
```

### Ver resultado

```sql
CALL GetNavigationState(1, 'DRV001');
```

---

## 🔍 LOGS Y DEBUGGING

### Ver logs de PHP

```powershell
# Windows
Get-Content "C:\laragon\www\angelow\storage\logs\error.log" -Tail 50

# Ver en tiempo real
Get-Content "C:\laragon\www\angelow\storage\logs\error.log" -Wait
```

### Ver logs de MySQL

```powershell
# Ubicación típica en Laragon
Get-Content "C:\laragon\bin\mysql\mysql-8.0.30\data\*.err" -Tail 50
```

### Habilitar query log en MySQL (temporal)

```sql
SET GLOBAL general_log = 'ON';
SET GLOBAL log_output = 'TABLE';

-- Ver queries
SELECT * FROM mysql.general_log 
WHERE command_type = 'Query' 
ORDER BY event_time DESC 
LIMIT 50;

-- Deshabilitar
SET GLOBAL general_log = 'OFF';
```

---

## 📞 AYUDA RÁPIDA

### ¿Las tablas no existen?

```powershell
Get-Content "database\migrations\009_navigation_session\001_create_navigation_session.sql" | mysql -u root -p angelow
```

### ¿Los procedimientos no existen?

```powershell
mysql -u root -p angelow -e "SHOW PROCEDURE STATUS WHERE Db = 'angelow';"
```

### ¿Los tests fallan?

```powershell
php tests\delivery\test_navigation_session.php
```

### ¿Error de conexión?

```powershell
mysql -u root -p -e "SELECT 1;"
```

---

## ✅ CHECKLIST RÁPIDO

```powershell
# Copiar todo y ejecutar
cd C:\laragon\www\angelow

# 1. Verificar tablas
mysql -u root -p angelow -e "SHOW TABLES LIKE 'delivery_navigation%';"

# 2. Contar registros
mysql -u root -p angelow -e "SELECT COUNT(*) FROM delivery_navigation_sessions;"

# 3. Ver procedimientos
mysql -u root -p angelow -e "SHOW PROCEDURE STATUS WHERE Db = 'angelow' AND Name LIKE '%Navigation%';"

# 4. Ejecutar tests
php tests\delivery\test_navigation_session.php

# 5. Ver sesiones activas
mysql -u root -p angelow -e "SELECT * FROM v_active_navigation_sessions;"
```

---

**Tip:** Guarda este archivo para referencia rápida. Todos los comandos están listos para copiar y pegar.
