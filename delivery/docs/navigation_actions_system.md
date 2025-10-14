# Sistema de Cancelación y Reporte de Problemas - Navegación

## Descripción General

Este sistema permite a los conductores cancelar entregas y reportar problemas durante la navegación, con registro completo en base de datos y capacidad de adjuntar evidencia fotográfica.

## Componentes Implementados

### 1. Base de Datos

**Tablas:**
- `delivery_navigation_cancellations`: Registra todas las cancelaciones de navegación
- `delivery_problem_reports`: Registra todos los problemas reportados durante entregas

**Procedimientos Almacenados:**
- `CancelNavigation()`: Procesa la cancelación de una navegación
- `ReportProblem()`: Registra un problema reportado

**Vistas:**
- `v_navigation_issues`: Vista consolidada de cancelaciones y problemas

**Triggers:**
- `after_problem_report_insert`: Genera alertas automáticas para problemas críticos

### 2. API Backend

**Archivo:** `delivery/api/navigation_actions.php`

**Endpoints:**

```php
// Cancelar navegación
POST /delivery/api/navigation_actions.php?action=cancel_navigation
Body: {
    "delivery_id": 9,
    "reason": "customer_unavailable",
    "notes": "Cliente no responde llamadas",
    "latitude": -12.046374,
    "longitude": -77.042793
}

// Reportar problema
POST /delivery/api/navigation_actions.php?action=report_problem
FormData: {
    "delivery_id": 9,
    "problem_type": "route_blocked",
    "title": "Calle bloqueada por construcción",
    "description": "Obra en la Av. Principal...",
    "severity": "high",
    "latitude": -12.046374,
    "longitude": -77.042793,
    "photo": File
}

// Obtener tipos de problemas
GET /delivery/api/navigation_actions.php?action=get_problem_types

// Obtener razones de cancelación
GET /delivery/api/navigation_actions.php?action=get_cancellation_reasons
```

### 3. Frontend - Modales

**Archivos:**
- `delivery/modals/cancel_navigation_modal.php`: Modal de cancelación
- `delivery/modals/report_problem_modal.php`: Modal de reporte de problemas

**Características:**
- Validación de formularios
- Contadores de caracteres
- Vista previa de fotos
- Indicadores de progreso
- Estados de severidad visual

### 4. JavaScript - Integración

**Archivo:** `js/delivery/navigation.js`

**Funciones Agregadas:**

```javascript
// Mostrar modal de cancelación
window.cancelNavigation()

// Procesar cancelación desde modal
window.processCancellation(reason, notes)

// Mostrar modal de reporte
window.reportProblem()

// Enviar reporte de problema
window.submitProblemReport(problemData)
```

## Flujos de Trabajo

### Cancelar Navegación

1. Usuario hace clic en botón "Cancelar Navegación"
2. Se muestra modal con:
   - Progreso actual (distancia, tiempo, porcentaje)
   - Selector de razón (6 opciones)
   - Campo de notas adicionales
3. Usuario selecciona razón y confirma
4. Sistema:
   - Obtiene ubicación actual
   - Llama al API `/navigation_actions.php?action=cancel_navigation`
   - API ejecuta procedimiento `CancelNavigation()`
   - Actualiza estado de sesión a 'cancelled'
   - Actualiza estado de entrega a 'cancelled'
5. Navegación se detiene y redirige a lista de órdenes

### Reportar Problema

1. Usuario hace clic en botón "Reportar Problema"
2. Se muestra modal con:
   - Selector de tipo (10 opciones)
   - Selector de severidad (4 niveles)
   - Campo de título
   - Campo de descripción
   - Opción de adjuntar foto
3. Usuario completa formulario y envía
4. Sistema:
   - Obtiene ubicación actual
   - Sube foto al servidor (si existe)
   - Llama al API `/navigation_actions.php?action=report_problem`
   - API ejecuta procedimiento `ReportProblem()`
   - Registra problema en BD
   - Si es crítico, trigger genera alerta automática
5. Usuario recibe confirmación y puede continuar navegando

## Tipos de Problemas

| Valor | Descripción | Icono |
|-------|-------------|-------|
| `route_blocked` | Ruta Bloqueada | fa-road-barrier |
| `wrong_address` | Dirección Incorrecta | fa-map-marker-question |
| `gps_error` | Error de GPS | fa-satellite-dish |
| `traffic_jam` | Tráfico Pesado | fa-cars |
| `road_closed` | Vía Cerrada | fa-road-circle-xmark |
| `vehicle_issue` | Problema del Vehículo | fa-car-burst |
| `weather` | Condición Climática | fa-cloud-rain |
| `customer_issue` | Problema con Cliente | fa-user-xmark |
| `app_error` | Error de la App | fa-mobile-screen-button |
| `other` | Otro | fa-circle-question |

## Razones de Cancelación

| Valor | Descripción | Icono |
|-------|-------------|-------|
| `order_cancelled` | Pedido Cancelado por Cliente | fa-ban |
| `customer_unavailable` | Cliente No Disponible | fa-user-slash |
| `address_wrong` | Dirección Incorrecta/No Existe | fa-location-crosshairs |
| `technical_issue` | Problema Técnico | fa-wrench |
| `driver_emergency` | Emergencia del Conductor | fa-ambulance |
| `other` | Otra Razón | fa-ellipsis |

## Niveles de Severidad

| Nivel | Descripción | Color | Acción |
|-------|-------------|-------|--------|
| `low` | Baja | Verde | Registro normal |
| `medium` | Media | Amarillo | Registro normal |
| `high` | Alta | Naranja | Notificación admin |
| `critical` | Crítica | Rojo | Alerta inmediata (trigger) |

## Archivos Modificados

1. ✅ `database/migrations/010_navigation_actions/001_create_tables.sql` (NUEVO)
2. ✅ `delivery/api/navigation_actions.php` (NUEVO)
3. ✅ `delivery/modals/cancel_navigation_modal.php` (NUEVO)
4. ✅ `delivery/modals/report_problem_modal.php` (NUEVO)
5. ✅ `js/delivery/navigation.js` (MODIFICADO - líneas 1277-1428)
6. ✅ `delivery/navigation.php` (MODIFICADO - includes de modales)

## Verificación de Instalación

```sql
-- Verificar tablas creadas
SHOW TABLES LIKE '%cancellation%';
SHOW TABLES LIKE '%problem_report%';

-- Verificar procedimientos
SHOW PROCEDURE STATUS WHERE Db = 'angelow' AND Name IN ('CancelNavigation', 'ReportProblem');

-- Verificar vista
SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_angelow = 'v_navigation_issues';

-- Probar vista
SELECT * FROM v_navigation_issues LIMIT 5;
```

## Ejemplo de Uso desde JavaScript

```javascript
// Cancelar navegación
window.cancelNavigation();
// Usuario selecciona razón en modal → processCancellation() se ejecuta automáticamente

// Reportar problema
window.reportProblem();
// Usuario completa formulario → submitProblemReport() se ejecuta automáticamente
```

## Almacenamiento de Fotos

- **Directorio:** `uploads/problem_reports/`
- **Formato:** `problem_{delivery_id}_{timestamp}.{ext}`
- **Tamaño máximo:** 5MB
- **Formatos permitidos:** JPG, PNG, GIF
- **Ejemplo:** `problem_9_1697235467.jpg`

## Consultas Útiles para Administración

```sql
-- Ver últimas cancelaciones
SELECT * FROM delivery_navigation_cancellations 
ORDER BY created_at DESC 
LIMIT 10;

-- Ver problemas reportados hoy
SELECT * FROM delivery_problem_reports 
WHERE DATE(created_at) = CURDATE() 
ORDER BY severity DESC, created_at DESC;

-- Ver estadísticas de cancelación por razón
SELECT 
    reason,
    COUNT(*) as total,
    AVG(progress_percentage) as avg_progress
FROM delivery_navigation_cancellations 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY reason 
ORDER BY total DESC;

-- Ver problemas críticos sin resolver
SELECT * FROM delivery_problem_reports 
WHERE severity = 'critical' 
AND status IN ('pending', 'in_review')
ORDER BY created_at DESC;

-- Usar vista consolidada
SELECT * FROM v_navigation_issues 
WHERE DATE(created_at) = CURDATE()
ORDER BY created_at DESC;
```

## Consideraciones de Seguridad

- ✅ Autenticación requerida en API (via `auth_middleware.php`)
- ✅ Validación de tipos de archivo en uploads
- ✅ Límite de tamaño de archivos (5MB)
- ✅ Sanitización de inputs en procedimientos almacenados
- ✅ Uso de prepared statements en PHP
- ✅ Validación de permisos por rol de usuario

## Testing

### Test de Cancelación
1. Iniciar navegación
2. Hacer clic en "Cancelar Navegación"
3. Verificar que modal muestra progreso correcto
4. Seleccionar razón y agregar notas
5. Confirmar
6. Verificar registro en BD: `SELECT * FROM delivery_navigation_cancellations WHERE delivery_id = ?`

### Test de Reporte
1. Durante navegación activa
2. Hacer clic en "Reportar Problema"
3. Seleccionar tipo, severidad, llenar formulario
4. Adjuntar foto (opcional)
5. Enviar
6. Verificar registro en BD: `SELECT * FROM delivery_problem_reports WHERE delivery_id = ?`
7. Verificar foto en: `uploads/problem_reports/`

## Próximos Pasos (Opcional)

- [ ] Panel de administración para revisar reportes
- [ ] Sistema de notificaciones push para problemas críticos
- [ ] Análisis de patrones de cancelación
- [ ] Heatmap de problemas reportados
- [ ] Exportación de reportes a CSV/PDF
- [ ] Integración con sistema de tickets de soporte

## Soporte

Para más información, consultar:
- Documentación de procedimientos: Ver comentarios en `001_create_tables.sql`
- API documentation: Headers de funciones en `navigation_actions.php`
- Frontend integration: Comentarios en `navigation.js`

---

**Fecha de implementación:** 13 de Octubre, 2025  
**Versión:** 1.0  
**Hotfix:** #007
