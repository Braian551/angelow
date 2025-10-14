// orders.js - Interacciones modernas y robustas
document.addEventListener('DOMContentLoaded', function() {
    // Elementos globales
    const orderSearch = document.getElementById('order-search');
    const statusFilter = document.getElementById('status-filter');
    
    // Inicializar funcionalidades
    initSearch();
    initOrderActions();

    // Filtros - REMOVIDO: Funcionalidad básica de filtrado
    // initFilters() { ... }

    // Búsqueda en tiempo real
    function initSearch() {
        let searchTimeout;
        orderSearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3 || this.value.length === 0) {
                    document.getElementById('apply-filter').click();
                }
            }, 500);
        });
    }

    // Acciones de orden
    function initOrderActions() {
        // Ver detalles - redirigir a página de detalles
        document.querySelectorAll('.btn-view-details').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                window.location.href = `order_detail.php?id=${orderId}`;
            });
        });

        // Cancelar orden
        document.querySelectorAll('.btn-cancel').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                const orderCard = this.closest('.order-card');
                const orderNumber = orderCard.dataset.orderNumber;
                
                showConfirmationModal(
                    'Cancelar Pedido',
                    `¿Estás seguro de que quieres cancelar el pedido #${orderNumber}?`,
                    'cancelar',
                    () => cancelOrder(orderId, orderCard)
                );
            });
        });

        // Rastrear envío
        document.querySelectorAll('.btn-track').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                trackOrder(orderId);
            });
        });

        // Volver a pedir
        document.querySelectorAll('.btn-reorder').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                reorderItems(orderId);
            });
        });
    }

    // Modales - REMOVIDO: Ya no se usan modales para detalles
    // initModals() { ... }

    // Funciones de modal - REMOVIDAS: Ahora se usan páginas separadas
    // loadOrderDetails(orderId) { ... }

    // Rastrear envío - redirigir a página de rastreo
    function trackOrder(orderId) {
        window.location.href = `track_order.php?id=${orderId}`;
    }

    // startLiveTracking(orderId) { ... }

    // Cancelar orden
    async function cancelOrder(orderId, orderCard) {
        try {
            const response = await fetch(`${BASE_URL}/users/ajax/ajax_cancel_order.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${orderId}`
            });
            const data = await response.json();

            if (data.success) {
                showNotification(data.message, 'success');
                
                // Actualizar UI
                const statusBadge = orderCard.querySelector('.status-badge');
                statusBadge.textContent = 'Cancelado';
                statusBadge.className = 'status-badge status-cancelled';
                
                // Remover botones de acción
                orderCard.querySelectorAll('.btn-cancel, .btn-track').forEach(btn => btn.remove());
                
                // Actualizar timeline
                orderCard.querySelector('.order-timeline').innerHTML = renderOrderTimeline('cancelled');
            } else {
                showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error al cancelar el pedido', 'error');
        }
    }

    // Volver a pedir
    async function reorderItems(orderId) {
        try {
            const response = await fetch(`${BASE_URL}/users/ajax/ajax_reorder.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `order_id=${orderId}`
            });
            const data = await response.json();

            if (data.success) {
                showNotification(data.message, 'success');
                // Redirigir al carrito o mostrar opciones
                if (data.redirect_url) {
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 1500);
                }
            } else {
                showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error al procesar la solicitud', 'error');
        }
    }

    // Funciones de modal de detalles - REMOVIDAS: Ya no se usan
    // initDetailActions() { ... }
    // updateTrackingDisplay(trackingData) { ... }

    // Utilidades - showLoader REMOVIDO: Ya no se usan modales

    function showConfirmationModal(title, message, confirmText, onConfirm) {
        const modal = document.createElement('div');
        modal.className = 'modal glass-effect confirmation-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <h3>${title}</h3>
                <p>${message}</p>
                <div class="confirmation-actions">
                    <button class="btn-secondary cancel-btn">Cancelar</button>
                    <button class="btn-danger confirm-btn">${confirmText}</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        modal.style.display = 'flex';

        modal.querySelector('.cancel-btn').addEventListener('click', () => {
            modal.remove();
        });

        modal.querySelector('.confirm-btn').addEventListener('click', () => {
            onConfirm();
            modal.remove();
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `floating-notification animate__animated animate__fadeInRight ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${getNotificationIcon(type)}"></i>
                <span>${message}</span>
            </div>
            <button class="close-notification">&times;</button>
        `;
        document.querySelector('.user-main-content').appendChild(notification);

        // Auto-remover después de 5 segundos
        setTimeout(() => {
            notification.classList.add('animate__fadeOutRight');
            setTimeout(() => notification.remove(), 500);
        }, 5000);

        // Cerrar manualmente
        notification.querySelector('.close-notification').addEventListener('click', () => {
            notification.classList.add('animate__fadeOutRight');
            setTimeout(() => notification.remove(), 500);
        });
    }

    function getNotificationIcon(type) {
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || 'fa-info-circle';
    }

    function renderOrderTimeline(status) {
        // Implementación simplificada para JS
        const steps = ['pending', 'processing', 'shipped', 'delivered'];
        const currentIndex = steps.indexOf(status);
        
        let html = '<div class="timeline">';
        steps.forEach((step, index) => {
            const isCompleted = index <= currentIndex;
            const isActive = index === currentIndex;
            
            html += `<div class="timeline-step ${isCompleted ? 'completed' : ''} ${isActive ? 'active' : ''}">`;
            html += `<div class="timeline-dot"></div>`;
            html += `<span class="timeline-label">${getStatusText(step)}</span>`;
            html += `</div>`;
        });
        html += '</div>';
        
        return html;
    }

    function getStatusText(status) {
        const statuses = {
            'pending': 'Pendiente',
            'processing': 'En Proceso',
            'shipped': 'Enviado',
            'delivered': 'Entregado',
            'cancelled': 'Cancelado'
        };
        return statuses[status] || status;
    }
});