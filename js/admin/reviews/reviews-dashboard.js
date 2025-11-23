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
        // Ensure icons are valid after initial render and on page updates
        this.scheduleIconFallbacks();
    }

    buildMediaUrl(path) {
        if (!path && path !== 0) return null;
        let candidate = path;
        if (typeof candidate === 'object' && candidate !== null) {
            candidate = candidate.url || candidate.path || candidate.src || '';
        }
        candidate = String(candidate || '').trim();
        if (!candidate) return null;
        if (/^https?:\/\//i.test(candidate)) return candidate;
        if (candidate.startsWith('//')) {
            return window.location.protocol + candidate;
        }
        const base = (this.config.baseUrl || '').replace(/\/+$/, '');
        const normalized = candidate.replace(/^\/+/, '');
        if (!base) return '/' + normalized;
        return `${base}/${normalized}`;
    }

    resolveProductImage(item) {
        const product = item?.product || {};
        const sources = [
            product.thumbnail,
            product.thumb,
            product.image,
            product.image_url,
            product.main_image,
            product.featured_image,
            product.cover_image,
            product.media,
            item?.product_image,
            item?.product_image_url
        ];
        if (Array.isArray(product.images)) sources.push(...product.images);
        if (Array.isArray(product.gallery)) sources.push(...product.gallery);
        if (Array.isArray(product.media)) sources.push(...product.media);
        if (Array.isArray(item?.product_images)) sources.push(...item.product_images);
        for (const src of sources) {
            const url = this.buildMediaUrl(src);
            if (url) return url;
        }
        return null;
    }

    cacheDom() {
        this.container = document.getElementById('reviews-hub');
        this.statCards = this.container?.querySelectorAll('.stat-card') || [];
        this.distributionList = document.getElementById('rating-distribution');
        this.ratingCanvas = document.getElementById('reviews-rating-chart');
        this.ratingLegend = document.getElementById('reviews-rating-legend');
        this.highlightsList = document.getElementById('reviews-highlights');
        this.tableBody = document.getElementById('reviews-table');
        this.listContainer = document.getElementById('reviews-list');
        this.searchInput = document.getElementById('reviews-search');
        this.statusSelect = document.getElementById('reviews-status');
        this.ratingSelect = document.getElementById('reviews-rating');
        this.verifiedSelect = document.getElementById('reviews-verified');
        this.pagination = document.getElementById('reviews-pagination');
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

        const delegatedClick = (evt) => {
            const actionBtn = evt.target.closest('[data-action]');
            if (actionBtn) {
                evt.stopPropagation();
                const id = actionBtn.closest('[data-id]')?.getAttribute('data-id') || actionBtn.closest('[data-review-id]')?.getAttribute('data-review-id');
                this.handleAction(actionBtn.getAttribute('data-action'), id, evt);
            }
        };
        this.tableBody?.addEventListener('click', delegatedClick);
        this.listContainer?.addEventListener('click', delegatedClick);
        // keyboard support: allow Enter/Space to select/highlight a card
        this.listContainer?.addEventListener('keydown', (evt) => {
            if (evt.key !== 'Enter' && evt.key !== ' ') return;
            const card = evt.target.closest('.review-card');
            if (card) {
                evt.preventDefault();
                card.click();
            }
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
        if (!this.tableBody && !this.listContainer) return;
        if (this.listContainer) this.listContainer.innerHTML = '<div class="text-muted">Cargando...</div>';
        if (this.tableBody) this.tableBody.innerHTML = '<tr><td colspan="5">Cargando...</td></tr>';
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
        const stars = Array.from({ length: 5 }).map((_, i) => {
            if (i < fullStars) return '<i class="fas fa-star" style="color:#f59e0b"></i>';
            if (i === fullStars && half) return '<i class="fas fa-star-half-stroke" style="color:#f59e0b"></i>';
            return '<i class="far fa-star" style="color:#d1d5db"></i>';
        }).join(' ');
        return `<div class="average-star-row">${stars} <small class="text-muted">${avg}</small></div>`;
    }

    // If a Font Awesome icon isn't included in the current CDN version,
    // `::before` will have no content. This helper will find elements marked
    // with `.fa-fallback` and replace the class with a known working icon.
    ensureIconFallbacks() {
        const els = document.querySelectorAll('.fa-fallback');
        if (els.length) console.info('REVIEWS DEBUG: ensureIconFallbacks found', els.length, 'elements');
        if (!els.length) return;
        els.forEach((el) => {
            try {
                const before = getComputedStyle(el, '::before').content;
                const svg = el.querySelector('svg');
                const inlineText = (el.textContent || '').trim();
                const beforeNorm = (before || '').toString().trim();
                // If the `::before` content is missing/empty (or "none") AND there is no SVG child and no inline text, the icon glyph is likely unavailable
                if ((beforeNorm === '' || beforeNorm === 'none' || /^['"]{2}$/.test(beforeNorm)) && !svg && inlineText === '') {
                    const fallback = el.getAttribute('data-fallback') || 'fa-check-circle';
                    // Replace the class name for the icon but keep style prefix classes
                    [...el.classList].forEach(cls => {
                        const styleExceptions = ['fa-fallback', 'fas', 'fa-solid', 'far', 'fa-regular', 'fab', 'fa-brands'];
                        if (cls.startsWith('fa-') && !styleExceptions.includes(cls)) el.classList.remove(cls);
                    });
                    console.info('REVIEWS DEBUG: icon unavailable, swapping', el, '->', fallback);
                    el.classList.add(fallback);
                    el.classList.remove('fa-fallback');
                    // If fallback class still results in no glyph, inject a simple inline SVG fallback
                    const after = getComputedStyle(el, '::before').content;
                    const afterNorm = (after || '').toString().trim();
                    const svgNow = el.querySelector('svg');
                    if ((afterNorm === '' || afterNorm === 'none' || /^['"]{2}$/.test(afterNorm)) && !svgNow) {
                        const svgStr = '<svg class="inline-icon inline-icon-fallback" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15-5-5 1.41-1.41L11 14.17l7.59-7.59L20 8l-9 9z"/></svg>';
                        try { el.outerHTML = svgStr; } catch (e) { console.info('REVIEWS DEBUG: could not inject inline svg', e); }
                    }
                }
            } catch (e) {
                // If anything goes wrong, fallback anyway
                const fallback = el.getAttribute('data-fallback') || 'fa-check-circle';
                console.info('REVIEWS DEBUG: icon fallback error, adding fallback', el, fallback, e);
                el.classList.add(fallback);
                el.classList.remove('fa-fallback');
            }
        });
    }

    // Re-run fallback checks a few times and when the window fully loads
    scheduleIconFallbacks() {
        this.ensureIconFallbacks();
        setTimeout(() => this.ensureIconFallbacks(), 100);
        setTimeout(() => this.ensureIconFallbacks(), 350);
        setTimeout(() => this.ensureIconFallbacks(), 1500);
        window.addEventListener('load', () => this.ensureIconFallbacks());
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
        const ordered = [...distribution].sort((a, b) => (b.rating ?? 0) - (a.rating ?? 0));
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
                datasets: [{ data: counts, backgroundColor: ['#22c55e', '#10b981', '#06b6d4', '#3b82f6', '#6366f1'] }]
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
                    <span class="chart-legend-swatch" style="background:${['#22c55e', '#10b981', '#06b6d4', '#3b82f6', '#6366f1'][idx % 5]}"></span>
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

        // Helper function to get initials from name
        const getInitials = (name) => {
            if (!name) return '?';
            const parts = name.trim().split(' ');
            if (parts.length >= 2) {
                return (parts[0][0] + parts[1][0]).toUpperCase();
            }
            return parts[0].substring(0, 2).toUpperCase();
        };

        this.highlightsList.innerHTML = highlights.map((item) => {
            const customerName = item.customer_name || 'Cliente anónimo';
            const initials = getInitials(customerName);
            const isVerified = item.is_verified || item.is_verified_purchase || false;
            const verifiedAttr = isVerified ? ' data-verified="true"' : '';

            return `
            <li role="button" tabindex="0" data-id="${item.id}"${verifiedAttr}>
                <span class="mini-dot" aria-hidden="true">${initials}</span>
                <div class="event-content">
                    <strong class="event-title">${customerName}</strong>
                    <small class="event-desc">${item.product_name || 'Producto'}</small>
                    <div class="mt-1"><span class="badge-ghost small">${item.rating} ★</span></div>
                    <p class="text-muted mt-2">${(item.comment || '').slice(0, 160)}${(item.comment || '').length > 160 ? '...' : ''}</p>
                </div>
                <div class="event-meta">${this.formatDate(item.created_at)}</div>
            </li>
        `;
        }).join('');

        // Attach interactions to bring the matching review into view and stay keyboard-friendly
        this.highlightsList.querySelectorAll('li[tabindex]')?.forEach((li) => {
            li.addEventListener('click', () => {
                const id = li.getAttribute('data-id');
                if (id) this.highlightReview(id);
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

    highlightReview(id) {
        if (!id || !this.container) return;
        const target = this.container.querySelector(`[data-id="${id}"]`) || this.container.querySelector(`[data-review-id="${id}"]`);
        if (!target) return;
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target.classList.add('is-selected');
        setTimeout(() => target.classList.remove('is-selected'), 1200);
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
            if (this.listContainer) this.listContainer.innerHTML = `<div class="text-muted">Sin resultados${filtersText} ${clear}</div>`;
            if (this.tableBody) this.tableBody.innerHTML = `<tr><td colspan="5">Sin resultados${filtersText} ${clear}</td></tr>`;
            // wire inline clear if present
            setTimeout(() => {
                const btn = document.getElementById('reviews-inline-clear');
                btn?.addEventListener('click', () => this.clearBtn?.click());
            }, 50);
            return;
        }
        // If a card-list container is present, render as review cards to match product pages
        if (this.listContainer) {
            this.listContainer.innerHTML = items.map((item) => {
                this.items.set(String(item.id), item);
                const title = item.title || 'Sin titulo';
                const safeComment = (item.comment || '').slice(0, 160);
                const userName = item.customer?.name || item.customer?.email || 'Cliente';
                const initials = (userName.split(' ').slice(0,2).map(p=>p[0]||'').join('') || '?').toUpperCase();
                const isVerified = Boolean(item.is_verified || item.is_verified_purchase || false);
                const verifiedPill = isVerified ? '<span class="pill pill-verified"><i class="fas fa-check-circle" aria-hidden="true"></i> Verificada</span>' : '';
                const categoryName = item.product?.category?.name || item.product?.category_name || item.category_name || '';
                const statusChip = item.is_approved ? '<span class="status-chip small success"><i class="fas fa-check"></i> Publicado</span>' : '<span class="status-chip small warning"><i class="fas fa-clock"></i> Pendiente</span>';
                const productName = item.product?.name || 'Producto';
                const productImageUrl = this.resolveProductImage(item);
                const status = item.is_approved ? 'approved' : 'pending';
                const titleId = `review-title-${item.id}`;
                const reviewImagesArr = Array.isArray(item.images) ? item.images : [];
                const reviewImageUrls = reviewImagesArr.map((img) => this.buildMediaUrl(img)).filter(Boolean);
                const reviewImages = reviewImageUrls.length ? reviewImageUrls.map((url) => `<div class="review-image"><img src="${url}" alt="Imagen reseña"></div>`).join('') : '';
                const approvalPill = item.is_approved ? '<span class="pill pill-published"><i class="fas fa-check" aria-hidden="true"></i> Publicado</span>' : '<span class="pill pill-pending"><i class="fas fa-clock" aria-hidden="true"></i> Pendiente</span>';
                const statusStack = [verifiedPill, approvalPill].filter(Boolean).join('');
                const productInitial = (productName?.trim()?.charAt(0) || 'P').toUpperCase();
                const productSku = item.product?.sku ? `<span class="product-sku">SKU: ${item.product.sku}</span>` : '';
                const reviewGallery = reviewImages ? `<div class="review-gallery">${reviewImages}</div>` : '';
                return `
                    <article class="review-card" tabindex="0" data-review-id="${item.id}" data-review-rating="${item.rating}" data-status="${status}" aria-labelledby="${titleId}">
                        <div class="review-media" data-product-id="${item.product?.id || ''}" aria-label="${productName}">
                            <div class="review-product-thumb${productImageUrl ? '' : ' placeholder'}">
                                ${productImageUrl ? `<img src="${productImageUrl}" alt="${productName}">` : `<span>${productInitial}</span>`}
                            </div>
                            <div class="product-mini-meta">
                                <p class="product-name">${productName}</p>
                                ${categoryName ? `<span class="product-category">${categoryName}</span>` : ''}
                                ${productSku}
                            </div>
                        </div>
                        <div class="review-body">
                            <div class="review-header-row">
                                <div class="review-user-block">
                                    <div class="user-avatar"><span>${initials}</span></div>
                                    <div class="user-meta">
                                        <strong class="review-user">${userName}</strong>
                                        <span class="time">${this.formatDate(item.created_at)}</span>
                                    </div>
                                </div>
                                <div class="review-score">
                                    <div class="user-rating review-stars" aria-label="${item.rating} estrellas"><span class="badge-ghost">${item.rating} ★</span></div>
                                    ${statusStack ? `<div class="status-stack">${statusStack}</div>` : ''}
                                </div>
                            </div>
                            <h4 id="${titleId}" class="review-title">${title}</h4>
                            <p class="review-comment text-muted">${safeComment}${(item.comment || '').length > 160 ? '...' : ''}</p>
                            ${reviewGallery}
                        </div>
                        <div class="review-actions actions">
                            <button type="button" class="btn-soft btn-sm btn-approve" data-action="approve" title="Aprobar" aria-label="Aprobar reseña"><i class="fas fa-check" aria-hidden="true"></i></button>
                            <button type="button" class="btn-soft btn-sm btn-reject btn-delete" data-action="reject" title="Rechazar" aria-label="Rechazar reseña"><i class="fas fa-ban" aria-hidden="true"></i></button>
                            <button type="button" class="btn-soft btn-sm btn-verify btn-status" data-action="verify" title="Marcar como verificada" aria-label="Marcar como verificada"><i class="fa-solid fa-badge-check fa-fallback" data-fallback="fa-check-circle" aria-hidden="true"></i></button>
                        </div>
                    </article>
                `;
            }).join('');
        }

        // Render table fallback (existing behavior)
        this.tableBody.innerHTML = items.map((item) => {
            this.items.set(String(item.id), item);
            const comment = (item.comment || '').slice(0, 70);
            const category = item.product?.category?.name || item.product?.category_name || item.category_name || '';
            return `
                <tr data-id="${item.id}">
                    <td class="review">
                        <div>${item.title || 'Sin titulo'}</div>
                        <small class="text-muted">${comment}${comment.length === 70 ? '...' : ''} ${category ? ' · ' + category : ''}</small>
                    </td>
                    <td>${item.product?.name || 'Producto'}</td>
                    <td><span class="badge-ghost">${item.rating} ★</span></td>
                    <td>${this.renderStatusChip(item)}</td>
                    <td class="actions">
                        <button type="button" class="btn-soft btn-sm btn-approve" data-action="approve" title="Aprobar"><i class="fas fa-check"></i></button>
                        <button type="button" class="btn-soft btn-sm btn-reject btn-delete" data-action="reject" title="Rechazar"><i class="fas fa-ban"></i></button>
                        <button type="button" class="btn-soft btn-sm btn-verify btn-status" data-action="verify" title="Marcar como verificada">
                            <i class="fa-solid fa-badge-check fa-fallback" data-fallback="fa-check-circle" aria-hidden="true"></i>
                        </button>
                    </td>
                </tr>
            `;
            }).join('');

        // Post-render: hide/alter verify buttons for already verified reviews
        setTimeout(() => {
            items.forEach((item) => {
                const row = (this.tableBody && this.tableBody.querySelector(`tr[data-id="${item.id}"]`)) || (this.listContainer && this.listContainer.querySelector(`.review-card[data-review-id="${item.id}"]`));
                if (!row) return;
                const verifyBtn = row.querySelector('button[data-action="verify"]');
                const approveBtn = row.querySelector('button[data-action="approve"]');
                const isVerified = Boolean(item.is_verified || item.is_verified_purchase || false);
                    if (isVerified) {
                    // If the review is verified, indicate it in the action cell and remove the action to avoid confusion
                    verifyBtn.setAttribute('title', 'Reseña verificada');
                        verifyBtn.disabled = true;
                    verifyBtn.classList.add('btn-verified');
                        verifyBtn.setAttribute('aria-pressed', 'true');
                        verifyBtn.setAttribute('aria-label', 'Reseña verificada');
                    // Show a small check mark and tooltip instead of clickable control
                    verifyBtn.innerHTML = '<i class="fa-solid fa-check-circle"></i>';
                } else {
                    verifyBtn.setAttribute('title', 'Marcar como verificada');
                    verifyBtn.disabled = false;
                    verifyBtn.classList.remove('btn-verified');
                        verifyBtn.setAttribute('aria-pressed', 'false');
                }
                // Hide approve button if the review is already approved
                const isApproved = Boolean(item.is_approved);
                if (approveBtn) {
                    if (isApproved) {
                        approveBtn.style.display = 'none';
                    } else {
                        approveBtn.style.display = '';
                    }
                }
            });
        }, 25);

        // Debug: log first action button classes to ensure JS rendered expected classes (remove once validated)
        setTimeout(() => {
            const firstActionBtn = (this.tableBody && this.tableBody.querySelector('button[data-action]')) || (this.listContainer && this.listContainer.querySelector('button[data-action]'));
            if (firstActionBtn) console.info('REVIEWS DEBUG: first action button classes ->', firstActionBtn.className);
            const firstTableBtn = (this.tableBody && this.tableBody.querySelector('td.actions button[data-action]')) || (this.listContainer && this.listContainer.querySelector('.review-actions button[data-action]'));
            if (firstTableBtn) console.info('REVIEWS DEBUG: action button computed styles ->', getComputedStyle(firstTableBtn).backgroundColor, getComputedStyle(firstTableBtn).color);
            // Ensure color class exists on all actions - if not, add it by mapping data-action -> class
            const allActionButtons = [ ...(this.tableBody ? Array.from(this.tableBody.querySelectorAll('td.actions button[data-action]')) : []), ...(this.listContainer ? Array.from(this.listContainer.querySelectorAll('.review-actions button[data-action]')) : []) ];
            allActionButtons.forEach((btn) => {
                const act = btn.getAttribute('data-action');
                if (act && !btn.classList.contains(`btn-${act}`)) {
                    btn.classList.add(`btn-${act}`);
                    console.info('REVIEWS DEBUG: added missing class', `btn-${act}`);
                }
            });
        }, 50);
        // Ensure icon colors are applied even if other CSS overrides
        // Colors handled by CSS, but apply inline fallback for stubborn overrides (cache or later CSS).
        this.applyActionButtonStyles();
        // After table DOM updates, ensure any `fa-fallback` icons are checked and swapped
        this.ensureIconFallbacks();
    }

    applyActionButtonStyles() {
        const root = getComputedStyle(document.documentElement);
        const primary = (root.getPropertyValue('--primary-color') || root.getPropertyValue('--hub-primary') || '#0077b6').trim();
        const primaryLight = (root.getPropertyValue('--primary-light') || 'rgba(0,119,182,0.08)').trim();
        const primaryHover = (root.getPropertyValue('--primary-hover') || '#015e90').trim();
        const map = {
            approve: { bg: primaryLight, color: primary, border: primaryLight },
            reject: { bg: 'rgba(220,38,38,0.06)', color: '#dc2626', border: 'rgba(220,38,38,0.12)' },
            verify: { bg: primaryLight, color: primary, border: primaryLight }
        };
        // Table & Card buttons
        const allButtons = [ ...(this.tableBody ? Array.from(this.tableBody.querySelectorAll('td.actions button[data-action]')) : []), ...(this.listContainer ? Array.from(this.listContainer.querySelectorAll('.review-actions button[data-action]')) : []) ];
        allButtons?.forEach((btn) => {
            const act = btn.getAttribute('data-action');
            const cfg = map[act];
            if (!cfg) return;
            btn.style.backgroundColor = cfg.bg;
            btn.style.borderColor = cfg.border;
            const ic = btn.querySelector('i') || btn.querySelector('svg');
            if (ic) {
                try {
                    ic.style.color = cfg.color;
                    if (ic.tagName?.toLowerCase() === 'svg') ic.style.fill = cfg.color;
                } catch(e) {
                    // ignore css assignment errors
                }
            }
        });
        // list buttons handled earlier via `allButtons` union to keep consistent.
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

    async handleAction(action, id, evt) {
        if (!id) return;
        const confirmMap = {
            approve: '¿Aprobar esta reseña para publicación?',
            reject: '¿Rechazar esta reseña? No será visible en la tienda.',
            verify: '¿Marcar como compra verificada?'
        };
        const item = this.items.get(String(id));
        let apiAction = action;
        const extra = {};
        if (action === 'verify') {
            apiAction = 'toggle_verified';
            extra.value = item?.is_verified ? 0 : 1;
            confirmMap.verify = item?.is_verified ? '¿Marcar como NO verificada esta reseña?' : '¿Marcar como verificada esta reseña?';
        }
        const confirmMessage = confirmMap[action] || null;
        if (confirmMessage && !window.confirm(confirmMessage)) return;

        const button = evt?.target?.closest('button')
            || document.querySelector('[data-id="' + id + '"] button[data-action="' + action + '"]')
            || document.querySelector('[data-review-id="' + id + '"] button[data-action="' + action + '"]');
        const originalHTML = button?.innerHTML;
        if (button) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }

        const body = JSON.stringify(Object.assign({ review_id: id, action: apiAction }, extra));
        try {
            const response = await fetch(this.config.endpoints.update, {
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body
            });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const payload = await response.json();
            if (!payload.success) throw new Error('API');

            if (payload.item) {
                this.items.set(String(id), payload.item);
                this.loadOverview();
                this.loadList();
            } else if (payload.deleted) {
                this.state.page = 1;
                this.loadOverview();
                this.loadList();
            }
        } catch (error) {
            console.error('review action', error);
            alert('No se pudo completar la acción. Por favor intenta de nuevo.');
        } finally {
            if (button && originalHTML) {
                button.disabled = false;
                button.innerHTML = originalHTML;
            }
        }
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
