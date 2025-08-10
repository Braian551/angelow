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


 
    ?>