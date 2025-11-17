<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../producto/includes/product-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default to JSON for API responses
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Support form-encoded and raw JSON
$input = $_POST;
if (empty($input)) {
    $json = json_decode(file_get_contents('php://input'), true);
    if (is_array($json)) $input = $json;
}

$productId = isset($input['product_id']) ? intval($input['product_id']) : 0;
$rating = isset($input['rating']) ? intval($input['rating']) : 0;
$title = isset($input['title']) ? trim($input['title']) : '';
$comment = isset($input['comment']) ? trim($input['comment']) : '';

// Basic validation
if (!$productId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Producto inválido']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Calificación inválida']);
    exit;
}

if (strlen($title) < 3) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El título debe tener al menos 3 caracteres']);
    exit;
}

if (strlen($comment) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El comentario debe tener al menos 10 caracteres']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para dejar una opinión']);
    exit;
}

// Check product exists
try {
    $stmt = $conn->prepare("SELECT id, slug, name FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        exit;
    }

    // Verify user can review: check if they purchased
    $canReview = canUserReviewProduct($conn, $_SESSION['user_id'] ?? null, $productId);
    if (!$canReview) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Solo los clientes que han comprado este producto pueden dejar una opinión']);
        exit;
    }

    // Detect order_id used for verifying purchase (optional)
    $orderId = null;
    $stmt = $conn->prepare("SELECT o.id FROM orders o JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'delivered' LIMIT 1");
    $stmt->execute([$_SESSION['user_id'], $productId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($order) $orderId = $order['id'];

    // Handle image uploads
    $savedImages = [];
    if (isset($_FILES['review_images']) && is_array($_FILES['review_images']['tmp_name'])) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/avif' => 'avif'];
        $maxFiles = 5;
        $maxSize = 5 * 1024 * 1024; // 5MB each

        $countFiles = count($_FILES['review_images']['tmp_name']);
        if ($countFiles > $maxFiles) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Máximo ' . $maxFiles . ' imágenes permitidas']);
            exit;
        }

        for ($i = 0; $i < $countFiles; $i++) {
            if (empty($_FILES['review_images']['tmp_name'][$i])) continue;

            $tmp = $_FILES['review_images']['tmp_name'][$i];
            $mime = mime_content_type($tmp);
            $size = (int)$_FILES['review_images']['size'][$i];

            if (!isset($allowed[$mime])) continue; // skip invalid mime types

            if ($size > $maxSize) continue;

            $ext = $allowed[$mime];
            $filename = 'review_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $destDir = __DIR__ . '/../uploads/reviews';
            if (!is_dir($destDir)) { @mkdir($destDir, 0777, true); }
            $destPath = $destDir . '/' . $filename;

            if (move_uploaded_file($tmp, $destPath)) {
                $savedImages[] = 'uploads/reviews/' . $filename;
            }
        }
    }

    // Insert review
    try {
        $stmt = $conn->prepare('INSERT INTO product_reviews (product_id, user_id, order_id, rating, title, comment, images, is_verified, is_approved, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $imagesJson = !empty($savedImages) ? json_encode($savedImages) : null;
        $isVerified = $orderId ? 1 : 0;
        $isApproved = 1; // auto-approved for now

        $stmt->execute([$productId, $_SESSION['user_id'], $orderId, $rating, $title, $comment, $imagesJson, $isVerified, $isApproved]);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "1364") !== false || stripos($e->getMessage(), "doesn't have a default value") !== false) {
            // fallback for missing AUTO_INCREMENT id column
            $nextStmt = $conn->query('SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM product_reviews');
            $nextId = intval($nextStmt->fetchColumn());
            $ins = $conn->prepare('INSERT INTO product_reviews (id, product_id, user_id, order_id, rating, title, comment, images, is_verified, is_approved, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $ins->execute([$nextId, $productId, $_SESSION['user_id'], $orderId, $rating, $title, $comment, $imagesJson, $isVerified, $isApproved]);
        } else {
            throw $e;
        }
    }

    $reviewId = $conn->lastInsertId();

    // Return success
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') === false && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Opinión enviada correctamente'];
        header('Location: ' . BASE_URL . '/producto/verproducto.php?slug=' . urlencode($product['slug']) . '#reviews');
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Opinión enviada', 'data' => ['id' => $reviewId]]);

} catch (PDOException $e) {
    error_log('submit_review error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar la opinión']);
}
