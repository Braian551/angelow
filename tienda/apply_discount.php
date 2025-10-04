<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$discount_code = trim($_POST['discount_code'] ?? '');
$subtotal = floatval($_POST['subtotal'] ?? 0);

if (empty($discount_code)) {
    echo json_encode(['success' => false, 'message' => 'El código de descuento no puede estar vacío']);
    exit();
}

if ($subtotal <= 0) {
    echo json_encode(['success' => false, 'message' => 'El subtotal debe ser mayor a cero']);
    exit();
}

try {
    $conn->beginTransaction();

    // Buscar el código de descuento
    $discountQuery = "
        SELECT dc.*, pd.percentage, pd.max_discount_amount 
        FROM discount_codes dc 
        LEFT JOIN percentage_discounts pd ON dc.id = pd.discount_code_id 
        WHERE dc.code = ? AND dc.is_active = 1 
        AND (dc.start_date IS NULL OR dc.start_date <= NOW()) 
        AND (dc.end_date IS NULL OR dc.end_date >= NOW())
        AND (dc.max_uses IS NULL OR dc.used_count < dc.max_uses)
    ";
    
    $stmt = $conn->prepare($discountQuery);
    $stmt->execute([$discount_code]);
    $discount = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$discount) {
        echo json_encode(['success' => false, 'message' => 'Código de descuento no válido o expirado']);
        exit();
    }

    // Verificar si es de un solo uso y ya fue usado por este usuario
    if ($discount['is_single_use']) {
        $usageCheck = $conn->prepare("SELECT id FROM discount_code_usage WHERE discount_code_id = ? AND user_id = ?");
        $usageCheck->execute([$discount['id'], $_SESSION['user_id']]);
        if ($usageCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Este código de descuento ya ha sido utilizado']);
            exit();
        }
    }

    // Calcular descuento según el tipo
    $discount_amount = 0;
    
    if ($discount['discount_type_id'] == 1 && $discount['percentage']) { // Porcentaje
        $discount_amount = ($subtotal * $discount['percentage']) / 100;
        if ($discount['max_discount_amount'] > 0 && $discount_amount > $discount['max_discount_amount']) {
            $discount_amount = $discount['max_discount_amount'];
        }
    }

    // Guardar en sesión para usar en el checkout
    $_SESSION['applied_discount'] = [
        'code' => $discount_code,
        'amount' => $discount_amount,
        'discount_id' => $discount['id']
    ];

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => '¡Descuento aplicado exitosamente!',
        'discount_amount' => $discount_amount,
        'discount_code' => $discount_code
    ]);

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Error al aplicar descuento: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al procesar el código de descuento']);
}
?>