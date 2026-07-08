    <!-- Pie de página (Footer) -->
    <footer class="main-footer">
        <div class="app-container footer-content">
            <!-- Columna 1: Branding y Bio -->
            <div class="footer-col footer-about">
                <a href="<?php echo $rootPath; ?>index.php" class="brand m-0 mb-24">
                    <img src="<?php echo $rootPath; ?>images/logo movie trailer hub (1) (1).png" alt="Logo" class="brand-icon">
                    <span class="brand-name">Movie Trailer Hub</span>
                </a>
                <p class="footer-desc">
                    Tu rincón favorito para descubrir, guardar y disfrutar de los mejores trailers de tus películas favoritas. Tu hub cinematográfico centralizado.
                </p>
            </div>

            <!-- Columna 2: Enlaces Rápidos -->
            <div class="footer-col footer-links">
                <h4 class="footer-title">Enlaces Rápidos</h4>
                <ul>
                    <li><a href="<?php echo $rootPath; ?>index.php"><i class="fa-solid fa-house"></i> Inicio</a></li>
                    <li><a href="<?php echo $rootPath; ?>trailers/estadisticas.php"><i class="fa-solid fa-chart-simple"></i> Estadísticas</a></li>
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <li><a href="<?php echo $rootPath; ?>trailers/favoritos.php"><i class="fa-solid fa-heart"></i> Mis Favoritos</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo $rootPath; ?>trailers/ranking_trailers.php"><i class="fa-solid fa-star"></i> Ranking</a></li>
                </ul>
            </div>

            <!-- Columna 3: Redes y Contacto -->
            <div class="footer-col footer-social">
                <h4 class="footer-title">Síguenos</h4>
                <p class="footer-desc">Mantente al día con los últimos estrenos cinematográficos.</p>
                <div class="social-icons">
                    <a href="https://instagram.com" target="_blank" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="https://twitter.com" target="_blank" title="Twitter / X"><i class="fa-brands fa-twitter"></i></a>
                    <a href="https://youtube.com" target="_blank" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
                </div>
            </div>
        </div>

        <!-- Barra inferior de copyright y enlaces legales -->
        <div class="footer-bottom">
            <div class="app-container footer-bottom-content">
                <p class="copyright">&copy; <?php echo date('Y'); ?> Movie Trailer Hub. Todos los derechos reservados.</p>
                <div class="legal-links">
                    <a href="#">Privacidad</a>
                    <span class="separator">&bull;</span>
                    <a href="#">Términos</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
