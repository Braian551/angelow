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
    // Validar rol admin
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || $user['role'] !== 'admin') {
        echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
        exit();
    }

    $search = isset($_GET['search']) ? '%' . trim($_GET['search']) . '%' : null;
    $status = isset($_GET['status']) ? ($_GET['status'] === 'active' ? 1 : 0) : null;
    $featured = isset($_GET['featured']) ? $_GET['featured'] : null; // 'featured' | 'not_featured'
    $published = isset($_GET['published']) ? $_GET['published'] : null; // 'published' | 'unpublished'
    $order = isset($_GET['order']) ? $_GET['order'] : 'newest';
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = 12;
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT id, title, slug, content, image, is_featured, is_active, published_at, created_at
            FROM news WHERE 1=1";
    $params = [];
    $types = '';

    if ($search) {
        $sql .= " AND (title LIKE ? OR content LIKE ?)";
        $params[] = $search; $params[] = $search; $types .= 'ss';
    }
    if ($status !== null) {
        $sql .= " AND is_active = ?";
        $params[] = $status; $types .= 'i';
    }
    if ($featured) {
        $sql .= $featured === 'featured' ? " AND is_featured = 1" : " AND is_featured = 0";
    }
    if ($published) {
        $sql .= $published === 'published' ? " AND published_at IS NOT NULL" : " AND published_at IS NULL";
    }

    switch ($order) {
        case 'title_asc': $sql .= " ORDER BY title ASC"; break;
        case 'title_desc': $sql .= " ORDER BY title DESC"; break;
        case 'published_desc':
            // MySQL no soporta NULLS LAST; usar expresión booleana para ordenar nulls al final
            $sql .= " ORDER BY (published_at IS NULL) ASC, published_at DESC, created_at DESC"; break;
        case 'published_asc':
            // Nulls primero usando expresión booleana
            $sql .= " ORDER BY (published_at IS NULL) DESC, published_at ASC, created_at DESC"; break;
        default: $sql .= " ORDER BY created_at DESC"; // newest
    }

    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $perPage; $params[] = $offset; $types .= 'ii';

    // Count
    $countSql = "SELECT COUNT(*) FROM news WHERE 1=1";
    $countParams = [];
    if ($search) { $countSql .= " AND (title LIKE ? OR content LIKE ?)"; $countParams[] = $search; $countParams[] = $search; }
    if ($status !== null) { $countSql .= " AND is_active = ?"; $countParams[] = $status; }
    if ($featured) { $countSql .= $featured === 'featured' ? " AND is_featured = 1" : " AND is_featured = 0"; }
    if ($published) { $countSql .= $published === 'published' ? " AND published_at IS NOT NULL" : " AND published_at IS NULL"; }

    $stmt = $conn->prepare($sql);
    foreach ($params as $idx => $param) {
        $paramType = $types[$idx] === 'i' ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($idx + 1, $param, $paramType);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countStmt = $conn->prepare($countSql);
    $countStmt->execute($countParams);
    $total = (int)$countStmt->fetchColumn();

    $items = array_map(function($r) {
        return [
            'id' => (int)$r['id'],
            'title' => $r['title'],
            'slug' => $r['slug'],
            'image' => $r['image'] ? BASE_URL . '/' . ltrim($r['image'], '/') : null,
            'is_featured' => (bool)$r['is_featured'],
            'is_active' => (bool)$r['is_active'],
            'published_at' => $r['published_at']
        ];
    }, $rows);

    echo json_encode([
        'success' => true,
        'items' => $items,
        'meta' => [
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ]
    ]);

} catch (PDOException $e) {
    error_log('newssearch.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos']);
}
ob_end_flush();
