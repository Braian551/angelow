/**
 * Filtros Avanzados - Componente Reutilizable
 * Maneja la UI y eventos de filtros en páginas de admin
 */

class FilterManager {
    constructor(formId, options = {}) {
        this.formId = formId;
        this.form = document.getElementById(formId);
        this.options = {
            onFilterChange: options.onFilterChange || (() => {}),
            onClearFilters: options.onClearFilters || (() => {}),
            countOrderAsFilter: options.countOrderAsFilter || false
        };
        
        this.elements = {
            toggleBtn: document.getElementById('toggle-filters'),
            advancedFilters: document.getElementById('advanced-filters'),
            searchInput: document.getElementById('search-input'),
            clearSearchBtn: document.getElementById('clear-search'),
            activeFiltersCount: document.getElementById('active-filters-count'),
            clearAllBtn: document.getElementById('clear-all-filters'),
            applyBtn: document.querySelector('.btn-apply-filters')
        };
        
        this.init();
    }
    
    init() {
        this.setupToggleFilters();
        this.setupSearchClear();
        this.setupFilterCounter();
        this.setupClearAll();
        this.setupApplyFilters();
    }
    
    setupToggleFilters() {
        if (!this.elements.toggleBtn || !this.elements.advancedFilters) return;
        
        const icon = this.elements.toggleBtn.querySelector('i');
        
        this.elements.toggleBtn.addEventListener('click', () => {
            const isCollapsed = this.elements.toggleBtn.classList.contains('collapsed');
            
            if (isCollapsed) {
                this.elements.advancedFilters.style.display = 'flex';
                this.elements.toggleBtn.classList.remove('collapsed');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
                setTimeout(() => {
                    this.elements.advancedFilters.style.opacity = '1';
                    this.elements.advancedFilters.style.maxHeight = '1000px';
                }, 10);
            } else {
                this.elements.advancedFilters.style.opacity = '0';
                this.elements.advancedFilters.style.maxHeight = '0';
                this.elements.toggleBtn.classList.add('collapsed');
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
                setTimeout(() => {
                    this.elements.advancedFilters.style.display = 'none';
                }, 400);
            }
        });
    }
    
    setupSearchClear() {
        if (!this.elements.searchInput || !this.elements.clearSearchBtn) return;
        
        this.elements.searchInput.addEventListener('input', () => {
            this.elements.clearSearchBtn.style.display = 
                this.elements.searchInput.value.length > 0 ? 'flex' : 'none';
        });
        
        this.elements.clearSearchBtn.addEventListener('click', () => {
            this.elements.searchInput.value = '';
            this.elements.clearSearchBtn.style.display = 'none';
            this.elements.searchInput.focus();
            this.options.onFilterChange();
        });
    }
    
    setupFilterCounter() {
        if (!this.form || !this.elements.activeFiltersCount) return;
        
        const updateCount = () => {
            let count = 0;
            const formData = new FormData(this.form);
            
            for (let [key, value] of formData.entries()) {
                // Excluir search y opcionalmente order del conteo
                const shouldCount = value && value.trim() !== '' && 
                                  key !== 'search' && 
                                  (this.options.countOrderAsFilter || key !== 'order');
                if (shouldCount) {
                    count++;
                }
            }
            
            const countSpan = this.elements.activeFiltersCount.querySelector('span');
            if (countSpan) {
                countSpan.textContent = `${count} ${count === 1 ? 'filtro activo' : 'filtros activos'}`;
            }
            
            this.elements.activeFiltersCount.classList.toggle('has-filters', count > 0);
        };
        
        const inputs = this.form.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('change', updateCount);
            input.addEventListener('input', updateCount);
        });
        
        updateCount();
    }
    
    setupClearAll() {
        if (!this.elements.clearAllBtn || !this.form) return;
        
        this.elements.clearAllBtn.addEventListener('click', () => {
            this.form.reset();
            if (this.elements.clearSearchBtn) {
                this.elements.clearSearchBtn.style.display = 'none';
            }
            this.updateFilterCounter();
            this.options.onClearFilters();
        });
    }
    
    setupApplyFilters() {
        if (!this.elements.applyBtn || !this.form) return;
        
        this.elements.applyBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.options.onFilterChange();
        });
    }
    
    updateFilterCounter() {
        if (!this.form || !this.elements.activeFiltersCount) return;
        
        let count = 0;
        const formData = new FormData(this.form);
        
        for (let [key, value] of formData.entries()) {
            const shouldCount = value && value.trim() !== '' && 
                              key !== 'search' && 
                              (this.options.countOrderAsFilter || key !== 'order');
            if (shouldCount) {
                count++;
            }
        }
        
        const countSpan = this.elements.activeFiltersCount.querySelector('span');
        if (countSpan) {
            countSpan.textContent = `${count} ${count === 1 ? 'filtro activo' : 'filtros activos'}`;
        }
        
        this.elements.activeFiltersCount.classList.toggle('has-filters', count > 0);
    }
    
    getFilters() {
        if (!this.form) return {};
        
        const filters = {};
        const formData = new FormData(this.form);
        
        for (let [key, value] of formData.entries()) {
            if (value && value.trim() !== '') {
                filters[key] = value;
            }
        }
        
        return filters;
    }
    
    setFilters(filters) {
        if (!this.form) return;
        
        Object.keys(filters).forEach(key => {
            const input = this.form.querySelector(`[name="${key}"]`);
            if (input) {
                input.value = filters[key];
            }
        });
        
        this.updateFilterCounter();
    }
}

// Exportar para uso global
window.FilterManager = FilterManager;
