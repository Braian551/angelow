<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../layouts/headerproducts.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
      <link rel="stylesheet" href="<?= BASE_URL ?>/error/css/404.css">
</head>
<body>
    <div class="error-container">
        <h1>404 - Página no encontrada</h1>
        <p>Lo sentimos, la página que estás buscando no existe.</p>
        <a href="<?= BASE_URL ?>" class="btn">Volver al inicio</a>
           
    </div>

    
    <?php includeFromRoot('layouts/footer.php'); ?>
</body>
</html>