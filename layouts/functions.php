  <?php




    // Función para obtener información completa del usuario con zona horaria de Colombia
    function getUserData($conn, $user_id)
    {
        try {
            // Consulta para obtener todos los datos relevantes del ussuario
            $stmt = $conn->prepare("
            SELECT 
                id, name, email, image, role, 
                CONVERT_TZ(created_at, '+00:00', '-05:00') as created_at,
                CONVERT_TZ(updated_at, '+00:00', '-05:00') as updated_at,
                CONVERT_TZ(last_access, '+00:00', '-05:00') as last_access
            FROM 
                users 
            WHERE 
                id = ?
            LIMIT 1
        ");

            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception("Usuario no encontrado");
            }

            return [
                'id' => $user['id'],
                'name' => $user['name'] ?? 'Usuario',
                'email' => $user['email'] ?? '',
                'image' => !empty($user['image']) ? 'uploads/users/' . $user['image'] : 'images/default-avatar.png',
                'role' => $user['role'] ?? 'customer',
                'created_at' => $user['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $user['updated_at'] ?? date('Y-m-d H:i:s'),
                'last_access' => $user['last_access'] ?? null
            ];
        } catch (PDOException $e) {
            error_log("Error de base de datos al obtener datos del usuario: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    // Uso de la función
    if (isset($_SESSION['user_id'])) {
        $userData = getUserData($conn, $_SESSION['user_id']);

        if ($userData === false) {
            // Manejar el error adecuadamente
            $userData = [
                'name' => 'Usuario',
                'email' => '',
                'image' => 'images/default-avatar.png',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        // Actualizar último acceso
        try {
            $stmt = $conn->prepare("UPDATE users SET last_access = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        } catch (PDOException $e) {
            error_log("Error al actualizar last_access: " . $e->getMessage());
        }
    } else {
        // Redirigir al login si no hay sesión
        header("Location: " . BASE_URL . "/users/formuser.php");
        exit();
    }


/**
 * Traducción genérica de estados de orden a español (para UI)
 */
if (!function_exists('getStatusText')) {
    function getStatusText($status) {
        $statuses = [
            'pending' => 'Pendiente',
            'processing' => 'En Proceso',
            'shipped' => 'Enviado',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
            'refunded' => 'Reembolsado',
            'partially_refunded' => 'Parcialmente Reembolsado'
        ];
        return $statuses[$status] ?? ucfirst($status);
    }
}

/**
 * Normaliza el path de la imagen del usuario para asegurar que tenga la carpeta `uploads/users/`
 * Si la ruta ya contiene `/` se devuelve tal cual (permite `images/default-avatar.png` o rutas completas).
 */
if (!function_exists('normalizeUserImagePath')) {
    function normalizeUserImagePath($image) {
        $image = trim((string)$image);
        if ($image === '') return 'images/default-avatar.png';
        // Si ya contiene una ruta o es un placeholder, devolver tal cual
        if (strpos($image, '/') !== false) return $image;
        // Sino, asumir que es solo el filename guardado en DB
        return 'uploads/users/' . $image;
    }
}

// Traducción/normalización de métodos de pago a etiquetas en español
if (!function_exists('translatePaymentMethod')) {
    function translatePaymentMethod($method) {
        $map = [
            'bank_transfer' => 'Transferencia Bancaria',
            'transfer' => 'Transferencia Bancaria',
            'transferencia' => 'Transferencia Bancaria',
            'transferencia_bancaria' => 'Transferencia Bancaria',
            'efectivo' => 'Efectivo',
            'cash' => 'Efectivo',
            'credit_card' => 'Tarjeta de Crédito',
            'debit_card' => 'Tarjeta de Débito',
            'paypal' => 'PayPal',
            'mercadopago' => 'Mercado Pago',
            'mercado_pago' => 'Mercado Pago',
            'oxxo' => 'OXXO',
            'pse' => 'PSE',
            'other' => 'Otro',
            'deposit' => 'Depósito',
            'stripe' => 'Stripe'
        ];

        $key = strtolower(trim((string) ($method ?? '')));
        if ($key === '') return 'No especificado';
        return $map[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }
}

if (!function_exists('getPaymentStatusText')) {
    function getPaymentStatusText($status) {
        $statuses = [
            'pending' => 'Pendiente',
            'paid' => 'Pagado',
            'failed' => 'Fallido',
            'refunded' => 'Reembolsado',
            'partially_refunded' => 'Parcialmente Reembolsado',
            'cancelled' => 'Cancelado'
        ];
        return $statuses[$status] ?? ucfirst($status);
    }
}

if (!function_exists('getRefundStatusText')) {
    function getRefundStatusText($status) {
        $map = [
            'pending' => 'Reembolso pendiente',
            'processing' => 'Reembolso en proceso',
            'refunded' => 'Reembolsado',
            'partially_refunded' => 'Parcialmente Reembolsado',
            'failed' => 'Reembolso fallido',
            'cancelled' => 'Cancelado'
        ];
        if (empty($status)) return $map['pending'];
        return $map[$status] ?? getPaymentStatusText($status);
    }
}

/**
 * Notificar a usuarios interesados cuando un producto tiene una promoción (compare_price)
 * Por defecto manda la notificación a usuarios que tengan el producto en su wishlist.
 * Si no hay usuarios en wishlist, por defecto envía a todos los usuarios con rol 'user' o 'customer'.
 */
if (!function_exists('notifyUsersOfProductPromotion')) {
    function notifyUsersOfProductPromotion($conn, $product_id, $title, $message)
    {
        try {
            // Buscar usuarios que tengan el producto en su wishlist
            $stmt = $conn->prepare("SELECT DISTINCT user_id FROM wishlist WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $users = array_map(function ($r) {
                return $r['user_id'];
            }, $rows);

            // Si no hay usuarios en wishlist, notificar a todos los clientes normales
            if (empty($users)) {
                $stmt = $conn->prepare("SELECT id FROM users WHERE role IN ('user','customer')");
                $stmt->execute();
                $users = array_map(function ($r) {
                    return $r['id'];
                }, $stmt->fetchAll(PDO::FETCH_ASSOC));
            }

            if (empty($users)) return false; // No hay usuarios registrados

            $notifyStmt = $conn->prepare("INSERT INTO notifications (user_id, type_id, title, message, related_entity_type, related_entity_id, is_read) VALUES (?, 3, ?, ?, 'promotion', ?, 0)");

            foreach ($users as $user_id) {
                try {
                    $notifyStmt->execute([$user_id, $title, $message, $product_id]);
                } catch (PDOException $e) {
                    // Ignorar errores individuales por usuario para no romper la operación
                    error_log('Error notificando usuario ' . $user_id . ': ' . $e->getMessage());
                }
            }

            return true;
        } catch (PDOException $e) {
            error_log('Error al notificar promociones: ' . $e->getMessage());
            return false;
        }
    }
}

 
    ?>