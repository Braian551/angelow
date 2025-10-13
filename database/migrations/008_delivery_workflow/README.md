# 🚚 Migración 008 - Delivery Workflow

Sistema completo de flujo de trabajo para gestión de entregas.

## 📋 Descripción

Esta migración implementa el workflow completo de entregas, incluyendo:
- Estados de entrega (pendiente, en ruta, entregado, cancelado)
- Sistema de asignación de conductores
- Tracking de estado de entregas
- Vistas de monitoreo
- Integración con navegación GPS

## 📁 Archivos

### Principal
- **`008_fix_delivery_workflow.sql`** - Workflow principal
- **`008_fix_delivery_workflow_final.sql`** - Versión final optimizada (RECOMENDADA)
- **`008_fix_delivery_workflow_simple.sql`** - Versión simplificada

### Adicionales
- **`add_delivery_system.sql`** - Sistema de entregas completo
- **`create_delivery_views.sql`** - Vistas del sistema de delivery

## 🚀 Instalación

### Opción 1: Script Automatizado (Recomendada)
```powershell
cd c:\laragon\www\angelow\database\scripts
.\ejecutar_migracion_008.ps1
```

O desde CMD:
```cmd
cd c:\laragon\www\angelow\database\scripts
ejecutar_migracion_008.bat
```

### Opción 2: Instalación Manual
```bash
# Ejecutar versión final (recomendada)
mysql -u root -p angelow_db < database/migrations/008_delivery_workflow/008_fix_delivery_workflow_final.sql

# O versión simplificada
mysql -u root -p angelow_db < database/migrations/008_delivery_workflow/008_fix_delivery_workflow_simple.sql
```

### Opción 3: Instalación Completa
```bash
# 1. Sistema de entregas
mysql -u root -p angelow_db < database/migrations/008_delivery_workflow/add_delivery_system.sql

# 2. Vistas
mysql -u root -p angelow_db < database/migrations/008_delivery_workflow/create_delivery_views.sql

# 3. Workflow final
mysql -u root -p angelow_db < database/migrations/008_delivery_workflow/008_fix_delivery_workflow_final.sql
```

## 📊 Cambios en la Base de Datos

### Tablas Modificadas
- `deliveries` - Campos de estado y tracking
- `orders` - Relación con entregas
- `drivers` - Estado de disponibilidad

### Nuevas Columnas
```sql
ALTER TABLE deliveries ADD COLUMN status ENUM('pending', 'assigned', 'in_route', 'delivered', 'cancelled');
ALTER TABLE deliveries ADD COLUMN assigned_at DATETIME;
ALTER TABLE deliveries ADD COLUMN started_at DATETIME;
ALTER TABLE deliveries ADD COLUMN completed_at DATETIME;
ALTER TABLE deliveries ADD COLUMN gps_usado BOOLEAN DEFAULT FALSE;
```

### Nuevas Vistas
- `v_pending_deliveries` - Entregas pendientes
- `v_active_deliveries` - Entregas en curso
- `v_completed_deliveries` - Entregas completadas
- `v_delivery_stats` - Estadísticas de entregas

### Procedimientos Actualizados
- `sp_assign_delivery()` - Asignar entrega a conductor
- `sp_start_delivery()` - Iniciar entrega
- `sp_complete_delivery()` - Completar entrega
- `sp_cancel_delivery()` - Cancelar entrega
- `start_navigation()` - Iniciar navegación con validaciones

## ✅ Verificación

Después de ejecutar la migración:

```sql
-- Verificar estructura de deliveries
DESCRIBE deliveries;

-- Verificar vistas
SELECT * FROM v_pending_deliveries LIMIT 5;
SELECT * FROM v_active_deliveries LIMIT 5;

-- Verificar procedimientos
SHOW PROCEDURE STATUS WHERE Db = 'angelow_db' AND Name LIKE '%delivery%';

-- Test básico
SELECT COUNT(*) as total_deliveries FROM deliveries;
```

## 🧪 Tests

Ejecutar tests de verificación:
```bash
# Verificar estado de entregas
php tests/database/check_deliveries.php
php tests/database/check_delivery_state.php
php tests/database/check_delivery_status.php

# Test completo
php tests/delivery/test_complete.php
```

## 📖 Documentación Relacionada

- **Guía Rápida**: `/docs/migraciones/GUIA_RAPIDA_008.md`
- **Soluciones**: `/docs/soluciones/SOLUCION_ENTREGAS_008.md`
- **Correcciones**: `/docs/correcciones/RESUMEN_CORRECCIONES_008.md`
- **Guía Completa**: `/docs/guias/GUIA_COMPLETA_DELIVERY.md`

## ⚠️ Notas Importantes

- **Requiere migración 007**: Debe ejecutarse después de la migración 007
- **Backup obligatorio**: Hacer backup antes de ejecutar
- **Tiempo de ejecución**: Puede tardar varios minutos en bases de datos grandes
- **Permisos**: Requiere permisos ALTER TABLE

## 🔧 Fixes Relacionados

Si encuentras problemas después de la migración:

```bash
# Fix de procedimientos de delivery
php database/fixes/procedures/fix_delivery_procedures.php

# Fix de ubicación de delivery
mysql -u root -p angelow_db < database/fixes/delivery/fix_update_delivery_location.sql
```

## 🔄 Rollback

**⚠️ PRECAUCIÓN**: El rollback eliminará datos

```sql
-- Eliminar columnas añadidas
ALTER TABLE deliveries 
  DROP COLUMN IF EXISTS status,
  DROP COLUMN IF EXISTS assigned_at,
  DROP COLUMN IF EXISTS started_at,
  DROP COLUMN IF EXISTS completed_at,
  DROP COLUMN IF EXISTS gps_usado;

-- Eliminar vistas
DROP VIEW IF EXISTS v_pending_deliveries;
DROP VIEW IF EXISTS v_active_deliveries;
DROP VIEW IF EXISTS v_completed_deliveries;
DROP VIEW IF EXISTS v_delivery_stats;

-- Eliminar procedimientos
DROP PROCEDURE IF EXISTS sp_assign_delivery;
DROP PROCEDURE IF EXISTS sp_start_delivery;
DROP PROCEDURE IF EXISTS sp_complete_delivery;
DROP PROCEDURE IF EXISTS sp_cancel_delivery;
```

## 📈 Mejoras Implementadas

- ✅ Estados de entrega bien definidos
- ✅ Tracking temporal completo
- ✅ Validaciones de flujo de trabajo
- ✅ Integración con GPS
- ✅ Vistas optimizadas para reportes
- ✅ Procedimientos almacenados para operaciones críticas

---

*Versión: 008 | Fecha: 2025*
