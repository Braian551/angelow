<?php
// Archivo de diagnóstico para identificar problemas con la exportación de PDF

session_start();

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Diagnóstico del Sistema de Exportación PDF</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
    hr { margin: 20px 0; }
</style>";

// 1. Verificar PHP
echo "<h3>1. Versión de PHP</h3>";
echo "<p class='success'>✓ PHP " . PHP_VERSION . "</p>";

// 2. Verificar archivos requeridos
echo "<h3>2. Archivos Requeridos</h3>";

$files = [
    'config.php' => __DIR__ . '/../../config.php',
    'conexion.php' => __DIR__ . '/../../conexion.php',
    'vendor/autoload.php' => __DIR__ . '/../../vendor/autoload.php',
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        echo "<p class='success'>✓ $name existe</p>";
    } else {
        echo "<p class='error'>✗ $name NO EXISTE en: $path</p>";
    }
}

// 3. Verificar Composer
echo "<h3>3. Dependencias de Composer</h3>";
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
    
    // Verificar TCPDF
    if (class_exists('TCPDF')) {
        echo "<p class='success'>✓ TCPDF está instalado</p>";
        try {
            $reflection = new ReflectionClass('TCPDF');
            echo "<p class='info'>Ubicación TCPDF: " . dirname($reflection->getFileName()) . "</p>";
        } catch (Exception $e) {
            echo "<p class='warning'>No se pudo obtener información de TCPDF</p>";
        }
    } else {
        echo "<p class='error'>✗ TCPDF NO está disponible</p>";
    }
    
    // Verificar Dompdf
    if (class_exists('Dompdf\Dompdf')) {
        echo "<p class='success'>✓ Dompdf está instalado</p>";
        try {
            $reflection = new ReflectionClass('Dompdf\Dompdf');
            echo "<p class='info'>Ubicación Dompdf: " . dirname($reflection->getFileName()) . "</p>";
        } catch (Exception $e) {
            echo "<p class='warning'>No se pudo obtener información de Dompdf</p>";
        }
    } else {
        echo "<p class='error'>✗ Dompdf NO está disponible</p>";
    }
} else {
    echo "<p class='error'>✗ Composer autoload no encontrado</p>";
    echo "<p class='warning'>⚠ Debes ejecutar: <code>composer install</code></p>";
}

// 4. Verificar sesión
echo "<h3>4. Sesión de Usuario</h3>";
if (isset($_SESSION['user_id'])) {
    echo "<p class='success'>✓ Usuario autenticado (ID: " . $_SESSION['user_id'] . ")</p>";
    
    // Verificar rol
    if (file_exists(__DIR__ . '/../../conexion.php')) {
        require_once __DIR__ . '/../../conexion.php';
        try {
            $stmt = $conn->prepare("SELECT role, name FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo "<p class='info'>Nombre: " . htmlspecialchars($user['name']) . "</p>";
                echo "<p class='info'>Rol: " . htmlspecialchars($user['role']) . "</p>";
                
                if ($user['role'] === 'admin') {
                    echo "<p class='success'>✓ Usuario es administrador</p>";
                } else {
                    echo "<p class='error'>✗ Usuario NO es administrador</p>";
                }
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error al verificar rol: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p class='error'>✗ No hay sesión activa</p>";
    echo "<p class='warning'>Debes iniciar sesión como administrador</p>";
}

// 5. Verificar conexión a base de datos
echo "<h3>5. Conexión a Base de Datos</h3>";
if (file_exists(__DIR__ . '/../../conexion.php')) {
    require_once __DIR__ . '/../../conexion.php';
    
    if (isset($conn)) {
        echo "<p class='success'>✓ Conexión a base de datos establecida</p>";
        
        // Probar consulta
        try {
            $stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p class='info'>Total de órdenes en BD: " . $result['total'] . "</p>";
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error al consultar órdenes: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='error'>✗ Variable \$conn no definida</p>";
    }
}

// 6. Verificar permisos
echo "<h3>6. Permisos del Sistema</h3>";
$tempDir = sys_get_temp_dir();
if (is_writable($tempDir)) {
    echo "<p class='success'>✓ Directorio temporal tiene permisos de escritura: $tempDir</p>";
} else {
    echo "<p class='error'>✗ Directorio temporal SIN permisos de escritura: $tempDir</p>";
}

$logDir = __DIR__ . '/../../';
if (is_writable($logDir)) {
    echo "<p class='success'>✓ Directorio del proyecto tiene permisos de escritura</p>";
} else {
    echo "<p class='warning'>⚠ Directorio del proyecto podría no tener permisos de escritura</p>";
}

// 7. Verificar logo
echo "<h3>7. Recursos (Logo)</h3>";
$logoPath = __DIR__ . '/../../images/logo2.png';
if (file_exists($logoPath)) {
    echo "<p class='success'>✓ Logo existe: $logoPath</p>";
    $logoSize = filesize($logoPath);
    echo "<p class='info'>Tamaño: " . number_format($logoSize / 1024, 2) . " KB</p>";
} else {
    echo "<p class='warning'>⚠ Logo no encontrado (opcional): $logoPath</p>";
}

// 8. Test de generación de PDF simple
echo "<h3>8. Test de Generación de PDF</h3>";

// Test TCPDF
if (class_exists('TCPDF')) {
    try {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetTitle('Test PDF');
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Write(0, 'Test PDF - TCPDF funciona correctamente', '', 0, 'L', true, 0, false, false, 0);
        
        echo "<p class='success'>✓ TCPDF puede generar PDFs</p>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error al crear PDF con TCPDF: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='warning'>⚠ TCPDF no disponible para prueba</p>";
}

// Test Dompdf
if (class_exists('Dompdf\Dompdf')) {
    try {
        $dompdf = new Dompdf\Dompdf();
        $html = '<html><body><h1>Test PDF - Dompdf</h1><p>Si ves esto, Dompdf funciona correctamente.</p></body></html>';
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        echo "<p class='success'>✓ Dompdf puede generar PDFs</p>";
        echo "<p class='info'>Puedes descargar un PDF de prueba <a href='../../tests/admin/api/test_simple_pdf.php' target='_blank'>aquí</a></p>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error al crear PDF con Dompdf: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='warning'>⚠ Dompdf no disponible para prueba</p>";
}

// 9. Resumen
echo "<hr>";
echo "<h3>Resumen y Acciones Recomendadas</h3>";

if (!file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    echo "<div style='background: #ffeeee; padding: 15px; border-left: 4px solid red;'>";
    echo "<strong>ACCIÓN REQUERIDA:</strong><br>";
    echo "Ejecuta en PowerShell:<br>";
    echo "<pre>cd c:\\xampp\\htdocs\\angelow\ncomposer install</pre>";
    echo "</div>";
} elseif (!isset($_SESSION['user_id'])) {
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid orange;'>";
    echo "<strong>ACCIÓN REQUERIDA:</strong><br>";
    echo "Debes iniciar sesión como administrador primero.";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid green;'>";
    echo "<strong>¡Sistema listo!</strong><br>";
    echo "Puedes exportar PDFs desde la página de órdenes.";
    echo "</div>";
}

echo "<hr>";
echo "<p><small>Diagnóstico completado el " . date('Y-m-d H:i:s') . "</small></p>";
?>
