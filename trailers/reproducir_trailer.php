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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reproduciendo: <?php echo htmlspecialchars($trailer['titulo']); ?></title>
    <link rel="stylesheet" href="../css/estilos.css">
    <style>
        .player-wrapper {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: var(--shadow-md);
            margin: 0 auto;
            max-width: 900px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .video-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 */
            height: 0;
            overflow: hidden;
            border-radius: var(--radius-md);
            background: #000;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
        }
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        .info-container h2 {
            font-family: 'Montserrat', sans-serif;
            color: #ffffff;
            margin-bottom: 8px;
            text-align: left;
            font-size: 1.8rem;
            font-weight: 800;
        }
        .info-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 0.95rem;
            color: var(--text-muted);
            margin-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 12px;
        }
        .info-meta span strong {
            color: var(--text-main);
        }
        .info-synopsis {
            font-size: 1.05rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navegación principal -->
    <header class="navbar">
        <div class="app-container navbar-content">
            <a href="../index.php" class="brand">
                <i class="fa-solid fa-clapperboard brand-icon"></i>
                <h1 class="brand-name">Movie Trailer Hub</h1>
            </a>
            <div class="nav-actions">
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <span class="user-greeting" style="font-size: 14px; font-weight: 600; color: #ffffff; margin-right: 8px;">
                        <i class="fa-solid fa-circle-user" style="color: var(--primary); margin-right: 5px;"></i>Hola, <?= htmlspecialchars($_SESSION['username']) ?>
                    </span>
                    
                    <a href="favoritos.php" class="btn btn-secondary" style="border-color: rgba(220, 38, 38, 0.3); color: var(--secondary);">
                        <i class="fa-solid fa-heart"></i> Mis Favoritos
                    </a>

                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                        <a href="añadir_reparto.php" class="btn btn-secondary">
                            <i class="fa-solid fa-user-plus"></i> Añadir Actor
                        </a>
                        <a href="listar_trailers.php" class="btn btn-secondary">
                            <i class="fa-solid fa-list"></i> Administrar
                        </a>
                        <a href="añadir_trailer.php" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Añadir
                        </a>
                    <?php endif; ?>
                    
                    <a href="../auth/logout.php" class="btn btn-secondary">
                        <i class="fa-solid fa-right-from-bracket"></i> Salir
                    </a>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn btn-secondary">
                        <i class="fa-solid fa-right-to-bracket"></i> Iniciar Sesión
                    </a>
                    <a href="../auth/registro.php" class="btn btn-primary">
                        <i class="fa-solid fa-user-plus"></i> Registrarse
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="app-container" style="margin-top: 30px;">
        <h1>Reproductor de Trailers</h1>
        <p>Disfruta del trailer oficial de la película seleccionada.</p>

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
                <div style="margin-bottom: 20px;">
                    <?php if ($isTrailerFavorito): ?>
                        <a href="toggle_favorito.php?id=<?= $trailer['id_trailer'] ?>" class="btn btn-secondary" style="border-color: rgba(220, 38, 38, 0.3); color: var(--secondary); display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-heart"></i> Quitar de Favoritos
                        </a>
                    <?php else: ?>
                        <a href="toggle_favorito.php?id=<?= $trailer['id_trailer'] ?>" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fa-regular fa-heart"></i> Añadir a Favoritos
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="info-synopsis">
                <p><?php echo htmlspecialchars($trailer['sinopsis'] ?? 'Sin sinopsis o descripción disponible.'); ?></p>
            </div>

            <?php if (!empty($reparto)): ?>
                <div class="info-cast" style="border-top: 1px solid var(--border-color); padding-top: 20px; margin-top: 20px;">
                    <h3 style="font-family: var(--font-headline); font-size: 1.2rem; font-weight: 700; color: #ffffff; margin-bottom: 12px;">Reparto / Elenco</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px;">
                        <?php foreach ($reparto as $actor): ?>
                            <a href="actor_peliculas.php?id=<?php echo $actor['id_reparto']; ?>" style="display: flex; align-items: center; gap: 10px; padding: 8px; background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: var(--radius-md); transition: var(--transition-smooth);" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border-color)'">
                                <img src="<?php echo htmlspecialchars($actor['foto_url'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200'); ?>" alt="<?php echo htmlspecialchars($actor['nombre']); ?>" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(255,255,255,0.1);">
                                <div style="display: flex; flex-direction: column; overflow: hidden;">
                                    <span style="font-weight: 600; font-size: 13px; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($actor['nombre'] . ' ' . $actor['apellidos']); ?></span>
                                    <span style="font-size: 11px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($actor['personaje'] !== '' ? $actor['personaje'] : 'N/A'); ?></span>
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
