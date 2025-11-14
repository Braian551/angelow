document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('confirmation-modal');
    if (!modal) {
        return;
    }

    const TYPE_CLASSES = ['success', 'warning', 'error', 'info'];
    const overlay = modal.querySelector('.confirmation-modal__overlay');
    const messageEl = modal.querySelector('[data-confirmation-message]');
    const titleEl = modal.querySelector('#confirmation-title');
    const confirmBtn = modal.querySelector('[data-confirmation-confirm]');
    const cancelBtn = modal.querySelector('[data-confirmation-cancel]');
    const closeEls = modal.querySelectorAll('[data-confirmation-close]');

    let confirmCallback = null;
    let cancelCallback = null;

    function setType(type) {
        TYPE_CLASSES.forEach(cls => modal.classList.remove(cls));
        if (type && TYPE_CLASSES.includes(type)) {
            modal.classList.add(type);
        }
    }

    function closeModal(triggerCancel = false) {
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');

        if (triggerCancel && typeof cancelCallback === 'function') {
            cancelCallback();
        }

        confirmCallback = null;
        cancelCallback = null;
    }

    if (overlay) {
        overlay.addEventListener('click', () => closeModal(true));
    }

    closeEls.forEach(btn => {
        btn.addEventListener('click', () => closeModal(true));
    });

    if (cancelBtn) {
        cancelBtn.addEventListener('click', (event) => {
            event.preventDefault();
            closeModal(true);
        });
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', (event) => {
            event.preventDefault();
            if (typeof confirmCallback === 'function') {
                confirmCallback();
            }
            closeModal(false);
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('active')) {
            closeModal(true);
        }
    });

    window.openConfirmationModal = function({
        message = 'Confirma esta acción',
        title = 'Confirmación requerida',
        confirmText = 'Confirmar',
        cancelText = 'Cancelar',
        type = 'warning',
        onConfirm,
        onCancel
    } = {}) {
        if (!modal || !confirmBtn || !messageEl) {
            return;
        }

        messageEl.innerHTML = message;
        if (titleEl) {
            titleEl.textContent = title;
        }

        confirmBtn.textContent = confirmText;
        if (cancelBtn) {
            cancelBtn.textContent = cancelText;
        }

        confirmCallback = typeof onConfirm === 'function' ? onConfirm : null;
        cancelCallback = typeof onCancel === 'function' ? onCancel : null;

        setType(type);

        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    };
});
