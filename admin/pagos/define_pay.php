<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../alertas/alerta1.php';

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Debes iniciar sesión para acceder a esta página'];
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

// Verificar rol de administrador
try {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'admin') {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'No tienes permisos para acceder a esta área'];
        header("Location: " . BASE_URL . "/index.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error de permisos: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al verificar permisos. Por favor intenta nuevamente.'];
    header("Refresh:0");
    exit();
}

// Obtener configuración actual de cuenta bancaria
function obtenerConfiguracionCuenta($conn) {
    $sql = "SELECT bac.*, cb.bank_name 
            FROM bank_account_config bac 
            LEFT JOIN colombian_banks cb ON bac.bank_code = cb.bank_code 
            WHERE bac.is_active = 1 
            ORDER BY bac.created_at DESC 
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$configuracionActual = obtenerConfiguracionCuenta($conn);

// Obtener lista de bancos
function obtenerBancos($conn) {
    $sql = "SELECT * FROM colombian_banks WHERE is_active = 1 ORDER BY bank_name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$bancos = obtenerBancos($conn);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_account') {
        $bank_code = filter_input(INPUT_POST, 'bank_code', FILTER_SANITIZE_SPECIAL_CHARS);
        $account_number = filter_input(INPUT_POST, 'account_number', FILTER_SANITIZE_SPECIAL_CHARS);
        $account_type = filter_input(INPUT_POST, 'account_type', FILTER_SANITIZE_SPECIAL_CHARS);
        $account_holder = filter_input(INPUT_POST, 'account_holder', FILTER_SANITIZE_SPECIAL_CHARS);
        $identification_type = filter_input(INPUT_POST, 'identification_type', FILTER_SANITIZE_SPECIAL_CHARS);
        $identification_number = filter_input(INPUT_POST, 'identification_number', FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Validaciones
        if (empty($bank_code) || empty($account_number) || empty($account_holder) || empty($identification_number)) {
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Todos los campos marcados con * son requeridos'];
            header("Location: define_pay.php");
            exit();
        }

        try {
            // Desactivar todas las cuentas existentes si estamos activando una nueva
            if ($is_active) {
                $stmt = $conn->prepare("UPDATE bank_account_config SET is_active = 0");
                $stmt->execute();
            }

            // Si ya existe una configuración, actualizarla, sino crear nueva
            if ($configuracionActual) {
                $stmt = $conn->prepare("UPDATE bank_account_config SET 
                    bank_code = ?, account_number = ?, account_type = ?, account_holder = ?, 
                    identification_type = ?, identification_number = ?, email = ?, phone = ?, is_active = ?
                    WHERE id = ?");
                $stmt->execute([
                    $bank_code, $account_number, $account_type, $account_holder,
                    $identification_type, $identification_number, $email, $phone, $is_active,
                    $configuracionActual['id']
                ]);
                $message = 'Configuración de cuenta actualizada exitosamente';
            } else {
                $stmt = $conn->prepare("INSERT INTO bank_account_config 
                    (bank_code, account_number, account_type, account_holder, identification_type, 
                    identification_number, email, phone, is_active, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $bank_code, $account_number, $account_type, $account_holder,
                    $identification_type, $identification_number, $email, $phone, $is_active,
                    $_SESSION['user_id']
                ]);
                $message = 'Configuración de cuenta creada exitosamente';
            }

            $_SESSION['alert'] = ['type' => 'success', 'message' => $message];
            header("Location: define_pay.php");
            exit();
        } catch (PDOException $e) {
            error_log("Error al guardar configuración de cuenta: " . $e->getMessage());
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al guardar la configuración. Por favor intenta nuevamente.'];
            header("Location: define_pay.php");
            exit();
        }
    }
}

// Mostrar alerta almacenada en sesión si existe
if (isset($_SESSION['alert'])) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() {
        showAlert('" . addslashes($_SESSION['alert']['message']) . "', '" . $_SESSION['alert']['type'] . "');
    });</script>";
    unset($_SESSION['alert']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Cuenta Bancaria - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/pagos/define_pay.css">
</head>

<body>
    <div class="admin-container">
        <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <h1>
                        <i class="fas fa-university"></i> Configuración de Cuenta Bancaria
                    </h1>
                    <div class="breadcrumb">
                        <a href="<?= BASE_URL ?>/admin">Dashboard</a> / <span>Pagos</span> / <span>Cuenta bancaria</span>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Información de la Cuenta para Transferencias</h3>
                        <?php if ($configuracionActual && $configuracionActual['is_active']): ?>
                            <span class="status-badge active">
                                <i class="fas fa-check-circle"></i> Activa
                            </span>
                        <?php else: ?>
                            <span class="status-badge inactive">
                                <i class="fas fa-times-circle"></i> Inactiva
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <form id="bank-account-form" method="POST" action="define_pay.php">
                            <input type="hidden" name="action" value="save_account">
                            
                            <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px;">
                                <div class="form-group" style="flex: 1;">
                                    <label for="bank_code">Banco *</label>
                                    <select id="bank_code" name="bank_code" class="form-control" required>
                                        <option value="">Seleccionar banco</option>
                                        <?php foreach ($bancos as $banco): ?>
                                            <option value="<?= htmlspecialchars($banco['bank_code']) ?>" 
                                                <?= ($configuracionActual && $configuracionActual['bank_code'] === $banco['bank_code']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($banco['bank_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group" style="flex: 1;">
                                    <label for="account_type">Tipo de Cuenta *</label>
                                    <select id="account_type" name="account_type" class="form-control" required>
                                        <option value="ahorros" <?= ($configuracionActual && $configuracionActual['account_type'] === 'ahorros') ? 'selected' : '' ?>>Cuenta de Ahorros</option>
                                        <option value="corriente" <?= ($configuracionActual && $configuracionActual['account_type'] === 'corriente') ? 'selected' : '' ?>>Cuenta Corriente</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="account_number">Número de Cuenta *</label>
                                <input type="text" id="account_number" name="account_number" class="form-control" 
                                    value="<?= htmlspecialchars($configuracionActual['account_number'] ?? '') ?>" 
                                    required placeholder="Ej: 1234567890">
                            </div>

                            <div class="form-group">
                                <label for="account_holder">Titular de la Cuenta *</label>
                                <input type="text" id="account_holder" name="account_holder" class="form-control" 
                                    value="<?= htmlspecialchars($configuracionActual['account_holder'] ?? '') ?>" 
                                    required placeholder="Nombre completo del titular">
                            </div>

                            <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px;">
                                <div class="form-group" style="flex: 1;">
                                    <label for="identification_type">Tipo de Documento *</label>
                                    <select id="identification_type" name="identification_type" class="form-control" required>
                                        <option value="cc" <?= ($configuracionActual && $configuracionActual['identification_type'] === 'cc') ? 'selected' : '' ?>>Cédula de Ciudadanía</option>
                                        <option value="ce" <?= ($configuracionActual && $configuracionActual['identification_type'] === 'ce') ? 'selected' : '' ?>>Cédula de Extranjería</option>
                                        <option value="nit" <?= ($configuracionActual && $configuracionActual['identification_type'] === 'nit') ? 'selected' : '' ?>>NIT</option>
                                    </select>
                                </div>

                                <div class="form-group" style="flex: 1;">
                                    <label for="identification_number">Número de Documento *</label>
                                    <input type="text" id="identification_number" name="identification_number" class="form-control" 
                                        value="<?= htmlspecialchars($configuracionActual['identification_number'] ?? '') ?>" 
                                        required placeholder="Número de documento">
                                </div>
                            </div>

                            <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px;">
                                <div class="form-group" style="flex: 1;">
                                    <label for="email">Email de contacto</label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                        value="<?= htmlspecialchars($configuracionActual['email'] ?? '') ?>" 
                                        placeholder="email@ejemplo.com">
                                </div>

                                <div class="form-group" style="flex: 1;">
                                    <label for="phone">Teléfono de contacto</label>
                                    <input type="text" id="phone" name="phone" class="form-control" 
                                        value="<?= htmlspecialchars($configuracionActual['phone'] ?? '') ?>" 
                                        placeholder="+57 300 123 4567">
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" id="is_active" name="is_active" class="form-check-input" 
                                        <?= isset($configuracionActual['is_active']) && $configuracionActual['is_active'] ? 'checked' : '' ?>>
                                    <label for="is_active" class="form-check-label">Cuenta activa para recibir pagos</label>
                                </div>
                                <small class="text-muted">Cuando está activa, los clientes podrán realizar transferencias a esta cuenta</small>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?= $configuracionActual ? 'Actualizar Configuración' : 'Guardar Configuración' ?>
                                </button>
                                <?php if ($configuracionActual): ?>
                                    <button type="button" class="btn btn-secondary" id="preview-btn">
                                        <i class="fas fa-eye"></i> Vista Previa
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Vista previa de la información de la cuenta -->
                <?php if ($configuracionActual): ?>
                    <div class="card" id="account-preview" style="display: none;">
                        <div class="card-header">
                            <h3>Vista Previa - Información para Clientes</h3>
                        </div>
                        <div class="card-body">
                            <div class="account-preview">
                                <div class="bank-info">
                                    <div class="bank-logo">
                                        <i class="fas fa-university"></i>
                                    </div>
                                    <div class="bank-details">
                                        <h4><?= htmlspecialchars($configuracionActual['bank_name'] ?? 'Banco') ?></h4>
                                        <p>Cuenta de <?= $configuracionActual['account_type'] === 'ahorros' ? 'Ahorros' : 'Corriente' ?></p>
                                    </div>
                                </div>
                                
                                <div class="account-details">
                                    <div class="detail-row">
                                        <span class="label">Número de Cuenta:</span>
                                        <span class="value"><?= htmlspecialchars($configuracionActual['account_number']) ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="label">Titular:</span>
                                        <span class="value"><?= htmlspecialchars($configuracionActual['account_holder']) ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="label">Documento:</span>
                                        <span class="value">
                                            <?= 
                                                ($configuracionActual['identification_type'] === 'cc' ? 'CC' : 
                                                ($configuracionActual['identification_type'] === 'ce' ? 'CE' : 'NIT'))
                                            ?> 
                                            <?= htmlspecialchars($configuracionActual['identification_number']) ?>
                                        </span>
                                    </div>
                                    <?php if ($configuracionActual['email']): ?>
                                        <div class="detail-row">
                                            <span class="label">Email:</span>
                                            <span class="value"><?= htmlspecialchars($configuracionActual['email']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($configuracionActual['phone']): ?>
                                        <div class="detail-row">
                                            <span class="label">Teléfono:</span>
                                            <span class="value"><?= htmlspecialchars($configuracionActual['phone']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="instructions">
                                    <h5>Instrucciones para el pago:</h5>
                                    <ol>
                                        <li>Realiza la transferencia a la cuenta mostrada arriba</li>
                                        <li>Guarda el comprobante de la transferencia</li>
                                        <li>Sube el comprobante en el proceso de pago</li>
                                        <li>Espera la verificación por parte del administrador</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="<?= BASE_URL ?>/js/dashboardadmin.js"></script>
    <script src="<?= BASE_URL ?>/js/alerta.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Vista previa
            const previewBtn = document.getElementById('preview-btn');
            const accountPreview = document.getElementById('account-preview');
            
            if (previewBtn && accountPreview) {
                previewBtn.addEventListener('click', function() {
                    if (accountPreview.style.display === 'none') {
                        accountPreview.style.display = 'block';
                        previewBtn.innerHTML = '<i class="fas fa-eye-slash"></i> Ocultar Vista Previa';
                    } else {
                        accountPreview.style.display = 'none';
                        previewBtn.innerHTML = '<i class="fas fa-eye"></i> Vista Previa';
                    }
                });
            }

            // Validación del formulario
            const form = document.getElementById('bank-account-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const accountNumber = document.getElementById('account_number').value;
                    const identificationNumber = document.getElementById('identification_number').value;
                    
                    if (!/^\d+$/.test(accountNumber)) {
                        e.preventDefault();
                        showAlert('El número de cuenta debe contener solo dígitos', 'error');
                        return false;
                    }
                    
                    if (!/^\d+$/.test(identificationNumber)) {
                        e.preventDefault();
                        showAlert('El número de documento debe contener solo dígitos', 'error');
                        return false;
                    }
                    
                    return true;
                });
            }
        });
    </script>
</body>
</html>