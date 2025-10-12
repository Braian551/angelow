# Sistema de Entregas Tipo Didi - Documentación

## 📋 Descripción General

Este sistema implementa un flujo de entregas similar a Didi/Uber donde los transportistas deben **aceptar** las órdenes antes de entregarlas, con seguimiento completo del proceso.

## 🔄 Flujo de Estados

### Estados de Entrega (`delivery_status`)

1. **awaiting_driver** - Esperando asignación de transportista
2. **driver_assigned** - Asignada a un transportista (esperando aceptación)
3. **driver_accepted** - Transportista aceptó la orden
4. **in_transit** - En camino al destino
5. **arrived** - Llegó al destino
6. **delivered** - Entregado exitosamente
7. **rejected** - Rechazado por el transportista
8. **cancelled** - Cancelado

### Flujo Completo

```
Orden Creada (pending)
    ↓
Admin: Procesa orden → (processing)
    ↓
Admin: Asigna a transportista → (shipped) + order_deliveries (driver_assigned)
    ↓
Transportista: ACEPTA o RECHAZA
    ↓ (acepta)
driver_accepted
    ↓
Transportista: Inicia Recorrido
    ↓
in_transit
    ↓
Transportista: Llega al destino
    ↓
arrived
    ↓
Transportista: Confirma entrega
    ↓
delivered (orden → status: delivered)
```

## 📦 Instalación

### Paso 1: Ejecutar Migración SQL

```sql
-- Ejecutar en phpMyAdmin o cliente MySQL
SOURCE database/migrations/add_delivery_system.sql
```

O importar el archivo directamente:

1. Abrir phpMyAdmin
2. Seleccionar base de datos `angelow`
3. Ir a "Importar"
4. Seleccionar archivo `add_delivery_system.sql`
5. Ejecutar

### Paso 2: Verificar Tablas Creadas

```sql
-- Verificar que las tablas existen
SHOW TABLES LIKE 'order_deliveries';
SHOW TABLES LIKE 'delivery_status_history';
SHOW TABLES LIKE 'driver_statistics';

-- Ver estructura
DESCRIBE order_deliveries;
```

### Paso 3: Verificar Archivos

Archivos creados/modificados:
- ✅ `database/migrations/add_delivery_system.sql` (nueva migración)
- ✅ `delivery/delivery_actions.php` (nuevo endpoint API)
- ✅ `delivery/dashboarddeli.php` (actualizado)

## 🎯 Funcionalidades Implementadas

### Para Transportistas

1. **Ver órdenes asignadas** - Dashboard muestra solo sus órdenes
2. **Aceptar orden** - Botón verde "Aceptar"
3. **Rechazar orden** - Botón rojo "Rechazar" (con razón)
4. **Iniciar recorrido** - Registra ubicación GPS
5. **Marcar llegada** - Notifica que llegó al destino
6. **Completar entrega** - Solicita nombre de quien recibe
7. **Ver estadísticas** - Entregas, calificación, tasa de aceptación

### Para Administradores

1. **Asignar órdenes** - Cuando orden pasa a "shipped"
2. **Ver historial completo** - Tabla `delivery_status_history`
3. **Estadísticas por transportista** - Tabla `driver_statistics`
4. **Reasignar órdenes rechazadas** - Automático a `awaiting_driver`

## 🔌 API Endpoints

### `POST /delivery/delivery_actions.php`

Acciones disponibles:

#### 1. Aceptar Orden
```json
{
  "action": "accept_order",
  "delivery_id": 123
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Orden #ORD-001 aceptada correctamente",
  "delivery_status": "driver_accepted"
}
```

#### 2. Rechazar Orden
```json
{
  "action": "reject_order",
  "delivery_id": 123,
  "reason": "Demasiado lejos de mi ubicación actual"
}
```

#### 3. Iniciar Recorrido
```json
{
  "action": "start_trip",
  "delivery_id": 123,
  "latitude": -12.0464,
  "longitude": -77.0428
}
```

#### 4. Marcar Llegada
```json
{
  "action": "mark_arrived",
  "delivery_id": 123,
  "latitude": -12.0464,
  "longitude": -77.0428
}
```

#### 5. Completar Entrega
```json
{
  "action": "complete_delivery",
  "delivery_id": 123,
  "recipient_name": "María García",
  "notes": "Cliente satisfecho",
  "photo": "uploads/delivery_proof_123.jpg"
}
```

#### 6. Actualizar Ubicación
```json
{
  "action": "update_location",
  "delivery_id": 123,
  "latitude": -12.0464,
  "longitude": -77.0428
}
```

#### 7. Obtener Mis Entregas
```json
{
  "action": "get_my_deliveries"
}
```

#### 8. Obtener Estadísticas
```json
{
  "action": "get_statistics"
}
```

## 📊 Consultas SQL Útiles

### Ver órdenes asignadas a un transportista
```sql
SELECT * FROM v_active_deliveries_by_driver 
WHERE driver_id = 'USER_ID';
```

### Ver órdenes esperando transportista
```sql
SELECT * FROM v_orders_awaiting_driver;
```

### Ver estadísticas de transportista
```sql
SELECT * FROM driver_statistics 
WHERE driver_id = 'USER_ID';
```

### Ver historial de una entrega
```sql
SELECT * FROM delivery_status_history 
WHERE delivery_id = 123 
ORDER BY created_at DESC;
```

### Ranking de transportistas
```sql
SELECT * FROM v_driver_rankings 
ORDER BY total_deliveries DESC;
```

## 🔧 Configuración Adicional

### Asignación Automática de Órdenes

Para asignar automáticamente órdenes a transportistas disponibles:

```sql
-- Crear procedimiento de asignación automática
DELIMITER $$
CREATE PROCEDURE AutoAssignOrders()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_order_id INT;
    DECLARE v_driver_id VARCHAR(20);
    
    DECLARE order_cursor CURSOR FOR 
        SELECT od.order_id 
        FROM order_deliveries od 
        WHERE od.delivery_status = 'awaiting_driver';
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN order_cursor;
    
    read_loop: LOOP
        FETCH order_cursor INTO v_order_id;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Seleccionar transportista con menos entregas activas
        SELECT u.id INTO v_driver_id
        FROM users u
        INNER JOIN driver_statistics ds ON u.id = ds.driver_id
        WHERE u.role = 'delivery' AND ds.is_available = 1
        ORDER BY (
            SELECT COUNT(*) FROM order_deliveries 
            WHERE driver_id = u.id 
            AND delivery_status IN ('driver_assigned', 'driver_accepted', 'in_transit')
        ) ASC
        LIMIT 1;
        
        IF v_driver_id IS NOT NULL THEN
            CALL AssignOrderToDriver(v_order_id, v_driver_id);
        END IF;
    END LOOP;
    
    CLOSE order_cursor;
END$$
DELIMITER ;
```

### Notificaciones Push (Opcional)

Para implementar notificaciones en tiempo real cuando se asigna una orden:

```javascript
// En el frontend del transportista
if ('Notification' in window) {
    Notification.requestPermission().then(permission => {
        if (permission === 'granted') {
            // Polling cada 10 segundos para nuevas órdenes
            setInterval(checkNewOrders, 10000);
        }
    });
}

function checkNewOrders() {
    fetch('/delivery/delivery_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_my_deliveries' })
    })
    .then(r => r.json())
    .then(data => {
        data.deliveries.forEach(delivery => {
            if (delivery.delivery_status === 'driver_assigned') {
                new Notification('Nueva Orden Asignada', {
                    body: `Orden #${delivery.order_number} - $${delivery.total}`,
                    icon: '/images/logo.png'
                });
            }
        });
    });
}
```

## 🐛 Solución de Problemas

### Error: Tabla no existe
```bash
# Verificar que ejecutaste la migración
mysql -u root -p angelow < database/migrations/add_delivery_system.sql
```

### Error: Foreign key constraint
```sql
-- Verificar que la tabla orders existe
SHOW TABLES LIKE 'orders';

-- Verificar columnas necesarias
DESCRIBE orders;
DESCRIBE users;
```

### Órdenes no aparecen en el dashboard
```sql
-- Verificar que el transportista tiene el rol correcto
SELECT id, name, role FROM users WHERE role = 'delivery';

-- Verificar que hay órdenes asignadas
SELECT * FROM order_deliveries WHERE driver_id = 'TU_USER_ID';
```

## 📈 Mejoras Futuras

1. ✅ Sistema básico de aceptación/rechazo
2. ✅ Seguimiento de estados
3. ✅ Estadísticas de transportistas
4. 🔄 Geolocalización en tiempo real
5. 🔄 Notificaciones push
6. 🔄 Chat entre cliente y transportista
7. 🔄 Calificaciones y reseñas
8. 🔄 Historial de rutas con mapa
9. 🔄 Optimización de rutas
10. 🔄 Asignación automática inteligente

## 👨‍💻 Uso del Sistema

### Como Transportista:

1. **Iniciar sesión** con rol `delivery`
2. **Ver dashboard** - Aparecen órdenes asignadas
3. **Aceptar orden** - Click en botón verde "Aceptar"
4. **Iniciar recorrido** - Click en "Iniciar Recorrido"
5. **Marcar llegada** - Click en "He Llegado"
6. **Completar entrega** - Click en "Entrega Completada"
   - Ingresar nombre de quien recibe
   - Agregar notas opcionales

### Como Administrador:

1. **Procesar orden** - Cambiar estado a "processing"
2. **Asignar transportista** - Cambiar a "shipped"
   - Esto crea registro en `order_deliveries`
   - Estado inicial: `driver_assigned`
3. **Ver historial** - Tabla `delivery_status_history`

## 📞 Soporte

Para problemas o preguntas:
- Revisar logs PHP: `error_log`
- Revisar consola del navegador: F12
- Verificar permisos de rol: `users.role = 'delivery'`

---

**Versión:** 1.0  
**Fecha:** 12 de Octubre de 2025  
**Base de datos:** angelow
