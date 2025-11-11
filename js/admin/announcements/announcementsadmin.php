<?php
// Solo enviar header si se accede directamente al archivo, no cuando se incluye
if (__FILE__ === $_SERVER['SCRIPT_FILENAME']) {
    header('Content-Type: application/javascript');
}
require_once __DIR__ . '/../../../config.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    let currentFilters = {};
    let deleteAnnouncementId = null;

    const container = document.getElementById('announcements-container');
    const paginationContainer = document.getElementById('pagination-container');
    const resultsCount = document.getElementById('results-count');
    const searchForm = document.getElementById('search-form');
    const deleteModal = document.getElementById('delete-modal');
    const confirmDeleteBtn = document.getElementById('confirm-delete');

    // Cargar anuncios
    function loadAnnouncements(page = 1) {
        currentPage = page;
        const params = new URLSearchParams({
            page: currentPage,
            ...currentFilters
        });

        container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Cargando anuncios...</div>';

        fetch(`<?= BASE_URL ?>/ajax/admin/get_announcements.php?${params}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Error al cargar anuncios');
                }

                displayAnnouncements(data.announcements);
                updatePagination(data.page, data.pages);
                updateResultsCount(data.total);
            })
            .catch(error => {
                container.innerHTML = `<div class="alert alert-error">${error.message}</div>`;
            });
    }

    // Mostrar anuncios
    function displayAnnouncements(announcements) {
        if (announcements.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No se encontraron anuncios.</div>';
            return;
        }

        container.innerHTML = announcements.map(announcement => {
            const typeLabel = announcement.type === 'top_bar' ? 'Barra Superior' : 'Banner Promo';
            const typeBadge = `<span class="type-badge type-${announcement.type}">${typeLabel}</span>`;
            const statusBadge = announcement.is_active 
                ? '<span class="badge-success">Activo</span>' 
                : '<span class="badge-danger">Inactivo</span>';
            
            const imageHtml = announcement.image 
                ? `<img src="<?= BASE_URL ?>/${announcement.image}" alt="${announcement.title}" style="max-width: 100%; height: auto; border-radius: 4px;">`
                : '<div style="background: #f0f0f0; height: 120px; display: flex; align-items: center; justify-content: center; border-radius: 4px;"><i class="fas fa-bullhorn fa-3x" style="color: #ccc;"></i></div>';

            const dates = [];
            if (announcement.start_date) dates.push(`Inicio: ${formatDate(announcement.start_date)}`);
            if (announcement.end_date) dates.push(`Fin: ${formatDate(announcement.end_date)}`);
            const datesHtml = dates.length > 0 ? `<small style="display: block; margin-top: 5px; color: #666;">${dates.join(' | ')}</small>` : '';

            return `
                <div class="product-card">
                    <div class="product-image">
                        ${imageHtml}
                    </div>
                    <div class="product-info">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            ${typeBadge}
                            ${statusBadge}
                        </div>
                        <h3>${announcement.title}</h3>
                        <p style="font-size: 0.85rem; color: #666; margin: 8px 0;">${truncate(announcement.message, 100)}</p>
                        ${datesHtml}
                        <div class="product-stats" style="margin-top: 10px;">
                            <span><i class="fas fa-sort-amount-up"></i> Prioridad: ${announcement.priority}</span>
                            <span><i class="far fa-calendar"></i> ${formatDate(announcement.created_at)}</span>
                        </div>
                        <div class="product-actions" style="margin-top: 15px; display: flex; gap: 8px;">
                            <a href="<?= BASE_URL ?>/admin/announcements/edit.php?id=${announcement.id}" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <button onclick="confirmDelete(${announcement.id})" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Actualizar paginación
    function updatePagination(current, total) {
        if (total <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        let html = '<div class="pagination-controls">';
        
        if (current > 1) {
            html += `<button onclick="loadAnnouncements(${current - 1})" class="btn btn-secondary"><i class="fas fa-chevron-left"></i></button>`;
        }

        for (let i = 1; i <= total; i++) {
            if (i === 1 || i === total || (i >= current - 2 && i <= current + 2)) {
                html += `<button onclick="loadAnnouncements(${i})" class="btn ${i === current ? 'btn-primary' : 'btn-secondary'}">${i}</button>`;
            } else if (i === current - 3 || i === current + 3) {
                html += '<span>...</span>';
            }
        }

        if (current < total) {
            html += `<button onclick="loadAnnouncements(${current + 1})" class="btn btn-secondary"><i class="fas fa-chevron-right"></i></button>`;
        }

        html += '</div>';
        paginationContainer.innerHTML = html;
    }

    // Actualizar contador de resultados
    function updateResultsCount(total) {
        resultsCount.textContent = `${total} anuncio${total !== 1 ? 's' : ''} encontrado${total !== 1 ? 's' : ''}`;
    }

    // Confirmar eliminación
    window.confirmDelete = function(id) {
        deleteAnnouncementId = id;
        deleteModal.classList.add('active');
    };

    // Eliminar anuncio
    confirmDeleteBtn.addEventListener('click', function() {
        if (!deleteAnnouncementId) return;

        const formData = new FormData();
        formData.append('id', deleteAnnouncementId);

        fetch('<?= BASE_URL ?>/admin/announcements/delete.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Anuncio eliminado exitosamente', 'success');
                deleteModal.classList.remove('active');
                loadAnnouncements(currentPage);
            } else {
                showAlert(data.message || 'Error al eliminar', 'error');
            }
        })
        .catch(error => {
            showAlert('Error al eliminar el anuncio', 'error');
        });
    });

    // Cerrar modal
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => {
            deleteModal.classList.remove('active');
        });
    });

    // Formulario de búsqueda
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        currentFilters = {
            search: document.getElementById('search-input').value,
            type: document.getElementById('type-filter').value,
            status: document.getElementById('status-filter').value,
            order: document.getElementById('order-filter').value
        };
        loadAnnouncements(1);
    });

    // Funciones auxiliares
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function truncate(text, length) {
        if (!text) return '';
        return text.length > length ? text.substring(0, length) + '...' : text;
    }

    // Hacer función global para la paginación
    window.loadAnnouncements = loadAnnouncements;

    // Cargar anuncios inicialmente
    loadAnnouncements(1);
});
</script>
