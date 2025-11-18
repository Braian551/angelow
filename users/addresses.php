<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../layouts/headerproducts.php';
require_once __DIR__ . '/../layouts/functions.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/users/formuser.php");
    exit();
}

/**
 * Obtiene todas las direcciones de un usuario
 */
function getUserAddresses($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT * FROM user_addresses 
            WHERE user_id = ? 
            ORDER BY is_default DESC, created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener direcciones: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene una dirección específica por ID
 */
function getAddressById($conn, $id, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT * FROM user_addresses 
            WHERE id = ? AND user_id = ? 
            LIMIT 1
        ");
        $stmt->execute([$id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener dirección: " . $e->getMessage());
        return false;
    }
}

/**
 * Guarda una dirección (crea o actualiza)
 */
function saveAddress($conn, $data, $user_id) {
    try {
        // Validación de campos requeridos
        $required = ['alias', 'recipient_name', 'recipient_phone', 'address', 'neighborhood', 'address_type', 'building_type'];
        $missing = [];
        
        foreach ($required as $field) {
            if (empty(trim($data[$field] ?? ''))) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            throw new Exception("Faltan campos requeridos: " . implode(', ', $missing));
        }

        // Validar formato del teléfono
        if (!preg_match('/^[0-9]{7,15}$/', $data['recipient_phone'])) {
            throw new Exception("El teléfono debe contener solo números (7-15 dígitos)");
        }

        // Preparar datos
        $gpsLat = !empty($data['gps_latitude']) ? floatval($data['gps_latitude']) : null;
        $gpsLng = !empty($data['gps_longitude']) ? floatval($data['gps_longitude']) : null;
        $hasGPS = ($gpsLat !== null && $gpsLng !== null);
        
        $params = [
            'address_type' => $data['address_type'],
            'alias' => $data['alias'],
            'recipient_name' => $data['recipient_name'],
            'recipient_phone' => $data['recipient_phone'],
            'address' => $data['address'],
            'complement' => !empty($data['complement']) ? $data['complement'] : null,
            'neighborhood' => $data['neighborhood'],
            'building_type' => $data['building_type'],
            'building_name' => !empty($data['building_name']) ? $data['building_name'] : null,
            'apartment_number' => !empty($data['apartment_number']) ? $data['apartment_number'] : null,
            'delivery_instructions' => !empty($data['delivery_instructions']) ? $data['delivery_instructions'] : null,
            'is_default' => !empty($data['is_default']) ? 1 : 0,
            'user_id' => $user_id,
            // Coordenadas GPS (opcionales)
            'gps_latitude' => $gpsLat,
            'gps_longitude' => $gpsLng,
            'gps_accuracy' => !empty($data['gps_accuracy']) ? floatval($data['gps_accuracy']) : null,
            'gps_timestamp' => $hasGPS ? date('Y-m-d H:i:s') : null,
            'gps_used' => $hasGPS ? 1 : 0  // Indicador de uso de GPS
        ];

        $conn->beginTransaction();

        // Actualizar dirección existente
        if (!empty($data['id'])) {
            $params['id'] = $data['id'];
            
            // Si es la dirección predeterminada, actualizar las demás
            if ($params['is_default']) {
                $stmt = $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ? AND id != ?");
                $stmt->execute([$user_id, $params['id']]);
            }
            
            $stmt = $conn->prepare("
                UPDATE user_addresses SET 
                address_type = :address_type,
                alias = :alias,
                recipient_name = :recipient_name,
                recipient_phone = :recipient_phone,
                address = :address,
                complement = :complement,
                neighborhood = :neighborhood,
                building_type = :building_type,
                building_name = :building_name,
                apartment_number = :apartment_number,
                delivery_instructions = :delivery_instructions,
                is_default = :is_default,
                gps_latitude = :gps_latitude,
                gps_longitude = :gps_longitude,
                gps_accuracy = :gps_accuracy,
                gps_timestamp = :gps_timestamp,
                gps_used = :gps_used,
                updated_at = NOW()
                WHERE id = :id AND user_id = :user_id
            ");
            
            $stmt->execute($params);
            $conn->commit();
            return $params['id'];
        } 
        // Crear nueva dirección
        else {
            if ($params['is_default']) {
                $stmt = $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
                $stmt->execute([$user_id]);
            }
            
            $stmt = $conn->prepare("
                INSERT INTO user_addresses 
                (user_id, address_type, alias, recipient_name, recipient_phone, 
                 address, complement, neighborhood, building_type, building_name,
                 apartment_number, delivery_instructions, is_default, 
                 gps_latitude, gps_longitude, gps_accuracy, gps_timestamp, gps_used,
                 created_at, updated_at) 
                VALUES 
                (:user_id, :address_type, :alias, :recipient_name, :recipient_phone, 
                 :address, :complement, :neighborhood, :building_type, :building_name,
                 :apartment_number, :delivery_instructions, :is_default,
                 :gps_latitude, :gps_longitude, :gps_accuracy, :gps_timestamp, :gps_used,
                 NOW(), NOW())
            ");
            
            $stmt->execute($params);
            $addressId = $conn->lastInsertId();
            $conn->commit();
            return $addressId;
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error en base de datos: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error de validación: " . $e->getMessage());
        return false;
    }
}

/**
 * Elimina una dirección
 */
function deleteAddress($conn, $id, $user_id) {
    try {
        $conn->beginTransaction();
        
        // Verificar si es la dirección predeterminada
        $stmt = $conn->prepare("SELECT is_default FROM user_addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$address) {
            throw new Exception("Dirección no encontrada");
        }
        
        // Eliminar la dirección
        $stmt = $conn->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        
        // Si era la predeterminada, establecer una nueva
        if ($address['is_default']) {
            $stmt = $conn->prepare("SELECT id FROM user_addresses WHERE user_id = ? LIMIT 1");
            $stmt->execute([$user_id]);
            $newDefault = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($newDefault) {
                $stmt = $conn->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ?");
                $stmt->execute([$newDefault['id']]);
            }
        }
        
        $conn->commit();
        return true;
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error al eliminar dirección: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Establece una dirección como predeterminada
 */
function setDefaultAddress($conn, $id, $user_id) {
    try {
        $conn->beginTransaction();
        
        // Quitar el estado de todas las direcciones
        $stmt = $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Establecer la nueva dirección como predeterminada
        $stmt = $conn->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        
        $conn->commit();
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error al establecer dirección predeterminada: " . $e->getMessage());
        return false;
    }
}

// Procesar acciones
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;
$success = false;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'save') {
        $data = [
            'address_type' => $_POST['address_type'] ?? 'casa',
            'alias' => trim($_POST['alias']),
            'recipient_name' => trim($_POST['recipient_name']),
            'recipient_phone' => trim($_POST['recipient_phone']),
            'address' => trim($_POST['address']),
            'complement' => trim($_POST['complement'] ?? ''),
            'neighborhood' => trim($_POST['neighborhood']),
            'building_type' => $_POST['building_type'] ?? 'casa',
            'building_name' => trim($_POST['building_name'] ?? ''),
            'apartment_number' => trim($_POST['apartment_number'] ?? ''),
            'delivery_instructions' => trim($_POST['delivery_instructions'] ?? ''),
            'is_default' => isset($_POST['is_default']) ? 1 : 0,
            'id' => $_POST['id'] ?? null,
            // Coordenadas GPS
            'gps_latitude' => $_POST['gps_latitude'] ?? null,
            'gps_longitude' => $_POST['gps_longitude'] ?? null,
            'gps_accuracy' => $_POST['gps_accuracy'] ?? null
        ];
        
        $result = saveAddress($conn, $data, $_SESSION['user_id']);
        if ($result) {
            $success = true;
            $message = $data['id'] ? "Dirección actualizada correctamente" : "Dirección agregada correctamente";
        } else {
            $message = "Error al guardar la dirección. Verifica los datos e intenta nuevamente.";
        }
    }
} elseif ($action === 'delete' && $id) {
    $result = deleteAddress($conn, $id, $_SESSION['user_id']);
    $success = $result;
    $message = $result ? "Dirección eliminada correctamente" : "Error al eliminar la dirección";
} elseif ($action === 'set_default' && $id) {
    $result = setDefaultAddress($conn, $id, $_SESSION['user_id']);
    $success = $result;
    $message = $result ? "Dirección principal actualizada" : "Error al actualizar la dirección principal";
}

// Obtener direcciones del usuario
$addresses = getUserAddresses($conn, $_SESSION['user_id']);

// Obtener dirección para editar
$editAddress = [];
if ($action === 'edit' && $id) {
    $editAddress = getAddressById($conn, $id, $_SESSION['user_id']);
    if (!$editAddress) {
        $message = "Dirección no encontrada";
        $action = '';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title>Mis Direcciones - Angelow</title>
    <meta name="description" content="Administra tus direcciones de envío en Angelow Ropa Infantil.">
    <link rel="icon" href="<?= BASE_URL ?>/images/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- Leaflet CSS para el mapa GPS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/addresses.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/addresses-gps.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboarduser2.css">
</head>
<body>
    <div class="user-dashboard-container">
        <?php require_once __DIR__ . '/../layouts/asideuser.php'; ?>

        <main class="user-main-content">
            <div class="dashboard-header">
                <h1>Mis Direcciones</h1>
                <p>Administra tus direcciones de envío para una experiencia de compra más rápida.</p>
            </div>

            <!-- Notificación flotante -->
            <?php if (!empty($message)): ?>
                <div class="floating-notification animate__animated animate__fadeInRight <?= $success ? 'success' : 'error' ?>">
                    <div class="notification-content">
                        <i class="fas <?= $success ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                    <button class="close-notification">&times;</button>
                </div>
            <?php endif; ?>

            <div class="addresses-container">
                <!-- Formulario de dirección (se muestra solo en agregar/editar) -->
                <?php if ($action === 'add' || $action === 'edit'): ?>
                    <div class="address-form-container animate__animated animate__fadeIn">
                        <div class="form-header">
                            <h2>
                                <i class="fas <?= $action === 'add' ? 'fa-plus-circle' : 'fa-edit' ?>"></i>
                                <?= $action === 'add' ? 'Agregar Nueva Dirección' : 'Editar Dirección' ?>
                            </h2>
                            <a href="addresses.php" class="btn-back">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                        
                        <form method="POST" action="?action=save" class="address-form">
                            <input type="hidden" name="id" value="<?= $editAddress['id'] ?? '' ?>">
                            
                            <!-- Paso 1: Tipo de dirección -->
                            <div class="form-step active" data-step="1">
                                <div class="step-header">
                                    <span class="step-number">1</span>
                                    <h3>Identifica tu dirección</h3>
                                    <p>Así podrás reconocerla fácilmente</p>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="alias">
                                            <i class="fas fa-tag"></i> Nombre descriptivo *
                                        </label>
                                        <input type="text" id="alias" name="alias" required 
                                               value="<?= htmlspecialchars($editAddress['alias'] ?? '') ?>"
                                               placeholder="Ej: Casa, Oficina, Mi mamá">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="address_type">
                                            <i class="fas fa-home"></i> Tipo de domicilio *
                                        </label>
                                        <select id="address_type" name="address_type" required>
                                            <option value="casa" <?= ($editAddress['address_type'] ?? 'casa') === 'casa' ? 'selected' : '' ?>>Casa</option>
                                            <option value="apartamento" <?= ($editAddress['address_type'] ?? '') === 'apartamento' ? 'selected' : '' ?>>Apartamento</option>
                                            <option value="oficina" <?= ($editAddress['address_type'] ?? '') === 'oficina' ? 'selected' : '' ?>>Oficina</option>
                                            <option value="otro" <?= ($editAddress['address_type'] ?? '') === 'otro' ? 'selected' : '' ?>>Otro</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="step-actions">
                                    <button type="button" class="btn btn-next" data-next="2">Siguiente <i class="fas fa-arrow-right"></i></button>
                                </div>
                            </div>
                            
                            <!-- Paso 2: Información del destinatario -->
                            <div class="form-step" data-step="2">
                                <div class="step-header">
                                    <span class="step-number">2</span>
                                    <h3>Información del destinatario</h3>
                                    <p>¿Quién recibirá los paquetes?</p>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="recipient_name">
                                            <i class="fas fa-user"></i> Nombre del destinatario *
                                        </label>
                                        <input type="text" id="recipient_name" name="recipient_name" required 
                                               value="<?= htmlspecialchars($editAddress['recipient_name'] ?? '') ?>"
                                               placeholder="Nombre completo de quien recibe">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="recipient_phone">
                                            <i class="fas fa-phone"></i> Teléfono del destinatario *
                                        </label>
                                        <input type="tel" id="recipient_phone" name="recipient_phone" required 
                                               value="<?= htmlspecialchars($editAddress['recipient_phone'] ?? '') ?>"
                                               placeholder="Ej: 3013636902">
                                    </div>
                                </div>
                                
                                <div class="step-actions">
                                    <button type="button" class="btn btn-prev" data-prev="1"><i class="fas fa-arrow-left"></i> Anterior</button>
                                    <button type="button" class="btn btn-next" data-next="3">Siguiente <i class="fas fa-arrow-right"></i></button>
                                </div>
                            </div>
                            
                            <!-- Paso 3: Detalles de la dirección -->
                            <div class="form-step" data-step="3">
                                <div class="step-header">
                                    <span class="step-number">3</span>
                                    <h3>Detalles de la dirección</h3>
                                    <p>¿Dónde debemos entregar tus pedidos?</p>
                                </div>
                                
                                <!-- Botón GPS -->
                                <div class="form-group">
                                    <button type="button" class="btn-gps" id="btn-open-gps">
                                        <i class="fas fa-map-marked-alt"></i>
                                        <span>Usar mi ubicación GPS</span>
                                    </button>
                                    <p style="font-size: 1.2rem; color: #666; margin-top: 0.5rem; text-align: center;">
                                        <i class="fas fa-info-circle"></i> Selecciona tu ubicación en el mapa de forma precisa
                                    </p>
                                </div>
                                
                                <div class="form-group">
                                    <label for="address">
                                        <i class="fas fa-map-marker-alt"></i> Dirección completa *
                                    </label>
                                    <input type="text" id="address" name="address" required 
                                           value="<?= htmlspecialchars($editAddress['address'] ?? '') ?>"
                                           placeholder="Ej: Calle 10 # 20-30, Cra 80 # 25-15">
                                </div>
                                
                                <div class="form-group">
                                    <label for="complement">
                                        <i class="fas fa-map-signs"></i> Complemento (opcional)
                                    </label>
                                    <input type="text" id="complement" name="complement" 
                                           value="<?= htmlspecialchars($editAddress['complement'] ?? '') ?>"
                                           placeholder="Ej: Bloque 2, Torre A, Portería">
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="neighborhood">
                                            <i class="fas fa-city"></i> Barrio *
                                        </label>
                                        <input type="text" id="neighborhood" name="neighborhood" required 
                                               value="<?= htmlspecialchars($editAddress['neighborhood'] ?? '') ?>"
                                               placeholder="Ej: Belén, Poblado, Laureles">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="building_type">
                                            <i class="fas fa-building"></i> Tipo de construcción *
                                        </label>
                                        <select id="building_type" name="building_type" required>
                                            <option value="casa" <?= ($editAddress['building_type'] ?? 'casa') === 'casa' ? 'selected' : '' ?>>Casa</option>
                                            <option value="apartamento" <?= ($editAddress['building_type'] ?? '') === 'apartamento' ? 'selected' : '' ?>>Apartamento</option>
                                            <option value="edificio" <?= ($editAddress['building_type'] ?? '') === 'edificio' ? 'selected' : '' ?>>Edificio</option>
                                            <option value="conjunto" <?= ($editAddress['building_type'] ?? '') === 'conjunto' ? 'selected' : '' ?>>Conjunto Residencial</option>
                                            <option value="local" <?= ($editAddress['building_type'] ?? '') === 'local' ? 'selected' : '' ?>>Local Comercial</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="building_name">
                                            <i class="fas fa-hotel"></i> Nombre del edificio/conjunto (opcional)
                                        </label>
                                        <input type="text" id="building_name" name="building_name" 
                                               value="<?= htmlspecialchars($editAddress['building_name'] ?? '') ?>"
                                               placeholder="Ej: Edificio Torre Poblado, Conjunto Las Lomas">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="apartment_number">
                                            <i class="fas fa-door-open"></i> Número de apartamento/oficina (opcional)
                                        </label>
                                        <input type="text" id="apartment_number" name="apartment_number" 
                                               value="<?= htmlspecialchars($editAddress['apartment_number'] ?? '') ?>"
                                               placeholder="Ej: 201, Oficina 502">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="delivery_instructions">
                                        <i class="fas fa-info-circle"></i> Indicaciones para la entrega (opcional)
                                    </label>
                                    <textarea id="delivery_instructions" name="delivery_instructions" 
                                              placeholder="Ej: Llamar antes de llegar, Portería azul, Dejar con el vigilante"><?= 
                                        htmlspecialchars($editAddress['delivery_instructions'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="form-group checkbox-group">
                               
                                    <label for="is_default">
                                             <input type="checkbox" id="is_default" name="is_default" 
                                        <?= (isset($editAddress['is_default']) && $editAddress['is_default']) || empty($addresses) ? 'checked' : '' ?>>
                                       Establecer como dirección principal
                                    </label>
                                </div>
                                
                                <div class="step-actions">
                                    <button type="button" class="btn btn-prev" data-prev="2"><i class="fas fa-arrow-left"></i> Anterior</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> <?= $action === 'add' ? 'Agregar Dirección' : 'Actualizar Dirección' ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Listado de direcciones -->
                    <div class="addresses-list-container animate__animated animate__fadeIn">
                        <div class="addresses-header">
                            <h2>
                                <i class="fas fa-map-marked-alt"></i> Mis Direcciones Guardadas
                            </h2>
                            <a href="?action=add" class="btn btn-primary btn-add-address">
                                <i class="fas fa-plus-circle"></i> Agregar Nueva Dirección
                            </a>
                        </div>
                        
                        <?php if (empty($addresses)): ?>
                            <div class="no-addresses animate__animated animate__pulse">
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <h3>Aún no tienes direcciones guardadas</h3>
                                    <p>Agrega tu primera dirección para recibir tus pedidos</p>
                                    <a href="?action=add" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Agregar mi primera dirección
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="addresses-grid">
                                <?php foreach ($addresses as $address): ?>
                                    <div class="address-card <?= $address['is_default'] ? 'default-address' : '' ?> animate__animated animate__fadeInUp" data-id="<?= $address['id'] ?>">
                                        <div class="address-header">
                                            <div class="address-icon">
                                                <?php if ($address['address_type'] === 'casa'): ?>
                                                    <i class="fas fa-home"></i>
                                                <?php elseif ($address['address_type'] === 'apartamento'): ?>
                                                    <i class="fas fa-building"></i>
                                                <?php elseif ($address['address_type'] === 'oficina'): ?>
                                                    <i class="fas fa-briefcase"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-map-marker-alt"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="address-title">
                                                <h3><?= htmlspecialchars($address['alias']) ?></h3>
                                                <span class="address-type"><?= ucfirst($address['address_type']) ?></span>
                                            </div>
                                            <?php if ($address['is_default']): ?>
                                                <div class="default-badge">
                                                    <i class="fas fa-star"></i> Principal
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="address-details">
                                            <div class="detail-item">
                                                <i class="fas fa-user"></i>
                                                <p><?= htmlspecialchars($address['recipient_name']) ?> (<?= htmlspecialchars($address['recipient_phone']) ?>)</p>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <p><?= htmlspecialchars($address['address']) ?></p>
                                            </div>
                                            <?php if (!empty($address['complement'])): ?>
                                                <div class="detail-item">
                                                    <i class="fas fa-plus-circle"></i>
                                                    <p><?= htmlspecialchars($address['complement']) ?></p>
                                                </div>
                                            <?php endif; ?>
                                            <div class="detail-item">
                                                <i class="fas fa-city"></i>
                                                <p><?= htmlspecialchars($address['neighborhood']) ?></p>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-building"></i>
                                                <p><?= ucfirst($address['building_type']) ?>
                                                <?php if (!empty($address['building_name'])): ?>
                                                    (<?= htmlspecialchars($address['building_name']) ?>)
                                                <?php endif; ?>
                                                </p>
                                            </div>
                                            <?php if (!empty($address['apartment_number'])): ?>
                                                <div class="detail-item">
                                                    <i class="fas fa-door-open"></i>
                                                    <p><?= htmlspecialchars($address['apartment_number']) ?></p>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($address['delivery_instructions'])): ?>
                                                <div class="detail-item">
                                                    <i class="fas fa-info-circle"></i>
                                                    <p><?= htmlspecialchars($address['delivery_instructions']) ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="address-actions">
                                            <?php if (!$address['is_default']): ?>
                                                <a href="?action=set_default&id=<?= $address['id'] ?>" class="btn btn-sm btn-secondary btn-set-default">
                                                    <i class="fas fa-star"></i> Establecer como principal
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="?action=edit&id=<?= $address['id'] ?>" class="btn btn-sm btn-outline btn-edit">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            
                                            <a href="?action=delete&id=<?= $address['id'] ?>" 
                                               class="btn btn-sm btn-outline btn-danger btn-delete"
                                               onclick="return confirm('¿Estás seguro de eliminar esta dirección?');">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php includeFromRoot('layouts/footer.php'); ?>
    
    <!-- Leaflet JS para el mapa GPS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <!-- GPS Address Picker Script -->
    <script src="<?= BASE_URL ?>/js/addresses-gps.js"></script>
    
    <script>
        $(document).ready(function() {
            // Cerrar notificación
            $('.close-notification').click(function() {
                $(this).parent().addClass('animate__fadeOutRight');
                setTimeout(() => {
                    $(this).parent().remove();
                }, 500);
            });
            
            // Auto cerrar notificación después de 5 segundos
            setTimeout(() => {
                $('.floating-notification').addClass('animate__fadeOutRight');
                setTimeout(() => {
                    $('.floating-notification').remove();
                }, 500);
            }, 5000);
            
            // Navegación por pasos del formulario
            $('.btn-next').click(function() {
                const nextStep = $(this).data('next');
                const currentStep = $(this).closest('.form-step').data('step');
                
                // Validar campos requeridos
                let isValid = true;
                $(`.form-step[data-step="${currentStep}"] [required]`).each(function() {
                    if (!$(this).val()) {
                        isValid = false;
                        $(this).addClass('error');
                        $(this).next('.error-message').remove();
                        $(this).after('<span class="error-message">Este campo es requerido</span>');
                    } else {
                        $(this).removeClass('error');
                        $(this).next('.error-message').remove();
                    }
                });
                
                if (isValid) {
                    $(`.form-step[data-step="${currentStep}"]`).removeClass('active').addClass('animate__animated animate__fadeOutLeft');
                    setTimeout(() => {
                        $(`.form-step[data-step="${currentStep}"]`).hide();
                        $(`.form-step[data-step="${nextStep}"]`).addClass('active animate__animated animate__fadeInRight').show();
                    }, 300);
                }
            });
            
            $('.btn-prev').click(function() {
                const prevStep = $(this).data('prev');
                const currentStep = $(this).closest('.form-step').data('step');
                
                $(`.form-step[data-step="${currentStep}"]`).removeClass('active').addClass('animate__animated animate__fadeOutRight');
                setTimeout(() => {
                    $(`.form-step[data-step="${currentStep}"]`).hide();
                    $(`.form-step[data-step="${prevStep}"]`).addClass('active animate__animated animate__fadeInLeft').show();
                }, 300);
            });
            
            // Efecto hover en tarjetas de dirección
            $('.address-card').hover(
                function() {
                    $(this).addClass('animate__animated animate__pulse');
                },
                function() {
                    $(this).removeClass('animate__animated animate__pulse');
                }
            );
            
            // Seleccionar dirección para compras futuras y establecer como principal vía AJAX
            $('.address-card').click(function(e) {
                if (!$(e.target).closest('.address-actions a').length) {
                    const $card = $(this);
                    const addressId = $card.data('id') || $card.find('.address-actions a').attr('href').match(/id=(\d+)/)[1];

                    // Guardar en localStorage para futuras compras (protegido por try/catch)
                    try {
                        localStorage.setItem('selectedAddressId', addressId);
                    } catch (e) {
                        console.warn('localStorage.setItem blocked or unavailable', e);
                    }

                    // Feedback visual
                    $('.address-card').removeClass('selected-address');
                    $card.addClass('selected-address animate__animated animate__pulse');

                    // Llamada AJAX para establecer como principal
                    $.post('ajax_set_default.php', { id: addressId }, function(resp) {
                        try {
                            const data = typeof resp === 'object' ? resp : JSON.parse(resp);
                            if (data.success) {
                                // Actualizar badges y clases
                                $('.address-card').removeClass('default-address');
                                $('.address-card .default-badge').remove();

                                $card.addClass('default-address');
                                $card.find('.address-header').append('<div class="default-badge"><i class="fas fa-star"></i> Principal</div>');

                                showNotification('Dirección seleccionada para tus próximas compras, ahora es tu dirección principal', 'success');
                            } else {
                                showNotification(data.message || 'No se pudo establecer como principal', 'error');
                            }
                        } catch (err) {
                            showNotification('Respuesta inesperada del servidor', 'error');
                        }
                    }).fail(function(jqXHR, textStatus, errorThrown) {
                        // Log detallado para depuración
                        console.error('AJAX error:', textStatus, errorThrown);
                        console.error('Response:', jqXHR.responseText);

                        // Intentar mostrar mensaje enviado por el servidor (JSON)
                        let serverMessage = null;
                        try {
                            const parsed = JSON.parse(jqXHR.responseText);
                            serverMessage = parsed.message || parsed.error || null;
                        } catch (e) {
                            // no es JSON
                        }

                        if (serverMessage) {
                            showNotification(serverMessage, 'error');
                        } else {
                            showNotification('Error al comunicarse con el servidor (ver consola para más detalles)', 'error');
                        }
                    });
                }
            });
            
            // Resaltar la dirección seleccionada si existe
            let selectedAddressId = null;
            try {
                selectedAddressId = localStorage.getItem('selectedAddressId');
            } catch (e) {
                console.warn('localStorage.getItem blocked or unavailable', e);
            }
            if (selectedAddressId) {
                $(`.address-actions a[href*="id=${selectedAddressId}"]`).closest('.address-card').addClass('selected-address');
            }
            
            // Función para mostrar notificaciones
            function showNotification(message, type) {
                const notification = $(`
                    <div class="floating-notification animate__animated animate__fadeInRight ${type}">
                        <div class="notification-content">
                            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                            <span>${message}</span>
                        </div>
                        <button class="close-notification">&times;</button>
                    </div>
                `);
                
                $('.user-main-content').append(notification);
                
                // Auto cerrar después de 3 segundos
                setTimeout(() => {
                    notification.addClass('animate__fadeOutRight');
                    setTimeout(() => notification.remove(), 500);
                }, 3000);
                
                // Cerrar manualmente
                notification.find('.close-notification').click(function() {
                    notification.addClass('animate__fadeOutRight');
                    setTimeout(() => notification.remove(), 500);
                });
            }
        });
    </script>
</body>
</html>