<!-- Modal para selección de usuarios -->
<div id="user-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Seleccionar usuarios para notificación</h3>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <div class="search-container">
                <input type="text" id="modal-user-search" placeholder="Buscar por nombre o email...">
                <button id="search-users-btn" type="button"><i class="fas fa-search"></i></button>
            </div>

            <div class="selection-options">
                <div class="select-all-wrapper">
                    <label>
                        <input type="checkbox" id="select-all-users-checkbox">
                        <span>Seleccionar todos los visibles</span>
                    </label>
                </div>
                <span id="selected-users-count" class="selected-count">0 seleccionados</span>
            </div>

            <div class="table-container">
                <div id="users-loading" class="loading-state" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Cargando usuarios...</p>
                </div>

                <table id="users-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                        </tr>
                    </thead>
                    <tbody id="users-list">
                        <!-- Los usuarios se cargarán aquí -->
                    </tbody>
                </table>

                <div id="no-users-found" class="empty-state" style="display: none;">
                    <i class="fas fa-user-times"></i>
                    <p>No se encontraron usuarios</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary close-modal">Cancelar</button>
            <button type="button" id="apply-users-selection" class="btn btn-primary">
                <i class="fas fa-check"></i> Aplicar selección
            </button>
        </div>
    </div>
</div>

