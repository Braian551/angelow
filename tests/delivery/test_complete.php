<?php
/**
 * Test final: Verifica que el sistema funciona correctamente
 * en diferentes escenarios
 */

session_start();
require_once __DIR__ . '/conexion.php';

echo "==============================================\n";
echo "TEST COMPLETO: Sistema de Historial de Órdenes\n";
echo "==============================================\n\n";

// Test 1: Verificar estructura de base de datos
echo "TEST 1: Verificar estructura de base de datos\n";
echo "----------------------------------------------\n";

$tests_passed = 0;
$tests_total = 0;

// Verificar tabla order_status_history
$tests_total++;
$stmt = $conn->query("SHOW TABLES LIKE 'order_status_history'");
if ($stmt->rowCount() > 0) {
    echo "✅ Tabla order_status_history existe\n";
    $tests_passed++;
} else {
    echo "❌ Tabla order_status_history NO existe\n";
}

// Verificar columna changed_by permite NULL
$tests_total++;
$stmt = $conn->query("DESCRIBE order_status_history");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
$changed_by_column = array_filter($columns, fn($c) => $c['Field'] === 'changed_by');
$changed_by_column = reset($changed_by_column);

if ($changed_by_column && $changed_by_column['Null'] === 'YES') {
    echo "✅ Columna changed_by permite NULL\n";
    $tests_passed++;
} else {
    echo "❌ Columna changed_by NO permite NULL\n";
}

// Verificar foreign key
$tests_total++;
$stmt = $conn->query("
    SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'angelow' 
    AND TABLE_NAME = 'order_status_history' 
    AND COLUMN_NAME = 'changed_by'
    AND REFERENCED_TABLE_NAME = 'users'
");
if ($stmt->rowCount() > 0) {
    echo "✅ Foreign key fk_order_history_user existe\n";
    $tests_passed++;
} else {
    echo "❌ Foreign key fk_order_history_user NO existe\n";
}

// Test 2: Verificar triggers
echo "\nTEST 2: Verificar triggers\n";
echo "----------------------------------------------\n";

$required_triggers = ['track_order_creation', 'track_order_changes_update'];
foreach ($required_triggers as $trigger_name) {
    $tests_total++;
    $stmt = $conn->query("
        SELECT TRIGGER_NAME 
        FROM information_schema.TRIGGERS 
        WHERE TRIGGER_SCHEMA = 'angelow' 
        AND TRIGGER_NAME = '$trigger_name'
    ");
    if ($stmt->rowCount() > 0) {
        echo "✅ Trigger $trigger_name existe\n";
        $tests_passed++;
    } else {
        echo "❌ Trigger $trigger_name NO existe\n";
    }
}

// Test 3: Verificar que existe un usuario admin
echo "\nTEST 3: Verificar usuario administrador\n";
echo "----------------------------------------------\n";

$tests_total++;
$stmt = $conn->query("SELECT id, name, role FROM users WHERE role = 'admin' LIMIT 1");
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    echo "✅ Usuario admin encontrado: {$admin['name']} (ID: {$admin['id']})\n";
    $tests_passed++;
    $_SESSION['user_id'] = $admin['id'];
} else {
    echo "❌ No hay usuario admin\n";
}

// Test 4: Simular actualización masiva (solo si tenemos órdenes)
echo "\nTEST 4: Simular actualización masiva\n";
echo "----------------------------------------------\n";

$tests_total++;
$stmt = $conn->query("SELECT id, order_number, status FROM orders LIMIT 1");
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if ($order && $admin) {
    try {
        // Establecer variables MySQL
        $conn->exec("SET @current_user_id = " . $conn->quote($admin['id']));
        $conn->exec("SET @current_user_name = " . $conn->quote($admin['name']));
        $conn->exec("SET @current_user_ip = '127.0.0.1 (test)'");
        
        // Guardar estado original
        $original_status = $order['status'];
        
        // Actualizar (sin cambiar realmente el estado, solo para probar el trigger)
        $new_status = $original_status === 'pending' ? 'processing' : 'pending';
        
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order['id']]);
        
        // Verificar que se creó el registro en historial
        $stmt = $conn->prepare("
            SELECT * FROM order_status_history 
            WHERE order_id = ? 
            AND change_type = 'status'
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$order['id']]);
        $history = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Revertir el cambio
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$original_status, $order['id']]);
        
        $conn->commit();
        
        if ($history) {
            echo "✅ Actualización masiva funciona correctamente\n";
            echo "   Orden: #{$order['order_number']}\n";
            echo "   Cambio: {$original_status} → {$new_status}\n";
            echo "   Registrado por: {$history['changed_by_name']} (ID: " . ($history['changed_by'] ?? 'NULL') . ")\n";
            $tests_passed++;
        } else {
            echo "❌ No se registró el cambio en el historial\n";
            $conn->rollBack();
        }
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo "❌ Error al simular actualización: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️  No hay órdenes para probar o no hay usuario admin\n";
    echo "   (Este test es opcional)\n";
}

// Test 5: Verificar que el historial permite changed_by NULL
echo "\nTEST 5: Inserción con changed_by NULL\n";
echo "----------------------------------------------\n";

$tests_total++;
if ($order) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO order_status_history 
            (order_id, changed_by, changed_by_name, change_type, field_changed, description, ip_address, created_at)
            VALUES (?, NULL, 'Test Sistema', 'other', 'test', 'Test de inserción con NULL', '127.0.0.1', NOW())
        ");
        $stmt->execute([$order['id']]);
        
        $insertedId = $conn->lastInsertId();
        
        // Limpiar el registro de prueba
        $stmt = $conn->prepare("DELETE FROM order_status_history WHERE id = ?");
        $stmt->execute([$insertedId]);
        
        echo "✅ Se puede insertar historial con changed_by = NULL\n";
        $tests_passed++;
    } catch (PDOException $e) {
        echo "❌ Error al insertar con NULL: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️  No hay órdenes para probar (Test opcional)\n";
}

// Resumen final
echo "\n==============================================\n";
echo "RESUMEN DE TESTS\n";
echo "==============================================\n";
echo "Tests ejecutados: $tests_total\n";
echo "Tests exitosos: $tests_passed\n";
echo "Tests fallidos: " . ($tests_total - $tests_passed) . "\n";

if ($tests_passed === $tests_total) {
    echo "\n✅ TODOS LOS TESTS PASARON\n";
    echo "==============================================\n";
    echo "El sistema está funcionando correctamente.\n";
    echo "Puedes usar la actualización masiva de órdenes\n";
    echo "sin problemas.\n";
    echo "==============================================\n";
    exit(0);
} else {
    $percentage = round(($tests_passed / $tests_total) * 100);
    echo "\n⚠️  ALGUNOS TESTS FALLARON ($percentage% exitosos)\n";
    echo "==============================================\n";
    echo "Revisa los errores arriba y aplica los fixes\n";
    echo "necesarios antes de usar el sistema.\n";
    echo "==============================================\n";
    exit(1);
}
