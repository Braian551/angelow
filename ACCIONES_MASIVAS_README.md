# Acciones Masivas de Órdenes - Documentación

## 🎯 Funcionalidades Implementadas

Se ha implementado un sistema completo de **acciones masivas** para la gestión de órdenes en el panel de administración.

## ✅ Características

### 1. **Selección de Órdenes**
- ✅ Checkbox individual por cada orden
- ✅ Checkbox "Seleccionar todas" en el encabezado de la tabla
- ✅ Contador visual de órdenes seleccionadas en el botón de acciones masivas
- ✅ Persistencia de selección al navegar por la tabla

### 2. **Cambiar Estado Masivo**
- ✅ Cambiar el estado de múltiples órdenes simultáneamente
- ✅ Estados disponibles:
  - Pendiente
  - En proceso
  - Enviado
  - Entregado
  - Cancelado
  - Reembolsado
- ✅ Validación de permisos (solo admin)
- ✅ Registro de cambios en historial (si existe la tabla)
- ✅ Mensajes informativos sobre órdenes actualizadas/omitidas

### 3. **Eliminar Órdenes Masivamente**
- ✅ Eliminar múltiples órdenes de forma permanente
- ✅ Advertencia clara antes de ejecutar la acción
- ✅ Eliminación en cascada:
  - Items de la orden
  - Transacciones de pago
  - Historial de estados
  - La orden principal
- ✅ Transacciones seguras (rollback en caso de error)

### 4. **Interfaz Visual**
- ✅ Modal intuitivo para acciones masivas
- ✅ Indicadores visuales de selección activa
- ✅ Animaciones suaves
- ✅ Feedback inmediato al usuario
- ✅ Botón de acciones masivas resaltado cuando hay selección

## 📁 Archivos Creados/Modificados

### Nuevos Archivos PHP

1. **`/admin/order/bulk_delete.php`**
   - Endpoint para eliminar múltiples órdenes
   - Validación de permisos
   - Eliminación transaccional segura
   - Logs detallados

2. **`/admin/order/bulk_update_status.php`**
   - Endpoint para actualizar estado de múltiples órdenes
   - Integración con sistema de triggers
   - Registro de IP y usuario que realiza el cambio
   - Manejo de notas adicionales

### Archivos JavaScript Modificados

3. **`/js/modals/bulk-actions.js`**
   - Funciones `updateOrdersStatusBulk()` y `deleteOrdersBulk()`
   - Integración con nuevos endpoints
   - Manejo de errores mejorado
   - Actualización automática de la tabla

4. **`/js/orderadmin.php`**
   - Variable global `window.selectedOrders`
   - Función `updateSelectionCount()` para contador visual
   - Mejora del checkbox "seleccionar todas"
   - Estilos CSS inyectados dinámicamente
   - Funciones globales accesibles

## 🚀 Cómo Usar

### Seleccionar Órdenes

1. Marca los checkboxes de las órdenes que deseas gestionar
2. O usa el checkbox del encabezado para seleccionar todas en la página actual
3. El botón "Acciones masivas" mostrará el número de órdenes seleccionadas

### Cambiar Estado Masivo

1. Selecciona las órdenes deseadas
2. Haz clic en el botón **"Acciones masivas"**
3. En el modal, selecciona **"Cambiar estado de las órdenes"**
4. Elige el nuevo estado del dropdown
5. Haz clic en **"Cambiar estado"**
6. Las órdenes se actualizarán automáticamente

### Eliminar Órdenes Masivamente

1. Selecciona las órdenes que deseas eliminar
2. Haz clic en el botón **"Acciones masivas"**
3. En el modal, selecciona **"Eliminar órdenes permanentemente"**
4. Lee la advertencia (⚠️ **Esta acción NO se puede deshacer**)
5. Haz clic en **"Eliminar"**
6. Las órdenes serán eliminadas permanentemente

## 🔒 Seguridad

- ✅ Validación de sesión y permisos en todos los endpoints
- ✅ Solo usuarios con rol `admin` pueden ejecutar acciones masivas
- ✅ Sanitización de datos de entrada (IDs convertidos a enteros)
- ✅ Uso de prepared statements para prevenir SQL injection
- ✅ Transacciones con rollback automático en caso de error
- ✅ Registro de logs detallados para auditoría

## 📊 Respuestas de la API

### Actualización de Estado Exitosa
```json
{
  "success": true,
  "message": "5 órdenes actualizadas a estado: Enviado",
  "updated": 5,
  "skipped": 2,
  "order_numbers": ["ORD-001", "ORD-002", "ORD-003", "ORD-004", "ORD-005"]
}
```

### Eliminación Exitosa
```json
{
  "success": true,
  "message": "3 órdenes eliminadas correctamente",
  "deleted": {
    "orders": 3,
    "items": 12,
    "payments": 3,
    "history": 8
  }
}
```

### Error de Permisos
```json
{
  "success": false,
  "message": "No tienes permisos para realizar esta acción"
}
```

## 🎨 Estilos Visuales

Se han añadido estilos CSS dinámicos para:

- **Botón con selección activa**: Se resalta con un gradiente azul y animación de pulso
- **Modal responsivo**: Se adapta a diferentes tamaños de pantalla
- **Indicadores visuales**: Colores y iconos según el tipo de acción
- **Animaciones suaves**: Transiciones fluidas en todos los elementos

## ⚡ Características Técnicas

### Optimizaciones
- Consultas SQL optimizadas con placeholders dinámicos
- Uso de transacciones para garantizar integridad de datos
- Carga asíncrona sin bloquear la interfaz
- Actualización automática de la tabla sin recargar página

### Manejo de Errores
- Try-catch en todos los endpoints
- Mensajes de error descriptivos para el usuario
- Logs detallados en servidor para debugging
- Rollback automático de transacciones fallidas

### Compatibilidad
- Compatible con MySQL/MariaDB
- Funciona con y sin la tabla `order_status_history`
- Integración con sistema de triggers existente
- Respeta variables de sesión MySQL personalizadas

## 🧪 Testing Recomendado

1. **Prueba de selección**
   - Verificar que el contador se actualiza correctamente
   - Probar seleccionar/deseleccionar todas

2. **Prueba de actualización de estado**
   - Actualizar una sola orden
   - Actualizar múltiples órdenes
   - Intentar actualizar con el mismo estado (debe omitir)

3. **Prueba de eliminación**
   - Eliminar una orden
   - Eliminar múltiples órdenes
   - Verificar que se eliminan los datos relacionados

4. **Prueba de permisos**
   - Intentar acceder sin sesión
   - Intentar acceder con usuario no-admin

5. **Prueba de errores**
   - Enviar datos inválidos
   - Simular fallo de conexión a BD

## 📝 Notas Importantes

- ⚠️ **La eliminación es permanente**: No hay papelera de reciclaje
- 🔐 **Solo administradores**: Los usuarios normales no verán estos botones
- 📊 **Historial**: Si existe la tabla, se registran todos los cambios
- 🌐 **IP tracking**: Se registra la IP del usuario que realiza cambios
- 🔄 **Auto-refresh**: La tabla se actualiza automáticamente tras cada acción

## 🛠️ Mantenimiento

Para modificar los estados disponibles, edita:
- `admin/orders.php` - Array `$statuses`
- `admin/modals/modal-bulk-actions.php` - Options del select

Para cambiar el comportamiento de eliminación:
- Modificar `admin/order/bulk_delete.php`
- Considerar implementar "soft delete" en lugar de eliminación permanente

## ✨ Mejoras Futuras

- [ ] Implementar "soft delete" (borrado lógico)
- [ ] Añadir opción de restaurar órdenes eliminadas
- [ ] Exportar órdenes seleccionadas a Excel/PDF
- [ ] Enviar notificaciones por email a clientes
- [ ] Añadir más filtros de búsqueda avanzada
- [ ] Historial de acciones masivas realizadas
- [ ] Confirmación doble para eliminación masiva

---

**Desarrollado**: Octubre 2025  
**Estado**: ✅ Funcional y probado  
**Versión**: 1.0
