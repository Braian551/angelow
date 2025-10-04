<?php
// admin/descuento/includes/generate_form.php
?>
<!-- Formulario para generar/editar códigos -->
<div class="card">
    <div class="card-header">
        <h3><?= $edit_id ? 'Editar Código de Descuento' : 'Generar Nuevo Código de Descuento' ?></h3>
    </div>
    <div class="card-body">
        <form id="discount-form" method="POST" action="generate_codes.php?action=<?= $edit_id ? 'update&id=' . $edit_id : 'generate' ?>">
            <?php if ($edit_id): ?>
                <input type="hidden" name="code_id" value="<?= $edit_id ?>">
                <!-- Campo oculto para enviar el tipo de descuento en modo edición -->
                <input type="hidden" name="discount_type" value="<?= $codigo_editar['discount_type_id'] ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group form-group-half">
                    <label for="discount_type">Tipo de Descuento*</label>
                    <select id="discount_type" name="<?= $edit_id ? 'discount_type_display' : 'discount_type' ?>" class="form-control" <?= $edit_id ? 'disabled' : 'required' ?>>
                        <option value="">Seleccionar tipo</option>
                        <?php foreach ($tiposDescuento as $tipo): ?>
                            <option value="<?= $tipo['id'] ?>" 
                                <?= ($edit_id && $codigo_editar['discount_type_id'] == $tipo['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tipo['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($edit_id): ?>
                        <small class="form-text text-muted">El tipo de descuento no se puede modificar</small>
                    <?php endif; ?>
                </div>
                
                <!-- Campo para porcentaje -->
                <div class="form-group form-group-half" id="discount-value-group" style="display: none;">
                    <label for="discount_value">Porcentaje de Descuento*</label>
                    <div class="input-group">
                        <input type="number" id="discount_value" name="discount_value"
                            class="form-control" min="1" max="100" step="0.01" 
                            placeholder="Ej: 10"
                            value="<?= $edit_id && $codigo_editar['discount_type_id'] == 1 ? $codigo_editar['percentage'] : '' ?>">
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
                
                <!-- Campo para monto fijo -->
                <div class="form-group form-group-half" id="fixed-amount-group" style="display: none;">
                    <label for="fixed_amount">Monto Fijo*</label>
                    <div class="input-group">
                        <input type="number" id="fixed_amount" name="fixed_amount"
                            class="form-control" min="1" step="0.01" 
                            placeholder="Ej: 50"
                            value="<?= $edit_id && $codigo_editar['discount_type_id'] == 2 ? $codigo_editar['amount'] : '' ?>">
                        <div class="input-group-append">
                            <span class="input-group-text">$</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <!-- Campo para monto máximo en porcentaje -->
                <div class="form-group form-group-half" id="max-discount-group" style="display: none;">
                    <label for="max_discount_amount">Monto Máximo de Descuento (opcional)</label>
                    <div class="input-group">
                        <input type="number" id="max_discount_amount" name="max_discount_amount"
                            class="form-control" min="0" step="0.01" 
                            placeholder="Ej: 100"
                            value="<?= $edit_id && $codigo_editar['discount_type_id'] == 1 ? $codigo_editar['max_discount_amount'] : '' ?>">
                        <div class="input-group-append">
                            <span class="input-group-text">$</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Límite máximo del descuento aplicable</small>
                </div>
                
                <!-- Campo para compra mínima en monto fijo -->
                <div class="form-group form-group-half" id="min-order-group" style="display: none;">
                    <label for="min_order_amount">Compra Mínima (opcional)</label>
                    <div class="input-group">
                        <input type="number" id="min_order_amount" name="min_order_amount"
                            class="form-control" min="0" step="0.01" 
                            placeholder="Ej: 200"
                            value="<?= $edit_id && $codigo_editar['discount_type_id'] == 2 ? $codigo_editar['min_order_amount'] : '' ?>">
                        <div class="input-group-append">
                            <span class="input-group-text">$</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Monto mínimo requerido para aplicar descuento</small>
                </div>
                
                <!-- Campo para método de envío -->
                <div class="form-group form-group-half" id="shipping-method-group" style="display: none;">
                    <label for="shipping_method_id">Método de Envío (opcional)</label>
                    <select id="shipping_method_id" name="shipping_method_id" class="form-control">
                        <option value="">Todos los métodos</option>
                        <?php foreach ($metodosEnvio as $metodo): ?>
                            <option value="<?= $metodo['id'] ?>" 
                                <?= ($edit_id && $codigo_editar['discount_type_id'] == 3 && $codigo_editar['shipping_method_id'] == $metodo['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($metodo['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group form-group-half">
                    <label for="max_uses">Usos Máximos (opcional)</label>
                    <input type="number" id="max_uses" name="max_uses" class="form-control" min="1" 
                        placeholder="Ej: 100"
                        value="<?= $edit_id ? $codigo_editar['max_uses'] : '' ?>">
                    <small class="form-text text-muted">Dejar vacío para usos ilimitados</small>
                </div>
                <div class="form-group form-group-half">
                    <label for="is_single_use" class="checkbox-container">
                        <input type="checkbox" id="is_single_use" name="is_single_use" class="form-check-input" 
                            <?= $edit_id && $codigo_editar['is_single_use'] ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        Uso único por cliente
                    </label>
                    <small class="form-text text-muted">Cada cliente solo puede usar este código una vez</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group form-group-half">
                    <label for="start_date">Fecha de Inicio (opcional)</label>
                    <input type="datetime-local" id="start_date" name="start_date" class="form-control"
                        value="<?= $edit_id && $codigo_editar['start_date'] ? date('Y-m-d\TH:i', strtotime($codigo_editar['start_date'])) : '' ?>">
                </div>
                <div class="form-group form-group-half">
                    <label for="end_date">Fecha de Expiración (opcional)</label>
                    <input type="datetime-local" id="end_date" name="end_date" class="form-control"
                        value="<?= $edit_id && $codigo_editar['end_date'] ? date('Y-m-d\TH:i', strtotime($codigo_editar['end_date'])) : '' ?>">
                </div>
            </div>

            <!-- Campo para activar/desactivar en modo edición -->
            <?php if ($edit_id): ?>
            <div class="form-row">
                <div class="form-group form-group-half">
                    <label for="is_active" class="checkbox-container">
                        <input type="checkbox" id="is_active" name="is_active" class="form-check-input" 
                            <?= $codigo_editar['is_active'] ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        Código activo
                    </label>
                    <small class="form-text text-muted">Desmarcar para desactivar este código de descuento</small>
                </div>
            </div>
            <?php endif; ?>

            <div class="form-group form-group-full product-selection-section">
                <label for="apply_to_all" class="checkbox-container">
                    <input type="checkbox" id="apply_to_all" name="apply_to_all" class="form-check-input check2" 
                        <?= !$edit_id || ($edit_id && empty($codigo_editar['product_ids'])) ? 'checked' : '' ?>>
                    <span class="checkmark"></span>
                    Aplicar a todos los productos
                </label>
                
                <button type="button" id="open-products-modal" class="btn btn-outline-primary" style="display: none; margin-top: 10px;">
                    <i class="fas fa-boxes"></i> Seleccionar productos específicos
                </button>

                <div id="selected-products-info" class="selected-products-info" style="display: none; margin-top: 10px;">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <span id="selected-products-count-display"></span>
                        <button type="button" id="clear-selected-products" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-times"></i> Limpiar
                    </button>
                    </div>
                    
                </div>

                <input type="hidden" name="products" id="selected-products" 
                    value="<?= $edit_id && $codigo_editar['product_ids'] ? '[' . $codigo_editar['product_ids'] . ']' : '[]' ?>">
            </div>

            <div class="form-group form-group-full notification-section">
                <label for="send_notification" class="checkbox-container">
                    <input type="checkbox" id="send_notification" name="send_notification" class="form-check-input">
                    <span class="checkmark"></span>
                    Enviar notificación por email
                </label>
            </div>

            <!-- Grupo de notificación por email (inicialmente oculto) -->
            <div class="form-group form-group-full" id="notification-email-group" style="display: none;">
                <label>Usuarios destinatarios</label>
                <div class="user-selection">
                    <button type="button" id="open-user-modal" class="btn btn-outline-primary">
                        <i class="fas fa-user-plus"></i> Seleccionar usuarios
                    </button>

                    <!-- Información de usuarios seleccionados -->
                    <div id="selected-users-info" class="selected-user-info" style="display: none; margin-top: 10px;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <span id="selected-users-count-display"></span>
                            <button type="button" id="clear-selected-users" class="btn btn-sm btn-outline-danger" style="margin-left: 10px;">
                                <i class="fas fa-times"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="selected_users" name="selected_users" value="[]">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-<?= $edit_id ? 'save' : 'barcode' ?>"></i> 
                    <?= $edit_id ? 'Actualizar Código' : 'Generar Código' ?>
                </button>
                <a href="generate_codes.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>