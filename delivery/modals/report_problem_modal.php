<!-- Modal de Reportar Problema (Glass Minimal) -->
<div class="modal fade" id="reportProblemModal" tabindex="-1" role="dialog" aria-labelledby="reportProblemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content glass-modal">
            <div class="modal-header glass-header">
                <div class="d-flex align-items-center">
                    <div class="header-icon"><i class="fas fa-triangle-exclamation"></i></div>
                    <h5 class="modal-title mb-0" id="reportProblemModalLabel">Reportar Problema</h5>
                </div>
                <button type="button" class="close glass-close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Tipo de Problema -->
                <div class="form-group">
                    <label class="glass-label" for="problemType">
                        <i class="fas fa-list"></i> Tipo de Problema <span class="text-danger">*</span>
                    </label>
                    <select class="form-control d-none" id="problemType" required>
                        <option value="">Selecciona el tipo de problema...</option>
                        <option value="route_blocked" data-icon="fa-road-barrier">Ruta Bloqueada</option>
                        <option value="wrong_address" data-icon="fa-map-marker-question">Dirección Incorrecta</option>
                        <option value="gps_error" data-icon="fa-satellite-dish">Error de GPS</option>
                        <option value="traffic_jam" data-icon="fa-cars">Tráfico Pesado</option>
                        <option value="road_closed" data-icon="fa-road-circle-xmark">Vía Cerrada</option>
                        <option value="vehicle_issue" data-icon="fa-car-burst">Problema del Vehículo</option>
                        <option value="weather" data-icon="fa-cloud-rain">Condición Climática</option>
                        <option value="customer_issue" data-icon="fa-user-xmark">Problema con Cliente</option>
                        <option value="app_error" data-icon="fa-mobile-screen-button">Error de software</option>
                        <option value="other" data-icon="fa-circle-question">Otro</option>
                    </select>
                    <div class="problem-type-grid" id="problemTypeGrid"></div>
                </div>

                <!-- Severidad -->
                <div class="form-group">
                    <label class="glass-label">
                        <i class="fas fa-signal"></i> Severidad <span class="text-danger">*</span>
                    </label>
                    <div class="severity-group" data-toggle="buttons">
                        <label class="severity-pill severity-low">
                            <input type="radio" name="severity" value="low" id="severityLow">
                            <i class="fas fa-circle-info"></i> Baja
                        </label>
                        <label class="severity-pill severity-medium active">
                            <input type="radio" name="severity" value="medium" id="severityMedium" checked>
                            <i class="fas fa-exclamation"></i> Media
                        </label>
                        <label class="severity-pill severity-high">
                            <input type="radio" name="severity" value="high" id="severityHigh">
                            <i class="fas fa-exclamation-triangle"></i> Alta
                        </label>
                        <label class="severity-pill severity-critical">
                            <input type="radio" name="severity" value="critical" id="severityCritical">
                            <i class="fas fa-circle-exclamation"></i> Crítica
                        </label>
                    </div>
                </div>

                <!-- Título -->
                <div class="form-group">
                    <label class="glass-label" for="problemTitle">
                        <i class="fas fa-heading"></i> Título <span class="text-danger">*</span>
                    </label>
                    <div class="glass-input">
                        <input 
                            type="text" 
                            class="form-control glass-control" 
                            id="problemTitle" 
                            placeholder="Ej: Calle bloqueada por construcción"
                            maxlength="255"
                            required
                        >
                        <div class="char-counter"><span id="titleCharCount">0</span>/255</div>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="form-group">
                    <label class="glass-label" for="problemDescription">
                        <i class="fas fa-align-left"></i> Descripción Detallada <span class="text-danger">*</span>
                    </label>
                    <textarea 
                        class="form-control glass-control" 
                        id="problemDescription" 
                        rows="4" 
                        placeholder="Describe el problema con el mayor detalle posible..."
                        required
                    ></textarea>
                    <small class="form-text text-muted">Incluye detalles como ubicación exacta, referencias y condiciones.</small>
                </div>

                <!-- Foto de Evidencia (Drag & Drop) -->
                <div class="form-group">
                    <label class="glass-label">
                        <i class="fas fa-camera"></i> Foto de Evidencia (Opcional)
                    </label>
                    <div class="photo-dropzone" id="photoDropzone">
                        <i class="fas fa-cloud-arrow-up"></i>
                        <p>Arrastra y suelta una imagen aquí<br><span>o haz clic para seleccionar</span></p>
                        <input 
                            type="file" 
                            class="d-none" 
                            id="problemPhoto" 
                            accept="image/*"
                            capture="environment"
                        >
                    </div>
                    <div id="photoPreview" class="photo-preview" style="display:none;">
                        <img id="previewImage" src="" alt="Vista previa">
                        <button type="button" class="btn btn-sm btn-outline-danger" id="removePhotoBtn">
                            <i class="fas fa-trash"></i> Quitar foto
                        </button>
                    </div>
                    <small class="form-text text-muted"><i class="fas fa-info-circle"></i> Máximo 5MB. Formatos: JPG, PNG, GIF</small>
                </div>

                <!-- Información de Ubicación -->
                <div class="glass-info">
                    <i class="fas fa-map-marker-alt"></i> Se adjuntará tu ubicación actual automáticamente.
                </div>
            </div>
            <div class="modal-footer glass-footer">
                <button type="button" class="btn btn-light glass-btn" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary glass-btn-primary" id="submitProblemBtn">
                    <i class="fas fa-paper-plane"></i> Enviar Reporte
                </button>
            </div>
        </div>
    </div>
    
</div>

<!-- Estilos del Modal (Glass Minimal) -->
<style>
:root {
    --glass-bg: rgba(255, 255, 255, 0.12);
    --glass-border: rgba(255, 255, 255, 0.25);
    --glass-shadow: rgba(0, 0, 0, 0.25);
    --soft-text: #e9eef6;
    --muted-text: #b4c0d1;
    --primary: #4c84ff;
    --primary-600: #3a6ee8;
    --danger: #ff5d5d;
    --success: #4cd964;
    --warning: #ffcc66;
    --critical: #ff4d88;
}

#reportProblemModal .modal-dialog {
    max-width: 860px;
}

.glass-modal {
    background: linear-gradient(135deg, rgba(20, 24, 35, 0.72), rgba(20, 24, 35, 0.55));
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 1px solid var(--glass-border);
    border-radius: 16px;
    box-shadow: 0 20px 40px var(--glass-shadow);
    color: var(--soft-text);
}

.glass-header {
    background: linear-gradient(120deg, rgba(76,132,255,0.18), rgba(255,93,93,0.12));
    border-bottom: 1px solid var(--glass-border);
}
.glass-footer {
    background: transparent;
    border-top: 1px solid var(--glass-border);
}
.glass-close {
    color: var(--soft-text);
    opacity: .8;
}
.glass-close:hover { opacity: 1; }

.header-icon {
    width: 36px; height: 36px; border-radius: 10px;
    display: inline-flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, rgba(255,93,93,.2), rgba(76,132,255,.2));
    margin-right: 10px; color: var(--soft-text);
}

.glass-label { color: var(--muted-text); font-weight: 600; }
.glass-input { position: relative; }
.glass-control {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    color: var(--soft-text);
    border-radius: 12px;
}
.glass-control::placeholder { color: #9fb1c9; opacity: .8; }
.glass-control:focus {
    background: rgba(255,255,255,0.16);
    border-color: rgba(76,132,255,0.55);
    box-shadow: 0 0 0 0.2rem rgba(76,132,255,0.15);
    color: var(--soft-text);
}
.char-counter {
    position: absolute; right: 10px; bottom: 8px; font-size: 12px; color: var(--muted-text);
}

.problem-type-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    grid-gap: 12px; margin-top: 10px;
}
.problem-card {
    border: 1px solid var(--glass-border);
    background: linear-gradient(145deg, rgba(255,255,255,.08), rgba(255,255,255,.06));
    padding: 14px; border-radius: 14px; cursor: pointer;
    transition: transform .15s ease, box-shadow .2s ease, border-color .2s ease;
}
.problem-card .icon { font-size: 22px; margin-bottom: 8px; }
.problem-card .title { font-weight: 600; color: var(--soft-text); }
.problem-card .hint { font-size: 12px; color: var(--muted-text); }
.problem-card:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,.18); }
.problem-card.active { border-color: rgba(76,132,255,0.7); box-shadow: 0 0 0 2px rgba(76,132,255,0.15) inset; }

.severity-group { display: flex; flex-wrap: wrap; gap: 10px; }
.severity-pill {
    border: 1px solid var(--glass-border);
    padding: 8px 12px; border-radius: 999px; cursor: pointer;
    color: var(--soft-text); user-select: none; background: var(--glass-bg);
}
.severity-pill input { display:none; }
.severity-pill.active { box-shadow: 0 0 0 2px rgba(76,132,255,0.15) inset; border-color: rgba(76,132,255,0.65); }
.severity-low { background: linear-gradient(135deg, rgba(76,217,100,.12), rgba(76,217,100,.08)); }
.severity-medium { background: linear-gradient(135deg, rgba(255,204,102,.14), rgba(255,204,102,.08)); }
.severity-high { background: linear-gradient(135deg, rgba(255,93,93,.14), rgba(255,93,93,.08)); }
.severity-critical { background: linear-gradient(135deg, rgba(255,77,136,.16), rgba(255,77,136,.08)); }

.photo-dropzone {
    border: 2px dashed rgba(255,255,255,.25);
    border-radius: 14px; padding: 18px; text-align: center; color: var(--muted-text);
    background: linear-gradient(145deg, rgba(255,255,255,.08), rgba(255,255,255,.06));
    cursor: pointer;
}
.photo-dropzone.dragover { border-color: rgba(76,132,255,.75); color: var(--soft-text); }
.photo-dropzone i { font-size: 28px; margin-bottom: 6px; display:block; }
.photo-dropzone p { margin: 0; }
.photo-dropzone span { color: var(--soft-text); }

.photo-preview { display:flex; align-items: center; gap: 12px; margin-top: 10px; }
.photo-preview img { max-height: 160px; border-radius: 10px; border: 1px solid var(--glass-border); }

.glass-info {
    background: linear-gradient(145deg, rgba(76,132,255,.12), rgba(76,132,255,.08));
    border: 1px solid rgba(76,132,255,.25);
    color: var(--soft-text); padding: 10px 12px; border-radius: 12px;
}

.glass-btn { border-radius: 10px; border: 1px solid var(--glass-border); }
.glass-btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-600));
    border: none; color: #fff; border-radius: 10px;
}
.glass-btn-primary:hover { filter: brightness(1.05); }
</style>

<!-- JavaScript del Modal (Interactividad) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('reportProblemModal');
    const typeSelect = document.getElementById('problemType');
    const typeGrid = document.getElementById('problemTypeGrid');
    const titleInput = document.getElementById('problemTitle');
    const descriptionTextarea = document.getElementById('problemDescription');
    const photoInput = document.getElementById('problemPhoto');
    const photoDropzone = document.getElementById('photoDropzone');
    const photoPreview = document.getElementById('photoPreview');
    const previewImage = document.getElementById('previewImage');
    const removePhotoBtn = document.getElementById('removePhotoBtn');
    const submitBtn = document.getElementById('submitProblemBtn');
    const titleCharCount = document.getElementById('titleCharCount');

    // Construir grid de tipos a partir del select existente
    if (typeGrid && typeSelect) {
        const options = Array.from(typeSelect.options).filter(o => o.value);
        typeGrid.innerHTML = options.map(o => {
            const icon = o.dataset.icon || 'fa-circle-question';
            return `
                <div class="problem-card" data-value="${o.value}">
                    <div class="icon"><i class="fas ${icon}"></i></div>
                    <div class="title">${o.text}</div>
                    <div class="hint">Seleccionar</div>
                </div>
            `;
        }).join('');

        typeGrid.addEventListener('click', (e) => {
            const card = e.target.closest('.problem-card');
            if (!card) return;
            const value = card.getAttribute('data-value');
            typeSelect.value = value;
            typeGrid.querySelectorAll('.problem-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
        });
    }

    // Contador de caracteres del título
    if (titleInput) {
        titleInput.addEventListener('input', function() {
            titleCharCount.textContent = this.value.length;
        });
    }

    // Photo: abrir file dialog al hacer click en dropzone
    if (photoDropzone && photoInput) {
        photoDropzone.addEventListener('click', () => photoInput.click());
        photoDropzone.addEventListener('dragover', (e) => { e.preventDefault(); photoDropzone.classList.add('dragover'); });
        photoDropzone.addEventListener('dragleave', () => photoDropzone.classList.remove('dragover'));
        photoDropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            photoDropzone.classList.remove('dragover');
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                photoInput.files = e.dataTransfer.files;
                updatePreview(e.dataTransfer.files[0]);
            }
        });
    }

    function updatePreview(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            photoPreview.style.display = 'flex';
        };
        reader.readAsDataURL(file);
    }

    // Actualizar preview al seleccionar archivo
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) updatePreview(file);
        });
    }

    // Eliminar foto
    if (removePhotoBtn) {
        removePhotoBtn.addEventListener('click', function() {
            photoInput.value = '';
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
            if (!problemType) { alert('Por favor selecciona el tipo de problema'); return; }
            if (!title) { alert('Por favor ingresa un título'); titleInput.focus(); return; }
            if (!description) { alert('Por favor describe el problema'); descriptionTextarea.focus(); return; }

            // Deshabilitar botón
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

            // Preparar datos
            const problemData = { problem_type: problemType, title, description, severity, photo };

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
        if (typeGrid) typeGrid.querySelectorAll('.problem-card').forEach(c => c.classList.remove('active'));
        titleInput.value = '';
        descriptionTextarea.value = '';
        photoInput.value = '';
        photoPreview.style.display = 'none';
        previewImage.src = '';
        titleCharCount.textContent = '0';

        // Resetear severidad a media
        document.getElementById('severityMedium').checked = true;
        document.querySelectorAll('.severity-pill').forEach(el => el.classList.remove('active'));
        document.querySelector('.severity-medium').classList.add('active');

        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Reporte';
    });

    // Cerrar el menú lateral si está abierto al abrir el modal
    $(modal).on('show.bs.modal', function() {
        const drawer = document.getElementById('menu-drawer');
        if (drawer && drawer.classList.contains('show') && typeof window.toggleMenu === 'function') {
            window.toggleMenu();
        }
    });

    // Toggle visual para pills de severidad
    document.querySelectorAll('.severity-pill').forEach(pill => {
        pill.addEventListener('click', () => {
            document.querySelectorAll('.severity-pill').forEach(el => el.classList.remove('active'));
            pill.classList.add('active');
            const input = pill.querySelector('input');
            if (input) input.checked = true;
        });
    });
});
</script>
