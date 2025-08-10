<!-- Modal para cambiar estado -->
<div id="status-change-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Cambiar estado de la orden</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="new-status-modal">Nuevo estado</label>
                <select id="new-status-modal" class="form-control">
                    <?php foreach ($statuses as $value => $label): ?>
                        <option value="<?= $value ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="status-change-notes">Notas (opcional)</label>
                <textarea id="status-change-notes" class="form-control" rows="3" placeholder="Agregar notas sobre el cambio de estado..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="cancel-status-change">Cancelar</button>
            <button class="btn btn-primary" id="confirm-status-change">Confirmar</button>
        </div>
    </div>
</div>