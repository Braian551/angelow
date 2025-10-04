<!-- Modal de confirmación para eliminar códigos seleccionados -->
<div class="delete-confirm" id="bulk-delete-confirm-modal" style="display: none;">
    <div class="delete-confirm-content">
        <div class="delete-confirm-header">
            <h3 id="bulk-delete-modal-title">Eliminar códigos seleccionados</h3>
            <button class="delete-confirm-close" id="bulk-delete-modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="delete-confirm-body">
            <p id="bulk-delete-modal-message">¿Estás seguro de que deseas eliminar los códigos seleccionados? Esta acción es irreversible y eliminará permanentemente los registros.</p>
        </div>
        <div class="delete-confirm-footer">
            <button class="btn btn-secondary" id="bulk-delete-modal-cancel">Cancelar</button>
            <button class="btn btn-primary" id="bulk-delete-modal-confirm">Eliminar seleccionados</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bulkDeleteModal = document.getElementById('bulk-delete-confirm-modal');
    const bulkDeleteModalClose = document.getElementById('bulk-delete-modal-close');
    const bulkDeleteModalCancel = document.getElementById('bulk-delete-modal-cancel');
    const bulkDeleteModalConfirm = document.getElementById('bulk-delete-modal-confirm');

    const bulkDeleteButton = document.getElementById('bulk-delete');
    const bulkForm = document.getElementById('bulk-form');

    function closeBulkDeleteModal() {
        bulkDeleteModal.classList.remove('active');
        setTimeout(() => { bulkDeleteModal.style.display = 'none'; }, 300);
    }

    if (bulkDeleteButton) {
        bulkDeleteButton.addEventListener('click', function() {
            // Verificar que haya al menos 1 checkbox seleccionado
            const anyChecked = Array.from(document.querySelectorAll('.row-checkbox')).some(cb => cb.checked);
            if (!anyChecked) return;

            bulkDeleteModal.style.display = 'flex';
            setTimeout(() => bulkDeleteModal.classList.add('active'), 10);
        });
    }

    bulkDeleteModalClose.addEventListener('click', closeBulkDeleteModal);
    bulkDeleteModalCancel.addEventListener('click', closeBulkDeleteModal);

    bulkDeleteModalConfirm.addEventListener('click', function() {
        // Cambiar el valor de la acción a `delete` y enviar el formulario
        // Asegurarse de que exista un input name="action" oculto o crear uno temporal
        let actionInput = bulkForm.querySelector('input[name="action"]');
        if (!actionInput) {
            actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            bulkForm.appendChild(actionInput);
        }
        actionInput.value = 'delete';
        bulkForm.submit();
    });

    bulkDeleteModal.addEventListener('click', function(e) {
        if (e.target === bulkDeleteModal) closeBulkDeleteModal();
    });
});
</script>
