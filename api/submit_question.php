<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

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

// Raw POST data (support both form-encoded and JSON body)
$input = $_POST;
if (empty($input)) {
    $json = json_decode(file_get_contents('php://input'), true);
    if (is_array($json)) $input = $json;
}

$productId = isset($input['product_id']) ? intval($input['product_id']) : 0;
$questionText = isset($input['question']) ? trim($input['question']) : '';

// Validate
if (!$productId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Producto inválido']);
    exit;
}

if (strlen($questionText) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La pregunta debe tener al menos 10 caracteres']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    // If the request expects JSON, return 401, otherwise redirect to login
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || isset($input['ajax'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para enviar preguntas']);
        exit;
    } else {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Debes iniciar sesión para enviar preguntas'];
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

try {
    // Verificar existencia del producto
    $stmt = $conn->prepare("SELECT id, slug, name FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        exit;
    }

    // Insertar pregunta - la columna id debería ser AUTO_INCREMENT, pero puede faltar en algunas instalaciones.
    try {
        $stmt = $conn->prepare("INSERT INTO product_questions (product_id, user_id, question) VALUES (?, ?, ?)");
        $stmt->execute([$productId, $_SESSION['user_id'], $questionText]);
    } catch (PDOException $e) {
        // SQLSTATE 1364: Field 'id' doesn't have a default value - fallback temporal
        if (strpos($e->getMessage(), "1364") !== false || stripos($e->getMessage(), "doesn't have a default value") !== false) {
            error_log("submit_question.php: detected missing AUTO_INCREMENT for product_questions.id, falling back to manual id generation");

            // Calcular siguiente id disponible (no es concurrencia-safe, pero útil como fallback)
            $nextStmt = $conn->query('SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM product_questions');
            $nextId = intval($nextStmt->fetchColumn());

            $ins = $conn->prepare('INSERT INTO product_questions (id, product_id, user_id, question) VALUES (?, ?, ?, ?)');
            $ins->execute([$nextId, $productId, $_SESSION['user_id'], $questionText]);
        } else {
            throw $e; // volver a lanzar para ser capturado por el catch exterior
        }
    }

    $questionId = $conn->lastInsertId();
    error_log("submit_question.php: pregunta insertada id={$questionId}, product_id={$productId}, user_id={$_SESSION['user_id']}");

    // Optionally: add a notification for the product's seller/admin (not implemented here)

    // If the form used a normal POST (no XMLHttpRequest), set alert and redirect back to product
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') === false && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Pregunta enviada. Pronto recibirás una respuesta.'];
        header('Location: ' . BASE_URL . '/producto/verproducto.php?slug=' . urlencode($product['slug']));
        exit;
    }

    // Return JSON success response with the created object
    echo json_encode([
        'success' => true,
        'message' => 'Pregunta enviada correctamente',
        'data' => [
            'id' => $questionId,
            'product_id' => $productId,
            'user_id' => $_SESSION['user_id'],
            'question' => $questionText,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error submit_question: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar la pregunta']);
}
