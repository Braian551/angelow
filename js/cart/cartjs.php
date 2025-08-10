
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const cartItemsContainer = document.querySelector('.cart-items-list');
    const cartSummary = document.querySelector('.cart-summary');
    const notificationContainer = document.querySelector('.floating-notification-container');

    // Mostrar notificación mejorada
    function showNotification(message, type = 'success') {
        // Limpiar notificaciones existentes primero
        const existingNotifications = document.querySelectorAll('.floating-notification');
        existingNotifications.forEach(notification => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });

        const notification = document.createElement('div');
        notification.className = `floating-notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-times-circle' : 'fa-info-circle'}"></i>
                </div>
                <div class="notification-text">
                    <span>${message}</span>
                </div>
                <button class="close-notification">&times;</button>
            </div>
        `;

        notificationContainer.appendChild(notification);

        // Forzar reflow para que la animación funcione
        void notification.offsetWidth;

        notification.classList.add('show');

        // Auto cerrar después de 3 segundos
        const autoClose = setTimeout(() => {
            closeNotification(notification);
        }, 3000);

        // Cerrar manualmente
        notification.querySelector('.close-notification').addEventListener('click', () => {
            clearTimeout(autoClose);
            closeNotification(notification);
        });

        function closeNotification(notificationElement) {
            notificationElement.classList.remove('show');
            setTimeout(() => {
                if (notificationElement.parentNode) {
                    notificationElement.remove();
                }
            }, 300);
        }
    }

    // Mostrar alerta de confirmación
    function showAlert(options) {
        return new Promise((resolve) => {
            const alertOverlay = document.createElement('div');
            alertOverlay.className = 'alert-overlay';
            
            alertOverlay.innerHTML = `
                <div class="alert-box ${options.type || ''}">
                    <button class="alert-close">&times;</button>
                    <div class="alert-icon-container">
                        <div class="alert-icon">
                            <i class="fas ${options.icon || 'fa-info-circle'}"></i>
                        </div>
                    </div>
                    <div class="alert-message">${options.message}</div>
                    <div class="alert-buttons">
                        <button class="alert-button outline cancel-btn">${options.cancelText || 'Cancelar'}</button>
                        <button class="alert-button confirm-btn">${options.confirmText || 'Confirmar'}</button>
                    </div>
                </div>
            `;

            document.body.appendChild(alertOverlay);
            
            // Forzar reflow para animación
            void alertOverlay.offsetWidth;
            alertOverlay.classList.add('active');

            // Event listeners
            const closeAlert = (result) => {
                alertOverlay.classList.remove('active');
                setTimeout(() => {
                    alertOverlay.remove();
                    resolve(result);
                }, 300);
            };

            alertOverlay.querySelector('.alert-close').addEventListener('click', () => closeAlert(false));
            alertOverlay.querySelector('.cancel-btn').addEventListener('click', () => closeAlert(false));
            alertOverlay.querySelector('.confirm-btn').addEventListener('click', () => closeAlert(true));
        });
    }

    // Actualizar cantidad del item
    function updateQuantity(itemId, newQuantity) {
        const itemElement = document.querySelector(`.cart-item[data-item-id="${itemId}"]`);
        if (!itemElement) {
            console.error(`Item con ID ${itemId} no encontrado`);
            return;
        }

        // Mostrar estado de carga solo en el item específico
        itemElement.classList.add('updating');
        
        // Guardar el valor anterior para posible restauración
        const inputElement = itemElement.querySelector('.quantity-input');
        const previousValue = inputElement.value;
        inputElement.setAttribute('data-previous-value', previousValue);

        fetch(`<?= BASE_URL ?>/ajax/cart/update-quantity.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    item_id: itemId,  // Asegurando que se envía el ID correcto
                    quantity: newQuantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar solo los elementos del item específico
                    const priceElement = itemElement.querySelector('.item-price');
                    const totalElement = itemElement.querySelector('.item-total');
                    
                    if (priceElement && totalElement) {
                        // Actualizar el valor del input
                        inputElement.value = newQuantity;
                        inputElement.setAttribute('max', data.max_quantity || inputElement.getAttribute('max'));

                        // Actualizar el total con el precio devuelto por el servidor
                        const newTotal = data.item_total;
                        const formattedTotal = `$${Math.round(newTotal).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`;

                        // Animación de actualización
                        totalElement.style.transform = 'scale(1.1)';
                        totalElement.style.color = 'var(--cart-primary, #007bff)';
                        
                        setTimeout(() => {
                            totalElement.textContent = formattedTotal;
                            totalElement.style.transform = 'scale(1)';
                            setTimeout(() => {
                                totalElement.style.color = '';
                            }, 500);
                        }, 200);
                    }

                    // Actualizar contador y resumen con los datos del servidor
                    updateCartSummary(data.cart_total);
                    updateCartCount();
                    
                    showNotification('Cantidad actualizada correctamente');
                } else {
                    showNotification(data.error || 'Error al actualizar la cantidad', 'error');
                    // Restaurar el valor anterior en caso de error
                    inputElement.value = previousValue;
                }
                itemElement.classList.remove('updating');
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al actualizar la cantidad', 'error');
                // Restaurar el valor anterior en caso de error
                inputElement.value = previousValue;
                itemElement.classList.remove('updating');
            });
    }

    // Eliminar item del carrito con mejor animación
    function removeItem(itemId) {
        const itemElement = document.querySelector(`.cart-item[data-item-id="${itemId}"]`);
        if (!itemElement) return;

        // Mostrar alerta de confirmación
        showAlert({
            type: 'warning',
            icon: 'fa-exclamation-triangle',
            message: '¿Estás seguro de que quieres eliminar este producto de tu carrito?',
            confirmText: 'Eliminar',
            cancelText: 'Cancelar'
        }).then(confirmed => {
            if (!confirmed) return;

            itemElement.classList.add('removing');

            fetch(`<?= BASE_URL ?>/tienda/api/cart/remove-cart.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        item_id: itemId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Animación de eliminación
                        itemElement.style.transform = 'translateX(-100%)';
                        itemElement.style.opacity = '0';
                        setTimeout(() => {
                            itemElement.remove();

                            // Verificar si el carrito quedó vacío
                            if (document.querySelectorAll('.cart-item').length === 0) {
                                location.reload();
                            } else {
                                updateCartSummary(data.cart_total);
                            }
                        }, 300);

                        showNotification('Producto eliminado del carrito');
                        updateCartCount(); // Actualizar el contador
                    } else {
                        itemElement.classList.remove('removing');
                        showNotification(data.error || 'Error al eliminar el producto', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    itemElement.classList.remove('removing');
                    showNotification('Error al eliminar el producto', 'error');
                });
        });
    }

    // Actualizar resumen del carrito con el total proporcionado
    function updateCartSummary(cartTotal) {
        // Actualizar elementos del resumen con animación
        const summaryRows = document.querySelectorAll('.summary-row');
        let subtotalElement = null;
        let totalElement = null;

        summaryRows.forEach(row => {
            const label = row.querySelector('span:first-child');
            if (label) {
                if (label.textContent.trim().toLowerCase() === 'subtotal') {
                    subtotalElement = row.querySelector('span:last-child');
                } else if (label.textContent.trim().toLowerCase() === 'total') {
                    totalElement = row.querySelector('span:last-child');
                }
            }
        });

        const formattedTotal = `$${Math.round(cartTotal).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`;

        // Animación de actualización para subtotal
        if (subtotalElement) {
            subtotalElement.style.transform = 'scale(1.1)';
            subtotalElement.style.color = 'var(--cart-primary, #007bff)';
            setTimeout(() => {
                subtotalElement.textContent = formattedTotal;
                subtotalElement.style.transform = 'scale(1)';
                setTimeout(() => {
                    subtotalElement.style.color = '';
                }, 500);
            }, 200);
        }

        // Animación de actualización para total
        if (totalElement) {
            totalElement.style.transform = 'scale(1.1)';
            setTimeout(() => {
                totalElement.textContent = formattedTotal;
                totalElement.style.transform = 'scale(1)';
            }, 200);
        }
    }

    // Actualizar contador del carrito en el header
    function updateCartCount() {
        fetch(`<?= BASE_URL ?>/ajax/cart/get_cart_count.php`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cartCountElements = document.querySelectorAll('.cart-count');
                
                cartCountElements.forEach(el => {
                    el.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        el.textContent = data.count;
                        el.style.transform = 'scale(1)';
                    }, 200);
                });
            }
        })
        .catch(error => console.error('Error al obtener carrito:', error));
    }

    // Event listeners
    if (cartItemsContainer) {
        // Botones de cantidad con feedback táctil
        cartItemsContainer.addEventListener('click', function(e) {
            const minusBtn = e.target.closest('.quantity-btn.minus');
            const plusBtn = e.target.closest('.quantity-btn.plus');
            const removeBtn = e.target.closest('.remove-item');

            if (minusBtn || plusBtn) {
                const btn = minusBtn || plusBtn;
                const itemId = btn.getAttribute('data-item-id');
                const itemElement = document.querySelector(`.cart-item[data-item-id="${itemId}"]`);
                
                if (!itemElement) {
                    console.error(`Item ${itemId} no encontrado`);
                    return;
                }
                
                const input = itemElement.querySelector('.quantity-input');
                if (!input) {
                    console.error(`Input de cantidad no encontrado para item ${itemId}`);
                    return;
                }
                
                let quantity = parseInt(input.value);
                const max = parseInt(input.getAttribute('max'));
                const min = parseInt(input.getAttribute('min')) || 1;

                // Efecto de clic
                btn.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    btn.style.transform = '';
                }, 200);

                if (minusBtn && quantity > min) {
                    quantity--;
                    updateQuantity(itemId, quantity);
                } else if (plusBtn && quantity < max) {
                    quantity++;
                    updateQuantity(itemId, quantity);
                } else if (plusBtn && quantity >= max) {
                    showNotification(`No hay suficiente stock disponible. Máximo: ${max}`, 'error');
                    // Animación de error en el input específico
                    input.style.borderColor = '#dc3545';
                    setTimeout(() => {
                        input.style.borderColor = '';
                    }, 1000);
                } else if (minusBtn && quantity <= min) {
                    showNotification(`Cantidad mínima: ${min}`, 'error');
                    input.style.borderColor = '#dc3545';
                    setTimeout(() => {
                        input.style.borderColor = '';
                    }, 1000);
                }
            }

            if (removeBtn) {
                const itemId = removeBtn.getAttribute('data-item-id');
                removeItem(itemId);
            }
        });

        // Cambio manual de cantidad con validación
        cartItemsContainer.addEventListener('change', function(e) {
            const input = e.target.closest('.quantity-input');
            if (input) {
                const itemElement = input.closest('.cart-item');
                const itemId = itemElement.getAttribute('data-item-id');
                
                let quantity = parseInt(input.value);
                const max = parseInt(input.getAttribute('max'));
                const min = parseInt(input.getAttribute('min')) || 1;

                if (isNaN(quantity)) quantity = min;
                if (quantity < min) quantity = min;
                if (quantity > max) {
                    quantity = max;
                    showNotification(`No hay suficiente stock disponible. Máximo: ${max}`, 'error');
                    // Animación de error
                    input.style.borderColor = '#dc3545';
                    setTimeout(() => {
                        input.style.borderColor = '';
                    }, 1000);
                }

                input.value = quantity;
                updateQuantity(itemId, quantity);
            }
        });
    }

    // Aplicar código de descuento con mejor feedback
    const promoBtn = document.querySelector('.promo-btn');
    if (promoBtn) {
        promoBtn.addEventListener('click', function() {
            const promoInput = document.querySelector('.promo-input');
            if (promoInput.value.trim() === '') {
                showNotification('Por favor ingresa un código de descuento', 'error');
                promoInput.focus();
                // Animación de error
                promoInput.style.borderColor = '#dc3545';
                setTimeout(() => {
                    promoInput.style.borderColor = '';
                }, 1000);
                return;
            }

            // Efecto de clic
            promoBtn.style.transform = 'scale(0.95)';
            setTimeout(() => {
                promoBtn.style.transform = '';
            }, 200);

            // Simular validación del código
            showNotification('Código de descuento no válido', 'error');
            promoInput.value = '';
        });
    }

    // Inicializar contador del carrito
    updateCartCount();
});
</script>
