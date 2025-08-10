<?php
// admin/modals/sub/size_modal.php
?>
<div id="size-modal" class="size-modal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Configurar Talla</h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-grid">
                <div class="form-group">
                    <label for="modal-price">Precio *</label>
                    <input type="text" id="modal-price" min="0" step="0.01" required class="form-control" placeholder="0">
                </div>
                
                <div class="form-group">
                    <label for="modal-quantity">Cantidad *</label>
                    <input type="number" id="modal-quantity" min="0" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="modal-compare-price">Precio de comparación</label>
                    <input type="text" id="modal-compare-price" min="0" step="0.01" class="form-control" placeholder="0">
                </div>
                
                <div class="form-group">
                    <label for="modal-sku">SKU *</label>
                    <input type="text" id="modal-sku" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="modal-barcode">Código de barras</label>
                    <input type="text" id="modal-barcode" class="form-control">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary close-modal">Cancelar</button>
            <button type="button" class="btn btn-primary save-size">Guardar</button>
        </div>
    </div>
</div>

<style>
.size-modal {
    display: none;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(6px);
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1000;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.modal-content {
    position: relative;
    max-width: 500px;
    margin: 5% auto;
    background-color: white;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-xl);
    overflow: hidden;
    animation: modalFadeIn 0.3s ease-out;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    padding: var(--space-md) var(--space-lg);
    background-color: var(--primary-color);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: white;
    font-size: 1.6rem;
}

.close-modal {
    background: none;
    border: none;
    color: white;
    font-size: 2rem;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.modal-body {
    padding: var(--space-lg);
}

.modal-footer {
    padding: var(--space-md) var(--space-lg);
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: var(--space-sm);
}

/* Estilos adicionales para los inputs del modal */
.modal-body .form-control {
    width: 100%;
    padding: 10px 15px;
    border: 2px solid var(--border-color);
    border-radius: var(--radius-sm);
    font-size: 1.4rem;
    transition: var(--transition);
}

.modal-body .form-control:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
}

.modal-body .form-group {
    margin-bottom: var(--space-md);
}

.modal-body label {
    display: block;
    margin-bottom: var(--space-xs);
    font-size: 1.4rem;
    font-weight: 600;
    color: var(--text-dark);
}
</style>