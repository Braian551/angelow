<!-- Modal genérico para cambiar un campo (estado de orden o pago) -->
<div id="change-field-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="change-field-title">Cambiar campo</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="change-field-select" id="change-field-label">Nuevo valor</label>
                <select id="change-field-select" class="form-control"></select>
            </div>
            <div class="form-group">
                <label for="change-field-notes">Notas (opcional)</label>
                <textarea id="change-field-notes" class="form-control" rows="3" placeholder="Agregar notas..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="cancel-change-field">Cancelar</button>
            <button class="btn btn-primary" id="confirm-change-field">Confirmar</button>
        </div>
    </div>
</div>
