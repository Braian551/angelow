<!-- Modal para eliminar orden -->
<div id="delete-order-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirmar eliminación</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="alert-icon-container">
                <div class="alert-icon error">!</div>
            </div>
            <p id="delete-message">¿Estás seguro que deseas eliminar esta orden? Esta acción no se puede deshacer.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="cancel-delete-order">Cancelar</button>
            <button class="btn btn-danger" id="confirm-delete-order">Eliminar</button>
        </div>
    </div>
</div>