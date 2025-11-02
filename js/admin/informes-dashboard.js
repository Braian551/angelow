// ==================== CONFIGURACIÓN ====================
const API_BASE = window.location.origin + '/angelow/admin/api/reports_api.php';

let salesChart, statusChart, categoriesChart;

// ==================== INICIALIZACIÓN ====================
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    loadSummaryData();
    loadSalesChart('month');
    loadStatusChart();
    loadCategoriesChart();
    loadTopProducts();
    loadTopCustomers();
    
    // Event listeners
    document.getElementById('period-select').addEventListener('change', function(e) {
        loadSalesChart(e.target.value);
    });
});

// ==================== FUNCIONES DE CARGA DE DATOS ====================

async function loadSummaryData() {
    try {
        const response = await fetch(`${API_BASE}?action=sales_summary`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Error:', data.error);
            return;
        }
        
        // Actualizar tarjetas de resumen
        updateSummaryCard('today', data.today);
        updateSummaryCard('month', data.month);
        updateSummaryCard('year', data.year);
        document.getElementById('avg-ticket').textContent = formatCurrency(data.average_ticket);
        
        // Cargar comparativas para mostrar crecimiento
        loadGrowthData();
        
    } catch (error) {
        console.error('Error al cargar resumen:', error);
    }
}

function updateSummaryCard(period, data) {
    document.getElementById(`sales-${period}`).textContent = formatCurrency(data.total_revenue);
    document.getElementById(`orders-${period}`).textContent = `${data.total_orders} órdenes`;
}

async function loadGrowthData() {
    try {
        const response = await fetch(`${API_BASE}?action=revenue_comparison`);
        const data = await response.json();
        
        if (data.error) return;
        
        // Crecimiento del mes
        const monthGrowth = document.getElementById('month-growth');
        if (data.month.growth_percentage !== 0) {
            const isPositive = data.month.growth_percentage > 0;
            monthGrowth.className = `card-growth ${isPositive ? 'positive' : 'negative'}`;
            monthGrowth.innerHTML = `<i class="fas fa-arrow-${isPositive ? 'up' : 'down'}"></i> ${Math.abs(data.month.growth_percentage).toFixed(1)}%`;
        }
        
        // Crecimiento del año
        const yearGrowth = document.getElementById('year-growth');
        if (data.year.growth_percentage !== 0) {
            const isPositive = data.year.growth_percentage > 0;
            yearGrowth.className = `card-growth ${isPositive ? 'positive' : 'negative'}`;
            yearGrowth.innerHTML = `<i class="fas fa-arrow-${isPositive ? 'up' : 'down'}"></i> ${Math.abs(data.year.growth_percentage).toFixed(1)}%`;
        }
        
    } catch (error) {
        console.error('Error al cargar comparativas:', error);
    }
}

async function loadSalesChart(period) {
    try {
        const response = await fetch(`${API_BASE}?action=sales_by_period&period=${period}`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Error:', data.error);
            return;
        }
        
        // Invertir el orden para mostrar cronológicamente
        data.reverse();
        
        const labels = data.map(item => formatPeriodLabel(item.period_label, period));
        const revenues = data.map(item => parseFloat(item.total_revenue));
        const orders = data.map(item => parseInt(item.total_orders));
        
        updateSalesChart(labels, revenues, orders);
        
    } catch (error) {
        console.error('Error al cargar gráfico de ventas:', error);
    }
}

async function loadStatusChart() {
    try {
        const response = await fetch(`${API_BASE}?action=sales_by_status`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Error:', data.error);
            return;
        }
        
        const labels = data.map(item => item.status_label);
        const counts = data.map(item => parseInt(item.count));
        
        updateStatusChart(labels, counts);
        
    } catch (error) {
        console.error('Error al cargar gráfico de estados:', error);
    }
}

async function loadCategoriesChart() {
    try {
        const response = await fetch(`${API_BASE}?action=product_categories_sales`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Error:', data.error);
            return;
        }
        
        // Filtrar categorías con ventas y limitar a top 10
        const filtered = data.filter(item => parseFloat(item.total_revenue) > 0)
                            .slice(0, 10);
        
        const labels = filtered.map(item => item.name);
        const revenues = filtered.map(item => parseFloat(item.total_revenue));
        
        updateCategoriesChart(labels, revenues);
        
    } catch (error) {
        console.error('Error al cargar gráfico de categorías:', error);
    }
}

async function loadTopProducts() {
    try {
        const response = await fetch(`${API_BASE}?action=top_products&limit=5`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Error:', data.error);
            return;
        }
        
        const tbody = document.querySelector('#top-products-table tbody');
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="loading">No hay datos disponibles</td></tr>';
            return;
        }
        
        tbody.innerHTML = data.map(product => `
            <tr>
                <td>
                    <div class="product-cell">
                        <img src="${window.location.origin}/angelow/${product.image || 'images/default-product.jpg'}" 
                             alt="${product.name}" 
                             class="product-img"
                             onerror="this.src='${window.location.origin}/angelow/images/default-product.jpg'">
                        <div class="product-info">
                            <span class="product-name">${product.name}</span>
                        </div>
                    </div>
                </td>
                <td>${product.category_name || 'Sin categoría'}</td>
                <td>${product.total_quantity} unidades</td>
                <td><strong>${formatCurrency(product.total_revenue)}</strong></td>
            </tr>
        `).join('');
        
    } catch (error) {
        console.error('Error al cargar productos:', error);
    }
}

async function loadTopCustomers() {
    try {
        const response = await fetch(`${API_BASE}?action=customer_lifetime_value&limit=5`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Error:', data.error);
            return;
        }
        
        const tbody = document.querySelector('#top-customers-table tbody');
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="loading">No hay datos disponibles</td></tr>';
            return;
        }
        
        tbody.innerHTML = data.map(customer => `
            <tr>
                <td>
                    <div class="product-info">
                        <span class="product-name">${customer.name}</span>
                        <span class="product-category">${customer.email}</span>
                    </div>
                </td>
                <td>${customer.total_orders} órdenes</td>
                <td><strong>${formatCurrency(customer.lifetime_value)}</strong></td>
            </tr>
        `).join('');
        
    } catch (error) {
        console.error('Error al cargar clientes:', error);
    }
}

// ==================== INICIALIZAR GRÁFICOS ====================

function initCharts() {
    // Configuración común
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#64748b';
    
    // Gráfico de ventas
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Ingresos',
                data: [],
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end'
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 14 },
                    bodyFont: { size: 13 },
                    callbacks: {
                        label: function(context) {
                            return 'Ingresos: ' + formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + (value / 1000).toFixed(0) + 'k';
                        }
                    },
                    grid: {
                        color: '#f1f5f9'
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
    
    // Gráfico de estados
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#fbbf24',
                    '#3b82f6',
                    '#8b5cf6',
                    '#10b981',
                    '#ef4444',
                    '#64748b'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    
    // Gráfico de categorías
    const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
    categoriesChart = new Chart(categoriesCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Ingresos',
                data: [],
                backgroundColor: '#667eea',
                borderRadius: 6,
                barThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            return 'Ingresos: ' + formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + (value / 1000).toFixed(0) + 'k';
                        }
                    },
                    grid: {
                        color: '#f1f5f9'
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
}

// ==================== ACTUALIZAR GRÁFICOS ====================

function updateSalesChart(labels, revenues, orders) {
    salesChart.data.labels = labels;
    salesChart.data.datasets[0].data = revenues;
    salesChart.update();
}

function updateStatusChart(labels, counts) {
    statusChart.data.labels = labels;
    statusChart.data.datasets[0].data = counts;
    statusChart.update();
}

function updateCategoriesChart(labels, revenues) {
    categoriesChart.data.labels = labels;
    categoriesChart.data.datasets[0].data = revenues;
    categoriesChart.update();
}

// ==================== FUNCIONES DE UTILIDAD ====================

function formatCurrency(amount) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

function formatPeriodLabel(label, period) {
    if (period === 'day') {
        const date = new Date(label);
        return date.toLocaleDateString('es-ES', { month: 'short', day: 'numeric' });
    } else if (period === 'week') {
        return 'Semana ' + label.split('-W')[1];
    } else if (period === 'month') {
        const [year, month] = label.split('-');
        const date = new Date(year, month - 1);
        return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'short' });
    } else {
        return label;
    }
}

function refreshAllData() {
    const btn = document.querySelector('.btn-refresh i');
    btn.style.transform = 'rotate(360deg)';
    
    loadSummaryData();
    loadSalesChart(document.getElementById('period-select').value);
    loadStatusChart();
    loadCategoriesChart();
    loadTopProducts();
    loadTopCustomers();
    
    setTimeout(() => {
        btn.style.transform = 'rotate(0deg)';
    }, 300);
}
