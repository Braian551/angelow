/**
 * =========================================================
 * SISTEMA DE NavegaciÃ³n EN TIEMPO REAL - ANGELOW DELIVERY
 * Funcionalidad: NavegaciÃ³n GPS estilo Uber/Waze con tracking en tiempo real
 * =========================================================
 */

(function() {
    'use strict';

    // =========================================================
    // CONFIGURACIN GLOBAL
    // =========================================================
    const CONFIG = {
        BASE_URL: document.querySelector('meta[name="base-url"]')?.content || '',
        UPDATE_INTERVAL: 5000, // Actualizar ubicaciÃ³n cada 5 segundos
        ROUTE_CHECK_INTERVAL: 30000, // Verificar ruta cada 30 segundos
        NEAR_DESTINATION_THRESHOLD: 0.1, // 100 metros
        OFF_ROUTE_THRESHOLD: 0.05, // 50 metros fuera de ruta
        SPEECH_ENABLED: true,
        MAP_ZOOM: 17,
        MAP_MIN_ZOOM: 10,
        MAP_MAX_ZOOM: 19
    };

    // =========================================================
    // ESTADO DE LA APLICACIN
    // =========================================================
    const state = {
        map: null,
        driverMarker: null,
        destinationMarker: null,
        routePolyline: null,
        trafficLayer: null,
        isTrafficVisible: false,
        currentLocation: null,
        destination: null,
        route: null,
        isNavigating: false,
        isPanelExpanded: false,
        isVoiceEnabled: true,
        voiceHelper: null, // Instancia de VoiceHelper
        watchId: null,
        updateInterval: null,
        routeCheckInterval: null,
        currentSpeed: 0,
        currentHeading: 0,
        distanceRemaining: 0,
        etaSeconds: 0,
        currentStepIndex: 0,
        deliveryData: null,
        batteryLevel: 100
    };

    // =========================================================
    // INICIALIZAR EVENTOS
    // =========================================================
    function initializeEvents() {
        console.log(' Inicializando eventos...');
        
        // Event listener para cerrar men al hacer clic en overlay
        const menuOverlay = document.getElementById('menu-overlay');
        if (menuOverlay) {
            menuOverlay.addEventListener('click', function() {
                window.toggleMenu();
            });
        }
        
        // Prevenir zoom con gestos en mviles
        document.addEventListener('gesturestart', function (e) {
            e.preventDefault();
        });
        
        // Mantener pantalla activa durante NavegaciÃ³n
        if ('wakeLock' in navigator) {
            navigator.wakeLock.request('screen').catch(err => {
                console.warn('Wake Lock no disponible:', err);
            });
        }
        
        console.log(' Eventos inicializados');
    }

    // =========================================================
    // INICIALIZACIN
    // =========================================================
    document.addEventListener('DOMContentLoaded', function() {
        console.log(' Iniciando sistema de NavegaciÃ³n...');
        
        // Inicializar Voice Helper
        if (typeof VoiceHelper !== 'undefined') {
            state.voiceHelper = new VoiceHelper();
            const engineInfo = state.voiceHelper.getEngineInfo();
            console.log(`🎙️ Motor de voz: ${engineInfo.name}`);
        } else {
            console.warn('⚠️ VoiceHelper no disponible');
        }
        
        // Cargar datos del delivery
        loadDeliveryData();
        
        // Solicitar permisos de ubicaciÃ³n
        requestLocationPermission();
        
        // Inicializar mapa
        initializeMap();
        
        // Inicializar eventos
        initializeEvents();
        
        // Obtener informaciÃ³n de batera si est disponible
        if ('getBattery' in navigator) {
            navigator.getBattery().then(battery => {
                state.batteryLevel = Math.round(battery.level * 100);
                battery.addEventListener('levelchange', () => {
                    state.batteryLevel = Math.round(battery.level * 100);
                });
            });
        }
        
        console.log(' Sistema de NavegaciÃ³n inicializado');
    });

    // =========================================================
    // CARGA DE DATOS DEL DELIVERY
    // =========================================================
    function loadDeliveryData() {
        const dataElement = document.getElementById('delivery-data');
        if (!dataElement) {
            showNotification('Error al cargar datos del pedido', 'error');
            return;
        }
        
        try {
            state.deliveryData = JSON.parse(dataElement.textContent);
            state.destination = {
                lat: state.deliveryData.destination.lat,
                lng: state.deliveryData.destination.lng
            };
            
            // Validar coordenadas de destino
            if (!state.destination.lat || !state.destination.lng || 
                state.destination.lat === 0 || state.destination.lng === 0) {
                console.error(' Coordenadas de destino no vlidas:', state.destination);
                showNotification('Error: La direcciÃ³n de entrega no tiene coordenadas GPS. Contacta al administrador.', 'error');
                
                // Deshabilitar NavegaciÃ³n
                const btnAction = document.getElementById('btn-action-main');
                if (btnAction) {
                    btnAction.disabled = true;
                    btnAction.style.opacity = '0.5';
                    btnAction.style.cursor = 'not-allowed';
                }
                
                updateStatus('Error: Sin coordenadas GPS');
                return;
            }
            
            console.log(' Datos del delivery cargados:', state.deliveryData);
            console.log(' Destino:', state.destination);
        } catch (e) {
            console.error('Error al parsear datos del delivery:', e);
            showNotification('Error al cargar informaciÃ³n del pedido', 'error');
        }
    }

    // =========================================================
    // SOLICITAR PERMISOS DE ubicaciÃ³n
    // =========================================================
    function requestLocationPermission() {
        if (!('geolocation' in navigator)) {
            showNotification('Tu dispositivo no soporta geolocalizacin', 'error');
            return;
        }

        updateStatus('Solicitando permisos de ubicaciÃ³n...');
        
        navigator.permissions.query({ name: 'geolocation' }).then(result => {
            if (result.state === 'granted') {
                console.log(' Permisos de ubicaciÃ³n concedidos');
                startLocationTracking();
            } else if (result.state === 'prompt') {
                // Solicitar permisos
                navigator.geolocation.getCurrentPosition(
                    position => {
                        console.log(' Permisos de ubicaciÃ³n concedidos');
                        startLocationTracking();
                    },
                    error => {
                        console.error(' Permisos de ubicaciÃ³n denegados:', error);
                        showNotification('Se requieren permisos de ubicaciÃ³n para navegar', 'error');
                    },
                    { enableHighAccuracy: true }
                );
            } else {
                showNotification('Se requieren permisos de ubicaciÃ³n para navegar', 'error');
            }
        });
    }

    // =========================================================
    // INICIALIZAR MAPA
    // =========================================================
    function initializeMap() {
        // Crear mapa centrado en Colombia por defecto
        state.map = L.map('map', {
            center: [4.6097, -74.0817],
            zoom: 13,
            zoomControl: true,
            minZoom: CONFIG.MAP_MIN_ZOOM,
            maxZoom: CONFIG.MAP_MAX_ZOOM
        });

        // Capa de mapa (OpenStreetMap)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: ' OpenStreetMap contributors',
            maxZoom: CONFIG.MAP_MAX_ZOOM
        }).addTo(state.map);

        // Agregar marcador de destino
        if (state.destination && state.destination.lat && state.destination.lng) {
            addDestinationMarker(state.destination);
        }

        console.log(' Mapa inicializado');
    }

    // =========================================================
    // TRACKING DE ubicaciÃ³n EN TIEMPO REAL
    // =========================================================
    function startLocationTracking() {
        if (state.watchId) {
            navigator.geolocation.clearWatch(state.watchId);
        }

        const options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        };

        state.watchId = navigator.geolocation.watchPosition(
            handleLocationUpdate,
            handleLocationError,
            options
        );

        updateStatus('Obteniendo ubicaciÃ³n...');
        console.log(' Tracking de ubicaciÃ³n iniciado');
    }

    // =========================================================
    // MANEJAR actualizaciÃ³n DE ubicaciÃ³n
    // =========================================================
    function handleLocationUpdate(position) {
        const { latitude, longitude, accuracy, speed, heading } = position.coords;
        
        state.currentLocation = { lat: latitude, lng: longitude };
        state.currentSpeed = speed ? (speed * 3.6) : 0; // Convertir m/s a km/h
        state.currentHeading = heading || 0;

        // Actualizar marcador del conductor
        updateDriverMarker(state.currentLocation, state.currentHeading);

        // Si es la primera ubicaciÃ³n, centrar mapa y calcular ruta
        if (!state.route) {
            state.map.setView(state.currentLocation, CONFIG.MAP_ZOOM);
            calculateRoute(state.currentLocation, state.destination);
        }

        // Actualizar UI
        updateSpeedDisplay(state.currentSpeed);

        // Si estamos navegando, enviar actualizaciÃ³n al servidor
        if (state.isNavigating) {
            sendLocationUpdate(position);
        }

        console.log(` ubicaciÃ³n actualizada: ${latitude.toFixed(6)}, ${longitude.toFixed(6)}`);
    }

    // =========================================================
    // MANEJAR ERROR DE ubicaciÃ³n
    // =========================================================
    function handleLocationError(error) {
        console.error(' Error de ubicaciÃ³n:', error);
        
        let message = 'Error al obtener ubicaciÃ³n';
        switch (error.code) {
            case error.PERMISSION_DENIED:
                message = 'Permisos de ubicaciÃ³n denegados';
                break;
            case error.POSITION_UNAVAILABLE:
                message = 'ubicaciÃ³n no disponible';
                break;
            case error.TIMEOUT:
                message = 'Timeout al obtener ubicaciÃ³n';
                break;
        }
        
        showNotification(message, 'error');
        updateStatus('Error de ubicaciÃ³n');
    }

    // =========================================================
    // CALCULAR RUTA
    // =========================================================
    async function calculateRoute(start, end) {
        try {
            // Validar coordenadas antes de calcular
            if (!start || !start.lat || !start.lng || start.lat === 0 || start.lng === 0) {
                throw new Error('Coordenadas de inicio no vlidas');
            }
            
            if (!end || !end.lat || !end.lng || end.lat === 0 || end.lng === 0) {
                throw new Error('Coordenadas de destino no vlidas');
            }
            
            updateStatus('Calculando ruta...');
            showNotification('Calculando mejor ruta...', 'info');

            const url = `${CONFIG.BASE_URL}/delivery/api/navigation_api.php?action=get_route` +
                        `&start_lat=${start.lat}&start_lng=${start.lng}` +
                        `&end_lat=${end.lat}&end_lng=${end.lng}`;
            
            console.log(' Calculando ruta:', url);

            const response = await fetch(url);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error al calcular ruta');
            }

            state.route = data.route;
            state.distanceRemaining = state.route.distance_km;
            state.etaSeconds = state.route.duration_seconds;

            // Dibujar ruta en el mapa
            drawRoute(state.route.geometry);

            // Actualizar UI
            updateRouteInfo(state.route);
            updateETADisplay(state.etaSeconds);
            updateDistanceDisplay(state.distanceRemaining);

            // Centrar mapa en la ruta
            fitMapToRoute();

            updateStatus('Ruta calculada');
            showNotification(`Ruta de ${state.route.distance_km} km calculada`, 'success');

            console.log(' Ruta calculada:', state.route);

        } catch (error) {
            console.error(' Error al calcular ruta:', error);
            showNotification(error.message, 'error');
            updateStatus('Error al calcular ruta');
        }
    }

    // =========================================================
    // DIBUJAR RUTA EN EL MAPA
    // =========================================================
    function drawRoute(geometry) {
        // Remover ruta anterior si existe
        if (state.routePolyline) {
            state.map.removeLayer(state.routePolyline);
        }

        // Convertir coordenadas de GeoJSON a formato Leaflet [lat, lng]
        const coordinates = geometry.coordinates.map(coord => [coord[1], coord[0]]);

        // Crear polyline
        state.routePolyline = L.polyline(coordinates, {
            color: '#667eea',
            weight: 6,
            opacity: 0.8,
            lineJoin: 'round'
        }).addTo(state.map);

        // Agregar borde al polyline para mejor visibilidad
        L.polyline(coordinates, {
            color: '#fff',
            weight: 8,
            opacity: 0.4,
            lineJoin: 'round'
        }).addTo(state.map);
    }

    // =========================================================
    // ACTUALIZAR MARCADOR DEL CONDUCTOR
    // =========================================================
    function updateDriverMarker(location, heading) {
        if (!state.driverMarker) {
            // Crear marcador personalizado
            const icon = L.divIcon({
                html: `<div class="driver-marker" style="transform: rotate(${heading}deg)">
                        <i class="fas fa-location-arrow"></i>
                       </div>`,
                className: '',
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            });

            state.driverMarker = L.marker([location.lat, location.lng], { icon })
                .addTo(state.map)
                .bindPopup('Tu ubicaciÃ³n');
        } else {
            // Actualizar posicin y rotacin
            state.driverMarker.setLatLng([location.lat, location.lng]);
            
            const iconElement = state.driverMarker.getElement();
            if (iconElement) {
                const markerDiv = iconElement.querySelector('.driver-marker');
                if (markerDiv) {
                    markerDiv.style.transform = `rotate(${heading}deg)`;
                }
            }
        }
    }

    // =========================================================
    // AGREGAR MARCADOR DE DESTINO
    // =========================================================
    function addDestinationMarker(location) {
        const icon = L.divIcon({
            html: `<div class="destination-marker">
                    <i class="fas fa-map-marker-alt"></i>
                   </div>`,
            className: '',
            iconSize: [40, 40],
            iconAnchor: [20, 40]
        });

        state.destinationMarker = L.marker([location.lat, location.lng], { icon })
            .addTo(state.map)
            .bindPopup('Destino: ' + (state.deliveryData?.destination.address || 'direcciÃ³n de entrega'));
    }

    // =========================================================
    // AJUSTAR MAPA A LA RUTA
    // =========================================================
    function fitMapToRoute() {
        if (state.routePolyline) {
            const bounds = state.routePolyline.getBounds();
            state.map.fitBounds(bounds, { padding: [50, 50] });
        }
    }

    // =========================================================
    // INICIAR NavegaciÃ³n
    // =========================================================
    async function startNavigation() {
        if (!state.currentLocation || !state.destination || !state.route) {
            showNotification('Esperando ubicaciÃ³n y ruta...', 'warning');
            return;
        }

        try {
            updateStatus('Iniciando NavegaciÃ³n...');

            const response = await fetch(`${CONFIG.BASE_URL}/delivery/api/navigation_api.php?action=start_navigation`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    delivery_id: state.deliveryData.delivery_id,
                    start_lat: state.currentLocation.lat,
                    start_lng: state.currentLocation.lng,
                    dest_lat: state.destination.lat,
                    dest_lng: state.destination.lng,
                    route: state.route,
                    distance_km: state.route.distance_km,
                    duration_seconds: state.route.duration_seconds
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error al iniciar NavegaciÃ³n');
            }

            state.isNavigating = true;
            
            // Cambiar botn de accin
            updateActionButton('pause', 'Pausar navegación');
            
            // Iniciar actualizaciones peridicas
            startPeriodicUpdates();
            
            // Centrar mapa en conductor
            centerOnDriver();
            
            updateStatus('Navegando');
            showNotification('Navegación iniciada', 'success');
            
            // Instrucción de voz
            speak('Navegación iniciada. Sigue la ruta marcada.');

            console.log('✅ Navegación iniciada');

        } catch (error) {
            console.error(' Error al iniciar NavegaciÃ³n:', error);
            showNotification(error.message, 'error');
        }
    }

    // =========================================================
    // PAUSAR NAVEGACIÓN
    // =========================================================
    async function pauseNavigation() {
        try {
            updateStatus('Pausando navegación...');

            const response = await fetch(`${CONFIG.BASE_URL}/delivery/api/navigation_api.php?action=pause_navigation`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    delivery_id: state.deliveryData.delivery_id
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error al pausar navegación');
            }

            state.isNavigating = false;
            
            // Detener actualizaciones periódicas
            if (state.updateInterval) {
                clearInterval(state.updateInterval);
                state.updateInterval = null;
            }
            
            if (state.routeCheckInterval) {
                clearInterval(state.routeCheckInterval);
                state.routeCheckInterval = null;
            }
            
            // Cambiar botón de acción
            updateActionButton('resume', 'Reanudar navegación');
            
            updateStatus('Navegación pausada');
            showNotification('Navegación pausada', 'warning');
            speak('Navegación pausada');

            console.log('⏸ Navegación pausada');

        } catch (error) {
            console.error('❌ Error al pausar navegación:', error);
            showNotification(error.message, 'error');
        }
    }

    // =========================================================
    // REANUDAR NAVEGACIÓN
    // =========================================================
    async function resumeNavigation() {
        try {
            updateStatus('Reanudando navegación...');

            const response = await fetch(`${CONFIG.BASE_URL}/delivery/api/navigation_api.php?action=resume_navigation`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    delivery_id: state.deliveryData.delivery_id
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error al reanudar navegación');
            }

            state.isNavigating = true;
            
            // Cambiar botón de acción
            updateActionButton('pause', 'Pausar navegación');
            
            // Reiniciar actualizaciones periódicas
            startPeriodicUpdates();
            
            updateStatus('Navegando');
            showNotification('Navegación reanudada', 'success');
            speak('Navegación reanudada');

            console.log('▶ Navegación reanudada');

        } catch (error) {
            console.error('❌ Error al reanudar navegación:', error);
            showNotification(error.message, 'error');
        }
    }

    // =========================================================
    // ACTUALIZACIONES PERIDICAS
    // =========================================================
    function startPeriodicUpdates() {
        // Actualizar ubicaciÃ³n al servidor cada 5 segundos
        state.updateInterval = setInterval(() => {
            if (state.isNavigating && state.currentLocation) {
                sendLocationUpdate({
                    coords: {
                        latitude: state.currentLocation.lat,
                        longitude: state.currentLocation.lng,
                        accuracy: 10,
                        speed: state.currentSpeed / 3.6, // Convertir a m/s
                        heading: state.currentHeading
                    }
                });
            }
        }, CONFIG.UPDATE_INTERVAL);

        // Verificar si estamos fuera de ruta cada 30 segundos
        state.routeCheckInterval = setInterval(() => {
            if (state.isNavigating) {
                checkIfOnRoute();
            }
        }, CONFIG.ROUTE_CHECK_INTERVAL);
    }

    // =========================================================
    // ENVIAR actualizaciÃ³n DE ubicaciÃ³n AL SERVIDOR
    // =========================================================
    async function sendLocationUpdate(position) {
        try {
            const response = await fetch(`${CONFIG.BASE_URL}/delivery/api/navigation_api.php?action=update_location`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    delivery_id: state.deliveryData.delivery_id,
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    speed: position.coords.speed || 0,
                    heading: position.coords.heading || 0,
                    battery_level: state.batteryLevel
                })
            });

            const data = await response.json();

            if (data.success) {
                // Actualizar informaciÃ³n local con datos del servidor
                if (data.distance_remaining !== null) {
                    state.distanceRemaining = data.distance_remaining;
                    updateDistanceDisplay(state.distanceRemaining);
                }
                
                if (data.eta_seconds !== null) {
                    state.etaSeconds = data.eta_seconds;
                    updateETADisplay(state.etaSeconds);
                }

                // Verificar si estamos cerca del destino
                if (state.distanceRemaining < CONFIG.NEAR_DESTINATION_THRESHOLD) {
                    handleNearDestination();
                }
            }

        } catch (error) {
            console.error('Error al enviar actualizaciÃ³n de ubicaciÃ³n:', error);
        }
    }

    // =========================================================
    // VERIFICAR SI ESTAMOS EN LA RUTA
    // =========================================================
    function checkIfOnRoute() {
        // TODO: Implementar verificaciÃ³n de distancia a la ruta
        // Por ahora, siempre asumimos que estamos en ruta
    }

    // =========================================================
    // MANEJAR PROXIMIDAD AL DESTINO
    // =========================================================
    function handleNearDestination() {
        if (!state.nearDestinationNotified) {
            state.nearDestinationNotified = true;
            showNotification('¡Estás cerca del destino!', 'success');
            speak('Estás cerca del destino');
            
            // Registrar evento
            logNavigationEvent('destination_near', {
                distance_remaining: state.distanceRemaining
            });
        }
    }

    // =========================================================
    // REGISTRAR EVENTO DE NavegaciÃ³n
    // =========================================================
    async function logNavigationEvent(eventType, eventData) {
        try {
            await fetch(`${CONFIG.BASE_URL}/delivery/api/navigation_api.php?action=log_event`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    delivery_id: state.deliveryData.delivery_id,
                    event_type: eventType,
                    event_data: eventData,
                    latitude: state.currentLocation?.lat || null,
                    longitude: state.currentLocation?.lng || null
                })
            });
        } catch (error) {
            console.error('Error al registrar evento:', error);
        }
    }

    // =========================================================
    // ACTUALIZAR DISPLAYS DE UI
    // =========================================================
    function updateStatus(text) {
        const statusElement = document.getElementById('nav-status');
        if (statusElement) {
            statusElement.textContent = text;
        }
    }

    function updateRouteInfo(route) {
        const instructionMain = document.getElementById('instruction-main');
        if (instructionMain && route.steps && route.steps.length > 0) {
            instructionMain.textContent = route.steps[0].name || 'Sigue por esta va';
        }
    }

    function updateETADisplay(seconds) {
        const minutes = Math.round(seconds / 60);
        const etaTimeElement = document.getElementById('eta-time');
        
        if (etaTimeElement) {
            etaTimeElement.textContent = minutes;
        }

        // Calcular hora de llegada
        const arrivalTime = new Date(Date.now() + seconds * 1000);
        const arrivalTimeElement = document.getElementById('arrival-time');
        
        if (arrivalTimeElement) {
            arrivalTimeElement.textContent = arrivalTime.toLocaleTimeString('es-CO', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }

    function updateDistanceDisplay(km) {
        const distanceElement = document.getElementById('distance-remaining');
        const instructionDistance = document.getElementById('instruction-distance');
        
        // Validar que km sea un número válido
        const distance = parseFloat(km);
        if (isNaN(distance) || distance === null || distance === undefined) {
            if (distanceElement) distanceElement.textContent = '-- km';
            if (instructionDistance) instructionDistance.textContent = '--';
            return;
        }
        
        if (distanceElement) {
            distanceElement.textContent = `${distance.toFixed(1)} km`;
        }
        
        if (instructionDistance) {
            instructionDistance.textContent = `En ${distance.toFixed(1)} km`;
        }
    }

    function updateSpeedDisplay(kmh) {
        const speedElement = document.getElementById('current-speed');
        if (speedElement) {
            speedElement.textContent = `${Math.round(kmh)} km/h`;
        }
    }

    function updateActionButton(action, text) {
        const button = document.getElementById('btn-action-main');
        const textElement = document.getElementById('btn-action-text');
        
        if (button && textElement) {
            button.dataset.action = action;
            textElement.textContent = text;
            
            const icon = button.querySelector('i');
            if (icon) {
                icon.className = action === 'start' ? 'fas fa-play-circle' : 
                                 action === 'pause' ? 'fas fa-pause-circle' : 
                                 'fas fa-stop-circle';
            }
        }
    }

    // =========================================================
    // FUNCIONES GLOBALES (accesibles desde HTML)
    // =========================================================
    window.handleMainAction = function() {
        const button = document.getElementById('btn-action-main');
        const action = button?.dataset.action || 'start';
        
        if (action === 'start') {
            startNavigation();
        } else if (action === 'pause') {
            pauseNavigation();
        } else if (action === 'resume') {
            resumeNavigation();
        }
    };

    window.togglePanel = function() {
        state.isPanelExpanded = !state.isPanelExpanded;
        
        const panelCompact = document.getElementById('panel-compact');
        const panelExpanded = document.getElementById('panel-expanded');
        
        if (state.isPanelExpanded) {
            panelCompact.style.display = 'none';
            panelExpanded.style.display = 'block';
        } else {
            panelCompact.style.display = 'block';
            panelExpanded.style.display = 'none';
        }
    };

    window.centerOnDriver = function() {
        if (state.currentLocation) {
            state.map.setView(state.currentLocation, CONFIG.MAP_ZOOM, {
                animate: true,
                duration: 0.5
            });
        }
    };

    window.toggleVoice = function() {
        state.isVoiceEnabled = !state.isVoiceEnabled;
        const button = document.getElementById('btn-voice');
        
        if (button) {
            const icon = button.querySelector('i');
            icon.className = state.isVoiceEnabled ? 'fas fa-volume-up' : 'fas fa-volume-mute';
            button.classList.toggle('active', state.isVoiceEnabled);
        }
        
        showNotification(
            state.isVoiceEnabled ? 'Instrucciones de voz activadas' : 'Instrucciones de voz desactivadas',
            'info'
        );
    };

    window.toggleTraffic = async function() {
        state.isTrafficVisible = !state.isTrafficVisible;
        
        const button = document.getElementById('btn-traffic');
        
        if (state.isTrafficVisible) {
            // Activar informaciÃ³n de trfico
            showNotification('Cargando informaciÃ³n de trfico...', 'info');
            
            try {
                // opciÃ³n 1: Usar capa de transporte de OpenStreetMap (muestra vas principales)
                if (!state.trafficLayer) {
                    state.trafficLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: ' OpenStreetMap contributors',
                        maxZoom: CONFIG.MAP_MAX_ZOOM,
                        className: 'traffic-overlay',
                        opacity: 0.7
                    });
                }
                
                state.trafficLayer.addTo(state.map);
                
                // Simular datos de trfico basados en hora del da
                const trafficLevel = getTrafficLevelByTime();
                displayTrafficInfo(trafficLevel);
                
                if (button) {
                    button.classList.add('active');
                }
                
                showNotification(`Trfico ${trafficLevel.label} en tu ruta`, 'success');
                console.log(' Vista de trfico activada');
                
                // Recalcular ETA considerando trfico
                if (state.route && trafficLevel.multiplier > 1) {
                    const adjustedETA = state.etaSeconds * trafficLevel.multiplier;
                    updateETADisplay(adjustedETA);
                    showNotification(`ETA ajustado por trfico: +${Math.round((adjustedETA - state.etaSeconds) / 60)} min`, 'warning');
                }
                
            } catch (error) {
                console.error('Error al activar trfico:', error);
                showNotification('Error al cargar informaciÃ³n de trfico', 'error');
            }
            
        } else {
            // Desactivar capa de trfico
            if (state.trafficLayer) {
                state.map.removeLayer(state.trafficLayer);
            }
            
            // Ocultar info de trfico
            hideTrafficInfo();
            
            if (button) {
                button.classList.remove('active');
            }
            
            // Restaurar ETA original
            if (state.route) {
                updateETADisplay(state.route.duration_seconds);
            }
            
            showNotification('Vista de trfico desactivada', 'info');
            console.log(' Capa de trfico desactivada');
        }
    };
    
    // =========================================================
    // OBTENER NIVEL DE TRFICO SEGN LA HORA
    // =========================================================
    function getTrafficLevelByTime() {
        const now = new Date();
        const hour = now.getHours();
        const dayOfWeek = now.getDay(); // 0 = Domingo, 6 = Sbado
        
        // Fin de semana - menos trfico
        if (dayOfWeek === 0 || dayOfWeek === 6) {
            if (hour >= 10 && hour <= 14) {
                return { level: 'medium', label: 'Moderado', color: '#fbbf24', multiplier: 1.2 };
            }
            return { level: 'low', label: 'Fluido', color: '#10b981', multiplier: 1.0 };
        }
        
        // Entre semana - evaluar horas pico
        // Hora pico maana: 6:00 - 9:00
        if (hour >= 6 && hour < 9) {
            return { level: 'high', label: 'Pesado', color: '#ef4444', multiplier: 1.5 };
        }
        
        // Medioda: 12:00 - 14:00
        if (hour >= 12 && hour < 14) {
            return { level: 'medium', label: 'Moderado', color: '#fbbf24', multiplier: 1.2 };
        }
        
        // Hora pico tarde: 17:00 - 20:00
        if (hour >= 17 && hour < 20) {
            return { level: 'high', label: 'Pesado', color: '#ef4444', multiplier: 1.5 };
        }
        
        // Resto del da - trfico normal
        return { level: 'low', label: 'Fluido', color: '#10b981', multiplier: 1.0 };
    }
    
    // =========================================================
    // MOSTRAR informaciÃ³n DE TRFICO EN UI
    // =========================================================
    function displayTrafficInfo(trafficLevel) {
        // Buscar o crear elemento de info de trfico
        let trafficInfo = document.getElementById('traffic-info');
        
        if (!trafficInfo) {
            trafficInfo = document.createElement('div');
            trafficInfo.id = 'traffic-info';
            trafficInfo.style.cssText = `
                position: fixed;
                top: 80px;
                right: 16px;
                z-index: 999;
                background: rgba(0, 0, 0, 0.9);
                backdrop-filter: blur(10px);
                padding: 12px 16px;
                border-radius: 12px;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                gap: 12px;
                animation: slideInRight 0.3s ease;
            `;
            document.body.appendChild(trafficInfo);
        }
        
        trafficInfo.innerHTML = `
            <div style="width: 8px; height: 8px; background: ${trafficLevel.color}; border-radius: 50%; box-shadow: 0 0 8px ${trafficLevel.color};"></div>
            <div style="color: white; font-size: 14px; font-weight: 500;">
                Trfico ${trafficLevel.label}
            </div>
        `;
        
        trafficInfo.style.display = 'flex';
    }
    
    // Ocultar info de trfico cuando se desactive
    function hideTrafficInfo() {
        const trafficInfo = document.getElementById('traffic-info');
        if (trafficInfo) {
            trafficInfo.style.display = 'none';
        }
    }

    window.toggleMenu = function() {
        const overlay = document.getElementById('menu-overlay');
        const drawer = document.getElementById('menu-drawer');
        
        overlay.classList.toggle('show');
        drawer.classList.toggle('show');
    };

    window.recalculateRoute = function() {
        if (state.currentLocation && state.destination) {
            showNotification('Recalculando ruta...', 'info');
            calculateRoute(state.currentLocation, state.destination);
            window.toggleMenu();
        }
    };

    window.reportIssue = function() {
        showNotification('funciÃ³n en desarrollo', 'info');
        window.toggleMenu();
    };

    window.viewOrderDetails = function() {
        window.togglePanel();
        window.toggleMenu();
    };

    window.cancelNavigation = function() {
        if (confirm('Deseas cancelar la NavegaciÃ³n?')) {
            stopNavigation();
            window.location.href = `${CONFIG.BASE_URL}/delivery/orders.php`;
        }
    };

    window.confirmExit = function() {
        if (state.isNavigating) {
            if (confirm('Deseas salir de la NavegaciÃ³n? El progreso se guardar.')) {
                stopNavigation();
                window.location.href = `${CONFIG.BASE_URL}/delivery/orders.php`;
            }
        } else {
            window.location.href = `${CONFIG.BASE_URL}/delivery/orders.php`;
        }
    };

    // =========================================================
    // DETENER NavegaciÃ³n
    // =========================================================
    function stopNavigation() {
        state.isNavigating = false;
        
        if (state.updateInterval) {
            clearInterval(state.updateInterval);
            state.updateInterval = null;
        }
        
        if (state.routeCheckInterval) {
            clearInterval(state.routeCheckInterval);
            state.routeCheckInterval = null;
        }
        
        if (state.watchId) {
            navigator.geolocation.clearWatch(state.watchId);
            state.watchId = null;
        }
        
        console.log(' NavegaciÃ³n detenida');
    }

    // =========================================================
    // SÍNTESIS DE VOZ (ESPAÑOL MEJORADO)
    // =========================================================
    
    // Variable global para almacenar la mejor voz en español
    let bestSpanishVoice = null;
    
    // Función para seleccionar la mejor voz en español
    function selectBestSpanishVoice() {
        const voices = window.speechSynthesis.getVoices();
        
        // Prioridad de voces (de mayor a menor calidad/naturalidad)
        const voicePriority = [
            // Voces de Google (muy naturales)
            { pattern: /google.*español|google.*spanish.*es/i, lang: 'es-ES', priority: 10 },
            { pattern: /google.*español.*mexico|google.*spanish.*mx/i, lang: 'es-MX', priority: 9 },
            { pattern: /google.*español.*us/i, lang: 'es-US', priority: 8 },
            
            // Voces de Microsoft (buena calidad)
            { pattern: /helena/i, lang: 'es-ES', priority: 7 },
            { pattern: /sabina/i, lang: 'es-MX', priority: 7 },
            
            // Voces de Apple (excelente calidad)
            { pattern: /monica/i, lang: 'es-ES', priority: 9 },
            { pattern: /paulina/i, lang: 'es-MX', priority: 9 },
            { pattern: /juan/i, lang: 'es-MX', priority: 8 },
            
            // Cualquier voz nativa en español
            { pattern: /español|spanish/i, lang: 'es', priority: 5 }
        ];
        
        let selectedVoice = null;
        let highestPriority = 0;
        
        voices.forEach(voice => {
            // Solo considerar voces que sean específicamente para español
            if (!voice.lang.startsWith('es-') && !voice.lang.startsWith('es')) {
                return;
            }
            
            // Excluir voces con acento inglés
            if (voice.name.toLowerCase().includes('en-') || 
                voice.name.toLowerCase().includes('english')) {
                return;
            }
            
            // Buscar coincidencias en la prioridad
            for (const prio of voicePriority) {
                if (prio.pattern.test(voice.name) || voice.lang.startsWith(prio.lang)) {
                    if (prio.priority > highestPriority) {
                        highestPriority = prio.priority;
                        selectedVoice = voice;
                    }
                }
            }
        });
        
        // Si no se encontró ninguna voz específica, buscar cualquiera en español
        if (!selectedVoice) {
            selectedVoice = voices.find(v => 
                v.lang.startsWith('es-') || v.lang === 'es'
            );
        }
        
        bestSpanishVoice = selectedVoice;
        
        if (selectedVoice) {
            console.log('✅ Mejor voz en español seleccionada:', selectedVoice.name, '(' + selectedVoice.lang + ')');
        } else {
            console.warn('⚠️ No se encontró ninguna voz en español. Total de voces:', voices.length);
        }
    }
    
    // Inicializar voces cuando estén disponibles
    if (window.speechSynthesis) {
        if (window.speechSynthesis.getVoices().length > 0) {
            selectBestSpanishVoice();
        }
        
        // Las voces pueden cargarse de forma asíncrona
        window.speechSynthesis.onvoiceschanged = selectBestSpanishVoice;
    }
        
    // =========================================================
    // SÍNTESIS DE VOZ - Usa VoiceHelper
    // =========================================================
    function speak(text) {
        if (!state.isVoiceEnabled) {
            console.log('� Voz desactivada por usuario');
            return;
        }
        
        if (state.voiceHelper) {
            state.voiceHelper.speak(text).catch(err => {
                console.error('Error al hablar:', err);
            });
        } else {
            console.warn('⚠️ VoiceHelper no inicializado');
        }
    }
    
    // Función de compatibilidad para código antiguo
    window.speak = speak;

    // =========================================================
    // DETENER NavegaciÃ³n
    // =========================================================
    function stopNavigation() {
        state.isNavigating = false;
        
        if (state.updateInterval) {
            clearInterval(state.updateInterval);
            state.updateInterval = null;
        }
        
        if (state.routeCheckInterval) {
            clearInterval(state.routeCheckInterval);
            state.routeCheckInterval = null;
        }
        
        if (state.watchId) {
            navigator.geolocation.clearWatch(state.watchId);
            state.watchId = null;
        }
        
        console.log(' NavegaciÃ³n detenida');
    }

    // =========================================================
    // NOTIFICACIONES
    // =========================================================
    function showNotification(message, type) {
        type = type || 'info';
        const container = document.getElementById('notification-container');
        if (!container) return;

        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        const iconMap = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        
        notification.innerHTML = `
            <i class="fas fa-${iconMap[type] || 'info-circle'}"></i>
            <span>${message}</span>
        `;
        
        container.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // =========================================================
    // LIMPIAR AL SALIR
    // =========================================================
    window.addEventListener('beforeunload', function() {
        stopNavigation();
    });

})();
