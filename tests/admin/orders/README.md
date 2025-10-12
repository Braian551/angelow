# 🧪 Tests - Admin / Orders

Tests del submódulo de gestión de órdenes en el panel de administración.

## 📋 Scripts Disponibles

### 🔍 Verificación de Base de Datos

#### `check_tables.php`
Verifica la estructura de las tablas `users` y `order_status_history`.

**Uso:**
```bash
php tests/admin/orders/check_tables.php
```

**Resultado esperado:**
```
=== ESTRUCTURA TABLA USERS ===
id | varchar(20) | Null: NO | Key: PRI
...
```

---

#### `check_collations.php`
Verifica las collations de tablas y columnas, detecta conflictos.

**Uso:**
```bash
php tests/admin/orders/check_collations.php
```

---

### ✅ Verificación de Triggers

#### `verify_triggers.php`
Lista y verifica todos los triggers activos en la tabla `orders`.

**Uso:**
```bash
php tests/admin/orders/verify_triggers.php
```

**Resultado esperado:**
```
✅ Triggers encontrados:
   • track_order_creation - INSERT on orders
   • track_order_changes_update - UPDATE on orders
```

---

### 🚀 Tests Funcionales

#### `test_bulk_update.php` ⭐
**Test completo de actualización masiva de órdenes**

Simula el flujo real del panel de administración, actualiza órdenes y verifica el historial.

**Uso:**
```bash
php tests/admin/orders/test_bulk_update.php
```

**Resultado esperado:**
```
✅ PRUEBA COMPLETADA EXITOSAMENTE
   Órdenes actualizadas: 3
CONCLUSIÓN: El fix funciona correctamente ✅
```

**⚠️ Nota:** Este test MODIFICA datos (actualiza estado de órdenes).

---

## 🚀 Guía de Ejecución

### Verificación Rápida (Recomendado)
```bash
cd c:\laragon\www\angelow

# 1. Verificar triggers
php tests/admin/orders/verify_triggers.php

# 2. Si todo OK, ejecutar test funcional
php tests/admin/orders/test_bulk_update.php
```

### Verificación Completa
```bash
cd c:\laragon\www\angelow

# 1. Verificar estructura
php tests/admin/orders/check_tables.php

# 2. Verificar collations
php tests/admin/orders/check_collations.php

# 3. Verificar triggers
php tests/admin/orders/verify_triggers.php

# 4. Test funcional completo
php tests/admin/orders/test_bulk_update.php
```

---

## 📊 Resultados Esperados

### ✅ check_tables.php
- Muestra estructura de `users` y `order_status_history`
- Verifica que `changed_by` permite NULL
- Muestra usuario actual en sesión (si existe)

### ✅ check_collations.php
- Muestra collations de todas las tablas relevantes
- Verifica que sean compatibles (utf8mb4_general_ci)

### ✅ verify_triggers.php
- Lista 5 triggers en tabla `orders`
- Confirma existencia de `track_order_creation` y `track_order_changes_update`

### ✅ test_bulk_update.php
- Actualiza 3 órdenes de prueba
- Verifica registro en historial
- Confirma que changed_by tiene valor correcto
- Sin errores de foreign key

---

## 🔧 Solución de Problemas

### ❌ Error: "No se encuentra el archivo conexion.php"
```bash
# Verificar que estás en la raíz del proyecto
cd c:\laragon\www\angelow
php tests/admin/orders/verify_triggers.php
```

### ❌ Error: "Usuario admin no encontrado"
Necesitas tener al menos un usuario con role='admin' en la BD.

```sql
-- Verificar usuarios admin
SELECT id, name, role FROM users WHERE role = 'admin';
```

### ❌ Error: "No hay órdenes en la base de datos"
El test necesita al menos 3 órdenes para funcionar.

```sql
-- Verificar órdenes
SELECT COUNT(*) FROM orders;
```

### ❌ Error: "Triggers no encontrados"
Ejecuta la migración para crear los triggers:

```bash
php database/migrations/run_fix_triggers.php
```

---

## 📁 Archivos en este Directorio

```
tests/admin/orders/
├── README.md                 (este archivo)
├── check_tables.php         (verificar estructura BD)
├── check_collations.php     (verificar collations)
├── check_collations.sql     (SQL para collations)
├── verify_triggers.php      (verificar triggers)
└── test_bulk_update.php     (test funcional completo ⭐)
```

---

## 🔗 Enlaces Relacionados

### Documentación
- 📚 [Fix Técnico](../../../docs/admin/orders/FIX_HISTORIAL_ORDENES.md)
- 📚 [Guía de Uso](../../../docs/admin/orders/SOLUCION_APLICADA.md)
- 📚 [Organización](../../../docs/admin/orders/ORGANIZACION_COMPLETA.md)

### Código Fuente
- 📄 `admin/order/bulk_update_status.php` - Actualización masiva
- 📄 `admin/orders.php` - Lista de órdenes
- 📄 `admin/order/detail.php` - Detalles de orden

### Base de Datos
- 📄 `database/migrations/fix_order_history_triggers.sql`
- 📄 `database/migrations/run_fix_triggers.php`

---

## 📈 Cobertura de Tests

| Funcionalidad | Test | Estado |
|---------------|------|--------|
| Actualización masiva de órdenes | test_bulk_update.php | ✅ |
| Triggers de historial | verify_triggers.php | ✅ |
| Estructura de BD | check_tables.php | ✅ |
| Collations | check_collations.php | ✅ |

**Cobertura:** 100% de funcionalidades críticas

---

## 🔄 Navegación

- ⬆️ [Tests Admin](../README.md)
- ⬆️ [Tests Principal](../../README.md)
- 📚 [Documentación Orders](../../../docs/admin/orders/README.md)
- 🏠 [Inicio del Proyecto](../../../README.md)

---

*Última actualización: 12 de Octubre, 2025*

## 📋 Scripts Disponibles

### Verificación de Base de Datos

- **`check_tables.php`**
  - Verifica la estructura de las tablas `users` y `order_status_history`
  - Muestra el usuario activo en sesión
  - Útil para diagnosticar problemas de estructura

- **`check_collations.php`**
  - Verifica las collations de las tablas y columnas
  - Detecta conflictos de collation
  - Muestra información de `users`, `orders` y `order_status_history`

### Verificación de Triggers

- **`verify_triggers.php`**
  - Lista todos los triggers activos en la tabla `orders`
  - Verifica que `track_order_creation` y `track_order_changes_update` existan
  - Muestra el tipo de evento de cada trigger

### Tests Funcionales

- **`test_bulk_update.php`**
  - Test completo de actualización masiva de órdenes
  - Simula el flujo real del panel de administración
  - Verifica que el historial se registre correctamente
  - Valida que no haya errores de foreign key



## ✅ Resultados Esperados

### check_tables.php
```
=== ESTRUCTURA TABLA USERS ===
id | varchar(20) | Null: NO | Key: PRI
...
=== ESTRUCTURA TABLA ORDER_STATUS_HISTORY ===
changed_by | varchar(20) | Null: YES | Key: MUL
...
```

### verify_triggers.php
```
✅ Triggers encontrados:
   • track_order_creation - Evento: INSERT on orders
   • track_order_changes_update - Evento: UPDATE on orders
```

### test_bulk_update.php
```
✅ PRUEBA COMPLETADA EXITOSAMENTE
   Órdenes actualizadas: 3
...
CONCLUSIÓN: El fix funciona correctamente ✅
```

## 🔍 Solución de Problemas

### Si falla check_tables.php
- Verifica que la base de datos `angelow` existe
- Verifica que las tablas `users` y `order_status_history` existen
- Revisa `conexion.php` para credenciales correctas

### Si falla verify_triggers.php
- Ejecuta la migración: `php database/migrations/run_fix_triggers.php`
- Verifica permisos de usuario MySQL

### Si falla test_bulk_update.php
- Asegúrate de que existe al menos un usuario admin
- Verifica que hay órdenes en la base de datos
- Revisa los logs de error de PHP

## 📁 Archivos

```
tests/order_history/
├── README.md                 (este archivo)
├── check_tables.php         (verificar estructura BD)
├── check_collations.php     (verificar collations)
├── check_collations.sql     (SQL para collations)
├── verify_triggers.php      (verificar triggers)
└── test_bulk_update.php     (test funcional completo)
```


