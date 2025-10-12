# Sistema de Notificaciones de Órdenes - Badge Funcional

Este sistema permite mostrar un badge con el número de órdenes nuevas (no vistas) en el sidebar del administrador.

## 🚀 Instalación

### Paso 1: Ejecutar la migración de la base de datos

Tienes dos opciones:

#### Opción A: Ejecutar el script PHP
```bash
php database/run_migration.php
```

#### Opción B: Ejecutar manualmente en phpMyAdmin
1. Abre phpMyAdmin
2. Selecciona la base de datos `angelow`
3. Ve a la pestaña SQL
4. Copia y pega el contenido del archivo `database/migrations/create_order_views_table.sql`
5. Haz clic en "Continuar"

### Paso 2: Verificar la instalación

Después de ejecutar la migración, verifica que la tabla se haya creado:

```sql
SHOW TABLES LIKE 'order_views';
```

## 📋 Funcionalidad

### ¿Cómo funciona?

1. **Badge Dinámico**: El badge muestra el número de órdenes que el administrador no ha visto
2. **Marcado Automático**: Cuando el administrador entra a la página de órdenes (`orders.php`), todas las órdenes se marcan automáticamente como vistas
3. **Actualización en Tiempo Real**: El badge se actualiza cada 30 segundos automáticamente
4. **Multi-Usuario**: Cada administrador tiene su propio contador de órdenes vistas

### Características

- ✅ Conteo individual por administrador
- ✅ Animación de pulso en el badge
- ✅ Desaparición suave del badge al entrar a órdenes
- ✅ Actualización automática cada 30 segundos
- ✅ No se actualiza cuando estás en la página de órdenes (para no sobrecargar)
- ✅ Reaparece automáticamente cuando hay nuevas órdenes

## 🔧 Archivos Modificados/Creados

### Nuevos Archivos:
- `database/migrations/create_order_views_table.sql` - Migración de la tabla
- `database/run_migration.php` - Script para ejecutar la migración
- `admin/api/mark_orders_viewed.php` - Endpoint para marcar órdenes como vistas
- `admin/api/get_new_orders_count.php` - Endpoint para obtener el conteo de órdenes nuevas
- `js/admin/orders-badge.js` - JavaScript para manejar el badge

### Archivos Modificados:
- `layouts/headeradmin2.php` - Agregada lógica de conteo y carga del script
- `css/dashboardadmin.css` - Agregada animación de pulso al badge

## 🧪 Pruebas

Para probar que funciona correctamente:

1. **Crear una nueva orden** (desde el frontend como cliente)
2. **Iniciar sesión como administrador** 
3. **Observar el badge** en el menú "Órdenes" con el número de órdenes nuevas
4. **Hacer clic en "Órdenes"** - el badge debería desaparecer
5. **Crear otra orden** desde el frontend
6. **Esperar 30 segundos** o recargar - el badge debería reaparecer

## 📊 Estructura de la Tabla

```sql
order_views
├── id (INT, AUTO_INCREMENT)
├── order_id (INT) - FK a orders(id)
├── user_id (VARCHAR(20)) - FK a users(id)
└── viewed_at (DATETIME)
```

- **order_id + user_id** tienen una restricción UNIQUE para evitar duplicados
- La tabla se limpia automáticamente cuando se elimina una orden (CASCADE)

## 🎨 Personalización

### Cambiar el color del badge

En `css/dashboardadmin.css`, modifica:

```css
.badge {
  background-color: var(--primary-color); /* Cambia esto por el color que desees */
}
```

### Cambiar el intervalo de actualización

En `js/admin/orders-badge.js`, modifica:

```javascript
setInterval(updateBadgeCount, 30000); // 30000ms = 30 segundos
```

## ⚠️ Notas Importantes

- El badge solo es visible para usuarios con rol `admin`
- Si múltiples administradores están trabajando, cada uno verá su propio contador
- El sistema no envía notificaciones push, solo actualiza el badge cada 30 segundos
- Las órdenes se marcan como vistas solo cuando el administrador accede a `orders.php`

## 🐛 Solución de Problemas

### El badge no aparece
1. Verifica que la migración se haya ejecutado correctamente
2. Revisa la consola del navegador (F12) para ver errores JavaScript
3. Verifica que existan órdenes no vistas en la base de datos

### El badge no desaparece al entrar a órdenes
1. Verifica que el archivo `admin/api/mark_orders_viewed.php` exista
2. Revisa la consola del navegador para ver si hay errores en la petición AJAX
3. Verifica los permisos de la carpeta `admin/api/`

### El conteo es incorrecto
Ejecuta esta consulta para verificar el conteo real:

```sql
SELECT COUNT(*) 
FROM orders o
LEFT JOIN order_views ov ON o.id = ov.order_id AND ov.user_id = 'TU_USER_ID'
WHERE ov.id IS NULL;
```

## 📝 Changelog

### Versión 1.0.0 (12 de Octubre, 2025)
- ✨ Implementación inicial del sistema de badge de órdenes
- ✨ Sistema de marcado automático de órdenes vistas
- ✨ Actualización automática cada 30 segundos
- ✨ Animación de pulso en el badge
