<?php
// Test script para probar los stored procedures
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

echo "Probando stored procedures...\n";

// Test ReportProblem
echo "\n=== Test ReportProblem ===\n";
try {
    $stmt = $conn->prepare("CALL ReportProblem(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        9, // delivery_id
        '6862b7448112f', // driver_id
        'gps_error', // problem_type
        'Test Problem Title', // title
        'Test problem description', // description
        'medium', // severity
        14.123456, // latitude
        -87.654321, // longitude
        null, // photo_path
        '{"test": "data", "timestamp": "' . date('Y-m-d H:i:s') . '"}' // device_info
    ]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "ReportProblem resultado: " . json_encode($result) . "\n";
    echo "ReportProblem - OK\n";
} catch (Exception $e) {
    echo "ReportProblem - ERROR: " . $e->getMessage() . "\n";
}

// Test CancelNavigation
echo "\n=== Test CancelNavigation ===\n";
try {
    $stmt = $conn->prepare("CALL CancelNavigation(?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        9, // delivery_id
        '6862b7448112f', // driver_id
        'technical_issue', // reason
        'Test cancellation notes', // notes
        14.123456, // latitude
        -87.654321, // longitude
        '{"test": "data", "timestamp": "' . date('Y-m-d H:i:s') . '"}' // device_info
    ]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "CancelNavigation resultado: " . json_encode($result) . "\n";
    echo "CancelNavigation - OK\n";
} catch (Exception $e) {
    echo "CancelNavigation - ERROR: " . $e->getMessage() . "\n";
}

echo "\nPruebas completadas.\n";
?>