<!-- Modal de confirmación para eliminar un código -->
<div class="delete-confirm" id="delete-confirm-modal" style="display: none;">
    <div class="delete-confirm-content">
        <div class="delete-confirm-header">
            <h3 id="delete-modal-title">Eliminar código</h3>
            <button class="delete-confirm-close" id="delete-modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="delete-confirm-body">
            <p id="delete-modal-message">¿Estás seguro de que deseas eliminar este código? Esta acción no se puede deshacer.</p>
        </div>
        <div class="delete-confirm-footer">
            <button class="btn btn-secondary" id="delete-modal-cancel">Cancelar</button>
            <button class="btn btn-primary" id="delete-modal-confirm">Eliminar</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteModal = document.getElementById('delete-confirm-modal');
    const deleteModalClose = document.getElementById('delete-modal-close');
    const deleteModalCancel = document.getElementById('delete-modal-cancel');
    const deleteModalConfirm = document.getElementById('delete-modal-confirm');

    let currentDeleteId = null;

    // Usar delegación de eventos para cubrir anchors <a> o buttons añadidos dinámicamente
    document.addEventListener('click', function(e) {
        const target = e.target.closest && e.target.closest('.btn-delete-row');
        if (!target) return;
        // Evitar comportamiento por defecto (enlace o envío de formulario)
        if (e && typeof e.preventDefault === 'function') e.preventDefault();
        currentDeleteId = target.getAttribute('data-id');
        deleteModal.style.display = 'flex';
        setTimeout(() => deleteModal.classList.add('active'), 10);
    });

    function closeDeleteModal() {
        deleteModal.classList.remove('active');
        setTimeout(() => { deleteModal.style.display = 'none'; }, 300);
        currentDeleteId = null;
    }

    deleteModalClose.addEventListener('click', closeDeleteModal);
    deleteModalCancel.addEventListener('click', closeDeleteModal);

    deleteModalConfirm.addEventListener('click', function() {
        if (currentDeleteId) {
            window.location.href = `generate_codes.php?action=delete&id=${currentDeleteId}`;
        }
    });

    deleteModal.addEventListener('click', function(e) {
        if (e.target === deleteModal) closeDeleteModal();
    });
});
</script>
