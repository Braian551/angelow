document.addEventListener('DOMContentLoaded', function() {
    // Variables para el modal de eliminación
    let currentOrderIdForDelete = null;
    const deleteOrderModal = document.getElementById('delete-order-modal');
    const cancelDeleteOrderBtn = document.getElementById('cancel-delete-order');
    const confirmDeleteOrderBtn = document.getElementById('confirm-delete-order');

    // Función para abrir modal de eliminación
    window.openDeleteOrderModal = function(orderId) {
        currentOrderIdForDelete = orderId;
        const message = `¿Estás seguro que deseas eliminar la orden #${orderId}? Esta acción no se puede deshacer.`;
        document.getElementById('delete-message').textContent = message;
        deleteOrderModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    // Función para cerrar modal de eliminación
    function closeDeleteOrderModal() {
        deleteOrderModal.classList.remove('active');
        document.body.style.overflow = '';
        currentOrderIdForDelete = null;
    }

    // Función para confirmar eliminación
    function confirmDeleteOrder() {
        if (!currentOrderIdForDelete) return;

        fetch(`<?= BASE_URL ?>/admin/order/delete.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_ids: [currentOrderIdForDelete]
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Orden eliminada correctamente', 'success');
                    loadOrders();
                } else {
                    throw new Error(data.message || 'Error al eliminar la orden');
                }
            })
            .catch(error => {
                showAlert(error.message, 'error');
            })
            .finally(() => {
                closeDeleteOrderModal();
            });
    }

    // Event listeners
    cancelDeleteOrderBtn.addEventListener('click', closeDeleteOrderModal);
    confirmDeleteOrderBtn.addEventListener('click', confirmDeleteOrder);

    // Cerrar modal con el botón X
    const modalCloseBtn = deleteOrderModal.querySelector('.modal-close');
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeDeleteOrderModal);
    }

    // Cerrar modal al hacer clic fuera del contenido
    deleteOrderModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteOrderModal();
        }
    });

    // Cerrar modal con tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && deleteOrderModal.classList.contains('active')) {
            closeDeleteOrderModal();
        }
    });
});