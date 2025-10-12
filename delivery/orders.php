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
            
            const url = `${BASE_URL}/delivery/api/get_orders.php?${params.toString()}`;
            console.log('Fetching orders from:', url);
            
            // Realizar petición
            fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                }
            })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    
                    // Verificar si la respuesta es JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            console.error('Response is not JSON:', text);
                            throw new Error('La respuesta del servidor no es JSON válido. Verifica la consola del navegador.');
                        });
                    }
                    
                    // Verificar código de estado
                    if (response.status === 401) {
                        throw new Error('Sesión expirada. Por favor inicia sesión nuevamente.');
                    }
                    if (response.status === 403) {
                        throw new Error('No tienes permisos para acceder a esta sección.');
                    }
                    if (!response.ok) {
                        throw new Error(`Error del servidor: ${response.status}`);
                    }
                    
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    
                    if (data.success) {
                        renderOrders(data.orders);
                        renderPagination(data.meta);
                        updateResultsCount(data.meta);
                        updateTabCounts(data.counts);
                    } else {
                        console.error('API Error:', data.error);
                        if (data.debug) {
                            console.error('Debug info:', data.debug);
                        }
                        throw new Error(data.error || 'Error desconocido');
                    }
                })
                .catch(error => {
                    console.error('Error completo:', error);
                    
                    let errorMessage = error.message;
                    let actionButton = '';
                    let debugInfo = '';
                    
                    // Intentar extraer información de debug si está disponible
                    try {
                        if (error.response && error.response.debug) {
                            debugInfo = `
                                <div style="margin-top: 1rem; padding: 1rem; background: var(--blue-05); border-radius: 8px; text-align: left; font-size: 1.2rem;">
                                    <strong>Info de Debug:</strong><br>
                                    <pre style="margin: 0.5rem 0; font-family: monospace;">${JSON.stringify(error.response.debug, null, 2)}</pre>
                                </div>
                            `;
                        }
                    } catch (e) {
                        console.error('Error al procesar debug info:', e);
                    }
                    
                    // Manejo específico de errores de sesión
                    if (error.message.includes('Sesión') || error.message.includes('sesión')) {
                        actionButton = `
                            <button onclick="window.location.href='${BASE_URL}/auth/login.php'" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                            </button>
                        `;
                    } else if (error.message.includes('permisos') || error.message.includes('rol')) {
                        errorMessage += '<br><br><small>Es posible que tu sesión tenga un rol incorrecto. Intenta cerrar sesión y volver a iniciarla.</small>';
                        actionButton = `
                            <button onclick="window.location.href='${BASE_URL}/auth/logout.php'" class="btn btn-danger">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </button>
                            <button onclick="loadOrders()" class="btn btn-primary">
                                <i class="fas fa-redo"></i> Reintentar
                            </button>
                        `;
                    } else {
                        actionButton = `
                            <button onclick="loadOrders()" class="btn btn-primary">
                                <i class="fas fa-redo"></i> Reintentar
                            </button>
                        `;
                    }
                    
                    ordersContainer.innerHTML = `
                        <div class="error-row">
                            <div class="error-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="error-content">
                                <h3>Error al cargar órdenes</h3>
                                <p>${errorMessage}</p>
                                ${debugInfo}
                                <div style="margin-top: 1.5rem;">
                                    ${actionButton}
                                </div>
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
                statusBadge = '<span class="status-badge shipped"><i class="fas fa-sparkles"></i> Disponible</span>';
                actionButtons = `
                    <button class="btn btn-success btn-sm" onclick="openAcceptModal(${order.delivery_id || order.id}, '${order.order_number}')">
                        <i class="fas fa-hand-pointer"></i> Aceptar
                    </button>
                `;
            } else if (currentTab === 'assigned') {
                statusBadge = '<span class="status-badge driver_assigned"><i class="fas fa-user-check"></i> Asignada</span>';
                actionButtons = `
                    <button class="btn btn-success btn-sm" onclick="acceptOrder(${order.delivery_id})">
                        <i class="fas fa-thumbs-up"></i> Aceptar
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="openRejectModal(${order.delivery_id})">
                        <i class="fas fa-times-circle"></i> Rechazar
                    </button>
                `;
            } else if (currentTab === 'active') {
                const status = order.delivery_status;
                if (status === 'driver_accepted') {
                    statusBadge = '<span class="status-badge driver_accepted"><i class="fas fa-thumbs-up"></i> Aceptada</span>';
                    actionButtons = `
                        <button class="btn btn-primary btn-sm" onclick="startTrip(${order.delivery_id})">
                            <i class="fas fa-play-circle"></i> Iniciar Recorrido
                        </button>
                    `;
                } else if (status === 'in_transit') {
                    statusBadge = '<span class="status-badge in_transit"><i class="fas fa-truck-moving"></i> En Tránsito</span>';
                    actionButtons = `
                        <button class="btn btn-info btn-sm" onclick="markArrived(${order.delivery_id})">
                            <i class="fas fa-flag-checkered"></i> He Llegado
                        </button>
                    `;
                } else if (status === 'arrived') {
                    statusBadge = '<span class="status-badge arrived"><i class="fas fa-map-pin"></i> En Destino</span>';
                    actionButtons = `
                        <button class="btn btn-success btn-sm" onclick="completeDelivery(${order.delivery_id})">
                            <i class="fas fa-check-double"></i> Completar Entrega
                        </button>
                    `;
                }
            } else if (currentTab === 'completed') {
                statusBadge = '<span class="status-badge delivered"><i class="fas fa-check-circle"></i> Entregado</span>';
                const deliveredDate = new Date(order.delivered_at).toLocaleDateString('es-CO');
                actionButtons = `<small class="text-muted"><i class="fas fa-calendar-check"></i> Entregado: ${deliveredDate}</small>`;
            }
            
            return `
                <div class="order-card">
                    <div class="order-card-header">
                        <div>
                            <h3><i class="fas fa-hashtag"></i>${order.order_number}</h3>
                            ${statusBadge}
                        </div>
                        <span class="order-total">${formattedTotal}</span>
                    </div>
                    <div class="order-card-body">
                        <div class="order-info-row">
                            <i class="fas fa-user-circle"></i>
                            <span><strong>Cliente:</strong> ${order.customer_name}</span>
                        </div>
                        <div class="order-info-row">
                            <i class="fas fa-phone"></i>
                            <span><strong>Teléfono:</strong> ${order.customer_phone || 'No disponible'}</span>
                        </div>
                        <div class="order-info-row">
                            <i class="fas fa-map-marked-alt"></i>
                            <span><strong>Dirección:</strong> ${order.shipping_address}, ${order.shipping_city}</span>
                        </div>
                        ${order.delivery_notes ? `
                        <div class="order-info-row">
                            <i class="fas fa-comment-dots"></i>
                            <span><strong>Notas:</strong> <em>${order.delivery_notes}</em></span>
                        </div>
                        ` : ''}
                        <div class="order-info-row">
                            <i class="fas fa-calendar-alt"></i>
                            <span><strong>Fecha:</strong> ${formattedDate}</span>
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
    /* ===== ESTILOS MINIMALISTAS AZUL - ORDERS GLASSMORPHISM ===== */
    
    /* ========== PAGE HEADER - ENCABEZADO MEJORADO ========== */
    .page-header {
        background: var(--glass-white);
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
        border-radius: 16px;
        padding: 2.5rem;
        margin-bottom: 2.5rem;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--glass-border);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }
    
    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, var(--blue-primary) 0%, var(--blue-light) 100%);
        opacity: 0.8;
    }
    
    .page-header:hover {
        box-shadow: var(--shadow-lg);
        background: var(--glass-white-hover);
    }
    
    .page-header h1 {
        font-size: 2.8rem;
        color: var(--blue-primary);
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 1.2rem;
        margin: 0 0 1.2rem 0;
        letter-spacing: -0.5px;
    }
    
    .page-header h1 i {
        font-size: 2.6rem;
        opacity: 0.9;
        background: var(--blue-10);
        padding: 0.8rem;
        border-radius: 12px;
        transition: var(--transition);
    }
    
    .page-header:hover h1 i {
        background: var(--blue-20);
        transform: scale(1.05);
    }
    
    .breadcrumb {
        font-size: 1.4rem;
        color: var(--text-medium);
        display: flex;
        align-items: center;
        gap: 0.8rem;
        flex-wrap: wrap;
        padding-left: 0.3rem;
    }
    
    .breadcrumb a {
        color: var(--blue-primary);
        text-decoration: none;
        font-weight: 500;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
    }
    
    .breadcrumb a:hover {
        background: var(--blue-10);
        color: var(--blue-dark);
    }
    
    .breadcrumb span {
        color: var(--text-dark);
        font-weight: 600;
    }
    
    /* ========== ORDER TABS - PESTAÑAS MINIMALISTAS ========== */
    .order-tabs {
        display: flex;
        gap: 1.2rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }
    
    .tab-btn {
        flex: 1;
        min-width: 160px;
        padding: 1.3rem 1.8rem;
        background: var(--glass-white);
        border: 1px solid var(--glass-border);
        border-radius: 14px;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        position: relative;
        font-weight: 600;
        font-size: 1.4rem;
        color: var(--text-medium);
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }
    
    .tab-btn::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: var(--blue-primary);
        transform: scaleX(0);
        transition: var(--transition);
    }
    
    .tab-btn:hover {
        border-color: var(--blue-30);
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
        background: var(--blue-05);
        color: var(--blue-primary);
    }
    
    .tab-btn:hover::before {
        transform: scaleX(1);
    }
    
    .tab-btn.active {
        background: var(--blue-primary);
        color: white;
        border-color: var(--blue-primary);
        box-shadow: var(--shadow-lg);
        transform: translateY(-2px);
    }
    
    .tab-btn.active::before {
        transform: scaleX(1);
        background: white;
        opacity: 0.3;
    }
    
    .tab-btn i {
        font-size: 1.7rem;
        opacity: 0.85;
        transition: var(--transition);
    }
    
    .tab-btn:hover i,
    .tab-btn.active i {
        opacity: 1;
        transform: scale(1.1);
    }
    
    .tab-count {
        background: var(--blue-20);
        color: var(--blue-dark);
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 1.2rem;
        font-weight: 700;
        min-width: 28px;
        text-align: center;
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
    }
    
    .tab-btn.active .tab-count {
        background: rgba(255, 255, 255, 0.25);
        color: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }
    
    .tab-btn:hover .tab-count {
        transform: scale(1.08);
    }
    
    /* ========== FILTERS CARD - TARJETA DE FILTROS GLASSMORPHISM ========== */
    .card.filters-card {
        background: var(--glass-white);
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
        border-radius: 16px;
        padding: 2rem 2.5rem;
        margin-bottom: 2.5rem;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--glass-border);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }
    
    .card.filters-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, var(--blue-primary) 0%, var(--blue-light) 50%, var(--blue-primary) 100%);
        opacity: 0;
        transition: var(--transition);
    }
    
    .card.filters-card:hover {
        box-shadow: var(--shadow-lg);
        background: var(--glass-white-hover);
    }
    
    .card.filters-card:hover::before {
        opacity: 0.7;
    }
    
    .filters-header {
        margin-bottom: 1.8rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid var(--blue-10);
    }
    
    .filters-title {
        display: flex;
        align-items: center;
        gap: 1.2rem;
    }
    
    .filters-title i {
        font-size: 2rem;
        color: var(--blue-primary);
        background: var(--blue-10);
        padding: 0.8rem;
        border-radius: 10px;
        transition: var(--transition);
    }
    
    .card.filters-card:hover .filters-title i {
        background: var(--blue-20);
        transform: rotate(90deg) scale(1.05);
    }
    
    .filters-title h3 {
        font-size: 1.8rem;
        color: var(--text-dark);
        font-weight: 600;
        margin: 0;
    }
    
    .filters-form {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .search-bar {
        width: 100%;
    }
    
    .search-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        background: white;
        border: 1px solid var(--blue-20);
        border-radius: 12px;
        transition: var(--transition);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }
    
    .search-input-wrapper:hover {
        border-color: var(--blue-30);
        box-shadow: var(--shadow-md);
    }
    
    .search-input-wrapper:focus-within {
        border-color: var(--blue-primary);
        box-shadow: 0 0 0 4px var(--blue-10);
        background: var(--glass-white-hover);
    }
    
    .search-icon {
        position: absolute;
        left: 1.5rem;
        font-size: 1.6rem;
        color: var(--blue-primary);
        opacity: 0.6;
        transition: var(--transition);
        pointer-events: none;
        z-index: 1;
    }
    
    .search-input-wrapper:focus-within .search-icon {
        opacity: 1;
        transform: scale(1.1);
    }
    
    .search-input {
        flex: 1;
        padding: 1.3rem 1.5rem 1.3rem 4.5rem;
        border: none;
        font-size: 1.4rem;
        color: var(--text-dark);
        background: transparent;
        outline: none;
        font-weight: 500;
    }
    
    .search-input::placeholder {
        color: var(--text-light);
        font-weight: 400;
    }
    
    .search-clear {
        width: 40px;
        height: 40px;
        margin-right: 0.5rem;
        background: var(--blue-10);
        border: none;
        border-radius: 10px;
        color: var(--blue-primary);
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        opacity: 0.7;
    }
    
    .search-clear:hover {
        background: var(--blue-20);
        opacity: 1;
        transform: scale(1.05);
    }
    
    .search-clear:active {
        transform: scale(0.95);
    }
    
    /* ========== RESULTS SUMMARY - RESUMEN DE RESULTADOS MEJORADO ========== */
    .results-summary {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2.5rem;
        padding: 1.5rem 2rem;
        background: var(--glass-white);
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
        border-radius: 14px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--glass-border);
        transition: var(--transition);
        flex-wrap: wrap;
        gap: 1.5rem;
    }
    
    .results-summary:hover {
        box-shadow: var(--shadow-md);
        background: var(--glass-white-hover);
        transform: translateY(-1px);
    }
    
    .results-info {
        display: flex;
        align-items: center;
        gap: 1rem;
        font-size: 1.4rem;
        color: var(--text-dark);
        font-weight: 600;
    }
    
    .results-info i {
        font-size: 1.8rem;
        color: var(--blue-primary);
        background: var(--blue-10);
        padding: 0.7rem;
        border-radius: 10px;
        transition: var(--transition);
    }
    
    .results-summary:hover .results-info i {
        background: var(--blue-20);
        transform: rotate(5deg) scale(1.05);
    }
    
    .results-info p {
        margin: 0;
        color: var(--text-dark);
    }
    
    .quick-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    
    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: auto ;
        gap: 0.8rem;
        padding: 1rem 1.8rem;
        background: white;
        border: 1px solid var(--blue-20);
        border-radius: 11px;
        font-size: 1.4rem;
        font-weight: 600;
        color: var(--blue-primary);
        cursor: pointer;
        transition: var(--transition);
        box-shadow: var(--shadow-sm);
        text-decoration: none;
    }
    
    .btn-action:hover {
        background: var(--blue-10);
        border-color: var(--blue-primary);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        color: var(--blue-dark);
    }
    
    .btn-action:active {
        transform: translateY(0);
    }
    
    .btn-action i {
        font-size: 1.5rem;
        transition: var(--transition);
    }
    
    .btn-refresh:hover i {
        transform: rotate(180deg);
    }
    
    .btn-action span {
        font-weight: 600;
    }
    
    /* ========== ORDERS TABLE CONTAINER - CONTENEDOR DE ÓRDENES ========== */
    .orders-table-container {
        background: var(--glass-white);
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
        border-radius: 16px;
        padding: 2rem;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--glass-border);
        transition: var(--transition);
        margin-bottom: 3rem;
        position: relative;
        overflow: hidden;
    }
    
    .orders-table-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, var(--blue-primary) 0%, var(--blue-light) 50%, var(--blue-primary) 100%);
        opacity: 0.7;
    }
    
    .orders-table-container:hover {
        box-shadow: var(--shadow-xl);
        background: var(--glass-white-hover);
    }
    
    .orders-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
        gap: 2rem;
        margin-top: 1rem;
    }
    
    /* ========== ORDER CARD - TARJETAS DE ÓRDENES GLASSMORPHISM ========== */
    .order-card {
        background: var(--glass-white);
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
        border-radius: 16px;
        box-shadow: var(--shadow-md);
        transition: var(--transition);
        overflow: hidden;
        border: 1px solid var(--glass-border);
        position: relative;
    }
    
    .order-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, var(--blue-primary) 0%, var(--blue-light) 100%);
        transform: scaleY(0);
        transition: var(--transition);
        z-index: 10;
    }
    
    .order-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: var(--blue-05);
        opacity: 0;
        transition: var(--transition);
        pointer-events: none;
    }
    
    .order-card:hover {
        box-shadow: var(--shadow-xl);
        transform: translateY(-6px) scale(1.01);
        background: var(--glass-white-hover);
    }
    
    .order-card:hover::before {
        transform: scaleY(1);
    }
    
    .order-card:hover::after {
        opacity: 1;
    }
    
    .order-card-header {
        padding: 1.8rem 2rem;
        background: var(--blue-primary);
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(74, 144, 226, 0.15);
    }
    
    .order-card-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
        transition: var(--transition);
    }
    
    .order-card:hover .order-card-header::before {
        transform: scale(1.5);
        opacity: 0.8;
    }
    
    .order-card-header > div {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
        position: relative;
        z-index: 1;
    }
    
    .order-card-header h3 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.6rem;
        letter-spacing: 0.3px;
    }
    
    .order-card-header h3 i {
        font-size: 1.3rem;
        opacity: 0.9;
        transition: var(--transition);
    }
    
    .order-card:hover .order-card-header h3 i {
        transform: scale(1.15) rotate(5deg);
    }
    
    .order-total {
        font-size: 1.8rem;
        font-weight: 800;
        position: relative;
        z-index: 1;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: var(--transition);
    }
    
    .order-card:hover .order-total {
        transform: scale(1.05);
    }
    
    .order-card-body {
        padding: 2rem;
        background: rgba(255, 255, 255, 0.6);
        position: relative;
        z-index: 1;
    }
    
    .order-info-row {
        display: flex;
        gap: 1.2rem;
        margin-bottom: 1.2rem;
        align-items: flex-start;
        font-size: 1.4rem;
        padding: 0.5rem 0;
        transition: var(--transition);
    }
    
    .order-info-row:hover {
        padding-left: 0.5rem;
        background: var(--blue-05);
        border-radius: 8px;
        margin-left: -0.5rem;
        margin-right: -0.5rem;
        padding-right: 0.5rem;
    }
    
    .order-info-row i {
        color: var(--blue-primary);
        width: 24px;
        min-width: 24px;
        text-align: center;
        margin-top: 3px;
        opacity: 0.8;
        font-size: 1.6rem;
        transition: var(--transition);
    }
    
    .order-info-row:hover i {
        opacity: 1;
        transform: scale(1.1);
    }
    
    .order-info-row span {
        flex: 1;
        line-height: 1.6;
    }
    
    .order-info-row strong {
        color: var(--text-dark);
        font-weight: 600;
    }
    
    .order-info-row em {
        color: var(--text-medium);
        font-style: italic;
    }
    
    .order-card-footer {
        padding: 1.5rem 2rem;
        background: var(--blue-05);
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        flex-wrap: wrap;
        border-top: 1px solid var(--blue-10);
        position: relative;
        z-index: 1;
    }
    
    .btn-sm {
        padding: 1rem 1.8rem;
        font-size: 1.35rem;
        border-radius: 11px;
        font-weight: 700;
        transition: var(--transition);
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.8rem;
        box-shadow: var(--shadow-sm);
        letter-spacing: 0.3px;
        text-transform: uppercase;
        font-size: 1.25rem;
    }
    
    .btn-sm i {
        font-size: 1.5rem;
        transition: var(--transition);
    }
    
    .btn-success.btn-sm {
        background: var(--blue-primary);
        color: white;
        border: 1px solid var(--blue-primary);
    }
    
    .btn-success.btn-sm:hover {
        background: var(--blue-dark);
        transform: translateY(-3px) scale(1.03);
        box-shadow: var(--shadow-lg);
    }
    
    .btn-success.btn-sm:hover i {
        transform: scale(1.15);
    }
    
    .btn-danger.btn-sm {
        background: var(--blue-20);
        color: var(--blue-dark);
        border: 1px solid var(--blue-30);
    }
    
    .btn-danger.btn-sm:hover {
        background: var(--blue-30);
        transform: translateY(-3px) scale(1.03);
        box-shadow: var(--shadow-md);
        color: var(--blue-dark);
    }
    
    .btn-primary.btn-sm {
        background: var(--blue-primary);
        color: white;
        border: 1px solid var(--blue-primary);
    }
    
    .btn-primary.btn-sm:hover {
        background: var(--blue-dark);
        transform: translateY(-3px) scale(1.03);
        box-shadow: var(--shadow-lg);
    }
    
    .btn-info.btn-sm {
        background: var(--blue-light);
        color: white;
        border: 1px solid var(--blue-light);
    }
    
    .btn-info.btn-sm:hover {
        background: var(--blue-primary);
        transform: translateY(-3px) scale(1.03);
        box-shadow: var(--shadow-lg);
    }
    
    .btn-sm:active {
        transform: translateY(-1px) scale(0.98);
    }
    
    /* ========== LOADING, ERROR Y EMPTY STATES - ESTADOS MEJORADOS ========== */
    .loading-row, .error-row, .empty-row {
        grid-column: 1 / -1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 6rem 3rem;
        text-align: center;
        background: var(--glass-white);
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
        border-radius: 16px;
        border: 1px solid var(--glass-border);
        box-shadow: var(--shadow-md);
        transition: var(--transition);
    }
    
    .loading-row:hover, .error-row:hover, .empty-row:hover {
        box-shadow: var(--shadow-lg);
        background: var(--glass-white-hover);
    }
    
    .loading-spinner, .error-icon, .empty-icon {
        width: 90px;
        height: 90px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 2.5rem;
        background: var(--blue-10);
        border-radius: 50%;
        position: relative;
        box-shadow: var(--shadow-md);
    }
    
    .loading-spinner::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: var(--blue-20);
        animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    @keyframes pulse-ring {
        0%, 100% {
            opacity: 1;
            transform: scale(1);
        }
        50% {
            opacity: 0;
            transform: scale(1.5);
        }
    }
    
    .loading-spinner i, .error-icon i, .empty-icon i {
        font-size: 4rem;
        color: var(--blue-primary);
        position: relative;
        z-index: 1;
    }
    
    .loading-spinner i {
        animation: spin-smooth 1.5s linear infinite;
    }
    
    @keyframes spin-smooth {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }
    
    .error-icon {
        background: rgba(220, 53, 69, 0.1);
    }
    
    .error-icon i {
        color: #dc3545;
    }
    
    .empty-icon i {
        opacity: 0.5;
    }
    
    .error-content h3, .empty-content h3 {
        color: var(--text-dark);
        margin-bottom: 1.2rem;
        font-size: 2rem;
        font-weight: 700;
    }
    
    .error-content p, .empty-content p {
        color: var(--text-medium);
        margin-bottom: 2.5rem;
        font-size: 1.5rem;
        line-height: 1.6;
        max-width: 500px;
    }
    
    /* ========== STATUS BADGES - BADGES DE ESTADO GLASSMORPHISM ========== */
    .status-badge {
        padding: 0.6rem 1.4rem;
        border-radius: 50px;
        font-size: 1.2rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        box-shadow: var(--shadow-sm);
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: var(--transition);
    }
    
    .status-badge i {
        font-size: 1.35rem;
        transition: var(--transition);
    }
    
    .order-card:hover .status-badge i {
        transform: scale(1.15) rotate(5deg);
    }
    
    .status-badge.shipped {
        background: rgba(74, 144, 226, 0.25);
        color: var(--blue-primary);
        border-color: rgba(74, 144, 226, 0.3);
    }
    
    .status-badge.driver_assigned {
        background: rgba(74, 144, 226, 0.15);
        color: var(--blue-dark);
        border-color: rgba(74, 144, 226, 0.2);
    }
    
    .status-badge.driver_accepted {
        background: rgba(74, 144, 226, 0.25);
        color: var(--blue-primary);
        border-color: rgba(74, 144, 226, 0.3);
    }
    
    .status-badge.in_transit {
        background: rgba(74, 144, 226, 0.35);
        color: white;
        border-color: rgba(74, 144, 226, 0.4);
        box-shadow: 0 4px 12px rgba(74, 144, 226, 0.2);
    }
    
    .status-badge.arrived {
        background: rgba(74, 144, 226, 0.6);
        color: white;
        border-color: rgba(74, 144, 226, 0.7);
        box-shadow: 0 4px 12px rgba(74, 144, 226, 0.25);
    }
    
    .status-badge.delivered {
        background: var(--blue-primary);
        color: white;
        border-color: var(--blue-primary);
        box-shadow: 0 4px 12px rgba(74, 144, 226, 0.3);
    }
    
    /* ========== MODALES GLASSMORPHISM - MODALES MEJORADOS ========== */
    .modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(26, 58, 82, 0.7);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .modal.show {
        display: flex;
    }
    
    .modal-content {
        background: var(--glass-white);
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
        border-radius: 20px;
        max-width: 540px;
        width: 92%;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        border: 1px solid var(--glass-border);
        animation: slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        overflow: hidden;
    }
    
    @keyframes slideUp {
        from {
            transform: translateY(80px) scale(0.9);
            opacity: 0;
        }
        to {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
    }
    
    .modal-header {
        padding: 2.5rem;
        border-bottom: 1px solid var(--blue-10);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, var(--blue-05) 0%, rgba(255, 255, 255, 0.5) 100%);
        position: relative;
        overflow: hidden;
    }
    
    .modal-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, var(--blue-10) 0%, transparent 70%);
        border-radius: 50%;
    }
    
    .modal-header h2 {
        margin: 0;
        font-size: 2rem;
        color: var(--blue-primary);
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 1.2rem;
        position: relative;
        z-index: 1;
    }
    
    .modal-header h2 i {
        font-size: 2.2rem;
        background: var(--blue-10);
        padding: 0.8rem;
        border-radius: 12px;
        transition: var(--transition);
    }
    
    .modal-close {
        background: white;
        border: 1px solid var(--blue-20);
        font-size: 2rem;
        cursor: pointer;
        color: var(--text-medium);
        width: 42px;
        height: 42px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
        box-shadow: var(--shadow-sm);
        position: relative;
        z-index: 1;
    }
    
    .modal-close:hover {
        background: var(--blue-10);
        color: var(--blue-primary);
        transform: rotate(90deg) scale(1.1);
        border-color: var(--blue-primary);
        box-shadow: var(--shadow-md);
    }
    
    .modal-body {
        padding: 2.5rem;
        background: rgba(255, 255, 255, 0.5);
    }
    
    .modal-body p {
        font-size: 1.5rem;
        color: var(--text-dark);
        margin-bottom: 1.8rem;
        line-height: 1.6;
        font-weight: 500;
    }
    
    .order-details {
        background: var(--blue-05);
        padding: 2rem;
        border-radius: 14px;
        margin-top: 1.8rem;
        border: 1px solid var(--blue-10);
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
    }
    
    .order-details:hover {
        background: var(--blue-10);
        box-shadow: var(--shadow-md);
    }
    
    .order-details p {
        margin-bottom: 1rem;
        font-size: 1.45rem;
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }
    
    .order-details p:last-child {
        margin-bottom: 0;
    }
    
    .modal-footer {
        padding: 2rem 2.5rem;
        border-top: 1px solid var(--blue-10);
        display: flex;
        gap: 1.2rem;
        justify-content: flex-end;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.5) 0%, var(--blue-05) 100%);
    }
    
    .modal-footer .btn {
        padding: 1.2rem 2.5rem;
        border-radius: 12px;
        font-size: 1.45rem;
        font-weight: 700;
        transition: var(--transition);
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.8rem;
        box-shadow: var(--shadow-sm);
        letter-spacing: 0.3px;
    }
    
    .modal-footer .btn-secondary {
        background: white;
        color: var(--text-dark);
        border: 1px solid var(--blue-20);
    }
    
    .modal-footer .btn-secondary:hover {
        background: var(--blue-05);
        border-color: var(--blue-30);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }
    
    .modal-footer .btn-success,
    .modal-footer .btn-danger {
        color: white;
        text-transform: uppercase;
        font-size: 1.3rem;
    }
    
    .modal-footer .btn-success {
        background: var(--blue-primary);
        border: 1px solid var(--blue-primary);
    }
    
    .modal-footer .btn-success:hover {
        background: var(--blue-dark);
        transform: translateY(-3px) scale(1.03);
        box-shadow: var(--shadow-lg);
    }
    
    .modal-footer .btn-danger {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border: 1px solid #dc3545;
    }
    
    .modal-footer .btn-danger:hover {
        background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
        transform: translateY(-3px) scale(1.03);
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
    }
    
    .form-control {
        width: 100%;
        padding: 1.2rem 1.5rem;
        border: 1px solid var(--blue-20);
        border-radius: 12px;
        font-size: 1.45rem;
        font-family: inherit;
        transition: var(--transition);
        background: white;
        color: var(--text-dark);
        box-shadow: var(--shadow-sm);
    }
    
    .form-control:hover {
        border-color: var(--blue-30);
    }
    
    .form-control:focus {
        outline: none;
        border-color: var(--blue-primary);
        box-shadow: 0 0 0 4px var(--blue-10);
        background: var(--glass-white-hover);
    }
    
    .text-muted {
        color: var(--text-light);
        font-size: 1.3rem;
        font-weight: 500;
    }
    
    /* ========== PAGINATION - PAGINACIÓN GLASSMORPHISM ========== */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 3rem;
        padding: 2rem;
    }
    
    .pagination-inner {
        display: flex;
        gap: 1rem;
        align-items: center;
        background: var(--glass-white);
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
        padding: 1rem 1.5rem;
        border-radius: 16px;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--glass-border);
    }
    
    .page-btn {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.45rem;
        font-weight: 700;
        color: var(--text-dark);
        background: white;
        border: 1px solid var(--blue-20);
        cursor: pointer;
        transition: var(--transition);
        box-shadow: var(--shadow-sm);
    }
    
    .page-btn:hover:not(.active) {
        background: var(--blue-10);
        border-color: var(--blue-primary);
        transform: translateY(-3px) scale(1.05);
        box-shadow: var(--shadow-md);
        color: var(--blue-primary);
    }
    
    .page-btn.active {
        background: var(--blue-primary);
        color: white;
        border-color: var(--blue-primary);
        box-shadow: 0 6px 16px rgba(74, 144, 226, 0.3);
        transform: translateY(-2px);
    }
    
    .page-btn i {
        font-size: 1.3rem;
    }
    
    /* ========== NOTIFICACIONES GLASSMORPHISM - NOTIFICACIONES MEJORADAS ========== */
    .notification {
        position: fixed;
        top: 90px;
        right: 25px;
        padding: 1.8rem 2.5rem;
        background: var(--glass-white);
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
        border-radius: 14px;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        border: 1px solid var(--glass-border);
        animation: slideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        max-width: 450px;
        min-width: 300px;
    }
    
    .notification::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        width: 5px;
        height: 100%;
        border-radius: 14px 0 0 14px;
    }
    
    .notification i {
        font-size: 2.4rem;
        min-width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
    }
    
    .notification span {
        flex: 1;
        font-size: 1.45rem;
        font-weight: 500;
        color: var(--text-dark);
        line-height: 1.5;
    }
    
    .notification-success::before {
        background: var(--blue-primary);
    }
    
    .notification-success i {
        color: var(--blue-primary);
        background: var(--blue-10);
    }
    
    .notification-error::before {
        background: #dc3545;
    }
    
    .notification-error i {
        color: #dc3545;
        background: rgba(220, 53, 69, 0.1);
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(120%) translateY(-10px);
            opacity: 0;
        }
        to {
            transform: translateX(0) translateY(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0) translateY(0);
            opacity: 1;
        }
        to {
            transform: translateX(120%) translateY(-10px);
            opacity: 0;
        }
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
    
    /* ========== RESPONSIVE - DISEÑO RESPONSIVO MEJORADO ========== */
    @media (max-width: 1200px) {
        .orders-grid {
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        }
    }
    
    @media (max-width: 992px) {
        .page-header h1 {
            font-size: 2.4rem;
        }
        
        .orders-grid {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
    }
    
    @media (max-width: 768px) {
        .page-header {
            padding: 2rem 1.5rem;
        }
        
        .page-header h1 {
            font-size: 2.2rem;
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .breadcrumb {
            width: 100%;
        }
        
        .order-tabs {
            flex-direction: column;
            gap: 1rem;
        }
        
        .tab-btn {
            width: 100%;
            min-width: auto;
            justify-content: flex-start;
            padding: 1.2rem 1.5rem;
        }
        
        .card.filters-card {
            padding: 1.5rem;
        }
        
        .filters-header {
            margin-bottom: 1.5rem;
        }
        
        .results-summary {
            flex-direction: column;
            align-items: flex-start;
            padding: 1.5rem;
        }
        
        .quick-actions {
            width: 100%;
            justify-content: flex-start;
        }
        
        .orders-table-container {
            padding: 1.5rem;
        }
        
        .orders-grid {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        
        .order-card-footer {
            flex-direction: column;
        }
        
        .btn-sm {
            width: 100%;
            justify-content: center;
        }
        
        .modal-content {
            width: 95%;
        }
        
        .modal-header,
        .modal-body,
        .modal-footer {
            padding: 1.8rem;
        }
        
        .modal-footer {
            flex-direction: column;
        }
        
        .modal-footer .btn {
            width: 100%;
            justify-content: center;
        }
        
        .notification {
            right: 15px;
            left: 15px;
            max-width: calc(100% - 30px);
        }
        
        .pagination-inner {
            padding: 0.8rem 1rem;
        }
        
        .page-btn {
            width: 40px;
            height: 40px;
            font-size: 1.35rem;
        }
    }
    
    @media (max-width: 576px) {
        .page-header h1 {
            font-size: 2rem;
        }
        
        .page-header h1 i {
            font-size: 2.2rem;
        }
        
        .breadcrumb {
            font-size: 1.3rem;
        }
        
        .filters-title h3 {
            font-size: 1.6rem;
        }
        
        .search-input {
            font-size: 1.35rem;
            padding-left: 4rem;
        }
        
        .search-icon {
            font-size: 1.5rem;
            left: 1.2rem;
        }
        
        .results-info {
            font-size: 1.35rem;
        }
        
        .btn-action {
            padding: 0.9rem 1.5rem;
            font-size: 1.35rem;
        }
        
        .order-card-header h3 {
            font-size: 1.4rem;
        }
        
        .order-total {
            font-size: 1.6rem;
        }
        
        .order-info-row {
            font-size: 1.35rem;
        }
        
        .status-badge {
            padding: 0.5rem 1.2rem;
            font-size: 1.1rem;
        }
        
        .modal-header h2 {
            font-size: 1.8rem;
        }
        
        .modal-body p {
            font-size: 1.4rem;
        }
        
        .notification {
            padding: 1.5rem 1.8rem;
        }
        
        .notification i {
            font-size: 2rem;
            min-width: 35px;
            height: 35px;
        }
        
        .notification span {
            font-size: 1.35rem;
        }
    }
    
    /* ========== ANIMACIONES ADICIONALES ========== */
    @keyframes float {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-5px);
        }
    }
    
    .order-card-header::before {
        animation: float 6s ease-in-out infinite;
    }
    
    /* Efecto hover suave en todos los elementos interactivos */
    .page-header,
    .card.filters-card,
    .results-summary,
    .orders-table-container,
    .order-card,
    .tab-btn,
    .btn-action,
    .page-btn,
    .modal-content {
        will-change: transform, box-shadow;
    }
    
    /* Optimización de rendimiento para animaciones */
    @media (prefers-reduced-motion: reduce) {
        *,
        *::before,
        *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
    </style>
</body>
</html>
