<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../layouts/header2.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Debes iniciar sesión para acceder a las donaciones";
    header("Location: " . BASE_URL . "/users/formuser.php");
    exit();
}

// Obtener campañas activas
function getActiveCampaigns($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT dc.*, co.name as organization_name, co.image as organization_image
            FROM donation_campaigns dc
            JOIN charity_organizations co ON dc.organization_id = co.id
            WHERE dc.is_active = 1 AND (dc.end_date IS NULL OR dc.end_date >= CURDATE())
            ORDER BY dc.is_featured DESC, dc.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener campañas: " . $e->getMessage());
        return [];
    }
}

// Obtener donaciones del usuario
function getUserDonations($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT d.*, dc.title as campaign_title, dc.slug as campaign_slug, 
                   co.name as organization_name, co.image as organization_image,
                   CONVERT_TZ(d.created_at, '+00:00', '-05:00') as donation_date
            FROM donations d
            JOIN donation_campaigns dc ON d.campaign_id = dc.id
            JOIN charity_organizations co ON dc.organization_id = co.id
            WHERE d.user_id = ?
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener donaciones del usuario: " . $e->getMessage());
        return [];
    }
}

// Procesar donación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['donate'])) {
    try {
        $conn->beginTransaction();
        
        $campaign_id = intval($_POST['campaign_id']);
        $amount = floatval($_POST['amount']);
        $payment_method = $_POST['payment_method'];
        $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
        $message = $_POST['message'] ?? null;
        $user_id = $_SESSION['user_id'];
        
        // Validar campaña
        $campaignStmt = $conn->prepare("SELECT id, target_amount FROM donation_campaigns WHERE id = ? AND is_active = 1");
        $campaignStmt->execute([$campaign_id]);
        $campaign = $campaignStmt->fetch();
        
        if (!$campaign) {
            throw new Exception("Campaña de donación no válida o no disponible");
        }
        
        // Validar monto mínimo
        if ($amount < 10000) {
            throw new Exception("El monto mínimo de donación es $10,000 COP");
        }
        
        // Crear registro de donación
        $donationStmt = $conn->prepare("
            INSERT INTO donations (
                campaign_id, user_id, amount, payment_method, payment_status, 
                is_anonymous, message, created_at, updated_at
            ) VALUES (?, ?, ?, ?, 'pending', ?, ?, NOW(), NOW())
        ");
        $donationStmt->execute([
            $campaign_id, 
            $is_anonymous ? NULL : $user_id, // Si es anónimo, no guardamos user_id
            $amount, 
            $payment_method,
            $is_anonymous,
            $message
        ]);
        $donation_id = $conn->lastInsertId();
        
        // Procesar según método de pago
        if ($payment_method === 'transferencia') {
            // Validar datos de transferencia
            if (empty($_POST['bank_name'])) {
                throw new Exception("Debes seleccionar un banco");
            }
            
            // Procesar archivo del comprobante
            $paymentProofPath = null;
            if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                $fileType = mime_content_type($_FILES['payment_proof']['tmp_name']);
                
                if (!in_array($fileType, $allowedTypes)) {
                    throw new Exception("Formato de archivo no permitido. Solo se aceptan JPG, PNG o PDF");
                }
                
                if ($_FILES['payment_proof']['size'] > 2097152) {
                    throw new Exception("El archivo es demasiado grande. Máximo 2MB permitidos");
                }
                
                $uploadDir = __DIR__ . '/../uploads/donation_proofs/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $filename = uniqid() . '_' . basename($_FILES['payment_proof']['name']);
                $targetPath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $targetPath)) {
                    $paymentProofPath = 'uploads/donation_proofs/' . $filename;
                } else {
                    throw new Exception("Error al subir el comprobante. Intente nuevamente.");
                }
            } else {
                throw new Exception("Debes adjuntar el comprobante de transferencia");
            }
            
            // Crear transacción
            $transactionStmt = $conn->prepare("
                INSERT INTO donation_transactions (
                    donation_id, reference_number, bank_name, account_number, 
                    account_type, account_holder, payment_proof, notes, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $transactionStmt->execute([
                $donation_id,
                $_POST['reference_number'] ?? null,
                $_POST['bank_name'],
                $_POST['account_number'] ?? null,
                $_POST['account_type'] ?? null,
                $_POST['account_holder'] ?? null,
                $paymentProofPath,
                $_POST['payment_notes'] ?? null
            ]);
        }
        
        // Actualizar monto recaudado en la campaña
        $updateCampaignStmt = $conn->prepare("
            UPDATE donation_campaigns 
            SET current_amount = current_amount + ? 
            WHERE id = ?
        ");
        $updateCampaignStmt->execute([$amount, $campaign_id]);
        
        $conn->commit();
        
        $_SESSION['success'] = "¡Gracias por tu donación! Hemos recibido tu contribución.";
        header("Location: " . BASE_URL . "/donaciones/donaciones.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error al procesar la donación: " . $e->getMessage();
        error_log("Error en donación: " . $e->getMessage());
    }
}

// Obtener datos para la vista
$campaigns = getActiveCampaigns($conn);
$userDonations = getUserDonations($conn, $_SESSION['user_id']);

// Obtener lista de bancos para el formulario
try {
    $banks = $conn->query("SELECT bank_code, bank_name FROM colombian_banks WHERE is_active = 1 ORDER BY bank_name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $banks = [];
    error_log("Error al obtener bancos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donaciones</title>
    <meta name="description" content="Panel de administración para la gestión de la tienda de ropa infantil Angelow">
    <link rel="icon" href="<?= BASE_URL ?>/images/logo.png" type="image/x-icon">
    <!-- Font Awesome para íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/donaciones.css">
</head>
<body>


<div class="donations-container">
    <h1>Donaciones para Niños sin Recursos</h1>
    <p class="subtitle">Tu contribución puede cambiar vidas. Apoya nuestras campañas activas.</p>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert success">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <div class="donation-tabs">
        <button class="tab-button active" onclick="openTab('campaigns')">Campañas Activas</button>
        <button class="tab-button" onclick="openTab('my-donations')">Mis Donaciones</button>
    </div>
    
    <div id="campaigns" class="tab-content active">
        <?php if (empty($campaigns)): ?>
            <p>No hay campañas activas en este momento. Por favor revisa más tarde.</p>
        <?php else: ?>
            <div class="campaigns-grid">
                <?php foreach ($campaigns as $campaign): ?>
                    <div class="campaign-card">
                        <div class="campaign-image">
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($campaign['image'] ?? 'images/default-donation.jpg') ?>" alt="<?= htmlspecialchars($campaign['title']) ?>">
                        </div>
                        <div class="campaign-info">
                            <div class="organization">
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($campaign['organization_image'] ?? 'images/default-charity.png') ?>" alt="<?= htmlspecialchars($campaign['organization_name']) ?>">
                                <span><?= htmlspecialchars($campaign['organization_name']) ?></span>
                            </div>
                            <h3><?= htmlspecialchars($campaign['title']) ?></h3>
                            <p><?= htmlspecialchars(substr($campaign['description'], 0, 150)) ?>...</p>
                            
                            <div class="progress-container">
                                <div class="progress-bar" style="width: <?= $campaign['target_amount'] > 0 ? min(100, ($campaign['current_amount'] / $campaign['target_amount']) * 100) : 0 ?>%"></div>
                                <div class="progress-info">
                                    <span>$<?= number_format($campaign['current_amount'], 0, ',', '.') ?> recaudados</span>
                                    <?php if ($campaign['target_amount'] > 0): ?>
                                        <span>Meta: $<?= number_format($campaign['target_amount'], 0, ',', '.') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <button class="donate-btn" onclick="openDonationModal(<?= $campaign['id'] ?>, '<?= htmlspecialchars($campaign['title']) ?>')">
                                Donar Ahora
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div id="my-donations" class="tab-content">
        <?php if (empty($userDonations)): ?>
            <p>Aún no has realizado ninguna donación. ¡Anímate a apoyar alguna de nuestras campañas!</p>
        <?php else: ?>
            <table class="donations-table">
                <thead>
                    <tr>
                        <th>Campaña</th>
                        <th>Organización</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userDonations as $donation): ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/donaciones/campana.php?slug=<?= htmlspecialchars($donation['campaign_slug']) ?>">
                                    <?= htmlspecialchars($donation['campaign_title']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($donation['organization_name']) ?></td>
                            <td>$<?= number_format($donation['amount'], 0, ',', '.') ?></td>
                            <td><?= ucfirst($donation['payment_method']) ?></td>
                            <td>
                                <span class="status-badge <?= $donation['payment_status'] ?>">
                                    <?= ucfirst($donation['payment_status']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($donation['donation_date'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Donación -->
<div id="donationModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle">Realizar Donación</h2>
        
        <form id="donationForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="campaign_id" id="campaignId">
            <input type="hidden" name="donate" value="1">
            
            <div class="form-group">
                <label for="amount">Monto a donar (COP)</label>
                <input type="number" name="amount" id="amount" min="10000" step="1000" required>
                <small>Mínimo $10,000 COP</small>
            </div>
            
            <div class="form-group">
                <label for="message">Mensaje (opcional)</label>
                <textarea name="message" id="message" rows="2" placeholder="Déjanos un mensaje con tu donación"></textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_anonymous" id="is_anonymous">
                    Donación anónima
                </label>
            </div>
            
            <div class="payment-methods">
                <h3>Método de Pago</h3>
                
                <div class="payment-options">
                    <div class="payment-option">
                        <input type="radio" name="payment_method" id="transferencia" value="transferencia" checked>
                        <label for="transferencia">
                            <i class="fas fa-university"></i>
                            <span>Transferencia Bancaria</span>
                        </label>
                    </div>
                    
                    <div class="payment-option">
                        <input type="radio" name="payment_method" id="contra_entrega" value="contra_entrega">
                        <label for="contra_entrega">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Pago Contra Entrega</span>
                            <span class="badge">Solo Medellín</span>
                        </label>
                    </div>
                </div>
                
                <div class="payment-details" id="transferencia-details">
                    <h4>Datos para Transferencia Bancaria</h4>
                    <p>Por favor realiza la transferencia a nuestra cuenta y adjunta el comprobante.</p>
                    
                    <div class="bank-info">
                        <p><strong>Banco:</strong> Bancolombia</p>
                        <p><strong>Tipo de Cuenta:</strong> Cuenta de Ahorros</p>
                        <p><strong>Número:</strong> 123-456789-00</p>
                        <p><strong>Titular:</strong> Fundación Ayuda Infantil</p>
                        <p><strong>Valor a transferir:</strong> <span id="transferAmount">$0</span></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="bank_name">Banco desde el que transfiere:</label>
                        <select name="bank_name" id="bank_name" class="form-control" required>
                            <option value="">Seleccione su banco</option>
                            <?php foreach ($banks as $bank): ?>
                                <option value="<?= htmlspecialchars($bank['bank_name']) ?>"><?= htmlspecialchars($bank['bank_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="reference_number">Número de Referencia/Transacción:</label>
                        <input type="text" name="reference_number" id="reference_number" class="form-control" placeholder="Ej: 123456789" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="account_type">Tipo de Cuenta:</label>
                        <select name="account_type" id="account_type" class="form-control" required>
                            <option value="">Seleccione tipo de cuenta</option>
                            <option value="ahorros">Ahorros</option>
                            <option value="corriente">Corriente</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="account_number">Número de Cuenta:</label>
                        <input type="text" name="account_number" id="account_number" class="form-control" placeholder="Número de la cuenta que realizó la transferencia" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="account_holder">Titular de la Cuenta:</label>
                        <input type="text" name="account_holder" id="account_holder" class="form-control" placeholder="Nombre del titular de la cuenta" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_proof">Comprobante de Transferencia:</label>
                        <input type="file" name="payment_proof" id="payment_proof" class="form-control" accept="image/*,.pdf" required>
                        <small>Formatos aceptados: JPG, PNG, PDF (Máx. 2MB)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_notes">Notas adicionales:</label>
                        <textarea name="payment_notes" id="payment_notes" class="form-control" rows="3" placeholder="Alguna información adicional sobre el pago"></textarea>
                    </div>
                </div>
                
                <div class="payment-details" id="contra_entrega-details" style="display: none;">
                    <h4>Pago Contra Entrega - Solo Medellín</h4>
                    <p>Paga en efectivo cuando recibas la confirmación. Un representante se comunicará contigo.</p>
                    
                    <div class="form-group">
                        <label for="shipping_city">Ciudad:</label>
                        <input type="text" name="shipping_city" id="shipping_city" class="form-control" value="Medellín" readonly required>
                    </div>
                    
                    <div class="form-group">
                        <label for="shipping_address">Dirección Completa:</label>
                        <textarea name="shipping_address" id="shipping_address" class="form-control" rows="3" required placeholder="Barrio, calle, carrera, número, apartamento, etc."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="delivery_notes">Instrucciones adicionales:</label>
                        <textarea name="delivery_notes" id="delivery_notes" class="form-control" rows="2" placeholder="Ej: Piso, apartamento, referencias, horario de entrega"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="cancel-btn" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="submit-btn">Confirmar Donación</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Funciones para manejar las pestañas
    function openTab(tabName) {
        const tabContents = document.getElementsByClassName('tab-content');
        for (let i = 0; i < tabContents.length; i++) {
            tabContents[i].classList.remove('active');
        }
        
        const tabButtons = document.getElementsByClassName('tab-button');
        for (let i = 0; i < tabButtons.length; i++) {
            tabButtons[i].classList.remove('active');
        }
        
        document.getElementById(tabName).classList.add('active');
        event.currentTarget.classList.add('active');
    }
    
    // Funciones para el modal de donación
    function openDonationModal(campaignId, campaignTitle) {
        document.getElementById('donationModal').style.display = 'block';
        document.getElementById('modalTitle').textContent = 'Donar a: ' + campaignTitle;
        document.getElementById('campaignId').value = campaignId;
    }
    
    function closeModal() {
        document.getElementById('donationModal').style.display = 'none';
    }
    
    // Cerrar modal al hacer clic fuera de él
    window.onclick = function(event) {
        const modal = document.getElementById('donationModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
    
    // Actualizar detalles de pago según método seleccionado
    document.addEventListener('DOMContentLoaded', function() {
        const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
        const paymentDetails = {
            'transferencia': document.getElementById('transferencia-details'),
            'contra_entrega': document.getElementById('contra_entrega-details')
        };
        
        function updatePaymentDetails() {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked').value;
            
            // Ocultar todos los detalles primero
            Object.values(paymentDetails).forEach(detail => {
                detail.style.display = 'none';
                detail.querySelectorAll('[required]').forEach(field => {
                    field.required = false;
                });
            });
            
            // Mostrar solo el seleccionado y hacer required sus campos
            if (paymentDetails[selectedMethod]) {
                paymentDetails[selectedMethod].style.display = 'block';
                paymentDetails[selectedMethod].querySelectorAll('[required]').forEach(field => {
                    field.required = true;
                });
            }
        }
        
        paymentMethods.forEach(method => {
            method.addEventListener('change', updatePaymentDetails);
        });
        
        // Actualizar monto mostrado en la transferencia
        const amountInput = document.getElementById('amount');
        if (amountInput) {
            amountInput.addEventListener('input', function() {
                const amount = parseFloat(this.value) || 0;
                document.getElementById('transferAmount').textContent = '$' + amount.toLocaleString('es-CO');
            });
        }
        
        // Validar formulario antes de enviar
        const donationForm = document.getElementById('donationForm');
        if (donationForm) {
            donationForm.addEventListener('submit', function(e) {
                const amount = parseFloat(document.getElementById('amount').value);
                if (amount < 10000) {
                    e.preventDefault();
                    alert('El monto mínimo de donación es $10,000 COP');
                    return false;
                }
                
                const method = document.querySelector('input[name="payment_method"]:checked').value;
                if (method === 'contra_entrega') {
                    const city = document.getElementById('shipping_city').value;
                    if (city.toLowerCase() !== 'medellín') {
                        e.preventDefault();
                        alert('El pago contra entrega solo está disponible para Medellín');
                        return false;
                    }
                }
                
                if (method === 'transferencia') {
                    const fileInput = document.getElementById('payment_proof');
                    if (fileInput.files.length === 0) {
                        e.preventDefault();
                        alert('Debes adjuntar el comprobante de transferencia');
                        return false;
                    }
                }
                
                return true;
            });
        }
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>