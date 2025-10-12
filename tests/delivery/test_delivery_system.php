<?php
/**
 * Test del Sistema de Entregas
 * Verifica que todas las funcionalidades estén operativas
 */

// Definir variables para modo CLI
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/angelow/';

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

// Colores para output
function colorize($text, $status) {
    $colors = [
        'success' => "\033[32m", // Verde
        'error' => "\033[31m",   // Rojo
        'info' => "\033[36m",    // Cyan
        'warning' => "\033[33m", // Amarillo
        'reset' => "\033[0m"
    ];
    return $colors[$status] . $text . $colors['reset'];
}

echo "\n" . colorize("=== TEST DEL SISTEMA DE ENTREGAS TIPO DIDI ===", 'info') . "\n\n";

$tests_passed = 0;
$tests_failed = 0;

// ============================================
// TEST 1: Verificar Tablas
// ============================================
echo colorize("[TEST 1] Verificando tablas...", 'info') . "\n";
try {
    $required_tables = ['order_deliveries', 'delivery_status_history', 'driver_statistics'];
    foreach ($required_tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "  ✓ Tabla '$table' existe\n";
            $tests_passed++;
        } else {
            echo colorize("  ✗ Tabla '$table' NO existe", 'error') . "\n";
            $tests_failed++;
        }
    }
} catch (Exception $e) {
    echo colorize("  ✗ Error: " . $e->getMessage(), 'error') . "\n";
    $tests_failed++;
}

echo "\n";

// ============================================
// TEST 2: Verificar Triggers
// ============================================
echo colorize("[TEST 2] Verificando triggers...", 'info') . "\n";
try {
    $stmt = $conn->query("SHOW TRIGGERS FROM angelow WHERE `Table` = 'order_deliveries'");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($triggers) >= 2) {
        echo "  ✓ Triggers encontrados: " . count($triggers) . "\n";
        foreach ($triggers as $trigger) {
            echo "    - " . $trigger['Trigger'] . "\n";
        }
        $tests_passed++;
    } else {
        echo colorize("  ✗ No se encontraron suficientes triggers", 'error') . "\n";
        $tests_failed++;
    }
} catch (Exception $e) {
    echo colorize("  ✗ Error: " . $e->getMessage(), 'error') . "\n";
    $tests_failed++;
}

echo "\n";

// ============================================
// TEST 3: Verificar Procedimientos
// ============================================
echo colorize("[TEST 3] Verificando procedimientos almacenados...", 'info') . "\n";
try {
    $required_procedures = [
        'AssignOrderToDriver',
        'DriverAcceptOrder',
        'DriverStartTrip',
        'DriverRejectOrder',
        'CompleteDelivery'
    ];
    
    foreach ($required_procedures as $procedure) {
        $stmt = $conn->query("SHOW PROCEDURE STATUS WHERE Db = 'angelow' AND Name = '$procedure'");
        if ($stmt->rowCount() > 0) {
            echo "  ✓ Procedimiento '$procedure' existe\n";
            $tests_passed++;
        } else {
            echo colorize("  ✗ Procedimiento '$procedure' NO existe", 'error') . "\n";
            $tests_failed++;
        }
    }
} catch (Exception $e) {
    echo colorize("  ✗ Error: " . $e->getMessage(), 'error') . "\n";
    $tests_failed++;
}

echo "\n";

// ============================================
// TEST 4: Verificar Vistas
// ============================================
echo colorize("[TEST 4] Verificando vistas...", 'info') . "\n";
try {
    $required_views = [
        'v_orders_awaiting_driver',
        'v_active_deliveries_by_driver',
        'v_driver_rankings'
    ];
    
    foreach ($required_views as $view) {
        $stmt = $conn->query("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_angelow = '$view'");
        if ($stmt->rowCount() > 0) {
            echo "  ✓ Vista '$view' existe\n";
            $tests_passed++;
        } else {
            echo colorize("  ✗ Vista '$view' NO existe", 'error') . "\n";
            $tests_failed++;
        }
    }
} catch (Exception $e) {
    echo colorize("  ✗ Error: " . $e->getMessage(), 'error') . "\n";
    $tests_failed++;
}

echo "\n";

// ============================================
// TEST 5: Verificar Usuario Transportista
// ============================================
echo colorize("[TEST 5] Verificando usuarios transportistas...", 'info') . "\n";
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'delivery'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "  ✓ Usuarios transportistas encontrados: " . $result['count'] . "\n";
        $tests_passed++;
    } else {
        echo colorize("  ⚠ No hay usuarios con rol 'delivery'", 'warning') . "\n";
        echo "    Ejecuta: INSERT INTO users (name, email, password, phone, role) VALUES ('Test Driver', 'driver@test.com', '\$2y\$10\$...', '999999999', 'delivery');\n";
        $tests_failed++;
    }
} catch (Exception $e) {
    echo colorize("  ✗ Error: " . $e->getMessage(), 'error') . "\n";
    $tests_failed++;
}

echo "\n";

// ============================================
// TEST 6: Verificar Archivo API
// ============================================
echo colorize("[TEST 6] Verificando archivos del sistema...", 'info') . "\n";
try {
    $required_files = [
        __DIR__ . '/../../delivery/delivery_actions.php',
        __DIR__ . '/../../delivery/dashboarddeli.php'
    ];
    
    foreach ($required_files as $file) {
        if (file_exists($file)) {
            echo "  ✓ Archivo existe: " . basename($file) . "\n";
            $tests_passed++;
        } else {
            echo colorize("  ✗ Archivo NO existe: " . basename($file), 'error') . "\n";
            $tests_failed++;
        }
    }
} catch (Exception $e) {
    echo colorize("  ✗ Error: " . $e->getMessage(), 'error') . "\n";
    $tests_failed++;
}

echo "\n";

// ============================================
// TEST 7: Prueba de Integración (Si hay datos)
// ============================================
echo colorize("[TEST 7] Prueba de integración...", 'info') . "\n";
try {
    // Verificar si hay órdenes de prueba
    $stmt = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status IN ('processing', 'shipped')");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "  ✓ Hay " . $result['count'] . " órdenes disponibles para pruebas\n";
        $tests_passed++;
    } else {
        echo colorize("  ⚠ No hay órdenes de prueba disponibles", 'warning') . "\n";
        echo "    Crea una orden con estado 'processing' o 'shipped' para probar\n";
    }
    
    // Verificar entregas activas
    $stmt = $conn->query("SELECT COUNT(*) as count FROM order_deliveries");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "  ℹ Registros en order_deliveries: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo colorize("  ✗ Error: " . $e->getMessage(), 'error') . "\n";
    $tests_failed++;
}

echo "\n";

// ============================================
// RESUMEN
// ============================================
echo colorize("=== RESUMEN DE PRUEBAS ===", 'info') . "\n";
echo colorize("Tests exitosos: $tests_passed", 'success') . "\n";
if ($tests_failed > 0) {
    echo colorize("Tests fallidos: $tests_failed", 'error') . "\n";
    echo "\n" . colorize("⚠ Hay errores que necesitan atención", 'warning') . "\n";
} else {
    echo colorize("Tests fallidos: 0", 'success') . "\n";
    echo "\n" . colorize("✓ Todos los tests pasaron correctamente!", 'success') . "\n";
}

$total_tests = $tests_passed + $tests_failed;
$percentage = $total_tests > 0 ? round(($tests_passed / $total_tests) * 100, 2) : 0;
echo colorize("Porcentaje de éxito: $percentage%", $percentage == 100 ? 'success' : 'warning') . "\n";

echo "\n" . colorize("=== FIN DE LAS PRUEBAS ===", 'info') . "\n\n";

// Retornar código de salida
exit($tests_failed > 0 ? 1 : 0);
