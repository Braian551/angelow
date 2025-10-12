# 🧪 PRUEBA RÁPIDA - Sistema de Roles

## ⚡ Prueba en 5 Minutos

### Paso 1: Verificar Base de Datos (1 min)

Abre phpMyAdmin o tu gestor de BD y ejecuta:

```sql
-- Ver usuarios y sus roles
SELECT id, name, email, role FROM users LIMIT 10;
```

**Si ves la columna `role`:** ✅ Continúa

**Si NO ves la columna `role`:** Ejecuta esto:

```sql
ALTER TABLE users 
ADD COLUMN role ENUM('admin', 'delivery', 'user', 'customer') 
DEFAULT 'user';
```

### Paso 2: Asignar Roles de Prueba (1 min)

Encuentra tu usuario y asigna roles:

```sql
-- Cambiar TU email aquí ↓
UPDATE users SET role = 'admin' WHERE email = 'tu-email@ejemplo.com';

-- Opcional: crear otro usuario delivery para probar
UPDATE users SET role = 'delivery' WHERE id = 2;

-- Los demás usuarios serán 'user' por defecto
UPDATE users SET role = 'user' WHERE role IS NULL;
```

### Paso 3: Ejecutar Script de Prueba (2 min)

Abre tu navegador y ve a:

```
http://localhost/angelow/tests/test_role_system.php
```

**Verifica que todo esté en verde (✓ PASS)**

Si hay errores (✗ FAIL), anota cuáles fallan y revisa la documentación.

### Paso 4: Probar Login (1 min)

1. **Cierra sesión** si estás logueado
2. Ve a: `http://localhost/angelow/auth/login.php`
3. **Inicia sesión con tu cuenta**

**¿A dónde te redirigió?**

- ✅ **Admin** → `/admin/dashboardadmin.php`
- ✅ **Delivery** → `/delivery/dashboarddeli.php`  
- ✅ **User** → `/users/dashboarduser.php`

### Paso 5: Probar Acceso Restringido (1 min)

Ahora, **copia y pega estas URLs** en tu navegador:

```
http://localhost/angelow/admin/dashboardadmin.php
http://localhost/angelow/delivery/dashboarddeli.php
http://localhost/angelow/users/dashboarduser.php
```

**Comportamiento esperado:**
- Solo puedes ver TU dashboard
- Los otros te redirigen de vuelta a tu dashboard

---

## ✅ Sistema Funciona SI:

1. ✅ El script de prueba muestra todo verde
2. ✅ El login te redirige al dashboard correcto
3. ✅ No puedes acceder a dashboards de otros roles
4. ✅ Puedes navegar normalmente en TU área

---

## ❌ Algo está mal SI:

### Problema: "No me redirige al dashboard correcto"

**Solución rápida:**
```sql
-- Ver tu rol actual
SELECT email, role FROM users WHERE email = 'tu-email@ejemplo.com';

-- Si está NULL o vacío, asignarlo
UPDATE users SET role = 'admin' WHERE email = 'tu-email@ejemplo.com';
```

Luego:
1. Cerrar sesión
2. Limpiar cookies (Ctrl+Shift+Delete)
3. Volver a iniciar sesión

### Problema: "Puedo acceder a dashboards de otros roles"

**Verificar:**
1. ¿El archivo `/auth/role_redirect.php` existe?
2. ¿Los headers incluyen `enforceRoleAccess()`?

**Solución:**
```bash
# Verificar que el archivo existe
ls c:\laragon\www\angelow\auth\role_redirect.php
```

### Problema: "Loop infinito de redirección"

**Solución:**
1. Limpiar cookies del navegador
2. Cerrar todas las ventanas del navegador
3. Abrir navegador de incógnito
4. Intentar de nuevo

### Problema: "Error 500 o página en blanco"

**Ver errores:**
1. Abrir `c:\laragon\www\angelow\storage\logs` (si existe)
2. O revisar logs de Apache/PHP en Laragon
3. Buscar línea con el error

**Solución común:**
- Verificar que todos los `require_once` tengan rutas correctas
- Verificar que `config.php` y `conexion.php` estén funcionando

---

## 🔧 Comandos SQL Útiles

### Ver todos los usuarios por rol
```sql
SELECT 
    role,
    COUNT(*) as cantidad,
    GROUP_CONCAT(name SEPARATOR ', ') as usuarios
FROM users
GROUP BY role;
```

### Cambiar rol de un usuario específico
```sql
-- Por email
UPDATE users SET role = 'admin' WHERE email = 'usuario@ejemplo.com';

-- Por ID
UPDATE users SET role = 'delivery' WHERE id = 5;
```

### Resetear todos los usuarios a 'user'
```sql
UPDATE users SET role = 'user' WHERE role IS NULL OR role = '';
```

### Ver intentos de acceso
```sql
-- Si tienes tabla de logs
SELECT * FROM access_logs 
ORDER BY access_date DESC 
LIMIT 20;
```

---

## 📱 Probar con Diferentes Usuarios

### Crear usuarios de prueba

```sql
-- Admin de prueba (contraseña: password)
INSERT INTO users (name, email, phone, password, role, created_at)
VALUES (
    'Admin Test',
    'admin@test.com',
    '1111111111',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    NOW()
);

-- Delivery de prueba (contraseña: password)
INSERT INTO users (name, email, phone, password, role, created_at)
VALUES (
    'Delivery Test',
    'delivery@test.com',
    '2222222222',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'delivery',
    NOW()
);

-- Cliente de prueba (contraseña: password)
INSERT INTO users (name, email, phone, password, role, created_at)
VALUES (
    'Cliente Test',
    'cliente@test.com',
    '3333333333',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'user',
    NOW()
);
```

**Probar login con:**
- Email: `admin@test.com` / Password: `password`
- Email: `delivery@test.com` / Password: `password`
- Email: `cliente@test.com` / Password: `password`

---

## 🎯 Checklist Final

Marca cada item cuando lo pruebes:

- [ ] Script de prueba ejecutado sin errores
- [ ] Login con admin redirige a dashboard admin
- [ ] Login con delivery redirige a dashboard delivery
- [ ] Login con user redirige a dashboard user
- [ ] Admin NO puede acceder a dashboard delivery
- [ ] Delivery NO puede acceder a dashboard admin
- [ ] User NO puede acceder a dashboard admin
- [ ] User NO puede acceder a dashboard delivery
- [ ] Todos los menús funcionan correctamente
- [ ] Logout funciona desde cualquier dashboard

---

## 📞 Si Algo Falla

1. **Revisa la documentación completa:**
   - `/docs/SISTEMA_ROLES.md`
   - `/docs/IMPLEMENTACION_ROLES.md`

2. **Ejecuta diagnóstico:**
   - `http://localhost/angelow/tests/test_role_system.php`

3. **Verifica logs de PHP:**
   - Laragon → Menu → PHP → Error Log

4. **Revisa la consola del navegador:**
   - F12 → Console (buscar errores en rojo)

---

## ✨ Todo Funciona!

Si completaste el checklist, ¡el sistema está listo! 🎉

**Ahora puedes:**
- ✅ Crear más usuarios con diferentes roles
- ✅ Personalizar dashboards
- ✅ Agregar más páginas protegidas
- ✅ Escalar el sistema según necesites

---

**Última actualización:** Octubre 2025  
**Tiempo estimado:** 5 minutos  
**Dificultad:** Fácil ⭐
