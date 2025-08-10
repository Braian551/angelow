<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

// Tiempo en días después del cual se considera inactivo un carrito
$days_inactive = 30;

try {
    // 1. Primero obtenemos los carritos a eliminar para registro
    $stmt = $conn->prepare("
        SELECT c.id, c.user_id, c.session_id, c.updated_at 
        FROM carts c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE (c.updated_at < DATE_SUB(NOW(), INTERVAL ? DAY))
        AND (u.id IS NULL OR u.role = 'customer')
    ");
    $stmt->execute([$days_inactive]);
    $old_carts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Luego eliminamos los items del carrito
    $stmt = $conn->prepare("
        DELETE ci FROM cart_items ci
        JOIN carts c ON ci.cart_id = c.id
        LEFT JOIN users u ON c.user_id = u.id
        WHERE (c.updated_at < DATE_SUB(NOW(), INTERVAL ? DAY))
        AND (u.id IS NULL OR u.role = 'customer')
    ");
    $stmt->execute([$days_inactive]);

    // 3. Finalmente eliminamos los carritos
    $stmt = $conn->prepare("
        DELETE c FROM carts c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE (c.updated_at < DATE_SUB(NOW(), INTERVAL ? DAY))
        AND (u.id IS NULL OR u.role = 'customer')
    ");
    $stmt->execute([$days_inactive]);

    // Registrar la acción
    error_log("[" . date('Y-m-d H:i:s') . "] Carritos limpiados: " . count($old_carts));
    
} catch (PDOException $e) {
    error_log("Error al limpiar carritos antiguos: " . $e->getMessage());
}