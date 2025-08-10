<?php
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

$response = [
    'exactMatch' => false,
    'product' => null
];

if (isset($_GET['term'])) {
    $searchTerm = trim($_GET['term']);
    
    try {
        $stmt = $conn->prepare("
            SELECT p.id, p.name, p.slug, pi.image_path
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.name = :term AND p.is_active = 1
            LIMIT 1
        ");
        $stmt->bindValue(':term', $searchTerm);
        $stmt->execute();
        
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $response['exactMatch'] = true;
            $response['product'] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'slug' => $product['slug'],
                'image_path' => $product['image_path']
            ];
        }
    } catch (PDOException $e) {
        error_log('Error en búsqueda exacta: ' . $e->getMessage());
    }
}

echo json_encode($response);
exit;