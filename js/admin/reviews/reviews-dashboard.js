class ReviewsInbox {
    constructor(config) {
        this.config = config;
        this.state = {
            page: 1,
            perPage: 10,
            search: '',
            status: 'all',
            rating: '',
            verified: ''
        };
        this.items = new Map();
        this.charts = {};
        this.cacheDom();
        // Use select value as initial state to avoid mismatch between UI and state
        this.state.status = this.statusSelect?.value || this.state.status;
        this.state.search = this.searchInput?.value || this.state.search;
        this.state.rating = this.ratingSelect?.value || this.state.rating;
        this.state.verified = this.verifiedSelect?.value || this.state.verified;
        this.bindEvents();
        this.loadOverview();
        this.loadList();
    }

    cacheDom() {
        this.container = document.getElementById('reviews-hub');
        this.statCards = this.container?.querySelectorAll('.stat-card') || [];
        this.distributionList = document.getElementById('rating-distribution');
        this.ratingCanvas = document.getElementById('reviews-rating-chart');
        this.ratingLegend = document.getElementById('reviews-rating-legend');
        this.highlightsList = document.getElementById('reviews-highlights');
        this.tableBody = document.getElementById('reviews-table');
        this.searchInput = document.getElementById('reviews-search');
        this.statusSelect = document.getElementById('reviews-status');
        this.ratingSelect = document.getElementById('reviews-rating');
        this.verifiedSelect = document.getElementById('reviews-verified');
        this.pagination = document.getElementById('reviews-pagination');
        this.detailPanel = document.getElementById('review-detail');
        this.detailToggle = document.getElementById('review-detail-toggle');
        this.refreshBtn = document.getElementById('reviews-refresh');
        this.clearBtn = document.getElementById('reviews-clear-filters');
        this.debugPanel = document.getElementById('reviews-debug');
        this.debugPre = document.getElementById('reviews-debug-pre');
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

        this.detailToggle?.addEventListener('click', (evt) => {
            evt.stopPropagation();
            if (!this.detailPanel) return;
            const expanded = this.detailPanel.classList.toggle('collapsed');
            this.detailPanel.setAttribute('aria-hidden', String(expanded));
            this.detailToggle.setAttribute('aria-expanded', String(!expanded));
        });

        this.refreshBtn?.addEventListener('click', () => {
            this.loadOverview();
            this.loadList();
        });

        this.clearBtn?.addEventListener('click', () => {
            // reset filters
            if (this.searchInput) this.searchInput.value = '';
            if (this.statusSelect) this.statusSelect.value = 'all';
            if (this.ratingSelect) this.ratingSelect.value = '';
            if (this.verifiedSelect) this.verifiedSelect.value = '';
            this.state.search = '';
            this.state.status = 'all';
            this.state.rating = '';
            this.state.verified = '';
            this.state.page = 1;
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
        const params = new URLSearchParams();
        params.set('page', this.state.page);
        params.set('per_page', this.state.perPage);
        if (this.state.search) params.set('search', this.state.search);
        if (this.state.status) params.set('status', this.state.status);
        if (this.state.rating !== '' && this.state.rating !== null && this.state.rating !== undefined) params.set('rating', this.state.rating);
        if (this.state.verified !== '' && this.state.verified !== null && this.state.verified !== undefined) params.set('verified', this.state.verified);
        try {
            const response = await fetch(`${this.config.endpoints.list}?${params}`, { credentials: 'same-origin' });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const payload = await response.json();
            if (!payload.success) {
                console.warn('reviews list API returned success=false', payload);
                throw new Error('API');
            }
            if (!payload.items || !payload.items.length) {
                console.debug('reviews list empty', { params: Object.fromEntries(params.entries()), pagination: payload.pagination, payload });
                if (this.debugPanel && this.debugPre) {
                    this.debugPanel.style.display = '';
                    this.debugPanel.removeAttribute('aria-hidden');
                    this.debugPre.textContent = 'URL: ' + this.config.endpoints.list + '?' + params.toString() + '\n\n' + JSON.stringify(payload, null, 2);
                }
            } else {
                if (this.debugPanel && this.debugPre) {
                    this.debugPanel.style.display = 'none';
                    this.debugPanel.setAttribute('aria-hidden', 'true');
                    this.debugPre.textContent = '';
                }
            }
            this.renderTable(payload.items);
            this.renderPagination(payload.pagination || { pages: 1, page: 1, total: payload.items?.length || 0 });
        } catch (error) {
            console.error('reviews list', error);
            this.tableBody.innerHTML = '<tr><td colspan="5">No se pudieron cargar las reseñas</td></tr>';
            if (this.debugPanel && this.debugPre) {
                this.debugPanel.style.display = '';
                this.debugPanel.removeAttribute('aria-hidden');
                this.debugPre.textContent = error?.message || String(error) + '\n\nURL: ' + this.config.endpoints.list + '?' + params.toString();
            }
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
                    const stars = card.querySelector('[data-role="average-stars"]');
                    if (stars) {
                        const avg = Math.round((stats.average_rating ?? 0) * 10) / 10;
                        stars.innerHTML = this.renderStars(avg);
                    }
                    break;
                default:
                    break;
            }
        });
    }

    renderStars(avg) {
        if (!avg) return '';
        const fullStars = Math.floor(avg);
        const half = (avg - fullStars) >= 0.5;
        const stars = Array.from({length: 5}).map((_, i) => {
            if (i < fullStars) return '<i class="fas fa-star" style="color:#f59e0b"></i>';
            if (i === fullStars && half) return '<i class="fas fa-star-half-stroke" style="color:#f59e0b"></i>';
            return '<i class="far fa-star" style="color:#d1d5db"></i>';
        }).join(' ');
        return `<div class="average-star-row">${stars} <small class="text-muted">${avg}</small></div>`;
    }

    renderDistribution(distribution = []) {
        // render fallback list if present
        if (this.distributionList) {
            if (!distribution.length) {
                this.distributionList.innerHTML = '<li>Sin datos disponibles</li>';
            } else {
                this.distributionList.innerHTML = distribution.map((row) => `
                    <li>
                        <strong>${row.rating} estrellas</strong>
                        <span>${row.count} reseñas · ${row.share}%</span>
                    </li>
                `).join('');
            }
        }
        // Render Chart.js donut for rating distribution
        if (!this.ratingCanvas || typeof Chart === 'undefined') return;
        // ensure descending order (5 -> 1)
        const ordered = [...distribution].sort((a,b) => (b.rating ?? 0) - (a.rating ?? 0));
        const labels = ordered.map(d => `${d.rating} ★`);
        const counts = ordered.map(d => d.count);
        // Destroy existing chart if present
        if (this.charts.rating) { this.charts.rating.destroy(); this.charts.rating = null; }
        if (!counts.length) {
            if (this.ratingCanvas) this.ratingCanvas.style.display = 'none';
            return;
        }
        this.ratingCanvas.style.display = '';
        const ctx = this.ratingCanvas.getContext('2d');
        this.charts.rating = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{ data: counts, backgroundColor: ['#22c55e','#10b981','#06b6d4','#3b82f6','#6366f1'] }]
            },
            options: {
                plugins: { legend: { display: false } },
                maintainAspectRatio: false
                ,
                onClick: (evt, elements, chart) => {
                    if (!elements.length) return;
                    const idx = elements[0].index;
                    const ratingValue = ordered[idx]?.rating;
                    if (ratingValue !== undefined) {
                        // toggle rating filter
                        if (String(this.state.rating) === String(ratingValue)) {
                            this.state.rating = '';
                        } else {
                            this.state.rating = String(ratingValue);
                        }
                        this.state.page = 1;
                        this.loadList();
                    }
                }
            }
        });
        // render legend manually
        if (this.ratingLegend) {
            this.ratingLegend.innerHTML = labels.map((l, idx) => `
                <span class="chart-legend-item">
                    <span class="chart-legend-swatch" style="background:${['#22c55e','#10b981','#06b6d4','#3b82f6','#6366f1'][idx % 5]}"></span>
                    <strong>${l}</strong>
                    <small>${counts[idx]} reseñas</small>
                </span>
            `).join('');
        }
    }

    renderHighlights(highlights = []) {
        if (!this.highlightsList) return;
        if (!highlights.length) {
            this.highlightsList.innerHTML = '<li class="text-muted">Sin actividad reciente</li>';
            return;
        }
        this.highlightsList.innerHTML = highlights.map((item) => `
            <li role="button" tabindex="0" data-id="${item.id}">
                <span class="mini-dot" aria-hidden="true"><i class="fas fa-comments" aria-hidden="true"></i></span>
                <div class="event-content">
                    <strong class="event-title">${item.customer_name || 'Cliente anónimo'}</strong>
                    <small class="event-desc">${item.product_name || 'Producto'}</small>
                    <div class="mt-1"><span class="badge-ghost small">${item.rating} ★</span></div>
                    <p class="text-muted mt-2">${(item.comment || '').slice(0, 160)}${(item.comment || '').length > 160 ? '...' : ''}</p>
                </div>
                <div class="event-meta">${this.formatDate(item.created_at)}</div>
            </li>
        `).join('');

        // Attach interactions to timeline items for keyboard and pointer accessibility
        this.highlightsList.querySelectorAll('li[tabindex]')?.forEach((li) => {
            li.addEventListener('click', () => {
                const id = li.getAttribute('data-id');
                // try to open detail if the ID is present in the list cache
                if (id) this.openDetail(id);
                li.classList.toggle('selected');
            });
            li.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    li.click();
                }
            });
        });
    }

    renderTable(items = []) {
        this.items.clear();
        if (!items.length) {
            const filters = [];
            if (this.state.search) filters.push(`Buscar: "${this.state.search}"`);
            if (this.state.status && this.state.status !== 'all') filters.push(`Estado: ${this.state.status}`);
            if (this.state.rating) filters.push(`Rating: ${this.state.rating}`);
            if (this.state.verified !== '') filters.push(`Verificado: ${this.state.verified}`);
            const filtersText = filters.length ? ` — filtros activos: ${filters.join(', ')}` : '';
            const clear = this.clearBtn ? `<button class="btn-soft" id="reviews-inline-clear">Limpiar filtros</button>` : '';
            this.tableBody.innerHTML = `<tr><td colspan="5">Sin resultados${filtersText} ${clear}</td></tr>`;
            // wire inline clear if present
            setTimeout(() => {
                const btn = document.getElementById('reviews-inline-clear');
                btn?.addEventListener('click', () => this.clearBtn?.click());
            }, 50);
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
            approve: '¿Aprobar esta reseña? ',
            reject: '¿Rechazar esta reseña? ',
            verify: '¿Marcar como verificada? '
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
        // highlight selected row
        this.tableBody?.querySelectorAll('tr[data-id]')?.forEach((r) => r.classList.toggle('is-selected', r.getAttribute('data-id') === String(id)));
        // expand detail panel
        this.detailPanel.classList.remove('collapsed');
        this.detailPanel.setAttribute('aria-hidden', 'false');
        if (this.detailToggle) this.detailToggle.setAttribute('aria-expanded', 'true');
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
        const verified = (item.is_verified || item.is_verified_purchase) ? ' · Verificado' : '';
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
