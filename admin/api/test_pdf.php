<?php
// Archivo de prueba para verificar que TCPDF esté disponible

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

try {
    // Verificar si TCPDF está disponible
    if (!class_exists('TCPDF')) {
        throw new Exception('TCPDF no está instalado. Por favor ejecuta: composer install');
    }
    
    echo "✓ TCPDF está correctamente instalado<br>";
    echo "✓ Versión de PHP: " . PHP_VERSION . "<br>";
    echo "✓ Base URL: " . BASE_URL . "<br>";
    
    // Verificar permisos de escritura
    $tempDir = sys_get_temp_dir();
    if (is_writable($tempDir)) {
        echo "✓ Directorio temporal tiene permisos de escritura<br>";
    } else {
        echo "⚠ Advertencia: El directorio temporal no tiene permisos de escritura<br>";
    }
    
    echo "<br><strong>Sistema listo para generar PDFs</strong>";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage();
}
?>
