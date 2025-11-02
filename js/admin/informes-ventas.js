// ==================== CONFIGURACIÓN ====================
const API_BASE = window.location.origin + '/angelow/admin/api/reports_api.php';

let salesEvolutionChart, monthlyComparisonChart;
let currentFilters = {
    startDate: null,
    endDate: null,
    status: '',
    period: 'month'
};

// ==================== INICIALIZACIÓN ====================
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    setDefaultDates();
    loadSalesData();
});

function setDefaultDates() {
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    
    document.getElementById('end-date').valueAsDate = today;
    document.getElementById('start-date').valueAsDate = firstDayOfMonth;
    
    currentFilters.startDate = formatDate(firstDayOfMonth);
    currentFilters.endDate = formatDate(today);
}

// ==================== CARGA DE DATOS ====================

async function loadSalesData() {
    try {
        await Promise.all([
            loadSalesSummary(),
            loadSalesEvolution(),
            loadMonthlyComparison(),
            loadSalesDetail()
        ]);
    } catch (error) {
        console.error('Error al cargar datos de ventas:', error);
    }
}

async function loadSalesSummary() {
    try {
        const params = new URLSearchParams({
            action: 'sales_by_period',
            period: 'day',
            start_date: currentFilters.startDate,
            end_date: currentFilters.endDate
        });
        
        const response = await fetch(`${API_BASE}?${params}`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Error:', data.error);
            return;
        }
        
        // Calcular totales
        const totals = data.reduce((acc, item) => {
            acc.revenue += parseFloat(item.total_revenue);
            acc.orders += parseInt(item.total_orders);
            acc.subtotal += parseFloat(item.subtotal);
            acc.shipping += parseFloat(item.shipping_revenue);
            return acc;
        }, { revenue: 0, orders: 0, subtotal: 0, shipping: 0 });
        
        // Actualizar métricas
        document.getElementById('total-revenue').textContent = formatCurrency(totals.revenue);
        document.getElementById('total-orders').textContent = totals.orders;
        
        const avgOrderValue = totals.orders > 0 ? totals.revenue / totals.orders : 0;
        document.getElementById('avg-order-value').textContent = 'Ticket promedio: ' + formatCurrency(avgOrderValue);
        
        document.getElementById('total-shipping').textContent = formatCurrency(totals.shipping);
        
        // Los descuentos se calcularían sumando el campo discount_amount
        const discounts = totals.subtotal + totals.shipping - totals.revenue;
        document.getElementById('total-discounts').textContent = formatCurrency(Math.max(0, discounts));
        
    } catch (error) {
        console.error('Error al cargar resumen de ventas:', error);
    }
}

async function loadSalesEvolution() {
    try {
        const params = new URLSearchParams({
            action: 'sales_by_period',
            period: currentFilters.period,
            start_date: currentFilters.startDate,
            end_date: currentFilters.endDate
        });
        
        const response = await fetch(`${API_BASE}?${params}`);
        const data = await response.json();
        
        if (data.error) return;
        
        data.reverse();
        
        const labels = data.map(item => formatPeriodLabel(item.period_label, currentFilters.period));
        const revenues = data.map(item => parseFloat(item.total_revenue));
        const orders = data.map(item => parseInt(item.total_orders));
        
        updateSalesEvolutionChart(labels, revenues, orders);
        
    } catch (error) {
        console.error('Error al cargar evolución de ventas:', error);
    }
}

async function loadMonthlyComparison() {
    try {
        const response = await fetch(`${API_BASE}?action=revenue_comparison`);
        const data = await response.json();
        
        if (data.error) return;
        
        const labels = ['Mes Anterior', 'Mes Actual'];
        const revenues = [
            parseFloat(data.month.previous),
            parseFloat(data.month.current)
        ];
        
        updateMonthlyComparisonChart(labels, revenues, data.month.growth_percentage);
        
    } catch (error) {
        console.error('Error al cargar comparativa mensual:', error);
    }
}

async function loadSalesDetail() {
    try {
        const params = new URLSearchParams({
            action: 'sales_by_period',
            period: currentFilters.period,
            start_date: currentFilters.startDate,
            end_date: currentFilters.endDate
        });
        
        const response = await fetch(`${API_BASE}?${params}`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Error:', data.error);
            return;
        }
        
        const tbody = document.querySelector('#sales-detail-table tbody');
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="loading">No hay datos para el período seleccionado</td></tr>';
            return;
        }
        
        data.reverse();
        
        tbody.innerHTML = data.map(item => {
            const discounts = parseFloat(item.subtotal) + parseFloat(item.shipping_revenue) - parseFloat(item.total_revenue);
            return `
                <tr>
                    <td><strong>${formatPeriodLabel(item.period_label, currentFilters.period)}</strong></td>
                    <td>${item.total_orders}</td>
                    <td>${formatCurrency(item.subtotal)}</td>
                    <td>${formatCurrency(item.shipping_revenue)}</td>
                    <td>${formatCurrency(Math.max(0, discounts))}</td>
                    <td><strong>${formatCurrency(item.total_revenue)}</strong></td>
                    <td>${formatCurrency(item.avg_order_value)}</td>
                </tr>
            `;
        }).join('');
        
    } catch (error) {
        console.error('Error al cargar detalle de ventas:', error);
    }
}

// ==================== INICIALIZAR GRÁFICOS ====================

function initCharts() {
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#64748b';
    
    // Gráfico de evolución
    const evolutionCtx = document.getElementById('salesEvolutionChart').getContext('2d');
    salesEvolutionChart = new Chart(evolutionCtx, {
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
                yAxisID: 'y'
            }, {
                label: 'Órdenes',
                data: [],
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                yAxisID: 'y1'
            }]
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
                    position: 'top',
                    align: 'end'
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            const label = context.dataset.label || '';
                            const value = context.parsed.y;
                            if (label === 'Ingresos') {
                                return label + ': ' + formatCurrency(value);
                            }
                            return label + ': ' + value;
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
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
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false
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
    
    // Gráfico de comparativa mensual
    const comparisonCtx = document.getElementById('monthlyComparisonChart').getContext('2d');
    monthlyComparisonChart = new Chart(comparisonCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Ingresos',
                data: [],
                backgroundColor: ['#94a3b8', '#667eea'],
                borderRadius: 6,
                barThickness: 60
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

function updateSalesEvolutionChart(labels, revenues, orders) {
    salesEvolutionChart.data.labels = labels;
    salesEvolutionChart.data.datasets[0].data = revenues;
    salesEvolutionChart.data.datasets[1].data = orders;
    salesEvolutionChart.update();
}

function updateMonthlyComparisonChart(labels, revenues, growthPercentage) {
    monthlyComparisonChart.data.labels = labels;
    monthlyComparisonChart.data.datasets[0].data = revenues;
    monthlyComparisonChart.update();
}

// ==================== FILTROS ====================

function applyFilters() {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    const status = document.getElementById('status-filter').value;
    const period = document.getElementById('period-filter').value;
    
    if (startDate && endDate) {
        currentFilters.startDate = startDate;
        currentFilters.endDate = endDate;
    }
    
    currentFilters.status = status;
    currentFilters.period = period;
    
    loadSalesData();
}

function resetFilters() {
    document.getElementById('status-filter').value = '';
    document.getElementById('period-filter').value = 'month';
    setDefaultDates();
    currentFilters.status = '';
    currentFilters.period = 'month';
    loadSalesData();
}

// ==================== EXPORTAR ====================

function exportToCSV() {
    const table = document.getElementById('sales-detail-table');
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
            row.push('"' + td.textContent.trim() + '"');
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
    link.setAttribute('download', `ventas_${currentFilters.startDate}_${currentFilters.endDate}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function printReport() {
    window.print();
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

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatPeriodLabel(label, period) {
    if (period === 'day') {
        const date = new Date(label);
        return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' });
    } else if (period === 'week') {
        return 'Semana ' + label.split('-W')[1];
    } else if (period === 'month') {
        const [year, month] = label.split('-');
        const date = new Date(year, month - 1);
        return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'long' });
    } else {
        return label;
    }
}
