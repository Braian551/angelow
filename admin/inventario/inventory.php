<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../alertas/alerta1.php';
require_once __DIR__ . '/inventory_functions.php';

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Debes iniciar sesión para acceder a esta página'];
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

// Verificar rol de administrador
try {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'admin') {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'No tienes permisos para acceder a esta área'];
        header("Location: " . BASE_URL . "/index.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error de permisos: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al verificar permisos. Por favor intenta nuevamente.'];
    header("Refresh:0");
    exit();
}

// Procesar acciones CRUD
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;
$product_id = $_GET['product_id'] ?? 0;

// Validar y sanitizar entrada
$id = filter_var($id, FILTER_VALIDATE_INT);
$product_id = filter_var($product_id, FILTER_VALIDATE_INT);

// Manejar acciones
switch ($action) {
    case 'update-stock':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $variant_id = filter_var($_POST['variant_id'], FILTER_VALIDATE_INT);
            $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
            $operation = $_POST['operation'];

            if ($variant_id && $quantity !== false && in_array($operation, ['add', 'subtract', 'set'])) {
                try {
                    $current_qty = getCurrentStock($conn, $variant_id);
                    $new_qty = calculateNewQuantity($current_qty, $quantity, $operation);

                    $stmt = $conn->prepare("UPDATE product_size_variants SET quantity = ? WHERE id = ?");
                    $stmt->execute([$new_qty, $variant_id]);

                    // Registrar movimiento en el historial
                    logStockMovement($conn, [
                        'variant_id' => $variant_id,
                        'user_id' => $_SESSION['user_id'],
                        'previous_qty' => $current_qty,
                        'new_qty' => $new_qty,
                        'operation' => $operation,
                        'notes' => $_POST['notes'] ?? null
                    ]);

                    $_SESSION['alert'] = ['type' => 'success', 'message' => 'Stock actualizado correctamente'];
                } catch (PDOException $e) {
                    error_log("Error al actualizar stock: " . $e->getMessage());
                    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error al actualizar el stock'];
                }
            } else {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Datos inválidos para actualizar stock'];
            }
            header("Location: inventory.php?product_id=" . $product_id);
            exit();
        }
        break;

    case 'transfer-stock':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $source_variant_id = filter_var($_POST['source_variant_id'], FILTER_VALIDATE_INT);
            $target_variant_id = filter_var($_POST['target_variant_id'], FILTER_VALIDATE_INT);
            $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);

            if ($source_variant_id && $target_variant_id && $quantity !== false && $quantity > 0) {
                try {
                    transferStock($conn, $source_variant_id, $target_variant_id, $quantity, $_SESSION['user_id']);
                    $_SESSION['alert'] = ['type' => 'success', 'message' => 'Transferencia de stock completada'];
                } catch (Exception $e) {
                    $_SESSION['alert'] = ['type' => 'error', 'message' => $e->getMessage()];
                }
            } else {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Datos inválidos para transferencia'];
            }
            header("Location: inventory.php");
            exit();
        }
        break;
}

// Obtener datos para la vista
$products = getProductsWithInventory($conn);
$lowStockItems = getLowStockItems($conn, 5); // Umbral de bajo stock
$stockHistory = $product_id ? getStockHistory($conn, $product_id) : [];
$productDetails = $product_id ? getProductDetails($conn, $product_id) : null;
$productVariants = $product_id ? getProductVariantsWithStock($conn, $product_id) : [];
$totalInventoryUnits = array_sum(array_column($products, 'total_stock'));

// Mostrar alerta almacenada en sesión si existe
if (isset($_SESSION['alert'])) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() {
        showAlert('" . addslashes($_SESSION['alert']['message']) . "', '" . $_SESSION['alert']['type'] . "');
    });</script>";
    unset($_SESSION['alert']);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Inventario - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/style-admin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/alerta.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin/inventario/inventory.css">
</head>

<body>
    <div class="admin-container">
        <?php require_once __DIR__ . '/../../layouts/headeradmin2.php'; ?>

        <main class="admin-content">
            <?php require_once __DIR__ . '/../../layouts/headeradmin1.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <h1>
                        <i class="fas fa-boxes"></i> Gestión de Inventario
                    </h1>
                    <div class="breadcrumb">
                        <a href="<?= BASE_URL ?>/admin">Dashboard</a> / <span>Inventario</span>
                    </div>
                </div>

                <?php if ($product_id): ?>
                    <!-- Vista detallada de un producto -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Inventario: <?= htmlspecialchars($productDetails['name']) ?></h3>
                            <a href="inventory.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver al listado
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="inventory-summary">
                                <div class="summary-card">
                                    <span class="summary-value"><?= $productDetails['total_stock'] ?></span>
                                    <span class="summary-label">Unidades totales</span>
                                </div>
                                <div class="summary-card <?= $productDetails['low_stock'] ? 'warning' : '' ?>">
                                    <span class="summary-value"><?= $productDetails['low_stock_count'] ?></span>
                                    <span class="summary-label">Variantes con bajo stock</span>
                                </div>
                                <div class="summary-card">
                                    <span class="summary-value"><?= count($productVariants) ?></span>
                                    <span class="summary-label">Variantes</span>
                                </div>
                            </div>

                            <div class="inventory-actions">
                                <button class="btn btn-primary" data-toggle="modal" data-target="#updateStockModal">
                                    <i class="fas fa-edit"></i> Actualizar stock
                                </button>
                                <button class="btn btn-secondary" data-toggle="modal" data-target="#transferStockModal">
                                    <i class="fas fa-exchange-alt"></i> Transferir stock
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Color</th>
                                            <th>Talla</th>
                                            <th>SKU</th>
                                            <th>Stock</th>
                                            <th>Estado</th>
                                            <th>Última actualización</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($productVariants as $variant): ?>
                                            <tr>
                                                <td>
                                                    <span class="color-badge" style="background-color: <?= $variant['hex_code'] ?? '#ccc' ?>"></span>
                                                    <?= htmlspecialchars($variant['color_name']) ?>
                                                </td>
                                                <td><?= htmlspecialchars($variant['size_name']) ?></td>
                                                <td><?= htmlspecialchars($variant['sku']) ?></td>
                                                <td><?= $variant['quantity'] ?></td>
                                                <td>
                                                    <span class="status-badge <?= $variant['quantity'] <= 5 ? 'danger' : ($variant['quantity'] <= 10 ? 'warning' : 'success') ?>">
                                                        <?= $variant['quantity'] <= 5 ? 'Bajo stock' : ($variant['quantity'] <= 10 ? 'Stock medio' : 'Stock suficiente') ?>
                                                    </span>
                                                </td>
                                                <td><?= $variant['last_updated'] ? date('d/m/Y H:i', strtotime($variant['last_updated'])) : 'N/A' ?></td>
                                                <td class="actions">
                                                    <button class="btn btn-sm btn-edit update-stock-btn"
                                                        data-variant-id="<?= $variant['id'] ?>"
                                                        data-current-stock="<?= $variant['quantity'] ?>">
                                                        <i class="fas fa-edit"></i> Editar
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="history-section">
                                <h4>Historial de movimientos</h4>
                                <?php if (!empty($stockHistory)): ?>
                                    <div class="history-list">
                                        <?php foreach ($stockHistory as $entry): ?>
                                            <div class="history-item">
                                                <div class="history-icon">
                                                    <i class="fas fa-<?= $entry['operation'] === 'add' ? 'plus-circle' : ($entry['operation'] === 'subtract' ? 'minus-circle' : 'exchange-alt') ?>"></i>
                                                </div>
                                                <div class="history-content">
                                                    <p>
                                                        <strong><?= htmlspecialchars($entry['user_name']) ?></strong>
                                                        <?= getOperationText($entry['operation']) ?>
                                                        <?= abs($entry['new_qty'] - $entry['previous_qty']) ?> unidades
                                                        <?= $entry['notes'] ? "(Nota: " . htmlspecialchars($entry['notes']) . ")" : "" ?>
                                                    </p>
                                                    <span class="history-time"><?= date('d/m/Y H:i', strtotime($entry['created_at'])) ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p>No hay historial de movimientos para este producto.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Vista general del inventario -->
                    <div class="card filters-card">
                        <div class="filters-header">
                            <div class="filters-title">
                                <i class="fas fa-sliders-h"></i>
                                <h3>Búsqueda de inventario</h3>
                            </div>
                        </div>
                        <form id="inventory-search-form" class="filters-form">
                            <div class="search-bar">
                                <div class="search-input-wrapper">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" placeholder="Buscar por producto, categoría o estado" id="search-inventory" name="search" class="search-input" autocomplete="off">
                                    <button type="button" class="search-clear" id="clear-inventory-search" style="display: none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <button type="submit" class="search-submit-btn">
                                    <i class="fas fa-search"></i>
                                    <span>Buscar</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="results-summary">
                        <div class="results-info">
                            <i class="fas fa-clipboard-list"></i>
                            <p><?= count($products) ?> productos monitoreados • <?= $totalInventoryUnits ?> unidades totales</p>
                        </div>
                        <div class="quick-actions">
                            <a href="<?= BASE_URL ?>/admin/products.php" class="btn-action btn-export">
                                <i class="fas fa-box-open"></i>
                                <span>Ver productos</span>
                            </a>
                            <a href="<?= BASE_URL ?>/admin/subproducto.php" class="btn-action btn-bulk">
                                <i class="fas fa-plus"></i>
                                <span>Nuevo producto</span>
                            </a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3>Resumen de Inventario</h3>
                        </div>
                        <div class="card-body">
                            <div class="inventory-stats">
                                <div class="stat-card">
                                    <div class="stat-icon bg-primary">
                                        <i class="fas fa-box-open"></i>
                                    </div>
                                    <div class="stat-info">
                                        <h3>Productos</h3>
                                        <span class="stat-value"><?= count($products) ?></span>
                                    </div>
                                </div>

                                <div class="stat-card">
                                    <div class="stat-icon bg-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="stat-info">
                                        <h3>Bajo stock</h3>
                                        <span class="stat-value"><?= count($lowStockItems) ?></span>
                                    </div>
                                </div>

                                <div class="stat-card">
                                    <div class="stat-icon bg-success">
                                        <i class="fas fa-boxes"></i>
                                    </div>
                                    <div class="stat-info">
                                        <h3>Stock total</h3>
                                        <span class="stat-value"><?= $totalInventoryUnits ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="tabs">
                                <button class="tab-btn active" data-tab="all">Todos los productos</button>
                                <button class="tab-btn" data-tab="low-stock">Bajo stock</button>
                            </div>

                            <div class="tab-content active" id="tab-all">
                              <div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Variantes</th>
                <th>Stock total</th>
                <th>Bajo stock</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="inventory-table-body">
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td><?= htmlspecialchars($product['category_name']) ?></td>
                    <td><?= $product['variant_count'] ?></td>
                    <td><?= $product['total_stock'] ?></td>
                    <td><?= $product['low_stock_count'] ?></td>
                    <td>
                        <span class="status-badge <?= $product['is_active'] ? 'active' : 'inactive' ?>">
                            <?= $product['is_active'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td class="actions">
                        <div class="table-actions">
                            <a href="inventory.php?product_id=<?= $product['id'] ?>" class="btn btn-sm btn-status" title="Ver producto" aria-label="Ver producto">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div id="no-results" class="empty-state" style="display: none;">
        <i class="fas fa-search-minus"></i>
        <p>No se encontraron productos que coincidan con tu búsqueda</p>
    </div>
</div>
                            </div>

                            <div class="tab-content" id="tab-low-stock">
                                <?php if (!empty($lowStockItems)): ?>
                                    <div class="table-responsive">
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Color</th>
                                                    <th>Talla</th>
                                                    <th>Stock</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($lowStockItems as $item): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                                        <td>
                                                            <span class="color-badge" style="background-color: <?= $item['hex_code'] ?? '#ccc' ?>"></span>
                                                            <?= htmlspecialchars($item['color_name']) ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($item['size_name']) ?></td>
                                                        <td class="<?= $item['quantity'] <= 3 ? 'text-danger' : 'text-warning' ?>">
                                                            <strong><?= $item['quantity'] ?></strong>
                                                        </td>
                                                        <td class="actions">
                                                            <div class="table-actions">
                                                                <a href="inventory.php?product_id=<?= $item['product_id'] ?>" class="btn btn-sm btn-primary">
                                                                    <i class="fas fa-edit"></i> Reabastecer
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                       

                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-check-circle"></i>
                                        <p>No hay productos con bajo stock</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal para actualizar stock -->
    <div class="modal-overlay" id="updateStockModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Actualizar stock</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="updateStockForm" method="POST" action="inventory.php?action=update-stock&product_id=<?= $product_id ?>">
                    <input type="hidden" name="variant_id" id="modalVariantId">

                    <div class="form-group">
                        <label for="currentStock">Stock actual</label>
                        <input type="text" id="currentStock" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label for="operation">Operación</label>
                        <select name="operation" id="operation" class="form-control" required>
                            <option value="add">Añadir stock</option>
                            <option value="subtract">Restar stock</option>
                            <option value="set">Establecer cantidad exacta</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quantity">Cantidad</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notas (opcional)</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        <button type="button" class="btn btn-secondary modal-close">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para transferir stock -->
    <div class="modal-overlay" id="transferStockModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Transferir stock entre variantes</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="transferStockForm" method="POST" action="inventory.php?action=transfer-stock">
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">

                    <div class="form-group">
                        <label for="sourceVariant">Variante origen</label>
                        <select name="source_variant_id" id="sourceVariant" class="form-control" required>
                            <option value="">Seleccionar variante</option>
                            <?php foreach ($productVariants as $variant): ?>
                                <option value="<?= $variant['id'] ?>" data-stock="<?= $variant['quantity'] ?>">
                                    <?= htmlspecialchars($variant['color_name']) ?> - <?= htmlspecialchars($variant['size_name']) ?> (Stock: <?= $variant['quantity'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="targetVariant">Variante destino</label>
                        <select name="target_variant_id" id="targetVariant" class="form-control" required>
                            <option value="">Seleccionar variante</option>
                            <?php foreach ($productVariants as $variant): ?>
                                <option value="<?= $variant['id'] ?>">
                                    <?= htmlspecialchars($variant['color_name']) ?> - <?= htmlspecialchars($variant['size_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="transferQuantity">Cantidad a transferir</label>
                        <input type="number" name="quantity" id="transferQuantity" class="form-control" min="1" required>
                        <small id="maxQuantityHelp" class="form-text text-muted"></small>
                    </div>

                    <div class="form-group">
                        <label for="transferNotes">Notas (opcional)</label>
                        <textarea name="notes" id="transferNotes" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Transferir stock</button>
                        <button type="button" class="btn btn-secondary modal-close">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>/js/dashboardadmin.js"></script>
    <script src="<?= BASE_URL ?>/js/alerta.js"></script>
    <script>
     document.addEventListener('DOMContentLoaded', function() {
    // Buscador de inventario - Versión corregida
    const searchInput = document.getElementById('search-inventory');
    const searchForm = document.getElementById('inventory-search-form');
    const clearSearchBtn = document.getElementById('clear-inventory-search');
    const tableBody = document.getElementById('inventory-table-body');
    const noResults = document.getElementById('no-results');
    
    if (searchInput && tableBody) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const rows = tableBody.querySelectorAll('tr');
            let hasVisibleRows = false;
            
            if (clearSearchBtn) {
                clearSearchBtn.style.display = searchTerm.length ? 'inline-flex' : 'none';
            }
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                let rowMatches = false;
                
                // Buscar en todas las celdas excepto la última (acciones)
                for (let i = 0; i < cells.length - 1; i++) {
                    const cellText = cells[i].textContent.toLowerCase();
                    if (cellText.includes(searchTerm)) {
                        rowMatches = true;
                        break;
                    }
                }
                
                if (rowMatches) {
                    row.style.display = '';
                    hasVisibleRows = true;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Mostrar u ocultar mensaje de no resultados
            if (searchTerm.length > 0 && !hasVisibleRows) {
                noResults.style.display = 'flex';
            } else {
                noResults.style.display = 'none';
            }
        });

        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                searchInput.dispatchEvent(new Event('input'));
            });
        }

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                this.style.display = 'none';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
            });
        }
    }
            // Tabs
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');

                    // Actualizar botones de tab
                    document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');

                    // Actualizar contenido de tabs
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                    document.getElementById(`tab-${tabId}`).classList.add('active');
                });
            });

            // Modal de actualización de stock
            document.querySelectorAll('.update-stock-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const variantId = this.getAttribute('data-variant-id');
                    const currentStock = this.getAttribute('data-current-stock');

                    document.getElementById('modalVariantId').value = variantId;
                    document.getElementById('currentStock').value = currentStock;

                    // Mostrar modal
                    document.getElementById('updateStockModal').style.display = 'flex';
                });
            });

            // Modal de transferencia de stock
            document.getElementById('sourceVariant').addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const maxQuantity = selectedOption.getAttribute('data-stock');

                document.getElementById('transferQuantity').max = maxQuantity;
                document.getElementById('maxQuantityHelp').textContent = `Máximo disponible: ${maxQuantity}`;
            });

            // Validación de transferencia
            document.getElementById('transferStockForm').addEventListener('submit', function(e) {
                const source = document.getElementById('sourceVariant').value;
                const target = document.getElementById('targetVariant').value;

                if (source === target) {
                    e.preventDefault();
                    alert('No puedes transferir stock a la misma variante');
                }
            });

            // Cerrar modales
            document.querySelectorAll('.modal-close').forEach(btn => {
                btn.addEventListener('click', function() {
                    this.closest('.modal-overlay').style.display = 'none';
                });
            });
        });
    </script>
</body>

</html>