class AdminDashboard {
    constructor() {
        const baseMeta = document.querySelector('meta[name="base-url"]');
        this.baseUrl = (baseMeta?.content || '').replace(/\/$/, '');
        this.apiUrl = `${this.baseUrl}/admin/api/dashboard/overview.php`;
        this.state = {
            range: 7,
            isLoading: false,
            autoRefreshMs: 5 * 60 * 1000,
            autoRefreshId: null
        };

        this.elements = {
            root: document.getElementById('dashboard-root'),
            statsSection: document.getElementById('stats-section'),
            metrics: document.getElementById('metrics-grid'),
            statusCard: document.getElementById('status-chart-card'),
            statusList: document.getElementById('status-list'),
            recentOrdersBody: document.getElementById('recent-orders-body'),
            lowStockList: document.getElementById('low-stock-list'),
            topProductsList: document.getElementById('top-products-list'),
            activityFeed: document.getElementById('activity-feed'),
            errorBanner: document.getElementById('dashboard-error'),
            refreshBtn: document.getElementById('refresh-dashboard'),
            rangeButtons: document.querySelectorAll('.chart-range'),
            salesChartCard: document.getElementById('sales-chart-card')
        };

        this.quickView = {
            modal: document.getElementById('quick-view-modal'),
            content: document.getElementById('quick-view-content'),
            editBtn: document.getElementById('edit-product-btn'),
            zoomModal: document.getElementById('image-zoom-modal'),
            zoomImage: document.getElementById('zoom-image'),
            zoomTitle: document.getElementById('zoom-title')
        };
        this.quickViewImages = [];

        this.charts = {
            sales: null,
            status: null
        };

        this.formatters = {
            currency: new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }),
            number: new Intl.NumberFormat('es-CO'),
            date: new Intl.DateTimeFormat('es-CO', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }),
            chartDate: new Intl.DateTimeFormat('es-CO', { day: '2-digit', month: 'short' })
        };

        if (this.elements.root) {
            this.init();
        }
    }

    init() {
        this.bindEvents();
        this.setupModalEvents();
        this.loadData();
        this.applyDataLabels();
        this.state.autoRefreshId = setInterval(() => this.loadData(false), this.state.autoRefreshMs);
    }

    bindEvents() {
        this.elements.refreshBtn?.addEventListener('click', () => this.loadData());

        this.elements.rangeButtons?.forEach((button) => {
            button.addEventListener('click', () => {
                const selectedRange = parseInt(button.dataset.range, 10);
                if (this.state.range === selectedRange) return;
                this.state.range = selectedRange;
                this.elements.rangeButtons.forEach((btn) => btn.classList.toggle('active', btn === button));
                this.loadData();
            });
        });

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) return;
            this.loadData(false);
        });
    }

    setupModalEvents() {
        const modals = [this.quickView.modal, this.quickView.zoomModal];
        modals.forEach((modal) => {
            if (!modal) return;
            modal.querySelectorAll('.modal-close').forEach((btn) => {
                btn.addEventListener('click', () => this.closeModal(modal));
            });
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    this.closeModal(modal);
                }
            });
        });
    }

    async loadData(showLoading = true) {
        if (this.state.isLoading) {
            return;
        }

        this.state.isLoading = true;
        if (showLoading) {
            this.setLoadingState(true);
        }
        this.showError(null);

        try {
            const response = await fetch(`${this.apiUrl}?range=${this.state.range}`, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error(`Error ${response.status}`);
            }

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'No se pudo cargar la información');
            }

            this.renderStats(data.stats);
            this.renderMetrics(data.stats);
            this.renderSalesChart(data.sales_trend || []);
            this.renderStatusChart(data.status_breakdown || []);
            this.renderRecentOrders(data.recent_orders || []);
            this.renderInventory(data.inventory || {}, data.low_stock || []);
            this.renderTopProducts(data.top_products || []);
            this.renderActivity(data.activity || []);
        } catch (error) {
            console.error('Dashboard load error:', error);
            this.showError('No pudimos actualizar el dashboard. Intenta nuevamente.');
        } finally {
            this.state.isLoading = false;
            if (showLoading) {
                this.setLoadingState(false);
            }
        }
    }

    showError(message) {
        if (!this.elements.errorBanner) return;
        if (!message) {
            this.elements.errorBanner.style.display = 'none';
            this.elements.errorBanner.textContent = '';
            return;
        }
        this.elements.errorBanner.textContent = message;
        this.elements.errorBanner.style.display = 'block';
    }

    setLoadingState(isLoading) {
        const toggle = (el) => el?.classList.toggle('is-loading', isLoading);
        toggle(this.elements.statsSection);
        toggle(this.elements.metrics);

        if (this.elements.statsSection) {
            this.elements.statsSection.querySelectorAll('.stat-card').forEach((card) => card.classList.toggle('is-loading', isLoading));
        }
        if (this.elements.metrics) {
            this.elements.metrics.querySelectorAll('.metric-card').forEach((card) => card.classList.toggle('is-loading', isLoading));
        }

        this.toggleChartSkeleton(this.elements.salesChartCard, !isLoading);
        this.toggleChartSkeleton(this.elements.statusCard, !isLoading);
    }

    renderStats(stats) {
        if (!stats || !this.elements.statsSection) return;
        const cards = this.elements.statsSection.querySelectorAll('[data-stat-card]');
        cards.forEach((card) => card.classList.remove('is-loading'));

        // Don't destructure instance methods: binding to `this` is required (formatNumber uses this.formatters)
        const formatNumber = (v) => this.formatNumber(v);
        const formatCurrency = (v) => this.formatCurrency(v);

        const mapping = {
            orders: {
                value: formatNumber(stats.orders_today),
                change: stats.orders_change,
                helper: 'vs. ayer'
            },
            revenue: {
                value: formatCurrency(stats.revenue_today),
                change: stats.revenue_change,
                helper: 'vs. ayer'
            },
            customers: {
                value: formatNumber(stats.new_customers),
                change: stats.customers_change,
                helper: 'vs. últimos 7 días'
            },
            inventory: {
                value: formatNumber(stats.active_products),
                change: null,
                extras: {
                    active: formatNumber(stats.active_products),
                    low: formatNumber(stats.low_stock_products)
                }
            }
        };

        Object.entries(mapping).forEach(([key, cfg]) => {
            const card = this.elements.statsSection.querySelector(`[data-stat-card="${key}"]`);
            if (!card) return;

            const valueNode = card.querySelector('[data-stat-value]');
            if (valueNode) {
                valueNode.textContent = cfg.value;
            }

            const changeNode = card.querySelector('[data-stat-change]');
            if (changeNode && cfg.change !== null && cfg.change !== undefined) {
                this.updateChangeBadge(changeNode, cfg.change);
            } else if (changeNode) {
                changeNode.textContent = '--';
                changeNode.classList.remove('positive', 'negative');
            }

            const helperNode = card.querySelector('.stat-helper');
            if (helperNode && cfg.helper) {
                helperNode.textContent = cfg.helper;
            }

            if (cfg.extras) {
                const activeNode = card.querySelector('[data-stat-extra="active"]');
                const lowNode = card.querySelector('[data-stat-extra="low"]');
                if (activeNode) activeNode.textContent = cfg.extras.active;
                if (lowNode) lowNode.textContent = cfg.extras.low;
            }
        });
    }

    renderMetrics(stats) {
        if (!stats || !this.elements.metrics) return;
        this.elements.metrics.classList.remove('is-loading');

        const avgTicketCard = this.elements.metrics.querySelector('[data-metric-card="avg_ticket"] [data-metric-value]');
        if (avgTicketCard) avgTicketCard.textContent = this.formatCurrency(stats.avg_ticket);

        const pendingCard = this.elements.metrics.querySelector('[data-metric-card="pending_orders"] [data-metric-value]');
        if (pendingCard) pendingCard.textContent = this.formatNumber(stats.pending_orders);

        const monthRevenueCard = this.elements.metrics.querySelector('[data-metric-card="revenue_month"]');
        if (monthRevenueCard) {
            const valueNode = monthRevenueCard.querySelector('[data-metric-value]');
            const changeNode = monthRevenueCard.querySelector('[data-metric-change]');
            if (valueNode) valueNode.textContent = this.formatCurrency(stats.month_revenue);
            if (changeNode && typeof stats.month_revenue_change === 'number') {
                this.updateChangeBadge(changeNode, stats.month_revenue_change);
            } else if (changeNode) {
                changeNode.textContent = '--';
                changeNode.classList.remove('positive', 'negative');
            }
        }
    }

    renderSalesChart(trend) {
        const ctx = document.getElementById('sales-trend-chart');
        if (!ctx) return;

        const labels = trend.map((point) => this.formatChartLabel(point.date));
        const revenueData = trend.map((point) => point.revenue);
        const ordersData = trend.map((point) => point.orders);

        if (this.charts.sales) {
            this.charts.sales.destroy();
        }

        this.charts.sales = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        type: 'line',
                        label: 'Ingresos',
                        data: revenueData,
                        borderColor: '#0077b6',
                        borderWidth: 3,
                        tension: 0.35,
                        pointRadius: 3,
                        fill: false,
                        yAxisID: 'revenueAxis'
                    },
                    {
                        type: 'bar',
                        label: 'Órdenes',
                        data: ordersData,
                        backgroundColor: '#48cae4',
                        borderRadius: 6,
                        yAxisID: 'ordersAxis'
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
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                if (context.dataset.label === 'Ingresos') {
                                    return `${context.dataset.label}: ${this.formatCurrency(context.raw)}`;
                                }
                                return `${context.dataset.label}: ${this.formatNumber(context.raw)}`;
                            }
                        }
                    }
                },
                scales: {
                    revenueAxis: {
                        position: 'left',
                        grid: {
                            drawOnChartArea: false
                        },
                        ticks: {
                            callback: (value) => this.formatCurrency(value)
                        }
                    },
                    ordersAxis: {
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        ticks: {
                            precision: 0
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        this.toggleChartSkeleton(this.elements.salesChartCard, true);
    }

    renderStatusChart(breakdown) {
        const ctx = document.getElementById('status-chart');
        if (!ctx) return;

        if (this.charts.status) {
            this.charts.status.destroy();
        }

        const normalized = this.normalizeStatusBreakdown(breakdown);
        const labels = normalized.map((item) => item.label);
        const values = normalized.map((item) => item.count);
        const colors = normalized.map((item) => item.color);

        this.charts.status = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [
                    {
                        data: values,
                        backgroundColor: colors,
                        borderWidth: 0
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        this.toggleChartSkeleton(this.elements.statusCard, true);
        this.renderStatusList(normalized);
    }

    renderStatusList(breakdown) {
        if (!this.elements.statusList) return;
        if (!breakdown.length) {
            this.elements.statusList.innerHTML = '<p class="empty-state">No hay órdenes registradas</p>';
            return;
        }

        const total = breakdown.reduce((sum, item) => sum + item.count, 0) || 1;
        this.elements.statusList.innerHTML = breakdown.map((item) => {
            const percentage = Math.round((item.count / total) * 100);
            return `
                <div class="status-row">
                    <div class="status-info">
                        <span class="status-dot" style="background:${item.color}"></span>
                        <span>${item.label}</span>
                    </div>
                    <div class="status-values">
                        <strong>${this.formatNumber(item.count)}</strong>
                        <span>${percentage}%</span>
                    </div>
                    <div class="status-progress">
                        <div class="status-progress-bar" style="width:${percentage}%; background:${item.color}"></div>
                    </div>
                </div>
            `;
        }).join('');
    }

    normalizeStatusBreakdown(breakdown = []) {
        return breakdown.map((item) => ({
            ...item,
            count: Number(item.count) || 0
        }));
    }

    renderRecentOrders(orders) {
        if (!this.elements.recentOrdersBody) return;
        if (!orders.length) {
            this.elements.recentOrdersBody.innerHTML = '<tr><td colspan="6" class="empty-state">No hay órdenes recientes</td></tr>';
            return;
        }

        this.elements.recentOrdersBody.innerHTML = orders.map((order) => {
            const statusClass = `status-badge ${order.status ?? 'pending'}`;
            const orderLabel = order.order_number || order.id;
            return `
                <tr>
                    <td data-label="Orden">#${orderLabel}</td>
                    <td data-label="Cliente">${order.customer_name || 'Sin cliente'}</td>
                    <td data-label="Fecha">${this.formatDate(order.created_at)}</td>
                    <td data-label="Total">${this.formatCurrency(order.total)}</td>
                    <td data-label="Estado"><span class="${statusClass}">${this.getStatusIcon(order.status)} ${this.getStatusLabel(order.status)}</span></td>
                    <td data-label="Pago">${this.getPaymentLabel(order.payment_status)}</td>
                </tr>
            `;
        }).join('');
        // ensure data-label attributes exist for accessibility and responsive layout
        this.applyDataLabels();
    }

    applyDataLabels() {
        const headerCells = document.querySelectorAll('.recent-orders-card .dashboard-table thead th');
        const labels = Array.from(headerCells).map(h => h.textContent.trim());
        const rows = document.querySelectorAll('.recent-orders-card .dashboard-table tbody tr');
        rows.forEach(row => {
            Array.from(row.querySelectorAll('td')).forEach((td, idx) => {
                // Skip skeleton rows or cells spanning multiple columns
                const colspan = parseInt(td.getAttribute('colspan') || td.colSpan || '1', 10);
                if (colspan > 1) return;
                if (!td.hasAttribute('data-label')) td.setAttribute('data-label', labels[idx] || '');
            });
        });
    }

    getStatusIcon(status) {
        const icons = {
            pending: '<i class="fas fa-clock"></i>',
            processing: '<i class="fas fa-cog"></i>',
            shipped: '<i class="fas fa-truck"></i>',
            delivered: '<i class="fas fa-check"></i>',
            cancelled: '<i class="fas fa-ban"></i>',
            refunded: '<i class="fas fa-undo"></i>'
        };
        return icons[status] || '';
    }

    renderInventory(summary, lowStock) {
        const totalNode = document.querySelector('[data-inventory="total"]');
        const zeroNode = document.querySelector('[data-inventory="zero"]');
        if (totalNode) totalNode.textContent = this.formatNumber(summary.total_products || 0);
        if (zeroNode) zeroNode.textContent = this.formatNumber(summary.zero_stock || 0);

        if (!this.elements.lowStockList) return;
        if (!lowStock.length) {
            this.elements.lowStockList.innerHTML = '<div class="empty-state">Todo el inventario está saludable.</div>';
            return;
        }

        this.elements.lowStockList.innerHTML = lowStock.map((product) => {
            const stockClass = product.total_stock <= 2 ? 'danger' : 'warning';
            return `
                <div class="low-stock-item">
                    <div class="low-stock-image">
                        <img src="${this.resolveImage(product.image)}" alt="${product.name}">
                    </div>
                    <div class="low-stock-info">
                        <h4>${product.name}</h4>
                        <p>${product.category || 'Sin categoría'}</p>
                    </div>
                    <div class="low-stock-meta">
                        <span class="stock-pill ${stockClass}">${product.total_stock} uds.</span>
                        <span class="price-pill">${this.formatCurrency(product.price)}</span>
                    </div>
                </div>
            `;
        }).join('');
    }

    renderTopProducts(products) {
        const container = this.elements.topProductsList;
        if (!container) return;
        if (!products.length) {
            container.innerHTML = '<div class="empty-state">Aún no hay ventas registradas.</div>';
            return;
        }

        const cardsHtml = products.map((product, index) => this.buildTopProductCard(product, index)).join('');
        container.innerHTML = `<div class="products-admin-grid top-products-grid">${cardsHtml}</div>`;
        this.bindTopProductActions();
    }

    buildTopProductCard(product, index) {
        const priceRange = this.formatPriceRange(product.min_price, product.max_price);
        const stockLabel = typeof product.total_stock === 'number'
            ? `${this.formatNumber(product.total_stock)} uds.`
            : 'Sin stock';
        const soldLabel = `${this.formatNumber(product.total_quantity)} uds.`;
        const revenueLabel = this.formatCurrency(product.total_revenue);
        const imageUrl = this.resolveImage(product.image);
        const rankLabel = `Top ${index + 1}`;

        return `
            <div class="product-admin-card top-product-card" data-product-id="${product.id}">
                <div class="product-admin-status">
                    <span class="status-badge status-active">${rankLabel}</span>
                </div>
                <div class="product-admin-image">
                    <img src="${imageUrl}" alt="${product.name}">
                    <div class="product-admin-overlay">
                        <button class="btn-overlay btn-quick-view" type="button" data-id="${product.id}" title="Vista rápida">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="product-admin-body">
                    <div class="product-admin-header">
                        <h3 class="product-admin-title">${product.name}</h3>
                        <span class="product-admin-id">${product.category || 'Sin categoría'}</span>
                    </div>
                    <div class="product-admin-meta product-meta-compact">
                        <div class="meta-item">
                            <i class="fas fa-dollar-sign"></i>
                            <span>${priceRange}</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-chart-line"></i>
                            <span>${revenueLabel}</span>
                        </div>
                    </div>
                    <div class="product-admin-info compact">
                        <div class="info-item">
                            <label>Vendidos</label>
                            <span>${soldLabel}</span>
                        </div>
                        <div class="info-item">
                            <label>Stock activo</label>
                            <span>${stockLabel}</span>
                        </div>
                    </div>
                </div>
                <div class="product-admin-actions compact-actions">
                    <a href="${this.baseUrl}/admin/editproducto.php?id=${product.id}" class="btn-action btn-edit" title="Editar">
                        <i class="fas fa-edit"></i>
                        <span>Editar</span>
                    </a>
                    <button class="btn-action btn-secondary btn-quick-view" type="button" data-id="${product.id}" title="Vista rápida">
                        <i class="fas fa-eye"></i>
                        <span>Ver</span>
                    </button>
                </div>
            </div>
        `;
    }

    bindTopProductActions() {
        const container = this.elements.topProductsList;
        if (!container) return;
        container.querySelectorAll('.top-products-grid .btn-quick-view').forEach((button) => {
            button.addEventListener('click', () => {
                const productId = button.getAttribute('data-id');
                this.openQuickView(productId);
            });
        });
    }

    async openQuickView(productId) {
        if (!productId) return;
        try {
            const response = await fetch(`${this.baseUrl}/admin/api/productos/get_product_details.php?id=${productId}`);
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'No se pudo cargar el producto');
            }
            this.renderQuickView(data);
            this.openModal(this.quickView.modal);
        } catch (error) {
            console.error('Quick view error:', error);
            alert('No se pudo abrir la vista rápida.');
        }
    }

    renderQuickView(data) {
        const product = data.product;
        const images = data.images || [];
        const variants = data.variants || [];

        this.quickViewImages = images;

        let imagesHtml = '<div class="quick-view-gallery empty-state">Sin imágenes disponibles</div>';
        if (images.length > 0) {
            const imagesByColor = this.groupImagesByColor(images);
            const primaryImage = images.find((img) => Number(img.is_primary) === 1) || images[0];
            imagesHtml = this.buildGalleryHTML(imagesByColor, primaryImage, product.name);
        }

        let variantsHtml = '';
        if (variants.length > 0) {
            variantsHtml = this.buildVariantsHTML(variants);
        }

        const minPrice = data.min_price !== null && data.min_price !== undefined ? Number(data.min_price) : null;
        const maxPrice = data.max_price !== null && data.max_price !== undefined ? Number(data.max_price) : null;
        const priceRange = this.formatPriceRange(minPrice, maxPrice);
        const html = `
            <div class="quick-view-content">
                ${imagesHtml}
                <div class="quick-view-info">
                    <div class="product-header">
                        <h2>${product.name}</h2>
                        <span class="product-id">ID: ${product.id}</span>
                    </div>

                    <div class="product-meta">
                        <div class="meta-item">
                            <i class="fas fa-tag"></i>
                            <span>Categoría: ${product.category_name || 'Sin categoría'}</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-palette"></i>
                            <span>${variants.length} variante${variants.length !== 1 ? 's' : ''}</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-boxes"></i>
                            <span>Stock total: ${data.total_stock} unidades</span>
                        </div>
                    </div>

                    <div class="product-description">
                        <h4>Descripción</h4>
                        <p>${product.description || 'Sin descripción'}</p>
                    </div>

                    <div class="product-pricing">
                        <h4>Precios</h4>
                        <p>${priceRange}</p>
                    </div>

                    ${variantsHtml}
                </div>
            </div>
        `;

        if (this.quickView.content) {
            this.quickView.content.innerHTML = html;
        }
        if (this.quickView.editBtn) {
            this.quickView.editBtn.href = `${this.baseUrl}/admin/editproducto.php?id=${product.id}`;
        }

        this.setupImageGallery();
    }

    groupImagesByColor(images) {
        return images.reduce((acc, image) => {
            const colorKey = image.color_name || 'General';
            if (!acc[colorKey]) {
                acc[colorKey] = [];
            }
            acc[colorKey].push(image);
            return acc;
        }, {});
    }

    buildGalleryHTML(imagesByColor, primaryImage, productName) {
        const colorButtons = [`<button class="color-filter-btn active" data-color="General"><span class="color-text">Principal</span></button>`];
        Object.keys(imagesByColor).forEach((color) => {
            if (color === 'General') return;
            const firstImage = imagesByColor[color][0];
            const hexCode = firstImage.hex_code || '#CCCCCC';
            colorButtons.push(`
                <button class="color-filter-btn" data-color="${color}" title="${color}">
                    <span class="color-circle" style="background-color: ${hexCode};"></span>
                    <span class="color-text">${color}</span>
                </button>
            `);
        });

        const thumbnailsHTML = this.quickViewImages.map((img, index) => `
            <img src="${img.url}" 
                 alt="${img.alt_text || 'Imagen ' + (index + 1)}" 
                 class="thumbnail ${img.id === primaryImage.id ? 'active' : ''}" 
                 data-index="${img.id}" 
                 data-color="${img.color_name || 'General'}" 
                 style="${img.color_name && img.color_name !== 'General' ? 'display:none;' : ''}">
        `).join('');

        return `
            <div class="quick-view-gallery">
                <div class="gallery-filters">${colorButtons.join('')}</div>
                <div class="main-image">
                    <img src="${primaryImage.url}" alt="${primaryImage.alt_text || productName}" id="main-product-image">
                    <button class="image-zoom-btn" data-image="${primaryImage.url}" data-alt="${primaryImage.alt_text || productName}">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
                <div class="thumbnail-gallery-container">
                    <button class="gallery-arrow left" type="button" id="gallery-left"><i class="fas fa-chevron-left"></i></button>
                    <div class="thumbnail-gallery" id="thumbnail-gallery">${thumbnailsHTML}</div>
                    <button class="gallery-arrow right" type="button" id="gallery-right"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        `;
    }

    buildVariantsHTML(variants) {
        const colors = [...new Set(variants.map((variant) => variant.color_name))];
        const sizes = [...new Set(variants.map((variant) => variant.size_name))];

        return `
            <div class="variants-section">
                <h4>Variantes</h4>
                ${colors.length ? `
                    <div class="variant-group">
                        <label>Colores:</label>
                        <div class="color-options">${colors.map((color) => `<span class="color-tag">${color}</span>`).join('')}</div>
                    </div>
                ` : ''}
                ${sizes.length ? `
                    <div class="variant-group">
                        <label>Tallas:</label>
                        <div class="size-options">${sizes.map((size) => `<span class="size-tag">${size}</span>`).join('')}</div>
                    </div>
                ` : ''}
                <div class="variant-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Color</th>
                                <th>Talla</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${variants.map((variant) => `
                                <tr>
                                    <td data-label="Color">${variant.color_name}</td>
                                    <td data-label="Talla">${variant.size_name}</td>
                                    <td data-label="Precio">${this.formatCurrency(Number(variant.price))}</td>
                                    <td data-label="Stock">${variant.quantity}</td>
                                    <td data-label="Estado"><span class="status ${variant.is_active ? 'active' : 'inactive'}">${variant.is_active ? 'Activo' : 'Inactivo'}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    setupImageGallery() {
        const modal = this.quickView.modal;
        if (!modal) return;
        const thumbnails = modal.querySelectorAll('.thumbnail');
        const mainImage = modal.querySelector('#main-product-image');
        const zoomBtn = modal.querySelector('.image-zoom-btn');
        const thumbnailsContainer = modal.querySelector('#thumbnail-gallery');
        const scrollLeftBtn = modal.querySelector('#gallery-left');
        const scrollRightBtn = modal.querySelector('#gallery-right');

        thumbnails.forEach((thumb) => {
            thumb.addEventListener('click', () => {
                const imgId = thumb.getAttribute('data-index');
                const image = this.quickViewImages.find((img) => img.id == imgId);
                if (!image) return;
                if (mainImage) {
                    mainImage.src = image.url;
                    mainImage.alt = image.alt_text || '';
                }
                if (zoomBtn) {
                    zoomBtn.setAttribute('data-image', image.url);
                    zoomBtn.setAttribute('data-alt', image.alt_text || '');
                }
                thumbnails.forEach((node) => node.classList.remove('active'));
                thumb.classList.add('active');
            });
        });

        if (zoomBtn) {
            zoomBtn.addEventListener('click', () => {
                const imageUrl = zoomBtn.getAttribute('data-image');
                const altText = zoomBtn.getAttribute('data-alt');
                this.openImageZoom(imageUrl, altText);
            });
        }

        modal.querySelectorAll('.color-filter-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const selectedColor = btn.getAttribute('data-color');
                modal.querySelectorAll('.color-filter-btn').forEach((button) => button.classList.remove('active'));
                btn.classList.add('active');

                thumbnails.forEach((thumb) => {
                    const thumbColor = thumb.getAttribute('data-color');
                    const shouldShow = (selectedColor === 'General' && (!thumbColor || thumbColor === 'General')) || thumbColor === selectedColor;
                    thumb.style.display = shouldShow ? 'block' : 'none';
                });

                const selector = selectedColor === 'General' ? '.thumbnail[data-color="General"]' : `.thumbnail[data-color="${selectedColor}"]`;
                const firstVisible = modal.querySelector(selector);
                if (firstVisible) {
                    firstVisible.click();
                }
            });
        });

        const scrollAmount = 160;
        if (scrollLeftBtn && thumbnailsContainer) {
            scrollLeftBtn.addEventListener('click', () => {
                thumbnailsContainer.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            });
        }
        if (scrollRightBtn && thumbnailsContainer) {
            scrollRightBtn.addEventListener('click', () => {
                thumbnailsContainer.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            });
        }
    }

    openImageZoom(imageUrl, altText) {
        if (!this.quickView.zoomModal || !this.quickView.zoomImage) return;
        this.quickView.zoomImage.src = imageUrl;
        this.quickView.zoomImage.alt = altText || '';
        if (this.quickView.zoomTitle) {
            this.quickView.zoomTitle.textContent = altText || 'Imagen del producto';
        }
        this.openModal(this.quickView.zoomModal);
    }

    renderActivity(entries) {
        if (!this.elements.activityFeed) return;
        if (!entries.length) {
            this.elements.activityFeed.innerHTML = '<div class="empty-state">Sin actividad reciente.</div>';
            return;
        }

        this.elements.activityFeed.innerHTML = entries.map((entry) => `
            <div class="activity-item">
                <div class="activity-icon activity-${entry.type}">
                    ${this.getActivityIcon(entry.type)}
                </div>
                <div class="activity-content">
                    <p class="activity-title">${entry.title}</p>
                    <span class="activity-description">${entry.description || ''}</span>
                    <span class="activity-time">${this.timeAgo(entry.created_at)}</span>
                </div>
            </div>
        `).join('');
    }

    toggleChartSkeleton(card, hideSkeleton) {
        if (!card) return;
        card.classList.toggle('is-loading', !hideSkeleton);
        const placeholder = card.querySelector('.chart-skeleton');
        if (placeholder) {
            placeholder.style.display = hideSkeleton ? 'none' : 'block';
        }
    }

    updateChangeBadge(element, value) {
        element.classList.remove('positive', 'negative');
        if (value > 0) {
            element.classList.add('positive');
            element.innerHTML = `<i class="fas fa-arrow-up"></i> ${value}%`;
        } else if (value < 0) {
            element.classList.add('negative');
            element.innerHTML = `<i class="fas fa-arrow-down"></i> ${Math.abs(value)}%`;
        } else {
            element.textContent = '0%';
        }
    }

    formatCurrency(value) {
        return this.formatters.currency.format(value || 0);
    }

    formatNumber(value) {
        return this.formatters.number.format(value || 0);
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        if (Number.isNaN(date.getTime())) return '--';
        return this.formatters.date.format(date);
    }

    getStatusLabel(status) {
        const labels = {
            pending: 'Pendiente',
            processing: 'En proceso',
            shipped: 'Enviado',
            delivered: 'Entregado',
            cancelled: 'Cancelado',
            refunded: 'Reembolsado'
        };
        return labels[status] || status || 'Pendiente';
    }

    getPaymentLabel(status) {
        const labels = {
            pending: 'Pendiente',
            paid: 'Pagado',
            failed: 'Fallido',
            refunded: 'Reembolso'
        };
        return labels[status] || 'Desconocido';
    }

    getActivityIcon(type) {
        const icons = {
            order: '<i class="fas fa-shopping-bag"></i>',
            customer: '<i class="fas fa-user"></i>',
            review: '<i class="fas fa-star"></i>'
        };
        return icons[type] || '<i class="fas fa-info-circle"></i>';
    }

    timeAgo(dateString) {
        const date = new Date(dateString);
        if (Number.isNaN(date.getTime())) return '';
        const diffMs = Date.now() - date.getTime();
        const minutes = Math.floor(diffMs / 60000);
        if (minutes < 60) return `${minutes} min`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours} h`;
        const days = Math.floor(hours / 24);
        return `${days} d`;
    }

    formatChartLabel(dateString) {
        const date = new Date(`${dateString}T00:00:00`);
        if (Number.isNaN(date.getTime())) return '--';
        return this.formatters.chartDate.format(date);
    }

    formatPriceRange(min, max) {
        const hasMin = typeof min === 'number' && !Number.isNaN(min);
        const hasMax = typeof max === 'number' && !Number.isNaN(max);
        if (!hasMin && !hasMax) {
            return 'Sin precio';
        }
        if (hasMin && hasMax) {
            if (min === max) {
                return this.formatCurrency(min);
            }
            return `${this.formatCurrency(min)} - ${this.formatCurrency(max)}`;
        }
        const value = hasMin ? min : max;
        return this.formatCurrency(value);
    }

    resolveImage(path) {
        if (!path) {
            return `${this.baseUrl}/images/default-product.jpg`;
        }
        if (/^https?:/i.test(path)) {
            return path;
        }
        const sanitized = path.replace(/^[/\\]+/, '');
        if (!this.baseUrl) {
            return `/${sanitized}`;
        }
        return `${this.baseUrl}/${sanitized}`;
    }

    openModal(modal) {
        if (!modal) return;
        modal.classList.add('active');
    }

    closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('active');
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new AdminDashboard());
} else {
    new AdminDashboard();
}
