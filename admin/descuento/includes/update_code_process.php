<?php
// admin/descuento/includes/update_code_process.php

// Debug: mostrar datos recibidos
error_log("POST data recibida para actualización: " . print_r($_POST, true));

$code_id = $_POST['code_id'] ?? null;
$discount_type = $_POST['discount_type'] ?? ($_POST['discount_type_display'] ?? null);
$discount_value = null;
$max_uses = $_POST['max_uses'] ?: null;
$start_date = $_POST['start_date'] ?: null;
$end_date = $_POST['end_date'] ?: null;
$is_single_use = isset($_POST['is_single_use']) ? 1 : 0;
$is_active = isset($_POST['is_active']) ? 1 : 0;
$apply_to_all = isset($_POST['apply_to_all']) ? 1 : 0;
$selected_products = json_decode($_POST['products'] ?? '[]', true) ?: [];
// Usuarios seleccionados para notificación (puede venir vacío)
$selected_users = json_decode($_POST['selected_users'] ?? '[]', true) ?: [];
// Flag para enviar notificación
$send_notification = isset($_POST['send_notification']) ? true : false;

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

    // Después de confirmar la transacción, enviar notificaciones si corresponde
    $sent = 0;
    $failed = 0;

    if ($send_notification && !empty($selected_users)) {
        // Incluir la función de envío
        $sendFile = __DIR__ . '/../../api/descuento/send_discount_email.php';
        if (file_exists($sendFile)) {
            require_once $sendFile;

            // Obtener el código si no lo tenemos
            if (empty($code)) {
                $q = $conn->prepare("SELECT code FROM discount_codes WHERE id = ?");
                $q->execute([$code_id]);
                $row = $q->fetch(PDO::FETCH_ASSOC);
                $code = $row['code'] ?? '';
            }

            // Obtener nombre tipo descuento si es posible
            $dtypeName = $discount_type;
            try {
                $q2 = $conn->prepare("SELECT name FROM discount_types WHERE id = ?");
                $q2->execute([$discount_type]);
                $dtypeName = $q2->fetchColumn() ?: $discount_type;
            } catch (Exception $e) {
                // ignorar, usar id si no hay nombre
            }

            // Generar PDF en memoria para adjuntar
            try {
                require_once __DIR__ . '/../../../vendor/autoload.php';
                $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'LETTER', true, 'UTF-8', false);
                $pdf->SetCreator(PDF_CREATOR);
                $pdf->SetAuthor('Angelow Ropa Infantil');
                $pdf->SetTitle('Código de Descuento ' . ($code ?: ''));
                $pdf->SetSubject('Código de Descuento');
                $pdf->SetMargins(15, 25, 15);
                $pdf->SetHeaderMargin(10);
                $pdf->SetFooterMargin(15);
                $pdf->setPrintFooter(true);
                $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
                $pdf->SetFont('helvetica', '', 10);
                $logoPath = __DIR__ . '/../../../images/logo2.png';
                $logoExists = file_exists($logoPath);
                $pdf->AddPage();

                $html = '<div style="text-align:center;">';
                if ($logoExists) {
                    $html .= '<img src="' . $logoPath . '" width="180"/>';
                } else {
                    $html .= '<h1>Angelow Ropa Infantil</h1>';
                }
                $html .= '<h2 style="color:#006699;">CÓDIGO DE DESCUENTO</h2>';
                $html .= '<div style="font-size:28px;color:#006699;margin:10px 0;font-weight:bold;">' . htmlspecialchars($code) . '</div>';
                $html .= '<div style="font-size:18px;color:#FF6600;margin-bottom:10px;">' . htmlspecialchars($dtypeName) . ' ' . htmlspecialchars($discount_value) . '%</div>';
                if ($end_date) {
                    $html .= '<div style="font-style:italic;color:#666;">Válido hasta: ' . date('d/m/Y', strtotime($end_date)) . '</div>';
                } else {
                    $html .= '<div style="font-style:italic;color:#666;">Sin fecha de expiración</div>';
                }
                $html .= '</div>';

                $pdf->writeHTML($html, true, false, true, false, '');
                $pdfContent = $pdf->Output('codigo_descuento_' . $code . '.pdf', 'S');
                $pdfFilename = 'codigo_descuento_' . $code . '.pdf';
            } catch (Exception $e) {
                error_log('Error generando PDF para adjuntar: ' . $e->getMessage());
                $pdfContent = null;
                $pdfFilename = null;
            }

            foreach ($selected_users as $userId) {
                try {
                    $ok = sendDiscountEmail($userId, $code, $dtypeName, $discount_value, $end_date, $pdfContent, $pdfFilename);
                    if ($ok) $sent++; else $failed++;
                } catch (Exception $e) {
                    error_log('Error enviando email a usuario ' . $userId . ': ' . $e->getMessage());
                    $failed++;
                }
            }
        } else {
            error_log('No se encontró el archivo de envío de emails: ' . $sendFile);
        }
    }

    // Preparar mensaje final
    $message = 'Código de descuento actualizado exitosamente.';
    if ($send_notification) {
        $message .= ' Correos enviados: ' . $sent . '. Fallidos: ' . $failed . '.';
    }

    $_SESSION['alert'] = ['type' => 'success', 'message' => $message];
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