<?php
require_once "../config/conexion.php";
define('BASE_PATH', '../');

$successMsg = $_SESSION['success'] ?? null;
$errorMsg = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['error'] = "Debes iniciar sesión para acceder a tus favoritos.";
    header("Location: ../auth/login.php");
    exit;
}

$id_usuario = $_SESSION['usuario_id'];

$sql = "SELECT t.*, GROUP_CONCAT(g.nombre SEPARATOR ', ') as genero,
               CONCAT(d.nombre, ' ', d.apellidos) as director
        FROM favoritos f
        JOIN trailers t ON f.id_trailer = t.id_trailer
        LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
        LEFT JOIN generos g ON tg.id_genero = g.id_genero
        LEFT JOIN directores d ON t.id_director = d.id_director
        WHERE f.id_usuario = ?
        GROUP BY t.id_trailer
        ORDER BY t.release_date DESC";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$trailers = [];
while ($row = mysqli_fetch_assoc($res)) {
    $trailers[] = $row;
}
mysqli_stmt_close($stmt);
mysqli_close($conexion);
?>
<?php
$pageTitle = "Mis Favoritos - Movie Trailer Hub";
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>

    <main class="app-container">
        
        <div class="mb-24">
            <h2 class="section-title m-0">Mis Películas Favoritas</h2>
            <p class="text-muted-helper">Aquí se muestran los trailers que has guardado en tu cuenta.</p>
        </div>

        <?php if (!empty($trailers)): ?>
            <section class="trailers-grid">
                <?php foreach ($trailers as $trailer): ?>
                    <article class="movie-card">
                        
                        <a class="movie-poster-container" href="reproducir_trailer.php?id=<?= $trailer['id_trailer'] ?>">
                            <img src="<?= htmlspecialchars($trailer['poster_url'] ?? 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=600') ?>" alt="<?= htmlspecialchars($trailer['titulo']) ?>" class="movie-poster">
                            
                            <div class="card-play-overlay">
                                <div class="play-icon-circle">
                                    <i class="fa-solid fa-play"></i>
                                </div>
                            </div>

                            <div class="rating-badge">
                                <i class="fa-solid fa-star"></i>
                                <span><?= htmlspecialchars((string)$trailer['valoracion']) ?></span>
                            </div>

                            <div class="genre-badge">
                                <?= htmlspecialchars($trailer['genero']) ?>
                            </div>
                        </a>

                        <div class="movie-info">
                            <h3 class="movie-title"><?= htmlspecialchars($trailer['titulo']) ?></h3>
                            
                            <div class="movie-meta-row">
                                <span><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y', strtotime($trailer['release_date'])) ?></span>
                                <span><i class="fa-regular fa-clock"></i> <?= htmlspecialchars((string)$trailer['duracion']) ?> min</span>
                                <span><i class="fa-solid fa-user-tie"></i> <?= htmlspecialchars($trailer['director'] ?? 'N/A') ?></span>
                            </div>

                            <p class="movie-description"><?= htmlspecialchars($trailer['sinopsis'] ?? 'Sin sinopsis.') ?></p>
                            
                            <div class="movie-actions">
                                <a class="btn btn-secondary" href="reproducir_trailer.php?id=<?= $trailer['id_trailer'] ?>">
                                    <i class="fa-solid fa-play"></i> Ver
                                </a>
                                
                                <a class="favorite-heart-btn" href="toggle_favorito.php?id=<?= $trailer['id_trailer'] ?>" title="Quitar de favoritos">
                                    <i class="fa-solid fa-heart"></i>
                                </a>

                                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                                    <a class="btn btn-secondary btn-modificar" href="modificar_trailer.php?id=<?= $trailer['id_trailer'] ?>">
                                        <i class="fa-solid fa-pen-to-square"></i> Editar
                                    </a>
                                    <a class="btn btn-danger btn-eliminar" href="eliminar_trailer.php?id=<?= $trailer['id_trailer'] ?>" onclick="return confirm('¿Estás seguro de que deseas eliminar este trailer?');">
                                        <i class="fa-solid fa-trash"></i> Borrar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-heart-crack empty-icon empty-icon-secondary"></i>
                <h3 class="empty-title">Aún no tienes favoritos</h3>
                <p>Navega al inicio de la página y haz clic en el ícono del corazón de cualquier película para añadirla aquí.</p>
                <a href="../index.php" class="btn btn-primary btn-empty-back">
                    <i class="fa-solid fa-arrow-left"></i> Explorar Catálogo
                </a>
            </div>
        <?php endif; ?>

    </main>

    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer">
        <?php if ($successMsg): ?>
            <div class="toast toast-success" id="successToast">
                <i class="fa-solid fa-circle-check toast-icon"></i>
                <div class="toast-message"><?= htmlspecialchars($successMsg) ?></div>
                <button class="toast-close" onclick="closeToast('successToast')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
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

        document.addEventListener('DOMContentLoaded', () => {
            const toasts = document.querySelectorAll('.toast');
            toasts.forEach((toast) => {
                setTimeout(() => {
                    toast.classList.add('show');
                }, 100);

                setTimeout(() => {
                    closeToast(toast.id);
                }, 4000);
            });
        });
    </script>
<?php
require_once $rootPath . 'includes/footer.php';
?>
