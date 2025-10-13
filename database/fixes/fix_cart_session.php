<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['migrate'])) {
    try {
        $current_session_id = session_id();
        $user_id = $_SESSION['user_id'] ?? null;
        
        // Encontrar el carrito más reciente con items
        $stmt = $conn->query("
            SELECT 
                c.id,
                c.user_id,
                c.session_id,
                COUNT(ci.id) as item_count
            FROM carts c
            INNER JOIN cart_items ci ON c.id = ci.cart_id
            GROUP BY c.id
            ORDER BY c.created_at DESC
            LIMIT 1
        ");
        
        $sourceCart = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sourceCart) {
            // Actualizar el carrito para usar la sesión actual
            $stmt = $conn->prepare("
                UPDATE carts 
                SET session_id = :new_session_id,
                    user_id = :user_id,
                    updated_at = NOW()
                WHERE id = :cart_id
            ");
            
            $stmt->execute([
                ':new_session_id' => $user_id ? null : $current_session_id,
                ':user_id' => $user_id,
                ':cart_id' => $sourceCart['id']
            ]);
            
            $message = "✓ Carrito migrado exitosamente! Se encontraron {$sourceCart['item_count']} items.";
            $messageType = 'success';
        } else {
            $message = "No hay carritos con items para migrar.";
            $messageType = 'info';
        }
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Obtener información actual
$current_session_id = session_id();
$user_id = $_SESSION['user_id'] ?? null;

// Obtener carritos con items
$stmt = $conn->query("
    SELECT 
        c.id,
        c.user_id,
        c.session_id,
        c.created_at,
        COUNT(ci.id) as item_count
    FROM carts c
    LEFT JOIN cart_items ci ON c.id = ci.cart_id
    GROUP BY c.id
    HAVING item_count > 0
    ORDER BY c.created_at DESC
");

$carts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verificar carrito actual
$cartQuery = "SELECT c.id FROM carts c WHERE ";
if ($user_id) {
    $cartQuery .= "c.user_id = :user_id";
    $params = [':user_id' => $user_id];
} else {
    $cartQuery .= "c.session_id = :session_id AND c.user_id IS NULL";
    $params = [':session_id' => $current_session_id];
}
$cartQuery .= " ORDER BY c.created_at DESC LIMIT 1";

$stmt = $conn->prepare($cartQuery);
$stmt->execute($params);
$currentCart = $stmt->fetch(PDO::FETCH_ASSOC);

$currentCartItemCount = 0;
if ($currentCart) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart_items WHERE cart_id = :cart_id");
    $stmt->execute([':cart_id' => $currentCart['id']]);
    $currentCartItemCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrar Carrito - Angelow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        
        .info-box {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .info-box.current {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
        }
        
        .info-box.warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        
        .info-box.success {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        
        .info-box h3 {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-box p {
            margin: 5px 0;
            color: #424242;
        }
        
        .carts-list {
            margin-top: 20px;
        }
        
        .cart-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .cart-info {
            flex: 1;
        }
        
        .cart-info strong {
            color: #333;
            display: block;
            margin-bottom: 5px;
        }
        
        .cart-info small {
            color: #666;
            display: block;
        }
        
        .cart-badge {
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-shopping-cart"></i> Gestión de Carrito</h1>
        
        <div class="card">
            <?php if ($message): ?>
                <div class="message <?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box current">
                <h3><i class="fas fa-info-circle"></i> Tu Sesión Actual</h3>
                <p><strong>Session ID:</strong> <code><?= htmlspecialchars($current_session_id) ?></code></p>
                <p><strong>User ID:</strong> <?= $user_id ? htmlspecialchars($user_id) : '<em>No logueado</em>' ?></p>
                <p><strong>Items en tu carrito:</strong> <span class="cart-badge"><?= $currentCartItemCount ?></span></p>
            </div>
            
            <?php if (count($carts) > 0): ?>
                <div class="info-box <?= $currentCartItemCount == 0 ? 'warning' : 'success' ?>">
                    <h3>
                        <i class="fas fa-<?= $currentCartItemCount == 0 ? 'exclamation-triangle' : 'check-circle' ?>"></i>
                        Estado del Carrito
                    </h3>
                    <?php if ($currentCartItemCount == 0): ?>
                        <p>Tu sesión actual no tiene items, pero hay <?= count($carts) ?> carrito(s) con items en la base de datos.</p>
                        <p><strong>Solución:</strong> Haz clic en "Migrar Carrito" para vincular los items a tu sesión actual.</p>
                    <?php else: ?>
                        <p>✓ Tu carrito está funcionando correctamente con <?= $currentCartItemCount ?> item(s).</p>
                    <?php endif; ?>
                </div>
                
                <h3 style="margin-top: 30px; margin-bottom: 15px;">Carritos Disponibles:</h3>
                <div class="carts-list">
                    <?php foreach ($carts as $cart): ?>
                        <div class="cart-item">
                            <div class="cart-info">
                                <strong>Carrito #<?= $cart['id'] ?></strong>
                                <small>
                                    User ID: <?= $cart['user_id'] ?? '<em>NULL</em>' ?> | 
                                    Session: <?= $cart['session_id'] ? substr($cart['session_id'], 0, 20) . '...' : '<em>NULL</em>' ?>
                                </small>
                                <small>Creado: <?= $cart['created_at'] ?></small>
                            </div>
                            <div class="cart-badge"><?= $cart['item_count'] ?> items</div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($currentCartItemCount == 0): ?>
                    <form method="POST">
                        <button type="submit" name="migrate">
                            <i class="fas fa-sync-alt"></i>
                            Migrar Carrito a Mi Sesión
                        </button>
                    </form>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="info-box warning">
                    <h3><i class="fas fa-shopping-basket"></i> No hay carritos con items</h3>
                    <p>No se encontraron carritos con productos en la base de datos.</p>
                    <p>Puedes agregar productos desde la tienda.</p>
                </div>
            <?php endif; ?>
            
            <div class="action-buttons">
                <a href="<?= BASE_URL ?>/tienda/cart.php" style="text-decoration: none;">
                    <button type="button" class="btn-secondary">
                        <i class="fas fa-shopping-cart"></i>
                        Ver Mi Carrito
                    </button>
                </a>
                <a href="<?= BASE_URL ?>/add_to_cart_test.php" style="text-decoration: none;">
                    <button type="button">
                        <i class="fas fa-plus-circle"></i>
                        Agregar Productos
                    </button>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
