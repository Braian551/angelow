<?php
require_once 'config.php';
require_once 'conexion.php';

try {
    echo "Probando el procedimiento GetFilteredProducts...\n\n";

    // Parámetros de prueba
    $searchQuery = 'ropa';
    $categoryFilter = null;
    $genderFilter = '';
    $priceMin = null;
    $priceMax = null;
    $sortBy = 'newest';
    $limit = 12;
    $offset = 0;
    $userId = null;

    echo "Parámetros:\n";
    echo "- searchQuery: '$searchQuery'\n";
    echo "- categoryFilter: " . ($categoryFilter ?? 'null') . "\n";
    echo "- genderFilter: '$genderFilter'\n";
    echo "- priceMin: " . ($priceMin ?? 'null') . "\n";
    echo "- priceMax: " . ($priceMax ?? 'null') . "\n";
    echo "- sortBy: '$sortBy'\n";
    echo "- limit: $limit\n";
    echo "- offset: $offset\n";
    echo "- userId: " . ($userId ?? 'null') . "\n\n";

    // Llamar al procedimiento almacenado
    $stmt = $conn->prepare("CALL GetFilteredProducts(?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bindValue(1, $searchQuery, PDO::PARAM_STR);
    $stmt->bindValue(2, $categoryFilter, $categoryFilter !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(3, $genderFilter, $genderFilter !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(4, $priceMin, $priceMin !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(5, $priceMax, $priceMax !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(6, $sortBy, PDO::PARAM_STR);
    $stmt->bindValue(7, $limit, PDO::PARAM_INT);
    $stmt->bindValue(8, $offset, PDO::PARAM_INT);
    $stmt->bindValue(9, $userId, $userId !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);

    echo "Ejecutando procedimiento...\n";
    $stmt->execute();

    echo "Obteniendo productos del primer resultset...\n";
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Productos obtenidos: " . count($products) . "\n";

    if (count($products) > 0) {
        echo "Primer producto:\n";
        print_r($products[0]);
    } else {
        echo "No se obtuvieron productos del primer resultset.\n";
    }

    echo "\nCambiando al siguiente resultset...\n";
    $stmt->nextRowset();
    $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total de productos: " . ($totalResult['total'] ?? 'N/A') . "\n";

    // Verificar si hay más resultsets
    echo "\nVerificando si hay más resultsets...\n";
    while ($stmt->nextRowset()) {
        $extraResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Resultset extra encontrado con " . count($extraResults) . " filas\n";
        if (count($extraResults) > 0) {
            print_r($extraResults);
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>