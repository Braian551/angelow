# 💾 Database - Gestión de Base de Datos

Esta carpeta contiene toda la gestión de la base de datos: migraciones, fixes, y scripts de mantenimiento.

## 📁 Estructura

```
database/
├── README.md                    (este archivo)
├── migrations/                  (migraciones organizadas por versión)
│   ├── 007_location_tracking/
│   ├── 008_delivery_workflow/
│   ├── 009_orders_addresses/
│   ├── order_history/
│   ├── orders_badge/
│   └── roles_system/
├── fixes/                       (correcciones y fixes de BD)
│   ├── procedures/
│   ├── navigation/
│   ├── delivery/
│   └── [archivos de fix generales]
└── scripts/                     (scripts de ejecución)
    ├── ejecutar_migracion.bat
    ├── ejecutar_migracion.ps1
    └── [otros scripts PHP]
```

## 🔄 Migraciones (`migrations/`)

Las migraciones están organizadas por funcionalidad y versión:

### 007 - Location Tracking
Sistema de seguimiento de ubicación para entregas.
- `007_add_location_tracking.sql` - Migración principal
- `007_EJECUTAR_DIRECTAMENTE.sql` - Script de ejecución directa
- `007_FINAL_CORRECTED.sql` - Versión final corregida
- `007_FIXED.sql` - Versión con correcciones
- `007_PART2_VIEWS.sql` - Vistas del sistema
- `007_PART3_PROCEDURES.sql` - Procedimientos almacenados
- `007_PART4_EVENTS.sql` - Eventos programados
- `007_STEP1.sql` - Paso 1 de instalación

### 008 - Delivery Workflow
Sistema de flujo de trabajo para entregas.
- `008_fix_delivery_workflow.sql` - Fix del workflow
- `008_fix_delivery_workflow_final.sql` - Versión final
- `008_fix_delivery_workflow_simple.sql` - Versión simplificada
- `add_delivery_system.sql` - Agregar sistema de entregas
- `create_delivery_views.sql` - Crear vistas de delivery

### 009 - Orders & Addresses
Gestión de órdenes y direcciones GPS.
- `migration_gps_addresses.sql` - Migración de direcciones GPS
- `query_examples_after_migration.sql` - Ejemplos de consultas

### Order History
Sistema de historial de órdenes.
- `add_order_history.sql` - Agregar historial completo
- `add_order_history_simple.sql` - Versión simplificada

### Orders Badge
Sistema de badges para órdenes.
- Ver carpeta `orders_badge/` para detalles

### Roles System
Sistema de roles y permisos.
- `setup_roles_system.sql` - Configuración del sistema de roles

## 🔧 Fixes (`fixes/`)

Correcciones y fixes organizados por módulo:

### Procedures (`fixes/procedures/`)
Correcciones de procedimientos almacenados:
- `fix_collation_procedures.sql` - Corrección de collations
- `fix_delivery_procedures.sql` - Corrección de procedimientos de delivery
- `fix_procedures_parameters.sql` - Corrección de parámetros
- `fix_search_procedure.sql` - Corrección de búsqueda
- `fix_order_history_triggers.sql` - Corrección de triggers
- `fix_procedures.php` - Script PHP para fixes

### Navigation (`fixes/navigation/`)
Correcciones del sistema de navegación:
- `fix_start_navigation_procedure.sql` - Fix de inicio de navegación
- `fix_start_navigation_v2.sql` - Versión 2 del fix

### Delivery (`fixes/delivery/`)
Correcciones del sistema de entregas:
- `fix_update_delivery_location.sql` - Fix de actualización de ubicación
- `fix_delivery_coordinates.php` - Fix de coordenadas

### Fixes Generales
- `fix_carts_table.php` - Fix de tabla de carritos
- `fix_cart_session.php` - Fix de sesión del carrito
- `quick_fix_cart.php` - Fix rápido del carrito

## 🚀 Scripts (`scripts/`)

Scripts de ejecución y automatización:

### Scripts de Shell
- `ejecutar_migracion.bat` - Ejecutar migración (Windows)
- `ejecutar_migracion.ps1` - Ejecutar migración (PowerShell)
- `ejecutar_migracion_008.bat` - Ejecutar migración 008 (Windows)
- `ejecutar_migracion_008.ps1` - Ejecutar migración 008 (PowerShell)
- `fix_utf8.ps1` - Fix de codificación UTF-8

### Scripts PHP
- `run_migration.php` - Ejecutar migraciones generales
- `run_migration_007.php` - Ejecutar migración 007
- `run_migration_gps.php` - Ejecutar migración GPS
- `migration_009_orders_addresses.php` - Ejecutar migración 009
- `run_fix_procedures.php` - Ejecutar fixes de procedimientos
- `run_fix_search.php` - Ejecutar fix de búsqueda
- `migrate_cart_items.php` - Migrar items del carrito
- `add_gps_used_field.php` - Agregar campo GPS usado

## 📖 Guías de Uso

### Ejecutar una Migración

#### Desde PowerShell:
```powershell
cd database/scripts
.\ejecutar_migracion.ps1
```

#### Desde CMD:
```cmd
cd database\scripts
ejecutar_migracion.bat
```

#### Desde PHP:
```bash
php database/scripts/run_migration.php
```

### Ejecutar un Fix

```bash
# Fix de procedimientos
php database/fixes/procedures/fix_procedures.php

# Fix de coordenadas de delivery
php database/fixes/delivery/fix_delivery_coordinates.php

# Fix rápido del carrito
php database/fixes/quick_fix_cart.php
```

### Aplicar SQL Directo

```bash
# Usando mysql client
mysql -u usuario -p nombre_bd < database/migrations/007_location_tracking/007_EJECUTAR_DIRECTAMENTE.sql

# Usando PHP
php database/scripts/run_migration_007.php
```

## ⚠️ Importantes

### Antes de Ejecutar Migraciones:
1. ✅ **Hacer backup de la base de datos**
2. ✅ Revisar el script SQL antes de ejecutar
3. ✅ Verificar que no haya migraciones pendientes
4. ✅ Ejecutar en ambiente de desarrollo primero

### Orden de Ejecución Recomendado:
1. Migración 007 - Location Tracking
2. Migración 008 - Delivery Workflow
3. Migración 009 - Orders & Addresses
4. Order History (si es necesario)
5. Roles System (si es necesario)

### Después de Ejecutar:
- Verificar que las tablas se crearon correctamente
- Revisar logs de errores
- Ejecutar tests de verificación (ver `/tests/database/`)

## 🔗 Enlaces Relacionados

- **Documentación**: `/docs/migraciones/` - Guías detalladas de migraciones
- **Tests**: `/tests/database/` - Scripts de verificación
- **Fixes documentados**: `/docs/correcciones/` - Documentación de correcciones

## 📝 Convenciones

- Archivos de migración con prefijo del número de versión (ej: `007_`, `008_`)
- Archivos de fix con prefijo `fix_`
- Scripts de ejecución con prefijo `run_` o `ejecutar_`
- Scripts SQL en carpetas por versión
- Scripts PHP de soporte en `/scripts/`

## 🛠️ Mantenimiento

### Agregar Nueva Migración:
1. Crear carpeta en `migrations/` con nombre descriptivo
2. Agregar archivos SQL necesarios
3. Crear script de ejecución en `scripts/` si es necesario
4. Documentar en `/docs/migraciones/`
5. Crear tests en `/tests/database/`

### Agregar Nuevo Fix:
1. Colocar en la carpeta apropiada de `fixes/`
2. Documentar en `/docs/correcciones/` o `/docs/soluciones/`
3. Agregar script de ejecución si es complejo

---

*Última actualización: 13 de Octubre, 2025*
