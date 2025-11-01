<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../auth/role_redirect.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Método no permitido'];
    header('Location: ' . BASE_URL . '/admin/sliders/sliders_list.php');
    exit();
}

$id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$subtitle = isset($_POST['subtitle']) ? trim($_POST['subtitle']) : null;
$link = isset($_POST['link']) ? trim($_POST['link']) : null;
$order_position = isset($_POST['order_position']) ? (int)$_POST['order_position'] : 1;
$is_active = isset($_POST['is_active']) ? 1 : 0;

if ($title === '') {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'El título es obligatorio'];
    header('Location: ' . BASE_URL . '/admin/sliders/sliders_list.php');
    exit();
}

$imagePath = null;
$hasUpload = isset($_FILES['image']) && isset($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name']);

if ($hasUpload) {
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/avif' => 'avif'];
    $mime = mime_content_type($_FILES['image']['tmp_name']);
    $size = (int)$_FILES['image']['size'];
    
    if (!isset($allowed[$mime])) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Formato de imagen no permitido'];
        header('Location: ' . BASE_URL . '/admin/sliders/sliders_list.php');
        exit();
    }
    if ($size > 15 * 1024 * 1024) { // 15MB
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'La imagen excede 15MB'];
        header('Location: ' . BASE_URL . '/admin/sliders/sliders_list.php');
        exit();
    }
    
    $ext = $allowed[$mime];
    $filename = 'slider_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destDir = __DIR__ . '/../../uploads/sliders';
    if (!is_dir($destDir)) { @mkdir($destDir, 0777, true); }
    $destPath = $destDir . '/' . $filename;
    
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'No se pudo guardar la imagen'];
        header('Location: ' . BASE_URL . '/admin/sliders/sliders_list.php');
        exit();
    }
    
    $imagePath = 'uploads/sliders/' . $filename;
}

try {
    if ($id) {
        // Update
        if ($imagePath) {
            // Eliminar imagen anterior
            $stmt = $conn->prepare('SELECT image FROM sliders WHERE id = ?');
            $stmt->execute([$id]);
            $old = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($old && !empty($old['image'])) {
                $oldPath = __DIR__ . '/../../' . $old['image'];
                if (file_exists($oldPath)) @unlink($oldPath);
            }
            
            $sql = 'UPDATE sliders SET title = ?, subtitle = ?, image = ?, link = ?, order_position = ?, is_active = ?, updated_at = NOW() WHERE id = ?';
            $params = [$title, $subtitle, $imagePath, $link, $order_position, $is_active, $id];
        } else {
            $sql = 'UPDATE sliders SET title = ?, subtitle = ?, link = ?, order_position = ?, is_active = ?, updated_at = NOW() WHERE id = ?';
            $params = [$title, $subtitle, $link, $order_position, $is_active, $id];
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Slider actualizado correctamente'];
    } else {
        // Insert
        if (!$imagePath) {
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'La imagen es obligatoria al crear un nuevo slider'];
            header('Location: ' . BASE_URL . '/admin/sliders/sliders_list.php');
            exit();
        }
        
        $sql = 'INSERT INTO sliders (title, subtitle, image, link, order_position, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())';
        $stmt = $conn->prepare($sql);
        $stmt->execute([$title, $subtitle, $imagePath, $link, $order_position, $is_active]);
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Slider creado correctamente'];
    }
    
    header('Location: ' . BASE_URL . '/admin/sliders/sliders_list.php');
    exit();
    
} catch (PDOException $e) {
    error_log('save_slider error: ' . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al guardar el slider'];
    header('Location: ' . BASE_URL . '/admin/sliders/sliders_list.php');
    exit();
}
