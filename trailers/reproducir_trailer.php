<?php
require_once "../config/conexion.php";
define('BASE_PATH', '../');

$successMsg = $_SESSION['success'] ?? null;
$errorMsg = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT t.*, GROUP_CONCAT(g.nombre SEPARATOR ', ') as genero,
               d.nombre as director_nombre, d.apellidos as director_apellidos, d.id_director
        FROM trailers t
        LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
        LEFT JOIN generos g ON tg.id_genero = g.id_genero
        LEFT JOIN directores d ON t.id_director = d.id_director
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

// Obtener géneros del trailer actual
$genresList = [];
$sqlGenresCur = "SELECT id_genero FROM trailers_generos WHERE id_trailer = ?";
$stmtGenresCur = mysqli_prepare($conexion, $sqlGenresCur);
mysqli_stmt_bind_param($stmtGenresCur, "i", $id);
mysqli_stmt_execute($stmtGenresCur);
$resGenresCur = mysqli_stmt_get_result($stmtGenresCur);
while ($row = mysqli_fetch_assoc($resGenresCur)) {
    $genresList[] = (int)$row['id_genero'];
}
mysqli_stmt_close($stmtGenresCur);

// Obtener actores del trailer actual para calcular coincidencia de reparto
$actorsList = [];
$sqlActorsCur = "SELECT id_reparto FROM reparto_trailers WHERE id_trailer = ?";
$stmtActorsCur = mysqli_prepare($conexion, $sqlActorsCur);
mysqli_stmt_bind_param($stmtActorsCur, "i", $id);
mysqli_stmt_execute($stmtActorsCur);
$resActorsCur = mysqli_stmt_get_result($stmtActorsCur);
while ($row = mysqli_fetch_assoc($resActorsCur)) {
    $actorsList[] = (int)$row['id_reparto'];
}
mysqli_stmt_close($stmtActorsCur);

// Obtener películas recomendadas usando el algoritmo inteligente de puntuación
$recommendations = [];
$id_director = $trailer['id_director'];

$scoreTerms = [];
$bindParams = [];
$bindTypes = "";

// Puntos por mismo director (si tiene director asignado)
if ($id_director !== null) {
    $scoreTerms[] = "(CASE WHEN t.id_director = ? THEN 3 ELSE 0 END)";
    $bindParams[] = (int)$id_director;
    $bindTypes .= "i";
} else {
    $scoreTerms[] = "0";
}

$joins = [];

// Coincidencia de géneros: 1 punto por cada género en común
if (!empty($genresList)) {
    $genresPlaceholders = implode(",", array_fill(0, count($genresList), "?"));
    $joins[] = "LEFT JOIN (
        SELECT id_trailer, COUNT(*) as cnt 
        FROM trailers_generos 
        WHERE id_genero IN ($genresPlaceholders) 
        GROUP BY id_trailer
    ) genre_matches ON t.id_trailer = genre_matches.id_trailer";
    $scoreTerms[] = "COALESCE(genre_matches.cnt, 0) * 1";
    
    foreach ($genresList as $gId) {
        $bindParams[] = $gId;
        $bindTypes .= "i";
    }
}

// Coincidencia de reparto: 2 puntos por cada actor/actriz en común
if (!empty($actorsList)) {
    $actorsPlaceholders = implode(",", array_fill(0, count($actorsList), "?"));
    $joins[] = "LEFT JOIN (
        SELECT id_trailer, COUNT(*) as cnt 
        FROM reparto_trailers 
        WHERE id_reparto IN ($actorsPlaceholders) 
        GROUP BY id_trailer
    ) actor_matches ON t.id_trailer = actor_matches.id_trailer";
    $scoreTerms[] = "COALESCE(actor_matches.cnt, 0) * 2";
    
    foreach ($actorsList as $aId) {
        $bindParams[] = $aId;
        $bindTypes .= "i";
    }
}

$scoreExpr = implode(" + ", $scoreTerms);
$joinsSql = implode("\n", $joins);

$sqlRecs = "SELECT t.id_trailer, t.titulo, t.poster_url, t.valoracion, t.release_date, t.duracion,
                   GROUP_CONCAT(DISTINCT g.nombre SEPARATOR ', ') as genero,
                   CONCAT(d.nombre, ' ', d.apellidos) as director,
                   ($scoreExpr) as recommendation_score
            FROM trailers t
            LEFT JOIN directores d ON t.id_director = d.id_director
            LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
            LEFT JOIN generos g ON tg.id_genero = g.id_genero
            $joinsSql
            WHERE t.id_trailer != ?
            GROUP BY t.id_trailer
            ORDER BY recommendation_score DESC, t.valoracion DESC
            LIMIT 5";

$bindParams[] = $id; // Excluir la película actual
$bindTypes .= "i";

$stmtRecs = mysqli_prepare($conexion, $sqlRecs);
if ($stmtRecs) {
    if (!empty($bindParams)) {
        mysqli_stmt_bind_param($stmtRecs, $bindTypes, ...$bindParams);
    }
    mysqli_stmt_execute($stmtRecs);
    $resRecs = mysqli_stmt_get_result($stmtRecs);
    while ($row = mysqli_fetch_assoc($resRecs)) {
        $recommendations[] = $row;
    }
    mysqli_stmt_close($stmtRecs);
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
        <div class="player-toolbar">
            <button type="button" id="cinemaModeBtn" class="btn btn-secondary btn-cinema-mode">
                <i class="fa-solid fa-moon"></i> <span>Modo Cine</span>
            </button>
        </div>
        <div class="video-container">
            <iframe src="<?php echo htmlspecialchars($embedUrl); ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>

        <div class="info-container">
            <h2><?php echo htmlspecialchars($trailer['titulo']); ?></h2>
            <div class="info-meta">
                <span>Director: <strong>
                    <?php if (isset($trailer['id_director'])): ?>
                        <a href="director_peliculas.php?id=<?= $trailer['id_director'] ?>" class="director-link">
                            <?= htmlspecialchars($trailer['director_nombre'] . ' ' . $trailer['director_apellidos']); ?>
                        </a>
                    <?php else: ?>
                        No especificado
                    <?php endif; ?>
                </strong></span>
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

    <!-- Sección de Recomendaciones -->
    <?php if (!empty($recommendations)): ?>
        <section class="recommendations-section">
            <h2 class="section-title">Te puede interesar</h2>
            <div class="recommendations-grid">
                <?php foreach ($recommendations as $rec): ?>
                    <article class="movie-card">
                        <a class="movie-poster-container" href="reproducir_trailer.php?id=<?= $rec['id_trailer'] ?>">
                            <img src="<?= htmlspecialchars($rec['poster_url'] ?? 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=600') ?>" alt="<?= htmlspecialchars($rec['titulo']) ?>" class="movie-poster">

                            <div class="card-play-overlay">
                                <div class="play-icon-circle">
                                    <i class="fa-solid fa-play"></i>
                                </div>
                            </div>

                            <div class="rating-badge">
                                <i class="fa-solid fa-star"></i>
                                <span><?= htmlspecialchars((string)$rec['valoracion']) ?></span>
                            </div>

                            <div class="genre-badge">
                                <?= htmlspecialchars($rec['genero']) ?>
                            </div>
                        </a>

                        <div class="movie-info">
                            <h3 class="movie-title" style="font-size: 15px; margin-bottom: 6px;"><?= htmlspecialchars($rec['titulo']) ?></h3>

                            <div class="movie-meta-row" style="margin-bottom: 8px;">
                                <span><i class="fa-regular fa-calendar"></i> <?= date('Y', strtotime($rec['release_date'])) ?></span>
                                <span><i class="fa-regular fa-clock"></i> <?= htmlspecialchars((string)$rec['duracion']) ?> min</span>
                            </div>

                            <?php if (isset($rec['recommendation_score']) && $rec['recommendation_score'] > 0): ?>
                                <div class="recommendation-badge" style="font-size: 10px; font-weight: 700; color: var(--primary); background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.25); padding: 2px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; margin-bottom: 8px; width: fit-content;">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i> Recomendado
                                </div>
                            <?php endif; ?>

                            <div class="movie-actions" style="margin-top: auto; padding-top: 10px;">
                                <a class="btn btn-secondary" href="reproducir_trailer.php?id=<?= $rec['id_trailer'] ?>" style="width: 100%; justify-content: center; font-size: 12px; padding: 8px 12px;">
                                    <i class="fa-solid fa-play"></i> Ver Ficha
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    </main>

    <a class="volver" href="../index.php">← Volver al catálogo</a>

    <!-- Capa de fondo para el Modo Cine -->
    <div class="cinema-backdrop" id="cinemaBackdrop"></div>

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

            // Lógica del Modo Cine (Apagar Luces)
            const cinemaBtn = document.getElementById('cinemaModeBtn');
            const backdrop = document.getElementById('cinemaBackdrop');

            if (cinemaBtn && backdrop) {
                function toggleCinemaMode() {
                    const isActive = document.body.classList.toggle('cinema-mode-active');
                    if (isActive) {
                        cinemaBtn.innerHTML = '<i class="fa-solid fa-sun"></i> <span>Encender Luces</span>';
                    } else {
                        cinemaBtn.innerHTML = '<i class="fa-solid fa-moon"></i> <span>Modo Cine</span>';
                    }
                }

                cinemaBtn.addEventListener('click', toggleCinemaMode);
                backdrop.addEventListener('click', () => {
                    if (document.body.classList.contains('cinema-mode-active')) {
                        toggleCinemaMode();
                    }
                });

                // Permitir salir de modo cine con la tecla Escape
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && document.body.classList.contains('cinema-mode-active')) {
                        toggleCinemaMode();
                    }
                });
            }
        });
    </script>
<?php
require_once $rootPath . 'includes/footer.php';
?>
<?php
mysqli_stmt_close($stmt);
mysqli_close($conexion);
?>
