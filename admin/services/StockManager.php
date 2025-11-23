<?php

class StockManager {
    
    // Estados que se consideran como "stock descontado"
    // pending: generalmente no descuenta hasta que se paga o confirma, pero depende del negocio.
    // El usuario pidió: "cuando una orden se pasa a un estado de enviado o pagado se baje el stock"
    // Asumimos que 'pending' NO ha descontado stock aún.
    private static $deductedStatuses = ['processing', 'shipped', 'delivered', 'completed'];

    /**
     * Ajusta el stock basado en el cambio de estado de la orden.
     *
     * @param PDO $conn Conexión a la base de datos
     * @param int $orderId ID de la orden
     * @param string $newStatus Nuevo estado de la orden
     * @param string $oldStatus Estado anterior de la orden
     * @return array Resultado de la operación ['success' => bool, 'message' => string]
     */
    public static function adjustStock($conn, $orderId, $newStatus, $oldStatus) {
        // Normalizar estados
        $newStatus = strtolower($newStatus);
        $oldStatus = strtolower($oldStatus);

        // Si el estado no cambia, no hacer nada
        if ($newStatus === $oldStatus) {
            return ['success' => true, 'message' => 'No status change'];
        }

        $isNewDeducted = in_array($newStatus, self::$deductedStatuses);
        $isOldDeducted = in_array($oldStatus, self::$deductedStatuses);

        try {
            if (!$isOldDeducted && $isNewDeducted) {
                // De "No descontado" a "Descontado" -> BAJAR STOCK
                return self::decreaseStock($conn, $orderId);
            } elseif ($isOldDeducted && !$isNewDeducted) {
                // De "Descontado" a "No descontado" (ej. Cancelado/Reembolsado) -> SUBIR STOCK
                return self::increaseStock($conn, $orderId);
            }
            
            // Si ambos son descontados (ej. processing -> shipped) o ambos no descontados (pending -> cancelled), no se hace nada.
            return ['success' => true, 'message' => 'No stock adjustment needed'];

        } catch (Exception $e) {
            error_log("StockManager Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Disminuye el stock de los productos en la orden.
     */
    public static function decreaseStock($conn, $orderId) {
        $items = self::getOrderItems($conn, $orderId);
        $count = 0;

        foreach ($items as $item) {
            if (!empty($item['size_variant_id'])) {
                $stmt = $conn->prepare("UPDATE product_size_variants SET quantity = quantity - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['size_variant_id']]);
                $count++;
            } elseif (!empty($item['product_id'])) {
                // Lógica para productos simples si existieran (actualmente parece que todo usa variantes)
                // $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                // $stmt->execute([$item['quantity'], $item['product_id']]);
            }
        }

        return ['success' => true, 'message' => "Stock decreased for $count items"];
    }

    /**
     * Aumenta (restaura) el stock de los productos en la orden.
     */
    public static function increaseStock($conn, $orderId) {
        $items = self::getOrderItems($conn, $orderId);
        $count = 0;

        foreach ($items as $item) {
            if (!empty($item['size_variant_id'])) {
                $stmt = $conn->prepare("UPDATE product_size_variants SET quantity = quantity + ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['size_variant_id']]);
                $count++;
            } elseif (!empty($item['product_id'])) {
                // Lógica para productos simples
                // $stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                // $stmt->execute([$item['quantity'], $item['product_id']]);
            }
        }

        return ['success' => true, 'message' => "Stock increased for $count items"];
    }

    /**
     * Obtiene los items de la orden.
     */
    private static function getOrderItems($conn, $orderId) {
        $stmt = $conn->prepare("SELECT product_id, size_variant_id, quantity FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
