class ReviewsInbox {
    constructor(config) {
        this.config = config;
        this.state = {
            page: 1,
            perPage: 10,
            search: '',
            status: 'pending',
            rating: '',
            verified: ''
        };
        this.items = new Map();
        this.cacheDom();
        this.bindEvents();
        this.loadOverview();
        this.loadList();
    }

    cacheDom() {
        this.container = document.getElementById('reviews-hub');
        this.statCards = this.container?.querySelectorAll('.stat-card') || [];
        this.distributionList = document.getElementById('rating-distribution');
        this.highlightsList = document.getElementById('reviews-highlights');
        this.tableBody = document.getElementById('reviews-table');
        this.searchInput = document.getElementById('reviews-search');
        this.statusSelect = document.getElementById('reviews-status');
        this.ratingSelect = document.getElementById('reviews-rating');
        this.verifiedSelect = document.getElementById('reviews-verified');
        this.pagination = document.getElementById('reviews-pagination');
        this.detailPanel = document.getElementById('review-detail');
        this.refreshBtn = document.getElementById('reviews-refresh');
    }

    bindEvents() {
        this.searchInput?.addEventListener('input', this.debounce((evt) => {
            this.state.search = evt.target.value.trim();
            this.state.page = 1;
            this.loadList();
        }, 350));

        this.statusSelect?.addEventListener('change', (evt) => {
            this.state.status = evt.target.value;
            this.state.page = 1;
            this.loadList();
        });

        this.ratingSelect?.addEventListener('change', (evt) => {
            this.state.rating = evt.target.value;
            this.state.page = 1;
            this.loadList();
        });

        this.verifiedSelect?.addEventListener('change', (evt) => {
            this.state.verified = evt.target.value;
            this.state.page = 1;
            this.loadList();
        });

        this.pagination?.querySelectorAll('button[data-page]')?.forEach((btn) => {
            btn.addEventListener('click', () => {
                const dir = btn.getAttribute('data-page');
                if (dir === 'prev' && this.state.page > 1) {
                    this.state.page -= 1;
                    this.loadList();
                } else if (dir === 'next') {
                    this.state.page += 1;
                    this.loadList();
                }
            });
        });

        this.tableBody?.addEventListener('click', (evt) => {
            const actionBtn = evt.target.closest('[data-action]');
            const row = evt.target.closest('tr[data-id]');
            if (actionBtn) {
                evt.stopPropagation();
                const id = actionBtn.closest('tr')?.getAttribute('data-id');
                this.handleAction(actionBtn.getAttribute('data-action'), id);
                return;
            }
            if (row) {
                this.openDetail(row.getAttribute('data-id'));
            }
        });

        this.refreshBtn?.addEventListener('click', () => {
            this.loadOverview();
            this.loadList();
        });
    }

    async loadOverview() {
        try {
            const response = await fetch(this.config.endpoints.overview, { credentials: 'same-origin' });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const payload = await response.json();
            if (!payload.success) return;
            this.renderStats(payload.stats);
            this.renderDistribution(payload.distribution);
            this.renderHighlights(payload.highlights);
        } catch (error) {
            console.error('reviews overview', error);
        }
    }

    async loadList() {
        if (!this.tableBody) return;
        this.tableBody.innerHTML = '<tr><td colspan="5">Cargando...</td></tr>';
        const params = new URLSearchParams({
            page: this.state.page,
            per_page: this.state.perPage,
            search: this.state.search,
            status: this.state.status,
            rating: this.state.rating,
            verified: this.state.verified
        });
        try {
            const response = await fetch(`${this.config.endpoints.list}?${params}`, { credentials: 'same-origin' });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const payload = await response.json();
            if (!payload.success) throw new Error('API');
            this.renderTable(payload.items);
            this.renderPagination(payload.pagination || { pages: 1, page: 1, total: payload.items?.length || 0 });
        } catch (error) {
            console.error('reviews list', error);
            this.tableBody.innerHTML = '<tr><td colspan="5">No se pudieron cargar las reseñas</td></tr>';
        }
    }

    renderStats(stats = {}) {
        this.statCards.forEach((card) => {
            const metric = card.getAttribute('data-metric');
            const strong = card.querySelector('strong');
            switch (metric) {
                case 'pending':
                    strong.textContent = stats.pending_reviews ?? '--';
                    break;
                case 'approved':
                    strong.textContent = stats.approved_reviews ?? '--';
                    break;
                case 'verified':
                    strong.textContent = stats.verified_reviews ?? '--';
                    break;
                case 'average':
                    strong.textContent = stats.average_rating ?? '--';
                    break;
                default:
                    break;
            }
        });
    }

    renderDistribution(distribution = []) {
        if (!this.distributionList) return;
        if (!distribution.length) {
            this.distributionList.innerHTML = '<li>Sin datos disponibles</li>';
            return;
        }
        this.distributionList.innerHTML = distribution.map((row) => `
            <li>
                <strong>${row.rating} estrellas</strong>
                <span>${row.count} reseñas · ${row.share}%</span>
            </li>
        `).join('');
    }

    renderHighlights(highlights = []) {
        if (!this.highlightsList) return;
        if (!highlights.length) {
            this.highlightsList.innerHTML = '<li>Sin actividad reciente</li>';
            return;
        }
        this.highlightsList.innerHTML = highlights.map((item) => `
            <li>
                <strong>${item.customer_name || 'Cliente anonimo'}</strong>
                <span>${item.product_name || 'Producto'} · ${this.formatDate(item.created_at)}</span>
            </li>
        `).join('');
    }

    renderTable(items = []) {
        this.items.clear();
        if (!items.length) {
            this.tableBody.innerHTML = '<tr><td colspan="5">Sin resultados</td></tr>';
            return;
        }
        this.tableBody.innerHTML = items.map((item) => {
            this.items.set(String(item.id), item);
            const comment = (item.comment || '').slice(0, 70);
            return `
                <tr data-id="${item.id}">
                    <td>
                        <div>${item.title || 'Sin titulo'}</div>
                        <small class="text-muted">${comment}${comment.length === 70 ? '...' : ''}</small>
                    </td>
                    <td>${item.product?.name || 'Producto'}</td>
                    <td><span class="badge-ghost">${item.rating} ★</span></td>
                    <td>${this.renderStatusChip(item)}</td>
                    <td>
                        <button class="btn-soft" data-action="approve" title="Aprobar"><i class="fas fa-check"></i></button>
                        <button class="btn-soft" data-action="reject" title="Rechazar"><i class="fas fa-ban"></i></button>
                        <button class="btn-soft" data-action="verify" title="Verificar"><i class="fas fa-shield"></i></button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    renderPagination(meta) {
        if (!this.pagination || !meta) return;
        const pages = Math.max(1, meta.pages || 1);
        this.state.page = Math.max(1, Math.min(pages, this.state.page));
        const info = this.pagination.querySelector('[data-role="meta"]');
        if (info) info.textContent = `${meta.total ?? 0} reseñas`;
        const buttons = this.pagination.querySelectorAll('button[data-page]');
        const [prevBtn, nextBtn] = buttons;
        if (prevBtn) prevBtn.disabled = this.state.page <= 1;
        if (nextBtn) nextBtn.disabled = this.state.page >= pages;
    }

    async handleAction(action, id) {
        if (!id) return;
        const confirmMap = {
            approve: 'Aprobar esta reseña?',
            reject: 'Rechazar esta reseña?',
            verify: 'Marcar como verificada?'
        };
        if (confirmMap[action] && !window.confirm(confirmMap[action])) return;
        const body = JSON.stringify({ review_id: id, action });
        try {
            const response = await fetch(this.config.endpoints.update, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body
            });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const payload = await response.json();
            if (!payload.success) throw new Error('API');
            if (payload.item) {
                this.items.set(String(id), payload.item);
                this.loadList();
                if (this.detailPanel?.querySelector('.detail-body')?.hidden === false) {
                    this.openDetail(id);
                }
            } else if (payload.deleted) {
                this.state.page = 1;
                this.loadList();
            }
        } catch (error) {
            console.error('review action', error);
            alert('No se pudo completar la accion');
        }
    }

    openDetail(id) {
        const item = this.items.get(String(id));
        if (!item || !this.detailPanel) return;
        const emptyState = this.detailPanel.querySelector('[data-state="empty"]');
        const content = this.detailPanel.querySelector('[data-state="content"]');
        emptyState?.setAttribute('hidden', 'hidden');
        content?.removeAttribute('hidden');
        content.querySelector('[data-role="title"]').textContent = item.title || 'Sin titulo';
        content.querySelector('[data-role="rating"]').textContent = `${item.rating} ★`;
        content.querySelector('[data-role="customer"]').textContent = item.customer?.name || item.customer?.email || 'Cliente';
        content.querySelector('[data-role="product"]').textContent = item.product?.name || 'Producto';
        content.querySelector('[data-role="date"]').textContent = this.formatDate(item.created_at);
        content.querySelector('[data-role="comment"]').textContent = item.comment;
        const actions = content.querySelector('[data-role="detail-actions"]');
        actions?.querySelectorAll('button').forEach((btn) => {
            btn.onclick = () => this.handleAction(btn.getAttribute('data-action'), id);
        });
    }

    renderStatusChip(item) {
        const status = item.is_approved ? 'Publicado' : 'Pendiente';
        const className = item.is_approved ? 'success' : 'warning';
        const verified = item.is_verified_purchase ? ' · Verificado' : '';
        return `<span class="status-chip ${className}">${status}${verified}</span>`;
    }

    formatDate(value) {
        if (!value) return 'Sin fecha';
        return new Intl.DateTimeFormat('es-CO', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
    }

    debounce(fn, wait) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => fn(...args), wait);
        };
    }
}

if (window.REVIEWS_INBOX_CONFIG) {
    document.addEventListener('DOMContentLoaded', () => new ReviewsInbox(window.REVIEWS_INBOX_CONFIG));
}
