# 🚀 INSTALACIÓN RÁPIDA - SISTEMA DE ENTREGAS TIPO DIDI

## ⚡ Pasos de Instalación (5 minutos)

### 1️⃣ Ejecutar Migración SQL

**Opción A: phpMyAdmin**
1. Abrir phpMyAdmin: `http://localhost/phpmyadmin`
2. Seleccionar base de datos: `angelow`
3. Clic en pestaña "SQL"
4. Copiar y pegar todo el contenido de: `database/migrations/add_delivery_system.sql`
5. Clic en "Continuar"

**Opción B: Línea de comandos**
```bash
cd c:\laragon\www\angelow
mysql -u root -p angelow < database\migrations\add_delivery_system.sql
```

**Opción C: Importar archivo**
1. phpMyAdmin → Base de datos `angelow`
2. Pestaña "Importar"
3. "Examinar" → Seleccionar `add_delivery_system.sql`
4. Clic en "Continuar"

### 2️⃣ Verificar Instalación

Ejecutar en phpMyAdmin:

```sql
-- Debe devolver 3 tablas
SHOW TABLES LIKE '%deliver%';

-- Debe devolver: order_deliveries, delivery_status_history, driver_statistics

-- Ver estructura de tabla principal
DESCRIBE order_deliveries;
```

### 3️⃣ Verificar Archivos

Asegurarse que existen estos archivos:
- ✅ `delivery/delivery_actions.php` (NUEVO)
- ✅ `delivery/dashboarddeli.php` (ACTUALIZADO)
- ✅ `database/migrations/add_delivery_system.sql` (NUEVO)

### 4️⃣ Probar el Sistema

**Como Administrador:**
1. Login como admin
2. Ir a Órdenes
3. Crear/editar una orden
4. Cambiar estado a "Shipped" (Enviado)
5. Esto crea automáticamente un registro en `order_deliveries`

**Como Transportista:**
1. Login con usuario rol `delivery`
2. Ir al Dashboard de Delivery
3. Verás órdenes asignadas con botones:
   - 🟢 **Aceptar** - Para aceptar la orden
   - 🔴 **Rechazar** - Para rechazar la orden
4. Después de aceptar:
   - ▶️ **Iniciar Recorrido**
   - 📍 **He Llegado**
   - ✅ **Entrega Completada**

## 🔍 Verificación Post-Instalación

### Verificar Triggers
```sql
SHOW TRIGGERS FROM angelow WHERE `Table` = 'order_deliveries';
-- Debe mostrar 2 triggers
```

### Verificar Vistas
```sql
SELECT * FROM v_orders_awaiting_driver LIMIT 1;
SELECT * FROM v_active_deliveries_by_driver LIMIT 1;
SELECT * FROM v_driver_rankings LIMIT 1;
```

### Verificar Procedimientos
```sql
SHOW PROCEDURE STATUS WHERE Db = 'angelow' AND Name LIKE '%Driver%';
-- Debe mostrar 5 procedimientos
```

## 📋 Estados del Sistema

### Estados de Orden (tabla `orders`)
- `pending` → Pendiente
- `processing` → En proceso
- `shipped` → Enviado (asignado a transportista)
- `delivered` → Entregado
- `cancelled` → Cancelado

### Estados de Entrega (tabla `order_deliveries`)
- `awaiting_driver` → Esperando asignación
- `driver_assigned` → **Asignada (esperando aceptación)** 👈 NUEVO
- `driver_accepted` → **Aceptada por transportista** 👈 NUEVO
- `in_transit` → **En camino** 👈 NUEVO
- `arrived` → **Llegó al destino** 👈 NUEVO
- `delivered` → Entregado
- `rejected` → Rechazado
- `cancelled` → Cancelado

## 🎯 Flujo Completo de Prueba

### Paso 1: Crear Usuario Transportista
```sql
-- Si no tienes un usuario delivery, crear uno
INSERT INTO users (name, email, password, phone, role) 
VALUES ('Juan Transportista', 'delivery@test.com', '$2y$10$...', '999888777', 'delivery');
```

### Paso 2: Crear Orden de Prueba
```sql
-- Crear orden de prueba
INSERT INTO orders (user_id, order_number, total, status, shipping_address, shipping_city) 
VALUES (1, 'TEST-001', 150.00, 'processing', 'Av. Test 123', 'Lima');
```

### Paso 3: Asignar a Transportista
```sql
-- Cambiar a shipped para asignar automáticamente
UPDATE orders SET status = 'shipped' WHERE order_number = 'TEST-001';

-- Asignar manualmente a transportista
INSERT INTO order_deliveries (order_id, driver_id, delivery_status, assigned_at)
VALUES ((SELECT id FROM orders WHERE order_number = 'TEST-001'), 
        (SELECT id FROM users WHERE role = 'delivery' LIMIT 1),
        'driver_assigned',
        NOW());
```

### Paso 4: Login como Transportista
1. Ir a: `http://localhost/angelow/auth/login.php`
2. Login con credenciales de transportista
3. Ir a: `http://localhost/angelow/delivery/dashboarddeli.php`
4. Ver orden TEST-001 con botones de Aceptar/Rechazar

### Paso 5: Aceptar Orden
1. Click en botón verde "Aceptar"
2. Ver mensaje de confirmación
3. Página se recarga
4. Ahora aparece botón "Iniciar Recorrido"

### Paso 6: Proceso Completo
1. **Aceptar** → Botón verde
2. **Iniciar Recorrido** → Botón azul
3. **He Llegado** → Botón celeste
4. **Entrega Completada** → Botón verde
   - Ingresar nombre: "María García"
   - Agregar notas: "Entrega exitosa"

## 🐛 Solución de Problemas Comunes

### Error: "Tabla order_deliveries no existe"
```bash
# Re-ejecutar migración
mysql -u root -p angelow < database\migrations\add_delivery_system.sql
```

### Error: "No aparecen órdenes"
```sql
-- Verificar que tienes órdenes asignadas
SELECT * FROM order_deliveries WHERE driver_id = 'TU_USER_ID';

-- Si no hay, asignar una:
CALL AssignOrderToDriver(1, 'TU_USER_ID');
```

### Error: "No puedo aceptar orden"
```sql
-- Verificar estado de la entrega
SELECT * FROM order_deliveries WHERE id = 'DELIVERY_ID';

-- El estado debe ser 'driver_assigned'
-- Si no lo es:
UPDATE order_deliveries SET delivery_status = 'driver_assigned' WHERE id = 'DELIVERY_ID';
```

### Error: "Call to undefined function"
- Verificar que actualizaste `dashboarddeli.php`
- Limpiar caché del navegador: Ctrl + Shift + R

### Error de permisos
```sql
-- Verificar rol del usuario
SELECT id, name, role FROM users WHERE id = 'TU_USER_ID';

-- Debe ser 'delivery', si no:
UPDATE users SET role = 'delivery' WHERE id = 'TU_USER_ID';
```

## 📊 Consultas Útiles para Debugging

### Ver todas las entregas activas
```sql
SELECT * FROM v_active_deliveries_by_driver;
```

### Ver historial de una orden
```sql
SELECT * FROM delivery_status_history 
WHERE order_id = 1 
ORDER BY created_at DESC;
```

### Ver estadísticas de transportista
```sql
SELECT * FROM driver_statistics WHERE driver_id = 'USER_ID';
```

### Ver órdenes esperando transportista
```sql
SELECT * FROM v_orders_awaiting_driver;
```

## ✅ Checklist de Instalación

- [ ] Migración SQL ejecutada sin errores
- [ ] 3 tablas creadas (order_deliveries, delivery_status_history, driver_statistics)
- [ ] 2 triggers creados (verificar con SHOW TRIGGERS)
- [ ] 5 procedimientos creados (verificar con SHOW PROCEDURE STATUS)
- [ ] 3 vistas creadas (verificar con SHOW FULL TABLES WHERE Table_type = 'VIEW')
- [ ] Archivo delivery_actions.php existe
- [ ] Archivo dashboarddeli.php actualizado
- [ ] Usuario con rol 'delivery' existe
- [ ] Orden de prueba creada y asignada
- [ ] Login como transportista funciona
- [ ] Dashboard muestra órdenes
- [ ] Botones de Aceptar/Rechazar funcionan
- [ ] Flujo completo probado

## 🎉 ¡Listo!

Si completaste todos los pasos, el sistema está funcionando correctamente.

### Próximos pasos:
1. Personalizar estilos CSS según tu diseño
2. Agregar notificaciones push (opcional)
3. Implementar geolocalización en tiempo real (opcional)
4. Crear módulo de asignación automática de órdenes

## 📞 ¿Necesitas ayuda?

- Revisa los logs PHP: `c:\laragon\www\angelow\logs`
- Revisa errores SQL en phpMyAdmin
- Verifica la consola del navegador (F12)

---

**Tiempo estimado:** 5-10 minutos  
**Dificultad:** Media  
**Requiere:** MySQL, PHP 7.4+, navegador moderno
