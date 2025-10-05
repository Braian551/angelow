<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para aplicar descuentos']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'remove') {
    // Remover descuento aplicado
    try {
        $deleteQuery = "DELETE FROM user_applied_discounts WHERE user_id = :user_id AND is_used = 0";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->execute([':user_id' => $user_id]);
        
        echo json_encode(['success' => true, 'message' => 'Descuento removido correctamente']);
    } catch (PDOException $e) {
        error_log("Error al remover descuento: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al remover el descuento']);
    }
    exit();
}

// Aplicar descuento
$discount_code = trim($_POST['discount_code'] ?? '');
$subtotal = floatval($_POST['subtotal'] ?? 0);

if (empty($discount_code)) {
    echo json_encode(['success' => false, 'message' => 'El código de descuento no puede estar vacío']);
    exit();
}

try {
    // Verificar el código de descuento
    $discountQuery = "
        SELECT dc.*, pd.percentage, pd.max_discount_amount 
        FROM discount_codes dc 
        LEFT JOIN percentage_discounts pd ON dc.id = pd.discount_code_id 
        WHERE dc.code = :code AND dc.is_active = 1 
        AND (dc.start_date IS NULL OR dc.start_date <= NOW()) 
        AND (dc.end_date IS NULL OR dc.end_date >= NOW())
        AND (dc.max_uses IS NULL OR dc.used_count < dc.max_uses)
    ";
    $stmt = $conn->prepare($discountQuery);
    $stmt->execute([':code' => $discount_code]);
    $discount = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$discount) {
        echo json_encode(['success' => false, 'message' => 'El código de descuento no es válido o ha expirado']);
        exit();
    }

    // Verificar si es de un solo uso y ya fue usado por este usuario
    if ($discount['is_single_use']) {
        $usageCheck = $conn->prepare("SELECT id FROM discount_code_usage WHERE discount_code_id = :discount_id AND user_id = :user_id");
        $usageCheck->execute([':discount_id' => $discount['id'], ':user_id' => $user_id]);
        if ($usageCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Este código de descuento ya ha sido utilizado']);
            exit();
        }
    }

    // Calcular descuento
    $discount_amount = 0;
    
    if ($discount['discount_type_id'] == 1 && $discount['percentage']) { // Porcentaje
        $discount_amount = ($subtotal * $discount['percentage']) / 100;
        if ($discount['max_discount_amount'] > 0 && $discount_amount > $discount['max_discount_amount']) {
            $discount_amount = $discount['max_discount_amount'];
        }
    }

    // Guardar en tabla de descuentos aplicados
    $expiresAt = $discount['end_date'] ?: date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // Primero eliminar cualquier descuento previo no usado
    $deleteOld = $conn->prepare("DELETE FROM user_applied_discounts WHERE user_id = :user_id AND is_used = 0");
    $deleteOld->execute([':user_id' => $user_id]);
    
    // Insertar nuevo descuento
    $saveDiscount = $conn->prepare("
        INSERT INTO user_applied_discounts (user_id, discount_code_id, discount_code, discount_amount, expires_at) 
        VALUES (:user_id, :discount_id, :discount_code, :discount_amount, :expires_at)
    ");
    $saveDiscount->execute([
        ':user_id' => $user_id,
        ':discount_id' => $discount['id'],
        ':discount_code' => $discount_code,
        ':discount_amount' => $discount_amount,
        ':expires_at' => $expiresAt
    ]);

    echo json_encode([
        'success' => true,
        'message' => '¡Descuento aplicado correctamente!',
        'discount_amount' => $discount_amount,
        'discount_code' => $discount_code
    ]);

} catch (PDOException $e) {
    error_log("Error al aplicar descuento: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al aplicar el código de descuento']);
}
?>