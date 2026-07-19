<?php
if (isset($_SESSION['usuario_id'])) {
    global $conexion;
    $is_closed = false;
    try {
        if (!isset($conexion) || !($conexion instanceof mysqli) || !@mysqli_ping($conexion)) {
            $is_closed = true;
        }
    } catch (Error $e) {
        $is_closed = true;
    }

    if ($is_closed) {
        if (defined('DB_HOST')) {
            $conexion = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conexion) {
                mysqli_set_charset($conexion, "utf8mb4");
                mysqli_query($conexion, "SET time_zone = '+02:00'");
            }
        }
    }

    $is_active = false;
    try {
        if (isset($conexion) && $conexion instanceof mysqli && @mysqli_ping($conexion)) {
            $is_active = true;
        }
    } catch (Error $e) {
        $is_active = false;
    }

    if ($is_active) {
        require_once __DIR__ . '/../badges/gamificacion_helper.php';
        procesar_badges_si_corresponde($conexion, (int) $_SESSION['usuario_id']);
    }
}
?>
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

    <!-- Contenedor Global de Toasts -->
    <div class="toast-container" id="toastContainer">
        <?php if (isset($successMsg) && $successMsg): ?>
            <div class="toast toast-success" id="successToast">
                <i class="fa-solid fa-circle-check toast-icon"></i>
                <div class="toast-message"><?= htmlspecialchars($successMsg) ?></div>
                <button class="toast-close" onclick="closeToast('successToast')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php endif; ?>
        <?php if (isset($errorMsg) && $errorMsg): ?>
            <div class="toast toast-error" id="errorToast">
                <i class="fa-solid fa-circle-exclamation toast-icon"></i>
                <div class="toast-message"><?= htmlspecialchars($errorMsg) ?></div>
                <button class="toast-close" onclick="closeToast('errorToast')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function closeToast(id) {
        const toast = document.getElementById(id);
        if (toast) {
            toast.classList.remove('show');
            toast.classList.add('hide');
            setTimeout(() => {
                toast.remove();
            }, 400);
        }
    }

    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        
        const id = 'toast-' + Math.random().toString(36).substr(2, 9);
        const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
        const toastClass = type === 'success' ? 'toast-success' : 'toast-error';
        
        const toast = document.createElement('div');
        toast.className = `toast ${toastClass}`;
        toast.id = id;

        const iconNode = document.createElement('i');
        iconNode.className = `fa-solid ${icon} toast-icon`;

        const messageNode = document.createElement('div');
        messageNode.className = 'toast-message';
        messageNode.textContent = String(message ?? '');

        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'toast-close';
        closeButton.addEventListener('click', () => closeToast(id));

        const closeIcon = document.createElement('i');
        closeIcon.className = 'fa-solid fa-xmark';
        closeButton.appendChild(closeIcon);

        toast.append(iconNode, messageNode, closeButton);
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 50);
        
        setTimeout(() => {
            closeToast(id);
        }, 4000);
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Animar Toasts autogenerados por PHP
        const toasts = document.querySelectorAll('.toast');
        toasts.forEach((toast) => {
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);

            setTimeout(() => {
                closeToast(toast.id);
            }, 4000);
        });

        // Mostrar notificaciones de insignias desbloqueadas en esta carga de página
        <?php if (isset($_SESSION['nuevos_logros_desbloqueados']) && !empty($_SESSION['nuevos_logros_desbloqueados'])): ?>
            <?php foreach ($_SESSION['nuevos_logros_desbloqueados'] as $logro): ?>
                <?php
                $mensajeLogro = '🏆 ¡Logro desbloqueado: '
                    . (string) ($logro['nombre'] ?? '')
                    . '! - '
                    . (string) ($logro['descripcion'] ?? '');
                ?>
                showToast(<?= json_encode($mensajeLogro, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, 'success');
            <?php endforeach; ?>
            <?php unset($_SESSION['nuevos_logros_desbloqueados']); ?>
        <?php endif; ?>

        // Manejador Asíncrono Global de Favoritos
        document.addEventListener('submit', function(e) {
            const form = e.target.closest('.favorite-toggle-form');
            if (form) {
                e.preventDefault();
                const btn = form.querySelector('.btn-toggle-favorito, .btn-toggle-favorito-detail, .favorite-heart-btn');
                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': formData.get('csrf_token'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Caso A: Corazón del catálogo
                        if (btn.classList.contains('btn-toggle-favorito')) {
                            if (data.isFavorito) {
                                btn.classList.add('btn-active-favorito');
                                btn.title = "Quitar de favoritos";
                                btn.innerHTML = '<i class="fa-solid fa-heart"></i>';
                            } else {
                                btn.classList.remove('btn-active-favorito');
                                btn.title = "Añadir a favoritos";
                                btn.innerHTML = '<i class="fa-regular fa-heart"></i>';
                            }
                        }
                        // Caso B: Botón del reproductor
                        else if (btn.classList.contains('btn-toggle-favorito-detail')) {
                            if (data.isFavorito) {
                                btn.className = "btn btn-secondary btn-toggle-favorito-detail btn-active-favorito-reproductor";
                                btn.innerHTML = '<i class="fa-solid fa-heart"></i> Quitar de Favoritos';
                            } else {
                                btn.className = "btn btn-secondary btn-toggle-favorito-detail btn-inline-flex";
                                btn.innerHTML = '<i class="fa-regular fa-heart"></i> Añadir a Favoritos';
                            }
                        }
                        // Caso C: Corazón en la página de favoritos.php
                        else if (btn.classList.contains('favorite-heart-btn')) {
                            const card = btn.closest('article.movie-card');
                            if (card) {
                                card.style.transition = 'all 0.3s ease';
                                card.style.opacity = '0';
                                card.style.transform = 'scale(0.9)';
                                setTimeout(() => {
                                    card.remove();
                                    const remaining = document.querySelectorAll('article.movie-card');
                                    if (remaining.length === 0) {
                                        window.location.reload();
                                    }
                                }, 300);
                            }
                        }
                        
                        showToast(data.message, 'success');

                        // Notificar logros desbloqueados por favoritos en AJAX
                        if (data.nuevos_logros && data.nuevos_logros.length > 0) {
                            data.nuevos_logros.forEach(logro => {
                                showToast(`🏆 ¡Logro desbloqueado: ${logro.nombre}! - ${logro.descripcion}`, 'success');
                            });
                        }
                    } else if (data.error) {
                        showToast(data.error, 'error');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    showToast('Error de conexión al actualizar favoritos.', 'error');
                });
            }
        });
    });
    </script>
</body>
</html>
