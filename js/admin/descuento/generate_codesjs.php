<script>
document.addEventListener('DOMContentLoaded', function() {
    // Declarar todas las variables al inicio para evitar errores de hoisting
    let selectedProducts = [];
    let allProducts = [];
    let filteredProducts = [];
    let selectedUsers = [];
    let allUsers = [];
    let filteredUsers = [];

    // Obtener elementos del DOM
    const selectedProductsInput = document.getElementById('selected-products');
    const selectedUsersInput = document.getElementById('selected_users');
    const productsModal = document.getElementById('products-modal');
    const openProductsModalBtn = document.getElementById('open-products-modal');
    const closeProductsModal = document.querySelector('#products-modal .close-modal');
    const productSearchInput = document.getElementById('modal-product-search');
    const productsList = document.getElementById('products-list');
    const applyProductsBtn = document.getElementById('apply-products-selection');
    const selectAllProductsCheckbox = document.getElementById('select-all-products-checkbox');
    const selectedProductsCount = document.getElementById('selected-products-count');
    const productsLoading = document.getElementById('products-loading');
    const noProductsFound = document.getElementById('no-products-found');

    // Inicializar arrays después de obtener los elementos del DOM
    if (selectedProductsInput) {
        selectedProducts = JSON.parse(selectedProductsInput.value || '[]');
    }
    if (selectedUsersInput) {
        selectedUsers = JSON.parse(selectedUsersInput.value || '[]');
    }

    // Cargar usuarios desde PHP
    if (typeof window.allUsersFromPHP !== 'undefined' && window.allUsersFromPHP) {
        allUsers = window.allUsersFromPHP;
        filteredUsers = allUsers;
        console.log('Usuarios cargados:', allUsers.length);
    } else {
        console.warn('No se pudieron cargar los usuarios desde PHP');
        // Cargar usuarios mediante AJAX como respaldo
        loadUsersFromServer();
    }

    // Inicializar sidebar (solución para el error setupSidebarCollapse)
    if (typeof setupSidebarCollapse === 'function') {
        setupSidebarCollapse();
    }

    // Función para cargar usuarios desde el servidor
    function loadUsersFromServer() {
        fetch('<?= BASE_URL ?>/admin/api/descuento/search_users.php')
            .then(response => response.json())
            .then(data => {
                if (Array.isArray(data)) {
                    allUsers = data;
                    filteredUsers = data;
                    console.log('Usuarios cargados vía AJAX:', allUsers.length);
                } else {
                    console.error('Error al cargar usuarios:', data);
                }
            })
            .catch(error => {
                console.error('Error en la carga de usuarios:', error);
            });
    }

    // Función debounce para búsquedas
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this,
                args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                func.apply(context, args);
            }, wait);
        };
    }

    // Función para escapar HTML
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return (text || '').replace(/[&<>"']/g, function(m) {
            return map[m];
        });
    }

    // Función para limpiar la selección de productos
    function clearProductsSelection(skipVisibilityUpdate = false) {
        selectedProducts = [];
        if (selectedProductsInput) {
            selectedProductsInput.value = '[]';
        }
        updateProductsSelectionSummary();
        
        // Solo actualizar visibilidad si no se está saltando (evita recursión)
        if (!skipVisibilityUpdate) {
            updateProductsSelectionVisibility();
        }

        // Actualizar checkboxes en el modal si está abierto
        if (productsModal && productsModal.style.display === 'block' && productsList) {
            const checkboxes = productsList.querySelectorAll('.product-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
                const row = checkbox.closest('tr');
                if (row) {
                    row.classList.remove('selected');
                }
            });
            updateSelectedCount();
            updateSelectAllProductsCheckbox();
        }
    }

    // Función para limpiar la selección de usuarios
    function clearUsersSelection() {
        selectedUsers = [];
        if (selectedUsersInput) {
            selectedUsersInput.value = '[]';
        }
        updateUsersSelectionSummary();

        // Actualizar checkboxes en el modal si está abierto
        if (userModal && userModal.style.display === 'block' && usersList) {
            const checkboxes = usersList.querySelectorAll('.user-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
                const row = checkbox.closest('tr');
                if (row) {
                    row.classList.remove('selected');
                }
            });
            updateSelectedUsersCount();
            updateSelectAllUsersCheckbox();
        }
    }

    // Función para actualizar la visibilidad del resumen de productos según "Aplicar a todos"
    function updateProductsSelectionVisibility() {
        const applyToAllCheckbox = document.getElementById('apply_to_all');
        const selectedProductsInfo = document.getElementById('selected-products-info');
        const openProductsModal = document.getElementById('open-products-modal');

        if (applyToAllCheckbox && selectedProductsInfo) {
            if (applyToAllCheckbox.checked) {
                // Si aplica a todos, ocultar el resumen y limpiar selección
                selectedProductsInfo.style.display = 'none';
                // Pasar true para evitar recursión infinita
                clearProductsSelection(true);
            } else {
                // Si no aplica a todos, mostrar el resumen si hay productos seleccionados
                if (selectedProducts.length > 0) {
                    selectedProductsInfo.style.display = 'block';
                } else {
                    selectedProductsInfo.style.display = 'none';
                }
            }
        }

        // También actualizar visibilidad del botón del modal
        if (openProductsModal) {
            openProductsModal.style.display = (applyToAllCheckbox && applyToAllCheckbox.checked) ? 'none' : 'block';
        }
    }

    // Función para actualizar el resumen de productos seleccionados
    function updateProductsSelectionSummary() {
        const selectedProductsInfo = document.getElementById('selected-products-info');
        const selectedProductsCountDisplay = document.getElementById('selected-products-count-display');
        const applyToAllCheckbox = document.getElementById('apply_to_all');

        if (selectedProductsInfo && selectedProductsCountDisplay) {
            // Solo mostrar si NO está marcado "Aplicar a todos" Y hay productos seleccionados
            if (!applyToAllCheckbox || (!applyToAllCheckbox.checked && selectedProducts.length > 0)) {
                selectedProductsInfo.style.display = 'block';
                selectedProductsCountDisplay.textContent = `${selectedProducts.length} producto(s) seleccionado(s)`;
            } else {
                selectedProductsInfo.style.display = 'none';
            }
        }
    }

    // Función para actualizar el resumen de usuarios seleccionados
    function updateUsersSelectionSummary() {
        const selectedUsersInfo = document.getElementById('selected-users-info');
        const selectedUsersCountDisplay = document.getElementById('selected-users-count-display');
        const sendNotificationCheckbox = document.getElementById('send_notification');

        if (selectedUsersInfo && selectedUsersCountDisplay) {
            // Solo mostrar si está marcado "Enviar notificación" Y hay usuarios seleccionados
            if (sendNotificationCheckbox && sendNotificationCheckbox.checked && selectedUsers.length > 0) {
                selectedUsersInfo.style.display = 'block';
                selectedUsersCountDisplay.textContent = `${selectedUsers.length} usuario(s) seleccionado(s)`;
            } else {
                selectedUsersInfo.style.display = 'none';
            }
        }
    }

    // Buscador de códigos
    const searchCodesInput = document.getElementById('search-codes');
    if (searchCodesInput) {
        searchCodesInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.data-table tbody tr');
            let hasResults = false;

            rows.forEach(row => {
                const code = row.getAttribute('data-searchable');
                if (code && code.includes(searchTerm)) {
                    row.style.display = '';
                    hasResults = true;
                } else {
                    row.style.display = 'none';
                }
            });

            const noResults = document.getElementById('no-results');
            if (noResults) {
                if (!hasResults && searchTerm.length > 0) {
                    noResults.style.display = 'flex';
                } else {
                    noResults.style.display = 'none';
                }
            }
        });
    }

    // Mostrar/ocultar campos según tipo de descuento
    const discountTypeSelect = document.getElementById('discount_type');
    const discountValueGroup = document.getElementById('discount-value-group');
    const fixedAmountGroup = document.getElementById('fixed-amount-group');
    const maxDiscountGroup = document.getElementById('max-discount-group');
    const minOrderGroup = document.getElementById('min-order-group');
    const shippingMethodGroup = document.getElementById('shipping-method-group');

    function updateDiscountFields() {
        if (!discountTypeSelect) return;
        
        const discountType = discountTypeSelect.value;

        // Ocultar todos los grupos primero
        if (discountValueGroup) discountValueGroup.style.display = 'none';
        if (fixedAmountGroup) fixedAmountGroup.style.display = 'none';
        if (maxDiscountGroup) maxDiscountGroup.style.display = 'none';
        if (minOrderGroup) minOrderGroup.style.display = 'none';
        if (shippingMethodGroup) shippingMethodGroup.style.display = 'none';

        // Limpiar valores de campos que se ocultan
        const discountValueInput = document.getElementById('discount_value');
        const fixedAmountInput = document.getElementById('fixed_amount');

        if (discountValueInput) discountValueInput.value = '';
        if (fixedAmountInput) fixedAmountInput.value = '';

        // Mostrar los campos relevantes según el tipo
        switch (discountType) {
            case '1': // Porcentaje
                if (discountValueGroup) discountValueGroup.style.display = 'block';
                if (maxDiscountGroup) maxDiscountGroup.style.display = 'block';
                break;
            case '2': // Monto fijo
                if (fixedAmountGroup) fixedAmountGroup.style.display = 'block';
                if (minOrderGroup) minOrderGroup.style.display = 'block';
                break;
            case '3': // Envío gratis
                if (shippingMethodGroup) shippingMethodGroup.style.display = 'block';
                break;
        }
    }

    if (discountTypeSelect) {
        discountTypeSelect.addEventListener('change', updateDiscountFields);
        // Ejecutar al cargar para establecer el estado inicial
        updateDiscountFields();
    }

    // Validación del formulario antes del envío
    const discountForm = document.getElementById('discount-form');
    if (discountForm) {
        discountForm.addEventListener('submit', function(e) {
            const discountType = discountTypeSelect ? discountTypeSelect.value : '';
            
            // Validar que se haya seleccionado un tipo
            if (!discountType) {
                e.preventDefault();
                if (typeof showAlert === 'function') {
                    showAlert('Por favor selecciona un tipo de descuento', 'error');
                } else {
                    alert('Por favor selecciona un tipo de descuento');
                }
                return false;
            }

            // Validar valores según el tipo
            if (discountType === '1') { // Porcentaje
                const discountValue = document.getElementById('discount_value');
                if (!discountValue || !discountValue.value || parseFloat(discountValue.value) <= 0 || parseFloat(discountValue.value) > 100) {
                    e.preventDefault();
                    if (typeof showAlert === 'function') {
                        showAlert('El porcentaje de descuento debe estar entre 1 y 100', 'error');
                    } else {
                        alert('El porcentaje de descuento debe estar entre 1 y 100');
                    }
                    return false;
                }
            } else if (discountType === '2') { // Monto fijo
                const fixedAmount = document.getElementById('fixed_amount');
                if (!fixedAmount || !fixedAmount.value || parseFloat(fixedAmount.value) <= 0) {
                    e.preventDefault();
                    if (typeof showAlert === 'function') {
                        showAlert('El monto fijo debe ser mayor a 0', 'error');
                    } else {
                        alert('El monto fijo debe ser mayor a 0');
                    }
                    return false;
                }
            }

            // Validar fechas
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            if (startDate && endDate && startDate.value && endDate.value) {
                if (new Date(startDate.value) > new Date(endDate.value)) {
                    e.preventDefault();
                    if (typeof showAlert === 'function') {
                        showAlert('La fecha de inicio no puede ser mayor a la fecha de fin', 'error');
                    } else {
                        alert('La fecha de inicio no puede ser mayor a la fecha de fin');
                    }
                    return false;
                }
            }

            return true;
        });
    }

    // Mostrar/ocultar selección de productos
    const applyToAllCheckbox = document.getElementById('apply_to_all');
    const openProductsModal = document.getElementById('open-products-modal');

    if (applyToAllCheckbox && openProductsModal) {
        applyToAllCheckbox.addEventListener('change', function() {
            openProductsModal.style.display = this.checked ? 'none' : 'block';
            updateProductsSelectionVisibility();
        });

        // Ejecutar al cargar la página
        openProductsModal.style.display = applyToAllCheckbox.checked ? 'none' : 'block';
        updateProductsSelectionVisibility();
    }

    // Mostrar/ocultar campo de email para notificación
    const sendNotificationCheckbox = document.getElementById('send_notification');
    if (sendNotificationCheckbox) {
        sendNotificationCheckbox.addEventListener('change', function() {
            const notificationGroup = document.getElementById('notification-email-group');
            if (notificationGroup) {
                notificationGroup.style.display = this.checked ? 'block' : 'none';
            }
            
            // Si se desmarca la notificación, limpiar la selección de usuarios
            if (!this.checked) {
                clearUsersSelection();
            } else {
                // Si se marca, actualizar el resumen
                updateUsersSelectionSummary();
            }
        });
    }

    // Copiar código al portapapeles
    document.querySelectorAll('.btn-copy').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const code = this.getAttribute('data-code');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code).then(() => {
                    if (typeof showAlert === 'function') {
                        showAlert('Código copiado al portapapeles', 'success');
                    }
                });
            } else {
                // Fallback para navegadores que no soportan clipboard API
                const textArea = document.createElement('textarea');
                textArea.value = code;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                if (typeof showAlert === 'function') {
                    showAlert('Código copiado al portapapeles', 'success');
                }
            }
        });
    });

    // Modal de productos
    if (openProductsModalBtn) {
        openProductsModalBtn.addEventListener('click', function() {
            if (productsModal) {
                productsModal.style.display = 'block';
                loadRealProducts(); // Cargar productos reales
            }
        });
    }

    if (closeProductsModal) {
        closeProductsModal.addEventListener('click', function() {
            if (productsModal) {
                productsModal.style.display = 'none';
            }
        });
    }

    // Función para cargar productos reales desde la base de datos
    function loadRealProducts(searchTerm = '') {
        if (productsLoading) productsLoading.style.display = 'block';
        if (productsList) productsList.innerHTML = '';
        if (noProductsFound) noProductsFound.style.display = 'none';

        // Hacer petición AJAX para obtener productos reales
        const formData = new FormData();
        formData.append('search', searchTerm);
        formData.append('action', 'get_products_for_discount');

        fetch('<?= BASE_URL ?>/admin/api/descuento/get_products.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    allProducts = data.products;
                    filteredProducts = allProducts;
                    displayProducts(filteredProducts);
                } else {
                    if (typeof showAlert === 'function') {
                        showAlert('Error al cargar productos: ' + data.message, 'error');
                    }
                    console.error('Error del servidor:', data.message);
                }
            })
            .catch(error => {
                console.error('Error en la petición:', error);
                if (typeof showAlert === 'function') {
                    showAlert('Error de conexión al cargar productos: ' + error.message, 'error');
                }
            })
            .finally(() => {
                if (productsLoading) productsLoading.style.display = 'none';
                if (filteredProducts.length === 0 && noProductsFound) {
                    noProductsFound.style.display = 'flex';
                }
            });
    }

    function displayProducts(products) {
        if (!productsList) return;
        
        productsList.innerHTML = '';

        if (products.length === 0) {
            if (noProductsFound) noProductsFound.style.display = 'flex';
            return;
        }

        products.forEach(product => {
            const isSelected = selectedProducts.includes(product.id.toString());
            const stockClass = getStockClass(product.stock);
            const statusClass = product.status === 'active' ? 'status-active' : 'status-inactive';
            const statusText = product.status === 'active' ? 'Activo' : 'Inactivo';
            const genderText = getGenderText(product.gender);

            const row = document.createElement('tr');
            row.className = isSelected ? 'selected' : '';
            row.innerHTML = `
            <td>
                <input type="checkbox" class="product-checkbox" value="${product.id}" 
                    ${isSelected ? 'checked' : ''}>
            </td>
            <td>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <img src="${product.image}" alt="${escapeHtml(product.name)}" 
                         class="product-image" onerror="this.src='<?= BASE_URL ?>/images/placeholder-product.jpg'">
                    <div>
                        <strong>${escapeHtml(product.name)}</strong>
                        <div style="font-size: 0.8em; color: #666;">
                            ${escapeHtml(product.category)} • ${genderText}
                        </div>
                        <div style="font-size: 0.7em; color: #888;">
                            ${product.color_variants} colores • ${product.size_variants} tallas
                        </div>
                    </div>
                </div>
            </td>
            <td>${escapeHtml(product.category)}</td>
            <td>$${product.price.toFixed(2)}</td>
            <td class="${stockClass}">${product.stock} unidades</td>
            <td class="${statusClass}">${statusText}</td>
        `;
            productsList.appendChild(row);

            // Manejar clic en la fila
            row.addEventListener('click', function(e) {
                if (e.target.tagName !== 'INPUT' && !e.target.closest('input')) {
                    const checkbox = row.querySelector('.product-checkbox');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        updateSelectedProducts(checkbox);
                    }
                }
            });

            // Manejar cambio en el checkbox
            const checkbox = row.querySelector('.product-checkbox');
            if (checkbox) {
                checkbox.addEventListener('change', function() {
                    updateSelectedProducts(this);
                });
            }
        });

        updateSelectedCount();
        updateSelectAllProductsCheckbox();
    }

    function getStockClass(stock) {
        if (stock === 0) return 'stock-low';
        if (stock <= 5) return 'stock-low';
        if (stock <= 15) return 'stock-medium';
        return 'stock-high';
    }

    function getGenderText(gender) {
        const genderMap = {
            'niño': 'Niño',
            'niña': 'Niña',
            'bebe': 'Bebé',
            'unisex': 'Unisex'
        };
        return genderMap[gender] || 'Unisex';
    }

    function updateSelectedProducts(checkbox) {
        if (!checkbox) return;
        
        const productId = checkbox.value;
        const row = checkbox.closest('tr');

        if (checkbox.checked) {
            if (!selectedProducts.includes(productId)) {
                selectedProducts.push(productId);
            }
            if (row) row.classList.add('selected');
        } else {
            selectedProducts = selectedProducts.filter(id => id !== productId);
            if (row) row.classList.remove('selected');
        }
        updateSelectedCount();
        updateSelectAllProductsCheckbox();
        updateProductsSelectionSummary(); // Actualizar resumen también aquí
    }

    function updateSelectAllProductsCheckbox() {
        if (!selectAllProductsCheckbox || !productsList) return;
        
        const visibleCheckboxes = productsList.querySelectorAll('.product-checkbox');
        const checkedCheckboxes = productsList.querySelectorAll('.product-checkbox:checked');

        if (visibleCheckboxes.length === 0) {
            selectAllProductsCheckbox.indeterminate = false;
            selectAllProductsCheckbox.checked = false;
        } else if (checkedCheckboxes.length === visibleCheckboxes.length) {
            selectAllProductsCheckbox.indeterminate = false;
            selectAllProductsCheckbox.checked = true;
        } else if (checkedCheckboxes.length > 0) {
            selectAllProductsCheckbox.indeterminate = true;
            selectAllProductsCheckbox.checked = false;
        } else {
            selectAllProductsCheckbox.indeterminate = false;
            selectAllProductsCheckbox.checked = false;
        }
    }

    if (selectAllProductsCheckbox) {
        selectAllProductsCheckbox.addEventListener('change', function() {
            if (!productsList) return;
            
            const visibleCheckboxes = productsList.querySelectorAll('.product-checkbox');

            visibleCheckboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');

                if (this.checked) {
                    checkbox.checked = true;
                    if (!selectedProducts.includes(checkbox.value)) {
                        selectedProducts.push(checkbox.value);
                    }
                    if (row) row.classList.add('selected');
                } else {
                    checkbox.checked = false;
                    selectedProducts = selectedProducts.filter(id => id !== checkbox.value);
                    if (row) row.classList.remove('selected');
                }
            });
            updateSelectedCount();
        });
    }

    function updateSelectedCount() {
        if (selectedProductsCount) {
            selectedProductsCount.textContent = `${selectedProducts.length} seleccionados`;
        }
    }

    if (applyProductsBtn) {
        applyProductsBtn.addEventListener('click', function() {
            if (selectedProductsInput) {
                selectedProductsInput.value = JSON.stringify(selectedProducts);
            }
            if (productsModal) {
                productsModal.style.display = 'none';
            }
            updateProductsSelectionSummary(); // Llamar a la función de resumen
        });
    }

    const clearSelectedProducts = document.getElementById('clear-selected-products');
    if (clearSelectedProducts) {
        clearSelectedProducts.addEventListener('click', function() {
            clearProductsSelection();
        });
    }

    if (productSearchInput) {
        productSearchInput.addEventListener('input', debounce(function() {
            loadRealProducts(this.value.trim());
        }, 300));
    }

    // Modal de usuarios
    const userModal = document.getElementById('user-modal');
    const openUserModal = document.getElementById('open-user-modal');
    const closeUserModal = document.querySelectorAll('#user-modal .close-modal');
    const userSearchInput = document.getElementById('modal-user-search');
    const usersList = document.getElementById('users-list');
    const applyUsersBtn = document.getElementById('apply-users-selection');
    const selectAllUsersCheckbox = document.getElementById('select-all-users-checkbox');
    const selectedUsersCount = document.getElementById('selected-users-count');

    if (openUserModal) {
        openUserModal.addEventListener('click', function() {
            if (userModal) {
                userModal.style.display = 'block';
                loadUsers();
            }
        });
    }

    if (closeUserModal) {
        closeUserModal.forEach(btn => {
            btn.addEventListener('click', function() {
                if (userModal) {
                    userModal.style.display = 'none';
                }
            });
        });
    }

    function loadUsers(searchTerm = '') {
        console.log('Cargando usuarios con término:', searchTerm);
        console.log('Total usuarios disponibles:', allUsers.length);

        if (searchTerm) {
            filteredUsers = allUsers.filter(user =>
                (user.name && user.name.toLowerCase().includes(searchTerm.toLowerCase())) ||
                (user.email && user.email.toLowerCase().includes(searchTerm.toLowerCase()))
            );
        } else {
            filteredUsers = allUsers;
        }

        console.log('Usuarios filtrados:', filteredUsers.length);

        if (!usersList) return;
        
        usersList.innerHTML = '';
        
        if (filteredUsers.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="3" style="text-align: center; color: #666;">No se encontraron usuarios</td>';
            usersList.appendChild(row);
            updateSelectedUsersCount();
            updateSelectAllUsersCheckbox();
            return;
        }

        filteredUsers.forEach(user => {
            const isSelected = selectedUsers.includes(user.id.toString());
            const row = document.createElement('tr');
            row.className = isSelected ? 'selected' : '';
            row.innerHTML = `
                <td>
                    <input type="checkbox" class="user-checkbox" value="${user.id}" 
                        ${isSelected ? 'checked' : ''}>
                    ${escapeHtml(user.name || 'Sin nombre')}
                </td>
                <td>${escapeHtml(user.email || 'Sin email')}</td>
                <td>${escapeHtml(user.phone || 'N/A')}</td>
            `;
            usersList.appendChild(row);

            // Manejar clic en la fila
            row.addEventListener('click', function(e) {
                if (e.target.tagName !== 'INPUT' && !e.target.closest('input')) {
                    const checkbox = row.querySelector('.user-checkbox');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        updateSelectedUsers(checkbox);
                    }
                }
            });

            // Manejar cambio en el checkbox
            const checkbox = row.querySelector('.user-checkbox');
            if (checkbox) {
                checkbox.addEventListener('change', function() {
                    updateSelectedUsers(this);
                });
            }
        });
        updateSelectedUsersCount();
        updateSelectAllUsersCheckbox();
    }

    function updateSelectedUsers(checkbox) {
        if (!checkbox) return;
        
        const userId = checkbox.value;
        const row = checkbox.closest('tr');

        if (checkbox.checked) {
            if (!selectedUsers.includes(userId)) {
                selectedUsers.push(userId);
            }
            if (row) row.classList.add('selected');
        } else {
            selectedUsers = selectedUsers.filter(id => id !== userId);
            if (row) row.classList.remove('selected');
        }
        updateSelectedUsersCount();
        updateSelectAllUsersCheckbox();
        updateUsersSelectionSummary();
    }

    function updateSelectAllUsersCheckbox() {
        if (!selectAllUsersCheckbox || !usersList) return;
        
        const visibleCheckboxes = usersList.querySelectorAll('.user-checkbox');
        const checkedCheckboxes = usersList.querySelectorAll('.user-checkbox:checked');

        if (visibleCheckboxes.length === 0) {
            selectAllUsersCheckbox.indeterminate = false;
            selectAllUsersCheckbox.checked = false;
        } else if (checkedCheckboxes.length === visibleCheckboxes.length) {
            selectAllUsersCheckbox.indeterminate = false;
            selectAllUsersCheckbox.checked = true;
        } else if (checkedCheckboxes.length > 0) {
            selectAllUsersCheckbox.indeterminate = true;
            selectAllUsersCheckbox.checked = false;
        } else {
            selectAllUsersCheckbox.indeterminate = false;
            selectAllUsersCheckbox.checked = false;
        }
    }

    if (selectAllUsersCheckbox) {
        selectAllUsersCheckbox.addEventListener('change', function() {
            if (!usersList) return;
            
            const visibleCheckboxes = usersList.querySelectorAll('.user-checkbox');

            visibleCheckboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');

                if (this.checked) {
                    checkbox.checked = true;
                    if (!selectedUsers.includes(checkbox.value)) {
                        selectedUsers.push(checkbox.value);
                    }
                    if (row) row.classList.add('selected');
                } else {
                    checkbox.checked = false;
                    selectedUsers = selectedUsers.filter(id => id !== checkbox.value);
                    if (row) row.classList.remove('selected');
                }
            });
            updateSelectedUsersCount();
            updateUsersSelectionSummary();
        });
    }

    function updateSelectedUsersCount() {
        if (selectedUsersCount) {
            selectedUsersCount.textContent = `${selectedUsers.length} seleccionados`;
        }
    }

    if (applyUsersBtn) {
        applyUsersBtn.addEventListener('click', function() {
            if (selectedUsersInput) {
                selectedUsersInput.value = JSON.stringify(selectedUsers);
            }

            updateUsersSelectionSummary();

            if (userModal) {
                userModal.style.display = 'none';
            }
        });
    }

    const clearSelectedUsers = document.getElementById('clear-selected-users');
    if (clearSelectedUsers) {
        clearSelectedUsers.addEventListener('click', function() {
            clearUsersSelection();
        });
    }

    if (userSearchInput) {
        userSearchInput.addEventListener('input', debounce(function() {
            loadUsers(this.value.trim());
        }, 300));
    }

    // Cerrar modales al hacer clic fuera
    window.addEventListener('click', function(event) {
        if (event.target === productsModal) {
            productsModal.style.display = 'none';
        }
        if (event.target === userModal) {
            userModal.style.display = 'none';
        }
    });
});
</script>