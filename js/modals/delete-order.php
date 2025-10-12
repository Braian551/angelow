<script>
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

        // Deshabilitar botón para evitar clicks múltiples
        confirmDeleteOrderBtn.disabled = true;
        confirmDeleteOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';

        const deleteUrl = '<?= BASE_URL ?>/admin/order/delete.php';
        console.log('Enviando solicitud DELETE a:', deleteUrl);
        console.log('Order ID:', currentOrderIdForDelete);

        fetch(deleteUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_ids: [currentOrderIdForDelete]
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    if (response.status === 403) {
                        throw new Error('No tienes permisos para eliminar órdenes (403 Forbidden)');
                    } else if (response.status === 404) {
                        throw new Error('No se encontró el archivo de eliminación (404)');
                    }
                    throw new Error(`Error del servidor: ${response.status}`);
                }
                
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    // Cerrar el modal
                    closeDeleteOrderModal();
                    
                    // Rehabilitar botón antes de cerrar
                    confirmDeleteOrderBtn.disabled = false;
                    confirmDeleteOrderBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Eliminar';
                    
                    // Mostrar alerta de éxito
                    showAlert('Orden eliminada correctamente', 'success');
                    
                    // Recargar órdenes si la función existe
                    if (typeof window.loadOrders === 'function') {
                        window.loadOrders();
                    } else {
                        // Si no existe, recargar la página
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    throw new Error(data.message || 'Error al eliminar la orden');
                }
            })
            .catch(error => {
                console.error('Error al eliminar orden:', error);
                showAlert(error.message || 'Error al eliminar la orden', 'error');
                
                // Rehabilitar botón en caso de error
                confirmDeleteOrderBtn.disabled = false;
                confirmDeleteOrderBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Eliminar';
            });
    }

    // Event listeners
    if (cancelDeleteOrderBtn) {
        cancelDeleteOrderBtn.addEventListener('click', closeDeleteOrderModal);
    }
    
    if (confirmDeleteOrderBtn) {
        confirmDeleteOrderBtn.addEventListener('click', confirmDeleteOrder);
    }

    // Cerrar modal con el botón X
    const modalCloseBtn = deleteOrderModal?.querySelector('.modal-close');
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeDeleteOrderModal);
    }

    // Cerrar modal al hacer clic fuera del contenido
    if (deleteOrderModal) {
        deleteOrderModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteOrderModal();
            }
        });
    }

    // Cerrar modal con tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && deleteOrderModal?.classList.contains('active')) {
            closeDeleteOrderModal();
        }
    });

    console.log('✓ Modal de eliminación de órdenes inicializado correctamente');
});
</script>
