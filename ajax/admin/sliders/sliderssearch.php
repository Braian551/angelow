<?php
ob_start();
session_start();
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || $user['role'] !== 'admin') {
        echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
        exit();
    }

    $sql = "SELECT id, title, subtitle, image, link, order_position, is_active, created_at
            FROM sliders ORDER BY order_position ASC, created_at ASC";
    $stmt = $conn->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = array_map(function($r) {
        return [
            'id' => (int)$r['id'],
            'title' => $r['title'],
            'subtitle' => $r['subtitle'],
            'image' => $r['image'] ? BASE_URL . '/' . ltrim($r['image'], '/') : null,
            'link' => $r['link'],
            'order_position' => (int)$r['order_position'],
            'is_active' => (bool)$r['is_active']
        ];
    }, $rows);

    echo json_encode(['success' => true, 'items' => $items]);

} catch (PDOException $e) {
    error_log('sliderssearch error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos']);
}
ob_end_flush();
