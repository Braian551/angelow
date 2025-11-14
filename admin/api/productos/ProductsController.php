<?php
/**
 * Controlador de Productos para Admin
 * Maneja la lógica de negocio para la gestión de productos
 */

class ProductsController {
    private $db;
    
    public function __construct($connection) {
        $this->db = $connection;
    }
    
    /**
     * Obtener todas las categorías activas
     * @return array Lista de categorías
     */
    public function getActiveCategories() {
        try {
            $stmt = $this->db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener categorías: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener productos con filtros
     * @param array $filters Filtros de búsqueda
     * @param int $page Número de página
     * @param int $perPage Productos por página
     * @return array Resultado con productos y metadatos
     */
    public function getProducts($filters = [], $page = 1, $perPage = 12) {
        try {
            $where = [];
            $params = [];
            
            // Filtro de búsqueda
            if (!empty($filters['search'])) {
                $where[] = "(p.name LIKE :search OR p.brand LIKE :search OR p.description LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            // Filtro de categoría
            if (!empty($filters['category'])) {
                $where[] = "p.category_id = :category";
                $params[':category'] = $filters['category'];
            }
            
            // Filtro de estado
            if (!empty($filters['status'])) {
                if ($filters['status'] === 'active') {
                    $where[] = "p.is_active = 1";
                } elseif ($filters['status'] === 'inactive') {
                    $where[] = "p.is_active = 0";
                }
            }
            
            // Filtro de género
            if (!empty($filters['gender'])) {
                $where[] = "p.gender = :gender";
                $params[':gender'] = $filters['gender'];
            }
            
            // Construir ORDER BY
            $orderBy = $this->buildOrderBy($filters['order'] ?? 'newest');
            
            // Construir WHERE clause
            $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            
            // Contar total de productos
            $countSql = "SELECT COUNT(DISTINCT p.id) as total FROM products p $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Obtener productos paginados
            $offset = ($page - 1) * $perPage;
            $sql = "
                SELECT 
                    p.id,
                    p.name,
                    p.slug,
                    p.brand,
                    p.gender,
                    p.is_active,
                    p.created_at,
                    c.name as category_name,
                    (SELECT COUNT(*) FROM product_color_variants pcv WHERE pcv.product_id = p.id) AS variant_count,
                    (SELECT COALESCE(SUM(psv.quantity), 0) 
                     FROM product_color_variants pcv
                     JOIN product_size_variants psv ON psv.color_variant_id = pcv.id
                     WHERE pcv.product_id = p.id) AS total_stock,
                    (SELECT COALESCE(MIN(psv.price), p.price) 
                     FROM product_color_variants pcv
                     JOIN product_size_variants psv ON psv.color_variant_id = pcv.id
                     WHERE pcv.product_id = p.id) AS min_price,
                    (SELECT COALESCE(MAX(psv.price), p.price) 
                     FROM product_color_variants pcv
                     JOIN product_size_variants psv ON psv.color_variant_id = pcv.id
                     WHERE pcv.product_id = p.id) AS max_price,
                    (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY `order` LIMIT 1) AS primary_image
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                $whereClause
                ORDER BY " . $orderBy . "
                LIMIT :limit OFFSET :offset
            ";
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'products' => $products,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'perPage' => $perPage,
                    'totalPages' => ceil($total / $perPage)
                ]
            ];
            
        } catch (PDOException $e) {
            error_log("Error al obtener productos: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al obtener productos',
                'products' => [],
                'meta' => ['total' => 0, 'page' => 1, 'perPage' => $perPage, 'totalPages' => 0]
            ];
        }
    }
    
    /**
     * Construir cláusula ORDER BY según el filtro
     * @param string $order Tipo de ordenamiento
     * @return string Cláusula ORDER BY
     */
    private function buildOrderBy($order) {
        switch ($order) {
            case 'name_asc':
                return "p.name ASC";
            case 'name_desc':
                return "p.name DESC";
            case 'price_asc':
                return "min_price ASC";
            case 'price_desc':
                return "max_price DESC";
            case 'stock_asc':
                return "total_stock ASC";
            case 'stock_desc':
                return "total_stock DESC";
            case 'newest':
            default:
                return "p.created_at DESC";
        }
    }
    
    /**
     * Obtener detalles completos de un producto
     * @param int $productId ID del producto
     * @return array Detalles del producto
     */
    public function getProductDetails($productId) {
        try {
            // Obtener información del producto
            $sql = "
                SELECT 
                    p.*,
                    c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = :id
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                return ['success' => false, 'message' => 'Producto no encontrado'];
            }
            
            // Obtener imágenes
            $images = $this->getProductImages($productId);
            
            // Obtener variantes
            $variants = $this->getProductVariants($productId);
            
            // Calcular stock total
            $totalStock = array_sum(array_column($variants, 'quantity'));
            $minPrice = min(array_column($variants, 'price'));
            $maxPrice = max(array_column($variants, 'price'));
            
            return [
                'success' => true,
                'product' => $product,
                'images' => $images,
                'variants' => $variants,
                'total_stock' => $totalStock,
                'min_price' => $minPrice,
                'max_price' => $maxPrice
            ];
            
        } catch (PDOException $e) {
            error_log("Error al obtener detalles del producto: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener detalles'];
        }
    }
    
    /**
     * Obtener imágenes de un producto
     * @param int $productId ID del producto
     * @return array Lista de imágenes
     */
    private function getProductImages($productId) {
        $sql = "
            SELECT 
                pi.*,
                pcv.color_id,
                co.name as color_name,
                co.hex_code
            FROM product_images pi
            LEFT JOIN product_color_variants pcv ON pi.color_variant_id = pcv.id
            LEFT JOIN colors co ON pcv.color_id = co.id
            WHERE pi.product_id = :id
            ORDER BY pi.is_primary DESC, pi.`order` ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener variantes de un producto
     * @param int $productId ID del producto
     * @return array Lista de variantes
     */
    private function getProductVariants($productId) {
        $sql = "
            SELECT 
                pcv.id,
                pcv.product_id,
                pcv.color_id,
                pcv.is_default,
                co.name as color_name,
                co.hex_code,
                s.id as size_id,
                s.name as size_name,
                psv.sku,
                psv.barcode,
                psv.price,
                psv.compare_price,
                psv.quantity,
                psv.is_active
            FROM product_color_variants pcv
            LEFT JOIN colors co ON pcv.color_id = co.id
            LEFT JOIN product_size_variants psv ON pcv.id = psv.color_variant_id
            LEFT JOIN sizes s ON psv.size_id = s.id
            WHERE pcv.product_id = :id
            ORDER BY co.name, s.display_order
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Eliminar producto (desactivar)
     * @param int $productId ID del producto
     * @return array Resultado de la operación
     */
    public function deleteProduct($productId) {
        try {
            $this->db->beginTransaction();
            
            // Desactivar el producto
            $sql = "UPDATE products SET is_active = 0 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $productId]);
            
            // Desactivar variantes de talla
            $sql = "UPDATE product_size_variants SET is_active = 0 WHERE color_variant_id IN (SELECT id FROM product_color_variants WHERE product_id = :id)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $productId]);
            
            $this->db->commit();
            
            return ['success' => true, 'message' => 'Producto desactivado correctamente'];
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error al desactivar producto: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al desactivar producto'];
        }
    }
    
    /**
     * Activar producto
     * @param int $productId ID del producto
     * @return array Resultado de la operación
     */
    public function activateProduct($productId) {
        try {
            $this->db->beginTransaction();
            
            // Activar el producto
            $sql = "UPDATE products SET is_active = 1 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $productId]);
            
            // Activar variantes de talla
            $sql = "UPDATE product_size_variants SET is_active = 1 WHERE color_variant_id IN (SELECT id FROM product_color_variants WHERE product_id = :id)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $productId]);
            
            $this->db->commit();
            
            return ['success' => true, 'message' => 'Producto activado correctamente'];
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error al activar producto: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al activar producto'];
        }
    }
}
