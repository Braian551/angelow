# Fix: Error de Foreign Key en Historial de Órdenes

## Problema Original

Al realizar una actualización masiva de estado de órdenes, se presentaba el siguiente error:

```
Error al actualizar estado de las órdenes: SQLSTATE[23000]: Integrity constraint violation: 1452 
Cannot add or update a child row: a foreign key constraint fails 
(`angelow`.`order_status_history`, CONSTRAINT `fk_order_history_user` 
FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE)
```

## Causa Raíz

El problema ocurría en los **triggers** de la tabla `orders`. Cuando se actualizaba una orden, los triggers `track_order_creation` y `track_order_changes_update` intentaban insertar registros en `order_status_history` usando la variable `@current_user_id`.

Sin embargo, estos triggers tenían dos problemas:

1. **Variables MySQL no establecidas**: El código PHP en `bulk_update_status.php` no estaba estableciendo las variables `@current_user_id`, `@current_user_name` y `@current_user_ip` que los triggers esperaban.

2. **Manejo incorrecto de usuarios inexistentes**: Los triggers originales usaban:
   ```sql
   SET changed_by_id = COALESCE(@current_user_id, 'system');
   ```
   Esto causaba que si `@current_user_id` era NULL, se intentara insertar el valor `'system'` que no existe como usuario en la tabla `users`, violando la foreign key constraint.

3. **Problema de collation**: Había un conflicto de collations entre `utf8mb4_general_ci` y `utf8mb4_0900_ai_ci` al comparar IDs de usuario.

## Solución Implementada

### 1. Actualización de `bulk_update_status.php`

Se agregó el código para establecer las variables MySQL antes de hacer las actualizaciones:

```php
// Establecer variables de sesión MySQL para los triggers
if ($userIdForHistory !== null) {
    $conn->exec("SET @current_user_id = " . $conn->quote($userIdForHistory));
    $conn->exec("SET @current_user_name = " . $conn->quote($userNameForHistory));
} else {
    $conn->exec("SET @current_user_id = NULL");
    $conn->exec("SET @current_user_name = " . $conn->quote($userNameForHistory));
}
$conn->exec("SET @current_user_ip = " . $conn->quote($userIp));
```

### 2. Corrección de Triggers

Se crearon nuevos triggers que:

- **Verifican si el usuario existe** antes de insertarlo en el historial
- **Usan NULL correctamente** cuando el usuario no existe
- **Manejan correctamente el collation** usando `COLLATE utf8mb4_general_ci`
- **No usan valores hardcoded** como `'system'` que no existen en la base de datos

#### Archivo de migración creado:
- `database/migrations/fix_order_history_triggers.sql`
- `database/migrations/run_fix_triggers.php`

### 3. Verificación

Se creó un script de prueba (`test_bulk_update.php`) que confirma que:
- ✅ Las órdenes se actualizan correctamente
- ✅ El historial se registra sin errores
- ✅ Los triggers funcionan correctamente
- ✅ No hay violaciones de foreign key constraint

## Archivos Modificados

1. **admin/order/bulk_update_status.php**
   - Se agregó el establecimiento de variables MySQL para los triggers
   - Se mejoró el logging para debugging
   - Se agregó validación robusta del usuario

2. **database/migrations/fix_order_history_triggers.sql** (NUEVO)
   - Contiene los triggers corregidos

3. **database/migrations/run_fix_triggers.php** (NUEVO)
   - Script PHP para ejecutar la migración de forma segura

## Cómo Aplicar el Fix

### Paso 1: Ejecutar la migración de triggers

```bash
cd c:\laragon\www\angelow\database\migrations
php run_fix_triggers.php
```

### Paso 2: Verificar que los triggers se crearon correctamente

```bash
cd c:\laragon\www\angelow
php verify_triggers.php
```

Deberías ver:
```
✅ Triggers encontrados:
   • track_order_creation - Evento: INSERT on orders
   • track_order_changes_update - Evento: UPDATE on orders
```

### Paso 3: (Opcional) Probar la funcionalidad

```bash
php test_bulk_update.php
```

## Resultado

Ahora puedes realizar actualizaciones masivas de estado de órdenes sin errores:
- ✅ Los triggers manejan correctamente usuarios existentes
- ✅ Los triggers manejan correctamente cuando el usuario no existe (usando NULL)
- ✅ No hay conflictos de collation
- ✅ El historial se registra correctamente con toda la información

## Archivos de Prueba Creados (pueden eliminarse después)

- `check_tables.php`
- `verify_triggers.php`
- `test_bulk_update.php`
- `check_collations.php`
- `check_collations.sql`

## Notas Importantes

- La columna `changed_by` en `order_status_history` permite valores NULL (gracias a `ON DELETE SET NULL`)
- Los triggers ahora verifican que el usuario existe antes de insertarlo
- Si el usuario no existe, se usa NULL en `changed_by` pero se mantiene el nombre del usuario en `changed_by_name` para referencia
- Las variables MySQL (`@current_user_id`, `@current_user_name`, `@current_user_ip`) deben ser establecidas ANTES de hacer UPDATE en la tabla `orders`
