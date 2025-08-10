document.addEventListener('DOMContentLoaded', function() {
    // Variables para el modal de cambio de estado
    let currentOrderIdForStatus = null;
    const statusChangeModal = document.getElementById('status-change-modal');
    const cancelStatusChangeBtn = document.getElementById('cancel-status-change');
    const confirmStatusChangeBtn = document.getElementById('confirm-status-change');

    // Función para abrir modal de cambio de estado
    window.openStatusChangeModal = function(orderId) {
        currentOrderIdForStatus = orderId;
        document.getElementById('status-change-notes').value = '';
        document.getElementById('new-status-modal').value = 'pending';
        statusChangeModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    // Función para cerrar modal de cambio de estado
    function closeStatusChangeModal() {
        statusChangeModal.classList.remove('active');
        document.body.style.overflow = '';
        currentOrderIdForStatus = null;
    }

    // Función para confirmar cambio de estado
    function confirmStatusChange() {
        const newStatus = document.getElementById('new-status-modal').value;
        const notes = document.getElementById('status-change-notes').value;

        if (!currentOrderIdForStatus) return;

        fetch(`<?= BASE_URL ?>/admin/order/update_status.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_ids: [currentOrderIdForStatus],
                    new_status: newStatus,
                    notes: notes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Estado actualizado correctamente', 'success');
                    loadOrders();
                } else {
                    throw new Error(data.message || 'Error al actualizar estado');
                }
            })
            .catch(error => {
                showAlert(error.message, 'error');
            })
            .finally(() => {
                closeStatusChangeModal();
            });
    }

    // Event listeners
    cancelStatusChangeBtn.addEventListener('click', closeStatusChangeModal);
    confirmStatusChangeBtn.addEventListener('click', confirmStatusChange);

    // Cerrar modal al hacer clic fuera del contenido
    statusChangeModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeStatusChangeModal();
        }
    });

    // Cerrar modal con tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && statusChangeModal.classList.contains('active')) {
            closeStatusChangeModal();
        }
    });
});