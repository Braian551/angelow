# Sistema de Autenticación y Roles - Angelow

## Descripción General

Se ha implementado un sistema robusto de autenticación y control de acceso basado en roles para garantizar que cada usuario acceda únicamente a las páginas correspondientes a su rol.

## Roles Disponibles

### 1. **admin** - Administrador
- **Dashboard:** `/admin/dashboardadmin.php`
- **Acceso a:**
  - Todas las páginas en `/admin/`
  - Gestión de productos, órdenes, usuarios, inventario
  - Configuración del sistema
  - Reportes y estadísticas

### 2. **delivery** - Repartidor/Transportista
- **Dashboard:** `/delivery/dashboarddeli.php`
- **Acceso a:**
  - Todas las páginas en `/delivery/`
  - Lista de órdenes asignadas
  - Actualización de estado de entregas
  - Historial de entregas

### 3. **user / customer** - Cliente
- **Dashboard:** `/users/dashboarduser.php`
- **Acceso a:**
  - Todas las páginas en `/users/`
  - Tienda y productos (`/tienda/`, `/producto/`)
  - Proceso de compra (`/pagos/`)
  - Donaciones (`/donaciones/`)
  - Gestión de perfil y direcciones

## Archivos Principales

### 1. `/auth/role_redirect.php`
Archivo central que maneja toda la lógica de roles y redirección.

**Funciones principales:**

- `getDashboardByRole($role)`: Retorna la URL del dashboard según el rol
- `getAllowedPagesByRole($role)`: Retorna array de páginas permitidas por rol
- `checkRoleAccess($role, $currentPage)`: Verifica si el usuario tiene acceso a una página
- `redirectToDashboard($userId, $conn)`: Redirige al dashboard correspondiente
- `enforceRoleAccess()`: Middleware que verifica el acceso en cada carga de página
- `requireRole($requiredRoles)`: Restringe acceso a roles específicos

### 2. `/auth/login.php`
Proceso de inicio de sesión actualizado que:
- Valida credenciales (email o teléfono)
- Verifica cuenta no bloqueada
- Crea sesión y cookies si "Recordar cuenta"
- **Redirige automáticamente al dashboard correcto según el rol**

### 3. Headers actualizados
- `/layouts/headerproducts.php`
- `/layouts/client/headerclientconfig.php`

Ambos incluyen `enforceRoleAccess()` para verificar el acceso en cada página.

## Flujo de Autenticación

```
1. Usuario ingresa credenciales en login
   ↓
2. Sistema valida credenciales
   ↓
3. Sistema obtiene el rol del usuario
   ↓
4. Sistema redirige al dashboard correspondiente:
   - admin → /admin/dashboardadmin.php
   - delivery → /delivery/dashboarddeli.php
   - user/customer → /users/dashboarduser.php
```

## Flujo de Verificación de Acceso

```
1. Usuario intenta acceder a una página
   ↓
2. Header incluye role_redirect.php
   ↓
3. enforceRoleAccess() se ejecuta
   ↓
4. Se obtiene el rol del usuario
   ↓
5. Se verifica si tiene acceso a la página
   ↓
6. Si NO tiene acceso → Redirige a su dashboard
   Si SÍ tiene acceso → Permite la carga
```

## Implementación en Páginas Protegidas

### Ejemplo para página de Admin:

```php
<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Verificar que el usuario tenga rol de admin
requireRole('admin');

// Resto del código de la página...
?>
```

### Ejemplo para página de Delivery:

```php
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Verificar que el usuario tenga rol de delivery
requireRole('delivery');

// Resto del código de la página...
?>
```

### Ejemplo para página de Usuario (múltiples roles):

```php
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Verificar que el usuario tenga rol de user o customer
requireRole(['user', 'customer']);

// Resto del código de la página...
?>
```

## Páginas Públicas

Las siguientes páginas NO requieren autenticación y son accesibles para todos:
- `index.php` - Página de inicio
- `login.php` - Inicio de sesión
- `register.php` - Registro
- `logout.php` - Cerrar sesión
- `productos.php` - Catálogo de productos
- `verproducto.php` - Vista de producto individual

## Seguridad

### Protecciones implementadas:

1. **Verificación de sesión:** Todas las páginas protegidas verifican `$_SESSION['user_id']`
2. **Verificación de rol:** Cada página verifica que el usuario tenga el rol correcto
3. **Redirección automática:** Si un usuario intenta acceder a una página no permitida, se redirige a su dashboard
4. **Registro de errores:** Todos los errores se registran con `error_log()`
5. **Prevención de inyección SQL:** Uso de prepared statements en todas las consultas

## Mantenimiento

### Agregar un nuevo rol:

1. Actualizar función `getDashboardByRole()` en `role_redirect.php`
2. Actualizar función `getAllowedPagesByRole()` con las páginas permitidas
3. Crear el dashboard correspondiente
4. Actualizar la tabla `users` en la base de datos si es necesario

### Agregar páginas a un rol existente:

1. Editar función `getAllowedPagesByRole()` en `role_redirect.php`
2. Agregar la ruta de la página en el array del rol correspondiente

## Ejemplo de Uso Completo

```php
// En cualquier página protegida del sistema
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Opción 1: Requerir un rol específico
requireRole('admin');

// Opción 2: Requerir uno de varios roles
requireRole(['admin', 'delivery']);

// El resto del código solo se ejecutará si el usuario tiene el rol correcto
?>
```

## Solución de Problemas

### Usuario no es redirigido correctamente:
- Verificar que la sesión esté iniciada
- Verificar que el rol en la base de datos sea correcto
- Verificar que `role_redirect.php` esté incluido correctamente

### Usuario puede acceder a páginas no permitidas:
- Verificar que `enforceRoleAccess()` se ejecute en el header
- Verificar que la ruta esté definida en `getAllowedPagesByRole()`
- Verificar que el header se incluya en la página

### Loops de redirección:
- Verificar que el dashboard del rol esté en las páginas permitidas
- Verificar que no haya conflictos en las rutas permitidas

## Base de Datos

### Tabla users - Campo role

Valores permitidos:
- `'admin'` - Administrador del sistema
- `'delivery'` - Repartidor/transportista
- `'user'` - Usuario cliente estándar
- `'customer'` - Sinónimo de user (compatibilidad)

```sql
-- Verificar rol de un usuario
SELECT id, name, email, role FROM users WHERE id = ?;

-- Cambiar rol de un usuario
UPDATE users SET role = 'delivery' WHERE id = ?;
```

## Notas Importantes

1. Todos los dashboards deben incluir `requireRole()` al inicio
2. Los headers deben incluir `enforceRoleAccess()`
3. Las páginas públicas están excluidas automáticamente de la verificación
4. El sistema registra errores en el log de PHP para debugging
5. Las redirecciones son permanentes (no retornan a la página anterior)

---

**Última actualización:** Octubre 2025
**Versión:** 1.0
**Autor:** Sistema Angelow
