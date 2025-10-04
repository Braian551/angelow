<?php
// admin/descuento/includes/codes_list.php
?>
<!-- Listado de códigos -->
<div class="actions-bar">
    <a href="generate_codes.php?action=generate" class="btn btn-primary">
        <i class="fas fa-plus"></i> Generar Código
    </a>
    <div class="search-box2">
        <input type="text" placeholder="Buscar códigos..." id="search-codes">
        <button><i class="fas fa-search"></i></button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($codigos)): ?>
            <div class="empty-state">
                <i class="fas fa-percentage"></i>
                <p>No hay códigos de descuento generados</p>
                <a href="generate_codes.php?action=generate" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Generar primer código
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Usos</th>
                            <th>Válido hasta</th>
                            <th>Productos</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($codigos as $codigo):
                            // Determinar estado del código
                            $isActive = $codigo['is_active'] ?? true;
                            $statusText = "Activo";
                            $statusClass = "active";

                            if (!$isActive) {
                                $statusText = "Inactivo";
                                $statusClass = "inactive";
                            } elseif ($codigo['end_date'] && strtotime($codigo['end_date']) < time()) {
                                $statusText = "Expirado";
                                $statusClass = "inactive";
                            } elseif ($codigo['max_uses'] && $codigo['used_count'] >= $codigo['max_uses']) {
                                $statusText = "Agotado";
                                $statusClass = "inactive";
                            }
                        ?>
                            <tr data-searchable="<?= htmlspecialchars(strtolower($codigo['code'])) ?>">
                                <td>
                                    <span class="discount-code"><?= htmlspecialchars($codigo['code']) ?></span>
                                    <?php if ($codigo['is_single_use']): ?>
                                        <span class="badge badge-info">Único</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($codigo['discount_type_name']) ?></td>
                                <td>
                                    <?php if ($codigo['discount_type_id'] == 3): ?>
                                        <span class="badge badge-info">Envío gratis</span>
                                    <?php elseif ($codigo['discount_type_id'] == 1): ?>
                                        <strong><?= $codigo['percentage'] ?>%</strong>
                                        <?php if ($codigo['max_discount_amount']): ?>
                                            <br><small class="text-muted">Máx: $<?= number_format($codigo['max_discount_amount'], 2) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <strong>$<?= number_format($codigo['amount'], 2) ?></strong>
                                        <?php if ($codigo['min_order_amount']): ?>
                                            <br><small class="text-muted">Mín: $<?= number_format($codigo['min_order_amount'], 2) ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="usage-info">
                                        <span class="usage-count"><?= $codigo['used_count'] ?></span>
                                        <?php if ($codigo['max_uses']): ?>
                                            <span class="usage-separator">/</span>
                                            <span class="usage-max"><?= $codigo['max_uses'] ?></span>
                                        <?php else: ?>
                                            <span class="usage-separator">/</span>
                                            <span class="usage-infinite">∞</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?= $codigo['end_date'] ? date('d/m/Y', strtotime($codigo['end_date'])) : '<span class="text-muted">Sin límite</span>' ?>
                                </td>
                                <td>
                                    <?= $codigo['product_count'] > 0 ?
                                        '<span class="badge badge-info">' . $codigo['product_count'] . ' productos</span>' :
                                        '<span class="text-muted">Todos</span>' ?>
                                </td>
                                <td>
                                    <span class="status-badge <?= $statusClass ?>">
                                        <i class="fas fa-<?= $isActive && $statusClass == 'active' ? 'check-circle' : 'times-circle' ?>"></i>
                                        <?= $statusText ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <button class="btn btn-sm btn-copy"
                                        data-tooltip="Copiar código"
                                        data-code="<?= htmlspecialchars($codigo['code']) ?>">
                                        <i class="fas fa-copy"></i>
                                    </button>

                                    <a href="<?= BASE_URL ?>/admin/api/descuento/generate_pdf.php?id=<?= $codigo['id'] ?>"
                                        class="btn btn-sm btn-pdf"
                                        data-tooltip="Descargar PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>

                                    <a href="generate_codes.php?action=generate&edit=<?= $codigo['id'] ?>"
                                        class="btn btn-sm btn-edit"
                                        data-tooltip="Editar código">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <button class="btn btn-sm btn-status"
                                        data-tooltip="<?= $isActive ? 'Desactivar' : 'Activar' ?>"
                                        data-id="<?= $codigo['id'] ?>"
                                        data-active="<?= $isActive ? '1' : '0' ?>">
                                        <i class="fas fa-<?= $isActive ? 'pause' : 'play' ?>"></i>
                                    </button>

                                    <a href="generate_codes.php?action=delete&id=<?= $codigo['id'] ?>"
                                        class="btn btn-sm btn-delete"
                                        data-tooltip="Eliminar código"
                                        onclick="return confirm('¿Estás seguro de eliminar este código?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div id="no-results" class="empty-state" style="display: none;">
                    <i class="fas fa-search-minus"></i>
                    <p>No se encontraron códigos que coincidan con tu búsqueda</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../../admin/descuento/modals/modal_desactivar.php'; ?>