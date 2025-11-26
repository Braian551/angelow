<?php
// test_pdf_logic.php
require_once __DIR__ . '/config.php';

// Mock data based on the issue
$imageUrl = 'http://localhost/angelow/uploads/productos/69150408e8b9c_687c4f85cd486_conjunto_niño2.jpg';
$projectRoot = __DIR__;

echo "Testing Image URL: $imageUrl\n";
echo "Project Root: $projectRoot\n";

// Logic from pdf_helpers.php
$imagePathFound = false;

if (strpos($imageUrl, BASE_URL) === 0) {
    $relativePath = substr($imageUrl, strlen(BASE_URL));
    $relativePath = strtok($relativePath, '?');
    
    if (strpos($relativePath, '/') !== 0) {
        $relativePath = '/' . $relativePath;
    }
    
    $localPath = $projectRoot . $relativePath;
    $localPath = str_replace(['//', '\\'], ['/', DIRECTORY_SEPARATOR], $localPath);
    
    echo "Resolved Local Path: $localPath\n";
    
    if (file_exists($localPath)) {
        $imageUrl = $localPath;
        $imagePathFound = true;
        echo "File Exists: YES\n";
    } else {
        echo "File Exists: NO\n";
    }
}

if ($imagePathFound) {
    try {
        $imageContent = file_get_contents($imageUrl);
        if ($imageContent !== false) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageContent);
            echo "MIME Type: $mimeType\n";
            
            $base64 = base64_encode($imageContent);
            echo "Base64 Length: " . strlen($base64) . "\n";
            echo "Data URI Start: data:$mimeType;base64," . substr($base64, 0, 20) . "...\n";
        } else {
            echo "file_get_contents failed\n";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
} else {
    echo "Image path not found, skipping conversion.\n";
}
?>
