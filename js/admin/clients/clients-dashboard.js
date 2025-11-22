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
        this.engagementContainer.innerHTML = `
            <article class="segment-tile">
                <h3>Por pedidos</h3>
                ${this.renderMiniList(orders)}
            </article>
            <article class="segment-tile">
                <h3>Por recencia</h3>
                ${this.renderMiniList(recency)}
            </article>
        `;
    }

    renderMiniList(bucket) {
        const entries = Object.entries(bucket);
        if (!entries.length) return '<p>Sin datos</p>';
        return `<ul class="text-muted">${entries.map(([label, value]) => `<li>${label}: <strong>${value}</strong></li>`).join('')}</ul>`;
    }

    renderTopCustomers(list = []) {
        if (!this.topCustomersList) return;
        if (!list.length) {
            this.topCustomersList.innerHTML = '<li>Sin clientes destacados</li>';
            return;
        }
        this.topCustomersList.innerHTML = list.map((item) => `
            <li>
                <strong>${item.name || item.email}</strong>
                <span>${this.formatCurrency(item.total_spent)} • ${item.orders_count} pedidos</span>
            </li>
        `).join('');
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
        this.tableBody.innerHTML = items.map((item) => `
            <tr data-id="${item.id}">
                <td data-label="Cliente">
                    <div class="table-primary">${item.name || item.email}</div>
                    <small class="text-muted">${item.email}</small>
                </td>
                <td data-label="Pedidos">${item.orders_count}</td>
                <td data-label="Último pedido">${item.last_order ? this.formatDate(item.last_order) : 'Sin datos'}</td>
                <td data-label="Ticket">${this.formatCurrency(item.avg_ticket)}</td>
                <td data-label="Status">${this.renderStatusChip(item.status)}</td>
            </tr>
        `).join('');

        this.tableBody.querySelectorAll('tr').forEach((row) => {
            row.addEventListener('click', () => {
                const id = row.getAttribute('data-id');
                this.openDetail(id);
            });
        });
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
        try {
            const response = await fetch(`${this.config.endpoints.detail}?id=${encodeURIComponent(id)}`, { credentials: 'same-origin' });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const payload = await response.json();
            if (!payload.success) throw new Error('API error');
            this.renderDetail(payload);
        } catch (error) {
            console.error('client detail', error);
        }
    }

    renderDetail(payload) {
        if (!this.detailPanel) return;
        const emptyState = this.detailPanel.querySelector('[data-state="empty"]');
        const content = this.detailPanel.querySelector('[data-state="content"]');
        emptyState?.setAttribute('hidden', 'hidden');
        content?.removeAttribute('hidden');

        content.querySelector('.detail-name').textContent = payload.profile.name || payload.profile.email;
        content.querySelector('[data-role="segment"]').textContent = (payload.metrics.segments || []).join(', ') || 'Sin segmento';
        content.querySelector('[data-role="email"]').textContent = payload.profile.email;
        content.querySelector('[data-role="phone"]').textContent = payload.profile.phone || 'Sin telefono';
        content.querySelector('[data-role="created"]').textContent = this.formatDate(payload.profile.created_at);

        const timeline = document.getElementById('client-activity');
        if (timeline) {
            const events = payload.activity || [];
            timeline.innerHTML = events.length ? events.map((event) => `
                <li>
                    <strong>${event.title}</strong>
                    <span>${this.formatDate(event.created_at)} · ${event.description}</span>
                </li>
            `).join('') : '<li>Sin actividad reciente</li>';
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
