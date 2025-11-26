<!-- Modal de confirmación para cambiar estado -->
<div class="delete-confirm" id="status-confirm-modal" style="display: none;">
    <div class="delete-confirm-content">
        <div class="delete-confirm-header">
            <h3 id="status-modal-title">Cambiar estado del código</h3>
            <button class="delete-confirm-close" id="status-modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="delete-confirm-body">
            <p id="status-modal-message">¿Estás seguro de que deseas cambiar el estado de este código?</p>
        </div>
        <div class="delete-confirm-footer">
            <button class="btn btn-secondary" id="status-modal-cancel">Cancelar</button>
            <button class="btn btn-primary" id="status-modal-confirm">Confirmar</button>
        </div>
    </div>
</div>

<script>
// Funcionalidad para copiar códigos
document.addEventListener('DOMContentLoaded', function() {
    // Copiar código al portapapeles
    const copyButtons = document.querySelectorAll('.btn-copy');
    copyButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const code = this.getAttribute('data-code');
            navigator.clipboard.writeText(code).then(() => {
                // Mostrar feedback visual
                const originalIcon = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i>';
                this.style.backgroundColor = 'rgba(22, 163, 74, 0.2)';
                this.style.color = 'var(--success-color)';
                
                setTimeout(() => {
                    this.innerHTML = originalIcon;
                    this.style.backgroundColor = '';
                    this.style.color = '';
                }, 2000);
                
                showAlert('Código copiado al portapapeles', 'success');
            }).catch(err => {
                console.error('Error al copiar: ', err);
                showAlert('Error al copiar el código', 'error');
            });
        });
    });
    
    // Funcionalidad para cambiar estado
    const statusButtons = document.querySelectorAll('.btn-status');
    const statusModal = document.getElementById('status-confirm-modal');
    const statusModalTitle = document.getElementById('status-modal-title');
    const statusModalMessage = document.getElementById('status-modal-message');
    const statusModalConfirm = document.getElementById('status-modal-confirm');
    const statusModalCancel = document.getElementById('status-modal-cancel');
    const statusModalClose = document.getElementById('status-modal-close');
    
    let currentStatusButton = null;
    
    statusButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            currentStatusButton = this;
            const codeId = this.getAttribute('data-id');
            const isActive = this.getAttribute('data-active') === '1';
            
            statusModalTitle.textContent = isActive ? 'Desactivar código' : 'Activar código';
            statusModalMessage.textContent = isActive ? 
                '¿Estás seguro de que deseas desactivar este código? Los usuarios ya no podrán utilizarlo.' :
                '¿Estás seguro de que deseas activar este código? Los usuarios podrán utilizarlo nuevamente.';
            
            // Mostrar modal
            statusModal.style.display = 'flex';
            setTimeout(() => {
                statusModal.classList.add('active');
            }, 10);
        });
    });
    
    // Cerrar modal
    function closeStatusModal() {
        statusModal.classList.remove('active');
        setTimeout(() => {
            statusModal.style.display = 'none';
        }, 300);
        currentStatusButton = null;
    }
    
    statusModalClose.addEventListener('click', closeStatusModal);
    statusModalCancel.addEventListener('click', closeStatusModal);
    
    // Confirmar cambio de estado
    statusModalConfirm.addEventListener('click', function() {
        if (currentStatusButton) {
            const codeId = currentStatusButton.getAttribute('data-id');
            
            // Realizar petición para cambiar estado
            window.location.href = `generate_codes.php?action=toggle_status&id=${codeId}`;
        }
    });
    
    // Cerrar modal al hacer clic fuera
    statusModal.addEventListener('click', function(e) {
        if (e.target === statusModal) {
            closeStatusModal();
        }
    });
    
    // Funcionalidad de búsqueda
    const searchInput = document.getElementById('search-codes');
    const tableRows = document.querySelectorAll('.data-table tbody tr');
    const noResults = document.getElementById('no-results');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        let hasResults = false;
        
        tableRows.forEach(row => {
            const searchableText = row.getAttribute('data-searchable');
            if (searchableText.includes(searchTerm)) {
                row.style.display = '';
                hasResults = true;
            } else {
                row.style.display = 'none';
            }
        });
        
        noResults.style.display = hasResults ? 'none' : 'flex';
    });
});
</script>

<style>
/* Estilos adicionales para mejorar la tabla */
.usage-info {
    display: flex;
    align-items: center;
    gap: 4px;
}

.usage-count {
    font-weight: 600;
    color: var(--primary-color);
}

.usage-max {
    font-weight: 500;
}

.usage-infinite {
    font-weight: 600;
    color: var(--success-color);
}

.usage-separator {
    color: var(--text-light);
}

.text-muted {
    color: var(--text-light) !important;
}

.data-table td {
    vertical-align: middle;
}

.badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    font-size: 1.2rem;
    font-weight: 600;
    border-radius: 6px;
}

.badge-info {
    background-color: var(--info-light);
    color: var(--info-color);
}

/* Estilos para el modal de confirmación */
.delete-confirm {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: var(--transition-slow);
    backdrop-filter: blur(4px);
}

.delete-confirm.active {
    opacity: 1;
    visibility: visible;
}

.delete-confirm-content {
    background-color: white;
    border-radius: var(--radius-xl);
    padding: var(--space-xl);
    max-width: 500px;
    width: 90%;
    transform: translateY(-20px) scale(0.95);
    transition: var(--transition-slow);
    box-shadow: var(--shadow-xl);
    border: 1px solid var(--border-color);
}

.delete-confirm.active .delete-confirm-content {
    transform: translateY(0) scale(1);
}

.delete-confirm-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-md);
    padding-bottom: var(--space-sm);
    border-bottom: 1px solid var(--border-color);
}

.delete-confirm-header h3 {
    margin: 0;
    color: var(--text-dark);
    font-size: 1.8rem;
}

.delete-confirm-close {
    background: none;
    border: none;
    font-size: 2rem;
    color: var(--text-light);
    cursor: pointer;
    transition: var(--transition);
    width: 3rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-sm);
}

.delete-confirm-close:hover {
    background-color: var(--bg-hover);
    color: var(--error-color);
}

.delete-confirm-body {
    margin-bottom: var(--space-lg);
}

.delete-confirm-body p {
    margin: 0;
    color: var(--text-medium);
    font-size: 1.4rem;
    line-height: 1.5;
}

.delete-confirm-footer {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-md);
}
</style>