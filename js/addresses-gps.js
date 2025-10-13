/**
 * GPS Address Picker con Leaflet y Nominatim
 * Permite seleccionar ubicación GPS para direcciones
 */

class GPSAddressPicker {
    constructor() {
        this.map = null;
        this.marker = null;
        this.currentPosition = null;
        this.selectedPosition = null;
        this.isLocating = false;
        this.isSearching = false;
        this.searchTimeout = null;
        this.baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
        
        this.init();
    }
    
    init() {
        this.createModalHTML();
        this.attachEventListeners();
        console.log('GPS Address Picker initialized');
    }
    
    createModalHTML() {
        const modalHTML = `
            <!-- GPS Modal Overlay -->
            <div class="gps-modal-overlay" id="gps-modal-overlay"></div>
            
            <!-- GPS Modal -->
            <div class="gps-modal" id="gps-modal">
                <div class="gps-modal-header">
                    <div class="gps-header-top">
                        <h3 class="gps-modal-title">
                            <i class="fas fa-map-marked-alt"></i>
                            Selecciona tu ubicación
                        </h3>
                        <button class="btn-close-gps" id="btn-close-gps">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Buscador de direcciones -->
                    <div class="address-search-container">
                        <div class="address-search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input 
                                type="text" 
                                id="address-search-input" 
                                class="address-search-input" 
                                placeholder="Buscar dirección, lugar o barrio..."
                                autocomplete="off"
                            >
                            <div class="search-results" id="search-results"></div>
                        </div>
                        <button class="btn-search" id="btn-search" disabled>
                            <i class="fas fa-search"></i>
                            Buscar
                        </button>
                    </div>
                </div>
                
                <div class="gps-map-container">
                    <div id="gps-map"></div>
                    
                    <!-- Loading Overlay -->
                    <div class="map-loading" id="map-loading">
                        <div class="loading-spinner"></div>
                        <p class="loading-text">Obteniendo tu ubicación...</p>
                    </div>
                    
                    <!-- Instructions -->
                    <div class="map-instructions" id="map-instructions">
                        <div class="instruction-content">
                            <i class="fas fa-hand-pointer"></i>
                            <span>Arrastra el marcador o toca el mapa para seleccionar tu dirección</span>
                            <span class="instruction-close">(Click para ocultar)</span>
                        </div>
                    </div>
                    
                    <!-- Fullscreen Button -->
                    <button class="btn-fullscreen" id="btn-fullscreen" title="Pantalla completa">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
                
                <!-- Floating Actions Container - Fuera del mapa para mejor posicionamiento -->
                <div class="floating-actions">
                    <button class="btn-current-location" id="btn-current-location" title="Ir a mi ubicación actual">
                        <i class="fas fa-crosshairs"></i>
                    </button>
                    
                    <button class="btn-toggle-panel" id="btn-toggle-panel" title="Ocultar información">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                
                <div class="address-info-panel" id="address-info-panel">
                    <div class="address-info-content">
                        <div class="address-preview">
                            <div class="address-preview-label">
                                <i class="fas fa-map-marker-alt"></i>
                                Dirección seleccionada
                            </div>
                            <div class="address-preview-text loading" id="address-preview">
                                Mueve el marcador para ver la dirección...
                            </div>
                        </div>
                        
                        <div class="coordinates-display">
                            <div class="coord-item">
                                <span class="coord-label">Latitud</span>
                                <span class="coord-value" id="coord-lat">--</span>
                            </div>
                            <div class="coord-item">
                                <span class="coord-label">Longitud</span>
                                <span class="coord-value" id="coord-lng">--</span>
                            </div>
                        </div>
                    </div>
                </div>                <div class="gps-modal-actions">
                    <button class="btn-gps-action btn-cancel-gps" id="btn-cancel-gps">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button class="btn-gps-action btn-confirm-location" id="btn-confirm-location" disabled>
                        <i class="fas fa-check-circle"></i>
                        Confirmar ubicación
                    </button>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
    
    attachEventListeners() {
        // Botón para abrir el modal
        const btnOpenGPS = document.getElementById('btn-open-gps');
        if (btnOpenGPS) {
            btnOpenGPS.addEventListener('click', () => this.openModal());
        }
        
        // Botones de cerrar
        document.getElementById('btn-close-gps').addEventListener('click', () => this.closeModal());
        document.getElementById('btn-cancel-gps').addEventListener('click', () => this.closeModal());
        document.getElementById('gps-modal-overlay').addEventListener('click', () => this.closeModal());
        
        // Botón de confirmar
        document.getElementById('btn-confirm-location').addEventListener('click', () => this.confirmLocation());
        
        // Botón de ubicación actual
        document.getElementById('btn-current-location').addEventListener('click', () => this.getCurrentLocation());
        
        // Botón de pantalla completa
        document.getElementById('btn-fullscreen').addEventListener('click', () => this.toggleFullscreen());
        
        // Botón toggle panel
        document.getElementById('btn-toggle-panel').addEventListener('click', () => this.togglePanel());
        
        // Buscador de direcciones
        const searchInput = document.getElementById('address-search-input');
        const btnSearch = document.getElementById('btn-search');
        
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            btnSearch.disabled = query.length < 3;
            
            // Búsqueda en tiempo real
            clearTimeout(this.searchTimeout);
            if (query.length >= 3) {
                this.searchTimeout = setTimeout(() => {
                    this.searchAddress(query);
                }, 500);
            } else {
                this.hideSearchResults();
            }
        });
        
        btnSearch.addEventListener('click', () => {
            const query = searchInput.value.trim();
            if (query.length >= 3) {
                this.searchAddress(query);
            }
        });
        
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = searchInput.value.trim();
                if (query.length >= 3) {
                    this.searchAddress(query);
                }
            }
        });
        
        // Cerrar resultados al hacer click fuera
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.address-search-wrapper')) {
                this.hideSearchResults();
            }
        });
        
        // Ocultar instrucciones al hacer click
        const instructions = document.getElementById('map-instructions');
        if (instructions) {
            instructions.addEventListener('click', () => {
                instructions.classList.add('hidden');
            });
            
            // Auto-ocultar después de 8 segundos
            setTimeout(() => {
                instructions.classList.add('hidden');
            }, 8000);
        }
        
        // Tecla ESC para cerrar
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && document.getElementById('gps-modal').classList.contains('active')) {
                this.closeModal();
            }
        });
    }
    
    togglePanel() {
        const panel = document.getElementById('address-info-panel');
        const btn = document.getElementById('btn-toggle-panel');
        const icon = btn.querySelector('i');
        
        panel.classList.toggle('collapsed');
        
        if (panel.classList.contains('collapsed')) {
            icon.className = 'fas fa-chevron-up';
            btn.title = 'Mostrar información';
        } else {
            icon.className = 'fas fa-chevron-down';
            btn.title = 'Ocultar información';
        }
        
        // Ajustar mapa después de la animación
        setTimeout(() => {
            if (this.map) {
                this.map.invalidateSize();
            }
        }, 450);
    }
    
    async searchAddress(query) {
        if (this.isSearching) return;
        
        this.isSearching = true;
        const resultsContainer = document.getElementById('search-results');
        
        // Mostrar loading
        resultsContainer.innerHTML = `
            <div class="search-loading">
                <i class="fas fa-spinner"></i>
                <span>Buscando...</span>
            </div>
        `;
        resultsContainer.classList.add('active');
        
        try {
            // Usar API proxy local para evitar problemas de CORS
            const url = `${this.baseUrl}/users/api/search_address.php?q=${encodeURIComponent(query)}&limit=5&t=${Date.now()}`;
            
            console.log('Buscando:', url);
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache'
                },
                cache: 'no-store'
            });
            
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                // Intentar leer el error del servidor
                const errorText = await response.text();
                console.error('Error del servidor:', errorText);
                throw new Error(`Error del servidor (${response.status}). Verifica los logs de PHP.`);
            }
            
            const result = await response.json();
            
            console.log('Respuesta:', result);
            
            if (!result.success) {
                throw new Error(result.error || 'Error desconocido');
            }
            
            const data = result.data || [];
            
            if (data.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="search-no-results">
                        <i class="fas fa-map-marker-slash"></i>
                        <p>No se encontraron resultados para "${query}"</p>
                        <p style="font-size: 1.1rem; margin-top: 0.5rem; color: #999;">Intenta con otro término de búsqueda</p>
                    </div>
                `;
            } else {
                resultsContainer.innerHTML = data.map(result => {
                    const name = result.name || result.display_name.split(',')[0];
                    const address = result.display_name;
                    return `
                        <div class="search-result-item" data-lat="${result.lat}" data-lng="${result.lon}">
                            <i class="fas fa-map-marker-alt search-result-icon"></i>
                            <div class="search-result-text">
                                <div class="search-result-name">${this.escapeHtml(name)}</div>
                                <div class="search-result-address">${this.escapeHtml(address)}</div>
                            </div>
                        </div>
                    `;
                }).join('');
                
                // Agregar eventos a los resultados
                resultsContainer.querySelectorAll('.search-result-item').forEach(item => {
                    item.addEventListener('click', () => {
                        const lat = parseFloat(item.dataset.lat);
                        const lng = parseFloat(item.dataset.lng);
                        this.goToLocation(lat, lng);
                        this.hideSearchResults();
                        document.getElementById('address-search-input').value = '';
                    });
                });
            }
        } catch (error) {
            console.error('Error en búsqueda:', error);
            resultsContainer.innerHTML = `
                <div class="search-no-results">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p style="font-weight: 600; color: #e53935;">Error al buscar</p>
                    <p style="font-size: 1.2rem; margin-top: 0.5rem;">${this.escapeHtml(error.message)}</p>
                    <p style="font-size: 1.1rem; margin-top: 0.5rem; color: #999;">
                        Abre la consola (F12) para más detalles
                    </p>
                </div>
            `;
        } finally {
            this.isSearching = false;
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    hideSearchResults() {
        const resultsContainer = document.getElementById('search-results');
        resultsContainer.classList.remove('active');
    }
    
    goToLocation(lat, lng) {
        if (!this.map || !this.marker) return;
        
        console.log('Navegando a:', lat, lng);
        
        // Mover el mapa y el marcador
        this.map.setView([lat, lng], 17, {
            animate: true,
            duration: 1
        });
        
        this.marker.setLatLng([lat, lng]);
        
        // Actualizar la dirección
        this.updatePosition(lat, lng);
    }
    
    toggleFullscreen() {
        const modal = document.getElementById('gps-modal');
        const btn = document.getElementById('btn-fullscreen');
        const icon = btn.querySelector('i');
        
        modal.classList.toggle('fullscreen');
        
        if (modal.classList.contains('fullscreen')) {
            icon.className = 'fas fa-compress';
            btn.title = 'Salir de pantalla completa';
        } else {
            icon.className = 'fas fa-expand';
            btn.title = 'Pantalla completa';
        }
        
        // Forzar redimensionamiento del mapa
        setTimeout(() => {
            if (this.map) {
                this.map.invalidateSize();
            }
        }, 300);
    }
    
    openModal() {
        document.getElementById('gps-modal-overlay').classList.add('active');
        document.getElementById('gps-modal').classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Asegurarse de que el panel esté visible al abrir
        const panel = document.getElementById('address-info-panel');
        const btn = document.getElementById('btn-toggle-panel');
        const icon = btn.querySelector('i');
        
        panel.classList.remove('collapsed');
        icon.className = 'fas fa-chevron-down';
        btn.title = 'Ocultar información';
        
        // Inicializar el mapa si no existe
        if (!this.map) {
            this.initMap();
        }
        
        // Obtener ubicación actual
        this.getCurrentLocation();
    }
    
    closeModal() {
        document.getElementById('gps-modal-overlay').classList.remove('active');
        document.getElementById('gps-modal').classList.remove('active');
        document.body.style.overflow = '';
    }
    
    initMap() {
        console.log('Inicializando mapa...');
        
        // Coordenadas por defecto (Medellín, Colombia)
        const defaultLat = 6.2442;
        const defaultLng = -75.5812;
        
        // Crear el mapa
        this.map = L.map('gps-map', {
            center: [defaultLat, defaultLng],
            zoom: 13,
            zoomControl: true,
            attributionControl: true
        });
        
        // Agregar capa de tiles (OpenStreetMap)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(this.map);
        
        // Crear ícono personalizado para el marcador
        const customIcon = L.divIcon({
            className: 'custom-marker-icon',
            html: `
                <div class="marker-pin">
                    <i class="fas fa-map-marker-alt"></i>
                    <div class="marker-shadow"></div>
                </div>
            `,
            iconSize: [40, 40],
            iconAnchor: [20, 40]
        });
        
        // Crear marcador arrastrable
        this.marker = L.marker([defaultLat, defaultLng], {
            icon: customIcon,
            draggable: true
        }).addTo(this.map);
        
        // Eventos del marcador
        this.marker.on('dragend', () => {
            const position = this.marker.getLatLng();
            this.updatePosition(position.lat, position.lng);
        });
        
        // Click en el mapa para mover el marcador
        this.map.on('click', (e) => {
            this.marker.setLatLng(e.latlng);
            this.updatePosition(e.latlng.lat, e.latlng.lng);
        });
        
        console.log('Mapa inicializado correctamente');
    }
    
    getCurrentLocation() {
        if (this.isLocating) return;
        
        if (!navigator.geolocation) {
            this.showNotification('Tu navegador no soporta geolocalización', 'error');
            document.getElementById('map-loading').classList.add('hidden');
            return;
        }
        
        this.isLocating = true;
        document.getElementById('map-loading').classList.remove('hidden');
        document.querySelector('.loading-text').textContent = 'Obteniendo tu ubicación...';
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                console.log('Ubicación obtenida:', lat, lng);
                
                this.currentPosition = { lat, lng };
                
                // Mover el mapa y el marcador
                this.map.setView([lat, lng], 16);
                this.marker.setLatLng([lat, lng]);
                
                // Actualizar la dirección
                this.updatePosition(lat, lng);
                
                document.getElementById('map-loading').classList.add('hidden');
                this.isLocating = false;
                
                this.showNotification('Ubicación obtenida correctamente', 'success');
            },
            (error) => {
                console.error('Error al obtener ubicación:', error);
                document.getElementById('map-loading').classList.add('hidden');
                this.isLocating = false;
                
                let errorMessage = 'No se pudo obtener tu ubicación';
                
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = 'Permiso de ubicación denegado. Por favor, habilita los permisos de ubicación.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = 'Ubicación no disponible. Verifica tu conexión GPS.';
                        break;
                    case error.TIMEOUT:
                        errorMessage = 'Tiempo de espera agotado. Intenta nuevamente.';
                        break;
                }
                
                this.showNotification(errorMessage, 'error');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }
    
    async updatePosition(lat, lng) {
        console.log('Actualizando posición:', lat, lng);
        
        this.selectedPosition = { lat, lng };
        
        // Actualizar coordenadas en la UI
        document.getElementById('coord-lat').textContent = lat.toFixed(6);
        document.getElementById('coord-lng').textContent = lng.toFixed(6);
        
        // Obtener dirección (geocodificación inversa)
        const addressPreview = document.getElementById('address-preview');
        addressPreview.textContent = 'Obteniendo dirección...';
        addressPreview.classList.add('loading');
        
        document.getElementById('btn-confirm-location').disabled = true;
        
        try {
            const response = await fetch(
                `${this.baseUrl}/users/api/geocoding.php?lat=${lat}&lng=${lng}`
            );
            
            const data = await response.json();
            
            if (data.success && data.data) {
                const addressData = data.data;
                
                // Mostrar dirección formateada
                addressPreview.textContent = addressData.display_name;
                addressPreview.classList.remove('loading');
                
                // Guardar datos de la dirección para uso posterior
                this.selectedPosition.addressData = addressData;
                
                // Habilitar botón de confirmar
                document.getElementById('btn-confirm-location').disabled = false;
                
                console.log('Dirección obtenida:', addressData);
            } else {
                throw new Error(data.error || 'No se pudo obtener la dirección');
            }
        } catch (error) {
            console.error('Error al obtener dirección:', error);
            addressPreview.textContent = 'No se pudo obtener la dirección. Puedes confirmar e ingresarla manualmente.';
            addressPreview.classList.remove('loading');
            
            // Permitir confirmar incluso sin dirección
            document.getElementById('btn-confirm-location').disabled = false;
            
            this.showNotification('Error al obtener la dirección. Puedes ingresarla manualmente.', 'error');
        }
    }
    
    confirmLocation() {
        if (!this.selectedPosition) {
            this.showNotification('Por favor, selecciona una ubicación', 'error');
            return;
        }
        
        const { lat, lng, addressData } = this.selectedPosition;
        
        console.log('Ubicación confirmada:', { lat, lng, addressData });
        
        // Llenar los campos del formulario
        if (addressData) {
            // Dirección principal
            const addressField = document.getElementById('address');
            if (addressField) {
                addressField.value = addressData.formatted_address || addressData.display_name;
                addressField.dispatchEvent(new Event('input'));
            }
            
            // Barrio
            const neighborhoodField = document.getElementById('neighborhood');
            if (neighborhoodField) {
                neighborhoodField.value = addressData.neighborhood || '';
                neighborhoodField.dispatchEvent(new Event('input'));
            }
            
            // Complemento (si hay info adicional)
            const complementField = document.getElementById('complement');
            if (complementField && addressData.raw_address) {
                const addr = addressData.raw_address;
                let complement = '';
                
                if (addr.house_number) complement += `# ${addr.house_number} `;
                if (addr.building) complement += addr.building;
                
                if (complement.trim()) {
                    complementField.value = complement.trim();
                    complementField.dispatchEvent(new Event('input'));
                }
            }
        }
        
        // Guardar coordenadas en campos ocultos (crearlos si no existen)
        this.setHiddenField('gps_latitude', lat);
        this.setHiddenField('gps_longitude', lng);
        // Marcar explícitamente que se usó GPS
        this.setHiddenField('gps_used', '1');
        
        console.log('GPS usado establecido: gps_used=1, lat=' + lat + ', lng=' + lng);
        
        // Notificar éxito
        this.showNotification('Ubicación GPS seleccionada correctamente', 'success');
        
        // Cerrar modal
        setTimeout(() => {
            this.closeModal();
        }, 500);
    }
    
    setHiddenField(name, value) {
        let field = document.querySelector(`input[name="${name}"]`);
        
        if (!field) {
            field = document.createElement('input');
            field.type = 'hidden';
            field.name = name;
            document.querySelector('.address-form').appendChild(field);
        }
        
        field.value = value;
    }
    
    showNotification(message, type = 'success') {
        // Remover notificaciones anteriores
        const existingNotifications = document.querySelectorAll('.gps-notification');
        existingNotifications.forEach(n => n.remove());
        
        const notification = document.createElement('div');
        notification.className = `gps-notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span class="notification-message">${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remover después de 4 segundos
        setTimeout(() => {
            notification.style.animation = 'slideInRight 0.3s ease reverse';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    // Solo inicializar si estamos en la página de direcciones
    if (document.getElementById('btn-open-gps')) {
        window.gpsAddressPicker = new GPSAddressPicker();
        console.log('GPS Address Picker ready');
    }
});
