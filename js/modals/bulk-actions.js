document.addEventListener('DOMContentLoaded', function() {
    // Variables para el modal de acciones masivas
    const bulkActionsModal = document.getElementById('bulk-actions-modal');
    const cancelBulkActionBtn = document.getElementById('cancel-bulk-action');
    const confirmBulkActionBtn = document.getElementById('confirm-bulk-action');
    const bulkActionTypeSelect = document.getElementById('bulk-action-type');
    const bulkStatusContainer = document.getElementById('bulk-status-container');
    const bulkDeleteContainer = document.getElementById('bulk-delete-container');
    const bulkNewStatusSelect = document.getElementById('bulk-new-status');
    const bulkActionsCount = document.getElementById('bulk-actions-count');

    // Función para abrir modal de acciones masivas
    function openBulkActionsModal() {
      if (!window.selectedOrders || window.selectedOrders.length === 0) {
        showAlert('Selecciona al menos una orden para realizar acciones', 'warning');
        return;
    }

    bulkActionsCount.innerHTML = 
        `Se aplicará a <span>${window.selectedOrders.length}</span> ${window.selectedOrders.length === 1 ? 'orden' : 'órdenes'} seleccionadas`;

        // Resetear el formulario
        bulkActionTypeSelect.value = '';
        bulkNewStatusSelect.value = 'pending';
        bulkStatusContainer.style.display = 'none';
        bulkDeleteContainer.style.display = 'none';
        confirmBulkActionBtn.disabled = true;
        confirmBulkActionBtn.textContent = 'Confirmar';
        confirmBulkActionBtn.classList.remove('btn-danger');

        bulkActionsModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // Hacer la función accesible globalmente
    window.openBulkActionsModal = openBulkActionsModal;
    // Función para cerrar modal de acciones masivas
    function closeBulkActionsModal() {
        bulkActionsModal.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Manejar cambio en el tipo de acción masiva
    bulkActionTypeSelect.addEventListener('change', function() {
        const actionType = this.value;
        bulkStatusContainer.style.display = 'none';
        bulkDeleteContainer.style.display = 'none';
        confirmBulkActionBtn.disabled = true;
        confirmBulkActionBtn.classList.remove('btn-danger');

        if (actionType === 'status') {
            bulkStatusContainer.style.display = 'block';
            confirmBulkActionBtn.disabled = false;
            confirmBulkActionBtn.textContent = 'Cambiar estado';
        } else if (actionType === 'delete') {
            bulkDeleteContainer.style.display = 'block';
            confirmBulkActionBtn.disabled = false;
            confirmBulkActionBtn.textContent = 'Eliminar';
            confirmBulkActionBtn.classList.add('btn-danger');
        }
    });

    // Función para confirmar acción masiva
 function confirmBulkAction() {
    const actionType = bulkActionTypeSelect.value;

    if (actionType === 'status') {
        const newStatus = bulkNewStatusSelect.value;
        updateOrdersStatus(window.selectedOrders, newStatus);
    } else if (actionType === 'delete') {
        deleteOrders(window.selectedOrders);
    }

    closeBulkActionsModal();
}

    // Event listeners
    cancelBulkActionBtn.addEventListener('click', closeBulkActionsModal);
    confirmBulkActionBtn.addEventListener('click', confirmBulkAction);

    // Cerrar modal al hacer clic fuera del contenido
    bulkActionsModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeBulkActionsModal();
        }
    });

    // Cerrar modal con tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && bulkActionsModal.classList.contains('active')) {
            closeBulkActionsModal();
        }
    });
});