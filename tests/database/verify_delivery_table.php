<?php
require_once __DIR__ . '/conexion.php';

echo "=== VERIFICACIÓN DE TABLA order_deliveries ===\n\n";

try {
    // Obtener estructura de la tabla
    $stmt = $conn->query("DESCRIBE order_deliveries");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columnas encontradas:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) {$column['Null']} {$column['Key']}\n";
    }
    
    echo "\n=== VERIFICACIÓN DE COLUMNAS CRÍTICAS ===\n\n";
    
    $criticalColumns = [
        'current_lat',
        'current_lng',
        'destination_lat',
        'destination_lng',
        'started_at',
        'accepted_at',
        'arrived_at',
        'delivered_at'
    ];
    
    $existingColumns = array_column($columns, 'Field');
    
    foreach ($criticalColumns as $col) {
        if (in_array($col, $existingColumns)) {
            echo "✅ $col - EXISTE\n";
        } else {
            echo "❌ $col - NO EXISTE\n";
        }
    }
    
    echo "\n=== VERIFICACIÓN DE ÓRDENES ===\n\n";
    
    // Ver órdenes disponibles
    $stmt = $conn->query("
        SELECT COUNT(*) as count
        FROM orders o
        WHERE o.status = 'shipped'
        AND o.payment_status = 'paid'
        AND NOT EXISTS (
            SELECT 1 FROM order_deliveries od 
            WHERE od.order_id = o.id 
            AND od.delivery_status NOT IN ('rejected', 'cancelled', 'failed')
        )
    ");
    $available = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Órdenes disponibles para asignar: {$available['count']}\n";
    
    // Ver entregas activas
    $stmt = $conn->query("
        SELECT COUNT(*) as count
        FROM order_deliveries
        WHERE delivery_status IN ('driver_assigned', 'driver_accepted', 'in_transit', 'arrived')
    ");
    $active = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Entregas activas: {$active['count']}\n";
    
    echo "\n✅ Verificación completada exitosamente\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
