# Migración 013: Agregar shipping_method_id a orders

Esta migración añade la columna `shipping_method_id` a la tabla `orders` para enlazar cada orden con su método de envío (opcional).

Archivos:
- `013_add_shipping_method_to_orders.sql` - SQL para ejecutar la migración (ALTER TABLE + índice + FK).

Instrucciones:
1. Hacer backup de la base de datos.
2. Ejecutar el SQL desde tu cliente de base de datos: phpMyAdmin, HeidiSQL, o línea de comandos.

Ejemplo en PowerShell (Windows):
```powershell
mysql -u root -p angelow < database/migrations/013_add_shipping_method_to_orders.sql
```

O usar el script PHP si prefieres la interfaz web:
```powershell
# Inicialmente guardar el archivo y ejecutar desde el navegador:
# http://localhost/angelow/database/migrations/013_add_shipping_method_to_orders/run_migration.php
```

Verificación:
```sql
DESCRIBE orders;
SHOW INDEX FROM orders WHERE Key_name = 'idx_shipping_method_id';
SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_orders_shipping_method';
```
