<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../auth/role_redirect.php';

requireRole('admin');

function slugify($text) {
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    $text = preg_replace('~[^-a-z0-9]+~', '', $text);
    return $text ?: 'noticia-' . time();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Método no permitido'];
    header('Location: ' . BASE_URL . '/admin/news/news_list.php');
    exit();
}

$id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$is_active = isset($_POST['is_active']) ? 1 : 0;
$is_featured = isset($_POST['is_featured']) ? 1 : 0;
$published_at = !empty($_POST['published_at']) ? date('Y-m-d H:i:s', strtotime($_POST['published_at'])) : null;

if ($title === '' || $content === '') {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Título y contenido son obligatorios'];
    header('Location: ' . BASE_URL . '/admin/news/news_list.php');
    exit();
}

$imagePath = null;
$hasUpload = isset($_FILES['image']) && isset($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name']);
if ($hasUpload) {
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = mime_content_type($_FILES['image']['tmp_name']);
    $size = (int)$_FILES['image']['size'];
    if (!isset($allowed[$mime])) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Formato de imagen no permitido'];
        header('Location: ' . BASE_URL . '/admin/news/news_list.php');
        exit();
    }
    if ($size > 3 * 1024 * 1024) { // 3MB
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'La imagen excede 3MB'];
        header('Location: ' . BASE_URL . '/admin/news/news_list.php');
        exit();
    }
    $ext = $allowed[$mime];
    $filename = 'news_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destDir = __DIR__ . '/../../uploads/news';
    if (!is_dir($destDir)) { @mkdir($destDir, 0777, true); }
    $destPath = $destDir . '/' . $filename;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'No se pudo guardar la imagen'];
        header('Location: ' . BASE_URL . '/admin/news/news_list.php');
        exit();
    }
    // Ruta relativa desde raíz del proyecto
    $imagePath = 'uploads/news/' . $filename;
}

try {
    if ($id) {
        // Update
        if ($imagePath) {
            $sql = 'UPDATE news SET title = ?, slug = ?, content = ?, image = ?, is_featured = ?, is_active = ?, published_at = ?, updated_at = NOW() WHERE id = ?';
            $params = [$title, slugify($title), $content, $imagePath, $is_featured, $is_active, $published_at, $id];
        } else {
            $sql = 'UPDATE news SET title = ?, slug = ?, content = ?, is_featured = ?, is_active = ?, published_at = ?, updated_at = NOW() WHERE id = ?';
            $params = [$title, slugify($title), $content, $is_featured, $is_active, $published_at, $id];
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Noticia actualizada correctamente'];
        header('Location: ' . BASE_URL . '/admin/news/news_list.php');
        exit();
    } else {
        // Insert
        $sql = 'INSERT INTO news (title, slug, content, image, is_featured, is_active, published_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())';
        $stmt = $conn->prepare($sql);
        $stmt->execute([$title, slugify($title), $content, $imagePath, $is_featured, $is_active, $published_at]);
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Noticia creada correctamente'];
        header('Location: ' . BASE_URL . '/admin/news/news_list.php');
        exit();
    }
} catch (PDOException $e) {
    error_log('save_news error: ' . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al guardar la noticia'];
    header('Location: ' . BASE_URL . '/admin/news/news_list.php');
    exit();
}
