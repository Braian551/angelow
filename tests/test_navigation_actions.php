<?php
/**
 * Script de Prueba Rápida - Sistema de Cancelación y Reportes
 * Ejecutar desde: php test_navigation_actions.php
 */

require_once __DIR__ . '/../config.php';

echo "===========================================\n";
echo "PRUEBA: Sistema de Cancelación y Reportes\n";
echo "===========================================\n\n";

// Test 1: Verificar estructura de BD
echo "TEST 1: Verificando estructura de base de datos...\n";
echo "---------------------------------------------------\n";

$tables_to_check = [
    'delivery_navigation_cancellations',
    'delivery_problem_reports'
];

foreach ($tables_to_check as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    $tableExists = $result ? $result->fetch(PDO::FETCH_NUM) : false;
    if ($tableExists) {
        echo "✅ Tabla '$table' existe\n";
        
        // Contar registros
        $count_result = $conn->query("SELECT COUNT(*) as total FROM $table");
        $count_row = $count_result ? $count_result->fetch(PDO::FETCH_ASSOC) : null;
        $count = $count_row ? (int)$count_row['total'] : 0;
        echo "   → Registros actuales: $count\n";
    } else {
        echo "❌ Tabla '$table' NO existe\n";
    }
}

echo "\n";

// Test 2: Verificar procedimientos almacenados
echo "TEST 2: Verificando procedimientos almacenados...\n";
echo "---------------------------------------------------\n";

$procedures = ['CancelNavigation', 'ReportProblem'];

foreach ($procedures as $proc) {
    $result = $conn->query("
        SELECT ROUTINE_NAME 
        FROM information_schema.ROUTINES 
        WHERE ROUTINE_TYPE = 'PROCEDURE' 
        AND ROUTINE_SCHEMA = 'angelow' 
        AND ROUTINE_NAME = '$proc'
    ");
    $procedureExists = $result ? $result->fetch(PDO::FETCH_ASSOC) : null;

    if ($procedureExists) {
        echo "✅ Procedimiento '$proc' existe\n";
    } else {
        echo "❌ Procedimiento '$proc' NO existe\n";
    }
}

echo "\n";

// Test 3: Verificar vista
echo "TEST 3: Verificando vista consolidada...\n";
echo "---------------------------------------------------\n";

$result = $conn->query("
    SELECT TABLE_NAME 
    FROM information_schema.VIEWS 
    WHERE TABLE_SCHEMA = 'angelow' 
    AND TABLE_NAME = 'v_navigation_issues'
");
$view = $result ? $result->fetch(PDO::FETCH_ASSOC) : null;

if ($view) {
    echo "✅ Vista 'v_navigation_issues' existe\n";
    
    // Probar consulta
    $test_result = $conn->query("SELECT COUNT(*) as total FROM v_navigation_issues");
    if ($test_result) {
        $total_row = $test_result->fetch(PDO::FETCH_ASSOC);
        $total = $total_row ? (int)$total_row['total'] : 0;
        echo "   → Total de issues: $total\n";
    }
} else {
    echo "❌ Vista 'v_navigation_issues' NO existe\n";
}

echo "\n";

// Test 4: Verificar triggers
echo "TEST 4: Verificando triggers...\n";
echo "---------------------------------------------------\n";

$result = $conn->query("
    SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE 
    FROM information_schema.TRIGGERS 
    WHERE TRIGGER_SCHEMA = 'angelow' 
    AND TRIGGER_NAME = 'after_problem_report_insert'
");
$trigger = $result ? $result->fetch(PDO::FETCH_ASSOC) : null;

if ($trigger) {
    echo "✅ Trigger 'after_problem_report_insert' existe\n";
    echo "   → Evento: {$trigger['EVENT_MANIPULATION']} en {$trigger['EVENT_OBJECT_TABLE']}\n";
} else {
    echo "❌ Trigger 'after_problem_report_insert' NO existe\n";
}

echo "\n";

// Test 5: Verificar archivos del sistema
echo "TEST 5: Verificando archivos del sistema...\n";
echo "---------------------------------------------------\n";

$files_to_check = [
    'delivery/api/navigation_actions.php' => 'API Backend',
    'delivery/modals/cancel_navigation_modal.php' => 'Modal Cancelación',
    'delivery/modals/report_problem_modal.php' => 'Modal Reportes',
    'uploads/problem_reports' => 'Directorio de Fotos',
    'uploads/problem_reports/.htaccess' => 'Protección htaccess'
];

foreach ($files_to_check as $path => $description) {
    $full_path = __DIR__ . '/../' . $path;
    
    if (is_dir($full_path)) {
        echo "✅ Directorio '$description' existe\n";
        echo "   → Ruta: $path\n";
        
        // Verificar permisos de escritura
        if (is_writable($full_path)) {
            echo "   → Permisos: Escritura ✓\n";
        } else {
            echo "   → Permisos: Sin escritura ⚠️\n";
        }
    } elseif (file_exists($full_path)) {
        echo "✅ Archivo '$description' existe\n";
        echo "   → Ruta: $path\n";
        echo "   → Tamaño: " . filesize($full_path) . " bytes\n";
    } else {
        echo "❌ '$description' NO existe\n";
        echo "   → Esperado en: $path\n";
    }
}

echo "\n";

// Test 6: Simular llamada a API (estructura)
echo "TEST 6: Verificando endpoints de API...\n";
echo "---------------------------------------------------\n";

$api_file = __DIR__ . '/../delivery/api/navigation_actions.php';

if (file_exists($api_file)) {
    $content = file_get_contents($api_file);
    
    $actions = ['cancel_navigation', 'report_problem', 'get_problem_types', 'get_cancellation_reasons'];
    
    foreach ($actions as $action) {
        if (strpos($content, "case '$action'") !== false) {
            echo "✅ Endpoint '$action' implementado\n";
        } else {
            echo "❌ Endpoint '$action' NO encontrado\n";
        }
    }
} else {
    echo "❌ Archivo API no encontrado\n";
}

echo "\n";

// Test 7: Verificar integración en navigation.js
echo "TEST 7: Verificando integración en JavaScript...\n";
echo "---------------------------------------------------\n";

$js_file = __DIR__ . '/../js/delivery/navigation.js';

if (file_exists($js_file)) {
    $content = file_get_contents($js_file);
    
    $functions = [
        'window.cancelNavigation' => 'Función de cancelación',
        'window.processCancellation' => 'Procesamiento de cancelación',
        'window.reportProblem' => 'Función de reporte',
        'window.submitProblemReport' => 'Envío de reporte'
    ];
    
    foreach ($functions as $func => $description) {
        if (strpos($content, $func) !== false) {
            echo "✅ $description implementada\n";
        } else {
            echo "❌ $description NO encontrada\n";
        }
    }
} else {
    echo "❌ Archivo navigation.js no encontrado\n";
}

echo "\n";

// Resumen final
echo "===========================================\n";
echo "RESUMEN DE PRUEBAS\n";
echo "===========================================\n";

$total_tests = 7;
echo "Total de tests ejecutados: $total_tests\n";
echo "\n";
echo "Para pruebas funcionales completas:\n";
echo "1. Iniciar navegación en el sistema\n";
echo "2. Hacer clic en 'Cancelar Navegación'\n";
echo "3. Verificar modal y envío de datos\n";
echo "4. Hacer clic en 'Reportar Problema'\n";
echo "5. Verificar modal, subida de foto y envío\n";
echo "\n";
echo "Consultas útiles para verificar datos:\n";
echo "----------------------------------------\n";
echo "SELECT * FROM delivery_navigation_cancellations ORDER BY created_at DESC LIMIT 5;\n";
echo "SELECT * FROM delivery_problem_reports ORDER BY created_at DESC LIMIT 5;\n";
echo "SELECT * FROM v_navigation_issues ORDER BY created_at DESC LIMIT 5;\n";
echo "\n";

$conn = null;
?>
