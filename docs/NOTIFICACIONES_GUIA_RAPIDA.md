# Sistema de Notificaciones - Guía Rápida

## 🚀 Inicio Rápido (5 minutos)

### 1. Configurar Base de Datos
```bash
cd c:\laragon\www\angelow\database\scripts
php setup_notification_types.php
php populate_notifications_cli.php
```

### 2. Acceder al Sistema
1. Abrir navegador
2. Ir a: `http://localhost/angelow/users/notifications.php`
3. Iniciar sesión con un usuario
4. ¡Listo! Deberías ver 11 notificaciones de ejemplo

---

## 📋 Archivos Principales

```
users/notifications.php          → Página principal
css/user/notifications.css       → Estilos
users/api/*.php                  → API endpoints
```

---

## 💻 Crear Notificación (Código)

### Notificación Simple
```php
$stmt = $conn->prepare("
    INSERT INTO notifications 
    (user_id, type_id, title, message, related_entity_type, related_entity_id)
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $user_id,           // ID del usuario
    1,                  // type_id: 1=order, 2=product, 3=promotion, 4=account, 5=system
    'Título',           // Título corto
    'Mensaje completo', // Mensaje largo
    'order',            // Tipo: order|product|promotion|account|system
    1024               // ID de la entidad relacionada (null si no aplica)
]);
```

### Ejemplo: Notificación de Pedido
```php
// Cuando se confirma un pedido
function createOrderNotification($user_id, $order_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (user_id, type_id, title, message, related_entity_type, related_entity_id)
        VALUES (?, 1, ?, ?, 'order', ?)
    ");
    
    $stmt->execute([
        $user_id,
        'Pedido Confirmado',
        "Tu pedido #{$order_id} ha sido confirmado y está siendo preparado para envío.",
        $order_id
    ]);
}
```

---

## 🎨 Tipos de Notificaciones

| ID | Tipo       | Descripción                    | Color  | Icono           |
|----|------------|--------------------------------|--------|-----------------|
| 1  | order      | Pedidos (confirmado, enviado)  | Azul   | shopping-cart   |
| 2  | product    | Productos (stock, disponible)  | Naranja| tag             |
| 3  | promotion  | Ofertas y promociones          | Rosa   | gift            |
| 4  | account    | Cambios en cuenta              | Púrpura| user            |
| 5  | system     | Mensajes del sistema           | Gris   | cog             |

---

## 🔌 API Endpoints

### Marcar como Leída
```javascript
fetch('/users/api/mark_notification_read.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({notification_id: 123})
});
```

### Marcar Todas como Leídas
```javascript
fetch('/users/api/mark_all_read.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'}
});
```

### Eliminar Notificación
```javascript
fetch('/users/api/delete_notification.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({notification_id: 123})
});
```

### Obtener Conteo No Leídas
```javascript
fetch('/users/api/get_unread_count.php')
    .then(r => r.json())
    .then(data => console.log(data.count));
```

---

## 🎯 Casos de Uso Comunes

### 1. Notificar Cambio de Estado de Pedido
```php
// En admin/orders.php o donde actualices pedidos
function notifyOrderStatusChange($order_id, $new_status, $user_id) {
    global $conn;
    
    $messages = [
        'confirmed' => 'ha sido confirmado y está siendo preparado.',
        'shipped' => 'ha salido para entrega. Esperalo pronto.',
        'delivered' => 'ha sido entregado exitosamente. ¡Gracias!',
        'cancelled' => 'ha sido cancelado.'
    ];
    
    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (user_id, type_id, title, message, related_entity_type, related_entity_id)
        VALUES (?, 1, ?, ?, 'order', ?)
    ");
    
    $stmt->execute([
        $user_id,
        "Pedido #{$order_id}",
        "Tu pedido #{$order_id} " . $messages[$new_status],
        $order_id
    ]);
}
```

### 2. Alerta de Producto en Wishlist Disponible
```php
// Cuando un producto vuelve a stock
function alertWishlistUsers($product_id, $product_name) {
    global $conn;
    
    // Obtener usuarios con producto en wishlist
    $stmt = $conn->prepare("SELECT DISTINCT user_id FROM wishlist WHERE product_id = ?");
    $stmt->execute([$product_id]);
    
    $stmt_notify = $conn->prepare("
        INSERT INTO notifications 
        (user_id, type_id, title, message, related_entity_type, related_entity_id)
        VALUES (?, 2, 'Producto Disponible', ?, 'product', ?)
    ");
    
    while ($row = $stmt->fetch()) {
        $stmt_notify->execute([
            $row['user_id'],
            "¡Buenas noticias! El producto \"{$product_name}\" ya está disponible.",
            $product_id
        ]);
    }
}
```

### 3. Promoción para Todos los Usuarios
```php
function broadcastPromotion($title, $message, $promo_id = null) {
    global $conn;
    
    // Obtener todos los usuarios activos
    $users = $conn->query("SELECT id FROM users WHERE role IN ('user', 'customer')")->fetchAll();
    
    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (user_id, type_id, title, message, related_entity_type, related_entity_id)
        VALUES (?, 3, ?, ?, 'promotion', ?)
    ");
    
    foreach ($users as $user) {
        $stmt->execute([$user['id'], $title, $message, $promo_id]);
    }
}
```

---

## 🐛 Troubleshooting

| Error | Solución |
|-------|----------|
| "No autorizado" | Usuario no logueado. Verificar sesión |
| Tabla no existe | Ejecutar angelow.sql |
| Foreign key error | Ejecutar setup_notification_types.php |
| Estilos no se ven | Verificar ruta de notifications.css |
| AJAX no funciona | Verificar console.log() del navegador |

---

## 📱 URLs Importantes

- **Página principal**: `/users/notifications.php`
- **API Base**: `/users/api/`
- **Estilos**: `/css/user/notifications.css`
- **Documentación completa**: `/docs/NOTIFICACIONES.md`

---

## ✅ Checklist de Implementación

- [x] Crear tipos de notificaciones (setup_notification_types.php)
- [x] Poblar datos de prueba (populate_notifications_cli.php)
- [x] Página principal funcionando
- [x] API endpoints operativos
- [x] Estilos aplicados
- [ ] Integrar con sistema de pedidos (tu turno)
- [ ] Integrar con wishlist (tu turno)
- [ ] Agregar badge en menú lateral (futuro)
- [ ] Notificaciones en tiempo real (futuro)

---

## 💡 Tips

1. **Siempre verifica user_id**: Las notificaciones son privadas por usuario
2. **Usa type_id correcto**: 1=order, 2=product, 3=promotion, 4=account, 5=system
3. **related_entity_id puede ser NULL**: Para notificaciones que no referencian entidades
4. **is_read empieza en 0**: Las notificaciones son no leídas por defecto
5. **Timestamps automáticos**: created_at se asigna automáticamente

---

## 🎓 Próximos Pasos

1. ✅ **Sistema básico funcionando** (completado)
2. 🔄 **Integrar con pedidos** → Crear notificaciones cuando cambie estado
3. 🔄 **Integrar con wishlist** → Alertar cuando productos estén disponibles
4. 🔜 **Badge en menú** → Mostrar conteo de no leídas
5. 🔜 **Notificaciones push** → WebSockets/SSE para tiempo real

---

**¿Dudas?** Consulta `/docs/NOTIFICACIONES.md` para documentación completa.
