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
        confirmBulkActionBtn.innerHTML = '<i class="fas fa-check"></i><span>Confirmar</span>';
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
        
        // Ocultar todos los contenedores con animación
        bulkStatusContainer.style.opacity = '0';
        bulkDeleteContainer.style.opacity = '0';
        
        setTimeout(() => {
            bulkStatusContainer.style.display = 'none';
            bulkDeleteContainer.style.display = 'none';
            confirmBulkActionBtn.disabled = true;
            confirmBulkActionBtn.classList.remove('btn-danger');

            if (actionType === 'status') {
                bulkStatusContainer.style.display = 'block';
                setTimeout(() => {
                    bulkStatusContainer.style.opacity = '1';
                }, 10);
                confirmBulkActionBtn.disabled = false;
                confirmBulkActionBtn.innerHTML = '<i class="fas fa-sync-alt"></i><span>Cambiar estado</span>';
            } else if (actionType === 'delete') {
                bulkDeleteContainer.style.display = 'block';
                setTimeout(() => {
                    bulkDeleteContainer.style.opacity = '1';
                }, 10);
                confirmBulkActionBtn.disabled = false;
                confirmBulkActionBtn.innerHTML = '<i class="fas fa-trash-alt"></i><span>Eliminar</span>';
                confirmBulkActionBtn.classList.add('btn-danger');
            } else {
                confirmBulkActionBtn.innerHTML = '<i class="fas fa-check"></i><span>Confirmar</span>';
            }
        }, 200);
    });

    // Función para confirmar acción masiva
    function confirmBulkAction() {
        const actionType = bulkActionTypeSelect.value;

        if (!window.selectedOrders || window.selectedOrders.length === 0) {
            showAlert('No hay órdenes seleccionadas', 'warning');
            closeBulkActionsModal();
            return;
        }

        if (actionType === 'status') {
            const newStatus = bulkNewStatusSelect.value;
            updateOrdersStatusBulk(window.selectedOrders, newStatus);
        } else if (actionType === 'delete') {
            deleteOrdersBulk(window.selectedOrders);
        }

        closeBulkActionsModal();
    }

    // Función para actualizar estado de múltiples órdenes
    function updateOrdersStatusBulk(orderIds, newStatus) {
        const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
        
        // Mostrar indicador de carga
        showAlert(`Actualizando estado de ${orderIds.length} ${orderIds.length === 1 ? 'orden' : 'órdenes'}...`, 'info');
        
        fetch(`${baseUrl}/admin/order/bulk_update_status.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_ids: orderIds,
                new_status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                
                // Recargar órdenes si la función está disponible
                if (typeof window.loadOrders === 'function') {
                    window.loadOrders();
                } else {
                    location.reload();
                }
                
                // Limpiar selección
                window.selectedOrders = [];
                const selectAllCheckbox = document.getElementById('select-all');
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = false;
                }
                
                // Desmarcar checkboxes
                const checkboxes = document.querySelectorAll('.order-checkbox');
                checkboxes.forEach(cb => cb.checked = false);
            } else {
                showAlert(data.message || 'Error al actualizar estado de las órdenes', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error de conexión al actualizar estado de las órdenes', 'error');
        });
    }

    // Función para eliminar múltiples órdenes
    function deleteOrdersBulk(orderIds) {
        const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
        
        // Mostrar indicador de carga
        showAlert(`Eliminando ${orderIds.length} ${orderIds.length === 1 ? 'orden' : 'órdenes'}...`, 'info');
        
        fetch(`${baseUrl}/admin/order/bulk_delete.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_ids: orderIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                
                // Recargar órdenes si la función está disponible
                if (typeof window.loadOrders === 'function') {
                    window.loadOrders();
                } else {
                    location.reload();
                }
                
                // Limpiar selección
                window.selectedOrders = [];
                const selectAllCheckbox = document.getElementById('select-all');
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = false;
                }
                
                // Desmarcar checkboxes
                const checkboxes = document.querySelectorAll('.order-checkbox');
                checkboxes.forEach(cb => cb.checked = false);
            } else {
                showAlert(data.message || 'Error al eliminar las órdenes', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error de conexión al eliminar las órdenes', 'error');
        });
    }

    // Event listeners
    cancelBulkActionBtn.addEventListener('click', closeBulkActionsModal);
    confirmBulkActionBtn.addEventListener('click', confirmBulkAction);

    // Cerrar modal con el botón X
    const modalCloseBtn = bulkActionsModal.querySelector('.modal-close');
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeBulkActionsModal);
    }

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