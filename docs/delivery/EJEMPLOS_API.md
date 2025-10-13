# 🔌 EJEMPLOS DE USO - API DE ENTREGAS

## 📋 Información General

**Endpoint Base:** `http://localhost/angelow/delivery/delivery_actions.php`  
**Método:** `POST`  
**Content-Type:** `application/json`  
**Autenticación:** Sesión PHP (debe estar logueado)

---

## 1️⃣ Aceptar Orden

### Request
```javascript
fetch('http://localhost/angelow/delivery/delivery_actions.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        action: 'accept_order',
        delivery_id: 123
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

### Response (Success)
```json
{
    "success": true,
    "message": "Orden #ORD-001 aceptada correctamente",
    "delivery_status": "driver_accepted"
}
```

### Response (Error)
```json
{
    "success": false,
    "message": "Esta orden no está asignada a ti"
}
```

---

## 2️⃣ Rechazar Orden

### Request
```javascript
fetch('http://localhost/angelow/delivery/delivery_actions.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        action: 'reject_order',
        delivery_id: 123,
        reason: 'Demasiado lejos de mi ubicación actual'
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

### Response (Success)
```json
{
    "success": true,
    "message": "Orden #ORD-001 rechazada",
    "delivery_status": "rejected"
}
```

---

## 3️⃣ Iniciar Recorrido

### Request (con Geolocalización)
```javascript
// Obtener ubicación del navegador
navigator.geolocation.getCurrentPosition(function(position) {
    fetch('http://localhost/angelow/delivery/delivery_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'start_trip',
            delivery_id: 123,
            latitude: position.coords.latitude,
            longitude: position.coords.longitude
        })
    })
    .then(response => response.json())
    .then(data => console.log(data));
});
```

### Request (sin Geolocalización)
```javascript
fetch('http://localhost/angelow/delivery/delivery_actions.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        action: 'start_trip',
        delivery_id: 123
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

### Response
```json
{
    "success": true,
    "message": "Recorrido iniciado para orden #ORD-001",
    "delivery_status": "in_transit"
}
```

---

## 4️⃣ Marcar Llegada

### Request
```javascript
navigator.geolocation.getCurrentPosition(function(position) {
    fetch('http://localhost/angelow/delivery/delivery_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'mark_arrived',
            delivery_id: 123,
            latitude: position.coords.latitude,
            longitude: position.coords.longitude
        })
    })
    .then(response => response.json())
    .then(data => console.log(data));
});
```

### Response
```json
{
    "success": true,
    "message": "Has llegado al destino de la orden #ORD-001",
    "delivery_status": "arrived"
}
```

---

## 5️⃣ Completar Entrega

### Request
```javascript
fetch('http://localhost/angelow/delivery/delivery_actions.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        action: 'complete_delivery',
        delivery_id: 123,
        recipient_name: 'María García',
        notes: 'Cliente satisfecho, entrega sin problemas',
        photo: 'uploads/delivery_proof_123.jpg' // Opcional
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

### Response
```json
{
    "success": true,
    "message": "Entrega completada exitosamente para orden #ORD-001",
    "delivery_status": "delivered"
}
```

---

## 6️⃣ Actualizar Ubicación (Durante Tránsito)

### Request
```javascript
// Actualizar ubicación cada 30 segundos
setInterval(() => {
    navigator.geolocation.getCurrentPosition(function(position) {
        fetch('http://localhost/angelow/delivery/delivery_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update_location',
                delivery_id: 123,
                latitude: position.coords.latitude,
                longitude: position.coords.longitude
            })
        })
        .then(response => response.json())
        .then(data => console.log(data));
    });
}, 30000);
```

### Response
```json
{
    "success": true,
    "message": "Ubicación actualizada"
}
```

---

## 7️⃣ Obtener Mis Entregas Activas

### Request
```javascript
fetch('http://localhost/angelow/delivery/delivery_actions.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        action: 'get_my_deliveries'
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

### Response
```json
{
    "success": true,
    "deliveries": [
        {
            "delivery_id": 123,
            "delivery_status": "driver_accepted",
            "assigned_at": "2025-10-12 10:30:00",
            "accepted_at": "2025-10-12 10:35:00",
            "started_at": null,
            "estimated_arrival": null,
            "location_lat": null,
            "location_lng": null,
            "order_id": 456,
            "order_number": "ORD-001",
            "total": "150.00",
            "shipping_address": "Av. Los Olivos 123",
            "shipping_city": "Lima",
            "shipping_state": "Lima",
            "delivery_notes": "Casa de dos pisos, tocar timbre",
            "customer_info": "María García - 999888777"
        },
        {
            "delivery_id": 124,
            "delivery_status": "in_transit",
            "assigned_at": "2025-10-12 09:00:00",
            "accepted_at": "2025-10-12 09:05:00",
            "started_at": "2025-10-12 09:10:00",
            "estimated_arrival": "2025-10-12 10:00:00",
            "location_lat": "-12.0464",
            "location_lng": "-77.0428",
            "order_id": 457,
            "order_number": "ORD-002",
            "total": "85.50",
            "shipping_address": "Jr. Las Flores 456",
            "shipping_city": "Lima",
            "shipping_state": "Lima",
            "delivery_notes": null,
            "customer_info": "Juan Pérez - 988777666"
        }
    ]
}
```

---

## 8️⃣ Obtener Estadísticas Personales

### Request
```javascript
fetch('http://localhost/angelow/delivery/delivery_actions.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        action: 'get_statistics'
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

### Response
```json
{
    "success": true,
    "statistics": {
        "id": 1,
        "driver_id": "123",
        "total_deliveries": 127,
        "deliveries_today": 5,
        "deliveries_week": 23,
        "deliveries_month": 87,
        "total_rejected": 3,
        "total_cancelled": 1,
        "average_rating": "4.80",
        "total_ratings": 115,
        "acceptance_rate": "95.45",
        "completion_rate": "98.50",
        "average_delivery_time": 35,
        "last_delivery_date": "2025-10-12 09:45:00",
        "is_available": 1,
        "updated_at": "2025-10-12 10:30:00"
    }
}
```

---

## 9️⃣ Obtener Órdenes Disponibles (Sin Asignar)

### Request
```javascript
fetch('http://localhost/angelow/delivery/delivery_actions.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        action: 'get_available_orders'
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

### Response
```json
{
    "success": true,
    "orders": [
        {
            "order_id": 789,
            "order_number": "ORD-005",
            "total": "200.00",
            "shipping_address": "Av. Principal 999",
            "shipping_city": "Lima",
            "shipping_state": "Lima",
            "delivery_notes": "Departamento 301",
            "created_at": "2025-10-12 11:00:00",
            "customer_info": "Carlos Rodríguez - 987654321",
            "delivery_id": null,
            "delivery_status": "awaiting_driver"
        }
    ]
}
```

---

## 🛠️ Ejemplos de Integración Completa

### Ejemplo 1: Flujo Completo en JavaScript

```javascript
class DeliveryManager {
    constructor(baseUrl) {
        this.baseUrl = baseUrl || 'http://localhost/angelow/delivery/delivery_actions.php';
    }

    async performAction(action, data = {}) {
        try {
            const response = await fetch(this.baseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: action,
                    ...data
                })
            });
            return await response.json();
        } catch (error) {
            console.error('Error:', error);
            return { success: false, message: 'Error de red' };
        }
    }

    async acceptOrder(deliveryId) {
        return await this.performAction('accept_order', { delivery_id: deliveryId });
    }

    async rejectOrder(deliveryId, reason) {
        return await this.performAction('reject_order', { 
            delivery_id: deliveryId,
            reason: reason 
        });
    }

    async startTrip(deliveryId) {
        return new Promise((resolve) => {
            navigator.geolocation.getCurrentPosition(async (position) => {
                const result = await this.performAction('start_trip', {
                    delivery_id: deliveryId,
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                });
                resolve(result);
            }, async () => {
                // Sin ubicación
                const result = await this.performAction('start_trip', {
                    delivery_id: deliveryId
                });
                resolve(result);
            });
        });
    }

    async markArrived(deliveryId) {
        return new Promise((resolve) => {
            navigator.geolocation.getCurrentPosition(async (position) => {
                const result = await this.performAction('mark_arrived', {
                    delivery_id: deliveryId,
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                });
                resolve(result);
            });
        });
    }

    async completeDelivery(deliveryId, recipientName, notes) {
        return await this.performAction('complete_delivery', {
            delivery_id: deliveryId,
            recipient_name: recipientName,
            notes: notes
        });
    }

    async getMyDeliveries() {
        return await this.performAction('get_my_deliveries');
    }

    async getStatistics() {
        return await this.performAction('get_statistics');
    }
}

// Uso
const deliveryManager = new DeliveryManager();

// Aceptar orden
deliveryManager.acceptOrder(123).then(result => {
    if (result.success) {
        console.log('✅ Orden aceptada');
    }
});

// Flujo completo
async function procesarEntrega(deliveryId) {
    // 1. Aceptar
    let result = await deliveryManager.acceptOrder(deliveryId);
    console.log(result);
    
    // 2. Iniciar recorrido
    result = await deliveryManager.startTrip(deliveryId);
    console.log(result);
    
    // 3. Marcar llegada
    result = await deliveryManager.markArrived(deliveryId);
    console.log(result);
    
    // 4. Completar entrega
    result = await deliveryManager.completeDelivery(
        deliveryId, 
        'María García', 
        'Entrega exitosa'
    );
    console.log(result);
}
```

### Ejemplo 2: Actualización de Ubicación en Tiempo Real

```javascript
class LocationTracker {
    constructor(deliveryId, updateInterval = 30000) {
        this.deliveryId = deliveryId;
        this.updateInterval = updateInterval;
        this.intervalId = null;
    }

    start() {
        console.log('🚗 Iniciando seguimiento de ubicación...');
        
        this.intervalId = setInterval(() => {
            this.updateLocation();
        }, this.updateInterval);

        // Primera actualización inmediata
        this.updateLocation();
    }

    stop() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            console.log('🛑 Seguimiento detenido');
        }
    }

    updateLocation() {
        if ('geolocation' in navigator) {
            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    const response = await fetch('http://localhost/angelow/delivery/delivery_actions.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'update_location',
                            delivery_id: this.deliveryId,
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude
                        })
                    });
                    
                    const data = await response.json();
                    console.log('📍 Ubicación actualizada:', data);
                },
                (error) => {
                    console.error('❌ Error al obtener ubicación:', error);
                }
            );
        }
    }
}

// Uso
const tracker = new LocationTracker(123, 30000); // Actualizar cada 30 segundos
tracker.start();

// Detener cuando se complete la entrega
// tracker.stop();
```

### Ejemplo 3: Sistema de Notificaciones

```javascript
class DeliveryNotifications {
    constructor() {
        this.requestPermission();
    }

    async requestPermission() {
        if ('Notification' in window) {
            const permission = await Notification.requestPermission();
            console.log('Permisos de notificación:', permission);
        }
    }

    show(title, options = {}) {
        if (Notification.permission === 'granted') {
            new Notification(title, {
                icon: '/images/logo.png',
                badge: '/images/badge.png',
                ...options
            });
        }
    }

    newOrderAssigned(orderNumber, total) {
        this.show('🚚 Nueva Orden Asignada', {
            body: `Orden ${orderNumber} - $${total}\n¡Acepta la orden para comenzar!`,
            tag: 'new-order'
        });
    }

    orderAccepted(orderNumber) {
        this.show('✅ Orden Aceptada', {
            body: `Orden ${orderNumber} aceptada correctamente`,
            tag: 'order-accepted'
        });
    }

    tripStarted(orderNumber) {
        this.show('🚗 Recorrido Iniciado', {
            body: `En camino a entregar orden ${orderNumber}`,
            tag: 'trip-started'
        });
    }

    deliveryCompleted(orderNumber) {
        this.show('🎉 Entrega Completada', {
            body: `Orden ${orderNumber} entregada exitosamente`,
            tag: 'delivery-completed'
        });
    }
}

// Uso
const notifications = new DeliveryNotifications();

// Cuando se acepta una orden
deliveryManager.acceptOrder(123).then(result => {
    if (result.success) {
        notifications.orderAccepted('ORD-001');
    }
});
```

---

## 🧪 Pruebas con cURL

### Aceptar Orden
```bash
curl -X POST http://localhost/angelow/delivery/delivery_actions.php \
  -H "Content-Type: application/json" \
  -d '{
    "action": "accept_order",
    "delivery_id": 123
  }'
```

### Rechazar Orden
```bash
curl -X POST http://localhost/angelow/delivery/delivery_actions.php \
  -H "Content-Type: application/json" \
  -d '{
    "action": "reject_order",
    "delivery_id": 123,
    "reason": "Muy lejos"
  }'
```

### Obtener Entregas
```bash
curl -X POST http://localhost/angelow/delivery/delivery_actions.php \
  -H "Content-Type: application/json" \
  -d '{
    "action": "get_my_deliveries"
  }'
```

---

## 📝 Notas Importantes

1. **Autenticación:** Todas las peticiones requieren sesión activa
2. **Permisos:** Solo usuarios con rol `delivery` pueden usar estas acciones
3. **Validaciones:** El sistema valida que el transportista tenga permisos sobre la entrega
4. **Geolocalización:** Opcional pero recomendada para seguimiento
5. **Manejo de errores:** Siempre verificar `success` en la respuesta

---

**🎯 Con estos ejemplos puedes integrar completamente el sistema de entregas en tu aplicación**
