// ==================== CONFIGURACIÓN ====================
const API_BASE = window.location.origin + '/angelow/admin/api/reports_api.php';

let topProductsChart, categoriesChart, quantityChart;
let currentFilters = {
    startDate: null,
    endDate: null,
    limit: 50
};

// ==================== INICIALIZACIÓN ====================
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    setDefaultDates();
    loadProductsData();
});

function setDefaultDates() {
    const today = new Date();
    const threeMonthsAgo = new Date(today.getFullYear(), today.getMonth() - 3, today.getDate());
    
    document.getElementById('end-date').valueAsDate = today;
    document.getElementById('start-date').valueAsDate = threeMonthsAgo;
    
    currentFilters.startDate = formatDate(threeMonthsAgo);
    currentFilters.endDate = formatDate(today);
}

// ==================== CARGA DE DATOS ====================

async function loadProductsData() {
    try {
        await Promise.all([
            loadTopProducts(),
            loadCategoriesSales(),
            loadProductsTable()
        ]);
    } catch (error) {
        console.error('Error al cargar datos de productos:', error);
    }
}

async function loadTopProducts() {
    try {
        const params = new URLSearchParams({
            action: 'top_products',
            limit: 10,
            start_date: currentFilters.startDate,
            end_date: currentFilters.endDate
        });
        
        const response = await fetch(`${API_BASE}?${params}`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Error:', data.error);
            return;
        }
        
        // Gráfico por ingresos
        const labels = data.map(p => p.name.length > 20 ? p.name.substring(0, 20) + '...' : p.name);
        const revenues = data.map(p => parseFloat(p.total_revenue));
        updateTopProductsChart(labels, revenues);
        
        // Gráfico por cantidad
        const quantities = data.map(p => parseInt(p.total_quantity));
        updateQuantityChart(labels, quantities);
        
    } catch (error) {
        console.error('Error al cargar productos:', error);
    }
}

async function loadCategoriesSales() {
    try {
        const response = await fetch(`${API_BASE}?action=product_categories_sales`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Error:', data.error);
            return;
        }
        
        const filtered = data.filter(item => parseFloat(item.total_revenue) > 0);
        const labels = filtered.map(item => item.name);
        const revenues = filtered.map(item => parseFloat(item.total_revenue));
        
        updateCategoriesChart(labels, revenues);
        
    } catch (error) {
        console.error('Error al cargar categorías:', error);
    }
}

async function loadProductsTable() {
    try {
        const params = new URLSearchParams({
            action: 'top_products',
            limit: currentFilters.limit,
            start_date: currentFilters.startDate,
            end_date: currentFilters.endDate
        });
        
        const response = await fetch(`${API_BASE}?${params}`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Error:', data.error);
            return;
        }
        
        const tbody = document.querySelector('#products-table tbody');
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="loading">No hay datos disponibles</td></tr>';
            return;
        }
        
        tbody.innerHTML = data.map((product, index) => `
            <tr>
                <td><strong>${index + 1}</strong></td>
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
                <td>${product.times_sold}</td>
                <td><strong>${product.total_quantity}</strong> unidades</td>
                <td>${formatCurrency(product.avg_price)}</td>
                <td><strong>${formatCurrency(product.total_revenue)}</strong></td>
            </tr>
        `).join('');
        
    } catch (error) {
        console.error('Error al cargar tabla de productos:', error);
    }
}

// ==================== INICIALIZAR GRÁFICOS ====================

function initCharts() {
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#64748b';
    
    // Gráfico de top productos por ingresos
    const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
    topProductsChart = new Chart(topProductsCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Ingresos',
                data: [],
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
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
                            return 'Ingresos: ' + formatCurrency(context.parsed.x);
                        }
                    }
                }
            },
            scales: {
                x: {
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
                y: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
    
    // Gráfico de categorías
    const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
    categoriesChart = new Chart(categoriesCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#667eea',
                    '#764ba2',
                    '#f093fb',
                    '#4facfe',
                    '#00f2fe',
                    '#43e97b',
                    '#ffd89b',
                    '#f6416c',
                    '#f4e285',
                    '#a770ef'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
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
                            return `${label}: ${formatCurrency(value)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    
    // Gráfico de cantidad vendida
    const quantityCtx = document.getElementById('quantityChart').getContext('2d');
    quantityChart = new Chart(quantityCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Cantidad',
                data: [],
                backgroundColor: '#f59e0b',
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
                    padding: 12
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
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

function updateTopProductsChart(labels, revenues) {
    topProductsChart.data.labels = labels;
    topProductsChart.data.datasets[0].data = revenues;
    topProductsChart.update();
}

function updateCategoriesChart(labels, revenues) {
    categoriesChart.data.labels = labels;
    categoriesChart.data.datasets[0].data = revenues;
    categoriesChart.update();
}

function updateQuantityChart(labels, quantities) {
    quantityChart.data.labels = labels;
    quantityChart.data.datasets[0].data = quantities;
    quantityChart.update();
}

// ==================== FILTROS ====================

function applyFilters() {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    const limit = document.getElementById('limit-select').value;
    
    if (startDate && endDate) {
        currentFilters.startDate = startDate;
        currentFilters.endDate = endDate;
    }
    
    currentFilters.limit = limit;
    
    loadProductsData();
}

function resetFilters() {
    document.getElementById('limit-select').value = '50';
    setDefaultDates();
    currentFilters.limit = 50;
    loadProductsData();
}

// ==================== EXPORTAR ====================

function exportToCSV() {
    const table = document.getElementById('products-table');
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
    link.setAttribute('download', `productos_${currentFilters.startDate}_${currentFilters.endDate}.csv`);
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

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}
