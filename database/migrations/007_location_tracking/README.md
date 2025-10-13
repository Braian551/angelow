# 🗺️ Migración 007 - Location Tracking

Sistema completo de seguimiento de ubicación para entregas en tiempo real.

## 📋 Descripción

Esta migración implementa el sistema de rastreo GPS para conductores y entregas, incluyendo:
- Tabla de eventos de navegación
- Tracking en tiempo real
- Vistas para monitoreo
- Procedimientos almacenados
- Eventos automatizados

## 📁 Archivos

### Principal
- **`007_add_location_tracking.sql`** - Migración principal completa

### Versiones Corregidas
- **`007_FINAL_CORRECTED.sql`** - Versión final con todas las correcciones
- **`007_FIXED.sql`** - Versión con fixes aplicados
- **`007_EJECUTAR_DIRECTAMENTE.sql`** - Script optimizado para ejecución directa

### Instalación por Partes
- **`007_STEP1.sql`** - Paso 1: Tablas base
- **`007_PART2_VIEWS.sql`** - Paso 2: Vistas del sistema
- **`007_PART3_PROCEDURES.sql`** - Paso 3: Procedimientos almacenados
- **`007_PART4_EVENTS.sql`** - Paso 4: Eventos programados

## 🚀 Instalación

### Opción 1: Instalación Completa (Recomendada)
```bash
cd c:\laragon\www\angelow\database\scripts
php run_migration_007.php
```

### Opción 2: Instalación Directa
```bash
mysql -u root -p angelow_db < database/migrations/007_location_tracking/007_EJECUTAR_DIRECTAMENTE.sql
```

### Opción 3: Instalación por Pasos
```bash
# Paso 1: Tablas
mysql -u root -p angelow_db < database/migrations/007_location_tracking/007_STEP1.sql

# Paso 2: Vistas
mysql -u root -p angelow_db < database/migrations/007_location_tracking/007_PART2_VIEWS.sql

# Paso 3: Procedimientos
mysql -u root -p angelow_db < database/migrations/007_location_tracking/007_PART3_PROCEDURES.sql

# Paso 4: Eventos
mysql -u root -p angelow_db < database/migrations/007_location_tracking/007_PART4_EVENTS.sql
```

## 📊 Cambios en la Base de Datos

### Nuevas Tablas
- `navigation_events` - Eventos de navegación GPS
- `delivery_tracking` - Tracking de entregas

### Nuevas Vistas
- `v_active_deliveries` - Entregas activas
- `v_delivery_locations` - Ubicaciones de entregas

### Nuevos Procedimientos
- `sp_start_tracking()` - Iniciar tracking
- `sp_update_location()` - Actualizar ubicación
- `sp_stop_tracking()` - Detener tracking

### Nuevos Eventos
- `evt_cleanup_old_locations` - Limpieza automática de ubicaciones antiguas

## ✅ Verificación

Después de ejecutar la migración, verificar:

```sql
-- Verificar tablas
SHOW TABLES LIKE '%navigation%';
SHOW TABLES LIKE '%tracking%';

-- Verificar vistas
SHOW FULL TABLES WHERE Table_type = 'VIEW';

-- Verificar procedimientos
SHOW PROCEDURE STATUS WHERE Db = 'angelow_db';

-- Verificar eventos
SHOW EVENTS;
```

## 🧪 Tests

Ejecutar tests de verificación:
```bash
php tests/database/check_navigation_events.php
php tests/database/verify_stored_procedure.php
```

## 📖 Documentación Relacionada

- **Guía Completa**: `/docs/guias/GUIA_COMPLETA_DELIVERY.md`
- **Documentación Técnica**: `/docs/delivery/DOCUMENTACION_TECNICA.md`
- **Migración Completada**: `/docs/migraciones/MIGRACION_007_COMPLETADA.md`

## ⚠️ Notas Importantes

- Esta migración requiere permisos de SUPER en MySQL para crear eventos
- El servidor MySQL debe tener el event scheduler habilitado
- Hacer backup antes de ejecutar

## 🔄 Rollback

Si necesitas revertir:
```sql
DROP TABLE IF EXISTS navigation_events;
DROP TABLE IF EXISTS delivery_tracking;
DROP VIEW IF EXISTS v_active_deliveries;
DROP VIEW IF EXISTS v_delivery_locations;
DROP PROCEDURE IF EXISTS sp_start_tracking;
DROP PROCEDURE IF EXISTS sp_update_location;
DROP PROCEDURE IF EXISTS sp_stop_tracking;
DROP EVENT IF EXISTS evt_cleanup_old_locations;
```

---

*Versión: 007 | Fecha: 2025*
