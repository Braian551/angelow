<?php
require_once __DIR__ . '/../settings/site_settings.php';
$siteSettings = fetch_site_settings($conn);
?>
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-column">
                <h3>Tienda</h3>
                <ul>
                    <li><a href="<?= BASE_URL ?>/ninas.html">Niñas</a></li>
                    <li><a href="<?= BASE_URL ?>/ninos.html">Niños</a></li>
                    <li><a href="<?= BASE_URL ?>/bebes.html">Bebés</a></li>
                    <li><a href="<?= BASE_URL ?>/novedades.html">Novedades</a></li>
                    <li><a href="<?= BASE_URL ?>/ofertas.html">Ofertas</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Información</h3>
                <ul>
                    <li><a href="<?= BASE_URL ?>/nosotros.html">Sobre nosotros</a></li>
                    <li><a href="<?= BASE_URL ?>/blog.html">Blog</a></li>
                    <li><a href="<?= BASE_URL ?>/contacto.html">Contacto</a></li>
                    <li><a href="<?= BASE_URL ?>/preguntas-frecuentes.html">Preguntas frecuentes</a></li>
                    <li><a href="<?= BASE_URL ?>/sostenibilidad.html">Sostenibilidad</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Ayuda</h3>
                <ul>
                    <li><a href="<?= BASE_URL ?>/guias-tallas.html">Guía de tallas</a></li>
                    <li><a href="<?= BASE_URL ?>/envios.html">Envíos y entregas</a></li>
                    <li><a href="<?= BASE_URL ?>/devoluciones.html">Devoluciones</a></li>
                    <li><a href="<?= BASE_URL ?>/terminos.html">Términos y condiciones</a></li>
                    <li><a href="<?= BASE_URL ?>/privacidad.html">Política de privacidad</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Contacto</h3>
                <address>
                    <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($siteSettings['support_address'] ?? 'Medellin, Colombia') ?></p>
                    <p><i class="fas fa-phone"></i> <?= htmlspecialchars($siteSettings['support_phone'] ?? '+57 300 000 0000') ?></p>
                    <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($siteSettings['support_email'] ?? 'soporte@angelow.com') ?></p>
                </address>
                <div class="social-links">
                    <?php if (!empty($siteSettings['social_facebook'])): ?>
                        <a href="<?= htmlspecialchars($siteSettings['social_facebook']) ?>" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($siteSettings['social_instagram'])): ?>
                        <a href="<?= htmlspecialchars($siteSettings['social_instagram']) ?>" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($siteSettings['social_tiktok'])): ?>
                        <a href="<?= htmlspecialchars($siteSettings['social_tiktok']) ?>" target="_blank" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($siteSettings['social_whatsapp'])): ?>
                        <a href="<?= htmlspecialchars($siteSettings['social_whatsapp']) ?>" target="_blank" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p class="copyright">&copy; <?= date('Y') ?> <?= htmlspecialchars($siteSettings['store_name'] ?? 'Angelow') ?>. Todos los derechos reservados.</p>
        </div>
    </footer>