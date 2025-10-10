/**
 * orderadmin.js - Gestión moderna de filtros y órdenes
 */

document.addEventListener('DOMContentLoaded', function() {
    // ===== TOGGLE DE FILTROS =====
    const toggleFiltersBtn = document.getElementById('toggle-filters');
    const advancedFilters = document.getElementById('advanced-filters');
    
    if (toggleFiltersBtn && advancedFilters) {
        const icon = toggleFiltersBtn.querySelector('i');
        
        toggleFiltersBtn.addEventListener('click', function() {
            // Verificar si está colapsado usando la clase del botón
            const isCollapsed = toggleFiltersBtn.classList.contains('collapsed');
            
            if (isCollapsed) {
                // Mostrar filtros
                advancedFilters.style.display = 'flex';
                toggleFiltersBtn.classList.remove('collapsed');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
                
                // Permitir que la animación funcione
                setTimeout(() => {
                    advancedFilters.style.opacity = '1';
                    advancedFilters.style.maxHeight = '1000px';
                }, 10);
            } else {
                // Ocultar filtros con animación
                advancedFilters.style.opacity = '0';
                advancedFilters.style.maxHeight = '0';
                toggleFiltersBtn.classList.add('collapsed');
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
                
                // Esperar a que termine la animación antes de ocultar
                setTimeout(() => {
                    advancedFilters.style.display = 'none';
                }, 400);
            }
        });
    }

    // ===== BOTÓN DE LIMPIAR BÚSQUEDA =====
    const searchInput = document.getElementById('search-input');
    const clearSearchBtn = document.getElementById('clear-search');
    
    if (searchInput && clearSearchBtn) {
        searchInput.addEventListener('input', function() {
            clearSearchBtn.style.display = this.value.length > 0 ? 'flex' : 'none';
        });
        
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            this.style.display = 'none';
            searchInput.focus();
        });
    }

    // ===== CONTADOR DE FILTROS ACTIVOS =====
    const filterForm = document.getElementById('search-orders-form');
    const activeFiltersCount = document.getElementById('active-filters-count');
    
    function updateActiveFiltersCount() {
        if (!filterForm || !activeFiltersCount) return;
        
        let count = 0;
        const formData = new FormData(filterForm);
        
        for (let [key, value] of formData.entries()) {
            if (value && value.trim() !== '') {
                count++;
            }
        }
        
        const countSpan = activeFiltersCount.querySelector('span');
        if (countSpan) {
            countSpan.textContent = `${count} ${count === 1 ? 'filtro activo' : 'filtros activos'}`;
        }
        
        // Añadir clase si hay filtros activos
        if (count > 0) {
            activeFiltersCount.classList.add('has-filters');
        } else {
            activeFiltersCount.classList.remove('has-filters');
        }
    }
    
    // Escuchar cambios en todos los campos del formulario
    if (filterForm) {
        const filterInputs = filterForm.querySelectorAll('input, select');
        filterInputs.forEach(input => {
            input.addEventListener('change', updateActiveFiltersCount);
            input.addEventListener('input', updateActiveFiltersCount);
        });
        
        // Actualizar al cargar la página
        updateActiveFiltersCount();
    }

    // ===== LIMPIAR TODOS LOS FILTROS =====
    const clearAllFiltersBtn = document.getElementById('clear-all-filters');
    
    if (clearAllFiltersBtn && filterForm) {
        clearAllFiltersBtn.addEventListener('click', function() {
            // Limpiar todos los inputs
            const inputs = filterForm.querySelectorAll('input[type="text"], input[type="date"]');
            inputs.forEach(input => input.value = '');
            
            // Resetear todos los selects
            const selects = filterForm.querySelectorAll('select');
            selects.forEach(select => select.selectedIndex = 0);
            
            // Ocultar botón de limpiar búsqueda
            if (clearSearchBtn) {
                clearSearchBtn.style.display = 'none';
            }
            
            // Actualizar contador
            updateActiveFiltersCount();
            
            // Opcional: enviar formulario para actualizar resultados
            // filterForm.submit();
        });
    }

    // ===== ANIMACIÓN DE ENFOQUE EN INPUTS =====
    const allInputs = document.querySelectorAll('.search-input, .filter-select, .filter-input');
    
    allInputs.forEach(input => {
        input.addEventListener('focus', function() {
            // Removido el efecto de clase focused para evitar el borde azul
        });
        
        input.addEventListener('blur', function() {
            // Removido el efecto de clase focused para evitar el borde azul
        });
    });

    // ===== EFECTO RIPPLE EN BOTONES =====
    function createRipple(event) {
        const button = event.currentTarget;
        const ripple = document.createElement('span');
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple-effect');
        
        button.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    }
    
    const rippleButtons = document.querySelectorAll('.search-submit-btn, .btn-apply-filters, .btn-clear-filters');
    rippleButtons.forEach(button => {
        button.addEventListener('click', createRipple);
    });

    // ===== VALIDACIÓN DE FECHAS =====
    const fromDateInput = document.getElementById('from-date');
    const toDateInput = document.getElementById('to-date');
    
    if (fromDateInput && toDateInput) {
        fromDateInput.addEventListener('change', function() {
            if (toDateInput.value && this.value > toDateInput.value) {
                toDateInput.value = this.value;
                showNotification('La fecha "desde" no puede ser posterior a la fecha "hasta"', 'warning');
            }
        });
        
        toDateInput.addEventListener('change', function() {
            if (fromDateInput.value && this.value < fromDateInput.value) {
                fromDateInput.value = this.value;
                showNotification('La fecha "hasta" no puede ser anterior a la fecha "desde"', 'warning');
            }
        });
    }

    // ===== FUNCIÓN PARA MOSTRAR NOTIFICACIONES =====
    function showNotification(message, type = 'info') {
        // Si existe la función showAlert global, usarla
        if (typeof showAlert === 'function') {
            showAlert(message, type);
        } else {
            // Crear notificación simple
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                background: ${type === 'warning' ? '#fbbf24' : '#3b82f6'};
                color: white;
                border-radius: 10px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                z-index: 10000;
                animation: slideInRight 0.3s ease-out;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    }

    // ===== PREVENIR ENVÍO DE FORMULARIO VACÍO =====
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            const formData = new FormData(this);
            let hasValue = false;
            
            for (let [key, value] of formData.entries()) {
                if (value && value.trim() !== '') {
                    hasValue = true;
                    break;
                }
            }
            
            if (!hasValue) {
                e.preventDefault();
                showNotification('Por favor, ingrese al menos un criterio de búsqueda', 'warning');
            }
        });
    }

    // ===== AUTOCOMPLETADO Y SUGERENCIAS (opcional) =====
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            const query = this.value.trim();
            
            if (query.length >= 3) {
                searchTimeout = setTimeout(() => {
                    // Aquí puedes implementar AJAX para buscar sugerencias
                    console.log('Buscando:', query);
                    // fetchSearchSuggestions(query);
                }, 500);
            }
        });
    }

    console.log('✓ Sistema de filtros de órdenes inicializado correctamente');
});

// ===== ANIMACIONES CSS ADICIONALES =====
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .active-filters.has-filters {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        animation: pulse 2s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.02);
        }
    }
    
    .ripple-effect {
        position: absolute;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.6);
        transform: scale(0);
        animation: ripple-animation 0.6s linear;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
