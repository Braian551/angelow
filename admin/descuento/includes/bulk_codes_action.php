<?php
// admin/descuento/includes/bulk_codes_action.php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple permission check
if (!isset($_SESSION['user_id'])) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'No autenticado'];
    header('Location: ../generate_codes.php');
    exit();
}

$userStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['role'] !== 'admin') {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Acceso no autorizado'];
    header('Location: ../generate_codes.php');
    exit();
}

$ids = $_POST['ids'] ?? [];
$action = $_POST['action'] ?? '';

// Sanitize ids
$ids = array_filter(array_map('intval', (array)$ids));
if (empty($ids) || !in_array($action, ['activate', 'deactivate', 'delete'])) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'No se seleccionaron códigos o acción inválida'];
    header('Location: ../generate_codes.php');
    exit();
}

try {
    $conn->beginTransaction();
    if ($action === 'activate' || $action === 'deactivate') {
        $val = $action === 'activate' ? 1 : 0;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE discount_codes SET is_active = ? WHERE id IN ($placeholders)";
        $params = array_merge([$val], $ids);
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $affected = $stmt->rowCount();
        $conn->commit();
        $_SESSION['alert'] = ['type' => 'success', 'message' => "$affected códigos actualizados."];
    } else {
        // delete: remove rows from related tables then discount_codes
        foreach ($ids as $id) {
            // delete related entries
            $stm = $conn->prepare("DELETE FROM discount_code_products WHERE discount_code_id = ?");
            $stm->execute([$id]);
            $stm = $conn->prepare("DELETE FROM percentage_discounts WHERE discount_code_id = ?");
            $stm->execute([$id]);
            $stm = $conn->prepare("DELETE FROM fixed_amount_discounts WHERE discount_code_id = ?");
            $stm->execute([$id]);
            $stm = $conn->prepare("DELETE FROM free_shipping_discounts WHERE discount_code_id = ?");
            $stm->execute([$id]);
            // delete main code
            $stm = $conn->prepare("DELETE FROM discount_codes WHERE id = ?");
            $stm->execute([$id]);
        }
        $conn->commit();
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Códigos eliminados correctamente.'];
    }
} catch (Exception $e) {
    $conn->rollBack();
    error_log('Error bulk action codes: ' . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Ocurrió un error al procesar la acción masiva.'];
}

header('Location: ../generate_codes.php');
exit();

?>
