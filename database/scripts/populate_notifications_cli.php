<?php
/**
 * Script CLI para poblar la base de datos con notificaciones de prueba
 * Ejecutar: php populate_notifications_cli.php
 */

// Configuración simple para CLI (sin $_SERVER)
define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/conexion.php';

// Usar el mismo nombre de variable que conexion.php
$pdo = $conn;

echo "Iniciando población de notificaciones de prueba...\n\n";

try {
    // Obtener un usuario de prueba (el primero disponible con rol user)
    $stmt = $pdo->query("SELECT id FROM users WHERE role IN ('user', 'customer') LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("Error: No se encontró ningún usuario en la base de datos.\n");
    }
    
    $user_id = $user['id'];
    echo "Usando usuario ID: $user_id\n\n";

    // Limpiar notificaciones existentes del usuario (opcional)
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
    $stmt->execute([$user_id]);
    echo "Notificaciones anteriores eliminadas.\n\n";

    // Notificaciones de ejemplo
    $notifications = [
        // Notificaciones de pedidos
        [
            'user_id' => $user_id,
            'type_id' => 1,
            'title' => 'Pedido Confirmado',
            'message' => 'Tu pedido #1024 ha sido confirmado y está siendo preparado para envío.',
            'related_entity_type' => 'order',
            'related_entity_id' => 1024,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'user_id' => $user_id,
            'type_id' => 1,
            'title' => 'Pedido en Camino',
            'message' => 'Tu pedido #1015 ha salido para entrega. Esperalo pronto.',
            'related_entity_type' => 'order',
            'related_entity_id' => 1015,
            'is_read' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'read_at' => date('Y-m-d H:i:s', strtotime('-1 day') + 3600)
        ],
        [
            'user_id' => $user_id,
            'type_id' => 1,
            'title' => 'Pedido Entregado',
            'message' => 'Tu pedido #1008 ha sido entregado exitosamente. ¡Gracias por tu compra!',
            'related_entity_type' => 'order',
            'related_entity_id' => 1008,
            'is_read' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'read_at' => date('Y-m-d H:i:s', strtotime('-3 days') + 7200)
        ],
        
        // Notificaciones de productos
        [
            'user_id' => $user_id,
            'type_id' => 2,
            'title' => 'Producto Disponible',
            'message' => '¡Buenas noticias! El producto "Vestido Rosa Princesa" que agregaste a tu wishlist ya está disponible.',
            'related_entity_type' => 'product',
            'related_entity_id' => 45,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))
        ],
        [
            'user_id' => $user_id,
            'type_id' => 2,
            'title' => 'Nuevo Stock',
            'message' => 'El producto "Pantalón Mezclilla Niño" ha vuelto a estar en stock.',
            'related_entity_type' => 'product',
            'related_entity_id' => 32,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ],
        
        // Notificaciones de promociones
        [
            'user_id' => $user_id,
            'type_id' => 3,
            'title' => '¡Oferta Especial del Fin de Semana!',
            'message' => 'Descuento del 30% en toda la colección primavera-verano. ¡No te lo pierdas!',
            'related_entity_type' => 'promotion',
            'related_entity_id' => 5,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))
        ],
        [
            'user_id' => $user_id,
            'type_id' => 3,
            'title' => 'Cupón de Bienvenida',
            'message' => 'Usa el cupón BIENVENIDO20 para obtener 20% de descuento en tu próxima compra.',
            'related_entity_type' => 'promotion',
            'related_entity_id' => 3,
            'is_read' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-7 days')),
            'read_at' => date('Y-m-d H:i:s', strtotime('-6 days'))
        ],
        
        // Notificaciones de cuenta
        [
            'user_id' => $user_id,
            'type_id' => 4,
            'title' => 'Perfil Actualizado',
            'message' => 'Tu información de perfil ha sido actualizada correctamente.',
            'related_entity_type' => 'account',
            'related_entity_id' => null,
            'is_read' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'read_at' => date('Y-m-d H:i:s', strtotime('-2 days') + 1800)
        ],
        [
            'user_id' => $user_id,
            'type_id' => 4,
            'title' => 'Nueva Dirección Agregada',
            'message' => 'Se ha agregado una nueva dirección de envío a tu cuenta.',
            'related_entity_type' => 'account',
            'related_entity_id' => null,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s', strtotime('-6 hours'))
        ],
        
        // Notificaciones del sistema
        [
            'user_id' => $user_id,
            'type_id' => 5,
            'title' => 'Actualización del Sistema',
            'message' => 'Hemos mejorado nuestra plataforma con nuevas funcionalidades. Descúbrelas ahora.',
            'related_entity_type' => 'system',
            'related_entity_id' => null,
            'is_read' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'read_at' => date('Y-m-d H:i:s', strtotime('-4 days'))
        ],
        [
            'user_id' => $user_id,
            'type_id' => 5,
            'title' => 'Bienvenido a Angelow',
            'message' => 'Gracias por registrarte. Explora nuestra colección de ropa infantil y descubre las mejores ofertas.',
            'related_entity_type' => 'system',
            'related_entity_id' => null,
            'is_read' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'read_at' => date('Y-m-d H:i:s', strtotime('-10 days') + 600)
        ]
    ];

    // Insertar notificaciones
    $stmt = $pdo->prepare("
        INSERT INTO notifications 
        (user_id, type_id, title, message, related_entity_type, related_entity_id, is_read, created_at, read_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $count = 0;
    foreach ($notifications as $notification) {
        $stmt->execute([
            $notification['user_id'],
            $notification['type_id'],
            $notification['title'],
            $notification['message'],
            $notification['related_entity_type'],
            $notification['related_entity_id'],
            $notification['is_read'],
            $notification['created_at'],
            $notification['read_at'] ?? null
        ]);
        $count++;
        echo "✓ Notificación creada: {$notification['title']}\n";
    }

    echo "\n===========================================\n";
    echo "✅ COMPLETADO: $count notificaciones creadas exitosamente\n";
    echo "===========================================\n\n";
    echo "Estadísticas:\n";
    echo "- Total notificaciones: $count\n";
    
    // Contar por estado
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $unread = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 1");
    $stmt->execute([$user_id]);
    $read = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "- No leídas: $unread\n";
    echo "- Leídas: $read\n\n";
    
    // Contar por tipo
    echo "Por tipo:\n";
    $stmt = $pdo->prepare("
        SELECT related_entity_type, COUNT(*) as count 
        FROM notifications 
        WHERE user_id = ? 
        GROUP BY related_entity_type
    ");
    $stmt->execute([$user_id]);
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($types as $type) {
        echo "  - {$type['related_entity_type']}: {$type['count']}\n";
    }
    
    echo "\n";
    echo "Accede a: http://localhost/angelow/users/notifications.php\n";
    echo "Para ver las notificaciones en el sistema.\n\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
