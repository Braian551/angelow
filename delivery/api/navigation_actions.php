<?php
/**
 * API para Cancelar Navegación y Reportar Problemas
 * Maneja las acciones de cancelación y reportes durante la navegación
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'No autenticado'
    ]);
    exit();
}

// Verificar rol de delivery
try {
    $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'delivery') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Sin permisos'
        ]);
        exit();
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de autenticación'
    ]);
    exit();
}

$driver_id = $user['id'];

// Obtener acción
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'cancel_navigation':
            cancelNavigation($driver_id);
            break;
            
        case 'report_problem':
            reportProblem($driver_id);
            break;
            
        case 'get_problem_types':
            getProblemTypes();
            break;
            
        case 'get_cancellation_reasons':
            getCancellationReasons();
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Acción no válida'
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Cancelar navegación actual
 */
function cancelNavigation($driver_id) {
    global $conn;
    
    // Obtener datos del POST
    $data = json_decode(file_get_contents('php://input'), true);
    
    $delivery_id = $data['delivery_id'] ?? null;
    $reason = $data['reason'] ?? 'other';
    $notes = $data['notes'] ?? '';
    $latitude = $data['latitude'] ?? null;
    $longitude = $data['longitude'] ?? null;
    
    // Información del dispositivo
    $device_info = json_encode([
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // Validar datos requeridos
    if (!$delivery_id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'delivery_id es requerido'
        ]);
        return;
    }
    
    // Verificar que el delivery existe y pertenece al driver
    try {
        $stmt = $conn->prepare("
            SELECT od.id, od.delivery_status 
            FROM order_deliveries od 
            WHERE od.id = ? AND od.driver_id = ? COLLATE utf8mb4_general_ci
        ");
        $stmt->execute([$delivery_id, $driver_id]);
        $deliveryCheck = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$deliveryCheck) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Esta entrega no te pertenece o no existe'
            ]);
            return;
        }
        
        if (!in_array($deliveryCheck['delivery_status'], ['driver_accepted', 'in_transit', 'arrived'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'No se puede cancelar navegación para entregas en estado: ' . $deliveryCheck['delivery_status']
            ]);
            return;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al validar la entrega'
        ]);
        return;
    }
    
    // Llamar al procedimiento almacenado usando PDO
    try {
        $stmt = $conn->prepare("CALL CancelNavigation(?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $delivery_id,
            $driver_id,
            $reason,
            $notes,
            $latitude,
            $longitude,
            $device_info
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => $result['message'] ?? 'Navegación cancelada exitosamente',
            'cancellation_id' => $result['cancellation_id'] ?? null
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al cancelar navegación: ' . $e->getMessage()
        ]);
    }
}

/**
 * Reportar un problema durante la navegación
 */
function reportProblem($driver_id) {
    global $conn;
    
    // Obtener datos del request soportando JSON y multipart/form-data
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    $isJson = stripos($contentType, 'application/json') !== false;
    $rawBody = file_get_contents('php://input');
    $data = $isJson ? (json_decode($rawBody, true) ?: []) : [];

    // Preferir $_POST cuando llega multipart/form-data o x-www-form-urlencoded
    $delivery_id = $_POST['delivery_id'] ?? ($data['delivery_id'] ?? null);
    $problem_type = $_POST['problem_type'] ?? ($data['problem_type'] ?? 'other');
    $title = $_POST['title'] ?? ($data['title'] ?? '');
    $description = $_POST['description'] ?? ($data['description'] ?? '');
    $severity = $_POST['severity'] ?? ($data['severity'] ?? 'medium');
    $latitude = isset($_POST['latitude']) ? $_POST['latitude'] : ($data['latitude'] ?? null);
    $longitude = isset($_POST['longitude']) ? $_POST['longitude'] : ($data['longitude'] ?? null);
    $photo_path = $_POST['photo_path'] ?? ($data['photo_path'] ?? null);
    
    // Información del dispositivo
    $device_info = json_encode([
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'timestamp' => date('Y-m-d H:i:s'),
        'screen_resolution' => $_POST['screen_resolution'] ?? ($data['screen_resolution'] ?? null)
    ]);
    
    // Validar datos requeridos
    if (!$delivery_id || !$title || !$description) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'delivery_id, title y description son requeridos'
        ]);
        return;
    }
    
    // Verificar que el delivery existe y pertenece al driver
    try {
        $stmt = $conn->prepare("
            SELECT od.id, od.delivery_status 
            FROM order_deliveries od 
            WHERE od.id = ? AND od.driver_id = ? COLLATE utf8mb4_general_ci
        ");
        $stmt->execute([$delivery_id, $driver_id]);
        $deliveryCheck = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$deliveryCheck) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Esta entrega no te pertenece o no existe'
            ]);
            return;
        }
        
        if (!in_array($deliveryCheck['delivery_status'], ['driver_accepted', 'in_transit', 'arrived'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'No se pueden reportar problemas para entregas en estado: ' . $deliveryCheck['delivery_status']
            ]);
            return;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al validar la entrega'
        ]);
        return;
    }
    
    // Manejar subida de foto si existe
    if (isset($_FILES['photo']) && is_array($_FILES['photo']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        try {
            $photo_path = handlePhotoUpload($_FILES['photo'], $delivery_id);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            return;
        }
    }
    
    // Llamar al procedimiento almacenado usando PDO
    try {
        $stmt = $conn->prepare("CALL ReportProblem(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $delivery_id,
            $driver_id,
            $problem_type,
            $title,
            $description,
            $severity,
            $latitude,
            $longitude,
            $photo_path,
            $device_info
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => $result['message'] ?? 'Problema reportado exitosamente',
            'report_id' => $result['report_id'] ?? null
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al reportar problema: ' . $e->getMessage()
        ]);
    }
}

/**
 * Obtener tipos de problemas disponibles
 */
function getProblemTypes() {
    $types = [
        [
            'value' => 'route_blocked',
            'label' => 'Ruta Bloqueada',
            'icon' => 'fa-road-barrier'
        ],
        [
            'value' => 'wrong_address',
            'label' => 'Dirección Incorrecta',
            'icon' => 'fa-map-marker-question'
        ],
        [
            'value' => 'gps_error',
            'label' => 'Error de GPS',
            'icon' => 'fa-satellite-dish'
        ],
        [
            'value' => 'traffic_jam',
            'label' => 'Tráfico Pesado',
            'icon' => 'fa-cars'
        ],
        [
            'value' => 'road_closed',
            'label' => 'Vía Cerrada',
            'icon' => 'fa-road-circle-xmark'
        ],
        [
            'value' => 'vehicle_issue',
            'label' => 'Problema del Vehículo',
            'icon' => 'fa-car-burst'
        ],
        [
            'value' => 'weather',
            'label' => 'Condición Climática',
            'icon' => 'fa-cloud-rain'
        ],
        [
            'value' => 'customer_issue',
            'label' => 'Problema con Cliente',
            'icon' => 'fa-user-xmark'
        ],
        [
            'value' => 'app_error',
            'label' => 'Error de software',
            'icon' => 'fa-mobile-screen-button'
        ],
        [
            'value' => 'other',
            'label' => 'Otro',
            'icon' => 'fa-circle-question'
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $types
    ]);
}

/**
 * Obtener razones de cancelación disponibles
 */
function getCancellationReasons() {
    $reasons = [
        [
            'value' => 'order_cancelled',
            'label' => 'Pedido Cancelado por Cliente',
            'icon' => 'fa-ban'
        ],
        [
            'value' => 'customer_unavailable',
            'label' => 'Cliente No Disponible',
            'icon' => 'fa-user-slash'
        ],
        [
            'value' => 'address_wrong',
            'label' => 'Dirección Incorrecta/No Existe',
            'icon' => 'fa-location-crosshairs'
        ],
        [
            'value' => 'technical_issue',
            'label' => 'Problema Técnico',
            'icon' => 'fa-wrench'
        ],
        [
            'value' => 'driver_emergency',
            'label' => 'Emergencia del Conductor',
            'icon' => 'fa-ambulance'
        ],
        [
            'value' => 'other',
            'label' => 'Otra Razón',
            'icon' => 'fa-ellipsis'
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $reasons
    ]);
}

/**
 * Manejar subida de foto de evidencia
 */
function handlePhotoUpload($file, $delivery_id) {
    $upload_dir = __DIR__ . '/../../uploads/problem_reports/';
    
    // Crear directorio si no existe
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Validar tipo de archivo
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $file_type = $file['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        throw new Exception('Tipo de archivo no permitido. Solo se permiten imágenes.');
    }
    
    // Validar tamaño (máximo 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('El archivo es demasiado grande. Máximo 5MB.');
    }
    
    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'problem_' . $delivery_id . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Error al guardar la foto');
    }
    
    // Retornar ruta relativa
    return 'uploads/problem_reports/' . $filename;
}

?>
