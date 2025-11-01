// collections.js - Gestión de colecciones
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let deleteCollectionId = null;
    
    // Búsqueda en tiempo real
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                applyFilters();
            }, 500);
        });
    }
    
    // Filtro de estado
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', applyFilters);
    }
    
    // Toggle de estado de colecciones
    const toggles = document.querySelectorAll('.collection-toggle');
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const collectionId = this.dataset.id;
            const isActive = this.checked;
            toggleCollectionStatus(collectionId, isActive, this);
        });
    });
    
    // Modal de eliminación
    window.deleteCollection = function(id, name) {
        deleteCollectionId = id;
        document.getElementById('collectionName').textContent = name;
        document.getElementById('deleteModal').classList.add('show');
    };
    
    window.closeDeleteModal = function() {
        document.getElementById('deleteModal').classList.remove('show');
        deleteCollectionId = null;
    };
    
    window.confirmDelete = function() {
        if (deleteCollectionId) {
            performDelete(deleteCollectionId);
        }
    };
    
    // Cerrar modal al hacer clic fuera
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });
});

// Aplicar filtros
function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (status !== 'all') params.append('status', status);
    
    window.location.href = `collections_list.php?${params.toString()}`;
}

// Limpiar filtros
function clearFilters() {
    window.location.href = 'collections_list.php';
}

// Toggle del estado de la colección
function toggleCollectionStatus(collectionId, isActive, toggleElement) {
    const formData = new FormData();
    formData.append('id', collectionId);
    
    fetch('../../ajax/admin/collections/toggle_collection.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Estado actualizado exitosamente', 'success');
        } else {
            // Revertir el toggle si falla
            toggleElement.checked = !isActive;
            showToast(data.message || 'Error al actualizar el estado', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toggleElement.checked = !isActive;
        showToast('Error de conexión', 'error');
    });
}

// Eliminar colección
function performDelete(collectionId) {
    const formData = new FormData();
    formData.append('id', collectionId);
    
    fetch('../../ajax/admin/collections/delete_collection.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Colección eliminada exitosamente', 'success');
            closeDeleteModal();
            
            // Eliminar la fila de la tabla
            const row = document.querySelector(`tr[data-collection-id="${collectionId}"]`);
            if (row) {
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    
                    // Verificar si ya no hay más colecciones
                    const tbody = document.querySelector('.data-table tbody');
                    if (tbody.querySelectorAll('tr').length === 0) {
                        location.reload();
                    }
                }, 300);
            }
        } else {
            showToast(data.message || 'Error al eliminar la colección', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
    });
}

// Mostrar notificación toast
function showToast(message, type = 'info') {
    // Crear elemento toast si no existe
    let toast = document.getElementById('toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.className = 'toast';
        document.body.appendChild(toast);
    }
    
    // Configurar y mostrar
    toast.textContent = message;
    toast.className = `toast toast-${type} show`;
    
    // Ocultar después de 3 segundos
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}
