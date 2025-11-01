<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    let isLoading = false;
    let newsToDelete = null;

    const searchInput = document.getElementById('search-input');
    const statusFilter = document.getElementById('status-filter');
    const featuredFilter = document.getElementById('featured-filter');
    const publishedFilter = document.getElementById('published-filter');
    const orderFilter = document.getElementById('order-filter');

    const newsContainer = document.getElementById('news-container');
    const resultsCount = document.getElementById('results-count');
    const paginationContainer = document.getElementById('pagination-container');
    const searchForm = document.getElementById('search-form');

    loadNews();

    function loadNews(page = 1) {
        if (isLoading) return;
        isLoading = true;
        currentPage = page;
        newsContainer.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Cargando noticias...</div>';

        const params = new URLSearchParams();
        if (searchInput.value) params.append('search', searchInput.value);
        if (statusFilter.value) params.append('status', statusFilter.value);
        if (featuredFilter.value) params.append('featured', featuredFilter.value);
        if (publishedFilter.value) params.append('published', publishedFilter.value);
        params.append('order', orderFilter.value);
        params.append('page', page);

        const apiUrl = `<?= BASE_URL ?>/ajax/admin/news/newssearch.php?${params.toString()}`;

        fetch(apiUrl)
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.error || 'Error de datos');
                renderNews(data.items);
                updateResultsCount(data.meta.total, data.items.length);
                renderPagination(data.meta.total, page, data.meta.perPage);
            })
            .catch(err => {
                console.error(err);
                showAlert('error', 'Error al cargar noticias: ' + err.message);
                newsContainer.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Error al cargar noticias</h3>
                        <p>${err.message}</p>
                        <button onclick="window.location.reload()" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Recargar
                        </button>
                    </div>
                `;
            })
            .finally(() => isLoading = false);
    }

    function renderNews(items) {
        if (!items || items.length === 0) {
            newsContainer.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-newspaper"></i>
                    <h3>No se encontraron noticias</h3>
                    <p>Empieza creando tu primera noticia.</p>
                    <a href="<?= BASE_URL ?>/admin/news/add_news.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Agregar noticia
                    </a>
                </div>
            `;
            return;
        }

        let html = '';
        items.forEach(n => {
            const statusClass = n.is_active ? '' : 'inactive';
            const imageUrl = n.image || '<?= BASE_URL ?>/images/default-product.jpg';
            const badgeFeatured = n.is_featured ? '<span class="badge badge-primary">Destacada</span>' : '';
            const badgeInactive = !n.is_active ? '<span class="badge badge-secondary">Inactiva</span>' : '';

            html += `
                <div class="product-card ${statusClass}">
                    <div class="product-image">
                        <img src="${imageUrl}" alt="${n.title.replace(/"/g, '&quot;')}" onerror="this.src='<?= BASE_URL ?>/images/default-product.jpg'">
                        <div class="product-badges">${badgeFeatured}${badgeInactive}</div>
                        <div class="product-actions">
                            <a href="<?= BASE_URL ?>/admin/news/edit_news.php?id=${n.id}" class="btn-action btn-edit" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn-action btn-delete" data-id="${n.id}" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="product-info">
                        <h3>${n.title}</h3>
                        <p class="product-meta">
                            <span class="category"><i class="fas fa-calendar"></i> ${n.published_at ? new Date(n.published_at).toLocaleString() : 'Sin publicar'}</span>
                        </p>
                        <div class="product-stats">
                            <div class="stat-item"><i class="fas fa-eye"></i> <span>${n.is_active ? 'Activo' : 'Inactivo'}</span></div>
                            <div class="stat-item"><i class="fas fa-star"></i> <span>${n.is_featured ? 'Destacada' : 'Normal'}</span></div>
                        </div>
                    </div>
                </div>
            `;
        });

        newsContainer.innerHTML = html;
        assignButtonEvents();
    }

    function updateResultsCount(total, showing) {
        resultsCount.textContent = `Mostrando ${showing} de ${total} noticias`;
    }

    function renderPagination(totalItems, current, perPage = 12) {
        const totalPages = Math.ceil(totalItems / perPage);
        if (totalPages <= 1) { paginationContainer.innerHTML = ''; return; }

        let html = '';
        const maxVisible = 5;
        let start = Math.max(1, current - Math.floor(maxVisible / 2));
        let end = Math.min(totalPages, start + maxVisible - 1);
        if (end - start + 1 < maxVisible) start = Math.max(1, end - maxVisible + 1);

        if (current > 1) {
            html += `<a href="#" data-page="1" class="page-link"><i class="fas fa-angle-double-left"></i></a>`;
            html += `<a href="#" data-page="${current - 1}" class="page-link"><i class="fas fa-angle-left"></i></a>`;
        }
        if (start > 1) html += `<span class="page-dots">...</span>`;
        for (let i = start; i <= end; i++) {
            html += `<a href="#" data-page="${i}" class="page-link ${i === current ? 'active' : ''}">${i}</a>`;
        }
        if (end < totalPages) html += `<span class="page-dots">...</span>`;
        if (current < totalPages) {
            html += `<a href="#" data-page="${current + 1}" class="page-link"><i class="fas fa-angle-right"></i></a>`;
            html += `<a href="#" data-page="${totalPages}" class="page-link"><i class="fas fa-angle-double-right"></i></a>`;
        }

        paginationContainer.innerHTML = html;
        document.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.getAttribute('data-page'));
                if (!isNaN(page)) { loadNews(page); window.scrollTo({ top: 0, behavior: 'smooth' }); }
            });
        });
    }

    function assignButtonEvents() {
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function() {
                newsToDelete = this.getAttribute('data-id');
                document.getElementById('delete-modal').classList.add('active');
            });
        });

        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.modal-overlay').classList.remove('active');
            });
        });

        document.getElementById('confirm-delete').addEventListener('click', function() {
            if (!newsToDelete) return;
            const url = `<?= BASE_URL ?>/admin/news/delete_news.php?id=${newsToDelete}`;
            fetch(url, { method: 'DELETE' })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message || 'No se pudo eliminar');
                    showAlert('success', 'Noticia eliminada correctamente');
                    document.getElementById('delete-modal').classList.remove('active');
                    loadNews(currentPage);
                })
                .catch(err => showAlert('error', 'Error: ' + err.message));
        });
    }

    // Eventos filtros
    function debounce(fn, wait) { let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), wait); }; }
    searchInput.addEventListener('input', debounce(() => loadNews(), 400));
    statusFilter.addEventListener('change', () => loadNews());
    featuredFilter.addEventListener('change', () => loadNews());
    publishedFilter.addEventListener('change', () => loadNews());
    orderFilter.addEventListener('change', () => loadNews());

    searchForm.addEventListener('submit', function(e) { e.preventDefault(); loadNews(); });
});
</script>
