class ClientsDashboard {
    constructor(config) {
        this.config = config;
        this.state = {
            page: 1,
            perPage: 10,
            search: '',
            segment: 'all',
            sort: 'recent',
            range: 30
        };
        this.charts = {
            acquisition: null,
            segments: null
        };
        this.theme = this.captureTheme();
        this.trendCollapsed = false;
        this.cacheDom();
        this.bindEvents();
        this.loadOverview();
        this.loadList();
        this.runEntranceAnimation();
    }

    updateRangeButtonsUI() {
        Array.from(this.rangeButtons || []).forEach((btn) => {
            const isActive = parseInt(btn.dataset.range, 10) === this.state.range;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-pressed', String(isActive));
        });
        // Update acquisition subtitle to show the number of weeks shown in the trend.
        if (this.acquisitionSubtitle) {
            const weeks = Math.ceil((this.state.range || 30) / 7);
            this.acquisitionSubtitle.textContent = `Comparativo últimas ${weeks} semana${weeks > 1 ? 's' : ''}.`;
        }
        // Debug: log current state and which buttons are active
        if (window && window.console && typeof window.console.debug === 'function') {
            const active = Array.from(this.rangeButtons || []).map((btn) => btn.classList.contains('active') ? btn.dataset.range : null).filter(Boolean);
            console.debug('[ClientsDashboard] updateRangeButtonsUI', 'state.range:', this.state.range, 'active:', active.join(','));
        }
    }

    runEntranceAnimation() {
        const nodes = [ ...(this.statCards || []), ...(this.chartCards || []), this.tableCard].filter(Boolean);
        nodes.forEach((el, i) => {
            setTimeout(() => {
                el.classList.add('card-appear');
            }, i * 60);
        });
    }

    cacheDom() {
        this.hub = document.getElementById('clients-hub');
        this.statCards = this.hub?.querySelectorAll('.stat-card') || [];
        this.segmentContainer = document.getElementById('client-segments');
        this.trendList = document.getElementById('acquisition-trend');
        this.trendContainer = this.trendList?.closest('article');
        this.trendSummaryEl = this.trendContainer?.querySelector('.trend-summary') || null;
        this.trendToggleBtn = null; // assigned when rendering
        // Acquire the subtitle element for the weekly acquisition section to
        // update it when the user selects a different range.
        this.acquisitionSubtitle = this.trendList?.closest('article')?.querySelector('.section-header p');
        this.engagementContainer = document.getElementById('engagement-matrix');
        this.topCustomersList = document.getElementById('top-customers');
        this.searchInput = document.getElementById('clients-search');
        this.segmentSelect = document.getElementById('clients-segment');
        this.sortSelect = document.getElementById('clients-sort');
        this.tableBody = document.getElementById('clients-table-body');
        this.pagination = document.getElementById('clients-pagination');
        this.detailPanel = document.getElementById('client-detail-panel');
        this.detailToggleBtn = document.getElementById('client-detail-toggle');
        this.refreshBtn = document.getElementById('clients-refresh-btn');
        this.rangeButtons = document.querySelectorAll('#clients-growth-card .chart-range');
        this.acquisitionCanvas = document.getElementById('clients-acquisition-chart');
        this.segmentsCanvas = document.getElementById('clients-segments-chart');
        this.segmentsLegend = document.getElementById('clients-segments-legend');
        this.emptyStates = {
            acquisition: document.querySelector('[data-empty="acquisition"]'),
            segments: document.querySelector('[data-empty="segments"]')
        };
        this.chartCards = this.hub?.querySelectorAll('.chart-card') || [];
        this.tableCard = this.hub?.querySelector('.table-card');

        // If there's an active range in the DOM, use it to override the default
        // state. This keeps the UI and the internal state in sync (e.g. HTML may
        // show 30d active, while the state default was 90).
        try {
            const activeBtn = Array.from(this.rangeButtons || []).find((b) => b.classList.contains('active'));
            if (activeBtn && activeBtn.dataset && activeBtn.dataset.range) {
                this.state.range = parseInt(activeBtn.dataset.range, 10);
            }
            // Ensure aria-pressed is set correctly on range buttons for a11y
            Array.from(this.rangeButtons || []).forEach((btn) => {
                btn.setAttribute('aria-pressed', String(btn.classList.contains('active')));
            });
        } catch (e) {
            // ignore if anything fails; state will stay as default
        }
    }

    bindEvents() {
        if (this.searchInput) {
            this.searchInput.addEventListener('input', this.debounce((evt) => {
                this.state.search = evt.target.value.trim();
                this.state.page = 1;
                this.loadList();
            }, 350));
        }

        this.segmentSelect?.addEventListener('change', (evt) => {
            this.state.segment = evt.target.value;
            this.state.page = 1;
            this.loadList();
        });

        // When segment select changes programmatically, keep tiles highlighted
        this.segmentSelect?.addEventListener('change', (evt) => {
            const val = evt.target.value;
            this.segmentContainer?.querySelectorAll('.segment-tile')?.forEach((tile) => {
                tile.classList.toggle('selected', tile.getAttribute('data-segment') === val);
            });
        });

        this.sortSelect?.addEventListener('change', (evt) => {
            this.state.sort = evt.target.value;
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

        this.refreshBtn?.addEventListener('click', () => {
            this.loadOverview();
            this.loadList();
        });

        this.rangeButtons?.forEach((button) => {
            button.addEventListener('click', () => {
                const selectedRange = parseInt(button.dataset.range, 10);
                if (!selectedRange || selectedRange === this.state.range) {
                    return;
                }
                if (window && window.console && typeof window.console.debug === 'function') {
                    console.debug('[ClientsDashboard] range click', 'prev:', this.state.range, 'selected:', selectedRange);
                }
                this.state.range = selectedRange;
                // Update active class and aria-pressed for all buttons so the UI
                // and a11y attributes remain in sync.
                Array.from(this.rangeButtons || []).forEach((btn) => {
                    const isActive = parseInt(btn.dataset.range, 10) === this.state.range;
                    btn.classList.toggle('active', isActive);
                    btn.setAttribute('aria-pressed', String(isActive));
                });
                this.loadOverview();
            });
        });

        // Detail panel toggle button (close / open)
        this.detailToggleBtn?.addEventListener('click', () => {
            const expanded = this.detailToggleBtn.getAttribute('aria-expanded') === 'true';
            const newExpanded = !expanded;
            this.detailToggleBtn.setAttribute('aria-expanded', String(newExpanded));
            this.detailToggleBtn.title = newExpanded ? 'Cerrar panel' : 'Abrir panel';
            this.detailToggleBtn.setAttribute('aria-label', newExpanded ? 'Cerrar panel' : 'Abrir panel');
            // toggle collapsed state and aria-hidden for the detail panel
            if (this.detailPanel) {
                this.detailPanel.classList.toggle('collapsed', !newExpanded);
                this.detailPanel.setAttribute('aria-hidden', String(!newExpanded));
            }
        });
    }

    setOverviewLoading(isLoading) {
        this.statCards?.forEach((card) => {
            card.classList.toggle('is-loading', !!isLoading);
            const placeholderClass = 'stat-placeholder';
            const existing = card.querySelector(`.skeleton-line.${placeholderClass}`);
            if (isLoading && !existing) {
                const el = document.createElement('div');
                el.className = `skeleton-line shimmer ${placeholderClass}`;
                el.style.width = '40%';
                el.style.height = '18px';
                el.style.marginTop = '8px';
                card.appendChild(el);
            } else if (!isLoading && existing) {
                existing.remove();
            }
        });
        this.chartCards?.forEach((card) => {
            card.classList.toggle('is-loading', !!isLoading);
            const chartBody = card.querySelector('.chart-body');
            if (!chartBody) return;
            const existing = chartBody.querySelector('.skeleton-line.chart-placeholder');
            if (isLoading && !existing) {
                const el = document.createElement('div');
                el.className = 'skeleton-line shimmer chart-placeholder';
                el.style.height = '12px';
                el.style.width = '70%';
                el.style.position = 'absolute';
                el.style.left = '1.6rem';
                el.style.top = '1.6rem';
                chartBody.appendChild(el);
            } else if (!isLoading && existing) {
                existing.remove();
            }
        });
    }

    setListLoading(isLoading) {
        if (!this.tableBody) return;
        this.tableCard?.classList.toggle('is-loading', !!isLoading);
        if (isLoading) {
            const rows = Math.min(8, Math.max(3, this.state.perPage || 4));
            let placeholder = '';
            for (let i = 0; i < rows; i++) {
                const width = 100 - (i * (40 / rows));
                placeholder += `<tr class="skeleton-row"><td colspan="5"><div class="skeleton-line shimmer" style="height:12px;border-radius:8px;width:${width}%"></div></td></tr>`;
            }
            this.tableBody.innerHTML = placeholder;
        }
    }

    async loadOverview() {
        // Keep the range UI synced before the request so the correct range
        // is encoded in the request and visually indicated to the user.
        this.updateRangeButtonsUI();
        this.setOverviewLoading(true);
        try {
            // Append a cache-busting timestamp during development to ensure the server
            // returns fresh values when testing UI range changes.
            const reqUrl = `${this.config.endpoints.overview}?range=${this.state.range}&_=${Date.now()}`;
            if (window && window.console && typeof window.console.debug === 'function') {
                console.debug('[ClientsDashboard] loading overview', 'url:', reqUrl, 'range:', this.state.range);
            }
            const response = await fetch(reqUrl, { credentials: 'same-origin' });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const payload = await response.json();
            if (!payload.success) return;
            this.renderStats(payload.stats);
            this.renderSegments(payload.segments);
            this.renderTrend(payload.acquisition_trend);
            this.renderEngagement(payload.engagement_matrix);
            this.renderTopCustomers(payload.top_customers);
        } catch (error) {
            console.error('clients overview', error);
        }
        finally {
            this.setOverviewLoading(false);
        }
    }

    async loadList() {
        if (!this.tableBody) return;
        this.setListLoading(true);
        const query = new URLSearchParams({
            page: this.state.page,
            per_page: this.state.perPage,
            search: this.state.search,
            segment: this.state.segment,
            sort: this.state.sort
        });
        try {
            const response = await fetch(`${this.config.endpoints.list}?${query.toString()}`, { credentials: 'same-origin' });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const payload = await response.json();
            if (!payload.success) throw new Error('API error');
            this.renderTable(payload.items);
            this.renderPagination(payload.pagination);
        } catch (error) {
            console.error('clients list', error);
            this.tableBody.innerHTML = '<tr><td colspan="5">No se pudo cargar la informacion</td></tr>';
        }
        finally {
            this.setListLoading(false);
        }
    }

    renderStats(stats = {}) {
        this.statCards.forEach((card) => {
            const metric = card.getAttribute('data-metric');
            const strong = card.querySelector('strong');
            const delta = card.querySelector('.delta');
            switch (metric) {
                case 'total':
                    strong.textContent = stats.total_customers?.toLocaleString('es-CO') || '--';
                    break;
                case 'new':
                    strong.textContent = stats.new_customers?.toLocaleString('es-CO') || '--';
                    delta.textContent = 'Ultimos 30 dias';
                    break;
                case 'active':
                    strong.textContent = stats.active_customers?.toLocaleString('es-CO') || '--';
                    break;
                case 'repeat':
                    strong.textContent = `${stats.repeat_rate ?? '--'}%`;
                    break;
                case 'ltv':
                    strong.textContent = this.formatCurrency(stats.lifetime_value);
                    break;
                case 'ticket':
                    // Mostrar 'valor promedio por pedido' (etiqueta en UI ya traducida)
                    strong.textContent = this.formatCurrency(stats.avg_ticket);
                    break;
                default:
                    break;
            }
        });
    }

    renderSegments(segments = []) {
        this.renderSegmentsChart(segments);
        if (!this.segmentContainer) return;
        if (!segments.length) {
            this.segmentContainer.innerHTML = '<p class="text-muted">Sin segmentos disponibles</p>';
            return;
        }
        const total = segments.reduce((s, seg) => s + (Number(seg.count) || 0), 0) || 1;
        this.segmentContainer.innerHTML = segments.map((segment) => {
            const value = Number(segment.count) || 0;
            const percentage = total ? Math.round((value / total) * 100) : 0;
            return `
            <article class="segment-tile" data-segment="${segment.key}">
                <div class="tile-head">
                    <h3>${segment.label}</h3>
                    <div class="count-badge"><strong>${value.toLocaleString('es-CO')}</strong><small>${percentage}%</small></div>
                </div>
                <p>${segment.description}</p>
            </article>
        `;
        }).join('');
        // after rendering segments, rebind click to tiles to update highlight and select first client
        this.segmentContainer.querySelectorAll('.segment-tile').forEach((tile) => {
            tile.addEventListener('click', () => {
                const seg = tile.getAttribute('data-segment');
                // apply segment to filter
                if (this.segmentSelect) {
                    this.segmentSelect.value = seg;
                    this.segmentSelect.dispatchEvent(new Event('change'));
                } else {
                    this.state.segment = seg;
                    this.loadList();
                }
                // clear selection and if any rows exist select first one automatically
                const firstRow = this.tableBody?.querySelector('tr[data-id]');
                if (firstRow) {
                    const id = firstRow.getAttribute('data-id');
                    if (id) setTimeout(() => this.selectCustomer(id), 100);
                }
            });
        });

        // Add interaction to tiles: focus/activate a segment to filter list
        this.segmentContainer.querySelectorAll('.segment-tile').forEach((tile) => {
            tile.addEventListener('click', () => {
                const seg = tile.getAttribute('data-segment');
                if (!seg) return;
                if (this.segmentSelect) {
                    this.segmentSelect.value = seg;
                    this.segmentSelect.dispatchEvent(new Event('change'));
                } else {
                    this.state.segment = seg;
                    this.loadList();
                }
            });
            // Provide the description as a tooltip for accessibility
            const p = tile.querySelector('p');
            if (p && p.textContent) tile.setAttribute('title', p.textContent.trim());
        });
        // highlight selected segment if any
        const selectVal = this.segmentSelect?.value || this.state.segment || 'all';
        Array.from(this.segmentContainer.querySelectorAll('.segment-tile')).forEach((tile) => {
            tile.classList.toggle('selected', tile.getAttribute('data-segment') === selectVal);
        });
    }

    renderTrend(trend = []) {
        this.renderAcquisitionChart(trend);
        if (!this.trendList) return;
        if (!trend.length) {
            this.trendList.innerHTML = '<li>No hay datos suficientes</li>';
            return;
        }
        // Build summary information: total and average
        const counts = trend.map((t) => Number(t.count) || 0);
        const total = counts.reduce((s, v) => s + v, 0);
        const weeks = trend.length || 0;
        const average = weeks ? Math.round(total / weeks) : 0;

        // Create or update the trend summary UI
        this.upsertTrendSummary({ total, average, weeks });

        // Render list items (keeps full list in DOM; collapsed view will hide via CSS and JS)
        this.trendList.innerHTML = trend.map((point) => {
            const width = Math.min(100, (point.count || 0) * 4);
            return `
                <li>
                    <strong>${point.label}</strong>
                    <span>${point.count} clientes</span>
                    <div class="progress-bar" style="background: var(--hub-primary-soft); border-radius: 8px; margin-top: 0.35rem;">
                        <div style="height:6px;background:var(--hub-primary);width:${width}%"></div>
                    </div>
                </li>
            `;
        }).join('');

        // If the trend is long, default collapse it for readability and add toggle
        const COLLAPSE_LIMIT = 8;
        if (trend.length > COLLAPSE_LIMIT) {
            this.trendCollapsed = true;
            this.trendList.classList.add('collapsed');
            this.addTrendToggle();
        } else {
            this.trendCollapsed = false;
            this.trendList.classList.remove('collapsed');
            this.removeTrendToggle();
        }
    }

    upsertTrendSummary({ total, average, weeks }) {
        if (!this.trendContainer) return;
        // Build HTML for summary; reuse existing element if present
        const html = `
            <div class="trend-summary" role="status">
                <div>
                    <strong>${total.toLocaleString('es-CO')} clientes</strong>
                    <small>Promedio ${average} / semana • Últimas ${weeks} semana${weeks > 1 ? 's' : ''}</small>
                </div>
                <div class="trend-controls">
                    <button class="btn-soft btn-mini" aria-expanded="false">Ver todas</button>
                </div>
            </div>
        `;
        if (this.trendSummaryEl) {
            this.trendSummaryEl.outerHTML = html;
            this.trendSummaryEl = this.trendContainer.querySelector('.trend-summary');
        } else {
            this.trendContainer.insertAdjacentHTML('afterbegin', html);
            this.trendSummaryEl = this.trendContainer.querySelector('.trend-summary');
        }
        // Ensure toggle button ref is updated and event bound
        this.trendToggleBtn = this.trendSummaryEl.querySelector('button');
        if (this.trendToggleBtn) {
            this.trendToggleBtn.addEventListener('click', () => {
                this.trendCollapsed = !this.trendCollapsed;
                this.toggleTrendCollapsed(this.trendCollapsed);
            });
            // Set initial accessible label
            this.trendToggleBtn.setAttribute('aria-expanded', String(!this.trendCollapsed));
            this.trendToggleBtn.textContent = this.trendCollapsed ? 'Ver todas' : 'Ver menos';
        }
    }

    addTrendToggle() {
        if (!this.trendSummaryEl) return;
        // Keep button updated
        this.trendToggleBtn = this.trendSummaryEl.querySelector('button');
        if (!this.trendToggleBtn) return;
        this.trendToggleBtn.setAttribute('aria-expanded', String(!this.trendCollapsed));
        this.trendToggleBtn.textContent = this.trendCollapsed ? 'Ver todas' : 'Ver menos';
    }

    removeTrendToggle() {
        if (!this.trendSummaryEl) return;
        const btn = this.trendSummaryEl.querySelector('button');
        if (btn) btn.remove();
    }

    toggleTrendCollapsed(collapsed) {
        if (!this.trendList) return;
        this.trendList.classList.toggle('collapsed', !!collapsed);
        if (this.trendToggleBtn) {
            this.trendToggleBtn.setAttribute('aria-expanded', String(!collapsed));
            this.trendToggleBtn.textContent = collapsed ? 'Ver todas' : 'Ver menos';
        }
    }

    renderEngagement(matrix = {}) {
        if (!this.engagementContainer) return;
        const orders = matrix.orders || {};
        const recency = matrix.recency || {};
        const totalOrders = Object.values(orders).reduce((s, v) => s + (v || 0), 0);
        const totalRecency = Object.values(recency).reduce((s, v) => s + (v || 0), 0);
        this.engagementContainer.innerHTML = `
            <article class="segment-tile" data-type="orders">
                <div class="tile-head">
                    <h3>Por pedidos</h3>
                    <div class="count-badge"><strong>${totalOrders.toLocaleString('es-CO')}</strong><small>clientes</small></div>
                </div>
                ${this.renderMiniList(orders, 'orders', totalOrders)}
            </article>
            <article class="segment-tile" data-type="recency">
                <div class="tile-head">
                    <h3>Por recencia</h3>
                    <div class="count-badge"><strong>${totalRecency.toLocaleString('es-CO')}</strong><small>clientes</small></div>
                </div>
                ${this.renderMiniList(recency, 'recency', totalRecency)}
            </article>
        `;

        // Add click handlers for mini-list items to apply filters when possible
        const items = this.engagementContainer.querySelectorAll('.mini-list li');
        // tile selection for engagement containers
        this.engagementContainer.querySelectorAll('.segment-tile').forEach((tile) => {
            tile.addEventListener('click', (evt) => {
                // prevent tile click from clashing with item click (only target tile itself)
                if (evt.target.closest('.mini-list')) return;
                this.engagementContainer.querySelectorAll('.segment-tile').forEach(t => t.classList.remove('selected'));
                tile.classList.add('selected');
            });
        });
        items.forEach((li) => {
            li.addEventListener('click', () => {
                const bucket = li.getAttribute('data-bucket');
                const type = li.getAttribute('data-type');
                const seg = this.bucketToSegment(type, bucket);
                if (seg) {
                    if (this.segmentSelect) {
                        this.segmentSelect.value = seg;
                        this.segmentSelect.dispatchEvent(new Event('change'));
                    } else {
                        this.state.segment = seg;
                        this.loadList();
                    }
                } else {
                    // If no segment mapping, highlight selection only
                    items.forEach((s) => s.classList.remove('active'));
                    li.classList.add('active');
                }
            });
            // keyboard support for accessibility: Enter / Space triggers click
            li.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    li.click();
                }
            });
            // Tooltip from text already handled on segment tiles; fallback to title attr
            if (!li.getAttribute('title')) li.setAttribute('title', li.querySelector('span')?.textContent || '');
        });
    }

    renderMiniList(bucket) {
        // versions: renderMiniList(bucket, type, total)
        const args = Array.from(arguments);
        const mapBucket = args[0] || {};
        const type = args[1] || null;
        const total = Number(args[2] || Object.values(mapBucket).reduce((s, v) => s + (v || 0), 0)) || 1;
        const entries = Object.entries(mapBucket);
        if (!entries.length) return '<p class="text-muted">Sin datos</p>';
        return `<ul class="text-muted mini-list">${entries.map(([label, value]) => {
            const human = this.humanizeLabel(label);
            const v = Number(value || 0);
            const pct = total ? Math.round((v / total) * 100) : 0;
            return `<li role="button" tabindex="0" data-bucket="${label}" data-type="${type}" data-count="${v}" title="${human}: ${v} clientes - ${pct}%">` +
                `<svg class="mini-dot" viewBox="0 0 12 12" width="12" height="12" aria-hidden="true"><circle cx="6" cy="6" r="6" fill="var(--hub-primary)"/></svg>` +
                `<span class="mini-label">${human}</span>` +
                `<div class="mini-metrics"><strong class="mini-count">${v.toLocaleString('es-CO')}</strong><small class="mini-pct">${pct}%</small></div>` +
            `</li>`;
        }).join('')}</ul>`;
    }

    humanizeLabel(s) {
        if (!s) return '';
        // map known tokens to friendly labels
        const mapping = {
            'sin_historial': 'Sin historial',
            'sin_pedidos': 'Sin pedidos',
            'sin historial': 'Sin historial',
            'sin pedidos': 'Sin pedidos'
        };
        const key = s.toLowerCase();
        if (mapping[key]) return mapping[key];
        // Replace underscores with spaces, keep ranges and dashes
        const cleaned = s.replace(/_/g, ' ');
        // Capitalize first letter
        return cleaned.charAt(0).toUpperCase() + cleaned.slice(1);
    }

    bucketToSegment(type, label) {
        if (!label) return null;
        // normalize
        const l = label.toLowerCase();
        if (type === 'orders') {
            if (l.includes('sin_pedidos') || l.includes('sin pedidos')) return 'no_orders';
            if (l.includes('2-4') || l.includes('5+') || l.includes('2-4 pedidos') || l.includes('5+ pedidos')) return 'repeat';
            // fallback: 1 pedido -> keep as is, no mapping
        }
        if (type === 'recency') {
            if (l.includes('sin_historial') || l.includes('sin historial')) return null;
            // older than 60 days -> 'inactive'
            if (l.match(/\b(61|90|90\+|90)\b/) || l.includes('61-90') || l.includes('90+')) return 'inactive';
            // For 0-30 we could show 'recent' but it's not equivalent to registrations
            if (l.includes('0-30')) return 'recent';
        }
        return null;
    }

    renderTopCustomers(list = []) {
        if (!this.topCustomersList) return;
        if (!list.length) {
            this.topCustomersList.innerHTML = '<li>Sin clientes destacados</li>';
            return;
        }
        // top customers list should not touch the main table. Render top customers below.
        // Insert or update summary in the article containing the top customers list
        const topContainer = this.topCustomersList?.closest('article');
        if (topContainer) {
            const headerEl = topContainer.querySelector('.section-header') || topContainer.querySelector('.surface-header');
            const existing = topContainer.querySelector('.trend-summary');
            // build a small summary for the top customers area
            const totalSpent = list.reduce((s, it) => s + (Number(it.total_spent) || 0), 0);
            const avg = list.length ? Math.round(totalSpent / list.length) : 0;
            const summaryHtml = `
                <div class="trend-summary" role="status">
                    <div>
                        <strong>${list.length} clientes</strong>
                        <small>Gasto promedio ${this.formatCurrency(avg)} • Top ${list.length}</small>
                    </div>
                    <div class="trend-controls">
                        <button class="btn-soft btn-mini" aria-expanded="false">Ver todos</button>
                    </div>
                </div>
            `;
            if (existing) existing.outerHTML = summaryHtml;
            else if (headerEl) headerEl.insertAdjacentHTML('afterend', summaryHtml);
            else topContainer.insertAdjacentHTML('afterbegin', summaryHtml);
        }
        this.topCustomersList.innerHTML = list.map((item) => {
            const name = item.name || item.email;
            const initials = this.getInitials(name);
            const bg = this.computeNameColor(name);
            return `
            <li role="button" tabindex="0" data-id="${item.id}"><span class="avatar" style="background:${bg}">${initials}</span>
                <strong>${name}</strong>
                <span>${this.formatCurrency(item.total_spent)} • ${item.orders_count} pedidos</span>
            </li>
        `;
        }).join('');
        // Add click/keyboard interactions to open detail from top customers
            this.topCustomersList.querySelectorAll('li[data-id]').forEach((li) => {
                li.addEventListener('click', () => {
                    const id = li.getAttribute('data-id');
                    if (!id) return;
                    this.selectCustomer(id, { source: 'top' });
                });
            li.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    li.click();
                }
            });
        });
            // ensure only the last span (the meta info) gets the top-customer-meta class,
            // avoid accidentally tagging the avatar span which would break the layout
            this.topCustomersList.querySelectorAll('li[data-id]').forEach((li) => {
                const meta = li.querySelector('span:not(.avatar)');
                if (meta) meta.classList.add('top-customer-meta');
            });
        // collapse after a limit for readability
        const COLLAPSE_LIMIT = 6;
        if (list.length > COLLAPSE_LIMIT) {
            this.topCustomersList.classList.add('collapsed');
            const btn = topContainer ? topContainer.querySelector('.trend-summary button') : this.topCustomersList.querySelector('.trend-summary button');
            if (btn) {
                btn.textContent = 'Ver todos';
                btn.addEventListener('click', () => {
                    const expanded = btn.getAttribute('aria-expanded') === 'true';
                    btn.setAttribute('aria-expanded', String(!expanded));
                    btn.textContent = expanded ? 'Ver todos' : 'Ver menos';
                    this.topCustomersList.classList.toggle('collapsed');
                });
            }
        } else {
            // ensure no toggle button is visible if the list is short
            const btn = topContainer ? topContainer.querySelector('.trend-summary button') : this.topCustomersList.querySelector('.trend-summary button');
            if (btn) btn.remove();
            this.topCustomersList.classList.remove('collapsed');
        }
        // Set initial focus/aria selection states for a11y
        this.topCustomersList.querySelectorAll('li[data-id]').forEach((li) => li.setAttribute('role', 'button'));
    }

    renderAcquisitionChart(trend = []) {
        if (!this.acquisitionCanvas || typeof Chart === 'undefined') return;
        const counts = Array.isArray(trend) ? trend.map((point) => Number(point.count) || 0) : [];
        const hasData = counts.some((value) => value > 0);
        // Debug: log counts and hasData to diagnose incorrect empty overlay
        if (window && window.console && typeof window.console.debug === 'function') {
            console.debug('[ClientsDashboard] acquisition counts:', counts, 'hasData:', hasData);
        }
        this.toggleEmptyState('acquisition', hasData);
        if (!hasData) {
            this.destroyChart('acquisition');
            this.clearCanvas(this.acquisitionCanvas);
            return;
        }
        const labels = trend.map((point) => point.label);
        const ctx = this.acquisitionCanvas.getContext('2d');
        const gradient = ctx?.createLinearGradient(0, 0, 0, this.acquisitionCanvas.height || 300);
        if (gradient) {
            gradient.addColorStop(0, this.theme.primarySoft || 'rgba(29,78,216,0.25)');
            gradient.addColorStop(1, 'rgba(255,255,255,0)');
        }
        this.destroyChart('acquisition');
        this.charts.acquisition = new Chart(this.acquisitionCanvas, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Clientes nuevos',
                        data: counts,
                        borderColor: this.theme.primary,
                        backgroundColor: gradient || this.theme.primarySoft,
                        tension: 0.35,
                        borderWidth: 3,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: this.theme.primary
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.formattedValue} clientes`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: this.theme.textMuted }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            color: this.theme.textMuted
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.25)'
                        }
                    }
                }
            }
        });
        // Also expose a method to highlight a row in the top customers list
        this.topCustomersList.querySelectorAll('li[data-id]').forEach((li) => {
            li.classList.remove('selected');
        });
    }

    // Utility to highlight customer elements in UI
    highlightById(id) {
        if (!id) return;
        // table
        // remove previous badge icon
        this.tableBody?.querySelectorAll('.selected-badge')?.forEach((el) => el.remove());
        this.tableBody?.querySelectorAll('tr')?.forEach((r) => {
            const is = r.getAttribute('data-id') === String(id);
            r.classList.toggle('selected', is);
            r.setAttribute('aria-selected', String(is));
        });
        // add badge to selected row inside the .table-primary element for correct layout
        const newRow = this.tableBody?.querySelector(`tr[data-id="${id}"]`);
        if (newRow) {
            const primary = newRow.querySelector('.table-primary');
            if (primary && !primary.querySelector('.selected-badge')) {
                const badge = document.createElement('span');
                badge.className = 'selected-badge';
                badge.setAttribute('aria-hidden', 'true');
                // small check mark SVG (white) without extra circle, the background circle is provided by CSS
                badge.innerHTML = `<svg viewBox="0 0 24 24" width="14" height="14" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path fill="#fff" d="M20.3 6.3a1 1 0 0 0-1.4-1.4l-8.2 8.2-3-3a1 1 0 1 0-1.4 1.4l3.7 3.7a1 1 0 0 0 1.4 0l9.7-9.9z"/></svg>`;
                //insert before the avatar to keep it aligned with name and avatar
                const avatar = primary.querySelector('.avatar');
                if (avatar) primary.insertBefore(badge, avatar);
                else primary.prepend(badge);
            }
        }
        // top customers: add both selected class and aria-selected state for assistive tech
        this.topCustomersList?.querySelectorAll('li')?.forEach((li) => {
            const is = li.getAttribute('data-id') === String(id);
            li.classList.toggle('selected', is);
            li.setAttribute('aria-selected', String(is));
            li.setAttribute('aria-pressed', String(is));
        });
        // ensure selected row is visible
        const row = this.tableBody?.querySelector(`tr[data-id="${id}"]`);
        if (row) row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // UI selection: highlight and show details
    selectCustomer(id, opts = {}) {
        if (!id) return;
        // remove previously selected rows and add to new
        this.highlightById(id);
        // load details with the new selection
        this.openDetail(id);
    }

    renderSegmentsChart(segments = []) {
        if (!this.segmentsCanvas || typeof Chart === 'undefined') return;
        const dataset = Array.isArray(segments) ? segments.map((segment) => Number(segment.count) || 0) : [];
        const hasData = dataset.some((value) => value > 0);
        // Debug: log dataset and hasData for troubleshooting overlay visibility
        if (window && window.console && typeof window.console.debug === 'function') {
            console.debug('[ClientsDashboard] segments dataset:', dataset, 'hasData:', hasData);
        }
        this.toggleEmptyState('segments', hasData);
        if (!hasData) {
            this.destroyChart('segments');
            this.clearCanvas(this.segmentsCanvas);
            this.updateSegmentsLegend([]);
            return;
        }
        const labels = segments.map((segment) => segment.label);
        const colors = this.theme.palette?.length ? this.theme.palette : ['#0077b6', '#48cae4', '#5c6ac4', '#f59e0b', '#059669', '#dc2626'];
        this.destroyChart('segments');
        this.charts.segments = new Chart(this.segmentsCanvas, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [
                    {
                        data: dataset,
                        backgroundColor: labels.map((_, index) => colors[index % colors.length]),
                        borderWidth: 0,
                        hoverOffset: 6
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
        this.updateSegmentsLegend(segments, colors);
    }

    updateSegmentsLegend(segments, colors) {
        if (!this.segmentsLegend) return;
        if (!segments.length) {
            this.segmentsLegend.innerHTML = '<p class="text-muted">Sin distribución disponible</p>';
            return;
        }
        const total = segments.reduce((sum, segment) => sum + (Number(segment.count) || 0), 0) || 1;
        this.segmentsLegend.innerHTML = segments.map((segment, index) => {
            const value = Number(segment.count) || 0;
            const percentage = Math.round((value / total) * 100);
            return `
                <span class="chart-legend-item">
                    <span class="chart-legend-swatch" style="background:${colors[index % colors.length]}"></span>
                    <span>${segment.label}</span>
                    <strong>${value.toLocaleString('es-CO')}</strong>
                    <small>${percentage}%</small>
                </span>
            `;
        }).join('');
    }

    toggleEmptyState(type, hasData) {
        const node = this.emptyStates?.[type];
        if (!node) return;
        /*
         * Prefer setting the boolean hidden property (node.hidden = true/false)
         * rather than working with attribute strings to avoid inconsistencies
         * with CSS or other scripts that might touch the attribute directly.
         */
        try {
            // toggle the hidden property, the DOM will normally reflect this as
            // the [hidden] attribute. Also set aria-hidden for accessibility.
            node.hidden = !hasData;
            node.setAttribute('aria-hidden', (!hasData).toString());
            // Explicitly set display inline style to guarantee overlay is hidden
            // even when author CSS may override the [hidden] attribute.
            node.style.display = hasData ? 'none' : 'flex';
            if (window && window.console && typeof window.console.debug === 'function') {
                console.debug('[ClientsDashboard] toggleEmptyState', type, 'hasData:', hasData, 'hidden:', node.hidden, 'display:', node.style.display, 'aria-hidden:', node.getAttribute('aria-hidden'));
            }
        } catch (e) {
            // Fallback: update attribute directly if setting property fails in some contexts
            if (hasData) {
                node.setAttribute('hidden', 'hidden');
                node.setAttribute('aria-hidden', 'false');
                node.style.display = 'none';
            } else {
                node.removeAttribute('hidden');
                node.setAttribute('aria-hidden', 'true');
                node.style.display = 'flex';
            }
        }
    }

    destroyChart(key) {
        if (this.charts?.[key]) {
            this.charts[key].destroy();
            this.charts[key] = null;
        }
    }

    clearCanvas(canvas) {
        const ctx = canvas?.getContext?.('2d');
        if (ctx) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
    }

    captureTheme() {
        if (typeof window === 'undefined' || !window.getComputedStyle) {
            return {
                primary: '#0077b6',
                primarySoft: 'rgba(0,119,182,0.18)',
                textMuted: '#64748b',
                palette: ['#0077b6', '#48cae4', '#5c6ac4', '#f59e0b', '#059669', '#dc2626']
            };
        }
        const styles = getComputedStyle(document.documentElement);
        const read = (varName, fallback) => {
            const value = styles.getPropertyValue(varName);
            return value ? value.trim() : fallback;
        };
        return {
            /* Prefer explicit dashboard root primary color if present */
            primary: read('--primary-color', read('--hub-primary', '#0077b6')),
            primarySoft: read('--primary-color', read('--hub-primary-soft', 'rgba(0,119,182,0.18)')),
            textMuted: read('--hub-muted', '#64748b'),
            palette: [
                read('--primary-color', read('--hub-primary', '#0077b6')),
                read('--secondary-color', read('--primary-light', '#48cae4')),
                read('--hub-success', '#059669'),
                read('--hub-warning', '#f59e0b'),
                read('--hub-danger', '#dc2626'),
                read('--primary-dark', '#0f172a')
            ]
        };
    }

    renderTable(items = []) {
        if (!items.length) {
            this.tableBody.innerHTML = '<tr><td colspan="5">No se encontraron clientes con los filtros actuales</td></tr>';
            return;
        }
        this.tableBody.innerHTML = items.map((item) => {
            const initials = this.getInitials(item.name || item.email);
            const bg = this.computeNameColor(item.name || item.email);
            return `
            <tr tabindex="0" data-id="${item.id}">
                <td data-label="Cliente">
                    <div class="table-primary"><span class="avatar" style="background:${bg}">${initials}</span>${item.name || item.email}</div>
                    <small class="text-muted">${item.email}</small>
                </td>
                <td data-label="Pedidos">${item.orders_count}</td>
                <td data-label="Último pedido">${item.last_order ? this.formatDate(item.last_order) : 'Sin datos'}</td>
                <td data-label="Ticket">${this.formatCurrency(item.avg_ticket)}</td>
                <td data-label="Status">${this.renderStatusChip(item.status)}</td>
            </tr>
        `;
        }).join('');

        this.tableBody.querySelectorAll('tr').forEach((row) => {
            row.addEventListener('click', () => {
                const id = row.getAttribute('data-id');
                if (!id) return;
                this.selectCustomer(id, { source: 'table' });
            });
            // keyboard support to select a row
            row.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    const id = row.getAttribute('data-id');
                    if (id) this.selectCustomer(id, { source: 'table' });
                }
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    const rows = Array.from(row.parentElement.querySelectorAll('tr'));
                    const i = rows.indexOf(row);
                    const next = e.key === 'ArrowDown' ? rows[i + 1] : rows[i - 1];
                    if (next) next.focus();
                }
            });
        });
        // Auto-select first row if there's no selection (only on first render)
        const currentlySelected = this.tableBody.querySelector('tr.selected');
        const firstRow = this.tableBody.querySelector('tr[data-id]');
        if (!currentlySelected && firstRow) {
            const id = firstRow.getAttribute('data-id');
            if (id) this.selectCustomer(id, { source: 'table-auto' });
        }
    }

    renderPagination(meta) {
        if (!this.pagination) return;
        const info = this.pagination.querySelector('[data-role="meta"]');
        info.textContent = `${meta.total} resultados`;
        this.state.page = Math.max(1, Math.min(meta.pages, this.state.page));
        const [prevBtn, nextBtn] = this.pagination.querySelectorAll('button[data-page]');
        if (prevBtn) prevBtn.disabled = this.state.page <= 1;
        if (nextBtn) nextBtn.disabled = this.state.page >= meta.pages;
    }

    async openDetail(id) {
        if (!id) return;
        // Show loading skeleton in detail panel
        if (this.detailPanel) {
            this.detailPanel.classList.add('is-loading');
            const empty = this.detailPanel.querySelector('[data-state="empty"]');
            const content = this.detailPanel.querySelector('[data-state="content"]');
            if (content) content.setAttribute('hidden', 'hidden');
            if (empty) empty.setAttribute('hidden', 'hidden');
        }
        try {
            const response = await fetch(`${this.config.endpoints.detail}?id=${encodeURIComponent(id)}`, { credentials: 'same-origin' });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const payload = await response.json();
            if (!payload.success) throw new Error('API error');
            this.renderDetail(payload);
        } catch (error) {
            console.error('client detail', error);
        }
        finally {
            if (this.detailPanel) {
                this.detailPanel.classList.remove('is-loading');
                // Ensure detail panel is visible when opening
                this.detailPanel.classList.remove('collapsed');
                this.detailPanel.setAttribute('aria-hidden', 'false');
                if (this.detailToggleBtn) this.detailToggleBtn.setAttribute('aria-expanded', 'true');
            }
        }
    }

    renderDetail(payload) {
        if (!this.detailPanel) return;
        const emptyState = this.detailPanel.querySelector('[data-state="empty"]');
        const content = this.detailPanel.querySelector('[data-state="content"]');
        emptyState?.setAttribute('hidden', 'hidden');
        content?.removeAttribute('hidden');

        const detailName = content.querySelector('.detail-name');
        if (detailName) {
            const name = payload.profile.name || payload.profile.email;
            const initials = this.getInitials(name);
            const bg = this.computeNameColor(name);
            detailName.innerHTML = `<span class="avatar-large" style="background:${bg}">${initials}</span> ${name}`;
        }
        // Render segments as small badges in the detail header
        const segNode = content.querySelector('[data-role="segment"]');
        if (segNode) {
            const segs = payload.metrics.segments || [];
            segNode.innerHTML = segs.length ? segs.map(s => `<span class="badge-ghost">${s}</span>`).join(' ') : '<span class="badge-ghost">Sin segmento</span>';
        }
        // show email/phone with icons for clarity
        const emailEl = content.querySelector('[data-role="email"]');
        const phoneEl = content.querySelector('[data-role="phone"]');
        if (emailEl) emailEl.innerHTML = `<i class="fas fa-envelope" aria-hidden="true"></i> ${payload.profile.email}`;
        if (phoneEl) phoneEl.innerHTML = payload.profile.phone ? `<i class="fas fa-phone" aria-hidden="true"></i> ${payload.profile.phone}` : 'Sin telefono';
        content.querySelector('[data-role="created"]').textContent = this.formatDate(payload.profile.created_at);

        const timeline = document.getElementById('client-activity');
        if (timeline) {
            const events = payload.activity || [];
            timeline.innerHTML = events.length ? events.map((event) => `
                <li role="button" tabindex="0" class="detail-event" data-event-id="${event.id || ''}">
                    <svg class="mini-dot" viewBox="0 0 12 12" width="12" height="12" aria-hidden="true"><circle cx="6" cy="6" r="6" fill="var(--hub-primary)"/></svg>
                    <div class="event-content">
                        <strong class="event-title">${event.title}</strong>
                        <small class="event-desc">${event.description || ''}</small>
                    </div>
                    <div class="event-meta">${this.formatDate(event.created_at)}</div>
                </li>
            `).join('') : '<li class="text-muted">Sin actividad reciente</li>';

            // Add keyboard support and click handler to each timeline item
            timeline.querySelectorAll('li.detail-event').forEach((li) => {
                li.addEventListener('click', () => {
                    // if the event contains an order id, open a new tab to the order detail
                    const eventId = li.getAttribute('data-event-id');
                    if (eventId) {
                        // open in new tab if it's an order; fallback: highlight the item
                        if (eventId.startsWith('ORD')) {
                            window.open(`${this.config.baseUrl}/admin/order/${encodeURIComponent(eventId)}`, '_blank');
                        } else {
                            li.classList.toggle('selected');
                        }
                    } else {
                        li.classList.toggle('selected');
                    }
                });
                li.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        li.click();
                    }
                });
            });
        }
        // highlight the client in table and top customers when detail is loaded
        if (payload.profile && payload.profile.id) {
            this.highlightById(payload.profile.id);
        }
        // highlight the segment tiles that apply to this client
        const segs = payload.metrics?.segments || [];
        if (this.segmentContainer) {
            this.segmentContainer.querySelectorAll('.segment-tile').forEach((tile) => {
                const h3 = tile.querySelector('h3')?.textContent?.trim();
                tile.classList.toggle('selected', h3 && segs.includes(h3));
            });
            // Move keyboard focus to detail toggle to help keyboard users notice the panel
            if (this.detailToggleBtn) this.detailToggleBtn.focus();
        }
    }

    renderStatusChip(status) {
        const className = status === 'Activo' ? 'success' : status === 'En riesgo' ? 'warning' : 'muted';
        return `<span class="status-chip ${className}">${status}</span>`;
    }

    formatCurrency(value) {
        const amount = Number(value || 0);
        return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(amount);
    }

    formatDate(value) {
        if (!value) return 'Sin datos';
        return new Intl.DateTimeFormat('es-CO', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
    }

    getInitials(name) {
        if (!name) return '';
        const parts = String(name).trim().split(/\s+/).filter(Boolean);
        if (!parts.length) return '';
        if (parts.length === 1) return (parts[0][0] || '').toUpperCase();
        const initials = (parts[0][0] || '') + (parts[1][0] || '');
        return initials.toUpperCase();
    }

    computeNameColor(name) {
        const palette = (this.theme && this.theme.palette) ? this.theme.palette : ['#0077b6', '#48cae4', '#5c6ac4', '#f59e0b', '#059669', '#dc2626'];
        const str = String(name || '');
        let sum = 0;
        for (let i = 0; i < str.length; i++) sum += str.charCodeAt(i);
        const idx = sum % palette.length;
        return palette[idx] || '#0077b6';
    }

    debounce(fn, delay) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => fn(...args), delay);
        };
    }
}

if (window.CLIENTS_DASHBOARD_CONFIG) {
    document.addEventListener('DOMContentLoaded', () => new ClientsDashboard(window.CLIENTS_DASHBOARD_CONFIG));
}
