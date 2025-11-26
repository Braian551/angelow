<?php
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $term = trim($_POST['term']);
    
    if (!empty($term)) {
        try {
            // Primero verificar si ya existe esta búsqueda para el usuario
            $checkStmt = $conn->prepare("
                SELECT id FROM search_history 
                WHERE user_id = :user_id AND search_term = :term
                LIMIT 1
            ");
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->bindParam(':term', $term);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                // Actualizar la fecha si ya existe
                $updateStmt = $conn->prepare("
                    UPDATE search_history 
                    SET created_at = NOW() 
                    WHERE user_id = :user_id AND search_term = :term
                ");
                $updateStmt->bindParam(':user_id', $userId);
                $updateStmt->bindParam(':term', $term);
                $updateStmt->execute();
            } else {
                // Insertar nueva búsqueda si no existe
                $insertStmt = $conn->prepare("
                    INSERT INTO search_history (user_id, search_term) 
                    VALUES (:user_id, :term)
                ");
                $insertStmt->bindParam(':user_id', $userId);
                $insertStmt->bindParam(':term', $term);
                $insertStmt->execute();
            }
            
            // También guardar en términos populares
            $popularStmt = $conn->prepare("
                INSERT INTO popular_searches (search_term) 
                VALUES (:term)
                ON DUPLICATE KEY UPDATE search_count = search_count + 1, last_searched = NOW()
            ");
            $popularStmt->bindParam(':term', $term);
            $popularStmt->execute();
        } catch (PDOException $e) {
            error_log('Error al guardar búsqueda: ' . $e->getMessage());
        }
    }
}