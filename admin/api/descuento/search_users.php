<?php
// Configuración estricta para evitar cualquier salida no deseada
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Limpiar todos los buffers de salida
while (ob_get_level()) {
    ob_end_clean();
}

// Iniciar nuevo buffer limpio
ob_start();

// Función para enviar respuesta JSON limpia
function sendJsonResponse($data, $statusCode = 200) {
    // Limpiar cualquier salida previa
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Establecer headers
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Enviar respuesta y terminar
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

try {
    // Incluir archivos de configuración sin mostrar errores
    $configPath = __DIR__ . '/../../../config.php';
    $conexionPath = __DIR__ . '/../../../conexion.php';
    
    if (!file_exists($configPath) || !file_exists($conexionPath)) {
        sendJsonResponse(['error' => 'Archivos de configuración no encontrados'], 500);
    }
    
    require_once $configPath;
    require_once $conexionPath;

    // Verificar autenticación y permisos de admin
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse(['error' => 'No autorizado'], 401);
    }

    // Verificar permisos de administrador
    if (!isset($conn) || !$conn) {
        sendJsonResponse(['error' => 'Error de conexión a la base de datos'], 500);
    }

    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    if (!$stmt) {
        sendJsonResponse(['error' => 'Error en la consulta de permisos'], 500);
    }
    
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'admin') {
        sendJsonResponse(['error' => 'No tienes permisos'], 403);
    }

    // Obtener y validar el término de búsqueda
    $searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (strlen($searchTerm) < 2) {
        sendJsonResponse([]);
    }

    // Realizar búsqueda de usuarios
    $searchPattern = "%$searchTerm%";
    $stmt = $conn->prepare("
        SELECT id, name, email, phone 
        FROM users 
        WHERE (name LIKE ? OR email LIKE ?) 
        AND role = 'customer'
        AND id IS NOT NULL
        ORDER BY name ASC
        LIMIT 20
    ");
    
    if (!$stmt) {
        sendJsonResponse(['error' => 'Error al preparar la consulta'], 500);
    }
    
    $result = $stmt->execute([$searchPattern, $searchPattern]);
    if (!$result) {
        sendJsonResponse(['error' => 'Error al ejecutar la búsqueda'], 500);
    }
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Limpiar y validar los datos
    $cleanUsers = [];
    foreach ($users as $user) {
        if (isset($user['id']) && $user['id']) {
            $cleanUsers[] = [
                'id' => (int)$user['id'],
                'name' => isset($user['name']) ? trim(strip_tags($user['name'])) : '',
                'email' => isset($user['email']) ? trim(strip_tags($user['email'])) : '',
                'phone' => isset($user['phone']) ? trim(strip_tags($user['phone'])) : ''
            ];
        }
    }
    
    sendJsonResponse($cleanUsers);

} catch (PDOException $e) {
    error_log("Error PDO en search_users.php: " . $e->getMessage());
    sendJsonResponse(['error' => 'Error en la base de datos'], 500);
} catch (Exception $e) {
    error_log("Error general en search_users.php: " . $e->getMessage());
    sendJsonResponse(['error' => 'Error interno del servidor'], 500);
}
?>