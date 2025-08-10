<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../layouts/header2.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Debes iniciar sesión para ver esta campaña";
    header("Location: " . BASE_URL . "/users/formuser.php");
    exit();
}

// Obtener slug de la URL
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header("Location: " . BASE_URL . "/donaciones/donaciones.php");
    exit();
}

// Obtener información de la campaña
try {
    $stmt = $conn->prepare("
        SELECT dc.*, co.name as organization_name, co.description as organization_description, 
               co.image as organization_image, co.website as organization_website,
               co.contact_email as organization_email, co.contact_phone as organization_phone
        FROM donation_campaigns dc
        JOIN charity_organizations co ON dc.organization_id = co.id
        WHERE dc.slug = ? AND dc.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campaign) {
        throw new Exception("Campaña no encontrada o no disponible");
    }
    
    // Obtener donaciones recientes para esta campaña (no anónimas)
    $donationsStmt = $conn->prepare("
        SELECT d.*, u.name as donor_name, 
               CONVERT_TZ(d.created_at, '+00:00', '-05:00') as donation_date
        FROM donations d
        LEFT JOIN users u ON d.user_id = u.id
        WHERE d.campaign_id = ? AND d.is_anonymous = 0
        ORDER BY d.created_at DESC
        LIMIT 10
    ");
    $donationsStmt->execute([$campaign['id']]);
    $recentDonations = $donationsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: " . BASE_URL . "/donaciones/donaciones.php");
    exit();
}
?>

<div class="campaign-detail-container">
    <div class="campaign-header">
        <div class="campaign-image">
            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($campaign['image'] ?? 'images/default-donation.jpg') ?>" alt="<?= htmlspecialchars($campaign['title']) ?>">
        </div>
        
        <div class="campaign-info">
            <div class="organization">
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($campaign['organization_image'] ?? 'images/default-charity.png') ?>" alt="<?= htmlspecialchars($campaign['organization_name']) ?>">
                <span><?= htmlspecialchars($campaign['organization_name']) ?></span>
            </div>
            
            <h1><?= htmlspecialchars($campaign['title']) ?></h1>
            
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
    
    <div class="campaign-content">
        <div class="campaign-description">
            <h2>Sobre esta campaña</h2>
            <p><?= nl2br(htmlspecialchars($campaign['description'])) ?></p>
            
            <?php if ($campaign['start_date'] || $campaign['end_date']): ?>
                <div class="campaign-dates">
                    <?php if ($campaign['start_date']): ?>
                        <p><strong>Fecha de inicio:</strong> <?= date('d/m/Y', strtotime($campaign['start_date'])) ?></p>
                    <?php endif; ?>
                    
                    <?php if ($campaign['end_date']): ?>
                        <p><strong>Fecha de cierre:</strong> <?= date('d/m/Y', strtotime($campaign['end_date'])) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="organization-info">
            <h2>Sobre la organización</h2>
            <div class="org-card">
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($campaign['organization_image'] ?? 'images/default-charity.png') ?>" alt="<?= htmlspecialchars($campaign['organization_name']) ?>">
                
                <div class="org-details">
                    <h3><?= htmlspecialchars($campaign['organization_name']) ?></h3>
                    <p><?= nl2br(htmlspecialchars($campaign['organization_description'])) ?></p>
                    
                    <div class="org-contact">
                        <?php if ($campaign['organization_website']): ?>
                            <p><i class="fas fa-globe"></i> <a href="<?= htmlspecialchars($campaign['organization_website']) ?>" target="_blank">Visitar sitio web</a></p>
                        <?php endif; ?>
                        
                        <?php if ($campaign['organization_email']): ?>
                            <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($campaign['organization_email']) ?></p>
                        <?php endif; ?>
                        
                        <?php if ($campaign['organization_phone']): ?>
                            <p><i class="fas fa-phone"></i> <?= htmlspecialchars($campaign['organization_phone']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($recentDonations)): ?>
        <div class="recent-donations">
            <h2>Donaciones recientes</h2>
            
            <div class="donations-list">
                <?php foreach ($recentDonations as $donation): ?>
                    <div class="donation-card">
                        <div class="donor-info">
                            <div class="donor-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <span><?= htmlspecialchars($donation['donor_name']) ?></span>
                        </div>
                        
                        <div class="donation-amount">
                            $<?= number_format($donation['amount'], 0, ',', '.') ?>
                        </div>
                        
                        <div class="donation-date">
                            <?= date('d/m/Y H:i', strtotime($donation['donation_date'])) ?>
                        </div>
                        
                        <?php if ($donation['message']): ?>
                            <div class="donation-message">
                                <p>"<?= htmlspecialchars($donation['message']) ?>"</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Donación (igual que en donaciones.php) -->
<div id="donationModal" class="modal">
    <!-- Contenido del modal igual que en donaciones.php -->
</div>

<script>
    // Funciones para el modal de donación (igual que en donaciones.php)
    function openDonationModal(campaignId, campaignTitle) {
        document.getElementById('donationModal').style.display = 'block';
        document.getElementById('modalTitle').textContent = 'Donar a: ' + campaignTitle;
        document.getElementById('campaignId').value = campaignId;
    }
    
    function closeModal() {
        document.getElementById('donationModal').style.display = 'none';
    }
    
    // Resto del script igual que en donaciones.php
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>