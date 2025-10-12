# 🎨 DIAGRAMA DE FLUJO - SISTEMA DE ENTREGAS

## 📊 Flujo Completo del Sistema

```
                    INICIO DEL PROCESO
                           │
                           ▼
        ┌──────────────────────────────────────┐
        │  Cliente Realiza Pedido              │
        │  Estado: PENDING                     │
        └──────────────────────────────────────┘
                           │
                           ▼
        ┌──────────────────────────────────────┐
        │  Admin Procesa Orden                 │
        │  Estado: PROCESSING                  │
        └──────────────────────────────────────┘
                           │
                           ▼
        ┌──────────────────────────────────────┐
        │  Admin Asigna a Transportista        │
        │  Estado Orden: SHIPPED               │
        │  Estado Entrega: DRIVER_ASSIGNED     │
        │  ✨ Se crea registro en              │
        │     order_deliveries                 │
        └──────────────────────────────────────┘
                           │
                           ▼
        ╔══════════════════════════════════════╗
        ║  🚨 DECISIÓN DEL TRANSPORTISTA       ║
        ║  ¿Acepta la orden?                   ║
        ╚══════════════════════════════════════╝
                    │            │
              ACEPTA│            │RECHAZA
                    │            │
         ┌──────────▼            ▼──────────┐
         │                                   │
    ┌────▼─────────────┐    ┌───────────────▼─────┐
    │  ✅ ACEPTADA      │    │  ❌ RECHAZADA        │
    │  Estado:          │    │  Estado:             │
    │  DRIVER_ACCEPTED  │    │  REJECTED            │
    └────┬─────────────┘    └───────────────┬─────┘
         │                                   │
         │                                   ▼
         │                    ┌─────────────────────┐
         │                    │ Orden vuelve a      │
         │                    │ AWAITING_DRIVER     │
         │                    │ Se puede reasignar  │
         │                    └─────────────────────┘
         │
         ▼
    ┌──────────────────────────────────────┐
    │  Transportista Inicia Recorrido      │
    │  Estado: IN_TRANSIT                  │
    │  📍 Se registra ubicación GPS        │
    └──────────────────────────────────────┘
         │
         ▼
    ┌──────────────────────────────────────┐
    │  Transportista Marca Llegada         │
    │  Estado: ARRIVED                     │
    │  📍 Se registra ubicación de llegada │
    └──────────────────────────────────────┘
         │
         ▼
    ┌──────────────────────────────────────┐
    │  Transportista Completa Entrega      │
    │  Estado Entrega: DELIVERED           │
    │  Estado Orden: DELIVERED             │
    │  ✅ Se registra:                     │
    │     - Nombre de quien recibe         │
    │     - Foto (opcional)                │
    │     - Notas                          │
    └──────────────────────────────────────┘
         │
         ▼
    ┌──────────────────────────────────────┐
    │  📊 Actualizar Estadísticas          │
    │  - total_deliveries++                │
    │  - deliveries_today++                │
    │  - Calcular tasa de aceptación       │
    └──────────────────────────────────────┘
         │
         ▼
                    FIN DEL PROCESO
```

## 🔄 Estados de Transición

### Transiciones Válidas

```
awaiting_driver
    ↓
driver_assigned ─────┬──→ driver_accepted ──→ in_transit ──→ arrived ──→ delivered
                     │
                     └──→ rejected ──→ awaiting_driver (reasignación)
```

### Tabla de Transiciones

| Estado Actual | Puede Cambiar a | Acción Requerida |
|---------------|----------------|------------------|
| `awaiting_driver` | `driver_assigned` | Admin asigna transportista |
| `driver_assigned` | `driver_accepted` | Transportista acepta |
| `driver_assigned` | `rejected` | Transportista rechaza |
| `driver_accepted` | `in_transit` | Transportista inicia recorrido |
| `in_transit` | `arrived` | Transportista marca llegada |
| `arrived` | `delivered` | Transportista confirma entrega |
| `rejected` | `awaiting_driver` | Sistema reasigna |

## 🎭 Actores y Acciones

### 👨‍💼 Administrador

```
┌─────────────────────────────────────┐
│  ACCIONES DEL ADMINISTRADOR         │
├─────────────────────────────────────┤
│  1. Procesar orden (pending →       │
│     processing)                      │
│                                      │
│  2. Asignar transportista            │
│     (processing → shipped)           │
│     • Crea order_deliveries          │
│     • Estado: driver_assigned        │
│                                      │
│  3. Ver historial completo           │
│     • delivery_status_history        │
│     • order_status_history           │
│                                      │
│  4. Ver estadísticas de              │
│     transportistas                   │
│     • driver_statistics              │
│     • Ranking de entregas            │
└─────────────────────────────────────┘
```

### 🚚 Transportista

```
┌─────────────────────────────────────┐
│  ACCIONES DEL TRANSPORTISTA         │
├─────────────────────────────────────┤
│  1. Ver órdenes asignadas            │
│     • v_active_deliveries_by_driver  │
│                                      │
│  2. ACEPTAR orden ✅                 │
│     • Cambia a driver_accepted       │
│                                      │
│  3. RECHAZAR orden ❌                │
│     • Cambia a rejected              │
│     • Proporciona razón              │
│                                      │
│  4. Iniciar recorrido 🚗             │
│     • Cambia a in_transit            │
│     • Registra ubicación GPS         │
│                                      │
│  5. Marcar llegada 📍                │
│     • Cambia a arrived               │
│     • Registra ubicación             │
│                                      │
│  6. Completar entrega ✅             │
│     • Cambia a delivered             │
│     • Registra datos de entrega      │
│                                      │
│  7. Ver estadísticas personales      │
│     • Entregas del día               │
│     • Tasa de aceptación             │
│     • Calificación promedio          │
└─────────────────────────────────────┘
```

## 🗄️ Arquitectura de Base de Datos

```
┌─────────────────────┐         ┌─────────────────────┐
│      orders         │         │       users         │
├─────────────────────┤         ├─────────────────────┤
│ • id                │         │ • id                │
│ • order_number      │         │ • name              │
│ • user_id      ────────────────→ • email             │
│ • status            │         │ • role              │
│ • total             │         │   (delivery)        │
│ • shipping_address  │         └─────────────────────┘
│ • created_at        │
└──────┬──────────────┘
       │
       │ FK: order_id
       │
       ▼
┌─────────────────────────────────────┐
│      order_deliveries               │
├─────────────────────────────────────┤
│ • id                                │
│ • order_id (FK → orders)            │
│ • driver_id (FK → users)            │
│ • delivery_status ⭐                │
│   (awaiting_driver,                 │
│    driver_assigned,                 │
│    driver_accepted,                 │
│    in_transit,                      │
│    arrived,                         │
│    delivered,                       │
│    rejected,                        │
│    cancelled)                       │
│ • assigned_at                       │
│ • accepted_at                       │
│ • started_at                        │
│ • arrived_at                        │
│ • delivered_at                      │
│ • rejection_reason                  │
│ • recipient_name                    │
│ • location_lat, location_lng        │
└──────┬──────────────────────────────┘
       │
       │ FK: delivery_id
       │
       ▼
┌─────────────────────────────────────┐
│   delivery_status_history           │
├─────────────────────────────────────┤
│ • id                                │
│ • delivery_id (FK → order_deliveries)│
│ • order_id (FK → orders)            │
│ • driver_id (FK → users)            │
│ • old_status                        │
│ • new_status                        │
│ • changed_by                        │
│ • notes                             │
│ • location_lat, location_lng        │
│ • ip_address                        │
│ • created_at                        │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│      driver_statistics              │
├─────────────────────────────────────┤
│ • id                                │
│ • driver_id (FK → users)            │
│ • total_deliveries                  │
│ • deliveries_today                  │
│ • deliveries_week                   │
│ • deliveries_month                  │
│ • total_rejected                    │
│ • average_rating                    │
│ • acceptance_rate %                 │
│ • completion_rate %                 │
│ • is_available                      │
└─────────────────────────────────────┘
```

## 🔧 Triggers Automáticos

### Trigger 1: Al cambiar estado de entrega
```
UPDATE order_deliveries
    ↓
Trigger: update_driver_stats_on_delivery_change
    ↓
Actualiza driver_statistics automáticamente
    • total_deliveries++
    • deliveries_today++
    • Calcula tasas
```

### Trigger 2: Registrar historial
```
UPDATE order_deliveries
    ↓
Trigger: track_delivery_status_changes
    ↓
INSERT INTO delivery_status_history
    • Guarda cambio de estado
    • Registra quién y cuándo
    • Guarda ubicación
```

### Trigger 3: Crear entrega automática
```
UPDATE orders SET status = 'processing'
    ↓
Trigger: create_delivery_on_order_processing
    ↓
INSERT INTO order_deliveries
    • Estado: awaiting_driver
    • Listo para asignar
```

## 📱 Flujo en Interfaz de Usuario

### Pantalla del Transportista

```
┌────────────────────────────────────────────────┐
│  🚚 Dashboard Transportista                    │
├────────────────────────────────────────────────┤
│                                                 │
│  📊 Estadísticas                                │
│  ┌─────────┬─────────┬─────────┬─────────┐    │
│  │ Hoy: 5  │ Pend: 2 │ Total:  │ ⭐: 4.8 │    │
│  │         │         │ 127     │         │    │
│  └─────────┴─────────┴─────────┴─────────┘    │
│                                                 │
│  🎯 Órdenes Asignadas                           │
│  ┌──────────────────────────────────────┐      │
│  │ #ORD-001            [ASIGNADA] 🔔    │      │
│  │ Cliente: María García                │      │
│  │ Dirección: Av. Los Olivos 123        │      │
│  │ Total: $150.00                       │      │
│  │ Asignada hace 5 minutos              │      │
│  │                                      │      │
│  │ [ ✅ Aceptar ]  [ ❌ Rechazar ]     │      │
│  └──────────────────────────────────────┘      │
│                                                 │
│  ┌──────────────────────────────────────┐      │
│  │ #ORD-002          [ACEPTADA] ✅      │      │
│  │ Cliente: Juan Pérez                  │      │
│  │ Dirección: Jr. Las Flores 456        │      │
│  │ Total: $85.50                        │      │
│  │ Aceptada hace 2 minutos              │      │
│  │                                      │      │
│  │ [ ▶️ Iniciar Recorrido ]            │      │
│  └──────────────────────────────────────┘      │
│                                                 │
│  ┌──────────────────────────────────────┐      │
│  │ #ORD-003        [EN TRÁNSITO] 🚗     │      │
│  │ Cliente: Ana López                   │      │
│  │ Dirección: Av. Principal 789         │      │
│  │ Total: $220.00                       │      │
│  │ En ruta desde hace 15 minutos        │      │
│  │                                      │      │
│  │ [ 📍 He Llegado ]                    │      │
│  └──────────────────────────────────────┘      │
│                                                 │
└────────────────────────────────────────────────┘
```

## 🔐 Seguridad y Validaciones

```
┌─────────────────────────────────────┐
│  VALIDACIONES IMPLEMENTADAS         │
├─────────────────────────────────────┤
│  ✅ Autenticación de sesión         │
│  ✅ Verificación de rol             │
│  ✅ Validación de permisos          │
│  ✅ Protección CSRF                 │
│  ✅ SQL Injection protection        │
│  ✅ Transacciones DB                │
│  ✅ Logging de errores              │
│  ✅ IP tracking                     │
└─────────────────────────────────────┘
```

## 📈 Métricas Calculadas

### Tasa de Aceptación
```
acceptance_rate = (órdenes_aceptadas / órdenes_asignadas) × 100
```

### Tasa de Completitud
```
completion_rate = (entregas_completadas / entregas_iniciadas) × 100
```

### Tiempo Promedio de Entrega
```
avg_delivery_time = AVG(delivered_at - started_at) en minutos
```

---

**📊 Este diagrama muestra el flujo completo del sistema de entregas tipo Didi**

Para más detalles técnicos, ver:
- `README_DELIVERY_SYSTEM.md` - Documentación completa
- `INSTALACION_RAPIDA.md` - Guía de instalación
- `add_delivery_system.sql` - Script SQL
