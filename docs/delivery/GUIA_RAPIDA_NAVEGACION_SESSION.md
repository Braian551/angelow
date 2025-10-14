# Guía Rápida: Sistema de Persistencia de Navegación

## 🚀 Inicio Rápido

### 1. Instalación (Ejecutar en consola MySQL)

```bash
# Conectar a MySQL
mysql -u root -p angelow

# PASO 1: Verificar antes de migrar
source database/migrations/009_navigation_session/002_verify_migration.sql

# PASO 2: Aplicar migración
source database/migrations/009_navigation_session/001_create_navigation_session.sql

# PASO 3: Verificar instalación
SELECT 'OK' as status FROM delivery_navigation_sessions LIMIT 1;
```

### 2. Ejecutar Tests

```bash
php tests/delivery/test_navigation_session.php
```

### 3. Consultar Estado (en consola MySQL)

```sql
-- Ver todas las sesiones activas
SELECT * FROM v_active_navigation_sessions;

-- Ver sesión específica
SET @delivery_id = 1;
CALL GetNavigationState(@delivery_id, 'DRV001');

-- Ver eventos de una sesión
SELECT * FROM delivery_navigation_events 
WHERE delivery_id = @delivery_id 
ORDER BY created_at DESC 
LIMIT 10;
```

---

## 💡 Casos de Uso Comunes

### Caso 1: Driver inicia navegación por primera vez

```javascript
// El sistema automáticamente:
// 1. Crea sesión en BD al hacer clic en "Iniciar Navegación"
// 2. Guarda ubicación inicial
// 3. Comienza auto-guardado cada 5 segundos
```

**SQL que se ejecuta:**
```sql
CALL StartNavigation(1, 'DRV001', -34.6037, -58.3816, '{"device":"Web"}');
```

### Caso 2: Driver recarga la página durante navegación

```javascript
// Al recargar:
// 1. Se llama a sessionManager.initialize()
// 2. Se recupera estado desde BD
// 3. Se restaura UI automáticamente
// 4. Continúa navegación sin interrupciones
```

**SQL que se ejecuta:**
```sql
CALL GetNavigationState(1, 'DRV001');
-- Retorna: session_status = 'navigating'
```

### Caso 3: Driver pausa navegación

```javascript
// Al pausar:
// 1. Cambia estado a 'paused'
// 2. Detiene auto-guardado
// 3. Guarda timestamp de pausa
// 4. Muestra botón "Reanudar"
```

**SQL que se ejecuta:**
```sql
CALL PauseNavigation(1, 'DRV001');
```

### Caso 4: Driver cierra la app y vuelve después

```javascript
// Al volver:
// 1. Detecta sesión pausada
// 2. Muestra estado actual
// 3. Permite reanudar desde donde quedó
```

---

## 🔍 Consultas de Diagnóstico

### Ver estado actual de un delivery

```sql
SET @delivery_id = 1;

SELECT 
    dns.session_status as 'Estado',
    CASE dns.session_status
        WHEN 'idle' THEN '⏸️ En Espera'
        WHEN 'navigating' THEN '🚗 Navegando'
        WHEN 'paused' THEN '⏸️ Pausado'
        WHEN 'completed' THEN '✅ Completado'
    END as 'Visual',
    TIME_FORMAT(SEC_TO_TIME(TIMESTAMPDIFF(SECOND, dns.navigation_started_at, NOW())), '%H:%i:%s') as 'Tiempo_Transcurrido',
    ROUND(dns.remaining_distance_km, 2) as 'KM_Restantes',
    ROUND(dns.current_speed_kmh, 1) as 'Velocidad_Actual',
    dns.battery_level as 'Batería_%',
    TIMESTAMPDIFF(SECOND, dns.last_update_at, NOW()) as 'Segundos_Sin_Actualizar'
FROM delivery_navigation_sessions dns
WHERE dns.delivery_id = @delivery_id;
```

### Ver sesiones con problemas (sin actualizar)

```sql
SELECT 
    o.order_number as 'Orden',
    u.name as 'Driver',
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
ORDER BY dns.last_update_at ASC;
```

### Estadísticas de hoy

```sql
SELECT 
    COUNT(*) as 'Sesiones_Hoy',
    SUM(CASE WHEN session_status = 'navigating' THEN 1 ELSE 0 END) as 'Navegando_Ahora',
    SUM(CASE WHEN session_status = 'completed' THEN 1 ELSE 0 END) as 'Completadas',
    ROUND(AVG(total_distance_km), 2) as 'Distancia_Promedio_KM',
    ROUND(AVG(average_speed_kmh), 1) as 'Velocidad_Promedio'
FROM delivery_navigation_sessions
WHERE DATE(created_at) = CURDATE();
```

---

## 🔧 Comandos PowerShell Útiles

### Ejecutar migración

```powershell
# Navegar a carpeta del proyecto
cd C:\laragon\www\angelow

# Ejecutar verificación
Get-Content "database\migrations\009_navigation_session\002_verify_migration.sql" | mysql -u root -p angelow

# Ejecutar migración
Get-Content "database\migrations\009_navigation_session\001_create_navigation_session.sql" | mysql -u root -p angelow

# Ejecutar tests
php tests\delivery\test_navigation_session.php
```

### Consultar estado desde PowerShell

```powershell
# Ver sesiones activas
mysql -u root -p angelow -e "SELECT * FROM v_active_navigation_sessions;"

# Backup antes de migración
mysqldump -u root -p angelow > "backup_antes_navegacion_$(Get-Date -Format 'yyyyMMdd_HHmmss').sql"
```

---

## ⚠️ Troubleshooting Rápido

### Problema: "Table doesn't exist"

```sql
-- Verificar que se crearon las tablas
SHOW TABLES LIKE 'delivery_navigation%';

-- Si no existen, ejecutar migración
source database/migrations/009_navigation_session/001_create_navigation_session.sql
```

### Problema: "Procedure doesn't exist"

```sql
-- Ver procedimientos
SHOW PROCEDURE STATUS WHERE Db = 'angelow';

-- Recrear
source database/migrations/009_navigation_session/001_create_navigation_session.sql
```

### Problema: Sesión no se restaura al recargar

```javascript
// En la consola del navegador:
console.log('Checking session...');
const result = await sessionManager.initialize();
console.log('Session result:', result);

// Debe mostrar: { success: true, hasActiveSession: true, state: {...} }
```

### Problema: No se guardan actualizaciones

```sql
-- Ver última actualización
SELECT 
    delivery_id,
    last_update_at,
    TIMESTAMPDIFF(SECOND, last_update_at, NOW()) as segundos_atras
FROM delivery_navigation_sessions
WHERE session_status = 'navigating';

-- Si es > 30 segundos, revisar:
-- 1. JavaScript está ejecutándose
-- 2. API responde correctamente
-- 3. No hay errores en PHP
```

---

## 📱 Flujo Completo de Usuario

```
1. Driver acepta orden
   └─> Se crea sesión automáticamente (estado: idle)

2. Driver abre navigation.php
   └─> Se carga estado desde BD
   └─> Se muestra botón "Iniciar Navegación"

3. Driver hace clic en "Iniciar Navegación"
   └─> sessionManager.startNavigation()
   └─> Estado cambia a 'navigating'
   └─> Comienza auto-guardado cada 5 seg

4. Durante navegación
   └─> Cada 5 seg: sessionManager.updateLocation()
   └─> Guarda: lat, lng, speed, distance, ETA, battery

5. Driver recarga página
   └─> sessionManager.initialize()
   └─> Detecta estado 'navigating'
   └─> Continúa automáticamente

6. Driver pausa navegación
   └─> sessionManager.pauseNavigation()
   └─> Estado cambia a 'paused'
   └─> Detiene auto-guardado

7. Driver reanuda
   └─> sessionManager.resumeNavigation()
   └─> Estado vuelve a 'navigating'
   └─> Continúa auto-guardado

8. Driver llega al destino
   └─> sessionManager.completeNavigation()
   └─> Estado cambia a 'completed'
   └─> Se guardan estadísticas finales
```

---

## 📊 Monitoreo en Tiempo Real

### Dashboard SQL (ejecutar cada 10 segundos)

```sql
SELECT 
    '🚗 NAVEGANDO AHORA' as Categoría,
    COUNT(*) as Cantidad
FROM delivery_navigation_sessions 
WHERE session_status = 'navigating'

UNION ALL

SELECT 
    '⏸️ PAUSADAS',
    COUNT(*)
FROM delivery_navigation_sessions 
WHERE session_status = 'paused'

UNION ALL

SELECT 
    '✅ COMPLETADAS HOY',
    COUNT(*)
FROM delivery_navigation_sessions 
WHERE session_status = 'completed' 
AND DATE(navigation_completed_at) = CURDATE();
```

### Ver última actividad

```sql
SELECT 
    o.order_number,
    u.name as driver,
    dns.session_status,
    dns.last_update_at,
    CONCAT(TIMESTAMPDIFF(SECOND, dns.last_update_at, NOW()), 's') as hace
FROM delivery_navigation_sessions dns
INNER JOIN order_deliveries od ON dns.delivery_id = od.id
INNER JOIN orders o ON od.order_id = o.id
INNER JOIN users u ON dns.driver_id = u.id
ORDER BY dns.last_update_at DESC
LIMIT 5;
```

---

## 🎯 Checklist de Implementación

- [ ] Ejecutar verificación pre-migración
- [ ] Hacer backup de base de datos
- [ ] Aplicar migración SQL
- [ ] Verificar tablas creadas
- [ ] Ejecutar tests
- [ ] Todos los tests pasan
- [ ] Integrar JavaScript en navigation.php
- [ ] Probar flujo completo
- [ ] Verificar persistencia al recargar
- [ ] Monitorear logs por 24 horas

---

## 📞 Soporte

Si algo no funciona:

1. Revisar logs: `error_log()` en PHP
2. Revisar consola del navegador
3. Ejecutar tests: `php tests/delivery/test_navigation_session.php`
4. Consultar documentación completa: `docs/delivery/NAVEGACION_SESSION_PERSISTENCIA.md`
