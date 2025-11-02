// ==================== CONFIGURACIÓN ====================
const API_BASE = window.location.origin + '/angelow/admin/api/reports_api.php';

let distributionChart, topCustomersChart;
let currentFilters = {
    minOrders: 2
};

// ==================== INICIALIZACIÓN ====================
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    loadCustomersData();
});

// ==================== CARGA DE DATOS ====================

async function loadCustomersData() {
    try {
        await Promise.all([
            loadCustomerStats(),
            loadRecurringCustomers(),
            loadTopCustomers(),
            loadCustomersTable()
        ]);
    } catch (error) {
        console.error('Error al cargar datos de clientes:', error);
    }
}

async function loadCustomerStats() {
    try {
        const response = await fetch(`${API_BASE}?action=customer_stats`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Error:', data.error);
            return;
        }
        
        // Actualizar métricas generales
        document.getElementById('total-customers').textContent = data.general.total_customers;
        document.getElementById('customers-with-orders').textContent = data.general.customers_with_orders;
        
        const orderPercentage = data.general.total_customers > 0 
            ? ((data.general.customers_with_orders / data.general.total_customers) * 100).toFixed(1)
            : 0;
        document.getElementById('order-percentage').textContent = `${orderPercentage}% del total`;
        
        document.getElementById('avg-orders').textContent = parseFloat(data.general.avg_orders_per_customer).toFixed(1);
        
        // Actualizar gráfico de distribución
        if (data.distribution && data.distribution.length > 0) {
            const labels = data.distribution.map(item => item.segment);
            const counts = data.distribution.map(item => parseInt(item.customer_count));
            updateDistributionChart(labels, counts);
        }
        
    } catch (error) {
        console.error('Error al cargar estadísticas:', error);
    }
}

async function loadRecurringCustomers() {
    try {
        const params = new URLSearchParams({
            action: 'recurring_customers',
            min_orders: currentFilters.minOrders
        });
        
        const response = await fetch(`${API_BASE}?${params}`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Error:', data.error);
            return;
        }
        
        document.getElementById('recurring-customers').textContent = data.length;
        
    } catch (error) {
        console.error('Error al cargar clientes recurrentes:', error);
    }
}

async function loadTopCustomers() {
    try {
        const response = await fetch(`${API_BASE}?action=customer_lifetime_value&limit=10`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Error:', data.error);
            return;
        }
        
        const labels = data.map(c => c.name.length > 15 ? c.name.substring(0, 15) + '...' : c.name);
        const values = data.map(c => parseFloat(c.lifetime_value));
        
        updateTopCustomersChart(labels, values);
        
    } catch (error) {
        console.error('Error al cargar top clientes:', error);
    }
}

async function loadCustomersTable() {
    try {
        const params = new URLSearchParams({
            action: 'recurring_customers',
            min_orders: currentFilters.minOrders
        });
        
        const response = await fetch(`${API_BASE}?${params}`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Error:', data.error);
            return;
        }
        
        const tbody = document.querySelector('#customers-table tbody');
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="loading">No hay datos disponibles</td></tr>';
            return;
        }
        
        tbody.innerHTML = data.map((customer, index) => `
            <tr>
                <td><strong>${index + 1}</strong></td>
                <td>
                    <div class="product-info">
                        <span class="product-name">${customer.name}</span>
                    </div>
                </td>
                <td>${customer.email}</td>
                <td>${customer.phone || 'N/A'}</td>
                <td><strong>${customer.total_orders}</strong></td>
                <td><strong>${formatCurrency(customer.total_spent)}</strong></td>
                <td>${formatCurrency(customer.avg_order_value)}</td>
                <td>${formatDate(customer.first_order)}</td>
                <td>
                    ${formatDate(customer.last_order)}
                    <span class="badge ${getDaysSinceBadge(customer.customer_age_days)}">
                        ${customer.customer_age_days} días
                    </span>
                </td>
            </tr>
        `).join('');
        
    } catch (error) {
        console.error('Error al cargar tabla de clientes:', error);
    }
}

// ==================== INICIALIZAR GRÁFICOS ====================

function initCharts() {
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#64748b';
    
    // Gráfico de distribución
    const distributionCtx = document.getElementById('distributionChart').getContext('2d');
    distributionChart = new Chart(distributionCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#667eea',
                    '#f59e0b',
                    '#10b981',
                    '#ef4444'
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
                            return `${label}: ${value} clientes (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    
    // Gráfico de top clientes
    const topCustomersCtx = document.getElementById('topCustomersChart').getContext('2d');
    topCustomersChart = new Chart(topCustomersCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Valor de Vida',
                data: [],
                backgroundColor: 'rgba(16, 185, 129, 0.8)',
                borderRadius: 6,
                barThickness: 30
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
                            return 'Total: ' + formatCurrency(context.parsed.y);
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

function updateDistributionChart(labels, counts) {
    distributionChart.data.labels = labels;
    distributionChart.data.datasets[0].data = counts;
    distributionChart.update();
}

function updateTopCustomersChart(labels, values) {
    topCustomersChart.data.labels = labels;
    topCustomersChart.data.datasets[0].data = values;
    topCustomersChart.update();
}

// ==================== FILTROS ====================

function applyFilters() {
    const minOrders = document.getElementById('min-orders').value;
    currentFilters.minOrders = minOrders;
    
    loadRecurringCustomers();
    loadCustomersTable();
}

// ==================== EXPORTAR ====================

function exportToCSV() {
    const table = document.getElementById('customers-table');
    let csv = [];
    
    // Headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.textContent);
    });
    csv.push(headers.join(','));
    
    // Rows
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            row.push('"' + td.textContent.trim().replace(/"/g, '""') + '"');
        });
        if (row.length > 0) {
            csv.push(row.join(','));
        }
    });
    
    // Download
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `clientes_recurrentes_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// ==================== UTILIDADES ====================

function formatCurrency(amount) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

function getDaysSinceBadge(days) {
    if (days <= 30) return 'badge-success';
    if (days <= 90) return 'badge-warning';
    return 'badge-danger';
}
