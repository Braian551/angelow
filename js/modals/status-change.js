// Fallback queue so the global helpers can be called even before DOMContentLoaded
window.__openChangeFieldQueue = window.__openChangeFieldQueue || [];
window.openStatusChangeModal = window.openStatusChangeModal || function(orderId, currentValue) {
    window.__openChangeFieldQueue.push({ orderId, type: 'status', currentValue });
};
window.openPaymentStatusModal = window.openPaymentStatusModal || function(orderId, currentValue) {
    window.__openChangeFieldQueue.push({ orderId, type: 'payment', currentValue });
};

document.addEventListener('DOMContentLoaded', function() {
    const changeFieldModal = document.getElementById('change-field-modal');
    if (!changeFieldModal) {
        return; // Modal no está presente en esta página
    }

    const cancelBtn = document.getElementById('cancel-change-field');
    const confirmBtn = document.getElementById('confirm-change-field');
    const notesField = document.getElementById('change-field-notes');
    const titleField = document.getElementById('change-field-title');
    const selectField = document.getElementById('change-field-select');

    let currentOrderIdForChange = null;
    let currentChangeType = null; // 'status' | 'payment'

    window.openStatusChangeModal = function(orderId, currentValue = null, paymentStatus = null) {
        openChangeFieldModal(orderId, {
            type: 'status',
            title: 'Cambiar estado',
            options: window.orderStatuses || window.statuses || {},
            paymentStatus: paymentStatus
        }, currentValue);
    };

    window.openPaymentStatusModal = function(orderId, currentValue = null) {
        openChangeFieldModal(orderId, {
            type: 'payment',
            title: 'Cambiar Estado de Pago',
            options: window.paymentStatuses || {}
        }, currentValue);
    };

    window.openChangeFieldModal = openChangeFieldModal;

    // process pending calls made before DOMContentLoaded
    if (Array.isArray(window.__openChangeFieldQueue) && window.__openChangeFieldQueue.length) {
        window.__openChangeFieldQueue.forEach(item => {
            if (item.type === 'status') {
                openChangeFieldModal(item.orderId, { type: 'status', title: 'Cambiar estado', options: window.orderStatuses || window.statuses || {} }, item.currentValue);
            } else {
                openChangeFieldModal(item.orderId, { type: 'payment', title: 'Cambiar Estado de Pago', options: window.paymentStatuses || {} }, item.currentValue);
            }
        });
        window.__openChangeFieldQueue.length = 0;
    }

    function closeChangeFieldModal() {
        changeFieldModal.classList.remove('active');
        document.body.style.overflow = '';
        currentOrderIdForChange = null;
        currentChangeType = null;
    }

    async function confirmChangeField() {
        if (!currentOrderIdForChange || !currentChangeType) return;
        const newValue = selectField.value;
        const notes = notesField.value;

        if (!newValue) {
            showAlert('Selecciona un valor antes de continuar', 'warning');
            return;
        }

        const meta = document.querySelector('meta[name="base-url"]');
        const baseUrl = meta ? meta.content : '';
        let url = '';
        let payload = {};

        if (currentChangeType === 'status') {
            url = `${baseUrl}/admin/order/update_status.php`;
            payload = { order_ids: [currentOrderIdForChange], new_status: newValue, notes };
        } else {
            url = `${baseUrl}/admin/order/update_payment_status.php`;
            payload = { order_ids: [currentOrderIdForChange], new_payment_status: newValue, notes };
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const contentType = response.headers.get('content-type') || '';
            if (!response.ok) {
                if (contentType.includes('application/json')) {
                    const data = await response.json();
                    throw new Error(data.message || data.error || 'Error al actualizar');
                }
                throw new Error((await response.text()) || 'Error al actualizar');
            }
            if (!contentType.includes('application/json')) {
                throw new Error('Respuesta inesperada: ' + (await response.text() || ''));
            }

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'Error al actualizar estado');
            }

            const successMsg = currentChangeType === 'payment'
                ? 'Estado de pago actualizado correctamente'
                : 'Estado actualizado correctamente';
            showAlert(successMsg, 'success');

            if (typeof window.loadOrders === 'function') {
                window.loadOrders();
            } else {
                setTimeout(() => location.reload(), 1200);
            }
        } catch (error) {
            showAlert(error.message || 'Error inesperado', 'error');
        } finally {
            closeChangeFieldModal();
        }
    }

    function openChangeFieldModal(orderId, { type = 'status', title = 'Cambiar', options = {}, paymentStatus = null } = {}, currentValue = null) {
        currentOrderIdForChange = orderId;
        currentChangeType = type;
        notesField.value = '';
        if (titleField) {
            titleField.textContent = title;
        }

        selectField.innerHTML = '';
        const opts = options && typeof options === 'object' ? options : {};
        Object.entries(opts).forEach(([value, label]) => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = label;
            // Si el admin intenta poner en proceso/enviado sin pago, deshabilitar la opción
            if (type === 'status' && paymentStatus && ['processing', 'shipped'].includes(value) && paymentStatus !== 'paid') {
                option.disabled = true;
                option.textContent += ' (requiere pago)';
            }
            selectField.appendChild(option);
        });

        // Si se pasó un currentValue, intentar seleccionarlo
        if (currentValue) {
            // Normalizar tipos y buscar coincidencia
            const desired = String(currentValue);
            const exists = Array.from(selectField.options).some(o => o.value === desired);
            if (exists) {
                selectField.value = desired;
            } else {
                // Si no existe la opción, agregarla como primer elemento y seleccionarla
                const opt = document.createElement('option');
                opt.value = desired;
                opt.textContent = desired;
                selectField.insertBefore(opt, selectField.firstChild);
                selectField.value = desired;
            }
        }

        changeFieldModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    cancelBtn?.addEventListener('click', closeChangeFieldModal);
    confirmBtn?.addEventListener('click', confirmChangeField);
    changeFieldModal.addEventListener('click', function(e) {
        if (e.target === this) closeChangeFieldModal();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && changeFieldModal.classList.contains('active')) {
            closeChangeFieldModal();
        }
    });
});