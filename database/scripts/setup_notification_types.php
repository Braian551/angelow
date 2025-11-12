<?php
/**
 * Script para crear los tipos de notificaciones básicos
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/conexion.php';
$pdo = $conn;

echo "Iniciando creación de tipos de notificaciones...\n\n";

try {
    // Limpiar tipos existentes
    $pdo->query("DELETE FROM notification_types");
    echo "Tipos de notificaciones anteriores eliminados.\n\n";

    // Tipos de notificaciones
    $types = [
        [
            'id' => 1,
            'name' => 'order',
            'description' => 'Notificaciones relacionadas con pedidos',
            'template' => 'Tu pedido #{order_id} ha cambiado de estado a: {status}',
            'is_active' => 1
        ],
        [
            'id' => 2,
            'name' => 'product',
            'description' => 'Notificaciones de productos (disponibilidad, nuevo stock)',
            'template' => 'El producto {product_name} está {status}',
            'is_active' => 1
        ],
        [
            'id' => 3,
            'name' => 'promotion',
            'description' => 'Ofertas y promociones especiales',
            'template' => 'Nueva promoción: {promotion_name}',
            'is_active' => 1
        ],
        [
            'id' => 4,
            'name' => 'account',
            'description' => 'Notificaciones de cuenta de usuario',
            'template' => 'Cambio en tu cuenta: {change_description}',
            'is_active' => 1
        ],
        [
            'id' => 5,
            'name' => 'system',
            'description' => 'Notificaciones del sistema',
            'template' => 'Mensaje del sistema: {message}',
            'is_active' => 1
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO notification_types (id, name, description, template, is_active)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($types as $type) {
        $stmt->execute([
            $type['id'],
            $type['name'],
            $type['description'],
            $type['template'],
            $type['is_active']
        ]);
        echo "✓ Tipo creado: {$type['name']} - {$type['description']}\n";
    }

    echo "\n===========================================\n";
    echo "✅ COMPLETADO: " . count($types) . " tipos de notificaciones creados\n";
    echo "===========================================\n\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
