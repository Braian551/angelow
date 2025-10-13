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
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/delivery/orders.css">
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
        let currentOrdersHash = null;
        let currentOrdersData = null;
        let isLoading = false;
        let pollingInterval = null;
        
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
            currentOrdersHash = null; // Reset hash al cambiar de pestaña
            
            // Actualizar UI de pestañas
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
            
            // Cargar órdenes
            loadOrders(true);
        };
        
        // ====================================
        // VERIFICAR SI HAY ACTUALIZACIONES
        // ====================================
        function checkForUpdates() {
            if (isLoading || document.querySelector('.modal.show')) {
                return; // No verificar si estamos cargando o hay un modal abierto
            }
            
            const params = new URLSearchParams();
            params.append('tab', currentTab);
            
            fetch(`${BASE_URL}/delivery/api/check_orders_update.php?${params.toString()}`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar contadores siempre
                    updateTabCounts(data.counts);
                    
                    // Verificar si hay cambios en las órdenes
                    if (currentOrdersHash === null || data.hash !== currentOrdersHash) {
                        console.log('📦 Cambios detectados, actualizando órdenes...');
                        loadOrders(false); // Cargar sin mostrar spinner
                    }
                }
            })
            .catch(error => {
                console.error('Error al verificar actualizaciones:', error);
            });
        }
        
        // ====================================
        // FUNCIÓN PARA CARGAR ÓRDENES
        // ====================================
        function loadOrders(showLoader = true) {
            if (isLoading) return;
            
            isLoading = true;
            
            if (showLoader) {
                ordersContainer.innerHTML = `
                    <div class="loading-row">
                        <div class="loading-spinner">
                            <i class="fas fa-spinner fa-spin"></i> Cargando órdenes...
                        </div>
                    </div>
                `;
            }
            
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
            
            // Realizar petición
            fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                }
            })
                .then(response => {
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
                    if (data.success) {
                        // Generar hash de las órdenes actuales
                        const newHash = JSON.stringify(data.orders.map(o => ({
                            id: o.id,
                            delivery_id: o.delivery_id,
                            delivery_status: o.delivery_status
                        })));
                        
                        // Solo actualizar si hay cambios o es la primera carga
                        if (!currentOrdersData || newHash !== currentOrdersHash || showLoader) {
                            currentOrdersHash = newHash;
                            currentOrdersData = data;
                            
                            renderOrders(data.orders);
                            renderPagination(data.meta);
                            updateResultsCount(data.meta);
                            updateTabCounts(data.counts);
                            
                            if (!showLoader) {
                                // Mostrar notificación sutil de actualización
                                showUpdateIndicator();
                            }
                        }
                    } else {
                        console.error('API Error:', data.error);
                        throw new Error(data.error || 'Error desconocido');
                    }
                })
                .catch(error => {
                    console.error('Error completo:', error);
                    
                    let errorMessage = error.message;
                    let actionButton = '';
                    
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
                            <button onclick="loadOrders(true)" class="btn btn-primary">
                                <i class="fas fa-redo"></i> Reintentar
                            </button>
                        `;
                    } else {
                        actionButton = `
                            <button onclick="loadOrders(true)" class="btn btn-primary">
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
                                <div style="margin-top: 1.5rem;">
                                    ${actionButton}
                                </div>
                            </div>
                        </div>
                    `;
                })
                .finally(() => {
                    isLoading = false;
                });
        }
        
        // ====================================
        // INDICADOR DE ACTUALIZACIÓN
        // ====================================
        function showUpdateIndicator() {
            const indicator = document.createElement('div');
            indicator.className = 'update-indicator';
            indicator.innerHTML = '<i class="fas fa-sync-alt"></i> Actualizado';
            indicator.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                background: #10b981;
                color: white;
                padding: 8px 16px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 500;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                z-index: 9999;
                animation: slideInRight 0.3s ease, fadeOut 0.3s ease 2s;
                display: flex;
                align-items: center;
                gap: 6px;
            `;
            
            document.body.appendChild(indicator);
            
            setTimeout(() => {
                indicator.style.opacity = '0';
                setTimeout(() => indicator.remove(), 300);
            }, 2000);
        }
        
        // ====================================
        // FUNCIÓN PARA RENDERIZAR ÓRDENES (SIN PARPADEO)
        // ====================================
        function renderOrders(orders) {
            const currentCards = ordersContainer.querySelectorAll('.order-card');
            const existingOrderIds = new Set(
                Array.from(currentCards).map(card => card.dataset.orderId)
            );
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
            
            // Crear un mapa de órdenes actuales para comparación
            const orderMap = new Map();
            orders.forEach(order => {
                const orderId = order.delivery_id || order.id;
                orderMap.set(String(orderId), order);
            });
            
            // Remover tarjetas que ya no existen
            currentCards.forEach(card => {
                const orderId = card.dataset.orderId;
                if (!orderMap.has(orderId)) {
                    card.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => card.remove(), 300);
                }
            });
            
            // Si no hay tarjetas actuales, renderizar todo de nuevo
            if (currentCards.length === 0) {
                let html = '';
                orders.forEach(order => {
                    html += renderOrderCard(order);
                });
                ordersContainer.innerHTML = html;
                return;
            }
            
            // Actualizar o agregar tarjetas
            const tempContainer = document.createElement('div');
            
            orders.forEach((order, index) => {
                const orderId = String(order.delivery_id || order.id);
                const existingCard = ordersContainer.querySelector(`[data-order-id="${orderId}"]`);
                
                if (existingCard) {
                    // Actualizar tarjeta existente solo si hay cambios
                    const newCardHTML = renderOrderCard(order);
                    tempContainer.innerHTML = newCardHTML;
                    const newCard = tempContainer.firstElementChild;
                    
                    // Comparar solo el contenido relevante (sin animaciones)
                    const existingContent = existingCard.querySelector('.order-card-body')?.innerHTML || '';
                    const newContent = newCard.querySelector('.order-card-body')?.innerHTML || '';
                    
                    if (existingContent !== newContent) {
                        existingCard.outerHTML = newCardHTML;
                    }
                } else {
                    // Agregar nueva tarjeta con animación
                    const newCardHTML = renderOrderCard(order);
                    tempContainer.innerHTML = newCardHTML;
                    const newCard = tempContainer.firstElementChild;
                    newCard.style.animation = 'fadeIn 0.5s ease';
                    ordersContainer.appendChild(newCard);
                }
            });
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
                <div class="order-card" data-order-id="${order.delivery_id || order.id}">
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
            // Redirigir a la página de navegación en tiempo real
            window.location.href = BASE_URL + '/delivery/navigation.php?delivery_id=' + deliveryId;
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
                    // Forzar recarga inmediata después de una acción
                    currentOrdersHash = null;
                    setTimeout(() => loadOrders(false), 500);
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
            currentOrdersHash = null; // Reset hash para forzar recarga
            loadOrders(true);
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
                currentOrdersHash = null; // Reset hash para forzar recarga
                loadOrders(true);
            }, 500);
        });
        
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            this.style.display = 'none';
            currentFilters.search = '';
            currentOrdersHash = null; // Reset hash
            loadOrders(true);
        });
        
        // ====================================
        // BOTÓN REFRESCAR
        // ====================================
        refreshBtn.addEventListener('click', function() {
            this.querySelector('i').classList.add('fa-spin');
            currentOrdersHash = null; // Forzar recarga completa
            loadOrders(true);
            setTimeout(() => {
                this.querySelector('i').classList.remove('fa-spin');
            }, 1000);
        });
        
        // ====================================
        // INICIAR SISTEMA DE POLLING
        // ====================================
        function startPolling() {
            // Detener polling anterior si existe
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
            
            // Verificar actualizaciones cada 5 segundos
            pollingInterval = setInterval(() => {
                checkForUpdates();
            }, 5000);
        }
        
        function stopPolling() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
        }
        
        // Pausar polling cuando la página no está visible
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                stopPolling();
                console.log('⏸️ Polling pausado (página oculta)');
            } else {
                startPolling();
                checkForUpdates(); // Verificar inmediatamente al volver
                console.log('▶️ Polling reanudado');
            }
        });
        
        // ====================================
        // CARGAR ÓRDENES AL INICIO E INICIAR POLLING
        // ====================================
        loadOrders(true);
        startPolling();
        
        // Limpiar al cerrar
        window.addEventListener('beforeunload', function() {
            stopPolling();
        });
    });
    </script>
</body>
</html>
