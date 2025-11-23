<script>
document.addEventListener('DOMContentLoaded', function() {
    let sliderToDelete = null;
    const container = document.getElementById('sliders-container');

    loadSliders();

    function loadSliders() {
        const url = `<?= BASE_URL ?>/ajax/admin/sliders/sliderssearch.php`;
        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.error || 'Error al cargar');
                renderSliders(data.items);
                initSortable();
            })
            .catch(err => {
                console.error(err);
                showAlert('error', 'Error: ' + err.message);
                container.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Error al cargar sliders</h3></div>`;
            });
    }

    function renderSliders(items) {
        if (!items || items.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-images"></i>
                    <h3>No hay sliders</h3>
                    <p>Empieza creando tu primer slider para el index.</p>
                    <a href="<?= BASE_URL ?>/admin/sliders/add_slider.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Agregar Slider
                    </a>
                </div>
            `;
            return;
        }

        let html = `<table class="data-table">
            <thead>
                <tr>
                    <th width="50"></th>
                    <th width="60">Orden</th>
                    <th width="150">Imagen</th>
                    <th>Título</th>
                    <th>Subtítulo</th>
                    <th>Enlace</th>
                    <th width="100">Estado</th>
                    <th width="120" class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="sortable-sliders">`;

        items.forEach(s => {
            const statusClass = s.is_active ? 'active' : 'inactive';
            const statusText = s.is_active ? 'Activo' : 'Inactivo';
            const imgSrc = s.image || '<?= BASE_URL ?>/images/default-product.jpg';

            html += `
                <tr data-id="${s.id}">
                    <td><i class="fas fa-grip-vertical drag-handle" style="cursor:grab; color:var(--text-light);"></i></td>
                    <td><span class="order-badge" style="background:var(--bg-dark); padding:2px 8px; border-radius:4px;">${s.order_position}</span></td>
                    <td><img src="${imgSrc}" class="slider-img-preview" alt="${s.title}" style="width:100px; height:60px; object-fit:cover; border-radius:var(--radius-sm);" onerror="this.src='<?= BASE_URL ?>/images/default-product.jpg'"></td>
                    <td><strong>${s.title}</strong></td>
                    <td>${s.subtitle || '<em class="text-muted">Sin subtítulo</em>'}</td>
                    <td>${s.link ? `<a href="${s.link}" target="_blank" class="text-primary"><i class="fas fa-external-link-alt"></i> Ver enlace</a>` : '<span class="text-muted">-</span>'}</td>
                    <td>
                        <span class="status-badge ${statusClass} toggle-active" data-id="${s.id}" data-active="${s.is_active}" style="cursor:pointer;">
                            ${statusText}
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="<?= BASE_URL ?>/admin/sliders/edit_slider.php?id=${s.id}" class="btn-icon btn-primary" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn-icon btn-danger btn-delete" data-id="${s.id}" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        html += `</tbody></table>`;
        container.innerHTML = html;
        assignEvents();
    }

    function initSortable() {
        const tbody = document.getElementById('sortable-sliders');
        if (!tbody) return;

        new Sortable(tbody, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            onEnd: function(evt) {
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const order = rows.map((row, idx) => ({
                    id: parseInt(row.getAttribute('data-id')),
                    order_position: idx + 1
                }));

                fetch('<?= BASE_URL ?>/ajax/admin/sliders/reorder.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order })
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message);
                    showAlert('success', 'Orden actualizado');
                    loadSliders();
                })
                .catch(err => showAlert('error', 'Error al reordenar: ' + err.message));
            }
        });
    }

    function assignEvents() {
        // Toggle activo/inactivo
        document.querySelectorAll('.toggle-active').forEach(badge => {
            badge.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const currentActive = parseInt(this.getAttribute('data-active'));
                const newActive = currentActive ? 0 : 1;

                fetch('<?= BASE_URL ?>/ajax/admin/sliders/toggle_active.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, is_active: newActive })
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message);
                    showAlert('success', 'Estado actualizado');
                    loadSliders();
                })
                .catch(err => showAlert('error', 'Error: ' + err.message));
            });
        });

        // Eliminar
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function() {
                sliderToDelete = this.getAttribute('data-id');
                document.getElementById('delete-modal').classList.add('active');
            });
        });

        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.modal-overlay').classList.remove('active');
            });
        });

        document.getElementById('confirm-delete').addEventListener('click', function() {
            if (!sliderToDelete) return;
            fetch(`<?= BASE_URL ?>/ajax/admin/sliders/delete.php?id=${sliderToDelete}`, { method: 'DELETE' })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message);
                    showAlert('success', 'Slider eliminado');
                    document.getElementById('delete-modal').classList.remove('active');
                    loadSliders();
                })
                .catch(err => showAlert('error', 'Error: ' + err.message));
        });
    }
});
</script>
