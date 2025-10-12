# 🎯 Guía Rápida de Instalación - Badge de Órdenes

## Paso a Paso

### 1️⃣ Ejecutar la Migración de Base de Datos

**Opción A:** Ejecutar desde terminal (RECOMENDADO)
```bash
php database/migrations/orders_badge/run_migration.php
```

**Opción B:** Ejecutar desde el navegador
```
http://localhost/angelow/database/migrations/orders_badge/run_migration.php
```

Deberías ver:
```
✅ Migración ejecutada exitosamente
✅ Tabla 'order_views' creada con 4 columnas
```

**Alternativa:** Si prefieres hacerlo manualmente:
1. Abre phpMyAdmin
2. Selecciona la base de datos `angelow`
3. Ve a la pestaña SQL
4. Pega este código:

```sql
CREATE TABLE IF NOT EXISTS `order_views` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `user_id` VARCHAR(20) NOT NULL,
  `viewed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order_user` (`order_id`, `user_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_order_views_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_views_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 2️⃣ Probar que Todo Funciona

Abre tu navegador y ve a:
```
http://localhost/angelow/tests/admin/test_orders_badge.html
```

Haz clic en **"Ejecutar Todo"** y verifica que todos los tests pasen.

### 3️⃣ Probar en el Panel de Administración

1. **Crea una orden nueva** (desde el frontend como cliente)
2. **Inicia sesión como administrador**
3. **Observa el sidebar** - deberías ver un badge rojo con un número al lado de "Órdenes"
4. **Haz clic en "Órdenes"** - el badge debería desaparecer con una animación suave
5. **Crea otra orden** desde el frontend
6. **Espera 30 segundos o recarga la página** - el badge debería reaparecer

## ✅ ¿Qué hace cada archivo?

### Base de Datos
- `database/migrations/create_order_views_table.sql` - Crea la tabla para rastrear órdenes vistas
- `database/run_migration.php` - Script para ejecutar la migración

### Backend (PHP)
- `admin/api/mark_orders_viewed.php` - Marca órdenes como vistas cuando entras a orders.php
- `admin/api/get_new_orders_count.php` - Obtiene el conteo de órdenes nuevas

### Frontend (JavaScript)
- `js/admin/orders-badge.js` - Maneja el badge automáticamente

### Estilos (CSS)
- `css/dashboardadmin.css` - Animación de pulso para el badge

### Modificados
- `layouts/headeradmin2.php` - Agregada lógica de conteo y badge dinámico

## 🎨 Características

✨ **Badge animado** con efecto de pulso  
✨ **Conteo individual** por administrador  
✨ **Actualización automática** cada 30 segundos  
✨ **Desaparición suave** al entrar a la página de órdenes  
✨ **Multi-usuario** - cada admin ve su propio conteo  

## 🐛 ¿Problemas?

### El badge no aparece
- Verifica que hayas ejecutado la migración
- Asegúrate de que haya órdenes sin ver en la base de datos
- Abre la consola del navegador (F12) y busca errores

### El badge no desaparece
- Verifica que el archivo `admin/api/mark_orders_viewed.php` exista
- Revisa la consola del navegador para ver errores AJAX
- Limpia el caché del navegador

### Verificar manualmente el conteo

Ejecuta esta consulta en phpMyAdmin (reemplaza 'TU_USER_ID' con tu ID de usuario):

```sql
SELECT COUNT(*) as nuevas
FROM orders o
LEFT JOIN order_views ov ON o.id = ov.order_id AND ov.user_id = 'TU_USER_ID'
WHERE ov.id IS NULL;
```

## 📱 Soporte

Si tienes problemas, verifica:
1. Que la tabla `order_views` exista en la base de datos
2. Que los archivos API estén en la carpeta correcta
3. Que el JavaScript se esté cargando (verifica en las DevTools del navegador)
4. Que tu usuario tenga rol 'admin'

¡Y listo! 🎉
