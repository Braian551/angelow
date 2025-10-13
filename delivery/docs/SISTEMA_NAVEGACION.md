# 🗺️ Sistema de Navegación GPS en Tiempo Real - Angelow Delivery

## 📋 Descripción

Sistema completo de navegación GPS en tiempo real estilo **Uber/Waze** para el módulo de delivery. Incluye:

- ✅ **Mapa 3D interactivo** con OpenStreetMap (Leaflet)
- ✅ **Rutas optimizadas** usando OSRM (Open Source Routing Machine)
- ✅ **Tracking GPS en tiempo real** con actualización cada 5 segundos
- ✅ **Instrucciones de voz** (Text-to-Speech)
- ✅ **Cálculo automático de ETA** (tiempo estimado de llegada)
- ✅ **Marcadores animados** con efectos de pulso
- ✅ **Diseño profesional** con tema oscuro tipo Uber
- ✅ **Responsive** para móviles y tablets
- ✅ **100% GRATUITO** - sin costos de APIs

---

## 🚀 Instalación

### 1️⃣ Ejecutar Migración de Base de Datos

La migración creará las tablas necesarias para el tracking GPS:

1. Abre tu navegador
2. Ve a: `http://localhost/angelow/database/run_migration_007.php`
3. Haz clic en **"▶️ Ejecutar Migración"**
4. Espera a que se complete (verás ✅ en verde)

**Tablas creadas:**
- `location_tracking` - Historial de ubicaciones GPS
- `delivery_waypoints` - Puntos de ruta
- `navigation_events` - Eventos de navegación
- Campos adicionales en `order_deliveries` para tracking

**Procedimientos creados:**
- `UpdateDeliveryLocation()` - Actualiza ubicación del delivery
- `StartNavigation()` - Inicia navegación
- `CalculateDistance()` - Calcula distancia entre puntos

---

## 📱 Uso del Sistema

### Para Conductores (Delivery)

1. **Iniciar sesión** como usuario delivery
2. Ir a **"Órdenes Disponibles"**
3. Ver las pestañas:
   - 📦 **Disponibles** - Órdenes sin asignar
   - 👤 **Asignadas a mí** - Órdenes que te fueron asignadas
   - 🚚 **En proceso** - Órdenes activas
   - ✅ **Completadas** - Historial

4. En órdenes **"En proceso"**, verás el botón: **"▶️ Iniciar Recorrido"**

5. Al hacer clic, se abrirá la pantalla de navegación GPS

### Pantalla de Navegación

#### 🎯 Características Principales:

**Header Superior:**
- ⬅️ Botón volver (con confirmación si está navegando)
- 📍 Número de orden
- ⚙️ Menú de opciones

**Mapa Principal:**
- 🗺️ Mapa interactivo con tu ubicación en tiempo real
- 📍 Marcador azul (tu ubicación) con animación de pulso
- 🎯 Marcador verde (destino)
- 🛣️ Línea de ruta en color morado

**Panel Inferior (compacto):**
- 🧭 Instrucción actual ("Continúa por Calle X")
- ⏱️ ETA (tiempo estimado en minutos)
- 📏 Distancia restante
- 🚗 Velocidad actual
- 🕐 Hora estimada de llegada

**Panel Inferior (expandido):**
- 👤 Información del cliente
- 📞 Botón para llamar
- 📍 Dirección completa de entrega
- 📝 Notas del pedido
- 💰 Monto total

**Botones Flotantes:**
- 🎯 **Centrar** - Centra el mapa en tu ubicación
- 🔊 **Voz** - Activa/desactiva instrucciones de voz
- 🚦 **Tráfico** - Ver información de tráfico (próximamente)

---

## 🔧 Flujo de Trabajo

```
1. Delivery ve orden → "Aceptar Orden"
2. Orden cambia a estado: "driver_accepted"
3. Click en "Iniciar Recorrido"
4. Sistema solicita permisos de ubicación ✋
5. Sistema calcula ruta automáticamente 🗺️
6. Click en "Iniciar Navegación" 🚀
7. Tracking GPS cada 5 segundos 📍
8. Actualizaciones en tiempo real a la BD 💾
9. Al llegar cerca: notificación "¡Estás cerca!" 🔔
10. Click en "He Llegado" 📍
11. Click en "Completar Entrega" ✅
```

---

## 🛠️ Tecnologías Utilizadas

### Frontend
- **Leaflet.js** - Mapas interactivos
- **OpenStreetMap** - Tiles de mapa (GRATIS)
- **OSRM** - Cálculo de rutas (GRATIS)
- **Nominatim** - Geocodificación (GRATIS)
- **Web Speech API** - Instrucciones de voz
- **Geolocation API** - GPS del navegador

### Backend
- **PHP 7.4+** - Lógica del servidor
- **MySQL 8.0** - Base de datos
- **PDO** - Conexión a BD
- **JSON** - Intercambio de datos

### APIs (Todas GRATUITAS)
- 🗺️ **OpenStreetMap** - Mapas sin límite
- 🛣️ **OSRM** - Rutas sin límite
- 📍 **Nominatim** - Geocoding (max 1 req/seg)

---

## 📁 Archivos Creados

### Base de Datos
```
database/
├── migrations/
│   └── 007_add_location_tracking.sql     # Migración principal
└── run_migration_007.php                  # Script de ejecución
```

### Delivery
```
delivery/
├── navigation.php                         # Página principal de navegación
└── api/
    └── navigation_api.php                 # API REST de navegación
```

### Estilos
```
css/delivery/
└── navigation.css                         # Estilos tipo Uber/Waze
```

### JavaScript
```
js/delivery/
└── navigation.js                          # Lógica completa de navegación
```

---

## 🎨 Características de Diseño

### Tema Oscuro Profesional
- Fondo negro con gradientes
- Paneles con blur effect (efecto vidrio)
- Colores morados (#667eea, #764ba2)
- Verde para confirmaciones (#10b981)
- Animaciones suaves y fluidas

### Responsive
- ✅ Desktop (768px+)
- ✅ Tablet (480px - 768px)
- ✅ Mobile (< 480px)

### Animaciones
- Pulse en marcador del conductor
- Fade in/out de notificaciones
- Slide up/down de paneles
- Transiciones suaves en botones

---

## 🔐 Permisos Requeridos

El sistema solicita automáticamente:

1. **📍 Ubicación GPS**
   - Necesario para tracking en tiempo real
   - Alta precisión (enableHighAccuracy: true)
   - Actualización continua

2. **🔊 Síntesis de Voz** (opcional)
   - Para instrucciones de voz
   - No requiere permisos explícitos

3. **🔋 Batería** (opcional)
   - Para optimizar actualizaciones
   - No requiere permisos explícitos

---

## 📊 Base de Datos

### Tabla: location_tracking
Almacena cada punto GPS del recorrido:
```sql
- latitude, longitude    # Coordenadas GPS
- accuracy               # Precisión en metros
- speed                  # Velocidad en km/h
- heading                # Dirección (0-360°)
- battery_level          # Nivel de batería
- is_moving              # Si está en movimiento
- recorded_at            # Timestamp
```

### Tabla: navigation_events
Eventos importantes:
```sql
- navigation_started     # Inicio de navegación
- route_recalculated     # Ruta recalculada
- destination_near       # Cerca del destino
- arrived                # Llegó al destino
- off_route              # Fuera de ruta
```

---

## 🔄 API Endpoints

### GET `/delivery/api/navigation_api.php`

#### `?action=get_route`
Obtiene ruta optimizada entre dos puntos.

**Parámetros:**
- `start_lat`, `start_lng` - Ubicación inicial
- `end_lat`, `end_lng` - Ubicación final

**Respuesta:**
```json
{
  "success": true,
  "route": {
    "geometry": {...},
    "distance_km": 5.2,
    "duration_seconds": 720,
    "steps": [...]
  }
}
```

#### `?action=geocode`
Convierte dirección en coordenadas.

**Parámetros:**
- `address` - Dirección a geocodificar

#### `?action=reverse_geocode`
Convierte coordenadas en dirección.

**Parámetros:**
- `lat`, `lng` - Coordenadas

### POST `/delivery/api/navigation_api.php`

#### `action=start_navigation`
Inicia navegación y guarda ruta en BD.

#### `action=update_location`
Actualiza ubicación GPS (cada 5 segundos).

#### `action=log_event`
Registra evento de navegación.

---

## 📈 Métricas Tracking

El sistema registra automáticamente:

- ✅ Distancia total recorrida
- ✅ Tiempo total de navegación
- ✅ Velocidad promedio
- ✅ Precisión GPS promedio
- ✅ Nivel de batería durante el recorrido
- ✅ Eventos importantes (desvíos, paradas, etc.)

---

## 🐛 Solución de Problemas

### "No se puede obtener ubicación"
- ✅ Verifica permisos del navegador
- ✅ Usa HTTPS (en producción)
- ✅ Verifica configuración de GPS del dispositivo

### "Error al calcular ruta"
- ✅ Verifica conexión a internet
- ✅ Confirma que las coordenadas sean válidas
- ✅ OSRM puede estar temporalmente no disponible

### "Mapa no carga"
- ✅ Verifica conexión a internet
- ✅ Revisa consola del navegador
- ✅ Confirma que Leaflet.js se cargó correctamente

---

## 🚀 Próximas Mejoras

- [ ] Capa de tráfico en tiempo real
- [ ] Múltiples paradas (waypoints)
- [ ] Rutas alternativas con comparación
- [ ] Modo offline con mapas descargados
- [ ] Compartir ubicación con el cliente
- [ ] Historial de rutas con replay
- [ ] Estadísticas del conductor

---

## 📞 Soporte

Para problemas o dudas:
1. Revisa la consola del navegador (F12)
2. Verifica los logs de PHP
3. Consulta la documentación de Leaflet.js

---

## 📄 Licencia

Sistema desarrollado para Angelow Delivery.
Tecnologías utilizadas son de código abierto.

---

## ✨ Créditos

- **OpenStreetMap** - Mapas
- **OSRM** - Routing
- **Leaflet.js** - Motor de mapas
- **Font Awesome** - Iconos

---

**¡Listo para navegar! 🚀🗺️**
