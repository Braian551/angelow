# 🚚 Sistema de Órdenes para Transportistas

## 📋 Descripción General

Sistema completo de gestión de órdenes para transportistas con funcionalidad tipo **Didi/Uber**, donde los conductores pueden:

1. ✅ Ver órdenes disponibles (sin asignar)
2. ✅ Auto-asignarse órdenes
3. ✅ Aceptar/Rechazar órdenes asignadas
4. ✅ Gestionar el flujo completo de entrega
5. ✅ Ver historial de entregas

---

## 🗂️ Estructura de Archivos

```
delivery/
├── dashboarddeli.php          ← Dashboard principal del transportista
├── orders.php                 ← Vista completa de órdenes (con pestañas)
├── delivery_actions.php       ← API para acciones de entrega
└── api/
    └── get_orders.php         ← API para obtener órdenes según categoría
```

---

## 🎯 Funcionalidades Implementadas

### 1️⃣ Dashboard Principal (`dashboarddeli.php`)

**Características:**
- 📊 Estadísticas del transportista (entregas hoy, totales, calificación, etc.)
- 📦 **Sección de Órdenes Disponibles** (nuevas sin asignar)
- 🚛 Mis órdenes en proceso
- 📜 Historial reciente de entregas

**Órdenes Disponibles:**
- Muestra órdenes con estado `shipped` (enviado) y `paid` (pagado)
- Sin asignar a ningún transportista
- Botón **"Quiero esta orden"** para auto-asignarse
- Se auto-asigna Y acepta en un solo paso

**URL:** `http://localhost/angelow/delivery/dashboarddeli.php`

---

### 2️⃣ Vista Completa de Órdenes (`orders.php`)

**Características:**
- 🔖 **4 Pestañas de categorías:**
  
  1. **Disponibles** - Órdenes sin asignar (shipped + paid)
  2. **Asignadas a mí** - Órdenes que el admin asignó pero no he aceptado
  3. **En proceso** - Órdenes aceptadas (accepted, in_transit, arrived)
  4. **Completadas** - Historial de entregas entregadas

- 🔍 Búsqueda en tiempo real
- 📄 Paginación automática
- 🔄 Botón de actualización
- 🎨 Diseño de tarjetas (cards) responsive

**Estados y Acciones:**

| Estado | Acción Disponible | Descripción |
|--------|------------------|-------------|
| `Disponible` | **Aceptar** | Auto-asignarse la orden |
| `Asignada` | **Aceptar / Rechazar** | Orden que el admin asignó |
| `Aceptada` | **Iniciar Recorrido** | Empezar el viaje |
| `En Tránsito` | **He Llegado** | Marcar llegada |
| `En Destino` | **Completar Entrega** | Finalizar con nombre del receptor |

**URL:** `http://localhost/angelow/delivery/orders.php`

---

### 3️⃣ API de Órdenes (`api/get_orders.php`)

**Endpoint:** `GET /delivery/api/get_orders.php`

**Parámetros:**
```
tab          - Categoría: available, assigned, active, completed
page         - Número de página (default: 1)
per_page     - Items por página (default: 12, max: 50)
search       - Búsqueda por orden, cliente, dirección
driver_id    - ID del transportista (se toma de sesión)
```

**Respuesta:**
```json
{
  "success": true,
  "orders": [
    {
      "id": 1,
      "order_number": "ORD-20251012171934",
      "total": 100.00,
      "shipping_address": "Calle Falsa 123",
      "shipping_city": "Bogotá",
      "customer_name": "Juan Pérez",
      "customer_phone": "3001234567",
      "delivery_status": null,
      "created_at": "2025-10-12 17:19:34"
    }
  ],
  "meta": {
    "total": 15,
    "page": 1,
    "per_page": 12,
    "total_pages": 2
  },
  "counts": {
    "available": 5,
    "assigned": 2,
    "active": 3,
    "completed": 120
  }
}
```

---

### 4️⃣ API de Acciones (`delivery_actions.php`)

**Endpoint:** `POST /delivery/delivery_actions.php`

**Acciones Disponibles:**

#### 1. Auto-asignarse una orden disponible
```json
{
  "action": "self_assign_order",
  "order_id": 1
}
```

**Flujo:**
1. Verifica que la orden esté disponible (shipped + paid + sin asignar)
2. Llama a `AssignOrderToDriver(order_id, driver_id)`
3. Inmediatamente llama a `DriverAcceptOrder(delivery_id, driver_id)`
4. Retorna `delivery_id` y `order_number`

**Respuesta:**
```json
{
  "success": true,
  "message": "Orden aceptada exitosamente",
  "delivery_id": 2,
  "order_number": "ORD-20251012171934"
}
```

---

#### 2. Aceptar orden asignada
```json
{
  "action": "accept_order",
  "delivery_id": 2
}
```

---

#### 3. Rechazar orden
```json
{
  "action": "reject_order",
  "delivery_id": 2,
  "reason": "Muy lejos de mi ubicación"
}
```

---

#### 4. Iniciar recorrido
```json
{
  "action": "start_trip",
  "delivery_id": 2,
  "latitude": 4.6097,
  "longitude": -74.0817
}
```

---

#### 5. Marcar llegada
```json
{
  "action": "mark_arrived",
  "delivery_id": 2,
  "latitude": 4.6097,
  "longitude": -74.0817
}
```

---

#### 6. Completar entrega
```json
{
  "action": "complete_delivery",
  "delivery_id": 2,
  "recipient_name": "María López",
  "notes": "Entregado en portería"
}
```

---

## 🔄 Flujo de Estados

### Flujo Tipo Didi (Auto-asignación)

```
┌─────────────────────┐
│  Orden Disponible   │ ← shipped + paid + sin asignar
│  (en orders.php)    │
└──────────┬──────────┘
           │
           │ [Transportista: "Quiero esta orden"]
           ↓
┌─────────────────────┐
│   driver_assigned   │ ← Se asigna automáticamente
└──────────┬──────────┘
           │
           │ [Se acepta automáticamente]
           ↓
┌─────────────────────┐
│   driver_accepted   │ ← Orden aceptada
└──────────┬──────────┘
           │
           │ [Transportista: "Iniciar Recorrido"]
           ↓
┌─────────────────────┐
│     in_transit      │ ← En camino
└──────────┬──────────┘
           │
           │ [Transportista: "He Llegado"]
           ↓
┌─────────────────────┐
│       arrived       │ ← En destino
└──────────┬──────────┘
           │
           │ [Transportista: "Completar Entrega"]
           ↓
┌─────────────────────┐
│      delivered      │ ← Entregado ✅
└─────────────────────┘
```

### Flujo Tradicional (Admin asigna)

```
┌─────────────────────┐
│  Admin asigna       │
│  orden a conductor  │
└──────────┬──────────┘
           │
           ↓
┌─────────────────────┐
│   driver_assigned   │ ← Esperando aceptación
└──────────┬──────────┘
           │
           ├─→ [Aceptar] ─→ driver_accepted
           │
           └─→ [Rechazar] ─→ rejected
```

---

## 🎨 Interfaz de Usuario

### Dashboard Principal

**Sección de Órdenes Disponibles:**
- Fondo verde claro con borde punteado
- Tarjetas resaltadas con borde verde
- Botón verde "Quiero esta orden"
- Muestra: cliente, teléfono, dirección, monto, tiempo

**Sección Mis Órdenes:**
- Tarjetas con estados coloreados
- Botones según el estado actual
- Información completa de contacto y ubicación

---

### Vista de Órdenes (orders.php)

**Pestañas:**
- Contador de órdenes en cada pestaña
- Diseño responsive con grid de tarjetas
- Búsqueda en tiempo real (500ms delay)
- Paginación automática

**Tarjetas de Orden:**
- Header con degradado púrpura
- Número de orden y estado
- Información del cliente
- Dirección de entrega
- Notas de entrega
- Botones de acción según estado

---

## 🧪 Pruebas

### Crear Orden de Prueba

```sql
INSERT INTO orders 
(user_id, order_number, status, payment_status, subtotal, total, shipping_address, shipping_city, delivery_notes) 
VALUES (
    (SELECT id FROM users WHERE role = 'customer' LIMIT 1),
    CONCAT('ORD-', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')),
    'shipped',
    'paid',
    150.00,
    150.00,
    'Carrera 7 #45-20',
    'Bogotá',
    'Apartamento 301, Portería'
);
```

### Ver Órdenes Disponibles

```sql
SELECT 
    o.order_number, 
    o.status, 
    o.payment_status, 
    o.shipping_address,
    CONCAT(u.name, ' - ', u.phone) as cliente
FROM orders o
INNER JOIN users u ON o.user_id = u.id
WHERE o.status = 'shipped'
AND o.payment_status = 'paid'
AND NOT EXISTS (
    SELECT 1 FROM order_deliveries od 
    WHERE od.order_id = o.id 
    AND od.delivery_status NOT IN ('rejected', 'cancelled')
);
```

---

## 📱 Uso del Sistema

### Para Transportistas

1. **Login:** Ingresa con credenciales de transportista
   ```
   http://localhost/angelow/auth/login.php
   ```

2. **Dashboard:** Ve órdenes disponibles
   ```
   http://localhost/angelow/delivery/dashboarddeli.php
   ```

3. **Aceptar Orden:** Click en "Quiero esta orden"
   - Se asigna automáticamente
   - Se acepta automáticamente
   - Pasa a estado "Aceptada"

4. **Gestionar Entrega:**
   - Iniciar Recorrido → in_transit
   - He Llegado → arrived
   - Completar Entrega → delivered

5. **Ver Historial:**
   - Tab "Completadas" en orders.php
   - Sección de historial en dashboard

---

### Para Administradores

1. **Crear orden en estado shipped + paid**
2. **Esperar a que transportista la acepte**
3. **Ver progreso en tiempo real**
4. **Verificar entrega completada**

---

## 🔐 Seguridad

- ✅ Verificación de rol `delivery` en todos los endpoints
- ✅ Validación de que la orden pertenece al transportista
- ✅ Transacciones SQL para atomicidad
- ✅ Prepared statements contra SQL injection
- ✅ Logs de errores para debugging

---

## 🌐 URLs del Sistema

| Página | URL | Descripción |
|--------|-----|-------------|
| Login | `/auth/login.php` | Inicio de sesión |
| Dashboard | `/delivery/dashboarddeli.php` | Panel principal |
| Órdenes | `/delivery/orders.php` | Vista completa con tabs |
| API Órdenes | `/delivery/api/get_orders.php` | Endpoint JSON |
| API Acciones | `/delivery/delivery_actions.php` | Acciones POST |

---

## 🚀 Características Destacadas

1. ✅ **Auto-actualización:** Dashboard se actualiza cada 30 segundos
2. ✅ **Geolocalización:** Captura automática de ubicación (GPS)
3. ✅ **Búsqueda en tiempo real:** 500ms de delay para optimizar
4. ✅ **Notificaciones:** Toast messages con animaciones
5. ✅ **Responsive:** Funciona en móviles y tablets
6. ✅ **Historial completo:** Tracking de todos los cambios de estado
7. ✅ **Estadísticas:** Métricas de rendimiento del transportista

---

## 📊 Reportes y Estadísticas

El sistema genera automáticamente:
- Entregas del día
- Entregas totales
- Calificación promedio
- Tasa de aceptación
- Tasa de completación

---

## 🎯 Estado Actual

✅ **SISTEMA 100% FUNCIONAL**

- Dashboard con órdenes disponibles
- Vista completa de órdenes (4 tabs)
- API REST funcional
- Flujo tipo Didi implementado
- Geolocalización integrada
- Notificaciones en tiempo real
- Historial de entregas

---

## 🔧 Mantenimiento

### Logs
```bash
# Ver logs de PHP
Get-Content c:\laragon\www\error.log -Tail 50

# Ver logs de MySQL
Get-Content c:\laragon\bin\mysql\mysql-8.0.30\data\*.err -Tail 50
```

### Limpiar datos de prueba
```sql
-- Eliminar órdenes de prueba
DELETE FROM orders WHERE order_number LIKE 'TEST-%';
DELETE FROM orders WHERE order_number LIKE 'ORD-202510%';
```

---

## 📞 Soporte

- **Documentación:** `docs/delivery/`
- **Tests:** `tests/delivery/`
- **Ejemplos:** `tests/delivery/EJEMPLOS_API.md`

---

**¡Sistema listo para producción!** 🎉
