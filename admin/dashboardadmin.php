<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../auth/role_redirect.php';

// Verificar que el usuario tenga rol de admin
requireRole('admin');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Angelow</title>
    <meta name="description" content="Panel de administración para la gestión de la tienda de ropa infantil Angelow">
    <link rel="icon" href="<?= BASE_URL ?>/images/logo.png" type="image/x-icon">
    <!-- Font Awesome para íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboardadmin.css">
</head>

<body>
    <!-- Contenedor principal -->
    <div class="admin-container">
        <!-- Sidebar de navegación -->
          <?php require_once __DIR__ . '/../layouts/headeradmin2.php'; ?>
        <!-- Contenido principal -->
        <main class="admin-content">
            <!-- Barra superior -->
            <?php require_once __DIR__ . '/../layouts/headeradmin1.php'; ?>
            
            <!-- Contenido del dashboard -->
            <div class="dashboard-content">
                <!-- Resumen estadístico -->
                <section class="stats-summary">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Órdenes hoy</h3>
                            <span class="stat-value">24</span>
                            <span class="stat-change positive">+12%</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-success">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Ingresos hoy</h3>
                            <span class="stat-value">$1,245.50</span>
                            <span class="stat-change positive">+8%</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-warning">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Nuevos clientes</h3>
                            <span class="stat-value">7</span>
                            <span class="stat-change negative">-3%</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-danger">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Visitas</h3>
                            <span class="stat-value">1,024</span>
                            <span class="stat-change positive">+22%</span>
                        </div>
                    </div>
                </section>
                
                <!-- Gráficos principales -->
                <section class="main-charts">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Ventas mensuales</h3>
                            <select class="chart-period">
                                <option>Últimos 7 días</option>
                                <option selected>Este mes</option>
                                <option>Últimos 3 meses</option>
                                <option>Este año</option>
                            </select>
                        </div>
                        <div class="chart-container">
                            <!-- Aquí iría el gráfico de ventas -->
                            <div class="chart-placeholder">
                                <i class="fas fa-chart-bar"></i>
                                <p>Gráfico de ventas mensuales</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Productos más vendidos</h3>
                            <select class="chart-period">
                                <option>Hoy</option>
                                <option selected>Esta semana</option>
                                <option>Este mes</option>
                            </select>
                        </div>
                        <div class="chart-container">
                            <!-- Aquí iría el gráfico de productos -->
                            <div class="chart-placeholder">
                                <i class="fas fa-chart-pie"></i>
                                <p>Gráfico de productos más vendidos</p>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Últimas órdenes -->
                <section class="recent-orders">
                    <div class="section-header">
                        <h3>Órdenes recientes</h3>
                        <a href="#todas-ordenes" class="view-all">Ver todas</a>
                    </div>
                    
                    <div class="orders-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID Orden</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#10025</td>
                                    <td>María González</td>
                                    <td>12/05/2025</td>
                                    <td>$89.90</td>
                                    <td><span class="status-badge processing">En proceso</span></td>
                                    <td>
                                        <button class="dashboard-action view"><i class="fas fa-eye"></i></button>
                                        <button class="dashboard-action edit"><i class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#10024</td>
                                    <td>Carlos Rodríguez</td>
                                    <td>12/05/2025</td>
                                    <td>$124.50</td>
                                    <td><span class="status-badge shipped">En camino</span></td>
                                    <td>
                                        <button class="dashboard-action view"><i class="fas fa-eye"></i></button>
                                        <button class="dashboard-action edit"><i class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#10023</td>
                                    <td>Ana Martínez</td>
                                    <td>11/05/2025</td>
                                    <td>$56.70</td>
                                    <td><span class="status-badge delivered">Entregado</span></td>
                                    <td>
                                        <button class="dashboard-action view"><i class="fas fa-eye"></i></button>
                                        <button class="dashboard-action edit"><i class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#10022</td>
                                    <td>Juan Pérez</td>
                                    <td>11/05/2025</td>
                                    <td>$210.30</td>
                                    <td><span class="status-badge pending">Pendiente</span></td>
                                    <td>
                                        <button class="dashboard-action view"><i class="fas fa-eye"></i></button>
                                        <button class="dashboard-action edit"><i class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#10021</td>
                                    <td>Laura Sánchez</td>
                                    <td>10/05/2025</td>
                                    <td>$78.20</td>
                                    <td><span class="status-badge cancelled">Cancelado</span></td>
                                    <td>
                                        <button class="dashboard-action view"><i class="fas fa-eye"></i></button>
                                        <button class="dashboard-action edit"><i class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
                
                <!-- Productos con bajo stock -->
                <section class="low-stock">
                    <div class="section-header">
                        <h3>Productos con bajo stock</h3>
                        <a href="#inventario" class="view-all">Ver inventario</a>
                    </div>
                    
                    <div class="products-grid">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?= BASE_URL ?>/images/productos/conjunto_niña.jpg" alt="Conjunto Niña">
                                <span class="stock-badge danger">Solo 2</span>
                            </div>
                            <div class="product-info">
                                <h4>Conjunto Niña</h4>
                                <p>Tallas: 2-4 años</p>
                                <div class="product-meta">
                                    <span class="price">$35.90</span>
                                    <button class="restock-btn">Reabastecer</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?= BASE_URL ?>/images/productos/pijamas.jpg" alt="Pijama Niño">
                                <span class="stock-badge warning">Solo 4</span>
                            </div>
                            <div class="product-info">
                                <h4>Pijama Niño</h4>
                                <p>Tallas: 6-8 años</p>
                                <div class="product-meta">
                                    <span class="price">$28.50</span>
                                    <button class="restock-btn">Reabastecer</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?= BASE_URL ?>/images/productos/vestido.jpg" alt="Vestido Niña">
                                <span class="stock-badge danger">Solo 1</span>
                            </div>
                            <div class="product-info">
                                <h4>Vestido Niña</h4>
                                <p>Tallas: 4-6 años</p>
                                <div class="product-meta">
                                    <span class="price">$42.90</span>
                                    <button class="restock-btn">Reabastecer</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?= BASE_URL ?>/images/productos/deportivo.jpg" alt="Conjunto Deportivo">
                                <span class="stock-badge warning">Solo 3</span>
                            </div>
                            <div class="product-info">
                                <h4>Conjunto Deportivo</h4>
                                <p>Tallas: 8-10 años</p>
                                <div class="product-meta">
                                    <span class="price">$39.90</span>
                                    <button class="restock-btn">Reabastecer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Actividad reciente -->
                <section class="recent-activity">
                    <div class="section-header">
                        <h3>Actividad reciente</h3>
                        <a href="#todas-actividades" class="view-all">Ver todo</a>
                    </div>
                    
                    <div class="activity-list">
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="activity-content">
                                <p><strong>Nueva orden</strong> #10025 de María González por $89.90</p>
                                <span class="activity-time">Hace 15 minutos</span>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="activity-content">
                                <p><strong>Nuevo cliente registrado:</strong> Carlos Fernández</p>
                                <span class="activity-time">Hace 1 hora</span>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="activity-content">
                                <p><strong>Nueva reseña</strong> para "Vestido Niña" - 5 estrellas</p>
                                <span class="activity-time">Hace 2 horas</span>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-tshirt"></i>
                            </div>
                            <div class="activity-content">
                                <p><strong>Producto actualizado:</strong> Pijama Niño - Talla 6-8</p>
                                <span class="activity-time">Hace 3 horas</span>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="activity-content">
                                <p><strong>Orden enviada</strong> #10024 a Carlos Rodríguez</p>
                                <span class="activity-time">Hace 5 horas</span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
    
    <!-- Modal para acciones rápidas -->
    <div class="modal-overlay" id="quickActionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Acción rápida</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="quick-actions-grid">
                    <a href="#agregar-producto" class="quick-action">
                        <i class="fas fa-plus-circle"></i>
                        <span>Agregar producto</span>
                    </a>
                    
                    <a href="#crear-orden" class="quick-action">
                        <i class="fas fa-shopping-bag"></i>
                        <span>Crear orden</span>
                    </a>
                    
                    <a href="#agregar-cliente" class="quick-action">
                        <i class="fas fa-user-plus"></i>
                        <span>Agregar cliente</span>
                    </a>
                    
                    <a href="#generar-informe" class="quick-action">
                        <i class="fas fa-file-export"></i>
                        <span>Generar informe</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script src="<?= BASE_URL ?>/js/dashboardadmin.js"></script>
</body>
</html>