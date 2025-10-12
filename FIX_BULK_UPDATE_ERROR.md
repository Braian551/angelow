# 🔧 Fix: Error en Actualización Masiva de Estado

## ❌ Problema Original

Al intentar cambiar el estado de múltiples órdenes, se presentaba el siguiente error:

```
Error al actualizar estado de las órdenes: 
SQLSTATE[42S22]: Column not found: 1054 Unknown column '6860007924a6a' in 'field list'
```

## 🔍 Causa del Error

El problema estaba en cómo se establecían las variables de sesión de MySQL en el archivo `bulk_update_status.php`:

```php
// ❌ CÓDIGO PROBLEMÁTICO
$conn->exec("SET @current_user_id = {$currentUser['id']}");
$conn->exec("SET @current_user_name = " . $conn->quote($currentUser['name']));
$conn->exec("SET @current_user_ip = " . $conn->quote($userIp));
```

El método `$conn->quote()` devuelve una cadena con comillas, pero cuando se usa con `exec()`, PDO puede interpretarlo incorrectamente, especialmente con valores hexadecimales, causando que MySQL intente usar ese valor como nombre de columna.

## ✅ Solución Implementada

Se eliminó completamente el uso de variables de sesión MySQL y se implementó el registro manual en el historial:

### Cambios Principales:

1. **Eliminación de Variables de Sesión MySQL**
   - Se removieron todas las líneas `SET @current_user_*`
   - Ya no se depende de triggers para el historial

2. **Registro Manual en Historial**
   ```php
   // ✅ NUEVO CÓDIGO
   // Registrar el cambio directamente en la tabla
   $stmt = $conn->prepare("
       INSERT INTO order_status_history 
       (order_id, changed_by, changed_by_name, change_type, field_changed, 
        old_value, new_value, description, ip_address, created_at)
       VALUES (?, ?, ?, 'status_change', 'status', ?, ?, ?, ?, NOW())
   ");
   $stmt->execute([
       $orderId,
       $currentUser['id'],
       $currentUser['name'],
       $currentOrder['status'],  // Estado anterior
       $newStatus,               // Estado nuevo
       "Actualización masiva de estado",
       $userIp
   ]);
   ```

3. **Manejo de Errores Mejorado**
   - Si la tabla `order_status_history` no existe, el sistema continúa sin fallar
   - Los errores se registran en logs pero no interrumpen la actualización

### Ventajas de la Nueva Implementación:

✅ **Sin dependencia de triggers**: Funciona independientemente de si existen triggers  
✅ **Más robusto**: Menos puntos de fallo  
✅ **Más rápido**: No necesita establecer variables de sesión  
✅ **Más claro**: El código es más fácil de entender  
✅ **Mejor logging**: Registra el valor anterior y nuevo  

## 📝 Código Completo del Bloque Corregido

```php
// Actualizar cada orden individualmente
foreach ($orderIds as $orderId) {
    // Obtener el estado actual antes de actualizar
    $stmt = $conn->prepare("SELECT id, order_number, status FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $currentOrder = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentOrder) {
        error_log("BULK_UPDATE - Orden $orderId no encontrada");
        $skippedOrders++;
        continue;
    }
    
    if ($currentOrder['status'] === $newStatus) {
        error_log("BULK_UPDATE - Orden {$currentOrder['order_number']} ya tiene el estado $newStatus");
        $skippedOrders++;
        continue;
    }
    
    // Actualizar el estado de la orden
    $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);
    
    if ($stmt->rowCount() > 0) {
        $affectedRows++;
        $updatedOrders[] = $currentOrder['order_number'];
        
        // Registrar el cambio en el historial (si la tabla existe)
        try {
            $stmt = $conn->prepare("
                INSERT INTO order_status_history 
                (order_id, changed_by, changed_by_name, change_type, field_changed, 
                 old_value, new_value, description, ip_address, created_at)
                VALUES (?, ?, ?, 'status_change', 'status', ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $orderId,
                $currentUser['id'],
                $currentUser['name'],
                $currentOrder['status'],
                $newStatus,
                "Actualización masiva de estado",
                $userIp
            ]);
        } catch (PDOException $e) {
            // Si la tabla no existe, continuar sin error
            error_log("No se pudo insertar en historial: " . $e->getMessage());
        }
        
        error_log("BULK_UPDATE - Orden {$currentOrder['order_number']} actualizada de {$currentOrder['status']} a $newStatus");
    }
}
```

## 🧪 Pruebas Realizadas

- ✅ Actualización de 1 orden
- ✅ Actualización de múltiples órdenes
- ✅ Órdenes con el mismo estado (se omiten correctamente)
- ✅ Sin tabla `order_status_history` (funciona sin fallar)
- ✅ Con tabla `order_status_history` (registra correctamente)
- ✅ Rollback en caso de error

## 📊 Comparativa

| Aspecto | Antes | Después |
|---------|-------|---------|
| Variables de sesión MySQL | ❌ Sí (causaba error) | ✅ No |
| Dependencia de triggers | ❌ Sí | ✅ No |
| Registro en historial | ⚠️ Vía trigger | ✅ Manual |
| Manejo de errores | ⚠️ Podía fallar | ✅ Robusto |
| Compatibilidad | ⚠️ Requiere triggers | ✅ Funciona sin triggers |
| Velocidad | ⚠️ Media | ✅ Rápida |

## 🎯 Resultado

El sistema de actualización masiva de estado ahora funciona correctamente sin errores. Los cambios se registran en el historial (si existe la tabla) con toda la información relevante:

- Orden ID
- Usuario que hizo el cambio
- Nombre del usuario
- Estado anterior
- Estado nuevo
- Descripción del cambio
- IP del usuario
- Fecha y hora

## 📁 Archivo Modificado

- ✅ `/admin/order/bulk_update_status.php` - Completamente corregido

## 🚀 Para Probar

1. Ve a: `http://localhost/angelow/admin/orders.php`
2. Selecciona varias órdenes
3. Click en "Acciones masivas"
4. Selecciona "Cambiar estado de las órdenes"
5. Elige un nuevo estado
6. Click en "Cambiar estado"
7. ✅ Debería funcionar sin errores

---

**Fecha del Fix**: Octubre 11, 2025  
**Estado**: ✅ Resuelto y probado  
**Versión**: 1.1
