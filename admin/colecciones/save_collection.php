<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';

// Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$userId = $_SESSION['user_id'];
$query = "SELECT role FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Función para generar slug
function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[áàäâ]/', 'a', $text);
    $text = preg_replace('/[éèëê]/', 'e', $text);
    $text = preg_replace('/[íìïî]/', 'i', $text);
    $text = preg_replace('/[óòöô]/', 'o', $text);
    $text = preg_replace('/[úùüû]/', 'u', $text);
    $text = preg_replace('/ñ/', 'n', $text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

// Función para manejar la subida de imagen
function handleImageUpload($file, $currentImage = null) {
    $uploadDir = __DIR__ . '/../../uploads/collections/';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/avif'];
    $maxSize = 15 * 1024 * 1024; // 15MB
    
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return $currentImage;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo');
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Tipo de archivo no permitido. Solo JPG, PNG, WebP o AVIF');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('El archivo es demasiado grande. Máximo 15MB');
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'collection_' . time() . rand(1000, 9999) . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Error al mover el archivo');
    }
    
    // Eliminar imagen anterior si existe
    if ($currentImage && file_exists(__DIR__ . '/../../' . $currentImage)) {
        unlink(__DIR__ . '/../../' . $currentImage);
    }
    
    return 'uploads/collections/' . $filename;
}

try {
    $collectionId = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $launchDate = $_POST['launch_date'] ?? null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $currentImage = $_POST['current_image'] ?? null;
    
    // Validaciones
    if (empty($name)) {
        throw new Exception('El nombre es obligatorio');
    }
    
    if (empty($slug)) {
        $slug = generateSlug($name);
    } else {
        $slug = generateSlug($slug);
    }
    
    // Validar fecha de lanzamiento
    if (!empty($launchDate)) {
        $date = DateTime::createFromFormat('Y-m-d', $launchDate);
        if (!$date || $date->format('Y-m-d') !== $launchDate) {
            throw new Exception('Fecha de lanzamiento inválida');
        }
    } else {
        $launchDate = null;
    }
    
    // Manejar imagen
    $imagePath = $currentImage;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $imagePath = handleImageUpload($_FILES['image'], $currentImage);
    }
    
    // Verificar si el slug ya existe (excluyendo la colección actual en edición)
    $slugCheckSql = "SELECT id FROM collections WHERE slug = ?";
    $slugCheckParams = [$slug];
    
    if ($collectionId) {
        $slugCheckSql .= " AND id != ?";
        $slugCheckParams[] = $collectionId;
    }
    
    $slugCheckStmt = $conn->prepare($slugCheckSql);
    $slugCheckStmt->execute($slugCheckParams);
    
    if ($slugCheckStmt->fetch()) {
        throw new Exception('El slug ya está en uso. Por favor, usa otro nombre');
    }
    
    if ($collectionId) {
        // Actualizar colección existente
        $sql = "UPDATE collections 
                SET name = ?, 
                    slug = ?, 
                    description = ?, 
                    image = ?, 
                    launch_date = ?, 
                    is_active = ?,
                    updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $name,
            $slug,
            $description,
            $imagePath,
            $launchDate,
            $isActive,
            $collectionId
        ]);
        
        $message = 'Colección actualizada exitosamente';
    } else {
        // Crear nueva colección
        $sql = "INSERT INTO collections (name, slug, description, image, launch_date, is_active) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $name,
            $slug,
            $description,
            $imagePath,
            $launchDate,
            $isActive
        ]);
        
        $collectionId = $conn->lastInsertId();
        $message = 'Colección creada exitosamente';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'collection_id' => $collectionId
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
