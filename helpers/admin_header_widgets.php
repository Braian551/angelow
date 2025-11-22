<?php
/**
 * Configuracion compartida para los widgets del header de administrador.
 */

declare(strict_types=1);

if (!function_exists('getAdminQuickActions')) {
    /**
     * Acciones principales del boton "Accion rapida".
     */
    function getAdminQuickActions(): array
    {
        return [
            [
                'id' => 'create-product',
                'label' => 'Nuevo producto',
                'description' => 'Registra inventario, variaciones y fotos.',
                'icon' => 'fa-box-open',
                'path' => '/admin/subproducto.php',
                'keywords' => ['producto', 'inventario', 'nuevo', 'agregar']
            ],
            [
                'id' => 'manage-products',
                'label' => 'Gestionar productos',
                'description' => 'Administra catalogos y filtros avanzados.',
                'icon' => 'fa-layer-group',
                'path' => '/admin/products.php',
                'keywords' => ['productos', 'catalogo', 'stock', 'listado']
            ],
            [
                'id' => 'create-discount-code',
                'label' => 'Generar codigos de descuento',
                'description' => 'Crea campanas con cupones segmentados.',
                'icon' => 'fa-ticket',
                'path' => '/admin/descuento/generate_codes.php',
                'keywords' => ['descuento', 'cupon', 'promocion']
            ],
            [
                'id' => 'bulk-discount',
                'label' => 'Descuentos por cantidad',
                'description' => 'Configura reglas automaticas por volumen.',
                'icon' => 'fa-chart-bar',
                'path' => '/admin/descuento/bulk_discounts.php',
                'keywords' => ['descuento', 'mayorista', 'precios']
            ],
            [
                'id' => 'new-announcement',
                'label' => 'Publicar anuncio',
                'description' => 'Envia comunicados a clientes y equipos.',
                'icon' => 'fa-bullhorn',
                'path' => '/admin/announcements/add.php',
                'keywords' => ['anuncio', 'comunicado', 'mensaje']
            ],
            [
                'id' => 'review-payments',
                'label' => 'Revisar pagos pendientes',
                'description' => 'Aprueba comprobantes y confirma transferencias.',
                'icon' => 'fa-money-check-dollar',
                'path' => '/admin/orders.php?filter=pending-payments',
                'keywords' => ['pago', 'transferencia', 'pendiente']
            ],
            [
                'id' => 'inventory-alerts',
                'label' => 'Alertas de inventario',
                'description' => 'Detecta productos con stock critico.',
                'icon' => 'fa-warehouse',
                'path' => '/admin/inventario/inventory.php#low-stock',
                'keywords' => ['inventario', 'stock', 'alertas']
            ],
            [
                'id' => 'create-slider',
                'label' => 'Nuevo slider home',
                'description' => 'Actualiza banners principales de la tienda.',
                'icon' => 'fa-images',
                'path' => '/admin/sliders/add_slider.php',
                'keywords' => ['slider', 'banner', 'home']
            ],
        ];
    }
}

if (!function_exists('getAdminNavigationShortcuts')) {
    /**
     * Secciones clave del panel que deben aparecer en la busqueda global.
     */
    function getAdminNavigationShortcuts(): array
    {
        return [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'description' => 'Resumen operativo en tiempo real.',
                'icon' => 'fa-chart-line',
                'path' => '/admin/dashboardadmin.php',
                'keywords' => ['dashboard', 'inicio', 'panel'],
                'category' => 'module'
            ],
            [
                'id' => 'orders',
                'label' => 'Ordenes',
                'description' => 'Monitorea pedidos y estados de entrega.',
                'icon' => 'fa-receipt',
                'path' => '/admin/orders.php',
                'keywords' => ['ordenes', 'ventas', 'pedidos'],
                'category' => 'module'
            ],
            [
                'id' => 'customers',
                'label' => 'Clientes',
                'description' => 'Historial y segmentacion de compradores.',
                'icon' => 'fa-users',
                'path' => '/admin/clientes/index.php',
                'keywords' => ['clientes', 'usuarios', 'crm'],
                'category' => 'module'
            ],
            [
                'id' => 'reviews',
                'label' => 'Reseñas',
                'description' => 'Aprueba comentarios y preguntas.',
                'icon' => 'fa-star',
                'path' => '/admin/resenas/index.php',
                'keywords' => ['reseñas', 'reviews', 'opiniones'],
                'category' => 'module'
            ],
            [
                'id' => 'questions',
                'label' => 'Preguntas de productos',
                'description' => 'Gestiona respuestas y tiempos de atencion.',
                'icon' => 'fa-question-circle',
                'path' => '/admin/resenas/preguntas.php',
                'keywords' => ['preguntas', 'faq', 'clientes'],
                'category' => 'module'
            ],
            [
                'id' => 'inventory',
                'label' => 'Inventario',
                'description' => 'Control de stock y movimientos.',
                'icon' => 'fa-boxes-stacked',
                'path' => '/admin/inventario/inventory.php',
                'keywords' => ['inventario', 'stock', 'bodega'],
                'category' => 'module'
            ],
            [
                'id' => 'payments',
                'label' => 'Pagos y transferencias',
                'description' => 'Configura metodos y verifica comprobantes.',
                'icon' => 'fa-money-bill-wave',
                'path' => '/admin/pagos/define_pay.php',
                'keywords' => ['pagos', 'transferencias', 'metodos'],
                'category' => 'module'
            ],
            [
                'id' => 'announcements',
                'label' => 'Anuncios',
                'description' => 'Comunicaciones internas y externas.',
                'icon' => 'fa-bullhorn',
                'path' => '/admin/announcements/list.php',
                'keywords' => ['anuncios', 'noticias', 'comunicados'],
                'category' => 'module'
            ],
            [
                'id' => 'reports',
                'label' => 'Informes avanzados',
                'description' => 'Ventas, productos y clientes recurrentes.',
                'icon' => 'fa-chart-pie',
                'path' => '/admin/informes/ventas.php',
                'keywords' => ['informes', 'reportes', 'analytics'],
                'category' => 'module'
            ],
            [
                'id' => 'settings',
                'label' => 'Configuracion general',
                'description' => 'Datos de tienda, sliders y envios.',
                'icon' => 'fa-gear',
                'path' => '/admin/settings/general.php',
                'keywords' => ['configuracion', 'ajustes', 'preferencias'],
                'category' => 'module'
            ],
        ];
    }
}

if (!function_exists('getAdminSearchShortcuts')) {
    /**
     * Combina acciones y modulos para la busqueda global.
     */
    function getAdminSearchShortcuts(): array
    {
        $shortcuts = [];
        $withCategory = function (array $item, string $category): array {
            $item['category'] = $category;
            return $item;
        };

        foreach (getAdminQuickActions() as $action) {
            $shortcuts[$action['id']] = $withCategory($action, 'action');
        }

        foreach (getAdminNavigationShortcuts() as $nav) {
            $shortcuts[$nav['id']] = $withCategory($nav, $nav['category'] ?? 'module');
        }

        return array_values($shortcuts);
    }
}
