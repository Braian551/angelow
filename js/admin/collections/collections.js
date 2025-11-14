const CollectionsAdmin = (() => {
    const baseUrl = document.body.dataset.baseUrl || '';
    const toggleEndpoint = `${baseUrl}/ajax/admin/collections/toggle_collection.php`;
    const deleteEndpoint = `${baseUrl}/ajax/admin/collections/delete_collection.php`;

    const selectors = {
        filterForm: 'collections-filter-form',
        clearFilters: 'clear-filters',
        searchInput: 'collection-search',
        clearSearch: 'clear-search',
        toggleButtons: '.js-collection-toggle',
        deleteButtons: '.js-collection-delete'
    };

    function initFilters() {
        const form = document.getElementById(selectors.filterForm);
        const clearFiltersBtn = document.getElementById(selectors.clearFilters);
        const searchInput = document.getElementById(selectors.searchInput);
        const clearSearchBtn = document.getElementById(selectors.clearSearch);
        const filtersToggle = document.getElementById('toggle-filters');
        const advancedFilters = document.getElementById('advanced-filters');

        if (!form) {
            return;
        }

        form.addEventListener('submit', () => {
            form.querySelectorAll('button[type="submit"]').forEach(btn => btn.disabled = true);
        });

        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', (event) => {
                event.preventDefault();
                window.location.href = 'collections_list.php';
            });
        }

        if (searchInput && clearSearchBtn) {
            const toggleClearVisibility = () => {
                clearSearchBtn.style.display = searchInput.value.trim().length ? 'inline-flex' : 'none';
            };

            toggleClearVisibility();
            searchInput.addEventListener('input', toggleClearVisibility);
            clearSearchBtn.addEventListener('click', (event) => {
                event.preventDefault();
                searchInput.value = '';
                toggleClearVisibility();
                form.submit();
            });
        }

        if (filtersToggle && advancedFilters) {
            const setFiltersVisibility = (collapsed) => {
                advancedFilters.classList.toggle('collapsed', collapsed);
                filtersToggle.setAttribute('aria-expanded', String(!collapsed));
                filtersToggle.setAttribute('aria-label', collapsed ? 'Mostrar filtros avanzados' : 'Ocultar filtros avanzados');
            };

            const initialCollapsed = filtersToggle.getAttribute('aria-expanded') === 'false';
            setFiltersVisibility(initialCollapsed);

            filtersToggle.addEventListener('click', () => {
                const nextState = !advancedFilters.classList.contains('collapsed');
                setFiltersVisibility(nextState);
            });
        }
    }

    function initToggleButtons() {
        document.querySelectorAll(selectors.toggleButtons).forEach((button) => {
            button.addEventListener('click', () => toggleCollection(button));
        });
    }

    function initDeleteButtons() {
        document.querySelectorAll(selectors.deleteButtons).forEach((button) => {
            button.addEventListener('click', () => handleDelete(button));
        });
    }

    function toggleCollection(button) {
        const collectionId = button.dataset.id;
        const currentStatus = button.dataset.active === '1';

        if (!collectionId) {
            return;
        }

        button.disabled = true;

        const payload = new FormData();
        payload.append('id', collectionId);

        fetch(toggleEndpoint, {
            method: 'POST',
            body: payload,
            credentials: 'same-origin'
        })
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) {
                    throw new Error(data.message || 'Error al actualizar el estado');
                }
                const statusValue = Number(data.new_status);
                updateRowState(collectionId, statusValue === 1);
                showToast('Estado actualizado', 'success');
            })
            .catch((error) => {
                console.error(error);
                showToast(error.message || 'Error al actualizar el estado', 'error');
                button.disabled = false;
            })
            .finally(() => {
                button.disabled = false;
            });
    }

    function updateRowState(collectionId, isActive) {
        const row = document.querySelector(`tr[data-collection-id="${collectionId}"]`);
        if (!row) {
            return;
        }

        const badge = row.querySelector('.status-badge');
        const statusCell = row.querySelector('.status-cell small');
        const toggleButton = row.querySelector(selectors.toggleButtons);

        if (badge) {
            badge.textContent = isActive ? 'Activa' : 'Inactiva';
            badge.classList.toggle('status-active', isActive);
            badge.classList.toggle('status-inactive', !isActive);
        }

        if (statusCell) {
            const now = new Date();
            statusCell.textContent = `Actualizada ${now.toLocaleDateString()} ${now.toLocaleTimeString().slice(0, 5)}`;
        }

        if (toggleButton) {
            toggleButton.dataset.active = isActive ? '1' : '0';
            const label = toggleButton.querySelector('span');
            if (label) {
                label.textContent = isActive ? 'Desactivar' : 'Activar';
            }
        }
    }

    function handleDelete(button) {
        const inUse = button.dataset.inUse === '1';
        const collectionName = button.dataset.name || 'esta colección';
        const collectionId = button.dataset.id;

        if (inUse) {
            showToast('No puedes eliminar una colección que está en uso por productos.', 'error');
            return;
        }

        const confirmDeletion = () => {
            const payload = new FormData();
            payload.append('id', collectionId);

            fetch(deleteEndpoint, {
                method: 'POST',
                body: payload,
                credentials: 'same-origin'
            })
                .then((response) => response.json())
                .then((data) => {
                    if (!data.success) {
                        throw new Error(data.message || 'No se pudo eliminar la colección');
                    }

                    const row = document.querySelector(`tr[data-collection-id="${collectionId}"]`);
                    if (row) {
                        row.classList.add('fade-out');
                        setTimeout(() => {
                            row.remove();
                            const hasRows = document.querySelectorAll('.collections-table tbody tr').length > 0;
                            if (!hasRows) {
                                window.location.reload();
                            }
                        }, 200);
                    }
                    showToast('Colección eliminada', 'success');
                })
                .catch((error) => {
                    console.error(error);
                    showToast(error.message || 'Error al eliminar la colección', 'error');
                });
        };

        if (typeof window.openConfirmationModal === 'function') {
            window.openConfirmationModal({
                title: 'Eliminar colección',
                message: `¿Deseas eliminar la colección <strong>${escapeHtml(collectionName)}</strong>?`,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                type: 'warning',
                onConfirm: confirmDeletion
            });
        } else if (confirm(`¿Eliminar ${collectionName}?`)) {
            confirmDeletion();
        }
    }

    function escapeHtml(text = '') {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        };
        return text.replace(/[&<>"']/g, (char) => map[char] || char);
    }

    function showToast(message, type = 'info') {
        let toast = document.getElementById('toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'toast';
            document.body.appendChild(toast);
        }

        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(() => toast.classList.remove('show'), 3200);
    }

    function init() {
        initFilters();
        initToggleButtons();
        initDeleteButtons();
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', () => CollectionsAdmin.init());
