<?php
require_once "../config/conexion.php";
define('BASE_PATH', '../');

$successMsg = $_SESSION['success'] ?? null;
$errorMsg = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT t.*, GROUP_CONCAT(g.nombre SEPARATOR ', ') as genero
        FROM trailers t
        LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
        LEFT JOIN generos g ON tg.id_genero = g.id_genero
        WHERE t.id_trailer = ?
        GROUP BY t.id_trailer
        LIMIT 1";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$trailer = mysqli_fetch_assoc($resultado);

if (!$trailer) {
    echo "<h1>Trailer no encontrado</h1>";
    exit;
}

// Registrar la visualización en la base de datos
$id_usuario_view = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
$ip_direccion = $_SERVER['REMOTE_ADDR'] ?? null;
$dispositivo = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 100) : null;

$sqlView = "INSERT INTO visualizaciones (id_trailer, id_usuario, ip_direccion, dispositivo) VALUES (?, ?, ?, ?)";
$stmtView = mysqli_prepare($conexion, $sqlView);
mysqli_stmt_bind_param($stmtView, "iiss", $id, $id_usuario_view, $ip_direccion, $dispositivo);
mysqli_stmt_execute($stmtView);
mysqli_stmt_close($stmtView);

// Convertir URL a embed
function getEmbedUrl(string $url): string {
    // YouTube
    $regExp = '/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/';
    if (preg_match($regExp, $url, $match)) {
        if (isset($match[2]) && strlen($match[2]) === 11) {
            return "https://www.youtube.com/embed/" . $match[2] . "?autoplay=1&rel=0";
        }
    }
    
    // Vimeo
    $regExpVimeo = '/vimeo\.com\/([0-9]+)/';
    if (preg_match($regExpVimeo, $url, $match)) {
        return "https://player.vimeo.com/video/" . $match[1] . "?autoplay=1";
    }

    return $url;
}

$embedUrl = getEmbedUrl($trailer['trailer_url']);

// Consultar reparto asociado
$sqlReparto = "SELECT r.*, rt.personaje 
               FROM reparto_trailers rt 
               JOIN reparto r ON rt.id_reparto = r.id_reparto 
               WHERE rt.id_trailer = ?";
$stmtReparto = mysqli_prepare($conexion, $sqlReparto);
mysqli_stmt_bind_param($stmtReparto, "i", $id);
mysqli_stmt_execute($stmtReparto);
$resReparto = mysqli_stmt_get_result($stmtReparto);
$reparto = [];
while ($row = mysqli_fetch_assoc($resReparto)) {
    $reparto[] = $row;
}
mysqli_stmt_close($stmtReparto);

$isTrailerFavorito = false;
if (isset($_SESSION['usuario_id'])) {
    $id_usuario = $_SESSION['usuario_id'];
    $sqlFav = "SELECT 1 FROM favoritos WHERE id_usuario = ? AND id_trailer = ? LIMIT 1";
    $stmtFav = mysqli_prepare($conexion, $sqlFav);
    mysqli_stmt_bind_param($stmtFav, "ii", $id_usuario, $id);
    mysqli_stmt_execute($stmtFav);
    $resFav = mysqli_stmt_get_result($stmtFav);
    $isTrailerFavorito = mysqli_num_rows($resFav) > 0;
    mysqli_stmt_close($stmtFav);
}
?>
<?php
$pageTitle = "Reproduciendo: " . $trailer['titulo'];
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>

    <main class="app-container">
        <div class="reproducer-header">
            <h1>Reproductor de Trailers</h1>
            <p>Disfruta del trailer oficial de la película seleccionada.</p>
        </div>

    <div class="player-wrapper">
        <div class="video-container">
            <iframe src="<?php echo htmlspecialchars($embedUrl); ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>

        <div class="info-container">
            <h2><?php echo htmlspecialchars($trailer['titulo']); ?></h2>
            <div class="info-meta">
                <span>Director: <strong><?php echo htmlspecialchars($trailer['director'] ?? 'N/A'); ?></strong></span>
                <span>Fecha de Estreno: <strong><?php echo date('d/m/Y', strtotime($trailer['release_date'])); ?></strong></span>
                <span>Género: <strong><?php echo htmlspecialchars($trailer['genero']); ?></strong></span>
                <span>Duración: <strong><?php echo htmlspecialchars((string)$trailer['duracion']); ?> min</strong></span>
                <span>Valoración: <strong>⭐ <?php echo htmlspecialchars((string)$trailer['valoracion']); ?>/10</strong></span>
            </div>
            
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <div class="text-center mb-24">
                    <?php if ($isTrailerFavorito): ?>
                        <a href="toggle_favorito.php?id=<?= $trailer['id_trailer'] ?>" class="btn btn-secondary btn-active-favorito-reproductor">
                            <i class="fa-solid fa-heart"></i> Quitar de Favoritos
                        </a>
                    <?php else: ?>
                        <a href="toggle_favorito.php?id=<?= $trailer['id_trailer'] ?>" class="btn btn-secondary btn-inline-flex">
                            <i class="fa-regular fa-heart"></i> Añadir a Favoritos
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="info-synopsis">
                <p><?php echo htmlspecialchars($trailer['sinopsis'] ?? 'Sin sinopsis o descripción disponible.'); ?></p>
            </div>

            <?php if (!empty($reparto)): ?>
                <div class="info-cast">
                    <h3 class="info-cast-title">Reparto / Elenco</h3>
                    <div class="cast-grid">
                        <?php foreach ($reparto as $actor): ?>
                            <a href="actor_peliculas.php?id=<?php echo $actor['id_reparto']; ?>" class="actor-card">
                                <img src="<?php echo htmlspecialchars($actor['foto_url'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200'); ?>" alt="<?php echo htmlspecialchars($actor['nombre']); ?>">
                                <div class="actor-card-info">
                                    <span class="actor-card-name"><?php echo htmlspecialchars($actor['nombre'] . ' ' . $actor['apellidos']); ?></span>
                                    <span class="actor-card-role"><?php echo htmlspecialchars($actor['personaje'] !== '' ? $actor['personaje'] : 'N/A'); ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    </main>

    <a class="volver" href="../index.php">← Volver al catálogo</a>

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
</body>
</html>
<?php
mysqli_stmt_close($stmt);
mysqli_close($conexion);
?>
