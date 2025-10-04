<?php
// admin/descuento/includes/update_code_process.php

// Debug: mostrar datos recibidos
error_log("POST data recibida para actualización: " . print_r($_POST, true));

$code_id = $_POST['code_id'] ?? null;
$discount_type = $_POST['discount_type'];
$discount_value = null;
$max_uses = $_POST['max_uses'] ?: null;
$start_date = $_POST['start_date'] ?: null;
$end_date = $_POST['end_date'] ?: null;
$is_single_use = isset($_POST['is_single_use']) ? 1 : 0;
$is_active = isset($_POST['is_active']) ? 1 : 0;
$apply_to_all = isset($_POST['apply_to_all']) ? 1 : 0;
$selected_products = json_decode($_POST['products'] ?? '[]', true) ?: [];

// Validaciones básicas
if (empty($code_id)) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'ID de código no válido'];
    header("Location: generate_codes.php");
    exit();
}

if (empty($discount_type)) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Tipo de descuento es requerido'];
    header("Location: generate_codes.php?action=generate&edit=" . $code_id);
    exit();
}

// Obtener el valor del descuento según el tipo
if ($discount_type == 1) { // Porcentaje
    $discount_value = $_POST['discount_value'] ?? null;
} elseif ($discount_type == 2) { // Monto fijo
    $discount_value = $_POST['fixed_amount'] ?? null;
} else { // Envío gratis
    $discount_value = 0;
}

// Validar valor según tipo
if ($discount_type != 3) { // No es envío gratis
    if (empty($discount_value) || $discount_value <= 0) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Valor del descuento es requerido y debe ser mayor a 0'];
        header("Location: generate_codes.php?action=generate&edit=" . $code_id);
        exit();
    }

    // Validar según tipo
    if ($discount_type == 1 && ($discount_value <= 0 || $discount_value > 100)) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'El porcentaje debe estar entre 1 y 100'];
        header("Location: generate_codes.php?action=generate&edit=" . $code_id);
        exit();
    }

    if ($discount_type == 2 && $discount_value <= 0) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'El monto fijo debe ser mayor a 0'];
        header("Location: generate_codes.php?action=generate&edit=" . $code_id);
        exit();
    }
}

// Validar fechas
if ($start_date && $end_date && strtotime($start_date) > strtotime($end_date)) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'La fecha de inicio no puede ser mayor a la fecha de fin'];
    header("Location: generate_codes.php?action=generate&edit=" . $code_id);
    exit();
}

try {
    $conn->beginTransaction();

    // Actualizar código de descuento principal
    $sql = "UPDATE discount_codes 
            SET discount_value = ?, max_uses = ?, start_date = ?, end_date = ?, is_single_use = ?, is_active = ?
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        $discount_value,
        $max_uses,
        $start_date,
        $end_date,
        $is_single_use,
        $is_active,
        $code_id
    ]);

    if (!$result) {
        throw new Exception("Error al actualizar código principal");
    }

    // Actualizar en la tabla específica según el tipo de descuento
    switch ($discount_type) {
        case '1': // Porcentaje
        case 1:
            $max_discount = $_POST['max_discount_amount'] ?? null;
            // Verificar si ya existe un registro
            $stmt = $conn->prepare("SELECT id FROM percentage_discounts WHERE discount_code_id = ?");
            $stmt->execute([$code_id]);
            
            if ($stmt->rowCount() > 0) {
                $stmt = $conn->prepare("UPDATE percentage_discounts 
                    SET percentage = ?, max_discount_amount = ? 
                    WHERE discount_code_id = ?");
                $stmt->execute([$discount_value, $max_discount, $code_id]);
            } else {
                $stmt = $conn->prepare("INSERT INTO percentage_discounts 
                    (discount_code_id, percentage, max_discount_amount) 
                    VALUES (?, ?, ?)");
                $stmt->execute([$code_id, $discount_value, $max_discount]);
            }
            break;

        case '2': // Monto fijo
        case 2:
            $min_order = $_POST['min_order_amount'] ?? null;
            $stmt = $conn->prepare("SELECT id FROM fixed_amount_discounts WHERE discount_code_id = ?");
            $stmt->execute([$code_id]);
            
            if ($stmt->rowCount() > 0) {
                $stmt = $conn->prepare("UPDATE fixed_amount_discounts 
                    SET amount = ?, min_order_amount = ? 
                    WHERE discount_code_id = ?");
                $stmt->execute([$discount_value, $min_order, $code_id]);
            } else {
                $stmt = $conn->prepare("INSERT INTO fixed_amount_discounts 
                    (discount_code_id, amount, min_order_amount) 
                    VALUES (?, ?, ?)");
                $stmt->execute([$code_id, $discount_value, $min_order]);
            }
            break;

        case '3': // Envío gratis
        case 3:
            $shipping_method = $_POST['shipping_method_id'] ?? null;
            $stmt = $conn->prepare("SELECT id FROM free_shipping_discounts WHERE discount_code_id = ?");
            $stmt->execute([$code_id]);
            
            if ($stmt->rowCount() > 0) {
                $stmt = $conn->prepare("UPDATE free_shipping_discounts 
                    SET shipping_method_id = ? 
                    WHERE discount_code_id = ?");
                $stmt->execute([$shipping_method, $code_id]);
            } else {
                $stmt = $conn->prepare("INSERT INTO free_shipping_discounts 
                    (discount_code_id, shipping_method_id) 
                    VALUES (?, ?)");
                $stmt->execute([$code_id, $shipping_method]);
            }
            break;
    }

    // Actualizar productos asociados
    // Primero eliminar productos existentes
    $stmt = $conn->prepare("DELETE FROM discount_code_products WHERE discount_code_id = ?");
    $stmt->execute([$code_id]);

    // Si no aplica a todos, asignar productos seleccionados
    if (!$apply_to_all && !empty($selected_products)) {
        $stmt = $conn->prepare("INSERT INTO discount_code_products (discount_code_id, product_id) VALUES (?, ?)");
        foreach ($selected_products as $product_id) {
            $stmt->execute([$code_id, $product_id]);
        }
    }

    $conn->commit();

    $_SESSION['alert'] = ['type' => 'success', 'message' => 'Código de descuento actualizado exitosamente'];
    header("Location: generate_codes.php");
    exit();
} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Error al actualizar código: " . $e->getMessage());
    
    $errorMessage = 'Error al actualizar el código: ' . $e->getMessage();
    if ($_ENV['APP_DEBUG'] ?? false) {
        $errorMessage .= ' (Código: ' . $e->getCode() . ')';
    }
    
    $_SESSION['alert'] = ['type' => 'error', 'message' => $errorMessage];
    header("Location: generate_codes.php?action=generate&edit=" . $code_id);
    exit();
}
?>