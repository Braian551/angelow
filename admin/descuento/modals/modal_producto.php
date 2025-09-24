<!-- Modal para selección de productos -->
<!-- Modal para selección de productos -->
<div id="products-modal" class="modal">
    <div class="modal-content large-modal">
        <div class="modal-header">
            <h3>Seleccionar productos</h3>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <div class="search-container">
                <input type="text" id="modal-product-search" placeholder="Buscar productos por nombre, categoría...">
                <button id="search-products-btn"><i class="fas fa-search"></i></button>
            </div>

            <div class="selection-options">
                <div class="select-all-wrapper">
                    <label>
                        <input type="checkbox" id="select-all-products-checkbox">
                        <span>Seleccionar todos los visibles</span>
                    </label>
                </div>
                <span id="selected-products-count" class="selected-count">0 seleccionados</span>
            </div>

            <div class="table-container">
                <table id="products-table" class="data-table">
                    <thead>
                        <tr>
                            <th width="30px"></th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="products-list">
                        <!-- Los productos se cargarán aquí -->
                    </tbody>
                </table>
            </div>

            <div id="products-loading" class="loading-spinner" style="display: none;">
                <i class="fas fa-spinner fa-spin"></i> Cargando productos...
            </div>

            <div id="no-products-found" class="empty-state" style="display: none;">
                <i class="fas fa-search-minus"></i>
                <p>No se encontraron productos que coincidan con tu búsqueda</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary close-modal">Cancelar</button>
            <button type="button" id="apply-products-selection" class="btn btn-primary">Aplicar selección</button>
        </div>
    </div>
</div>
<style>
    /* Estilos mejorados para la tabla de productos */
    .modal-body .data-table {
        font-size: 0.9em;
    }

    .modal-body .data-table th {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .product-image {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #ddd;
    }

    .stock-low {
        color: #f44336;
        font-weight: bold;
    }

    .stock-medium {
        color: #ff9800;
    }

    .stock-high {
        color: #4caf50;
    }

    .status-active {
        color: #4caf50;
        font-weight: bold;
    }

    .status-inactive {
        color: #f44336;
    }

    tr.selected {
        background-color: #e3f2fd !important;
    }

    tr.selected td {
        border-color: #2196f3;
    }

    /* Mejoras de responsive */
    @media (max-width: 768px) {
        .large-modal {
            width: 98%;
            margin: 1%;
        }

        .modal-body .table-container {
            max-height: 300px;
        }

        .modal-body .data-table {
            font-size: 0.8em;
        }
    }
</style>