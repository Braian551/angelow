<?php
// admin/modals/sub/size_modal.php
?>
<div id="size-modal" class="size-modal" aria-hidden="true">
    <div class="size-modal__overlay" data-size-modal-close></div>
    <div class="size-modal__panel" role="dialog" aria-modal="true" aria-labelledby="size-modal-title">
        <div class="size-modal__header">
            <div>
                <p class="size-modal__eyebrow">Configura precio, stock y SKU</p>
                <h3 id="size-modal-title">Configurar Talla</h3>
            </div>
            <button type="button" class="size-modal__close" data-size-modal-close aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="size-modal__body">
            <div class="size-modal__grid">
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
        <div class="size-modal__footer">
            <button type="button" class="btn btn-secondary" data-size-modal-close>Cancelar</button>
            <button type="button" class="btn btn-primary save-size">Guardar</button>
        </div>
    </div>
</div>