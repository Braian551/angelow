<!-- Modal de Cancelar Navegación -->
<div class="modal fade" id="cancelNavigationModal" tabindex="-1" role="dialog" aria-labelledby="cancelNavigationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="cancelNavigationModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Cancelar Navegación
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Información de Progreso -->
                <div class="alert alert-info" id="cancellationProgress" style="display:none;">
                    <h6 class="mb-2"><i class="fas fa-chart-line"></i> Progreso Actual</h6>
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
                    <label for="cancellationReason">
                        <i class="fas fa-question-circle"></i> Razón de Cancelación <span class="text-danger">*</span>
                    </label>
                    <select class="form-control" id="cancellationReason" required>
                        <option value="">Selecciona una razón...</option>
                        <option value="order_cancelled">
                            <i class="fas fa-ban"></i> Pedido Cancelado por Cliente
                        </option>
                        <option value="customer_unavailable">
                            <i class="fas fa-user-slash"></i> Cliente No Disponible
                        </option>
                        <option value="address_wrong">
                            <i class="fas fa-location-crosshairs"></i> Dirección Incorrecta/No Existe
                        </option>
                        <option value="technical_issue">
                            <i class="fas fa-wrench"></i> Problema Técnico
                        </option>
                        <option value="driver_emergency">
                            <i class="fas fa-ambulance"></i> Emergencia del Conductor
                        </option>
                        <option value="other">
                            <i class="fas fa-ellipsis"></i> Otra Razón
                        </option>
                    </select>
                </div>

                <!-- Notas Adicionales -->
                <div class="form-group">
                    <label for="cancellationNotes">
                        <i class="fas fa-comment-dots"></i> Notas Adicionales
                    </label>
                    <textarea 
                        class="form-control" 
                        id="cancellationNotes" 
                        rows="4" 
                        placeholder="Proporciona detalles adicionales sobre la cancelación..."
                        maxlength="500"
                    ></textarea>
                    <small class="form-text text-muted">
                        <span id="notesCharCount">0</span>/500 caracteres
                    </small>
                </div>

                <!-- Advertencia -->
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Nota:</strong> Esta acción cancelará la navegación y el estado de la entrega. 
                    Asegúrate de que realmente deseas cancelar.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> No, Continuar
                </button>
                <button type="button" class="btn btn-warning" id="confirmCancellationBtn">
                    <i class="fas fa-check"></i> Sí, Cancelar Navegación
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos del Modal -->
<style>
#cancelNavigationModal .modal-content {
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

#cancelNavigationModal .modal-header {
    border-radius: 12px 12px 0 0;
}

#cancelNavigationModal .form-control:focus {
    border-color: #ffc107;
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
}

#cancelNavigationModal .alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
}

#cancelNavigationModal #progressDistance,
#cancelNavigationModal #progressTime,
#cancelNavigationModal #progressPercent {
    font-size: 1.1rem;
    color: #0c5460;
}

#confirmCancellationBtn {
    font-weight: bold;
}

#confirmCancellationBtn:hover {
    background-color: #e0a800;
    border-color: #d39e00;
}
</style>

<!-- JavaScript del Modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('cancelNavigationModal');
    const reasonSelect = document.getElementById('cancellationReason');
    const notesTextarea = document.getElementById('cancellationNotes');
    const charCount = document.getElementById('notesCharCount');
    const confirmBtn = document.getElementById('confirmCancellationBtn');
    
    // Contador de caracteres
    if (notesTextarea) {
        notesTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }
    
    // Manejar confirmación
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            const reason = reasonSelect.value;
            const notes = notesTextarea.value.trim();
            
            if (!reason) {
                alert('Por favor selecciona una razón de cancelación');
                return;
            }
            
            // Deshabilitar botón para evitar doble clic
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelando...';
            
            // Llamar función global de cancelación
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
        notesTextarea.value = '';
        charCount.textContent = '0';
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="fas fa-check"></i> Sí, Cancelar Navegación';
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
