/**
 * =========================================================
 * SISTEMA DE Navegación EN TIEMPO REAL - ANGELOW DELIVERY
 * Funcionalidad: Navegación GPS estilo Uber/Waze con tracking en tiempo real
 * =========================================================
 */

(function() {
    'use strict';

    // =========================================================
    // CONFIGURACIN GLOBAL
    // =========================================================
    const CONFIG = {
        BASE_URL: document.querySelector('meta[name="base-url"]')?.content || '',
        UPDATE_INTERVAL: 5000, // Actualizar ubicación cada 5 segundos
        ROUTE_CHECK_INTERVAL: 30000, // Verificar ruta cada 30 segundos
        INSTRUCTION_CHECK_INTERVAL: 3000, // Verificar instrucciones cada 3 segundos
        NEAR_DESTINATION_THRESHOLD: 0.1, // 100 metros
        OFF_ROUTE_THRESHOLD: 0.05, // 50 metros fuera de ruta
        SPEECH_ENABLED: true,
        MAP_ZOOM: 17,
        MAP_MIN_ZOOM: 10,
        MAP_MAX_ZOOM: 19,
        // Umbrales para instrucciones de voz (en metros)
        INSTRUCTION_DISTANCES: [500, 200, 100, 50]
    };

    // =========================================================
    // ESTADO DE LA APLICACIÓN
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
        instructionCheckInterval: null,
        currentSpeed: 0,
        currentHeading: 0,
        distanceRemaining: 0,
        etaSeconds: 0,
        currentStepIndex: 0,
        currentStep: null,
        lastInstructionDistance: null,
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
        
        // Prevenir zoom con gestos en móviles
        document.addEventListener('gesturestart', function (e) {
            e.preventDefault();
        });
        
        // Mantener pantalla activa durante Navegación
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
        console.log(' Iniciando sistema de Navegación...');
        
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
        
        // Solicitar permisos de ubicación
        requestLocationPermission();
        
        // Inicializar mapa
        initializeMap();
        
        // Inicializar eventos
        initializeEvents();
        
        // Obtener información de batería si está disponible
        if ('getBattery' in navigator) {
            navigator.getBattery().then(battery => {
                state.batteryLevel = Math.round(battery.level * 100);
                battery.addEventListener('levelchange', () => {
                    state.batteryLevel = Math.round(battery.level * 100);
                });
            });
        }
        
        console.log(' Sistema de Navegación inicializado');
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
                console.error(' Coordenadas de destino no vstlidas:', state.destination);
                showNotification('Error: La dirección de entrega no tiene coordenadas GPS. Contacta al administrador.', 'error');
                
                // Deshabilitar Navegación
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
            showNotification('Error al cargar información del pedido', 'error');
        }
    }

    // =========================================================
    // SOLICITAR PERMISOS DE ubicación
    // =========================================================
    function requestLocationPermission() {
        if (!('geolocation' in navigator)) {
            showNotification('Tu dispositivo no soporta geolocalizacin', 'error');
            return;
        }

        updateStatus('Solicitando permisos de ubicación...');
        
        navigator.permissions.query({ name: 'geolocation' }).then(result => {
            if (result.state === 'granted') {
                console.log(' Permisos de ubicación concedidos');
                startLocationTracking();
            } else if (result.state === 'prompt') {
                // Solicitar permisos
                navigator.geolocation.getCurrentPosition(
                    position => {
                        console.log(' Permisos de ubicación concedidos');
                        startLocationTracking();
                    },
                    error => {
                        console.error(' Permisos de ubicación denegados:', error);
                        showNotification('Se requieren permisos de ubicación para navegar', 'error');
                    },
                    { enableHighAccuracy: true }
                );
            } else {
                showNotification('Se requieren permisos de ubicación para navegar', 'error');
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
            addDestáinationMarker(state.destination);
        }

        console.log(' Mapa inicializado');
    }

    // =========================================================
    // TRACKING DE ubicación EN TIEMPO REAL
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

        updateStatus('Obteniendo ubicación...');
        console.log(' Tracking de ubicación iniciado');
    }

    // =========================================================
    // MANEJAR actualización³n DE ubicación
    // =========================================================
    function handleLocationUpdate(position) {
        const { latitude, longitude, accuracy, speed, heading } = position.coords;
        
        state.currentLocation = { lat: latitude, lng: longitude };
        state.currentSpeed = speed ? (speed * 3.6) : 0; // Convertir m/s a km/h
        state.currentHeading = heading || 0;

        // Actualizar marcador del conductor
        updateDriverMarker(state.currentLocation, state.currentHeading);

        // Si es la primera ubicación, centrar mapa y calcular ruta
        if (!state.route) {
            state.map.setView(state.currentLocation, CONFIG.MAP_ZOOM);
            calculateRoute(state.currentLocation, state.destination);
        }

        // Actualizar UI
        updateSpeedDisplay(state.currentSpeed);

        // Si estáamos navegando, enviar actualización³n al servidor
        if (state.isNavigating) {
            sendLocationUpdate(position);
        }

        console.log(` ubicación actualizada: ${latitude.toFixed(6)}, ${longitude.toFixed(6)}`);
    }

    // =========================================================
    // MANEJAR ERROR DE ubicación
    // =========================================================
    function handleLocationError(error) {
        console.error(' Error de ubicación:', error);
        
        let message = 'Error al obtener ubicación';
        switch (error.code) {
            case error.PERMISSION_DENIED:
                message = 'Permisos de ubicación denegados';
                break;
            case error.POSITION_UNAVAILABLE:
                message = 'ubicación no disponible';
                break;
            case error.TIMEOUT:
                message = 'Timeout al obtener ubicación';
                break;
        }
        
        showNotification(message, 'error');
        updateStatus('Error de ubicación');
    }

    // =========================================================
    // CALCULAR RUTA
    // =========================================================
    async function calculateRoute(start, end) {
        try {
            // Validar coordenadas antes de calcular
            if (!start || !start.lat || !start.lng || start.lat === 0 || start.lng === 0) {
                throw new Error('Coordenadas de inicio no vstlidas');
            }
            
            if (!end || !end.lat || !end.lng || end.lat === 0 || end.lng === 0) {
                throw new Error('Coordenadas de destino no vstlidas');
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
                .bindPopup('Tu ubicación');
        } else {
            // Actualizar posición y rotación
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
    // AGREGAR MARCADOR DE Destino
    // =========================================================
    function addDestáinationMarker(location) {
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
            .bindPopup('Destino: ' + (state.deliveryData?.destination.address || 'dirección de entrega'));
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
    // INICIAR Navegación
    // =========================================================
    async function startNavigation() {
        if (!state.currentLocation || !state.destination || !state.route) {
            showNotification('Esperando ubicación y ruta...', 'warning');
            return;
        }

        try {
            updateStatus('Iniciando Navegación...');

            const response = await fetch(`${CONFIG.BASE_URL}/delivery/api/navigation_api.php?action=start_navigation`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    delivery_id: state.deliveryData.delivery_id,
                    start_lat: state.currentLocation.lat,
                    start_lng: state.currentLocation.lng,
                    destá_lat: state.destination.lat,
                    destá_lng: state.destination.lng,
                    route: state.route,
                    distance_km: state.route.distance_km,
                    duration_seconds: state.route.duration_seconds
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error al iniciar Navegación');
            }

            state.isNavigating = true;
            
            // Cambiar botn de accin
            updateActionButton('pause', 'Pausar Navegación');
            
            // CERRAR PANEL AUTOMstTICAMENTE
            if (state.isPanelExpanded) {
                togglePanel();
            }
            
            // Iniciar actualizaciones peridicas
            startPeriodicUpdates();
            
            // Centrar mapa en conductor
            centerOnDriver();
            
            updateStatus('Navegando');
            showNotification('Navegación iniciada', 'success');
            
            // Instrucción de voz inicial (PRIORIDAD ALTA)
            speak('Navegación iniciada. Sigue la ruta marcada.', 1);
            
            // Dar primera instrucción si hay pasos
            if (state.route.steps && state.route.steps.length > 0) {
                state.currentStep = state.route.steps[0];
                updateCurrentInstruction();
            }

            console.log('✅ Navegación iniciada');

        } catch (error) {
            console.error(' Error al iniciar Navegación:', error);
            showNotification(error.message, 'error');
        }
    }

    // =========================================================
    // PAUSAR Navegación
    // =========================================================
    async function pauseNavigation() {
        try {
            updateStatus('Pausando Navegación...');

            const response = await fetch(`${CONFIG.BASE_URL}/delivery/api/navigation_api.php?action=pause_navigation`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    delivery_id: state.deliveryData.delivery_id
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error al pausar Navegación');
            }

            state.isNavigating = false;
            
            // Detener actualizaciones perióndicas
            if (state.updateInterval) {
                clearInterval(state.updateInterval);
                state.updateInterval = null;
            }
            
            if (state.routeCheckInterval) {
                clearInterval(state.routeCheckInterval);
                state.routeCheckInterval = null;
            }
            
            if (state.instructionCheckInterval) {
                clearInterval(state.instructionCheckInterval);
                state.instructionCheckInterval = null;
            }
            
            // Cambiar botón de acción
            updateActionButton('resume', 'Reanudar Navegación');
            
            updateStatus('Navegación pausada');
            showNotification('Navegación pausada', 'warning');
            speak('Navegación pausada', 1); // PRIORIDAD ALTA

            console.log('⏸ Navegación pausada');

        } catch (error) {
            console.error('❌ Error al pausar Navegación:', error);
            showNotification(error.message, 'error');
        }
    }

    // =========================================================
    // REANUDAR Navegación
    // =========================================================
    async function resumeNavigation() {
        try {
            updateStatus('Reanudando Navegación...');

            const response = await fetch(`${CONFIG.BASE_URL}/delivery/api/navigation_api.php?action=resume_navigation`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    delivery_id: state.deliveryData.delivery_id
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error al reanudar Navegación');
            }

            state.isNavigating = true;
            
            // Cambiar botón de acción
            updateActionButton('pause', 'Pausar Navegación');
            
            // Reiniciar actualizaciones perióndicas
            startPeriodicUpdates();
            
            updateStatus('Navegando');
            showNotification('Navegación reanudada', 'success');
            speak('Navegación reanudada', 1); // PRIORIDAD ALTA

            console.log('▶ Navegación reanudada');

        } catch (error) {
            console.error('❌ Error al reanudar Navegación:', error);
            showNotification(error.message, 'error');
        }
    }

    // =========================================================
    // ACTUALIZACIONES PERIDICAS
    // =========================================================
    function startPeriodicUpdates() {
        // Actualizar ubicación al servidor cada 5 segundos
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

        // Verificar si estáamos fuera de ruta cada 30 segundos
        state.routeCheckInterval = setInterval(() => {
            if (state.isNavigating) {
                checkIfOnRoute();
            }
        }, CONFIG.ROUTE_CHECK_INTERVAL);
        
        // Verificar instrucciones de Navegación cada 3 segundos
        state.instructionCheckInterval = setInterval(() => {
            if (state.isNavigating && state.currentLocation) {
                checkNavigationInstructions();
            }
        }, CONFIG.INSTRUCTION_CHECK_INTERVAL);
    }

    // =========================================================
    // ENVIAR actualización³n DE ubicación AL SERVIDOR
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
                // Actualizar información local con datos del servidor
                if (data.distance_remaining !== null) {
                    state.distanceRemaining = data.distance_remaining;
                    updateDistanceDisplay(state.distanceRemaining);
                }
                
                if (data.eta_seconds !== null) {
                    state.etaSeconds = data.eta_seconds;
                    updateETADisplay(state.etaSeconds);
                }

                // Verificar si estáamos cerca del destino
                if (state.distanceRemaining < CONFIG.NEAR_DESTINATION_THRESHOLD) {
                    handleNearDestáination();
                }
            }

        } catch (error) {
            console.error('Error al enviar actualización³n de ubicación:', error);
        }
    }

    // =========================================================
    // VERIFICAR SI estáAMOS EN LA RUTA
    // =========================================================
    function checkIfOnRoute() {
        // TODO: Implementar verificación³n de distancia a la ruta
        // Por ahora, siempre asumimos que estáamos en ruta
    }
    
    // =========================================================
    // VERIFICAR INSTRUCCIONES DE Navegación (estilo WAZE)
    // =========================================================
    function checkNavigationInstructions() {
        if (!state.route || !state.route.steps || state.route.steps.length === 0) {
            return;
        }
        
        // Calcular distancia al siguiente paso
        const nextStep = state.route.steps[state.currentStepIndex];
        if (!nextStep || !nextStep.location) {
            return;
        }
        
        const distanceToStep = calculateDistance(
            state.currentLocation.lat,
            state.currentLocation.lng,
            nextStep.location[1], // lat
            nextStep.location[0]  // lng
        );
        
        const distanceInMeters = distanceToStep * 1000;
        
        // Actualizar instrucción visual
        updateNavigationInstruction(nextStep, distanceInMeters);
        
        // Dar instrucción de voz en puntos especínficos
        giveVoiceInstruction(nextStep, distanceInMeters);
        
        // Si ya pasamos estáe paso, avanzar al siguiente
        if (distanceInMeters < 20 && state.currentStepIndex < state.route.steps.length - 1) {
            state.currentStepIndex++;
            state.lastInstructionDistance = null;
            console.log(`➡️ Avanzando al paso ${state.currentStepIndex + 1}/${state.route.steps.length}`);
        }
    }
    
    // =========================================================
    // ACTUALIZAR INSTRUCCIón VISUAL EN PANTALLA
    // =========================================================
    function updateNavigationInstruction(step, distanceInMeters) {
        const instructionMain = document.getElementById('instruction-main');
        const instructionDistance = document.getElementById('instruction-distance');
        const instructionIcon = document.getElementById('instruction-icon');
        
        if (!instructionMain || !instructionDistance || !instructionIcon) return;
        
        // Obtener tipo de maniobra y texto
        const maneuver = getManeuverInfo(step);
        
        // Actualizar icono
        instructionIcon.innerHTML = `<i class="${maneuver.icon}"></i>`;
        
        // Actualizar texto principal
        instructionMain.textContent = maneuver.text;
        
        // Actualizar distancia
        if (distanceInMeters > 1000) {
            instructionDistance.textContent = `En ${(distanceInMeters / 1000).toFixed(1)} km`;
        } else {
            instructionDistance.textContent = `En ${Math.round(distanceInMeters)} m`;
        }
    }
    
    // =========================================================
    // DAR INSTRUCCIón DE VOZ EN PUNTOS ESPECínFICOS
    // =========================================================
    function giveVoiceInstruction(step, distanceInMeters) {
        // Solo dar instrucciones en distancias especínficas
        const distances = CONFIG.INSTRUCTION_DISTANCES;
        
        for (const threshold of distances) {
            // Si estáamos cerca de estáe umbral y no lo hemos anunciado aúnn
            if (distanceInMeters <= threshold && 
                distanceInMeters > threshold - 50 &&
                state.lastInstructionDistance !== threshold) {
                
                state.lastInstructionDistance = threshold;
                const maneuver = getManeuverInfo(step);
                const distanceText = threshold >= 1000 
                    ? `${threshold / 1000} kilónmetros`
                    : `${threshold} metros`;
                
                const instruction = `En ${distanceText}, ${maneuver.voiceText}`;
                speak(instruction, 5); // PRIORIDAD BAJA (guína de Navegación)
                
                console.log(`🔊 Instrucción: ${instruction}`);
                break;
            }
        }
        
        // Instrucción inmediata cuando estáamos muy cerca
        if (distanceInMeters <= 30 && state.lastInstructionDistance !== 0) {
            state.lastInstructionDistance = 0;
            const maneuver = getManeuverInfo(step);
            speak(maneuver.voiceText, 5); // PRIORIDAD BAJA (guína de Navegación)
        }
    }
    
    // =========================================================
    // OBTENER información DE MANIOBRA (TIPO WAZE)
    // =========================================================
    function getManeuverInfo(step) {
        const instruction = step.instruction || step.name || '';
        const instructionLower = instruction.toLowerCase();
        
        // Detectar tipo de maniobra
        let icon = 'fas fa-arrow-up';
        let text = 'Continúna recto';
        let voiceText = 'continúna recto';
        
        // Giros a la derecha
        if (instructionLower.includes('derecha') || instructionLower.includes('right')) {
            if (instructionLower.includes('ligera') || instructionLower.includes('slight')) {
                icon = 'fas fa-arrow-right';
                text = 'Gira ligeramente a la derecha';
                voiceText = 'gira ligeramente a la derecha';
            } else {
                icon = 'fas fa-arrow-right';
                text = 'Gira a la derecha';
                voiceText = 'gira a la derecha';
            }
        }
        // Giros a la izquierda
        else if (instructionLower.includes('izquierda') || instructionLower.includes('left')) {
            if (instructionLower.includes('ligera') || instructionLower.includes('slight')) {
                icon = 'fas fa-arrow-left';
                text = 'Gira ligeramente a la izquierda';
                voiceText = 'gira ligeramente a la izquierda';
            } else {
                icon = 'fas fa-arrow-left';
                text = 'Gira a la izquierda';
                voiceText = 'gira a la izquierda';
            }
        }
        // Rotondas
        else if (instructionLower.includes('rotonda') || instructionLower.includes('roundabout')) {
            icon = 'fas fa-circle-notch';
            text = 'Toma la rotonda';
            voiceText = 'toma la rotonda';
        }
        // Salidas
        else if (instructionLower.includes('salida') || instructionLower.includes('exit')) {
            icon = 'fas fa-sign-out-alt';
            text = 'Toma la salida';
            voiceText = 'toma la salida';
        }
        // Continuar en calle
        else if (instructionLower.includes('continua') || instructionLower.includes('continue')) {
            icon = 'fas fa-arrow-up';
            text = step.name || 'Continúna por estáa vína';
            voiceText = `continúna por ${step.name || 'estáa vína'}`;
        }
        // Destino
        else if (instructionLower.includes('destino') || instructionLower.includes('destination') ||
                 instructionLower.includes('llegaste') || instructionLower.includes('arrived')) {
            icon = 'fas fa-map-marker-alt';
            text = 'Has llegado a tu destino';
            voiceText = 'has llegado a tu destino';
        }
        // Incorporación
        else if (instructionLower.includes('incorpora') || instructionLower.includes('merge')) {
            icon = 'fas fa-compress-arrows-alt';
            text = 'Incorpónrate';
            voiceText = 'incorpónrate a la vína';
        }
        // Recto por defecto
        else {
            text = step.name || instruction || 'Continúna por estáa vína';
            voiceText = step.name ? `continúna por ${step.name}` : 'continúna recto';
        }
        
        return { icon, text, voiceText };
    }
    
    // =========================================================
    // CALCULAR DISTANCIA ENTRE DOS PUNTOS (Haversine)
    // =========================================================
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Radio de la Tierra en km
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c; // Distancia en km
    }
    
    function toRad(degrees) {
        return degrees * (Math.PI / 180);
    }
    
    // =========================================================
    // ACTUALIZAR INSTRUCCIón ACTUAL
    // =========================================================
    function updateCurrentInstruction() {
        if (!state.currentStep) return;
        
        const maneuver = getManeuverInfo(state.currentStep);
        const instructionMain = document.getElementById('instruction-main');
        const instructionIcon = document.getElementById('instruction-icon');
        
        if (instructionMain) {
            instructionMain.textContent = maneuver.text;
        }
        
        if (instructionIcon) {
            instructionIcon.innerHTML = `<i class="${maneuver.icon}"></i>`;
        }
    }

    // =========================================================
    // MANEJAR PROXIMIDAD AL Destino
    // =========================================================
    function handleNearDestáination() {
        if (!state.nearDestáinationNotified) {
            state.nearDestáinationNotified = true;
            showNotification('¡estásts cerca del destino!', 'success');
            speak('estásts cerca del destino', 3); // PRIORIDAD MEDIA (notificación importante)
            
            // Registrar evento
            logNavigationEvent('destination_near', {
                distance_remaining: state.distanceRemaining
            });
        }
    }

    // =========================================================
    // REGISTRAR EVENTO DE Navegación
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
            instructionMain.textContent = route.steps[0].name || 'Sigue por estáa va';
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
        
        // Validar que km sea un núnmero vstlido
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

    // Exponer funciones para interceptación de persistencia
    window.startNavigation = startNavigation;
    window.pauseNavigation = pauseNavigation;
    window.resumeNavigation = resumeNavigation;
    window.showNotification = showNotification;
    window.updateActionButton = updateActionButton;

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
            // Activar información de trstfico
            showNotification('Cargando información de trstfico...', 'info');
            
            try {
                // opción³n 1: Usar capa de transporte de OpenStreetMap (muestára vínas principales)
                if (!state.trafficLayer) {
                    state.trafficLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: ' OpenStreetMap contributors',
                        maxZoom: CONFIG.MAP_MAX_ZOOM,
                        className: 'traffic-overlay',
                        opacity: 0.7
                    });
                }
                
                state.trafficLayer.addTo(state.map);
                
                // Simular datos de trstfico basados en hora del da
                const trafficLevel = getTrafficLevelByTime();
                displayTrafficInfo(trafficLevel);
                
                if (button) {
                    button.classList.add('active');
                }
                
                showNotification(`trstfico ${trafficLevel.label} en tu ruta`, 'success');
                console.log(' Vista de trstfico activada');
                
                // Recalcular ETA considerando trstfico
                if (state.route && trafficLevel.multiplier > 1) {
                    const adjustedETA = state.etaSeconds * trafficLevel.multiplier;
                    updateETADisplay(adjustedETA);
                    showNotification(`ETA ajustado por trstfico: +${Math.round((adjustedETA - state.etaSeconds) / 60)} min`, 'warning');
                }
                
            } catch (error) {
                console.error('Error al activar trstfico:', error);
                showNotification('Error al cargar información de trstfico', 'error');
            }
            
        } else {
            // Desactivar capa de trstfico
            if (state.trafficLayer) {
                state.map.removeLayer(state.trafficLayer);
            }
            
            // Ocultar info de trstfico
            hideTrafficInfo();
            
            if (button) {
                button.classList.remove('active');
            }
            
            // Restáaurar ETA original
            if (state.route) {
                updateETADisplay(state.route.duration_seconds);
            }
            
            showNotification('Vista de trstfico desactivada', 'info');
            console.log(' Capa de trstfico desactivada');
        }
    };
    
    // =========================================================
    // OBTENER NIVEL DE trstfico SEGN LA HORA
    // =========================================================
    function getTrafficLevelByTime() {
        const now = new Date();
        const hour = now.getHours();
        const dayOfWeek = now.getDay(); // 0 = Domingo, 6 = Sbado
        
        // Fin de semana - menos trstfico
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
        
        // Restáo del da - trstfico normal
        return { level: 'low', label: 'Fluido', color: '#10b981', multiplier: 1.0 };
    }
    
    // =========================================================
    // MOSTRAR información DE trstfico EN UI
    // =========================================================
    function displayTrafficInfo(trafficLevel) {
        // Buscar o crear elemento de info de trstfico
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
                trstfico ${trafficLevel.label}
            </div>
        `;
        
        trafficInfo.style.display = 'flex';
    }
    
    // Ocultar info de trstfico cuando se desactive
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
        showNotification('Función no implementada. Use "Reportar problema" del menú.', 'warning');
        window.toggleMenu();
    };

    window.viewOrderDetails = function() {
        window.togglePanel();
        window.toggleMenu();
    };

    window.cancelNavigation = function() {
        // Obtener información de progreso actual
        if (state.navigationSession) {
            const distance = state.totalDistance ? (state.totalDistance / 1000).toFixed(2) + ' km' : '-';
            const time = state.elapsedTime ? formatTime(state.elapsedTime) : '-';
            const percent = state.progressPercentage ? state.progressPercentage.toFixed(1) + '%' : '-';
            
            // Actualizar modal con datos de progreso
            if (typeof updateCancellationProgress === 'function') {
                updateCancellationProgress(distance, time, percent);
            }
        }
        
        // Mostrar modal
        $('#cancelNavigationModal').modal('show');
    };

    // Procesar cancelación desde el modal
    window.processCancellation = async function(reason, notes) {
        try {
            const position = await getCurrentPosition();
            
            const cancelData = {
                delivery_id: state.deliveryId,
                reason: reason,
                notes: notes,
                latitude: position.coords.latitude,
                longitude: position.coords.longitude
            };
            
            const response = await fetch(`${CONFIG.BASE_URL}/delivery/api/navigation_actions.php?action=cancel_navigation`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(cancelData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Cerrar modal
                $('#cancelNavigationModal').modal('hide');
                
                // Detener navegación
                stopNavigation();
                
                // Notificación de éxito
                voiceHelper.speak('Navegación cancelada', 1);
                
                // Redirigir después de un breve delay
                setTimeout(() => {
                    window.location.href = `${CONFIG.BASE_URL}/delivery/orders.php`;
                }, 1500);
            } else {
                throw new Error(result.error || 'Error al cancelar navegación');
            }
        } catch (error) {
            console.error('Error al cancelar navegación:', error);
            alert('Error al cancelar la navegación: ' + error.message);
            
            // Rehabilitar botón
            const confirmBtn = document.getElementById('confirmCancellationBtn');
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-check"></i> Sí, Cancelar Navegación';
            }
        }
    };

    // Reportar problema
    window.reportProblem = function() {
        // Mostrar modal
        $('#reportProblemModal').modal('show');
    };

    // Enviar reporte de problema desde el modal
    window.submitProblemReport = async function(problemData) {
        try {
            const position = await getCurrentPosition();
            
            // Crear FormData para soportar foto
            const formData = new FormData();
            formData.append('delivery_id', state.deliveryId);
            formData.append('problem_type', problemData.problem_type);
            formData.append('title', problemData.title);
            formData.append('description', problemData.description);
            formData.append('severity', problemData.severity);
            formData.append('latitude', position.coords.latitude);
            formData.append('longitude', position.coords.longitude);
            
            // Agregar foto si existe
            if (problemData.photo) {
                formData.append('photo', problemData.photo);
            }
            
            const response = await fetch(`${CONFIG.BASE_URL}/delivery/api/navigation_actions.php?action=report_problem`, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Cerrar modal
                $('#reportProblemModal').modal('hide');
                
                // Notificación de éxito
                voiceHelper.speak('Problema reportado exitosamente', 1);
                alert('Problema reportado exitosamente. ID: ' + result.report_id);
                
                // Registrar en el estado
                if (!state.problemReports) {
                    state.problemReports = [];
                }
                state.problemReports.push({
                    id: result.report_id,
                    type: problemData.problem_type,
                    severity: problemData.severity,
                    timestamp: new Date().toISOString()
                });
            } else {
                throw new Error(result.error || 'Error al reportar problema');
            }
        } catch (error) {
            console.error('Error al reportar problema:', error);
            alert('Error al reportar el problema: ' + error.message);
        } finally {
            // Rehabilitar botón
            const submitBtn = document.getElementById('submitProblemBtn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Reporte';
            }
        }
    };

    // Función auxiliar para obtener posición actual
    function getCurrentPosition() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocalización no disponible'));
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                position => resolve(position),
                error => reject(error),
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        });
    }

    // Función auxiliar para formatear tiempo
    function formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        if (hours > 0) {
            return `${hours}h ${minutes}m`;
        } else if (minutes > 0) {
            return `${minutes}m ${secs}s`;
        } else {
            return `${secs}s`;
        }
    }

    window.confirmExit = function() {
        if (state.isNavigating) {
            if (confirm('Deseas salir de la Navegación? El progreso se guardarst.')) {
                stopNavigation();
                window.location.href = `${CONFIG.BASE_URL}/delivery/orders.php`;
            }
        } else {
            window.location.href = `${CONFIG.BASE_URL}/delivery/orders.php`;
        }
    };

    // =========================================================
    // DETENER Navegación
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
        
        console.log(' Navegación detenida');
    }

    // =========================================================
    // SínNTESIS DE VOZ (ESPAñnOL MEJORADO)
    // =========================================================
    
    // Variable global para almacenar la mejor voz en españnol
    let bestáSpanishVoice = null;
    
    // Función para seleccionar la mejor voz en españnol
    function selectBestSpanishVoice() {
        const voices = window.speechSynthesis.getVoices();
        
        // Prioridad de voces (de mayor a menor calidad/naturalidad)
        const voicePriority = [
            // Voces de Google (muy naturales)
            { pattern: /google.*españnol|google.*spanish.*es/i, lang: 'es-ES', priority: 10 },
            { pattern: /google.*españnol.*mexico|google.*spanish.*mx/i, lang: 'es-MX', priority: 9 },
            { pattern: /google.*españnol.*us/i, lang: 'es-US', priority: 8 },
            
            // Voces de Microsoft (buena calidad)
            { pattern: /helena/i, lang: 'es-ES', priority: 7 },
            { pattern: /sabina/i, lang: 'es-MX', priority: 7 },
            
            // Voces de Apple (excelente calidad)
            { pattern: /monica/i, lang: 'es-ES', priority: 9 },
            { pattern: /paulina/i, lang: 'es-MX', priority: 9 },
            { pattern: /juan/i, lang: 'es-MX', priority: 8 },
            
            // Cualquier voz nativa en españnol
            { pattern: /españnol|spanish/i, lang: 'es', priority: 5 }
        ];
        
        let selectedVoice = null;
        let highestáPriority = 0;
        
        voices.forEach(voice => {
            // Solo considerar voces que sean especínficamente para españnol
            if (!voice.lang.startsWith('es-') && !voice.lang.startsWith('es')) {
                return;
            }
            
            // Excluir voces con acento ingléns
            if (voice.name.toLowerCase().includes('en-') || 
                voice.name.toLowerCase().includes('english')) {
                return;
            }
            
            // Buscar coincidencias en la prioridad
            for (const prio of voicePriority) {
                if (prio.pattern.testá(voice.name) || voice.lang.startsWith(prio.lang)) {
                    if (prio.priority > highestáPriority) {
                        highestáPriority = prio.priority;
                        selectedVoice = voice;
                    }
                }
            }
        });
        
        // Si no se encontrón ninguna voz especínfica, buscar cualquiera en españnol
        if (!selectedVoice) {
            selectedVoice = voices.find(v => 
                v.lang.startsWith('es-') || v.lang === 'es'
            );
        }
        
        bestáSpanishVoice = selectedVoice;
        
        if (selectedVoice) {
            console.log('✅ Mejor voz en españnol seleccionada:', selectedVoice.name, '(' + selectedVoice.lang + ')');
        } else {
            console.warn('⚠️ No se encontrón ninguna voz en españnol. Total de voces:', voices.length);
        }
    }
    
    // Inicializar voces cuando estáén disponibles
    if (window.speechSynthesis) {
        if (window.speechSynthesis.getVoices().length > 0) {
            selectBestSpanishVoice();
        }
        
        // Las voces pueden cargarse de forma asíncrona
        window.speechSynthesis.onvoiceschanged = selectBestSpanishVoice;
    }
        
    // =========================================================
    // SínNTESIS DE VOZ - Usa VoiceHelper
    // =========================================================
    function speak(text, priority = 5) {
        if (!state.isVoiceEnabled) {
            console.log('🔇 Voz desactivada por usuario');
            return;
        }
        
        if (state.voiceHelper) {
            state.voiceHelper.speak(text, { priority }).catch(err => {
                console.error('Error al hablar:', err);
            });
        } else {
            console.warn('⚠️ VoiceHelper no inicializado');
        }
    }
    
    // Función de compatibilidad para cóndigo antiguo
    window.speak = speak;

    // =========================================================
    // DETENER Navegación
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
        
        if (state.instructionCheckInterval) {
            clearInterval(state.instructionCheckInterval);
            state.instructionCheckInterval = null;
        }
        
        if (state.watchId) {
            navigator.geolocation.clearWatch(state.watchId);
            state.watchId = null;
        }
        
        console.log(' Navegación detenida');
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
