// orders.js - Interacciones modernas y robustas
document.addEventListener('DOMContentLoaded', function() {
    // Elementos globales
    const orderDetailsModal = document.getElementById('order-details-modal');
    const trackingModal = document.getElementById('tracking-modal');
    const orderSearch = document.getElementById('order-search');
    const statusFilter = document.getElementById('status-filter');
    
    // Inicializar funcionalidades
    initFilters();
    initSearch();
    initOrderActions();
    initModals();

    // Filtros
    function initFilters() {
        document.getElementById('apply-filter').addEventListener('click', function() {
            const status = statusFilter.value;
            const search = orderSearch.value;
            let url = `?status=${status}`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            window.location.href = url;
        });

        // Filtro automático al cambiar selección
        statusFilter.addEventListener('change', function() {
            if (this.value) {
                document.getElementById('apply-filter').click();
            }
        });
    }

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
        // Ver detalles
        document.querySelectorAll('.btn-view-details').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                loadOrderDetails(orderId);
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

    // Modales
    function initModals() {
        // Cerrar modales
        document.querySelectorAll('.close-modal').forEach(closeBtn => {
            closeBtn.addEventListener('click', function() {
                this.closest('.modal').style.display = 'none';
            });
        });

        // Cerrar al hacer clic fuera
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        });

        // Cerrar con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.style.display = 'none';
                });
            }
        });
    }

    // Cargar detalles de orden
    async function loadOrderDetails(orderId) {
        try {
            showLoader(orderDetailsModal);
            orderDetailsModal.style.display = 'flex';

            const response = await fetch(`${BASE_URL}/users/orders/order_details.php?id=${orderId}`);
            const data = await response.json();

            if (data.success) {
                document.getElementById('order-details-content').innerHTML = data.html;
                initDetailActions();
            } else {
                showNotification(data.message, 'error');
                orderDetailsModal.style.display = 'none';
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error al cargar detalles del pedido', 'error');
            orderDetailsModal.style.display = 'none';
        }
    }

    // Rastrear orden
    async function trackOrder(orderId) {
        try {
            showLoader(trackingModal);
            trackingModal.style.display = 'flex';

            const response = await fetch(`${BASE_URL}/users/orders/track_order.php?id=${orderId}`);
            const data = await response.json();

            if (data.success) {
                document.getElementById('tracking-content').innerHTML = data.html;
                
                // Actualizar en tiempo real si está en camino
                if (data.tracking_active) {
                    startLiveTracking(orderId);
                }
            } else {
                document.getElementById('tracking-content').innerHTML = `
                    <div class="tracking-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Información de rastreo no disponible</h3>
                        <p>${data.message}</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error:', error);
            document.getElementById('tracking-content').innerHTML = `
                <div class="tracking-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Error de conexión</h3>
                    <p>No se pudo cargar la información de rastreo</p>
                </div>
            `;
        }
    }

    // Rastreo en tiempo real
    function startLiveTracking(orderId) {
        const trackingInterval = setInterval(async () => {
            try {
                const response = await fetch(`${BASE_URL}/users/orders/live_tracking.php?id=${orderId}`);
                const data = await response.json();
                
                if (data.success) {
                    updateTrackingDisplay(data.tracking_data);
                } else {
                    clearInterval(trackingInterval);
                }
            } catch (error) {
                console.error('Tracking error:', error);
                clearInterval(trackingInterval);
            }
        }, 10000); // Actualizar cada 10 segundos

        // Limpiar intervalo cuando se cierre el modal
        trackingModal.addEventListener('close', () => {
            clearInterval(trackingInterval);
        });
    }

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

    // Utilidades
    function showLoader(container) {
        container.querySelector('.modal-content > div').innerHTML = `
            <div class="loader-container">
                <div class="loader"></div>
                <p>Cargando...</p>
            </div>
        `;
    }

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