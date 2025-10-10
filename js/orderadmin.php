<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let currentPage = 1;
    const perPage = 15;
    window.selectedOrders = [];
    let currentFilters = {};

    // Elementos del DOM
    const ordersContainer = document.getElementById('orders-container');
    const paginationContainer = document.getElementById('pagination-container');
    const resultsCount = document.getElementById('results-count');
    const searchForm = document.getElementById('search-orders-form');
    const selectAllCheckbox = document.getElementById('select-all');
    const exportBtn = document.getElementById('export-orders');
    const bulkActionsBtn = document.getElementById('bulk-actions');

    // Verificar que los elementos existan antes de agregar event listeners
    if (!ordersContainer || !paginationContainer || !resultsCount || !searchForm || !selectAllCheckbox || !exportBtn || !bulkActionsBtn) {
        console.error('Error: No se encontraron todos los elementos necesarios en el DOM');
        return;
    }

    // Cargar órdenes al iniciar
    loadOrders();

    // Manejar el envío del formulario de búsqueda
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        currentPage = 1;

        // Obtener filtros del formulario
        currentFilters = {
            search: document.getElementById('search-input').value,
            status: document.getElementById('status-filter').value,
            payment_status: document.getElementById('payment-status-filter').value,
            payment_method: document.getElementById('payment-method-filter').value,
            from_date: document.getElementById('from-date').value,
            to_date: document.getElementById('to-date').value
        };

        loadOrders();
    });

    // Manejar clic en "Limpiar"
    const clearBtn = searchForm.querySelector('a[href$="orders.php"]');
    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            searchForm.reset();
            currentFilters = {};
            currentPage = 1;
            loadOrders();
        });
    }

    // Manejar selección de todas las órdenes
    selectAllCheckbox.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
            updateSelectedOrders(checkbox);
        });
    });

    // Manejar exportación de órdenes
exportBtn.addEventListener('click', function() {
    if (window.selectedOrders.length === 0) {
        showAlert('Selecciona al menos una orden para exportar', 'warning');
        return;
    }

    // Mostrar mensaje de procesamiento
    showAlert(`Preparando exportación de ${window.selectedOrders.length} órdenes...`, 'info');
    
    // Crear elemento de carga
    const loadingElement = document.createElement('div');
    loadingElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando PDF...';
    loadingElement.className = 'loading-message';
    
    // Mostrar alerta de carga
    showAlert('Generando PDF...', 'info', 0); // 0 = sin tiempo de auto-cierre

    // Llamar a la API de exportación
    fetch('<?= BASE_URL ?>/admin/api/export_orders_pdf.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_ids: window.selectedOrders
        })
    })
    .then(response => {
        // Verificar si la respuesta es exitosa
        if (!response.ok) {
            // Si es un error 500, intentar obtener el mensaje JSON
            if (response.status === 500) {
                return response.json().then(err => {
                    throw new Error(err.error || 'Error interno del servidor');
                }).catch(() => {
                    throw new Error('Error interno del servidor (500)');
                });
            } else {
                throw new Error(`Error del servidor: ${response.status}`);
            }
        }
        
        // Verificar si la respuesta es un PDF
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/pdf')) {
            // Es un PDF, procesarlo como descarga
            return response.blob().then(blob => {
                // Crear URL para el blob
                const url = window.URL.createObjectURL(blob);
                
                // Crear elemento de descarga
                const a = document.createElement('a');
                a.href = url;
                a.download = `reporte_ordenes_${new Date().toISOString().slice(0, 10)}.pdf`;
                document.body.appendChild(a);
                a.click();
                
                // Limpiar
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                // Cerrar alerta de carga
                const alertBox = document.querySelector('.alert-box');
                if (alertBox) alertBox.remove();
                
                // Mostrar mensaje de éxito
                showAlert('PDF generado y descargado exitosamente', 'success');
                
                // Limpiar selección
                window.selectedOrders = [];
                const selectAllCheckbox = document.getElementById('select-all');
                if (selectAllCheckbox) selectAllCheckbox.checked = false;
                
                // Desmarcar todos los checkboxes
                const checkboxes = document.querySelectorAll('.order-checkbox');
                checkboxes.forEach(cb => cb.checked = false);
                
                return;
            });
        } else {
            // Podría ser JSON con error
            return response.json().then(data => {
                if (data.success === false) {
                    throw new Error(data.error || 'Error desconocido');
                }
                throw new Error('Respuesta inesperada del servidor');
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Cerrar alerta de carga
        const alertBox = document.querySelector('.alert-box');
        if (alertBox) alertBox.remove();
        
        // Mostrar error
        showAlert(`Error al generar el PDF: ${error.message}`, 'error');
    });
});

    // Manejar acciones masivas
    bulkActionsBtn.addEventListener('click', function() {
        openBulkActionsModal();
    });

    // Función para cargar órdenes
    function loadOrders() {
        // Mostrar estado de carga
        ordersContainer.innerHTML = `
            <tr>
                <td colspan="8" class="loading-row">
                    <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Cargando órdenes...</div>
                </td>
            </tr>
        `;

        // Construir URL con parámetros
        const params = new URLSearchParams();
        params.append('page', currentPage);
        params.append('per_page', perPage);

        // Agregar filtros
        for (const [key, value] of Object.entries(currentFilters)) {
            if (value) params.append(key, value);
        }

        // Realizar petición AJAX
        fetch(`<?= BASE_URL ?>/admin/order/search.php?${params.toString()}`)
            .then(response => {
                // Verificar si la respuesta es JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new Error('La respuesta no es JSON válido: ' + text.substring(0, 100));
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    renderOrders(data.orders);
                    renderPagination(data.meta);
                    updateResultsCount(data.meta.total);
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                ordersContainer.innerHTML = `
                    <tr>
                        <td colspan="8" class="error-row">
                            <i class="fas fa-exclamation-circle"></i> Error al cargar órdenes. Por favor recarga la página.
                        </td>
                    </tr>
                `;
                showAlert('Error al cargar órdenes. Por favor intenta nuevamente.', 'error');
            });
    }

    // Función para renderizar órdenes
    function renderOrders(orders) {
        if (orders.length === 0) {
            ordersContainer.innerHTML = `
                <tr>
                    <td colspan="8" class="empty-row">
                        <i class="fas fa-info-circle"></i> No se encontraron órdenes
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';

        orders.forEach(order => {
            const orderDate = new Date(order.created_at);
            const formattedDate = orderDate.toLocaleDateString('es-CO', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });

            const formattedTotal = new Intl.NumberFormat('es-CO', {
                style: 'currency',
                currency: 'COP',
                minimumFractionDigits: 0
            }).format(order.total);

            html += `
                <tr data-order-id="${order.id}">
                    <td><input type="checkbox" class="order-checkbox" value="${order.id}" onchange="updateSelectedOrders(this)"></td>
                    <td>${order.order_number}</td>
                    <td>${order.user_name || 'Cliente no registrado'}</td>
                    <td>${formattedDate}</td>
                    <td>${formattedTotal}</td>
                    <td><span class="status-badge ${order.status}">${getStatusLabel(order.status)}</span></td>
                    <td>
                        <span class="payment-status ${order.payment_status}">
                            ${getPaymentStatusLabel(order.payment_status)}
                            ${order.payment_method ? `(${getPaymentMethodLabel(order.payment_method)})` : ''}
                        </span>
                    </td>
                    <td>
                        <div class="actions-cell">
                            <a href="<?= BASE_URL ?>/admin/order/detail.php?id=${order.id}" class="action-btn view" title="Ver detalle">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="action-btn edit" title="Editar" onclick="editOrder(${order.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn status" title="Cambiar estado" onclick="openStatusChangeModal(${order.id})">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                            <button class="action-btn delete" title="Eliminar" onclick="openDeleteOrderModal(${order.id})">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        ordersContainer.innerHTML = html;
    }

    // Función para renderizar paginación
    function renderPagination(meta) {
        if (meta.total_pages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        let html = '<div class="pagination-inner">';

        // Botón anterior
        if (currentPage > 1) {
            html += `<button class="page-btn" onclick="goToPage(${currentPage - 1})">
                <i class="fas fa-chevron-left"></i>
            </button>`;
        }

        // Páginas
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(meta.total_pages, currentPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">
                ${i}
            </button>`;
        }

        // Botón siguiente
        if (currentPage < meta.total_pages) {
            html += `<button class="page-btn" onclick="goToPage(${currentPage + 1})">
                <i class="fas fa-chevron-right"></i>
            </button>`;
        }

        html += '</div>';
        paginationContainer.innerHTML = html;
    }

    // Función para actualizar el contador de resultados
    function updateResultsCount(total) {
        resultsCount.textContent = `${total} ${total === 1 ? 'orden encontrada' : 'órdenes encontradas'}`;
    }

    // Funciones globales accesibles desde HTML
    window.goToPage = function(page) {
        currentPage = page;
        loadOrders();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    };

    window.updateSelectedOrders = function(checkbox) {
        const orderId = checkbox.value;

        if (checkbox.checked) {
            if (!selectedOrders.includes(orderId)) {
                selectedOrders.push(orderId);
            }
        } else {
            selectedOrders = selectedOrders.filter(id => id !== orderId);
            selectAllCheckbox.checked = false;
        }
    };

    window.editOrder = function(orderId) {
        window.location.href = `<?= BASE_URL ?>/admin/order/edit.php?id=${orderId}`;
    };

    // Funciones de ayuda
    function getStatusLabel(status) {
        return {
            'pending': 'Pendiente',
            'processing': 'En proceso',
            'shipped': 'Enviado',
            'delivered': 'Entregado',
            'cancelled': 'Cancelado',
            'refunded': 'Reembolsado'
        } [status] || status;
    }

    function getPaymentStatusLabel(status) {
        return {
            'pending': 'Pendiente',
            'paid': 'Pagado',
            'failed': 'Fallido',
            'refunded': 'Reembolsado'
        } [status] || status;
    }

    function getPaymentMethodLabel(method) {
        return {
            'transferencia': 'Transferencia',
            'contra_entrega': 'Contra entrega',
            'pse': 'PSE',
            'efectivo': 'Efectivo'
        } [method] || method;
    }

    // Función para actualizar estado de órdenes
    function updateOrdersStatus(orderIds, newStatus) {
        fetch(`<?= BASE_URL ?>/admin/order/update_status.php`, {
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
                    showAlert(`Estado de ${orderIds.length} órdenes actualizado correctamente`, 'success');
                    loadOrders();
                    selectedOrders = [];
                    selectAllCheckbox.checked = false;
                } else {
                    throw new Error(data.message || 'Error al actualizar estado');
                }
            })
            .catch(error => {
                showAlert(error.message, 'error');
            });
    }

    // Función para eliminar órdenes
    function deleteOrders(orderIds) {
        fetch(`<?= BASE_URL ?>/admin/order/delete.php`, {
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
                    showAlert(`${orderIds.length} órdenes eliminadas correctamente`, 'success');
                    loadOrders();
                    selectedOrders = [];
                    selectAllCheckbox.checked = false;
                } else {
                    throw new Error(data.message || 'Error al eliminar órdenes');
                }
            })
            .catch(error => {
                showAlert(error.message, 'error');
            });
    }

    // ===== INYECTAR ESTILOS CSS PARA BOTONES DE ACCIÓN =====
    const actionButtonsStyles = document.createElement('style');
    actionButtonsStyles.textContent = `
        /* Estilos para botones de acción en tabla de órdenes */
        .orders-table .actions-cell {
            display: flex !important;
            gap: 0.8rem !important;
            justify-content: center !important;
            align-items: center !important;
        }

        .orders-table .action-btn {
            width: 3.4rem !important;
            height: 3.4rem !important;
            border-radius: 0.8rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
            font-size: 1.4rem !important;
            position: relative !important;
            overflow: hidden !important;
            border: none !important;
            cursor: pointer !important;
            text-decoration: none !important;
        }

        .orders-table .action-btn::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: rgba(255, 255, 255, 0.1) !important;
            z-index: 0 !important;
            transform: scaleX(0) !important;
            transform-origin: right !important;
            transition: transform 0.3s cubic-bezier(0.5, 1.6, 0.4, 0.7) !important;
        }

        .orders-table .action-btn:hover::before {
            transform: scaleX(1) !important;
            transform-origin: left !important;
        }

        .orders-table .action-btn i {
            position: relative !important;
            z-index: 1 !important;
        }

        /* Botón Ver/Ver detalle */
        .orders-table .action-btn.view {
            background-color: rgba(2, 132, 199, 0.1) !important;
            color: #0284C7 !important;
            border: 1px solid rgba(2, 132, 199, 0.2) !important;
        }

        .orders-table .action-btn.view:hover {
            background-color: rgba(2, 132, 199, 0.2) !important;
            color: #0369A1 !important;
            transform: translateY(-2px) scale(1.1) !important;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25) !important;
        }

        /* Botón Editar */
        .orders-table .action-btn.edit {
            background-color: rgba(37, 99, 235, 0.1) !important;
            color: #2563EB !important;
            border: 1px solid rgba(37, 99, 235, 0.2) !important;
        }

        .orders-table .action-btn.edit:hover {
            background-color: rgba(37, 99, 235, 0.2) !important;
            color: #1D4ED8 !important;
            transform: translateY(-2px) scale(1.1) !important;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25) !important;
        }

        /* Botón Estado/Cambiar estado */
        .orders-table .action-btn.status {
            background-color: rgba(255, 193, 7, 0.1) !important;
            color: #FFC107 !important;
            border: 1px solid rgba(255, 193, 7, 0.2) !important;
        }

        .orders-table .action-btn.status:hover {
            background-color: rgba(255, 193, 7, 0.2) !important;
            color: #FFB300 !important;
            transform: translateY(-2px) scale(1.1) !important;
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.25) !important;
        }

        /* Botón Eliminar */
        .orders-table .action-btn.delete {
            background-color: rgba(220, 38, 38, 0.1) !important;
            color: #DC2626 !important;
            border: 1px solid rgba(220, 38, 38, 0.2) !important;
        }

        .orders-table .action-btn.delete:hover {
            background-color: rgba(220, 38, 38, 0.2) !important;
            color: #B91C1C !important;
            transform: translateY(-2px) scale(1.1) !important;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25) !important;
        }
    `;
    document.head.appendChild(actionButtonsStyles);
    console.log('✓ Estilos de botones de acción inyectados correctamente');
});
</script>