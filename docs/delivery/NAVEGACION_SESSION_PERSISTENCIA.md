# Sistema de Persistencia de Sesiones de Navegación

## 📋 Descripción General

Sistema completo para persistir el estado de navegación del módulo de delivery, permitiendo que el estado se mantenga entre recargas de página y sesiones del navegador.

**Fecha de implementación:** 13 de Octubre, 2025  
**Módulo:** Delivery Navigation  
**Versión:** 1.0.0

---

## 🎯 Características Principales

### ✅ Funcionalidades Implementadas

1. **Persistencia de Estado**
   - Guarda automáticamente el estado de navegación en la base de datos
   - Recupera el estado al recargar la página
   - Mantiene el estado entre sesiones del navegador

2. **Gestión de Sesiones**
   - Iniciar navegación
   - Pausar navegación
   - Reanudar navegación
   - Completar navegación
   - Cancelar navegación

3. **Tracking en Tiempo Real**
   - Actualización automática de ubicación cada 5 segundos
   - Guardado de métricas: velocidad, distancia, ETA
   - Nivel de batería del dispositivo
   - Datos de la ruta calculada

4. **Historial de Eventos**
   - Registro automático de todos los eventos de navegación
   - Triggers para tracking automático
   - Vista de eventos para auditoría

5. **Estadísticas**
   - Tiempo total de navegación
   - Velocidad promedio
   - Distancia recorrida
   - Número de pausas

---

## 🗂️ Estructura de Archivos

```
angelow/
├── database/
│   ├── migrations/
│   │   └── 009_navigation_session/
│   │       ├── 001_create_navigation_session.sql    # Migración principal
│   │       └── 002_verify_migration.sql             # Verificación pre-migración
│   └── scripts/
│       └── check_navigation_status.sql              # Consultas de estado
│
├── delivery/
│   └── api/
│       └── navigation_session.php                   # API REST para sesiones
│
├── js/
│   └── delivery/
│       └── navigation-session.js                    # Módulo JavaScript cliente
│
├── tests/
│   └── delivery/
│       └── test_navigation_session.php              # Tests automatizados
│
└── docs/
    └── delivery/
        └── NAVEGACION_SESSION_PERSISTENCIA.md       # Esta documentación
```

---

## 🗄️ Estructura de Base de Datos

### Tabla: `delivery_navigation_sessions`

Almacena las sesiones activas de navegación.

```sql
CREATE TABLE delivery_navigation_sessions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  delivery_id INT NOT NULL,
  driver_id VARCHAR(20) NOT NULL,
  session_status ENUM('idle', 'navigating', 'paused', 'completed', 'cancelled'),
  
  -- Timestamps
  navigation_started_at DATETIME,
  navigation_paused_at DATETIME,
  navigation_resumed_at DATETIME,
  navigation_completed_at DATETIME,
  navigation_cancelled_at DATETIME,
  
  -- Ubicación actual
  current_lat DECIMAL(10, 8),
  current_lng DECIMAL(11, 8),
  destination_lat DECIMAL(10, 8),
  destination_lng DECIMAL(11, 8),
  
  -- Métricas
  total_distance_km DECIMAL(8, 2),
  remaining_distance_km DECIMAL(8, 2),
  current_speed_kmh DECIMAL(5, 2),
  average_speed_kmh DECIMAL(5, 2),
  eta_seconds INT,
  total_navigation_time_seconds INT,
  pause_count INT,
  
  -- Configuración
  voice_enabled TINYINT(1),
  traffic_visible TINYINT(1),
  route_data JSON,
  last_instruction TEXT,
  current_step_index INT,
  
  -- Tracking
  last_update_at DATETIME,
  update_count INT,
  battery_level INT,
  device_info JSON,
  
  created_at DATETIME,
  updated_at DATETIME
);
```

### Tabla: `delivery_navigation_events`

Historial de eventos durante la navegación.

```sql
CREATE TABLE delivery_navigation_events (
  id INT PRIMARY KEY AUTO_INCREMENT,
  session_id INT NOT NULL,
  delivery_id INT NOT NULL,
  driver_id VARCHAR(20) NOT NULL,
  event_type ENUM(
    'session_started', 'session_paused', 'session_resumed',
    'session_completed', 'session_cancelled', 'route_calculated',
    'route_recalculated', 'location_updated', 'instruction_given',
    'off_route', 'arrived_destination', 'speed_alert', 'error_occurred'
  ),
  event_data JSON,
  location_lat DECIMAL(10, 8),
  location_lng DECIMAL(11, 8),
  notes TEXT,
  created_at DATETIME
);
```

---

## 🔧 Procedimientos Almacenados

### 1. `StartNavigation`

Inicia o reanuda una sesión de navegación.

```sql
CALL StartNavigation(
  p_delivery_id INT,
  p_driver_id VARCHAR(20),
  p_lat DECIMAL(10, 8),
  p_lng DECIMAL(11, 8),
  p_device_info JSON
);
```

**Ejemplo:**
```sql
CALL StartNavigation(1, 'DRV001', -34.6037, -58.3816, '{"device": "iPhone"}');
```

### 2. `PauseNavigation`

Pausa la navegación activa.

```sql
CALL PauseNavigation(p_delivery_id INT, p_driver_id VARCHAR(20));
```

### 3. `UpdateNavigationLocation`

Actualiza la ubicación y métricas durante la navegación.

```sql
CALL UpdateNavigationLocation(
  p_delivery_id INT,
  p_driver_id VARCHAR(20),
  p_lat DECIMAL(10, 8),
  p_lng DECIMAL(11, 8),
  p_speed DECIMAL(5, 2),
  p_distance_remaining DECIMAL(8, 2),
  p_eta_seconds INT,
  p_battery_level INT
);
```

### 4. `GetNavigationState`

Obtiene el estado completo de la sesión.

```sql
CALL GetNavigationState(p_delivery_id INT, p_driver_id VARCHAR(20));
```

### 5. `SaveRouteData`

Guarda los datos de la ruta calculada.

```sql
CALL SaveRouteData(
  p_delivery_id INT,
  p_driver_id VARCHAR(20),
  p_route_data JSON,
  p_total_distance DECIMAL(8, 2)
);
```

### 6. `CompleteNavigation`

Marca la navegación como completada.

```sql
CALL CompleteNavigation(
  p_delivery_id INT,
  p_driver_id VARCHAR(20),
  p_total_distance DECIMAL(8, 2)
);
```

---

## 📡 API REST

### Endpoint Base

```
/delivery/api/navigation_session.php
```

### Endpoints Disponibles

#### 1. Obtener Estado

```http
GET /delivery/api/navigation_session.php?action=get-state&delivery_id=1
```

**Respuesta:**
```json
{
  "success": true,
  "state": {
    "id": 1,
    "session_status": "navigating",
    "current_lat": -34.6037,
    "current_lng": -58.3816,
    "remaining_distance_km": 5.2,
    "eta_seconds": 900,
    ...
  },
  "has_active_session": true
}
```

#### 2. Iniciar Navegación

```http
POST /delivery/api/navigation_session.php?action=start
Content-Type: application/json

{
  "delivery_id": 1,
  "lat": -34.6037,
  "lng": -58.3816,
  "device_info": {
    "device": "iPhone 12",
    "os": "iOS 16"
  }
}
```

#### 3. Pausar Navegación

```http
POST /delivery/api/navigation_session.php?action=pause
Content-Type: application/json

{
  "delivery_id": 1
}
```

#### 4. Actualizar Ubicación

```http
POST /delivery/api/navigation_session.php?action=update-location
Content-Type: application/json

{
  "delivery_id": 1,
  "lat": -34.6040,
  "lng": -58.3820,
  "speed": 35.5,
  "distance_remaining": 4.8,
  "eta_seconds": 850,
  "battery_level": 85
}
```

#### 5. Guardar Ruta

```http
POST /delivery/api/navigation_session.php?action=save-route
Content-Type: application/json

{
  "delivery_id": 1,
  "total_distance": 5.5,
  "route_data": {
    "waypoints": [...],
    "instructions": [...]
  }
}
```

#### 6. Completar Navegación

```http
POST /delivery/api/navigation_session.php?action=complete
Content-Type: application/json

{
  "delivery_id": 1,
  "total_distance": 5.8
}
```

---

## 💻 Uso en JavaScript

### Inicialización

```javascript
// Incluir el módulo en navigation.php
<script src="<?= BASE_URL ?>/js/delivery/navigation-session.js"></script>

// En el código de navegación
const sessionManager = new NavigationSessionManager(
    BASE_URL,
    deliveryData.delivery_id,
    deliveryData.driver_id
);

// Inicializar y cargar estado
const result = await sessionManager.initialize();

if (result.hasActiveSession) {
    console.log('Restaurando sesión:', result.state);
    
    // Restaurar UI según el estado
    if (result.state.session_status === 'navigating') {
        // Ya estaba navegando, continuar
        resumeNavigationUI(result.state);
    } else if (result.state.session_status === 'paused') {
        // Estaba pausado, mostrar botón de reanudar
        showResumeButton();
    }
}
```

### Ciclo de Vida de la Navegación

```javascript
// 1. Usuario inicia navegación
async function handleMainAction() {
    if (!state.isNavigating) {
        // Iniciar nueva navegación
        const result = await sessionManager.startNavigation(
            state.currentLocation.lat,
            state.currentLocation.lng,
            {
                device: 'Web',
                browser: navigator.userAgent
            }
        );
        
        if (result.success) {
            state.isNavigating = true;
            updateUI();
        }
    }
}

// 2. Auto-guardado durante navegación
function onLocationUpdate(location) {
    // Actualizar UI
    updateDriverMarker(location);
    
    // Guardar en base de datos
    sessionManager.updateLocation({
        lat: location.lat,
        lng: location.lng,
        speed: location.speed,
        distanceRemaining: state.distanceRemaining,
        etaSeconds: state.etaSeconds,
        batteryLevel: state.batteryLevel
    });
}

// 3. Guardar ruta cuando se calcula
function onRouteCalculated(route) {
    const routeData = {
        waypoints: route.waypoints,
        instructions: route.instructions,
        bounds: route.bounds
    };
    
    sessionManager.saveRoute(routeData, route.summary.totalDistance / 1000);
}

// 4. Pausar navegación
async function pauseNavigation() {
    const result = await sessionManager.pauseNavigation();
    if (result.success) {
        state.isNavigating = false;
        updateUI();
    }
}

// 5. Completar navegación
async function completeNavigation() {
    const totalDistance = state.route.summary.totalDistance / 1000;
    const result = await sessionManager.completeNavigation(totalDistance);
    
    if (result.success) {
        // Redirigir a completar entrega
        window.location.href = `${BASE_URL}/delivery/complete.php?delivery_id=${deliveryId}`;
    }
}
```

---

## 🧪 Testing

### Ejecutar Tests

```bash
# Desde la raíz del proyecto
php tests/delivery/test_navigation_session.php
```

### Tests Incluidos

1. ✅ Verificar existencia de tablas
2. ✅ Verificar procedimientos almacenados
3. ✅ Iniciar navegación
4. ✅ Actualizar ubicación
5. ✅ Pausar navegación
6. ✅ Reanudar navegación
7. ✅ Guardar datos de ruta
8. ✅ Completar navegación
9. ✅ Registrar eventos
10. ✅ Verificar triggers
11. ✅ Verificar vistas

### Salida Esperada

```
╔═══════════════════════════════════════════════════╗
║   TESTS: Sistema de Sesiones de Navegación      ║
╚═══════════════════════════════════════════════════╝

✅ PASS: Verificar existencia de tablas
✅ PASS: Verificar procedimientos almacenados
✅ PASS: Iniciar navegación
✅ PASS: Actualizar ubicación
✅ PASS: Pausar navegación
✅ PASS: Reanudar navegación
✅ PASS: Guardar datos de ruta
✅ PASS: Completar navegación
✅ PASS: Registrar eventos de navegación
✅ PASS: Verificar triggers automáticos
✅ PASS: Verificar vistas

╔═══════════════════════════════════════════════════╗
║                    RESUMEN                       ║
╚═══════════════════════════════════════════════════╝

Total de tests ejecutados: 11
Tests exitosos: 11
Tests fallidos: 0

🎉 ¡Todos los tests pasaron correctamente!
```

---

## 📊 Consultas Útiles

### Ver sesiones activas

```sql
SELECT * FROM v_active_navigation_sessions;
```

### Ver historial de una sesión

```sql
SET @delivery_id = 1;
SELECT * FROM delivery_navigation_events 
WHERE delivery_id = @delivery_id 
ORDER BY created_at DESC;
```

### Estadísticas por driver

```sql
SELECT 
    driver_id,
    COUNT(*) as total_sesiones,
    AVG(total_distance_km) as distancia_promedio,
    AVG(average_speed_kmh) as velocidad_promedio
FROM delivery_navigation_sessions
GROUP BY driver_id;
```

---

## 🚀 Instalación

### 1. Verificar Pre-requisitos

```bash
# Conectar a MySQL
mysql -u root -p angelow

# Ejecutar script de verificación
source database/migrations/009_navigation_session/002_verify_migration.sql
```

### 2. Aplicar Migración

```bash
# Aplicar migración principal
source database/migrations/009_navigation_session/001_create_navigation_session.sql
```

### 3. Ejecutar Tests

```bash
php tests/delivery/test_navigation_session.php
```

### 4. Integrar en navigation.php

```php
<!-- Añadir antes del cierre de </body> -->
<script src="<?= BASE_URL ?>/js/delivery/navigation-session.js"></script>
```

---

## 🔒 Seguridad

- ✅ Validación de rol de usuario (delivery)
- ✅ Verificación de propiedad de entrega
- ✅ Protección contra SQL injection con prepared statements
- ✅ Sanitización de entradas JSON
- ✅ Headers de seguridad en API

---

## 📈 Métricas Monitoreadas

1. **Tiempo de Navegación**
   - Tiempo total
   - Tiempo en movimiento
   - Tiempo pausado

2. **Distancia**
   - Distancia total recorrida
   - Distancia restante
   - Desviaciones de ruta

3. **Velocidad**
   - Velocidad actual
   - Velocidad promedio
   - Alertas de velocidad

4. **Batería**
   - Nivel actual
   - Tendencia de descarga

5. **Actualizaciones**
   - Frecuencia de actualizaciones
   - Última actualización

---

## 🐛 Troubleshooting

### Problema: La sesión no se restaura al recargar

**Solución:**
1. Verificar que el procedimiento `GetNavigationState` retorna datos
2. Revisar la consola del navegador para errores
3. Verificar que `delivery_id` es correcto

### Problema: No se guardan las actualizaciones de ubicación

**Solución:**
1. Verificar que la sesión está en estado 'navigating'
2. Revisar logs de error en PHP
3. Verificar permisos de API

### Problema: Los triggers no se ejecutan

**Solución:**
```sql
-- Verificar que existen
SHOW TRIGGERS WHERE `Table` = 'delivery_navigation_sessions';

-- Recrear si es necesario
source database/migrations/009_navigation_session/001_create_navigation_session.sql
```

---

## 📝 Changelog

### Versión 1.0.0 (2025-10-13)
- ✅ Implementación inicial
- ✅ Tablas de sesiones y eventos
- ✅ Procedimientos almacenados
- ✅ API REST completa
- ✅ Módulo JavaScript
- ✅ Tests automatizados
- ✅ Documentación completa

---

## 👥 Autor

**Proyecto:** Angelow Delivery System  
**Módulo:** Navigation Session Persistence  
**Fecha:** 13 de Octubre, 2025

---

## 📚 Referencias

- [Leaflet Documentation](https://leafletjs.com/)
- [MySQL JSON Functions](https://dev.mysql.com/doc/refman/8.0/en/json-functions.html)
- [Geolocation API](https://developer.mozilla.org/en-US/docs/Web/API/Geolocation_API)
