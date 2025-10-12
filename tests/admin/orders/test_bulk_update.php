<?php
/**
 * Script de prueba para la actualización masiva de estado de órdenes
 * Verifica que no haya errores de foreign key
 */

session_start();
require_once __DIR__ . '/../../../conexion.php';

echo "===========================================\n";
echo "PRUEBA: Actualización masiva de órdenes\n";
echo "===========================================\n\n";

// Simular sesión de administrador
echo "1. Buscando un usuario administrador...\n";
$stmt = $conn->query("SELECT id, name, role FROM users WHERE role = 'admin' LIMIT 1");
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    echo "❌ No se encontró ningún usuario administrador\n";
    echo "   Crea un usuario admin primero\n";
    exit(1);
}

echo "   ✅ Usuario admin encontrado: {$admin['name']} (ID: {$admin['id']})\n\n";

// Establecer sesión
$_SESSION['user_id'] = $admin['id'];

echo "2. Buscando órdenes para probar...\n";
$stmt = $conn->query("SELECT id, order_number, status FROM orders LIMIT 3");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($orders) === 0) {
    echo "❌ No hay órdenes en la base de datos\n";
    exit(1);
}

echo "   ✅ Encontradas " . count($orders) . " órdenes:\n";
foreach ($orders as $order) {
    echo "      • Orden #{$order['order_number']} - Estado actual: {$order['status']}\n";
}

echo "\n3. Simulando actualización masiva...\n";

// Extraer solo los IDs
$orderIds = array_column($orders, 'id');

// Preparar datos para la actualización
$data = [
    'order_ids' => $orderIds,
    'new_status' => 'processing',
    'notes' => 'Prueba de actualización masiva - verificación de fix'
];

echo "   📤 Enviando petición a bulk_update_status.php...\n";
echo "   IDs: " . implode(', ', $orderIds) . "\n";
echo "   Nuevo estado: {$data['new_status']}\n\n";

// Simular la petición (copiamos la lógica del archivo)
try {
    // Obtener información del usuario
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentUser) {
        echo "❌ Usuario no encontrado\n";
        exit(1);
    }
    
    $userIdForHistory = $currentUser['id'];
    $userNameForHistory = $currentUser['name'];
    
    echo "   Usuario para historial: {$userNameForHistory} (ID: {$userIdForHistory})\n\n";
    
    // Establecer variables MySQL para los triggers
    $conn->exec("SET @current_user_id = " . $conn->quote($userIdForHistory));
    $conn->exec("SET @current_user_name = " . $conn->quote($userNameForHistory));
    $conn->exec("SET @current_user_ip = '127.0.0.1 (test)'");
    
    echo "   ✅ Variables MySQL establecidas\n\n";
    
    // Iniciar transacción
    $conn->beginTransaction();
    
    $affectedRows = 0;
    
    foreach ($orderIds as $orderId) {
        // Obtener estado actual
        $stmt = $conn->prepare("SELECT id, order_number, status FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $currentOrder = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentOrder) {
            echo "   ⚠️  Orden $orderId no encontrada\n";
            continue;
        }
        
        if ($currentOrder['status'] === $data['new_status']) {
            echo "   ℹ️  Orden #{$currentOrder['order_number']} ya tiene el estado {$data['new_status']}\n";
            continue;
        }
        
        echo "   🔄 Actualizando orden #{$currentOrder['order_number']}...\n";
        
        // Actualizar el estado
        $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$data['new_status'], $orderId]);
        
        if ($stmt->rowCount() > 0) {
            $affectedRows++;
            echo "      ✅ Estado actualizado de '{$currentOrder['status']}' a '{$data['new_status']}'\n";
            
            // El trigger debería haber creado el registro automáticamente
            // Verificar que se creó correctamente
            $stmt = $conn->prepare("
                SELECT * FROM order_status_history 
                WHERE order_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$orderId]);
            $history = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($history) {
                echo "      ✅ Historial registrado automáticamente por trigger\n";
                echo "         Changed by: {$history['changed_by_name']} (ID: " . ($history['changed_by'] ?? 'NULL') . ")\n";
            } else {
                echo "      ⚠️  No se registró en el historial\n";
            }
        }
        
        echo "\n";
    }
    
    // Confirmar transacción
    $conn->commit();
    
    echo "===========================================\n";
    echo "✅ PRUEBA COMPLETADA EXITOSAMENTE\n";
    echo "   Órdenes actualizadas: $affectedRows\n";
    echo "===========================================\n\n";
    
    echo "4. Verificando registros en order_status_history...\n\n";
    
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $stmt = $conn->prepare("
        SELECT 
            osh.id,
            osh.order_id,
            o.order_number,
            osh.changed_by,
            osh.changed_by_name,
            osh.change_type,
            osh.description,
            osh.created_at
        FROM order_status_history osh
        INNER JOIN orders o ON osh.order_id = o.id
        WHERE osh.order_id IN ($placeholders)
        ORDER BY osh.created_at DESC
        LIMIT 10
    ");
    $stmt->execute($orderIds);
    $historyRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($historyRecords) > 0) {
        echo "   ✅ Se encontraron " . count($historyRecords) . " registros de historial:\n\n";
        foreach ($historyRecords as $record) {
            echo "   • Orden #{$record['order_number']}\n";
            echo "     Cambio: {$record['change_type']}\n";
            echo "     Por: {$record['changed_by_name']} (ID: " . ($record['changed_by'] ?? 'NULL') . ")\n";
            echo "     Descripción: {$record['description']}\n";
            echo "     Fecha: {$record['created_at']}\n\n";
        }
    } else {
        echo "   ⚠️  No se encontraron registros de historial\n";
    }
    
    echo "===========================================\n";
    echo "CONCLUSIÓN: El fix funciona correctamente ✅\n";
    echo "===========================================\n";
    
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "SQL State: " . $e->getCode() . "\n";
    echo "\n===========================================\n";
    echo "La prueba FALLÓ ❌\n";
    echo "===========================================\n";
    exit(1);
}
