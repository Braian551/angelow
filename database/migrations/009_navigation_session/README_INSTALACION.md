# 🚀 Instalación del Sistema de Persistencia de Navegación

## ⚠️ IMPORTANTE: Leer antes de ejecutar

Este sistema permite que el estado de navegación se persista en la base de datos, permitiendo que los drivers puedan recargar la página sin perder su progreso.

---

## 📋 Pre-requisitos

- ✅ Laragon o servidor MySQL corriendo
- ✅ Base de datos `angelow` existente
- ✅ PHP 7.4 o superior
- ✅ Acceso a consola (PowerShell o CMD)

---

## 🔧 PASO 1: Verificar Estado Actual (OBLIGATORIO)

Antes de aplicar cualquier cambio, **SIEMPRE** verifica el estado de tu base de datos:

### Opción A: Desde MySQL Workbench / phpMyAdmin

```sql
-- Conectar a la base de datos 'angelow'
USE angelow;

-- Ejecutar el script de verificación completo
source C:/laragon/www/angelow/database/migrations/009_navigation_session/002_verify_migration.sql
```

### Opción B: Desde consola PowerShell

```powershell
# Navegar a la carpeta del proyecto
cd C:\laragon\www\angelow

# Ejecutar verificación
Get-Content "database\migrations\009_navigation_session\002_verify_migration.sql" | mysql -u root -p angelow
```

### Opción C: Desde CMD

```cmd
cd C:\laragon\www\angelow
type database\migrations\009_navigation_session\002_verify_migration.sql | mysql -u root -p angelow
```

### ✅ Qué debes revisar en los resultados:

1. **Tablas existentes**: Deben aparecer `order_deliveries`, `users`, `orders`
2. **Nuevas tablas**: NO deben existir `delivery_navigation_sessions` ni `delivery_navigation_events`
3. **Entregas activas**: Verás cuántas entregas hay en progreso
4. **Foreign Keys**: Deben estar correctamente configuradas

Si todo está OK, continúa con el paso 2.

---

## 💾 PASO 2: Backup de la Base de Datos (RECOMENDADO)

**Antes de aplicar cualquier migración, haz un backup:**

### PowerShell:

```powershell
# Crear carpeta de backups si no existe
New-Item -ItemType Directory -Force -Path "C:\laragon\www\angelow\database\backups"

# Hacer backup con fecha
$fecha = Get-Date -Format "yyyyMMdd_HHmmss"
mysqldump -u root -p angelow > "C:\laragon\www\angelow\database\backups\backup_antes_navegacion_$fecha.sql"
```

### CMD:

```cmd
mkdir C:\laragon\www\angelow\database\backups
mysqldump -u root -p angelow > C:\laragon\www\angelow\database\backups\backup_antes_navegacion_%date:~-4,4%%date:~-7,2%%date:~-10,2%.sql
```

### Desde MySQL directamente:

```bash
mysqldump -u root -p angelow > backup_antes_navegacion.sql
```

---

## 🎯 PASO 3: Aplicar la Migración

Una vez verificado el estado y hecho el backup, aplica la migración:

### Opción A: Desde MySQL Workbench / phpMyAdmin

```sql
USE angelow;
source C:/laragon/www/angelow/database/migrations/009_navigation_session/001_create_navigation_session.sql
```

### Opción B: Desde PowerShell

```powershell
cd C:\laragon\www\angelow

# Aplicar migración
Get-Content "database\migrations\009_navigation_session\001_create_navigation_session.sql" | mysql -u root -p angelow

# Verificar que se aplicó correctamente
mysql -u root -p angelow -e "SHOW TABLES LIKE 'delivery_navigation%';"
```

### Opción C: Desde CMD

```cmd
cd C:\laragon\www\angelow
type database\migrations\009_navigation_session\001_create_navigation_session.sql | mysql -u root -p angelow

mysql -u root -p angelow -e "SHOW TABLES LIKE 'delivery_navigation%';"
```

### ✅ Resultado esperado:

```
+----------------------------------------+
| Tables_in_angelow (delivery_navigation%)   |
+----------------------------------------+
| delivery_navigation_events              |
| delivery_navigation_sessions            |
+----------------------------------------+
2 rows in set (0.00 sec)
```

---

## ✔️ PASO 4: Ejecutar Tests

Verifica que todo funciona correctamente:

### PowerShell / CMD:

```powershell
cd C:\laragon\www\angelow
php tests\delivery\test_navigation_session.php
```

### ✅ Resultado esperado:

```
╔═══════════════════════════════════════════════════╗
║   TESTS: Sistema de Sesiones de Navegación      ║
╚═══════════════════════════════════════════════════╝

📋 Preparando datos de prueba...
✅ Datos de prueba creados correctamente

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

🧹 Limpiando datos de prueba...
✅ Datos de prueba eliminados

╔═══════════════════════════════════════════════════╗
║                    RESUMEN                       ║
╚═══════════════════════════════════════════════════╝

Total de tests ejecutados: 11
Tests exitosos: 11
Tests fallidos: 0

🎉 ¡Todos los tests pasaron correctamente!
```

Si ves este mensaje, **¡felicidades!** El sistema está instalado correctamente.

---

## 🔍 PASO 5: Verificar Instalación

### Consulta rápida desde consola:

```powershell
# Ver estructura de la tabla principal
mysql -u root -p angelow -e "DESCRIBE delivery_navigation_sessions;"

# Ver procedimientos almacenados
mysql -u root -p angelow -e "SHOW PROCEDURE STATUS WHERE Db = 'angelow' AND Name LIKE '%Navigation%';"

# Ver triggers
mysql -u root -p angelow -e "SHOW TRIGGERS WHERE \`Table\` = 'delivery_navigation_sessions';"
```

---

## 📊 PASO 6: Consultas de Monitoreo

### Ver sesiones activas:

```sql
USE angelow;
SELECT * FROM v_active_navigation_sessions;
```

### Ver estadísticas:

```sql
SELECT 
    COUNT(*) as total_sesiones,
    SUM(CASE WHEN session_status = 'navigating' THEN 1 ELSE 0 END) as navegando_ahora,
    SUM(CASE WHEN session_status = 'completed' THEN 1 ELSE 0 END) as completadas
FROM delivery_navigation_sessions;
```

### Consultar estado de un delivery específico:

```sql
-- Cambiar el número 1 por el delivery_id que quieras consultar
CALL GetNavigationState(1, 'DRV001');
```

---

## 🎨 PASO 7: Integración con el Frontend (Ya está hecho)

El archivo `navigation.php` ya incluye el script necesario:

```html
<!-- Navigation Session Manager - Sistema de persistencia -->
<script src="<?= BASE_URL ?>/js/delivery/navigation-session.js"></script>
```

El sistema se inicializa automáticamente al cargar la página.

---

## 🐛 Troubleshooting

### Problema: "ERROR 1050: Table already exists"

**Solución:**
Las tablas ya existen. Verifica si fueron creadas correctamente:

```sql
SHOW TABLES LIKE 'delivery_navigation%';
SELECT COUNT(*) FROM delivery_navigation_sessions;
```

Si las tablas existen y funcionan, no necesitas ejecutar la migración nuevamente.

### Problema: "ERROR 1064: Syntax error"

**Solución:**
Verifica que estás usando MySQL 5.7 o superior:

```sql
SELECT VERSION();
```

Si la versión es antigua, actualiza MySQL.

### Problema: Tests fallan

**Solución:**

1. Verifica que las tablas existan:
```sql
SHOW TABLES LIKE 'delivery_navigation%';
```

2. Verifica que los procedimientos existan:
```sql
SHOW PROCEDURE STATUS WHERE Db = 'angelow';
```

3. Re-ejecuta la migración si es necesario.

### Problema: "Access denied"

**Solución:**
Verifica tus credenciales de MySQL:

```powershell
# Probar conexión
mysql -u root -p -e "SELECT 1;"
```

Si no puedes conectar, verifica el usuario y contraseña en Laragon.

---

## 🔄 Rollback (Deshacer cambios)

Si necesitas deshacer la instalación:

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

-- Eliminar tablas (¡CUIDADO! Esto borra los datos)
DROP TABLE IF EXISTS delivery_navigation_events;
DROP TABLE IF EXISTS delivery_navigation_sessions;
```

Luego restaura el backup:

```powershell
mysql -u root -p angelow < "C:\laragon\www\angelow\database\backups\backup_antes_navegacion_YYYYMMDD_HHMMSS.sql"
```

---

## 📞 Soporte

Si encuentras problemas:

1. **Revisa los logs de error**: `C:\laragon\www\angelow\storage\logs\`
2. **Consulta la documentación completa**: `docs/delivery/NAVEGACION_SESSION_PERSISTENCIA.md`
3. **Ejecuta las consultas de diagnóstico**: `database/scripts/check_navigation_status.sql`

---

## ✅ Checklist Final

- [ ] ✅ Verificación pre-migración ejecutada
- [ ] 💾 Backup de base de datos realizado
- [ ] 🎯 Migración aplicada correctamente
- [ ] ✔️ Tests ejecutados y pasados
- [ ] 🔍 Tablas verificadas
- [ ] 📊 Procedimientos verificados
- [ ] 🎨 Frontend integrado (ya hecho)
- [ ] 🚀 Sistema listo para usar

---

## 🎉 ¡Listo!

El sistema de persistencia de navegación está instalado y funcionando.

Ahora los drivers pueden:
- ✅ Recargar la página sin perder el estado
- ✅ Pausar y reanudar navegación
- ✅ Ver su historial de navegación
- ✅ Continuar desde donde quedaron

**Próximos pasos:**
1. Prueba el sistema con un usuario delivery real
2. Monitorea las consultas en `v_active_navigation_sessions`
3. Revisa los eventos en `delivery_navigation_events`

---

**Fecha de instalación:** ___________  
**Instalado por:** ___________  
**Versión:** 1.0.0
