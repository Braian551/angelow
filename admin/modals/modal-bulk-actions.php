<!-- Modal para acciones masivas -->
<div id="bulk-actions-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Acciones masivas</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p id="bulk-actions-count">Se aplicará a <span>0</span> órdenes seleccionadas</p>
            
            <div class="form-group">
                <label>Acción a realizar</label>
                <select id="bulk-action-type" class="form-control">
                    <option value="">Seleccionar acción...</option>
                    <option value="status">Cambiar estado</option>
                    <option value="delete">Eliminar órdenes</option>
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
            
            <div id="bulk-delete-container" class="form-group" style="display: none;">
                <div class="alert-icon-container">
                    <div class="alert-icon error">!</div>
                </div>
                <p>Se eliminarán permanentemente las órdenes seleccionadas. Esta acción no se puede deshacer.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="cancel-bulk-action">Cancelar</button>
            <button class="btn btn-primary" id="confirm-bulk-action" disabled>Confirmar</button>
        </div>
    </div>
</div>