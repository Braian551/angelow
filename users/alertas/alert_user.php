<!-- Sistema de Alertas para Usuarios -->
<div class="user-alert-overlay" id="userAlertOverlay" style="display: none;">
    <div class="user-alert-container">
        <div class="user-alert-content">
            <div class="user-alert-icon-wrapper">
                <i class="user-alert-icon" id="userAlertIcon"></i>
            </div>
            <h3 class="user-alert-title" id="userAlertTitle"></h3>
            <p class="user-alert-message" id="userAlertMessage"></p>
            <div class="user-alert-actions" id="userAlertActions">
                <!-- Los botones se crean dinámicamente por JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Estilos y Scripts -->
<link rel="stylesheet" href="<?= BASE_URL ?>/css/user/alert_user.css">
<script src="<?= BASE_URL ?>/js/user/alert_user.js"></script>
