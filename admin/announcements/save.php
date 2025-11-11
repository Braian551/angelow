<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../auth/role_redirect.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/announcements/list.php');
    exit;
}

try {
    $id = isset($_POST['id']) && !empty($_POST['id']) ? intval($_POST['id']) : null;
    $type = trim($_POST['type']);
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $subtitle = !empty($_POST['subtitle']) ? trim($_POST['subtitle']) : null;
    $button_text = !empty($_POST['button_text']) ? trim($_POST['button_text']) : null;
    $button_link = !empty($_POST['button_link']) ? trim($_POST['button_link']) : null;
    
    $icon = !empty($_POST['icon']) ? trim($_POST['icon']) : null;
    $priority = intval($_POST['priority']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

    // Validaciones
    if (empty($type) || !in_array($type, ['top_bar', 'promo_banner'])) {
        throw new Exception('Tipo de anuncio inválido.');
    }
    if (empty($title) || empty($message)) {
        throw new Exception('El título y el mensaje son obligatorios.');
    }
    if (empty($icon)) {
        throw new Exception('Debes seleccionar un icono.');
    }
    
    // Validar que no haya más de 2 anuncios (solo al crear)
    if (!$id) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM announcements");
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($count >= 2) {
            throw new Exception('Ya existen 2 anuncios. Debes eliminar uno antes de agregar otro.');
        }
    }

    // Manejo de imagen
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $max_size = 3 * 1024 * 1024; // 3MB

        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            throw new Exception('Formato de imagen no permitido. Use JPG, PNG o WEBP.');
        }
        if ($_FILES['image']['size'] > $max_size) {
            throw new Exception('La imagen no debe superar 3MB.');
        }

        // Crear directorio si no existe
        $upload_dir = __DIR__ . '/../../uploads/announcements';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generar nombre único
        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'announcement_' . time() . '_' . uniqid() . '.' . $extension;
        $upload_path = $upload_dir . '/' . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $image_path = 'uploads/announcements/' . $filename;

            // Si es edición, eliminar imagen anterior
            if ($id) {
                $stmt = $conn->prepare("SELECT image FROM announcements WHERE id = ?");
                $stmt->execute([$id]);
                $old_image = $stmt->fetchColumn();
                if ($old_image && file_exists(__DIR__ . '/../../' . $old_image)) {
                    unlink(__DIR__ . '/../../' . $old_image);
                }
            }
        } else {
            throw new Exception('Error al subir la imagen.');
        }
    }

    if ($id) {
        // Actualizar anuncio existente
        $sql = "UPDATE announcements SET 
                type = ?, title = ?, message = ?, subtitle = ?, 
                button_text = ?, button_link = ?, icon = ?, priority = ?, is_active = ?, 
                start_date = ?, end_date = ?";
        
        $params = [$type, $title, $message, $subtitle, $button_text, $button_link, 
                   $icon, $priority, $is_active, $start_date, $end_date];
        
        if ($image_path) {
            $sql .= ", image = ?";
            $params[] = $image_path;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        $_SESSION['alert'] = [
            'message' => 'Anuncio actualizado exitosamente.',
            'type' => 'success'
        ];
    } else {
        // Crear nuevo anuncio
        $sql = "INSERT INTO announcements 
                (type, title, message, subtitle, button_text, button_link, 
                 image, icon, priority, is_active, start_date, end_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $type, $title, $message, $subtitle, $button_text, $button_link,
            $image_path, $icon, $priority, $is_active, $start_date, $end_date
        ]);

        $_SESSION['alert'] = [
            'message' => 'Anuncio creado exitosamente.',
            'type' => 'success'
        ];
    }

    header('Location: ' . BASE_URL . '/admin/announcements/list.php');
    exit;

} catch (Exception $e) {
    $_SESSION['alert'] = [
        'message' => 'Error: ' . $e->getMessage(),
        'type' => 'error'
    ];
    header('Location: ' . BASE_URL . '/admin/announcements/' . ($id ? 'edit.php?id=' . $id : 'add.php'));
    exit;
}
