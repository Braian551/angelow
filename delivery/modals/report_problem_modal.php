<!-- Modal de Reportar Problema -->
<div class="modal fade" id="reportProblemModal" tabindex="-1" role="dialog" aria-labelledby="reportProblemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="reportProblemModalLabel">
                    <i class="fas fa-exclamation-circle"></i> Reportar Problema
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Tipo de Problema -->
                <div class="form-group">
                    <label for="problemType">
                        <i class="fas fa-list"></i> Tipo de Problema <span class="text-danger">*</span>
                    </label>
                    <select class="form-control" id="problemType" required>
                        <option value="">Selecciona el tipo de problema...</option>
                        <option value="route_blocked" data-icon="fa-road-barrier">Ruta Bloqueada</option>
                        <option value="wrong_address" data-icon="fa-map-marker-question">Dirección Incorrecta</option>
                        <option value="gps_error" data-icon="fa-satellite-dish">Error de GPS</option>
                        <option value="traffic_jam" data-icon="fa-cars">Tráfico Pesado</option>
                        <option value="road_closed" data-icon="fa-road-circle-xmark">Vía Cerrada</option>
                        <option value="vehicle_issue" data-icon="fa-car-burst">Problema del Vehículo</option>
                        <option value="weather" data-icon="fa-cloud-rain">Condición Climática</option>
                        <option value="customer_issue" data-icon="fa-user-xmark">Problema con Cliente</option>
                        <option value="app_error" data-icon="fa-mobile-screen-button">Error de la App</option>
                        <option value="other" data-icon="fa-circle-question">Otro</option>
                    </select>
                </div>

                <!-- Severidad -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-exclamation-triangle"></i> Severidad <span class="text-danger">*</span>
                    </label>
                    <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                        <label class="btn btn-outline-success flex-fill">
                            <input type="radio" name="severity" value="low" id="severityLow">
                            <i class="fas fa-circle-info"></i> Baja
                        </label>
                        <label class="btn btn-outline-warning flex-fill active">
                            <input type="radio" name="severity" value="medium" id="severityMedium" checked>
                            <i class="fas fa-exclamation"></i> Media
                        </label>
                        <label class="btn btn-outline-orange flex-fill">
                            <input type="radio" name="severity" value="high" id="severityHigh">
                            <i class="fas fa-exclamation-triangle"></i> Alta
                        </label>
                        <label class="btn btn-outline-danger flex-fill">
                            <input type="radio" name="severity" value="critical" id="severityCritical">
                            <i class="fas fa-circle-exclamation"></i> Crítica
                        </label>
                    </div>
                </div>

                <!-- Título -->
                <div class="form-group">
                    <label for="problemTitle">
                        <i class="fas fa-heading"></i> Título <span class="text-danger">*</span>
                    </label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="problemTitle" 
                        placeholder="Ej: Calle bloqueada por construcción"
                        maxlength="255"
                        required
                    >
                    <small class="form-text text-muted">
                        <span id="titleCharCount">0</span>/255 caracteres
                    </small>
                </div>

                <!-- Descripción -->
                <div class="form-group">
                    <label for="problemDescription">
                        <i class="fas fa-align-left"></i> Descripción Detallada <span class="text-danger">*</span>
                    </label>
                    <textarea 
                        class="form-control" 
                        id="problemDescription" 
                        rows="5" 
                        placeholder="Describe el problema con el mayor detalle posible..."
                        required
                    ></textarea>
                    <small class="form-text text-muted">
                        Incluye detalles importantes como ubicación específica, referencias, etc.
                    </small>
                </div>

                <!-- Foto de Evidencia -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-camera"></i> Foto de Evidencia (Opcional)
                    </label>
                    <div class="custom-file">
                        <input 
                            type="file" 
                            class="custom-file-input" 
                            id="problemPhoto" 
                            accept="image/*"
                            capture="environment"
                        >
                        <label class="custom-file-label" for="problemPhoto">Seleccionar foto...</label>
                    </div>
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> Máximo 5MB. Formatos: JPG, PNG, GIF
                    </small>
                </div>

                <!-- Vista Previa de Foto -->
                <div id="photoPreview" class="text-center mb-3" style="display:none;">
                    <img id="previewImage" src="" alt="Vista previa" class="img-fluid rounded" style="max-height: 200px;">
                    <button type="button" class="btn btn-sm btn-danger mt-2" id="removePhotoBtn">
                        <i class="fas fa-trash"></i> Eliminar Foto
                    </button>
                </div>

                <!-- Información de Ubicación -->
                <div class="alert alert-info mb-0">
                    <i class="fas fa-map-marker-alt"></i> 
                    <strong>Ubicación:</strong> Se adjuntará tu ubicación actual automáticamente.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-danger" id="submitProblemBtn">
                    <i class="fas fa-paper-plane"></i> Enviar Reporte
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos del Modal -->
<style>
#reportProblemModal .modal-content {
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

#reportProblemModal .modal-header {
    border-radius: 12px 12px 0 0;
}

#reportProblemModal .btn-outline-orange {
    color: #fd7e14;
    border-color: #fd7e14;
}

#reportProblemModal .btn-outline-orange:hover,
#reportProblemModal .btn-outline-orange.active {
    background-color: #fd7e14;
    border-color: #fd7e14;
    color: #fff;
}

#reportProblemModal .form-control:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

#reportProblemModal #photoPreview {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}

#reportProblemModal .custom-file-label::after {
    content: "Buscar";
}

#submitProblemBtn {
    font-weight: bold;
}
</style>

<!-- JavaScript del Modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('reportProblemModal');
    const typeSelect = document.getElementById('problemType');
    const titleInput = document.getElementById('problemTitle');
    const descriptionTextarea = document.getElementById('problemDescription');
    const photoInput = document.getElementById('problemPhoto');
    const photoPreview = document.getElementById('photoPreview');
    const previewImage = document.getElementById('previewImage');
    const removePhotoBtn = document.getElementById('removePhotoBtn');
    const submitBtn = document.getElementById('submitProblemBtn');
    const titleCharCount = document.getElementById('titleCharCount');
    
    // Contador de caracteres del título
    if (titleInput) {
        titleInput.addEventListener('input', function() {
            titleCharCount.textContent = this.value.length;
        });
    }
    
    // Actualizar label del archivo
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Seleccionar foto...';
            const label = this.nextElementSibling;
            label.textContent = fileName;
            
            // Mostrar vista previa
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    photoPreview.style.display = 'block';
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    }
    
    // Eliminar foto
    if (removePhotoBtn) {
        removePhotoBtn.addEventListener('click', function() {
            photoInput.value = '';
            photoInput.nextElementSibling.textContent = 'Seleccionar foto...';
            photoPreview.style.display = 'none';
            previewImage.src = '';
        });
    }
    
    // Manejar envío
    if (submitBtn) {
        submitBtn.addEventListener('click', function() {
            const problemType = typeSelect.value;
            const title = titleInput.value.trim();
            const description = descriptionTextarea.value.trim();
            const severity = document.querySelector('input[name="severity"]:checked')?.value || 'medium';
            const photo = photoInput.files[0] || null;
            
            // Validaciones
            if (!problemType) {
                alert('Por favor selecciona el tipo de problema');
                return;
            }
            
            if (!title) {
                alert('Por favor ingresa un título');
                titleInput.focus();
                return;
            }
            
            if (!description) {
                alert('Por favor describe el problema');
                descriptionTextarea.focus();
                return;
            }
            
            // Deshabilitar botón
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            
            // Preparar datos
            const problemData = {
                problem_type: problemType,
                title: title,
                description: description,
                severity: severity,
                photo: photo
            };
            
            // Llamar función global
            if (typeof window.submitProblemReport === 'function') {
                window.submitProblemReport(problemData);
            } else {
                console.error('Función submitProblemReport no encontrada');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Reporte';
            }
        });
    }
    
    // Resetear modal al cerrar
    $(modal).on('hidden.bs.modal', function() {
        typeSelect.value = '';
        titleInput.value = '';
        descriptionTextarea.value = '';
        photoInput.value = '';
        photoInput.nextElementSibling.textContent = 'Seleccionar foto...';
        photoPreview.style.display = 'none';
        previewImage.src = '';
        titleCharCount.textContent = '0';
        
        // Resetear severidad a media
        document.getElementById('severityMedium').checked = true;
        document.querySelectorAll('.btn-group-toggle label').forEach(label => {
            label.classList.remove('active');
        });
        document.querySelector('label[for="severityMedium"]').classList.add('active');
        
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Reporte';
    });
});
</script>
