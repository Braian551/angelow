<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Verificar que el usuario tenga rol de delivery
requireRole('delivery');

$driverId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title>Órdenes Disponibles - Transportista</title>
    <link rel="icon" href="<?= BASE_URL ?>/images/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboarddelivery.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/ordersadmin.css">
</head>
<body>
    <div class="delivery-container">
        <!-- Sidebar -->
        <?php require_once __DIR__ . '/../layouts/delivery/asidedelivery.php'; ?>

        <!-- Main Content -->
        <main class="delivery-content">
            <!-- Header -->
            <?php require_once __DIR__ . '/../layouts/delivery/headerdelivery.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <h1>
                        <i class="fas fa-box"></i> Órdenes Disponibles
                    </h1>
                    <div class="breadcrumb">
                        <a href="<?= BASE_URL ?>/delivery/dashboarddeli.php">Dashboard</a> / <span>Órdenes</span>
                    </div>
                </div>

                <!-- Pestañas de categorías -->
                <div class="order-tabs">
                    <button class="tab-btn active" data-tab="available" onclick="switchTab('available')">
                        <i class="fas fa-inbox"></i>
                        <span>Disponibles</span>
                        <span class="tab-count" id="count-available">0</span>
                    </button>
                    <button class="tab-btn" data-tab="assigned" onclick="switchTab('assigned')">
                        <i class="fas fa-user-clock"></i>
                        <span>Asignadas a mí</span>
                        <span class="tab-count" id="count-assigned">0</span>
                    </button>
                    <button class="tab-btn" data-tab="active" onclick="switchTab('active')">
                        <i class="fas fa-route"></i>
                        <span>En proceso</span>
                        <span class="tab-count" id="count-active">0</span>
                    </button>
                    <button class="tab-btn" data-tab="completed" onclick="switchTab('completed')">
                        <i class="fas fa-check-circle"></i>
                        <span>Completadas</span>
                        <span class="tab-count" id="count-completed">0</span>
                    </button>
                </div>

                <!-- Filtros -->
                <div class="card filters-card">
                    <div class="filters-header">
                        <div class="filters-title">
                            <i class="fas fa-sliders-h"></i>
                            <h3>Filtros</h3>
                        </div>
                    </div>

                    <form id="search-orders-form" class="filters-form">
                        <div class="search-bar">
                            <div class="search-input-wrapper">
                                <i class="fas fa-search search-icon"></i>
                                <input 
                                    type="text" 
                                    id="search-input" 
                                    name="search" 
                                    placeholder="Buscar por N° orden, cliente, dirección..." 
                                    class="search-input"
                                    autocomplete="off">
                                <button type="button" class="search-clear" id="clear-search" style="display: none;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Resumen de resultados -->
                <div class="results-summary">
                    <div class="results-info">
                        <i class="fas fa-list-ul"></i>
                        <p id="results-count">Cargando órdenes...</p>
                    </div>
                    <div class="quick-actions">
                        <button class="btn-action btn-refresh" id="refresh-orders" title="Actualizar">
                            <i class="fas fa-sync-alt"></i>
                            <span>Actualizar</span>
                        </button>
                    </div>
                </div>

                <!-- Listado de órdenes -->
                <div class="orders-table-container">
                    <div class="orders-grid" id="orders-container">
                        <div class="loading-row">
                            <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Cargando órdenes...</div>
                        </div>
                    </div>
                </div>

                <!-- Paginación -->
                <div class="pagination" id="pagination-container"></div>
            </div>
        </main>
    </div>

    <!-- Modal para aceptar orden -->
    <div id="accept-order-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-check-circle"></i> Aceptar Orden</h2>
                <button class="modal-close" onclick="closeModal('accept-order-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas aceptar esta orden de entrega?</p>
                <div class="order-details" id="accept-order-details"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('accept-order-modal')">Cancelar</button>
                <button class="btn btn-success" id="confirm-accept-order">
                    <i class="fas fa-check"></i> Aceptar Orden
                </button>
            </div>
        </div>
    </div>

    <!-- Modal para rechazar orden -->
    <div id="reject-order-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-times-circle"></i> Rechazar Orden</h2>
                <button class="modal-close" onclick="closeModal('reject-order-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Por qué deseas rechazar esta orden?</p>
                <textarea id="reject-reason" class="form-control" rows="3" placeholder="Razón del rechazo (opcional)"></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('reject-order-modal')">Cancelar</button>
                <button class="btn btn-danger" id="confirm-reject-order">
                    <i class="fas fa-times"></i> Rechazar Orden
                </button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const BASE_URL = '<?= BASE_URL ?>';
        const DRIVER_ID = '<?= $driverId ?>';
        
        // Variables globales
        let currentTab = 'available';
        let currentPage = 1;
        const perPage = 12;
        let currentFilters = {};
        let currentDeliveryId = null;
        
        // Elementos del DOM
        const ordersContainer = document.getElementById('orders-container');
        const paginationContainer = document.getElementById('pagination-container');
        const resultsCount = document.getElementById('results-count');
        const searchForm = document.getElementById('search-orders-form');
        const searchInput = document.getElementById('search-input');
        const clearSearchBtn = document.getElementById('clear-search');
        const refreshBtn = document.getElementById('refresh-orders');
        
        // ====================================
        // FUNCIÓN PARA CAMBIAR DE PESTAÑA
        // ====================================
        window.switchTab = function(tab) {
            currentTab = tab;
            currentPage = 1;
            
            // Actualizar UI de pestañas
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
            
            // Cargar órdenes
            loadOrders();
        };
        
        // ====================================
        // FUNCIÓN PARA CARGAR ÓRDENES
        // ====================================
        function loadOrders() {
            ordersContainer.innerHTML = `
                <div class="loading-row">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i> Cargando órdenes...
                    </div>
                </div>
            `;
            
            // Construir URL con parámetros
            const params = new URLSearchParams();
            params.append('tab', currentTab);
            params.append('page', currentPage);
            params.append('per_page', perPage);
            params.append('driver_id', DRIVER_ID);
            
            // Agregar filtros
            if (currentFilters.search) {
                params.append('search', currentFilters.search);
            }
            
            // Realizar petición
            fetch(`${BASE_URL}/delivery/api/get_orders.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderOrders(data.orders);
                        renderPagination(data.meta);
                        updateResultsCount(data.meta);
                        updateTabCounts(data.counts);
                    } else {
                        throw new Error(data.error || 'Error desconocido');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    ordersContainer.innerHTML = `
                        <div class="error-row">
                            <div class="error-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="error-content">
                                <h3>Error al cargar órdenes</h3>
                                <p>${error.message}</p>
                                <button onclick="loadOrders()" class="btn btn-primary">
                                    <i class="fas fa-redo"></i> Reintentar
                                </button>
                            </div>
                        </div>
                    `;
                });
        }
        
        // ====================================
        // FUNCIÓN PARA RENDERIZAR ÓRDENES
        // ====================================
        function renderOrders(orders) {
            if (orders.length === 0) {
                ordersContainer.innerHTML = `
                    <div class="empty-row">
                        <div class="empty-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <div class="empty-content">
                            <h3>No hay órdenes ${getTabTitle()}</h3>
                            <p>Actualmente no hay órdenes disponibles en esta categoría.</p>
                        </div>
                    </div>
                `;
                return;
            }
            
            let html = '';
            
            orders.forEach(order => {
                html += renderOrderCard(order);
            });
            
            ordersContainer.innerHTML = html;
        }
        
        // ====================================
        // FUNCIÓN PARA RENDERIZAR TARJETA DE ORDEN
        // ====================================
        function renderOrderCard(order) {
            const formattedTotal = new Intl.NumberFormat('es-CO', {
                style: 'currency',
                currency: 'COP',
                minimumFractionDigits: 0
            }).format(order.total);
            
            const formattedDate = new Date(order.created_at).toLocaleDateString('es-CO', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
            
            let statusBadge = '';
            let actionButtons = '';
            
            // Determinar badge y botones según el estado
            if (currentTab === 'available') {
                statusBadge = '<span class="status-badge shipped">Disponible</span>';
                actionButtons = `
                    <button class="btn btn-success btn-sm" onclick="openAcceptModal(${order.delivery_id || order.id}, '${order.order_number}')">
                        <i class="fas fa-check"></i> Aceptar
                    </button>
                `;
            } else if (currentTab === 'assigned') {
                statusBadge = '<span class="status-badge driver_assigned">Asignada</span>';
                actionButtons = `
                    <button class="btn btn-success btn-sm" onclick="acceptOrder(${order.delivery_id})">
                        <i class="fas fa-check"></i> Aceptar
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="openRejectModal(${order.delivery_id})">
                        <i class="fas fa-times"></i> Rechazar
                    </button>
                `;
            } else if (currentTab === 'active') {
                const status = order.delivery_status;
                if (status === 'driver_accepted') {
                    statusBadge = '<span class="status-badge driver_accepted">Aceptada</span>';
                    actionButtons = `
                        <button class="btn btn-primary btn-sm" onclick="startTrip(${order.delivery_id})">
                            <i class="fas fa-play"></i> Iniciar Recorrido
                        </button>
                    `;
                } else if (status === 'in_transit') {
                    statusBadge = '<span class="status-badge in_transit">En Tránsito</span>';
                    actionButtons = `
                        <button class="btn btn-info btn-sm" onclick="markArrived(${order.delivery_id})">
                            <i class="fas fa-map-marker-alt"></i> He Llegado
                        </button>
                    `;
                } else if (status === 'arrived') {
                    statusBadge = '<span class="status-badge arrived">En Destino</span>';
                    actionButtons = `
                        <button class="btn btn-success btn-sm" onclick="completeDelivery(${order.delivery_id})">
                            <i class="fas fa-check-double"></i> Completar Entrega
                        </button>
                    `;
                }
            } else if (currentTab === 'completed') {
                statusBadge = '<span class="status-badge delivered">Entregado</span>';
                const deliveredDate = new Date(order.delivered_at).toLocaleDateString('es-CO');
                actionButtons = `<small class="text-muted">Entregado: ${deliveredDate}</small>`;
            }
            
            return `
                <div class="order-card">
                    <div class="order-card-header">
                        <div>
                            <h3>#${order.order_number}</h3>
                            ${statusBadge}
                        </div>
                        <span class="order-total">${formattedTotal}</span>
                    </div>
                    <div class="order-card-body">
                        <div class="order-info-row">
                            <i class="fas fa-user"></i>
                            <span><strong>Cliente:</strong> ${order.customer_name}</span>
                        </div>
                        <div class="order-info-row">
                            <i class="fas fa-phone"></i>
                            <span>${order.customer_phone || 'No disponible'}</span>
                        </div>
                        <div class="order-info-row">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>${order.shipping_address}, ${order.shipping_city}</span>
                        </div>
                        ${order.delivery_notes ? `
                        <div class="order-info-row">
                            <i class="fas fa-sticky-note"></i>
                            <span><em>${order.delivery_notes}</em></span>
                        </div>
                        ` : ''}
                        <div class="order-info-row">
                            <i class="fas fa-calendar"></i>
                            <span>${formattedDate}</span>
                        </div>
                    </div>
                    <div class="order-card-footer">
                        ${actionButtons}
                    </div>
                </div>
            `;
        }
        
        // ====================================
        // FUNCIONES DE ACCIÓN
        // ====================================
        window.openAcceptModal = function(deliveryId, orderNumber) {
            currentDeliveryId = deliveryId;
            document.getElementById('accept-order-details').innerHTML = `
                <p><strong>Orden:</strong> #${orderNumber}</p>
            `;
            document.getElementById('accept-order-modal').classList.add('show');
        };
        
        window.openRejectModal = function(deliveryId) {
            currentDeliveryId = deliveryId;
            document.getElementById('reject-order-modal').classList.add('show');
        };
        
        window.closeModal = function(modalId) {
            document.getElementById(modalId).classList.remove('show');
            currentDeliveryId = null;
        };
        
        window.acceptOrder = function(deliveryId) {
            performAction('accept_order', deliveryId);
        };
        
        window.startTrip = function(deliveryId) {
            if (confirm('¿Iniciar el recorrido hacia el cliente?')) {
                performAction('start_trip', deliveryId);
            }
        };
        
        window.markArrived = function(deliveryId) {
            if (confirm('¿Has llegado al destino?')) {
                performAction('mark_arrived', deliveryId);
            }
        };
        
        window.completeDelivery = function(deliveryId) {
            const recipientName = prompt('¿Nombre de quien recibió el pedido?');
            if (recipientName && recipientName.trim() !== '') {
                const notes = prompt('Notas adicionales (opcional)') || '';
                performAction('complete_delivery', deliveryId, { recipient_name: recipientName, notes: notes });
            }
        };
        
        function performAction(action, deliveryId, additionalData = {}) {
            const data = {
                action: action,
                delivery_id: deliveryId,
                ...additionalData
            };
            
            // Obtener ubicación si está disponible
            if (navigator.geolocation && ['start_trip', 'mark_arrived', 'update_location'].includes(action)) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    data.latitude = position.coords.latitude;
                    data.longitude = position.coords.longitude;
                    sendDeliveryRequest(data);
                }, function(error) {
                    console.warn('No se pudo obtener la ubicación:', error);
                    sendDeliveryRequest(data);
                });
            } else {
                sendDeliveryRequest(data);
            }
        }
        
        function sendDeliveryRequest(data) {
            fetch(BASE_URL + '/delivery/delivery_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showNotification(result.message, 'success');
                    closeModal('accept-order-modal');
                    closeModal('reject-order-modal');
                    setTimeout(() => loadOrders(), 1000);
                } else {
                    showNotification('Error: ' + (result.message || 'No se pudo procesar la acción'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al procesar la solicitud', 'error');
            });
        }
        
        // ====================================
        // BOTONES DE MODALES
        // ====================================
        document.getElementById('confirm-accept-order').addEventListener('click', function() {
            if (currentDeliveryId) {
                performAction('accept_order', currentDeliveryId);
            }
        });
        
        document.getElementById('confirm-reject-order').addEventListener('click', function() {
            if (currentDeliveryId) {
                const reason = document.getElementById('reject-reason').value || 'Sin razón especificada';
                performAction('reject_order', currentDeliveryId, { reason: reason });
            }
        });
        
        // ====================================
        // FUNCIONES AUXILIARES
        // ====================================
        function renderPagination(meta) {
            if (meta.total_pages <= 1) {
                paginationContainer.innerHTML = '';
                return;
            }
            
            let html = '<div class="pagination-inner">';
            
            if (currentPage > 1) {
                html += `<button class="page-btn" onclick="goToPage(${currentPage - 1})">
                    <i class="fas fa-chevron-left"></i>
                </button>`;
            }
            
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(meta.total_pages, currentPage + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">
                    ${i}
                </button>`;
            }
            
            if (currentPage < meta.total_pages) {
                html += `<button class="page-btn" onclick="goToPage(${currentPage + 1})">
                    <i class="fas fa-chevron-right"></i>
                </button>`;
            }
            
            html += '</div>';
            paginationContainer.innerHTML = html;
        }
        
        window.goToPage = function(page) {
            currentPage = page;
            loadOrders();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        };
        
        function updateResultsCount(meta) {
            const { total, page, per_page } = meta;
            const start = total > 0 ? ((page - 1) * per_page) + 1 : 0;
            const end = Math.min(page * per_page, total);
            
            if (total === 0) {
                resultsCount.innerHTML = '<i class="fas fa-info-circle"></i> No se encontraron órdenes';
            } else {
                resultsCount.textContent = `${start}-${end} de ${total} ${total === 1 ? 'orden' : 'órdenes'}`;
            }
        }
        
        function updateTabCounts(counts) {
            document.getElementById('count-available').textContent = counts.available || 0;
            document.getElementById('count-assigned').textContent = counts.assigned || 0;
            document.getElementById('count-active').textContent = counts.active || 0;
            document.getElementById('count-completed').textContent = counts.completed || 0;
        }
        
        function getTabTitle() {
            const titles = {
                'available': 'disponibles',
                'assigned': 'asignadas',
                'active': 'en proceso',
                'completed': 'completadas'
            };
            return titles[currentTab] || '';
        }
        
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                background: ${type === 'success' ? '#4caf50' : '#f44336'};
                color: white;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                z-index: 10000;
                animation: slideIn 0.3s ease;
            `;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // ====================================
        // BÚSQUEDA
        // ====================================
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            clearSearchBtn.style.display = query.length > 0 ? 'flex' : 'none';
            
            searchTimeout = setTimeout(() => {
                currentPage = 1;
                currentFilters.search = query;
                loadOrders();
            }, 500);
        });
        
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            this.style.display = 'none';
            currentFilters.search = '';
            loadOrders();
        });
        
        // ====================================
        // BOTÓN REFRESCAR
        // ====================================
        refreshBtn.addEventListener('click', function() {
            this.querySelector('i').classList.add('fa-spin');
            loadOrders();
            setTimeout(() => {
                this.querySelector('i').classList.remove('fa-spin');
            }, 1000);
        });
        
        // ====================================
        // CARGAR ÓRDENES AL INICIO
        // ====================================
        loadOrders();
        
        // Auto-actualizar cada 30 segundos
        setInterval(() => {
            if (!document.querySelector('.modal.show')) {
                loadOrders();
            }
        }, 30000);
    });
    </script>
    
    <style>
    .order-tabs {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }
    
    .tab-btn {
        flex: 1;
        min-width: 150px;
        padding: 1rem 1.5rem;
        background: white;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        position: relative;
    }
    
    .tab-btn:hover {
        border-color: #2196F3;
        transform: translateY(-2px);
    }
    
    .tab-btn.active {
        background: #2196F3;
        color: white;
        border-color: #2196F3;
    }
    
    .tab-count {
        background: rgba(0,0,0,0.1);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .tab-btn.active .tab-count {
        background: rgba(255,255,255,0.3);
    }
    
    .orders-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 2rem;
        margin-top: 2rem;
    }
    
    .order-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.3s;
        overflow: hidden;
    }
    
    .order-card:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        transform: translateY(-4px);
    }
    
    .order-card-header {
        padding: 1.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .order-card-header h3 {
        margin: 0;
        font-size: 1.3rem;
    }
    
    .order-total {
        font-size: 1.5rem;
        font-weight: bold;
    }
    
    .order-card-body {
        padding: 1.5rem;
    }
    
    .order-info-row {
        display: flex;
        gap: 1rem;
        margin-bottom: 0.8rem;
        align-items: flex-start;
    }
    
    .order-info-row i {
        color: #666;
        width: 20px;
        margin-top: 3px;
    }
    
    .order-card-footer {
        padding: 1rem 1.5rem;
        background: #f5f5f5;
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
    }
    
    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    
    .loading-row, .error-row, .empty-row {
        grid-column: 1 / -1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem;
        text-align: center;
    }
    
    .loading-spinner, .error-icon, .empty-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: #666;
    }
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .status-badge.shipped {
        background: #e3f2fd;
        color: #1976d2;
    }
    
    .status-badge.driver_assigned {
        background: #fff3e0;
        color: #f57c00;
    }
    
    .status-badge.driver_accepted {
        background: #e1f5fe;
        color: #0288d1;
    }
    
    .status-badge.in_transit {
        background: #f3e5f5;
        color: #7b1fa2;
    }
    
    .status-badge.arrived {
        background: #e0f2f1;
        color: #00796b;
    }
    
    .status-badge.delivered {
        background: #e8f5e9;
        color: #388e3c;
    }
    
    /* Modales */
    .modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        align-items: center;
        justify-content: center;
    }
    
    .modal.show {
        display: flex;
    }
    
    .modal-content {
        background: white;
        border-radius: 12px;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }
    
    .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h2 {
        margin: 0;
        font-size: 1.5rem;
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #666;
    }
    
    .modal-body {
        padding: 1.5rem;
    }
    
    .modal-footer {
        padding: 1.5rem;
        border-top: 1px solid #e0e0e0;
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }
    
    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 1rem;
        font-family: inherit;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    @media (max-width: 768px) {
        .orders-grid {
            grid-template-columns: 1fr;
        }
        
        .order-tabs {
            flex-direction: column;
        }
        
        .tab-btn {
            width: 100%;
        }
    }
    </style>
</body>
</html>
