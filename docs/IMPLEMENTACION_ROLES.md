# Implementación del Sistema de Roles - Guía Rápida

## 🚀 Resumen de Cambios

Se ha implementado un **sistema robusto de autenticación y control de acceso basado en roles** para que cada usuario sea redirigido automáticamente a su dashboard correspondiente según su rol.

## ✅ Archivos Modificados/Creados

### Nuevos Archivos:
1. **`/auth/role_redirect.php`** - Sistema central de roles y redirección
2. **`/docs/SISTEMA_ROLES.md`** - Documentación completa del sistema
3. **`/tests/test_role_system.php`** - Script de pruebas automáticas
4. **`/database/migrations/setup_roles_system.sql`** - Queries SQL para configuración

### Archivos Modificados:
1. **`/auth/login.php`** - Actualizado para usar redirección por roles
2. **`/layouts/headerproducts.php`** - Integrado con sistema de roles
3. **`/layouts/client/headerclientconfig.php`** - Integrado con sistema de roles
4. **`/admin/dashboardadmin.php`** - Protegido con `requireRole('admin')`
5. **`/delivery/dashboarddeli.php`** - Protegido con `requireRole('delivery')`
6. **`/users/dashboarduser.php`** - Protegido con `requireRole(['user', 'customer'])`

## 📋 Pasos de Implementación

### Paso 1: Verificar Base de Datos

Ejecuta las siguientes consultas SQL para verificar que la columna `role` existe:

```sql
-- Ver estructura actual
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'users' 
  AND COLUMN_NAME = 'role';

-- Ver todos los usuarios y sus roles
SELECT id, name, email, role FROM users;
```

Si la columna `role` NO existe, créala:

```sql
ALTER TABLE users 
ADD COLUMN role ENUM('admin', 'delivery', 'user', 'customer') 
DEFAULT 'user';
```

### Paso 2: Asignar Roles a Usuarios

```sql
-- Asignar rol de admin
UPDATE users SET role = 'admin' WHERE email = 'tu-email-admin@ejemplo.com';

-- Asignar rol de delivery
UPDATE users SET role = 'delivery' WHERE email = 'repartidor@ejemplo.com';

-- Asignar rol de user (clientes)
UPDATE users SET role = 'user' WHERE role IS NULL;
```

### Paso 3: Ejecutar Pruebas

Accede a través de tu navegador:
```
http://localhost/angelow/tests/test_role_system.php
```

Este script verificará:
- ✅ Que todos los archivos existan
- ✅ Que las funciones estén definidas
- ✅ Que la base de datos esté configurada
- ✅ Que los dashboards existan
- ✅ Que los headers estén actualizados

### Paso 4: Probar el Login

1. **Cerrar sesión actual** (si estás logueado)
2. **Ir a:** `http://localhost/angelow/auth/login.php`
3. **Probar con usuarios de diferentes roles:**

   - **Usuario Admin:** Debe redirigir a `/admin/dashboardadmin.php`
   - **Usuario Delivery:** Debe redirigir a `/delivery/dashboarddeli.php`
   - **Usuario Cliente:** Debe redirigir a `/users/dashboarduser.php`

### Paso 5: Verificar Protección de Páginas

Intenta acceder manualmente a dashboards de otros roles:

```
http://localhost/angelow/admin/dashboardadmin.php  (solo admin)
http://localhost/angelow/delivery/dashboarddeli.php (solo delivery)
http://localhost/angelow/users/dashboarduser.php (solo user/customer)
```

**Comportamiento esperado:**
- Si NO tienes el rol correcto → Redirige a TU dashboard
- Si tienes el rol correcto → Muestra la página

## 🎯 Roles y Dashboards

| Rol | Dashboard | Acceso a |
|-----|-----------|----------|
| **admin** | `/admin/dashboardadmin.php` | Todo en `/admin/` |
| **delivery** | `/delivery/dashboarddeli.php` | Todo en `/delivery/` |
| **user/customer** | `/users/dashboarduser.php` | `/users/`, `/tienda/`, `/producto/`, `/pagos/` |

## 🔧 Uso en Nuevas Páginas

Para proteger cualquier página nueva con roles:

```php
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Requerir un rol específico
requireRole('admin');

// O requerir uno de varios roles
requireRole(['admin', 'delivery']);

// El resto de tu código aquí...
?>
```

## 🐛 Solución de Problemas

### Problema: "Usuario no es redirigido correctamente"

**Solución:**
1. Verificar que el usuario tenga un rol asignado en la BD
2. Verificar que la sesión esté iniciada correctamente
3. Limpiar cookies y sesiones del navegador

```sql
-- Verificar rol del usuario
SELECT id, name, email, role FROM users WHERE email = 'tu-email@ejemplo.com';
```

### Problema: "Loop infinito de redirección"

**Solución:**
1. Verificar que el dashboard del rol esté en las páginas permitidas
2. Revisar el archivo `/auth/role_redirect.php` - función `getAllowedPagesByRole()`

### Problema: "Puede acceder a páginas no permitidas"

**Solución:**
1. Verificar que el header incluya `enforceRoleAccess()`
2. Verificar que la página tenga `requireRole()` al inicio

## 📊 Estadísticas y Monitoreo

Para ver estadísticas de usuarios por rol:

```sql
SELECT 
    role,
    COUNT(*) as total_users,
    COUNT(CASE WHEN is_blocked = 0 THEN 1 END) as active_users
FROM users
GROUP BY role;
```

## 🔒 Seguridad

El sistema implementa:
- ✅ Verificación de sesión en cada página
- ✅ Verificación de rol en cada acceso
- ✅ Redirección automática si no tiene permisos
- ✅ Registro de errores en log de PHP
- ✅ Prepared statements para prevenir SQL injection

## 📝 Crear Usuarios de Prueba

Si necesitas usuarios de prueba (contraseña: `password`):

```sql
-- Usuario Admin
INSERT INTO users (name, email, phone, password, role, created_at)
VALUES (
    'Admin Test',
    'admin@test.com',
    '1234567890',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    NOW()
);

-- Usuario Delivery
INSERT INTO users (name, email, phone, password, role, created_at)
VALUES (
    'Delivery Test',
    'delivery@test.com',
    '0987654321',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'delivery',
    NOW()
);
```

## 📚 Documentación Adicional

Para más detalles, consulta:
- **Documentación completa:** `/docs/SISTEMA_ROLES.md`
- **Queries SQL:** `/database/migrations/setup_roles_system.sql`
- **Script de pruebas:** `/tests/test_role_system.php`

## ✨ Características Implementadas

- ✅ Redirección automática según rol después del login
- ✅ Control de acceso por roles en todas las páginas
- ✅ Protección de dashboards específicos
- ✅ Manejo de cookies "Recordar cuenta"
- ✅ Registro de errores para debugging
- ✅ Sistema escalable para agregar nuevos roles
- ✅ Compatible con el sistema actual de usuarios

## 🎉 ¡Listo!

El sistema de roles está completamente implementado. Ahora:

1. ✅ Los usuarios serán redirigidos a su dashboard correcto
2. ✅ Los dashboards están protegidos según el rol
3. ✅ No podrán acceder a páginas de otros roles
4. ✅ El sistema es seguro y escalable

---

**Última actualización:** Octubre 2025  
**Versión:** 1.0  
**Autor:** Sistema Angelow
