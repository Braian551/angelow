# 📦 Migraciones de Base de Datos

Este directorio contiene todas las migraciones de base de datos organizadas por módulos.

## 📁 Estructura

```
migrations/
├── orders_badge/              # Sistema de badge de notificaciones
│   ├── 001_create_order_views_table.sql
│   └── run_migration.php
├── add_order_history.sql      # Historial de órdenes (legacy)
└── README.md                  # Este archivo
```

## 🚀 Cómo Ejecutar Migraciones

### Migraciones por Módulo

Cada módulo tiene su propia carpeta con su script de migración:

```bash
# Desde la terminal
php database/migrations/{nombre_modulo}/run_migration.php

# O desde el navegador
http://localhost/angelow/database/migrations/{nombre_modulo}/run_migration.php
```

### Ejemplo: Badge de Órdenes

```bash
php database/migrations/orders_badge/run_migration.php
```

## 📋 Lista de Migraciones

### ✅ Activas

| Módulo | Archivo | Estado | Descripción |
|--------|---------|--------|-------------|
| `orders_badge` | `001_create_order_views_table.sql` | ✅ Aplicada | Crea tabla para rastrear órdenes vistas |

### 📦 Legacy (Anteriores)

| Archivo | Descripción |
|---------|-------------|
| `add_order_history.sql` | Sistema de historial de órdenes |
| `add_order_history_simple.sql` | Versión simplificada del historial |
| `fix_order_history_triggers.sql` | Correcciones de triggers |

## 🔒 Convenciones

### Nomenclatura de Archivos
```
{numero}_{descripcion}.sql

Ejemplo: 001_create_order_views_table.sql
```

### Estructura de Módulos
```
migrations/
└── {nombre_modulo}/
    ├── {numero}_{descripcion}.sql
    └── run_migration.php
```

## 📝 Crear una Nueva Migración

1. **Crea la carpeta del módulo:**
   ```
   mkdir database/migrations/mi_modulo
   ```

2. **Crea el archivo SQL:**
   ```sql
   -- 001_create_my_table.sql
   CREATE TABLE IF NOT EXISTS `my_table` (
     `id` INT NOT NULL AUTO_INCREMENT,
     ...
   );
   ```

3. **Crea el script PHP:**
   ```php
   <?php
   // run_migration.php
   // (Copia la estructura de orders_badge/run_migration.php)
   ```

4. **Ejecuta la migración:**
   ```bash
   php database/migrations/mi_modulo/run_migration.php
   ```

## ⚠️ Notas Importantes

- ✅ Siempre usa `IF NOT EXISTS` para evitar errores
- ✅ Cada migración debe ser idempotente (se puede ejecutar múltiples veces)
- ✅ Documenta bien cada migración
- ✅ Haz backup antes de ejecutar migraciones en producción
- ✅ Prueba las migraciones en desarrollo primero

## 🔄 Rollback

Si necesitas revertir una migración, crea un archivo de rollback:

```sql
-- 001_rollback_create_order_views_table.sql
DROP TABLE IF EXISTS `order_views`;
```

## 📖 Documentación

Para más información sobre cada módulo, consulta:
- `docs/admin/{nombre_modulo}/README.md`

---

*Última actualización: 12 de Octubre, 2025*
