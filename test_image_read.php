<?php
require_once __DIR__ . '/config.php';

$relativePath = 'uploads/productos/69150408e8b9c_687c4f85cd486_conjunto_niño2.jpg';
$fullPath = __DIR__ . '/' . $relativePath;

echo "Testing file: " . $fullPath . "\n";

if (file_exists($fullPath)) {
    echo "File exists: YES\n";
    $content = file_get_contents($fullPath);
    if ($content !== false) {
        echo "Read success: YES\n";
        echo "Size: " . strlen($content) . " bytes\n";
        echo "Base64 start: " . substr(base64_encode($content), 0, 20) . "...\n";
    } else {
        echo "Read success: NO\n";
        echo "Last error: " . print_r(error_get_last(), true) . "\n";
    }
} else {
    echo "File exists: NO\n";
}
?>
