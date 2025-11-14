<?php
// Componente reutilizable de modal de confirmación
?>
<div class="confirmation-modal" id="confirmation-modal" aria-hidden="true">
    <div class="confirmation-modal__overlay" data-confirmation-close></div>
    <div class="confirmation-modal__panel" role="dialog" aria-modal="true" aria-labelledby="confirmation-title">
        <div class="confirmation-modal__header">
            <div class="confirmation-modal__icon" aria-hidden="true">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div class="confirmation-modal__titles">
                <p class="confirmation-modal__eyebrow">Confirmación requerida</p>
                <h3 id="confirmation-title">¿Estás seguro?</h3>
            </div>
            <button type="button" class="confirmation-modal__close" data-confirmation-close aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="confirmation-modal__body" data-confirmation-message>
            Esta acción no se puede deshacer.
        </div>
        <div class="confirmation-modal__footer">
            <button type="button" class="btn btn-secondary" data-confirmation-cancel>Cancelar</button>
            <button type="button" class="btn btn-primary" data-confirmation-confirm>Confirmar</button>
        </div>
    </div>
</div>
