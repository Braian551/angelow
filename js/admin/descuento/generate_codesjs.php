<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar sidebar (solución para el error setupSidebarCollapse)
        if (typeof setupSidebarCollapse === 'function') {
            setupSidebarCollapse();
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
                    if (code.includes(searchTerm)) {
                        row.style.display = '';
                        hasResults = true;
                    } else {
                        row.style.display = 'none';
                    }
                });

                const noResults = document.getElementById('no-results');
                if (!hasResults && searchTerm.length > 0) {
                    noResults.style.display = 'flex';
                } else {
                    noResults.style.display = 'none';
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
            const discountType = discountTypeSelect.value;

            // Ocultar todos los grupos primero
            discountValueGroup.style.display = 'none';
            fixedAmountGroup.style.display = 'none';
            maxDiscountGroup.style.display = 'none';
            minOrderGroup.style.display = 'none';
            shippingMethodGroup.style.display = 'none';

            // Mostrar los campos relevantes según el tipo
            switch (discountType) {
                case '1': // Porcentaje
                    discountValueGroup.style.display = 'block';
                    maxDiscountGroup.style.display = 'block';
                    break;
                case '2': // Monto fijo
                    fixedAmountGroup.style.display = 'block';
                    minOrderGroup.style.display = 'block';
                    break;
                case '3': // Envío gratis
                    shippingMethodGroup.style.display = 'block';
                    break;
            }
        }

        if (discountTypeSelect) {
            discountTypeSelect.addEventListener('change', updateDiscountFields);
            // Ejecutar al cargar para establecer el estado inicial
            updateDiscountFields();
        }

        // Mostrar/ocultar selección de productos
        const applyToAllCheckbox = document.getElementById('apply_to_all');
        const openProductsModal = document.getElementById('open-products-modal');

        if (applyToAllCheckbox && openProductsModal) {
            applyToAllCheckbox.addEventListener('change', function() {
                openProductsModal.style.display = this.checked ? 'none' : 'block';
            });

            // Ejecutar al cargar la página
            openProductsModal.style.display = applyToAllCheckbox.checked ? 'none' : 'block';
        }

        // Mostrar/ocultar campo de email para notificación
        const sendNotificationCheckbox = document.getElementById('send_notification');
        if (sendNotificationCheckbox) {
            sendNotificationCheckbox.addEventListener('change', function() {
                const notificationGroup = document.getElementById('notification-email-group');
                if (notificationGroup) {
                    notificationGroup.style.display = this.checked ? 'block' : 'none';
                }
            });
        }

        // Copiar código al portapapeles
        document.querySelectorAll('.btn-copy').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const code = this.getAttribute('data-code');
                navigator.clipboard.writeText(code).then(() => {
                    showAlert('Código copiado al portapapeles', 'success');
                });
            });
        });

        // Modal de productos
        const productsModal = document.getElementById('products-modal');
        const openProductsModalBtn = document.getElementById('open-products-modal');
        const closeProductsModal = document.querySelector('#products-modal .close-modal');
        const productSearchInput = document.getElementById('modal-product-search');
        const productsList = document.getElementById('products-list');
        const applyProductsBtn = document.getElementById('apply-products-selection');
        const selectAllProductsCheckbox = document.getElementById('select-all-products-checkbox');
        const selectedProductsCount = document.getElementById('selected-products-count');
        const selectedProductsInput = document.getElementById('selected-products');
        const productsLoading = document.getElementById('products-loading');
        const noProductsFound = document.getElementById('no-products-found');

        let selectedProducts = JSON.parse(selectedProductsInput.value || '[]');
        let allProducts = [];
        let filteredProducts = [];

        if (openProductsModalBtn) {
            openProductsModalBtn.addEventListener('click', function() {
                productsModal.style.display = 'block';
                loadRealProducts(); // Cargar productos reales
            });
        }

        if (closeProductsModal) {
            closeProductsModal.addEventListener('click', function() {
                productsModal.style.display = 'none';
            });
        }

        // Función para cargar productos reales desde la base de datos
        function loadRealProducts(searchTerm = '') {
            productsLoading.style.display = 'block';
            productsList.innerHTML = '';
            noProductsFound.style.display = 'none';

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
                        showAlert('Error al cargar productos: ' + data.message, 'error');
                        console.error('Error del servidor:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error en la petición:', error);
                    showAlert('Error de conexión al cargar productos: ' + error.message, 'error');
                })
                .finally(() => {
                    productsLoading.style.display = 'none';
                    if (filteredProducts.length === 0) {
                        noProductsFound.style.display = 'flex';
                    }
                });
        }

        function displayProducts(products) {
            productsList.innerHTML = '';

            if (products.length === 0) {
                noProductsFound.style.display = 'flex';
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
                        checkbox.checked = !checkbox.checked;
                        updateSelectedProducts(checkbox);
                    }
                });

                // Manejar cambio en el checkbox
                const checkbox = row.querySelector('.product-checkbox');
                checkbox.addEventListener('change', function() {
                    updateSelectedProducts(this);
                });
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
            const productId = checkbox.value;
            const row = checkbox.closest('tr');

            if (checkbox.checked) {
                if (!selectedProducts.includes(productId)) {
                    selectedProducts.push(productId);
                }
                row.classList.add('selected');
            } else {
                selectedProducts = selectedProducts.filter(id => id !== productId);
                row.classList.remove('selected');
            }
            updateSelectedCount();
            updateSelectAllProductsCheckbox();
        }

        function updateSelectAllProductsCheckbox() {
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
                const visibleCheckboxes = productsList.querySelectorAll('.product-checkbox');

                visibleCheckboxes.forEach(checkbox => {
                    const row = checkbox.closest('tr');

                    if (this.checked) {
                        checkbox.checked = true;
                        if (!selectedProducts.includes(checkbox.value)) {
                            selectedProducts.push(checkbox.value);
                        }
                        row.classList.add('selected');
                    } else {
                        checkbox.checked = false;
                        selectedProducts = selectedProducts.filter(id => id !== checkbox.value);
                        row.classList.remove('selected');
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
                selectedProductsInput.value = JSON.stringify(selectedProducts);
                productsModal.style.display = 'none';

                // Mostrar resumen de productos seleccionados
                updateProductsSelectionSummary();
            });
        }

        function updateProductsSelectionSummary() {
            const selectedProductsInfo = document.getElementById('selected-products-info');
            const selectedProductsCountDisplay = document.getElementById('selected-products-count-display');

            if (selectedProductsInfo && selectedProductsCountDisplay) {
                if (selectedProducts.length > 0) {
                    selectedProductsInfo.style.display = 'block';
                    selectedProductsCountDisplay.textContent = `${selectedProducts.length} producto(s) seleccionado(s)`;
                } else {
                    selectedProductsInfo.style.display = 'none';
                }
            }
        }

        if (productSearchInput) {
            productSearchInput.addEventListener('input', debounce(function() {
                loadRealProducts(this.value.trim());
            }, 300));
        }

        // Cerrar modal al hacer clic fuera
        window.addEventListener('click', function(event) {
            if (event.target === productsModal) {
                productsModal.style.display = 'none';
            }
        });

        // Modal de usuarios
        const userModal = document.getElementById('user-modal');
        const openUserModal = document.getElementById('open-user-modal');
        const closeUserModal = document.querySelectorAll('#user-modal .close-modal');
        const userSearchInput = document.getElementById('modal-user-search');
        const usersList = document.getElementById('users-list');
        const applyUsersBtn = document.getElementById('apply-users-selection');
        const selectAllUsersCheckbox = document.getElementById('select-all-users-checkbox');
        const selectedUsersCount = document.getElementById('selected-users-count');
        const selectedUsersInput = document.getElementById('selected_users');
        const selectedUsersInfo = document.getElementById('selected-users-info');
        const selectedUsersCountDisplay = document.getElementById('selected-users-count-display');
        const clearSelectedUsers = document.getElementById('clear-selected-users');

        let selectedUsers = JSON.parse(selectedUsersInput.value || '[]');
        let allUsers = <?= json_encode($usuarios) ?>;
        let filteredUsers = allUsers;

        if (openUserModal) {
            openUserModal.addEventListener('click', function() {
                userModal.style.display = 'block';
                loadUsers();
            });
        }

        if (closeUserModal) {
            closeUserModal.forEach(btn => {
                btn.addEventListener('click', function() {
                    userModal.style.display = 'none';
                });
            });
        }

        function loadUsers(searchTerm = '') {
            if (searchTerm) {
                filteredUsers = allUsers.filter(user =>
                    user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                    user.email.toLowerCase().includes(searchTerm.toLowerCase())
                );
            } else {
                filteredUsers = allUsers;
            }

            usersList.innerHTML = '';
            filteredUsers.forEach(user => {
                const isSelected = selectedUsers.includes(user.id.toString());
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <input type="checkbox" class="user-checkbox" value="${user.id}" 
                            ${isSelected ? 'checked' : ''}>
                        ${escapeHtml(user.name)}
                    </td>
                    <td>${escapeHtml(user.email)}</td>
                    <td>${escapeHtml(user.phone || 'N/A')}</td>
                `;
                usersList.appendChild(row);

                // Manejar cambio en el checkbox
                const checkbox = row.querySelector('.user-checkbox');
                checkbox.addEventListener('change', function() {
                    updateSelectedUsers(this);
                });
            });
            updateSelectedUsersCount();
            updateSelectAllUsersCheckbox();
        }

        function updateSelectedUsers(checkbox) {
            const userId = checkbox.value;
            if (checkbox.checked) {
                if (!selectedUsers.includes(userId)) {
                    selectedUsers.push(userId);
                }
            } else {
                selectedUsers = selectedUsers.filter(id => id !== userId);
            }
            updateSelectedUsersCount();
            updateSelectAllUsersCheckbox();
        }

        function updateSelectAllUsersCheckbox() {
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
                const visibleCheckboxes = usersList.querySelectorAll('.user-checkbox');

                visibleCheckboxes.forEach(checkbox => {
                    if (this.checked) {
                        checkbox.checked = true;
                        if (!selectedUsers.includes(checkbox.value)) {
                            selectedUsers.push(checkbox.value);
                        }
                    } else {
                        checkbox.checked = false;
                        selectedUsers = selectedUsers.filter(id => id !== checkbox.value);
                    }
                });
                updateSelectedUsersCount();
            });
        }

        function updateSelectedUsersCount() {
            if (selectedUsersCount) {
                selectedUsersCount.textContent = `${selectedUsers.length} seleccionados`;
            }
        }

        if (applyUsersBtn) {
            applyUsersBtn.addEventListener('click', function() {
                selectedUsersInput.value = JSON.stringify(selectedUsers);

                if (selectedUsers.length > 0) {
                    selectedUsersInfo.style.display = 'block';
                    selectedUsersCountDisplay.textContent = `${selectedUsers.length} usuario(s) seleccionado(s)`;
                } else {
                    selectedUsersInfo.style.display = 'none';
                }

                userModal.style.display = 'none';
            });
        }

        if (clearSelectedUsers) {
            clearSelectedUsers.addEventListener('click', function() {
                selectedUsers = [];
                selectedUsersInput.value = '[]';
                selectedUsersInfo.style.display = 'none';
            });
        }

        if (userSearchInput) {
            userSearchInput.addEventListener('input', debounce(function() {
                loadUsers(this.value.trim());
            }, 300));
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