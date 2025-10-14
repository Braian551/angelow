# EJEMPLOS DE USO - Sistema de Cancelación y Reportes
> Archivo de referencia rápida para desarrolladores

---

## EJEMPLO 1: Cancelar Navegación desde JavaScript

```javascript

// Forma básica: mostrar modal
window.cancelNavigation();

// El modal se encarga de recoger datos y llamar a:
window.processCancellation(reason, notes);

// Ejemplo de llamada manual (no recomendado, usar modal):
window.processCancellation('customer_unavailable', 'Cliente no responde llamadas ni mensajes');

// =====================================================
// EJEMPLO 2: Reportar Problema desde JavaScript
// =====================================================

// Forma básica: mostrar modal
window.reportProblem();

// El modal se encarga de recoger datos y llamar a:
window.submitProblemReport(problemData);

// Ejemplo de objeto problemData:
const exampleProblemData = {
    problem_type: 'route_blocked',
    title: 'Calle bloqueada por construcción',
    description: 'Av. Principal altura 1234 tiene obras, no se puede pasar',
    severity: 'high',
    photo: File // objeto File del input type="file"
};

window.submitProblemReport(exampleProblemData);
```

---

## EJEMPLO 3: Llamada Directa a API (fetch)

```javascript

// Cancelar navegación
async function cancelNavigationExample() {
    const response = await fetch(`${CONFIG.BASE_URL}/delivery/api/navigation_actions.php?action=cancel_navigation`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            delivery_id: 9,
            reason: 'technical_issue',
            notes: 'App no responde correctamente',
            latitude: -12.046374,
            longitude: -77.042793
        })
    });
    
    const result = await response.json();
    console.log(result);
    // { success: true, message: "...", cancellation_id: 123 }
}

// Reportar problema (con foto)
async function reportProblemExample() {
    const formData = new FormData();
    formData.append('delivery_id', 9);
    formData.append('problem_type', 'gps_error');
    formData.append('title', 'GPS muestra ubicación incorrecta');
    formData.append('description', 'El GPS me ubica 2 calles más allá de donde estoy realmente');
    formData.append('severity', 'medium');
    formData.append('latitude', -12.046374);
    formData.append('longitude', -77.042793);
    
    // Adjuntar foto (si existe)
    const photoInput = document.getElementById('problemPhoto');
    if (photoInput.files[0]) {
        formData.append('photo', photoInput.files[0]);
    }
    
    const response = await fetch(`${CONFIG.BASE_URL}/delivery/api/navigation_actions.php?action=report_problem`, {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    console.log(result);
    // { success: true, message: "...", report_id: 456 }
}

// Obtener tipos de problemas
async function getProblemTypesExample() {
    const response = await fetch(`${CONFIG.BASE_URL}/delivery/api/navigation_actions.php?action=get_problem_types`);
    const result = await response.json();
    console.log(result);
    /*
    {
        success: true,
        data: [
            { value: 'route_blocked', label: 'Ruta Bloqueada', icon: 'fa-road-barrier' },
            { value: 'wrong_address', label: 'Dirección Incorrecta', icon: 'fa-map-marker-question' },
            ...
        ]
    }
    */
}

// Obtener razones de cancelación
async function getCancellationReasonsExample() {
    const response = await fetch(`${CONFIG.BASE_URL}/delivery/api/navigation_actions.php?action=get_cancellation_reasons`);
    const result = await response.json();
    console.log(result);
    /*
    {
        success: true,
        data: [
            { value: 'order_cancelled', label: 'Pedido Cancelado por Cliente', icon: 'fa-ban' },
            { value: 'customer_unavailable', label: 'Cliente No Disponible', icon: 'fa-user-slash' },
            ...
        ]
    }
    */
}
```

---

## EJEMPLO 4: Consultas SQL Útiles

```sql

-- Ver últimas cancelaciones con detalles
SELECT 
    c.*,
    o.order_number,
    u.name as driver_name,
    od.status as delivery_status
FROM delivery_navigation_cancellations c
JOIN order_deliveries od ON c.delivery_id = od.id
JOIN orders o ON od.order_id = o.id
JOIN users u ON od.driver_id = u.id
ORDER BY c.created_at DESC
LIMIT 10;

-- Ver problemas reportados hoy con severidad alta o crítica
SELECT 
    p.*,
    o.order_number,
    u.name as driver_name
FROM delivery_problem_reports p
JOIN order_deliveries od ON p.delivery_id = od.id
JOIN orders o ON od.order_id = o.id
JOIN users u ON od.driver_id = u.id
WHERE DATE(p.created_at) = CURDATE()
AND p.severity IN ('high', 'critical')
ORDER BY 
    FIELD(p.severity, 'critical', 'high'),
    p.created_at DESC;

-- Estadísticas de cancelación por razón
SELECT 
    reason,
    COUNT(*) as total_cancelaciones,
    AVG(progress_percentage) as progreso_promedio,
    AVG(TIMESTAMPDIFF(MINUTE, start_time, cancelled_at)) as minutos_promedio
FROM delivery_navigation_cancellations
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY reason
ORDER BY total_cancelaciones DESC;

-- Análisis de problemas por tipo y severidad
SELECT 
    problem_type,
    severity,
    COUNT(*) as total,
    COUNT(CASE WHEN photo_path IS NOT NULL THEN 1 END) as con_foto,
    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resueltos
FROM delivery_problem_reports
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY problem_type, severity
ORDER BY total DESC;

-- Vista consolidada de issues (cancelaciones + problemas)
SELECT * FROM v_navigation_issues
WHERE DATE(created_at) = CURDATE()
ORDER BY created_at DESC;
```

---

## EJEMPLO 5: Llamar Procedimientos desde PHP

```php
// Cancelar navegación
$stmt = $conn->prepare("CALL CancelNavigation(?, ?, ?, ?, ?, ?, ?)");

$delivery_id = 9;
$driver_id = '6862b7448112f';
$reason = 'customer_unavailable';
$notes = 'Cliente no responde';
$latitude = -12.046374;
$longitude = -77.042793;
$device_info = json_encode([
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'ip_address' => $_SERVER['REMOTE_ADDR']
]);

$stmt->bind_param(
    "isssdds",
    $delivery_id,
    $driver_id,
    $reason,
    $notes,
    $latitude,
    $longitude,
    $device_info
);

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo "Cancelación ID: " . $row['cancellation_id'];
echo "Mensaje: " . $row['message'];
?>

<?php
// Reportar problema
$stmt = $conn->prepare("CALL ReportProblem(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$delivery_id = 9;
$driver_id = '6862b7448112f';
$problem_type = 'route_blocked';
$title = 'Calle bloqueada';
$description = 'Obras en la Av. Principal';
$severity = 'high';
$latitude = -12.046374;
$longitude = -77.042793;
$photo_path = 'uploads/problem_reports/problem_9_1697235467.jpg';
$device_info = json_encode([
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'ip_address' => $_SERVER['REMOTE_ADDR']
]);

$stmt->bind_param(
    "isssssddss",
    $delivery_id,
    $driver_id,
    $problem_type,
    $title,
    $description,
    $severity,
    $latitude,
    $longitude,
    $photo_path,
    $device_info
);

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo "Reporte ID: " . $row['report_id'];
echo "Mensaje: " . $row['message'];
?>
```

---

## EJEMPLO 6: Actualizar Modal con Datos Dinámicos

```javascript

// Actualizar progreso en modal de cancelación
updateCancellationProgress('3.5 km', '12m 34s', '45.2%');

// Cambiar dinámicamente las opciones del select
const problemTypeSelect = document.getElementById('problemType');
problemTypeSelect.innerHTML = '<option value="">Cargando...</option>';

fetch(`${CONFIG.BASE_URL}/delivery/api/navigation_actions.php?action=get_problem_types`)
    .then(res => res.json())
    .then(data => {
        problemTypeSelect.innerHTML = '<option value="">Selecciona...</option>';
        data.data.forEach(type => {
            const option = document.createElement('option');
            option.value = type.value;
            option.textContent = type.label;
            option.setAttribute('data-icon', type.icon);
            problemTypeSelect.appendChild(option);
        });
    });
```

---

## EJEMPLO 7: Capturar Foto desde Cámara (Móvil)

```javascript

// El input ya tiene capture="environment" en el HTML
// Esto activa la cámara automáticamente en móviles

// Para capturar desde JavaScript:
async function capturePhoto() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: { facingMode: 'environment' } 
        });
        
        const video = document.createElement('video');
        video.srcObject = stream;
        await video.play();
        
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        
        stream.getTracks().forEach(track => track.stop());
        
        canvas.toBlob(blob => {
            const file = new File([blob], 'photo.jpg', { type: 'image/jpeg' });
            
            // Asignar al input
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            document.getElementById('problemPhoto').files = dataTransfer.files;
            
            // Disparar evento change para mostrar preview
            document.getElementById('problemPhoto').dispatchEvent(new Event('change'));
        }, 'image/jpeg');
    } catch (error) {
        console.error('Error al capturar foto:', error);
        alert('No se pudo acceder a la cámara');
    }
}
```

---

## EJEMPLO 8: Validaciones Personalizadas

```javascript

// Validar antes de enviar cancelación
window.processCancellation = async function(reason, notes) {
    // Validación personalizada
    if (reason === 'other' && notes.trim().length < 20) {
        alert('Para "Otra Razón", proporciona al menos 20 caracteres en las notas');
        return;
    }
    
    // Validar que hay navegación activa
    if (!state.navigationSession || !state.isNavigating) {
        alert('No hay navegación activa para cancelar');
        return;
    }
    
    // Continuar con el proceso normal...
    // ... código existente ...
};

// Validar antes de enviar reporte
window.submitProblemReport = async function(problemData) {
    // Validación de foto (opcional pero si existe, validar tamaño)
    if (problemData.photo) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (problemData.photo.size > maxSize) {
            alert('La foto es demasiado grande. Máximo 5MB');
            return;
        }
        
        // Validar tipo
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(problemData.photo.type)) {
            alert('Tipo de archivo no válido. Solo se permiten imágenes JPG, PNG o GIF');
            return;
        }
    }
    
    // Validar severidad crítica requiere descripción detallada
    if (problemData.severity === 'critical' && problemData.description.length < 50) {
        if (!confirm('Problemas críticos requieren descripción detallada. ¿Continuar de todos modos?')) {
            return;
        }
    }
    
    // Continuar con el proceso normal...
    // ... código existente ...
};
```

---

## EJEMPLO 9: Manejo de Errores Avanzado

```javascript

window.processCancellation = async function(reason, notes) {
    const confirmBtn = document.getElementById('confirmCancellationBtn');
    
    try {
        // Obtener posición con timeout personalizado
        const position = await Promise.race([
            getCurrentPosition(),
            new Promise((_, reject) => 
                setTimeout(() => reject(new Error('Timeout de geolocalización')), 15000)
            )
        ]);
        
        // Mostrar indicador de carga
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelando...';
        
        const response = await fetch(...);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Error desconocido del servidor');
        }
        
        // Éxito
        $('#cancelNavigationModal').modal('hide');
        stopNavigation();
        voiceHelper.speak('Navegación cancelada', 1);
        
        setTimeout(() => {
            window.location.href = `${CONFIG.BASE_URL}/delivery/orders.php`;
        }, 1500);
        
    } catch (error) {
        console.error('Error completo:', error);
        
        // Mensajes específicos según el tipo de error
        let userMessage = 'Error al cancelar navegación: ';
        
        if (error.message.includes('geolocalización')) {
            userMessage += 'No se pudo obtener tu ubicación. Verifica los permisos de GPS.';
        } else if (error.message.includes('HTTP')) {
            userMessage += 'Error de conexión con el servidor.';
        } else if (error.message.includes('NetworkError')) {
            userMessage += 'Sin conexión a internet. Verifica tu red.';
        } else {
            userMessage += error.message;
        }
        
        alert(userMessage);
        
        // Rehabilitar botón
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="fas fa-check"></i> Sí, Cancelar Navegación';
    }
};
```

---

## EJEMPLO 10: Testing Manual desde Consola del Navegador

```javascript

// Test 1: Simular cancelación
(async function testCancellation() {
    console.log('🧪 Test: Cancelación de navegación');
    
    // Simular estado de navegación
    window.state = window.state || {};
    window.state.deliveryId = 9;
    window.state.navigationSession = { id: 123 };
    window.state.totalDistance = 3500; // metros
    window.state.elapsedTime = 754; // segundos
    window.state.progressPercentage = 45.2;
    
    // Abrir modal
    window.cancelNavigation();
    
    // Esperar y simular confirmación
    setTimeout(() => {
        document.getElementById('cancellationReason').value = 'customer_unavailable';
        document.getElementById('cancellationNotes').value = 'Cliente no responde llamadas';
        document.getElementById('confirmCancellationBtn').click();
    }, 2000);
})();

// Test 2: Simular reporte de problema
(async function testProblemReport() {
    console.log('🧪 Test: Reporte de problema');
    
    window.state = window.state || {};
    window.state.deliveryId = 9;
    
    // Abrir modal
    window.reportProblem();
    
    // Esperar y simular envío
    setTimeout(() => {
        document.getElementById('problemType').value = 'route_blocked';
        document.getElementById('problemTitle').value = 'Test: Ruta bloqueada';
        document.getElementById('problemDescription').value = 'Esta es una prueba del sistema de reportes';
        document.getElementById('severityHigh').checked = true;
        document.getElementById('submitProblemBtn').click();
    }, 2000);
})();

// Test 3: Verificar respuesta de API
fetch('/delivery/api/navigation_actions.php?action=get_problem_types')
    .then(res => res.json())
    .then(data => console.table(data.data));

fetch('/delivery/api/navigation_actions.php?action=get_cancellation_reasons')
    .then(res => res.json())
    .then(data => console.table(data.data));
```

---

**Fin de los ejemplos. Archivo de referencia para desarrollo.**
