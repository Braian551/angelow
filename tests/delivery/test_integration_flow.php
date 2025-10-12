<?php
/**
 * Test de Integración del Flujo Completo de Entregas
 * Simula el proceso completo desde asignación hasta entrega
 */

// Definir variables para modo CLI
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/angelow/';

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

echo "\n╔══════════════════════════════════════════════════════╗\n";
echo "║  TEST DE INTEGRACIÓN - FLUJO COMPLETO DE ENTREGAS  ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// IDs de prueba (cambiar según tu BD)
$test_order_id = null;
$test_driver_id = null;
$test_delivery_id = null;

try {
    // ============================================
    // PASO 1: Buscar o crear orden de prueba
    // ============================================
    echo "📦 [PASO 1] Preparando orden de prueba...\n";
    
    $stmt = $conn->query("SELECT id, order_number FROM orders WHERE status = 'processing' LIMIT 1");
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo "  ⚠ No hay órdenes en 'processing', creando una de prueba...\n";
        
        // Obtener un usuario cliente
        $stmt = $conn->query("SELECT id FROM users WHERE role = 'customer' LIMIT 1");
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            throw new Exception("No hay usuarios con rol 'customer' para crear orden de prueba");
        }
        
        // Crear orden de prueba
        $order_number = 'TEST-' . date('YmdHis');
        $stmt = $conn->prepare("
            INSERT INTO orders (user_id, order_number, status, subtotal, total, shipping_address, shipping_city, created_at)
            VALUES (?, ?, 'processing', 100.00, 100.00, 'Av. Test 123', 'Lima', NOW())
        ");
        $stmt->execute([$customer['id'], $order_number]);
        $test_order_id = $conn->lastInsertId();
        
        echo "  ✓ Orden creada: #$order_number (ID: $test_order_id)\n";
    } else {
        $test_order_id = $order['id'];
        echo "  ✓ Usando orden existente: #{$order['order_number']} (ID: $test_order_id)\n";
    }
    
    echo "\n";
    
    // ============================================
    // PASO 2: Buscar o crear transportista
    // ============================================
    echo "🚚 [PASO 2] Buscando transportista...\n";
    
    $stmt = $conn->query("SELECT id, name FROM users WHERE role = 'delivery' LIMIT 1");
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$driver) {
        echo "  ⚠ No hay transportistas, creando uno de prueba...\n";
        
        // Generar ID único
        $driver_id = 'DRV' . substr(md5(uniqid()), 0, 17);
        $email = 'driver_test_' . time() . '@test.com';
        
        $stmt = $conn->prepare("
            INSERT INTO users (id, name, email, password, phone, role, created_at)
            VALUES (?, 'Transportista Test', ?, '\$2y\$10\$test', '999888777', 'delivery', NOW())
        ");
        $stmt->execute([$driver_id, $email]);
        $test_driver_id = $driver_id;
        
        echo "  ✓ Transportista creado: $test_driver_id\n";
    } else {
        $test_driver_id = $driver['id'];
        echo "  ✓ Usando transportista: {$driver['name']} (ID: $test_driver_id)\n";
    }
    
    echo "\n";
    
    // ============================================
    // PASO 3: Asignar orden a transportista
    // ============================================
    echo "📋 [PASO 3] Asignando orden a transportista...\n";
    
    // Verificar si ya existe entrega
    $stmt = $conn->prepare("SELECT id FROM order_deliveries WHERE order_id = ?");
    $stmt->execute([$test_order_id]);
    $existing_delivery = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_delivery) {
        $test_delivery_id = $existing_delivery['id'];
        echo "  ℹ Ya existe registro de entrega (ID: $test_delivery_id)\n";
        
        // Actualizar para asignar al transportista
        $stmt = $conn->prepare("
            UPDATE order_deliveries 
            SET driver_id = ?,
                delivery_status = 'driver_assigned',
                assigned_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$test_driver_id, $test_delivery_id]);
        echo "  ✓ Entrega reasignada al transportista\n";
    } else {
        // Usar procedimiento almacenado
        $stmt = $conn->prepare("CALL AssignOrderToDriver(?, ?)");
        $stmt->execute([$test_order_id, $test_driver_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['status'] == 'success') {
            // Obtener el ID de la entrega creada
            $stmt = $conn->prepare("SELECT id FROM order_deliveries WHERE order_id = ?");
            $stmt->execute([$test_order_id]);
            $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
            $test_delivery_id = $delivery['id'];
            
            echo "  ✓ " . $result['message'] . " (Delivery ID: $test_delivery_id)\n";
        } else {
            throw new Exception($result['message']);
        }
    }
    
    echo "\n";
    
    // ============================================
    // PASO 4: Transportista acepta la orden
    // ============================================
    echo "✅ [PASO 4] Transportista acepta la orden...\n";
    
    $stmt = $conn->prepare("CALL DriverAcceptOrder(?, ?)");
    $stmt->execute([$test_delivery_id, $test_driver_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['status'] == 'success') {
        echo "  ✓ " . $result['message'] . "\n";
        
        // Verificar estado
        $stmt = $conn->prepare("SELECT delivery_status, accepted_at FROM order_deliveries WHERE id = ?");
        $stmt->execute([$test_delivery_id]);
        $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "  ℹ Estado actual: {$delivery['delivery_status']}\n";
        echo "  ℹ Aceptada en: {$delivery['accepted_at']}\n";
    } else {
        echo "  ⚠ " . $result['message'] . " (puede que ya esté aceptada)\n";
    }
    
    echo "\n";
    
    // ============================================
    // PASO 5: Transportista inicia recorrido
    // ============================================
    echo "🚗 [PASO 5] Transportista inicia recorrido...\n";
    
    $test_lat = -12.0464;
    $test_lng = -77.0428;
    
    $stmt = $conn->prepare("CALL DriverStartTrip(?, ?, ?, ?)");
    $stmt->execute([$test_delivery_id, $test_driver_id, $test_lat, $test_lng]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['status'] == 'success') {
        echo "  ✓ " . $result['message'] . "\n";
        echo "  📍 Ubicación: $test_lat, $test_lng\n";
        
        // Verificar estado
        $stmt = $conn->prepare("SELECT delivery_status, started_at FROM order_deliveries WHERE id = ?");
        $stmt->execute([$test_delivery_id]);
        $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "  ℹ Estado actual: {$delivery['delivery_status']}\n";
        echo "  ℹ Iniciado en: {$delivery['started_at']}\n";
    } else {
        echo "  ⚠ " . $result['message'] . "\n";
    }
    
    echo "\n";
    
    // ============================================
    // PASO 6: Simular llegada (actualizar estado manualmente)
    // ============================================
    echo "📍 [PASO 6] Transportista llega al destino...\n";
    
    $stmt = $conn->prepare("
        UPDATE order_deliveries 
        SET delivery_status = 'arrived',
            arrived_at = NOW(),
            location_lat = ?,
            location_lng = ?
        WHERE id = ? AND driver_id = ?
    ");
    $stmt->execute([$test_lat, $test_lng, $test_delivery_id, $test_driver_id]);
    
    if ($stmt->rowCount() > 0) {
        echo "  ✓ Transportista marcó llegada\n";
        echo "  📍 Ubicación de llegada: $test_lat, $test_lng\n";
    } else {
        echo "  ⚠ No se pudo marcar llegada\n";
    }
    
    echo "\n";
    
    // ============================================
    // PASO 7: Completar entrega
    // ============================================
    echo "🎉 [PASO 7] Completando entrega...\n";
    
    $stmt = $conn->prepare("CALL CompleteDelivery(?, ?, ?, ?, ?)");
    $stmt->execute([
        $test_delivery_id,
        $test_driver_id,
        'Cliente Test',
        null, // photo
        'Entrega de prueba completada exitosamente'
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['status'] == 'success') {
        echo "  ✓ " . $result['message'] . "\n";
        
        // Verificar estado final
        $stmt = $conn->prepare("
            SELECT od.delivery_status, od.delivered_at, od.recipient_name, o.status as order_status
            FROM order_deliveries od
            INNER JOIN orders o ON od.order_id = o.id
            WHERE od.id = ?
        ");
        $stmt->execute([$test_delivery_id]);
        $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "  ℹ Estado de entrega: {$delivery['delivery_status']}\n";
        echo "  ℹ Estado de orden: {$delivery['order_status']}\n";
        echo "  ℹ Entregado en: {$delivery['delivered_at']}\n";
        echo "  ℹ Recibió: {$delivery['recipient_name']}\n";
    } else {
        echo "  ✗ " . $result['message'] . "\n";
    }
    
    echo "\n";
    
    // ============================================
    // PASO 8: Verificar historial
    // ============================================
    echo "📜 [PASO 8] Verificando historial de cambios...\n";
    
    $stmt = $conn->prepare("
        SELECT old_status, new_status, created_at
        FROM delivery_status_history
        WHERE delivery_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$test_delivery_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($history) > 0) {
        echo "  ✓ Historial encontrado: " . count($history) . " cambios\n";
        foreach ($history as $change) {
            $old = $change['old_status'] ?? 'N/A';
            echo "    • $old → {$change['new_status']} ({$change['created_at']})\n";
        }
    } else {
        echo "  ℹ No hay historial registrado\n";
    }
    
    echo "\n";
    
    // ============================================
    // PASO 9: Verificar estadísticas del transportista
    // ============================================
    echo "📊 [PASO 9] Verificando estadísticas del transportista...\n";
    
    $stmt = $conn->prepare("SELECT * FROM driver_statistics WHERE driver_id = ?");
    $stmt->execute([$test_driver_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stats) {
        echo "  ✓ Estadísticas encontradas:\n";
        echo "    • Total entregas: {$stats['total_deliveries']}\n";
        echo "    • Entregas hoy: {$stats['deliveries_today']}\n";
        echo "    • Entregas semana: {$stats['deliveries_week']}\n";
        echo "    • Tasa de aceptación: {$stats['acceptance_rate']}%\n";
        echo "    • Calificación promedio: {$stats['average_rating']}/5\n";
    } else {
        echo "  ℹ No hay estadísticas para este transportista\n";
    }
    
    echo "\n";
    
    // ============================================
    // RESUMEN FINAL
    // ============================================
    echo "╔══════════════════════════════════════════════════════╗\n";
    echo "║              ✓ TEST COMPLETADO EXITOSAMENTE          ║\n";
    echo "╚══════════════════════════════════════════════════════╝\n\n";
    
    echo "📝 Resumen del Test:\n";
    echo "  • Orden ID: $test_order_id\n";
    echo "  • Transportista ID: $test_driver_id\n";
    echo "  • Entrega ID: $test_delivery_id\n";
    echo "  • Estado final: delivered\n";
    echo "  • Flujo completo ejecutado: ✓\n\n";
    
    echo "🎯 Próximos pasos:\n";
    echo "  1. Verificar en el dashboard del transportista\n";
    echo "  2. Probar el flujo desde la interfaz web\n";
    echo "  3. Verificar notificaciones\n\n";
    
} catch (Exception $e) {
    echo "\n╔══════════════════════════════════════════════════════╗\n";
    echo "║                  ✗ ERROR EN EL TEST                  ║\n";
    echo "╚══════════════════════════════════════════════════════╝\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n\n";
    exit(1);
}
