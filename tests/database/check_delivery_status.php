<?php
require_once __DIR__ . '/conexion.php';

echo "=== DIAGNÓSTICO DE ENTREGAS ===\n\n";

// Obtener el driver_id (ajusta este valor si es necesario)
$driverId = isset($argv[1]) ? $argv[1] : 1;

echo "Driver ID: $driverId\n\n";

try {
    // Ver todas las entregas del driver
    $stmt = $conn->prepare("
        SELECT 
            od.id,
            od.delivery_status,
            o.order_number,
            od.assigned_at,
            od.accepted_at,
            od.started_at
        FROM order_deliveries od
        INNER JOIN orders o ON od.order_id = o.id
        WHERE od.driver_id = ?
        ORDER BY od.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$driverId]);
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($deliveries)) {
        echo "❌ No hay entregas para este driver\n";
    } else {
        echo "✅ Entregas encontradas: " . count($deliveries) . "\n\n";
        
        foreach ($deliveries as $d) {
            echo "ID: {$d['id']}\n";
            echo "Orden: {$d['order_number']}\n";
            echo "Estado: {$d['delivery_status']}\n";
            echo "Asignada: " . ($d['assigned_at'] ?? 'No') . "\n";
            echo "Aceptada: " . ($d['accepted_at'] ?? 'No') . "\n";
            echo "Iniciada: " . ($d['started_at'] ?? 'No') . "\n";
            
            // Verificar si puede acceder a navegación
            $canNavigate = in_array($d['delivery_status'], ['driver_accepted', 'in_transit', 'arrived']);
            echo "¿Puede navegar?: " . ($canNavigate ? "✅ SÍ" : "❌ NO") . "\n";
            
            if ($canNavigate) {
                echo "URL: http://localhost/angelow/delivery/navigation.php?delivery_id={$d['id']}\n";
            }
            
            echo "---\n\n";
        }
    }
    
    // Ver entregas activas específicamente
    echo "=== ENTREGAS ACTIVAS (navegables) ===\n\n";
    $stmt = $conn->prepare("
        SELECT 
            od.id,
            od.delivery_status,
            o.order_number
        FROM order_deliveries od
        INNER JOIN orders o ON od.order_id = o.id
        WHERE od.driver_id = ?
        AND od.delivery_status IN ('driver_accepted', 'in_transit', 'arrived')
        ORDER BY od.started_at DESC, od.accepted_at DESC
    ");
    $stmt->execute([$driverId]);
    $active = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($active)) {
        echo "❌ No hay entregas activas\n";
        echo "Solución: Ve al dashboard y:\n";
        echo "1. Busca una orden disponible\n";
        echo "2. Click 'Quiero esta orden'\n";
        echo "3. Click 'Iniciar Recorrido'\n";
    } else {
        echo "✅ Entregas activas: " . count($active) . "\n\n";
        foreach ($active as $a) {
            echo "ID: {$a['id']} - {$a['order_number']} - {$a['delivery_status']}\n";
            echo "URL: http://localhost/angelow/delivery/navigation.php?delivery_id={$a['id']}\n\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
