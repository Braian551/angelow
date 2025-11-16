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

    window.openStatusChangeModal = function(orderId) {
        openChangeFieldModal(orderId, {
            type: 'status',
            title: 'Cambiar estado',
            options: window.orderStatuses || window.statuses || {}
        });
    };

    window.openChangeFieldModal = openChangeFieldModal;

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

    function openChangeFieldModal(orderId, { type = 'status', title = 'Cambiar', options = {} } = {}) {
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
            selectField.appendChild(option);
        });

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