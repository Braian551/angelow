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
        this.loadData();
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

        const labels = breakdown.map((item) => item.label);
        const values = breakdown.map((item) => item.count);
        const colors = breakdown.map((item) => item.color);

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
        this.renderStatusList(breakdown);
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
                    <td>#${orderLabel}</td>
                    <td>${order.customer_name || 'Sin cliente'}</td>
                    <td>${this.formatDate(order.created_at)}</td>
                    <td>${this.formatCurrency(order.total)}</td>
                    <td><span class="${statusClass}">${this.getStatusLabel(order.status)}</span></td>
                    <td>${this.getPaymentLabel(order.payment_status)}</td>
                </tr>
            `;
        }).join('');
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
        if (!this.elements.topProductsList) return;
        if (!products.length) {
            this.elements.topProductsList.innerHTML = '<div class="empty-state">Aún no hay ventas registradas.</div>';
            return;
        }

        this.elements.topProductsList.innerHTML = products.map((product) => `
            <div class="top-product-item">
                <div>
                    <h4>${product.name}</h4>
                    <span>${product.category || 'Sin categoría'}</span>
                </div>
                <div class="top-product-metrics">
                    <span>${this.formatNumber(product.total_quantity)} uds.</span>
                    <strong>${this.formatCurrency(product.total_revenue)}</strong>
                </div>
            </div>
        `).join('');
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
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new AdminDashboard());
} else {
    new AdminDashboard();
}
