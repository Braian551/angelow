# Migración: Eliminación de Campos Redundantes en Orders

## 📋 Resumen

Se eliminan los siguientes campos de la tabla `orders` por ser redundantes o innecesarios:

1. **`client_identification`** - Redundante con `users.identification_number`
2. **`client_phone`** - Redundante con `users.phone`
3. **`tax`** - Campo innecesario para el modelo de negocio

## 🚀 Pasos para Migrar

### 1. Hacer Backup (IMPORTANTE)
```bash
# Desde la terminal
cd c:\xampp\mysql\bin
.\mysqldump.exe -u root -p angelow > c:\xampp\htdocs\angelow\database\backups\angelow_backup_before_migration_$(date +%Y%m%d).sql
```

### 2. Ejecutar Script SQL
```bash
# Opción 1: Desde phpMyAdmin
# - Abrir phpMyAdmin
# - Seleccionar base de datos 'angelow'
# - Ir a pestaña SQL
# - Copiar y ejecutar el contenido de: database/migrations/remove_redundant_fields.sql

# Opción 2: Desde línea de comandos
cd c:\xampp\mysql\bin
.\mysql.exe -u root -p angelow < c:\xampp\htdocs\angelow\database\migrations\remove_redundant_fields.sql
```

### 3. Verificar Cambios
```sql
DESCRIBE orders;
-- Verificar que los campos client_identification, client_phone y tax ya no aparezcan
```

## 📝 Archivos que Necesitan Actualización

### ✅ Todos los Archivos Actualizados
- [x] `admin/order/detail.php` - Eliminadas referencias a tax
- [x] `admin/order/edit.php` - Eliminadas referencias a tax
- [x] `users/orders.php` - Eliminadas referencias a tax
- [x] `tienda/api/pay/send_confirmation.php` - Eliminadas referencias a tax
- [x] `pagos/generar_factura.php` - Actualizado SELECT y eliminadas referencias redundantes
- [x] `admin/api/export_orders_pdf.php` - Actualizado SELECT y eliminadas referencias redundantes

### 📋 Cambios Realizados

Todos los archivos PHP han sido actualizados para:
1. **Eliminar referencias a `$order['tax']`** y sus condicionales
2. **Usar `u.phone` en lugar de `client_phone`** en consultas SQL
3. **Usar `u.identification_number` en lugar de `client_identification`** en consultas SQL
4. **Simplificar los totales** (subtotal + envío = total, sin impuestos)

## 🔍 Cómo Buscar Referencias

Para encontrar más referencias en el código:
```bash
# Buscar 'tax' en archivos PHP
grep -r "tax" --include="*.php" c:\xampp\htdocs\angelow\

# Buscar 'client_identification'
grep -r "client_identification" --include="*.php" c:\xampp\htdocs\angelow\

# Buscar 'client_phone'
grep -r "client_phone" --include="*.php" c:\xampp\htdocs\angelow\
```

## ✅ Testing Post-Migración

Después de actualizar todos los archivos, probar:

1. **Visualizar detalles de una orden**
   - Ir a: `/admin/order/detail.php?id=X`
   - Verificar que no aparezca el campo "Impuestos"
   - Verificar que los datos del cliente se muestren correctamente

2. **Editar una orden**
   - Ir a: `/admin/order/edit.php?id=X`
   - Verificar que no haya errores

3. **Ver órdenes como usuario**
   - Ir a: `/users/orders.php`
   - Verificar que las órdenes se muestren correctamente

4. **Generar factura**
   - Probar: `/pagos/generar_factura.php?order_id=X`
   - Verificar datos del cliente y totales

5. **Exportar PDF de órdenes**
   - Probar: `/admin/api/export_orders_pdf.php`
   - Verificar formato y datos

## 🎯 Beneficios de la Migración

- ✅ **Normalización:** Datos del cliente centralizados en tabla `users`
- ✅ **Mantenibilidad:** Un solo lugar para actualizar datos del cliente
- ✅ **Consistencia:** Elimina duplicación de datos
- ✅ **Simplicidad:** Estructura más limpia y fácil de entender
- ✅ **Performance:** Menos campos = queries más eficientes

## 🆘 Rollback (Si hay problemas)

Si necesitas revertir los cambios:

```sql
-- Restaurar campos eliminados
ALTER TABLE `orders` 
ADD COLUMN `client_identification` varchar(20) DEFAULT NULL COMMENT 'Documento del cliente',
ADD COLUMN `client_phone` varchar(20) DEFAULT NULL,
ADD COLUMN `tax` decimal(10,2) DEFAULT 0.00;

-- Restaurar el backup
-- mysql -u root -p angelow < backup_file.sql
```

## 📞 Soporte

Si encuentras algún problema durante la migración, documenta:
1. El error exacto (mensaje y archivo)
2. La consulta SQL o código PHP problemático
3. Los pasos para reproducir el error

---
**Fecha de creación:** 11/10/2025  
**Versión:** 2.0  
**Estado:** ✅ Todos los archivos PHP actualizados - Listo para ejecutar migración SQL
