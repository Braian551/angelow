<?php
   // Obtener la URL actual sin parámetros GET
    $current_url = strtok($_SERVER['REQUEST_URI'], '?');
    $current_url = str_replace(BASE_URL, '', $current_url);

    // Definir las URLs del menú
    $menu_items = [
        'dashboard' => '/users/dashboarduser.php',
        'orders' => '/users/orders.php',
        'notifications' => '/users/notifications.php',
        'addresses' => '/users/addresses.php',
        'wishlist' => '/users/wishlist.php',
        'settings' => '/users/settings.php'
    ];

    // Función para verificar si el ítem del menú está activo
    function isMenuItemActive($item_url, $current_url)
    {
        return strpos($current_url, $item_url) !== false;
    }
?>
  <aside class="user-sidebar">
      <div class="user-profile-summary">
          <a href="<?= BASE_URL ?>" class="back-button" style="position: absolute; left: 20px; top: 20px;" onclick="event.preventDefault(); if (document.referrer && document.referrer !== window.location.href) { history.back(); } else { window.location.href = '<?= BASE_URL ?>'; }">
              <i class="fas fa-arrow-left"></i>
          </a>
          <noscript>
              <a href="<?= BASE_URL ?>" class="back-button" style="position: absolute; left: 20px; top: 20px;">
                  <i class="fas fa-arrow-left"></i>
              </a>
          </noscript>
          <div class="user-avatar">
              <img src="<?= BASE_URL ?>/<?php echo !empty($userData['image']) ? htmlspecialchars($userData['image']) : 'images/default-avatar.png'; ?>" alt="Foto de perfil">
          </div>
          <div class="user-info">
              <h3><?php echo htmlspecialchars($userData['name'] ?? 'Usuario'); ?></h3>
              <p><?php echo htmlspecialchars($userData['email'] ?? ''); ?></p>
              <p>Miembro desde <?php echo date('M Y', strtotime($userData['created_at'] ?? 'now')); ?></p>
          </div>
      </div>

      <nav class="user-menu">
          <ul>
              <li class="<?= isMenuItemActive($menu_items['dashboard'], $current_url) ? 'active' : '' ?>">
                  <a href="<?= BASE_URL ?><?= $menu_items['dashboard'] ?>">
                      <i class="fas fa-tachometer-alt"></i> Resumen
                  </a>
              </li>
              <li class="<?= isMenuItemActive($menu_items['orders'], $current_url) ? 'active' : '' ?>">
                  <a href="<?= BASE_URL ?><?= $menu_items['orders'] ?>">
                      <i class="fas fa-shopping-bag"></i> Mis Pedidos
                  </a>
              </li>
              <li class="<?= isMenuItemActive($menu_items['notifications'], $current_url) ? 'active' : '' ?>">
                  <a href="<?= BASE_URL ?><?= $menu_items['notifications'] ?>">
                     <i class="fas fa-bell"></i> Notificaciones
                  </a>
              </li>
              <li class="<?= isMenuItemActive($menu_items['addresses'], $current_url) ? 'active' : '' ?>">
                  <a href="<?= BASE_URL ?><?= $menu_items['addresses'] ?>">
                      <i class="fas fa-map-marker-alt"></i> Direcciones
                  </a>
              </li>
              <li class="<?= isMenuItemActive($menu_items['wishlist'], $current_url) ? 'active' : '' ?>">
                  <a href="<?= BASE_URL ?><?= $menu_items['wishlist'] ?>">
                      <i class="fas fa-heart"></i> Favoritos
                  </a>
              </li>
             
              <li class="<?= isMenuItemActive($menu_items['settings'], $current_url) ? 'active' : '' ?>">
                  <a href="<?= BASE_URL ?><?= $menu_items['settings'] ?>">
                      <i class="fas fa-user-cog"></i> Configuración
                  </a>
              </li>
              <li>
                  <a href="<?= BASE_URL ?>/auth/logout.php">
                      <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                  </a>
              </li>
          </ul>
      </nav>
  </aside>

  <?php
// Obtener datos actualizados del usuario



  ?>