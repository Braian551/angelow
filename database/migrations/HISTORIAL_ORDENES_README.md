# 📋 Sistema de Historial de Órdenes - Documentación

## 🎯 Descripción
Sistema completo de seguimiento de cambios para órdenes con registro automático mediante triggers de MySQL y visualización en timeline con diseño glassmorphism.

---

## 🚀 Instalación

### 1. Ejecutar el Script SQL
```bash
# Desde MySQL Workbench o phpMyAdmin, ejecutar:
source add_order_history.sql

# O desde línea de comandos:
mysql -u root -p angelow < add_order_history.sql
```

### 2. Verificar Instalación
```sql
-- Verificar que la tabla existe
SHOW TABLES LIKE 'order_status_history';

-- Ver los triggers creados
SHOW TRIGGERS LIKE 'orders';

-- Ver el procedimiento almacenado
SHOW PROCEDURE STATUS WHERE Db = 'angelow';
```

---

## 📊 Estructura de la Base de Datos

### Tabla: `order_status_history`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT | ID único del registro |
| `order_id` | INT | ID de la orden (FK) |
| `changed_by` | VARCHAR(20) | ID del usuario que hizo el cambio |
| `changed_by_name` | VARCHAR(100) | Nombre del usuario |
| `change_type` | ENUM | Tipo de cambio (status, payment_status, shipping, etc.) |
| `field_changed` | VARCHAR(100) | Campo específico modificado |
| `old_value` | TEXT | Valor anterior |
| `new_value` | TEXT | Valor nuevo |
| `description` | TEXT | Descripción legible del cambio |
| `ip_address` | VARCHAR(45) | IP del usuario |
| `user_agent` | TEXT | User agent del navegador |
| `created_at` | DATETIME | Fecha del cambio |

### Tipos de Cambios (`change_type`)
- `created` - Orden creada
- `status` - Cambio de estado de la orden
- `payment_status` - Cambio de estado de pago
- `shipping` - Cambio de método/ciudad de envío
- `address` - Cambio de dirección
- `notes` - Cambio de notas
- `cancelled` - Orden cancelada
- `refunded` - Orden reembolsada
- `items` - Modificación de items/total
- `other` - Otros cambios

---

## 🔧 Uso en PHP

### Registrar Cambios Manualmente (Opcional)
Los triggers registran automáticamente los cambios, pero puedes hacerlo manualmente:

```php
// Configurar variables de sesión antes de UPDATE
$conn->exec("SET @current_user_id = '{$_SESSION['user_id']}'");
$conn->exec("SET @current_user_name = '{$userName}'");
$conn->exec("SET @current_user_ip = '{$_SERVER['REMOTE_ADDR']}'");

// Realizar el UPDATE (el trigger registrará el cambio)
$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->execute(['shipped', $orderId]);

// Limpiar variables
$conn->exec("SET @current_user_id = NULL");
$conn->exec("SET @current_user_name = NULL");
$conn->exec("SET @current_user_ip = NULL");
```

### Obtener Historial de una Orden

```php
// Método 1: Consulta directa
$query = "SELECT 
    osh.*,
    u.name as changed_by_full_name,
    u.role as changed_by_role
FROM order_status_history osh
LEFT JOIN users u ON osh.changed_by = u.id
WHERE osh.order_id = ?
ORDER BY osh.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute([$orderId]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Método 2: Usando procedimiento almacenado
$stmt = $conn->prepare("CALL GetOrderHistory(?)");
$stmt->execute([$orderId]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

### Insertar Registro Manual

```php
$stmt = $conn->prepare("
    INSERT INTO order_status_history 
    (order_id, changed_by, changed_by_name, change_type, field_changed, old_value, new_value, description, ip_address)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $orderId,
    $_SESSION['user_id'],
    $userName,
    'status',
    'status',
    'pending',
    'shipped',
    'Estado cambiado de "Pendiente" a "Enviado"',
    $_SERVER['REMOTE_ADDR']
]);
```

---

## 🎨 Visualización en Frontend

### HTML (Ya implementado en detail.php)
```php
<?php foreach ($orderHistory as $history): ?>
    <div class="timeline-item" data-change-type="<?= $history['change_type'] ?>">
        <div class="timeline-point" style="background: <?= getChangeTypeColor($history['change_type']) ?>;">
            <i class="<?= getChangeTypeIcon($history['change_type']) ?>"></i>
        </div>
        <div class="timeline-content">
            <div class="timeline-header">
                <h4><?= htmlspecialchars($history['description']) ?></h4>
                <span class="timeline-date">
                    <i class="fas fa-clock"></i>
                    <?= formatDate($history['created_at']) ?>
                </span>
            </div>
            <!-- ... más detalles ... -->
        </div>
    </div>
<?php endforeach; ?>
```

### Funciones Helper PHP
```php
function getChangeTypeIcon($changeType) {
    $icons = [
        'status' => 'fas fa-info-circle',
        'payment_status' => 'fas fa-credit-card',
        'shipping' => 'fas fa-truck',
        'address' => 'fas fa-map-marker-alt',
        'notes' => 'fas fa-sticky-note',
        'created' => 'fas fa-plus-circle',
        'cancelled' => 'fas fa-ban',
        'refunded' => 'fas fa-undo',
        'items' => 'fas fa-shopping-cart',
        'other' => 'fas fa-edit'
    ];
    return $icons[$changeType] ?? 'fas fa-edit';
}

function getChangeTypeColor($changeType) {
    $colors = [
        'status' => '#0077b6',
        'payment_status' => '#10b981',
        'shipping' => '#f59e0b',
        'address' => '#8b5cf6',
        'notes' => '#6366f1',
        'created' => '#22c55e',
        'cancelled' => '#ef4444',
        'refunded' => '#f97316',
        'items' => '#ec4899',
        'other' => '#64748b'
    ];
    return $colors[$changeType] ?? '#64748b';
}
```

---

## 📈 Consultas Útiles

### Ver todos los cambios de una orden
```sql
SELECT * FROM order_status_history 
WHERE order_id = 10 
ORDER BY created_at DESC;
```

### Ver cambios de estado de pago
```sql
SELECT * FROM order_status_history 
WHERE order_id = 10 AND change_type = 'payment_status'
ORDER BY created_at DESC;
```

### Ver quién hizo más cambios
```sql
SELECT 
    changed_by_name,
    COUNT(*) as total_changes
FROM order_status_history
GROUP BY changed_by_name
ORDER BY total_changes DESC;
```

### Resumen de cambios por orden
```sql
SELECT * FROM v_order_history_summary;
```

### Órdenes con más modificaciones
```sql
SELECT 
    o.order_number,
    COUNT(osh.id) as total_changes
FROM orders o
LEFT JOIN order_status_history osh ON o.id = osh.order_id
GROUP BY o.id, o.order_number
ORDER BY total_changes DESC
LIMIT 10;
```

### Ver historial de los últimos 7 días
```sql
SELECT 
    o.order_number,
    osh.description,
    osh.changed_by_name,
    osh.created_at
FROM order_status_history osh
INNER JOIN orders o ON osh.order_id = o.id
WHERE osh.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY osh.created_at DESC;
```

---

## 🔒 Seguridad

### Consideraciones
1. **Auditoría Completa**: Todos los cambios quedan registrados con usuario, IP y timestamp
2. **No Editable**: El historial no se puede modificar, solo consultar
3. **Cascade Delete**: Si se elimina una orden, su historial también se elimina
4. **Roles**: Se registra el rol del usuario que hizo el cambio

### Permisos Recomendados
```sql
-- Solo administradores pueden ver el historial completo
GRANT SELECT ON angelow.order_status_history TO 'admin_user'@'localhost';

-- Usuarios normales solo ven su propio historial
GRANT SELECT ON angelow.order_status_history TO 'regular_user'@'localhost' 
WHERE changed_by = CURRENT_USER();
```

---

## 🐛 Troubleshooting

### Problema: Los triggers no se activan
```sql
-- Verificar que los triggers existen
SHOW TRIGGERS WHERE `Table` = 'orders';

-- Verificar permisos
SHOW GRANTS FOR CURRENT_USER();
```

### Problema: No se registran los cambios
```sql
-- Verificar que las variables de sesión están configuradas
SELECT @current_user_id, @current_user_name, @current_user_ip;

-- Verificar el último registro
SELECT * FROM order_status_history ORDER BY id DESC LIMIT 1;
```

### Problema: Error de foreign key
```sql
-- Verificar constraints
SELECT * FROM information_schema.TABLE_CONSTRAINTS 
WHERE TABLE_NAME = 'order_status_history';

-- Deshabilitar temporalmente para migración
SET FOREIGN_KEY_CHECKS = 0;
-- ... tu operación ...
SET FOREIGN_KEY_CHECKS = 1;
```

---

## 🎯 Características

✅ **Registro Automático**: Los triggers capturan cambios sin código adicional
✅ **Timeline Visual**: Diseño moderno con glassmorphism
✅ **Iconos Font Awesome**: Iconos específicos por tipo de cambio
✅ **Colores Dinámicos**: Cada tipo de cambio tiene su color distintivo
✅ **Roles Visuales**: Badges para identificar quién hizo el cambio
✅ **Responsive**: Se adapta a móviles y tablets
✅ **Animaciones**: Transiciones suaves y efectos modernos
✅ **Histórico Completo**: Guarda valor anterior y nuevo
✅ **IP Tracking**: Registra desde dónde se hizo el cambio
✅ **Timestamps**: Fecha y hora exacta de cada cambio

---

## 📝 Ejemplos de Uso

### Ejemplo 1: Cambiar estado de orden desde admin
```php
// En edit.php
$conn->exec("SET @current_user_id = '{$_SESSION['user_id']}'");
$conn->exec("SET @current_user_name = 'Braian Admin'");
$conn->exec("SET @current_user_ip = '{$_SERVER['REMOTE_ADDR']}'");

$stmt = $conn->prepare("UPDATE orders SET status = 'shipped' WHERE id = ?");
$stmt->execute([15]);

// El trigger registrará: "Estado cambiado de 'Pendiente' a 'Enviado'"
```

### Ejemplo 2: Aprobar pago
```php
$conn->exec("SET @current_user_id = '{$_SESSION['user_id']}'");
$conn->exec("SET @current_user_name = 'Admin'");

$stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
$stmt->execute([15]);

// El trigger registrará: "Estado de pago cambiado de 'Pendiente' a 'Pagado'"
```

### Ejemplo 3: Actualizar dirección
```php
$stmt = $conn->prepare("UPDATE orders SET shipping_address = ? WHERE id = ?");
$stmt->execute(['Nueva dirección 123', 15]);

// El trigger registrará: "Dirección de envío actualizada"
```

---

## 🆕 Actualizaciones Futuras

- [ ] Filtros por tipo de cambio
- [ ] Exportar historial a PDF
- [ ] Notificaciones por email en cambios críticos
- [ ] Dashboard de estadísticas de cambios
- [ ] Comparación de versiones
- [ ] Revertir cambios (undo)

---

## 📞 Soporte

Para preguntas o problemas:
1. Revisar los logs de MySQL: `SHOW ENGINE INNODB STATUS;`
2. Verificar errores PHP: `error_log()`
3. Consultar la documentación de triggers: [MySQL Triggers](https://dev.mysql.com/doc/refman/8.0/en/triggers.html)

---

**Última actualización**: 11 de Octubre, 2025  
**Versión**: 1.0.0  
**Compatible con**: MySQL 5.7+, MariaDB 10.2+, PHP 7.4+
