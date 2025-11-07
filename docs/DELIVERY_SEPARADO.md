# Gestión de Delivery - Aplicación Separada

## Fecha de Modificación
**7 de Noviembre de 2025**

## Resumen de Cambios

El sistema de delivery (entrega/repartidores) ha sido separado de la aplicación principal de AngeloW. El módulo de delivery ahora será gestionado como una **aplicación independiente**.

---

## Motivo de la Separación

La gestión de delivery se desarrollará como una aplicación aparte para:
- **Escalabilidad**: Permitir un desarrollo independiente del módulo de entregas
- **Mantenibilidad**: Facilitar actualizaciones y cambios sin afectar la aplicación principal
- **Especialización**: Enfocarse en funcionalidades específicas para repartidores

---

## Cambios Realizados en la Base de Datos

### Eliminación del Rol Delivery

**Tabla afectada**: `users`

**Campo modificado**: `role`

```sql
-- ANTES
role enum('customer','admin','delivery') COLLATE utf8mb4_general_ci DEFAULT 'customer'

-- DESPUÉS
role enum('customer','admin') COLLATE utf8mb4_general_ci DEFAULT 'customer'
```

### Roles Actuales del Sistema

El sistema AngeloW ahora maneja únicamente dos roles:

1. **customer** (cliente): Usuario final que realiza compras
2. **admin** (administrador): Usuario con permisos administrativos completos

---

## Tablas de Delivery Mantenidas para Simulaciones

Las siguientes tablas relacionadas con delivery **SE MANTIENEN** en la base de datos para permitir simulaciones y pruebas:

### Tablas Principales
- `order_deliveries` - Información de entregas asociadas a órdenes
- `delivery_cities` - Ciudades donde se realiza delivery
- `delivery_status_history` - Historial de estados de entrega

### Tablas de Navegación
- `delivery_navigation_sessions` - Sesiones de navegación activas
- `delivery_navigation_events` - Eventos durante la navegación
- `delivery_navigation_cancellations` - Cancelaciones de navegación
- `delivery_waypoints` - Puntos de ruta durante el delivery
- `delivery_problem_reports` - Reportes de problemas durante entregas

### Stored Procedures Mantenidos
- `AssignOrderToDriver` - Asignar orden a un repartidor
- `CompleteDelivery` - Completar una entrega
- `DriverAcceptOrder` - Repartidor acepta orden
- `DriverRejectOrder` - Repartidor rechaza orden
- `DriverMarkArrived` - Marcar llegada al destino
- `CancelNavigation` - Cancelar navegación
- `CompleteNavigation` - Completar navegación

**Nota**: Estas tablas y procedimientos permiten realizar simulaciones y pruebas del flujo de entrega desde la aplicación principal, aunque los repartidores no tendrán acceso directo a través de este sistema.

---

## Cambios Realizados en el Código

### Archivos Modificados

#### 1. `auth/role_redirect.php`

**Funciones actualizadas**:

```php
// getDashboardByRole() - Eliminado dashboard de delivery
$dashboards = [
    'admin' => BASE_URL . '/admin/dashboardadmin.php',
    'user' => BASE_URL . '/users/dashboarduser.php',
    'customer' => BASE_URL . '/users/dashboarduser.php'
];

// getAllowedPagesByRole() - Eliminadas páginas de delivery
$allowedPages = [
    'admin' => ['admin/', 'auth/logout.php'],
    'user' => ['users/', 'tienda/', 'producto/', 'pagos/', 'donaciones/', 'auth/logout.php'],
    'customer' => ['users/', 'tienda/', 'producto/', 'pagos/', 'donaciones/', 'auth/logout.php']
];
```

**Impacto**: Los usuarios ya no pueden tener el rol 'delivery' y no pueden acceder a las rutas de delivery.

---

## Carpeta de Delivery Mantenida

La carpeta `/delivery` **se mantiene en el proyecto** pero:
- ⚠️ **NO está integrada** con el sistema de autenticación principal
- 📝 Es utilizada únicamente para **referencia** y **desarrollo de la aplicación separada**
- 🔒 No es accesible a través del sistema de roles actual

**Contenido de la carpeta**:
```
delivery/
├── dashboarddeli.php
├── delivery_actions_backup.php
├── delivery_actions_v2.php
├── delivery_actions.php
├── navigation.php
├── orders.php
├── api/
├── docs/
└── modals/
```

---

## Aplicación Separada de Delivery

### Arquitectura Planificada

La aplicación de delivery será un sistema independiente con:

1. **Base de Datos Compartida**: Acceso a las tablas de delivery existentes
2. **Autenticación Independiente**: Sistema de login propio para repartidores
3. **API de Integración**: Endpoints para comunicación con AngeloW principal
4. **Interfaz Móvil**: Optimizada para uso en dispositivos móviles

### Funcionalidades Previstas

- ✅ Login/Registro de repartidores
- ✅ Visualización de órdenes asignadas
- ✅ Navegación GPS en tiempo real
- ✅ Actualización de estado de entregas
- ✅ Captura de evidencias (fotos, firmas)
- ✅ Historial de entregas
- ✅ Reportes de problemas

---

## Flujo de Trabajo Actual

### Desde AngeloW (Aplicación Principal)

1. **Administrador** asigna órdenes a repartidores (usando IDs)
2. Se crean registros en `order_deliveries`
3. Se pueden ejecutar simulaciones de entregas

### Desde Aplicación de Delivery (Futura)

1. **Repartidor** se autentica en la app separada
2. Visualiza órdenes asignadas desde `order_deliveries`
3. Actualiza estados y completa entregas
4. Datos se sincronizan en las tablas compartidas

---

## Migración y Datos Históricos

### Usuarios con Rol Delivery Existentes

Si existen usuarios con `role = 'delivery'` en la base de datos actual:

```sql
-- Verificar usuarios con rol delivery
SELECT id, name, email, role FROM users WHERE role = 'delivery';

-- Opción 1: Convertir a customer (si necesitan acceso)
UPDATE users SET role = 'customer' WHERE role = 'delivery';

-- Opción 2: Bloquear acceso (mantener para referencia)
UPDATE users SET is_blocked = 1 WHERE role = 'delivery';

-- Opción 3: Eliminar (si no son necesarios)
-- DELETE FROM users WHERE role = 'delivery';
```

**Recomendación**: Mantener los usuarios bloqueados hasta que la aplicación de delivery esté lista.

---

## Scripts de Migración

### Script para Actualizar Rol en Base de Datos

```sql
-- Archivo: database/migrations/remove_delivery_role.sql

-- 1. Actualizar definición de tabla users
ALTER TABLE users 
MODIFY COLUMN role enum('customer','admin') 
COLLATE utf8mb4_general_ci DEFAULT 'customer';

-- 2. Migrar usuarios delivery existentes (si los hay)
UPDATE users 
SET role = 'customer', 
    is_blocked = 1,
    updated_at = NOW()
WHERE role = 'delivery';

-- 3. Agregar comentario en tabla
ALTER TABLE users 
COMMENT = 'Tabla de usuarios. Rol delivery eliminado - gestionado en app separada desde Nov 2025';
```

---

## Consideraciones Técnicas

### Integración Futura

La aplicación de delivery deberá:

1. **Conectarse a la misma base de datos** o usar una API REST
2. **Respetar el esquema** de las tablas de delivery existentes
3. **Implementar autenticación propia** (JWT, OAuth, etc.)
4. **Comunicarse con AngeloW** para recibir órdenes y actualizar estados

### Seguridad

- 🔐 Implementar autenticación separada para repartidores
- 🔑 Usar API keys para comunicación entre aplicaciones
- 📱 Validar ubicación GPS para prevenir fraudes
- 🛡️ Encriptar datos sensibles en tránsito

---

## Documentación Relacionada

- `docs/SISTEMA_ROLES.md` - Sistema de roles actualizado (solo admin y customer)
- `docs/database/` - Esquemas de base de datos
- `delivery/docs/` - Documentación específica del módulo delivery

---

## Notas Importantes

⚠️ **IMPORTANTE**: Las tablas de delivery NO fueron eliminadas para permitir:
- Simulaciones desde el panel de administración
- Pruebas de integración
- Desarrollo de la aplicación separada
- Mantener datos históricos

✅ **RECOMENDACIÓN**: Al desarrollar la aplicación separada de delivery:
1. Usar las mismas tablas de la base de datos
2. Implementar autenticación independiente
3. Crear APIs para comunicación entre sistemas
4. Documentar endpoints y contratos de datos

---

## Historial de Cambios

| Fecha | Cambio | Responsable |
|-------|--------|-------------|
| 2025-11-07 | Eliminación del rol delivery del sistema principal | Sistema |
| 2025-11-07 | Documentación de separación de aplicación delivery | Sistema |

---

## Contacto y Soporte

Para más información sobre la integración o desarrollo de la aplicación de delivery separada, consultar:
- Documentación técnica en `/docs`
- Esquemas de base de datos en `/database`
- Código de referencia en `/delivery`

---

**Última actualización**: 7 de Noviembre de 2025
