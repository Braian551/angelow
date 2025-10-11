<!-- Modal para acciones masivas -->
<div id="bulk-actions-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Acciones masivas</h3>
            <button class="modal-close" aria-label="Cerrar modal">&times;</button>
        </div>
        <div class="modal-body">
            <p id="bulk-actions-count">Se aplicará a <span>0</span> órdenes seleccionadas</p>
            
            <div class="form-group">
                <label for="bulk-action-type">Acción a realizar</label>
                <select id="bulk-action-type" class="form-control">
                    <option value="">Seleccionar acción...</option>
                    <option value="status">Cambiar estado de las órdenes</option>
                    <option value="delete">Eliminar órdenes permanentemente</option>
                </select>
            </div>
            
            <div id="bulk-status-container" class="form-group" style="display: none;">
                <label for="bulk-new-status">Nuevo estado</label>
                <select id="bulk-new-status" class="form-control">
                    <?php foreach ($statuses as $value => $label): ?>
                        <option value="<?= $value ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="bulk-delete-container" style="display: none;">
                <div class="alert-icon-container">
                    <div class="alert-icon error">!</div>
                </div>
                <p><strong>¡Advertencia!</strong> Esta acción eliminará permanentemente las órdenes seleccionadas y no se puede deshacer. Se perderán todos los datos asociados.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="cancel-bulk-action">
                <i class="fas fa-times"></i>
                <span>Cancelar</span>
            </button>
            <button class="btn btn-primary" id="confirm-bulk-action" disabled>
                <i class="fas fa-check"></i>
                <span>Confirmar</span>
            </button>
        </div>
    </div>
</div>