<!-- Modal para eliminar orden -->
<div id="delete-order-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirmar eliminación</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="alert-icon-container error">
                <i class="fas fa-exclamation-triangle alert-icon"></i>
            </div>
            <p id="delete-message" class="delete-message">¿Estás seguro que deseas eliminar esta orden? Esta acción no se puede deshacer.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="cancel-delete-order">
                <i class="fas fa-times"></i>
                Cancelar
            </button>
            <button class="btn btn-danger" id="confirm-delete-order">
                <i class="fas fa-trash-alt"></i>
                Eliminar
            </button>
        </div>
    </div>
</div>