<!-- Modal Cancelar Navegación (Glass Minimal) -->
<div class="modal fade" id="cancelNavigationModal" tabindex="-1" role="dialog" aria-labelledby="cancelNavigationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content glass-modal">
            <div class="modal-header glass-header">
                <div class="d-flex align-items-center">
                    <div class="header-icon"><i class="fas fa-circle-exclamation"></i></div>
                    <h5 class="modal-title mb-0" id="cancelNavigationModalLabel">Cancelar Navegación</h5>
                </div>
                <button type="button" class="close glass-close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Información de Progreso -->
                <div class="glass-info" id="cancellationProgress" style="display:none;">
                    <div class="d-flex align-items-center mb-2"><i class="fas fa-chart-line mr-2"></i> Progreso Actual</div>
                    <div class="row text-center">
                        <div class="col-4">
                            <small class="text-muted">Distancia</small>
                            <p class="mb-0 font-weight-bold" id="progressDistance">-</p>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">Tiempo</small>
                            <p class="mb-0 font-weight-bold" id="progressTime">-</p>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">Completado</small>
                            <p class="mb-0 font-weight-bold" id="progressPercent">-</p>
                        </div>
                    </div>
                </div>

                <!-- Razón de Cancelación -->
                <div class="form-group">
                    <label class="glass-label" for="cancellationReason">
                        <i class="fas fa-list-check"></i> Razón de Cancelación <span class="text-danger">*</span>
                    </label>
                    <select class="form-control d-none" id="cancellationReason" required>
                        <option value="">Selecciona una razón...</option>
                        <option value="order_cancelled" data-icon="fa-ban">Pedido Cancelado por Cliente</option>
                        <option value="customer_unavailable" data-icon="fa-user-slash">Cliente No Disponible</option>
                        <option value="address_wrong" data-icon="fa-location-crosshairs">Dirección Incorrecta/No Existe</option>
                        <option value="technical_issue" data-icon="fa-screwdriver-wrench">Problema Técnico</option>
                        <option value="driver_emergency" data-icon="fa-ambulance">Emergencia del Conductor</option>
                        <option value="other" data-icon="fa-ellipsis">Otra Razón</option>
                    </select>
                    <div class="reason-grid" id="reasonGrid"></div>
                </div>

                <!-- Notas Adicionales -->
                <div class="form-group">
                    <label class="glass-label" for="cancellationNotes">
                        <i class="fas fa-comment-dots"></i> Notas Adicionales
                    </label>
                    <div class="glass-input">
                        <textarea 
                            class="form-control glass-control" 
                            id="cancellationNotes" 
                            rows="3" 
                            placeholder="Proporciona detalles adicionales sobre la cancelación..."
                            maxlength="500"
                        ></textarea>
                        <div class="char-counter"><span id="notesCharCount">0</span>/500</div>
                    </div>
                </div>

                <!-- Advertencia -->
                <div class="glass-info">
                    <i class="fas fa-info-circle"></i>
                    Esta acción cancelará la navegación y actualizará el estado de la entrega. Confirma que deseas continuar.
                </div>
            </div>
            <div class="modal-footer glass-footer">
                <button type="button" class="glass-btn glass-btn-ghost" data-dismiss="modal">
                    <i class="fas fa-times"></i> No, Continuar
                </button>
                <button type="button" class="glass-btn-primary" id="confirmCancellationBtn">
                    <i class="fas fa-check"></i> Sí, Cancelar Navegación
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos del Modal (Glass Minimal) -->
<style>
#cancelNavigationModal .modal-dialog { max-width: 720px; }
/* Reutiliza variables y estilos base del otro modal glass */
.glass-modal { background: linear-gradient(135deg, rgba(20, 24, 35, 0.72), rgba(20, 24, 35, 0.55)); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid rgba(255,255,255,0.25); border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.25); color: #e9eef6; }
.glass-header { background: linear-gradient(120deg, rgba(255,204,102,0.18), rgba(255,93,93,0.12)); border-bottom: 1px solid rgba(255,255,255,0.25); }
.glass-footer { background: transparent; border-top: 1px solid rgba(255,255,255,0.25); }
.glass-close { color: #e9eef6; opacity: .85; }
.glass-close:hover { opacity: 1; }
.header-icon { width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(255,204,102,.25), rgba(255,93,93,.2)); margin-right: 10px; color: #e9eef6; }
.glass-label { color: #b4c0d1; font-weight: 600; }
.glass-input { position: relative; }
.glass-control { background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.25); color: #e9eef6; border-radius: 12px; }
.glass-control::placeholder { color: #9fb1c9; opacity: .85; }
.glass-control:focus { background: rgba(255,255,255,0.16); border-color: rgba(76,132,255,0.55); box-shadow: 0 0 0 0.2rem rgba(76,132,255,0.15); color: #e9eef6; }
.char-counter { position: absolute; right: 10px; bottom: 8px; font-size: 12px; color: #b4c0d1; }
.glass-info { background: linear-gradient(145deg, rgba(76,132,255,.12), rgba(76,132,255,.08)); border: 1px solid rgba(76,132,255,.25); color: #e9eef6; padding: 10px 12px; border-radius: 12px; }
.glass-btn { border-radius: 10px; border: 1px solid rgba(255,255,255,0.25); }
.glass-btn-primary { background: linear-gradient(135deg, #ffcc66, #e0a800); border: none; color: #2a2a2a; border-radius: 10px; font-weight: 600; }
.glass-btn-primary:hover { filter: brightness(1.05); }
/* Ghost button and shared states */
.glass-btn-ghost { background: transparent; color: #e9eef6; border: 1px solid rgba(255,255,255,0.25); }
.glass-btn-ghost:hover { background: rgba(255,255,255,0.06); }
.glass-btn, .glass-btn-primary { padding: 10px 16px; letter-spacing: .2px; }
.glass-btn i, .glass-btn-primary i { margin-right: 6px; }
.glass-btn:focus-visible, .glass-btn-primary:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(255,204,102,0.35); }
.glass-btn[disabled], .glass-btn-primary[disabled] { opacity: .7; cursor: not-allowed; }
/* Enhanced buttons sizing and spacing */
.glass-btn, .glass-btn-primary { padding: 10px 16px; letter-spacing: .2px; }
.glass-btn i, .glass-btn-primary i { margin-right: 6px; }

.reason-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(165px, 1fr)); grid-gap: 12px; margin-top: 10px; }
.reason-card { border: 1px solid rgba(255,255,255,0.25); background: linear-gradient(145deg, rgba(255,255,255,.08), rgba(255,255,255,.06)); padding: 14px; border-radius: 14px; cursor: pointer; transition: transform .15s ease, box-shadow .2s ease, border-color .2s ease; }
.reason-card .icon { font-size: 20px; margin-bottom: 6px; }
.reason-card .title { font-weight: 600; color: #e9eef6; }
.reason-card .hint { font-size: 12px; color: #b4c0d1; }
.reason-card:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,.18); }
.reason-card:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(255,204,102,0.45); }
.reason-card.active { border-color: rgba(255,204,102,0.95); background: linear-gradient(145deg, rgba(255,204,102,.18), rgba(255,255,255,.06)); box-shadow: 0 6px 24px rgba(255,204,102,.18), 0 0 0 2px rgba(255,204,102,0.25) inset; position: relative; }
.reason-card.active::after { content: '✓'; position: absolute; top: 10px; right: 12px; font-weight: 700; color: #ffe6b3; font-size: 16px; }
</style>

<!-- JavaScript del Modal (Interactividad) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('cancelNavigationModal');
    const reasonSelect = document.getElementById('cancellationReason');
    const reasonGrid = document.getElementById('reasonGrid');
    const notesTextarea = document.getElementById('cancellationNotes');
    const charCount = document.getElementById('notesCharCount');
    const confirmBtn = document.getElementById('confirmCancellationBtn');

    // Construir grid de razones desde el select
    if (reasonGrid && reasonSelect) {
        const options = Array.from(reasonSelect.options).filter(o => o.value);
        reasonGrid.innerHTML = options.map(o => {
            const icon = o.dataset.icon || 'fa-ellipsis';
            return `
                <div class="reason-card" data-value="${o.value}">
                    <div class="icon"><i class="fas ${icon}"></i></div>
                    <div class="title">${o.text}</div>
                    <div class="hint">Seleccionar</div>
                </div>
            `;
        }).join('');

        reasonGrid.addEventListener('click', (e) => {
            const card = e.target.closest('.reason-card');
            if (!card) return;
            const value = card.getAttribute('data-value');
            reasonSelect.value = value;
            reasonGrid.querySelectorAll('.reason-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
        });
    }

    // Contador de caracteres
    if (notesTextarea) {
        notesTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }

    // Confirmación
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            const reason = reasonSelect.value;
            const notes = notesTextarea.value.trim();

            if (!reason) { if (typeof window.showAlert === 'function') window.showAlert('Por favor selecciona una razón de cancelación', 'warning'); return; }

            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelando...';

            if (typeof window.processCancellation === 'function') {
                window.processCancellation(reason, notes);
            } else {
                console.error('Función processCancellation no encontrada');
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-check"></i> Sí, Cancelar Navegación';
            }
        });
    }

    // Resetear modal al cerrar
    $(modal).on('hidden.bs.modal', function() {
        reasonSelect.value = '';
        if (reasonGrid) reasonGrid.querySelectorAll('.reason-card').forEach(c => c.classList.remove('active'));
        notesTextarea.value = '';
        charCount.textContent = '0';
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="fas fa-check"></i> Sí, Cancelar Navegación';
    });

    // Cerrar el menú lateral si está abierto al abrir el modal
    $(modal).on('show.bs.modal', function() {
        const drawer = document.getElementById('menu-drawer');
        if (drawer && drawer.classList.contains('show') && typeof window.toggleMenu === 'function') {
            window.toggleMenu();
        }
    });
});

/**
 * Actualizar información de progreso en el modal
 */
function updateCancellationProgress(distance, time, percent) {
    const progressDiv = document.getElementById('cancellationProgress');
    const distanceEl = document.getElementById('progressDistance');
    const timeEl = document.getElementById('progressTime');
    const percentEl = document.getElementById('progressPercent');
    
    if (progressDiv && distanceEl && timeEl && percentEl) {
        distanceEl.textContent = distance || '-';
        timeEl.textContent = time || '-';
        percentEl.textContent = percent || '-';
        progressDiv.style.display = 'block';
    }
}
</script>
