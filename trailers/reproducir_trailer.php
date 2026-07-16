<?php
require_once "../config/conexion.php";
require_once __DIR__ . "/../includes/seguridad.php";
define('BASE_PATH', '../');

// Auto-migración: Crear tabla de reseñas si no existe
$sqlMigrateResenas = "CREATE TABLE IF NOT EXISTS resenas (
    id_resena INT AUTO_INCREMENT PRIMARY KEY,
    id_trailer INT NOT NULL,
    id_usuario INT NOT NULL,
    valoracion INT NOT NULL,
    comentario TEXT DEFAULT NULL,
    fecha_alta DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_trailer_usuario (id_trailer, id_usuario),
    CONSTRAINT fk_resenas_trailers FOREIGN KEY (id_trailer) REFERENCES trailers(id_trailer) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_resenas_usuarios FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
mysqli_query($conexion, $sqlMigrateResenas);

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

// Procesar el envío de una nueva reseña o actualización/eliminación de una existente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_login("reproducir_trailer.php?id=" . $id, "Debes iniciar sesión para publicar o modificar una reseña.");
    
    $id_usuario = (int)$_SESSION['usuario_id'];
    
    if ($_POST['action'] === 'guardar_resena') {
        $valoracion = isset($_POST['valoracion']) ? (int)$_POST['valoracion'] : 0;
        $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';
        
        if ($valoracion < 1 || $valoracion > 5) {
            $_SESSION['error'] = "Por favor selecciona una valoración entre 1 y 5 estrellas.";
            header("Location: reproducir_trailer.php?id=" . $id);
            exit;
        }
        
        $sqlSave = "INSERT INTO resenas (id_trailer, id_usuario, valoracion, comentario, fecha_alta) 
                    VALUES (?, ?, ?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE valoracion = VALUES(valoracion), comentario = VALUES(comentario), fecha_alta = NOW()";
        $stmtSave = mysqli_prepare($conexion, $sqlSave);
        if ($stmtSave) {
            mysqli_stmt_bind_param($stmtSave, "iiis", $id, $id_usuario, $valoracion, $comentario);
            if (mysqli_stmt_execute($stmtSave)) {
                $_SESSION['success'] = "¡Tu reseña ha sido guardada con éxito!";
            } else {
                $_SESSION['error'] = "Error al guardar la reseña. Inténtalo de nuevo.";
            }
            mysqli_stmt_close($stmtSave);
        } else {
            $_SESSION['error'] = "Error en el servidor al procesar la reseña.";
        }
    } elseif ($_POST['action'] === 'eliminar_resena') {
        $sqlDel = "DELETE FROM resenas WHERE id_trailer = ? AND id_usuario = ?";
        $stmtDel = mysqli_prepare($conexion, $sqlDel);
        if ($stmtDel) {
            mysqli_stmt_bind_param($stmtDel, "ii", $id, $id_usuario);
            if (mysqli_stmt_execute($stmtDel)) {
                $_SESSION['success'] = "Tu reseña ha sido eliminada.";
            } else {
                $_SESSION['error'] = "Error al eliminar la reseña.";
            }
            mysqli_stmt_close($stmtDel);
        } else {
            $_SESSION['error'] = "Error en el servidor al eliminar la reseña.";
        }
    }
    
    header("Location: reproducir_trailer.php?id=" . $id);
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
if ($stmtView) {
    mysqli_stmt_bind_param($stmtView, "iiss", $id, $id_usuario_view, $ip_direccion, $dispositivo);
    if (mysqli_stmt_execute($stmtView)) {
        echo "<!-- DEBUG VISUALIZACION: Insertada con éxito para trailer ID $id y usuario " . ($id_usuario_view ?? 'Invitado') . " -->";
    } else {
        echo "<!-- DEBUG VISUALIZACION ERROR EJECUCION: " . htmlspecialchars(mysqli_stmt_error($stmtView)) . " -->";
    }
    mysqli_stmt_close($stmtView);
} else {
    echo "<!-- DEBUG VISUALIZACION ERROR PREPARACION: " . htmlspecialchars(mysqli_error($conexion)) . " -->";
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
if ($stmtReparto) {
    mysqli_stmt_bind_param($stmtReparto, "i", $id);
    mysqli_stmt_execute($stmtReparto);
    $resReparto = mysqli_stmt_get_result($stmtReparto);
    $reparto = [];
    while ($row = mysqli_fetch_assoc($resReparto)) {
        $reparto[] = $row;
    }
    mysqli_stmt_close($stmtReparto);
    echo "<!-- DEBUG ACTORES: Encontrados " . count($reparto) . " actores para trailer ID $id -->";
} else {
    echo "<!-- DEBUG ACTORES ERROR: " . htmlspecialchars(mysqli_error($conexion)) . " -->";
}

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

// Consultar todas las reseñas y valoraciones para este trailer
$resenas = [];
$sqlResenas = "SELECT r.*, u.username, u.avatar_url, u.nombre, u.apellidos 
               FROM resenas r 
               JOIN usuarios u ON r.id_usuario = u.id_usuario 
               WHERE r.id_trailer = ? 
               ORDER BY r.fecha_alta DESC";
$stmtResenas = mysqli_prepare($conexion, $sqlResenas);
if ($stmtResenas) {
    mysqli_stmt_bind_param($stmtResenas, "i", $id);
    mysqli_stmt_execute($stmtResenas);
    $resRes = mysqli_stmt_get_result($stmtResenas);
    while ($row = mysqli_fetch_assoc($resRes)) {
        $resenas[] = $row;
    }
    mysqli_stmt_close($stmtResenas);
}

// Buscar si el usuario actual ya ha dejado una reseña
$userReview = null;
if (isset($_SESSION['usuario_id'])) {
    $id_usuario = (int)$_SESSION['usuario_id'];
    foreach ($resenas as $r) {
        if ((int)$r['id_usuario'] === $id_usuario) {
            $userReview = $r;
            break;
        }
    }
}

// Calcular el promedio de valoración
$avgRating = 0;
if (!empty($resenas)) {
    $totalRating = 0;
    foreach ($resenas as $r) {
        $totalRating += (int)$r['valoracion'];
    }
    $avgRating = round($totalRating / count($resenas), 1);
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
                        <a href="toggle_favorito.php?id=<?= $trailer['id_trailer'] ?>" class="btn btn-secondary btn-toggle-favorito-detail btn-active-favorito-reproductor" data-id="<?= $trailer['id_trailer'] ?>">
                            <i class="fa-solid fa-heart"></i> Quitar de Favoritos
                        </a>
                    <?php else: ?>
                        <a href="toggle_favorito.php?id=<?= $trailer['id_trailer'] ?>" class="btn btn-secondary btn-toggle-favorito-detail btn-inline-flex" data-id="<?= $trailer['id_trailer'] ?>">
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

            <!-- Sección de Reseñas y Comentarios -->
            <div class="reviews-section">
                <h3 class="info-cast-title" style="cursor: pointer;" id="toggleReviewsHeader">
                    <i class="fa-solid fa-comments"></i> Reseñas y Valoraciones (<?= count($resenas) ?>) <span style="font-size: 13px; color: var(--text-muted); font-weight: normal; margin-left: 10px;">(Clic para desplegar)</span>
                    <?php if ($avgRating > 0): ?>
                        <span class="reviews-avg-rating">
                            <i class="fa-solid fa-star"></i> <?= $avgRating ?> / 5 promedio
                        </span>
                    <?php endif; ?>
                </h3>
                
                <div id="reviewsCollapsibleContent" style="display: none; margin-top: 15px;">
                
                <!-- Formulario para escribir/editar reseña (si está logueado) -->
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <div class="write-review-card">
                        <h4>
                            <?= $userReview ? 'Editar tu reseña' : 'Escribe tu reseña' ?>
                        </h4>
                        
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="guardar_resena">
                            
                            <!-- Selector de estrellas interactivo -->
                            <div class="star-rating-container">
                                <span>Tu valoración:</span>
                                <div class="star-rating">
                                    <?php 
                                    $userRating = $userReview ? (int)$userReview['valoracion'] : 0;
                                    for ($i = 5; $i >= 1; $i--): 
                                    ?>
                                        <input type="radio" id="star-<?= $i ?>" name="valoracion" value="<?= $i ?>" style="display: none;" <?= $userRating === $i ? 'checked' : '' ?> required>
                                        <label for="star-<?= $i ?>" class="star-label" title="<?= $i ?> estrellas">
                                            <i class="fa-solid fa-star"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <textarea name="comentario" rows="3" placeholder="Escribe tu reseña u opinión sobre este trailer (opcional)..." required><?= $userReview ? htmlspecialchars($userReview['comentario']) : '' ?></textarea>
                            
                            <div class="review-form-actions">
                                <?php if ($userReview): ?>
                                    <button type="submit" name="delete_btn" onclick="document.getElementById('deleteReviewForm').submit(); return false;" class="btn btn-danger">
                                        <i class="fa-solid fa-trash"></i> Eliminar
                                    </button>
                                <?php endif; ?>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-paper-plane"></i> <?= $userReview ? 'Guardar Cambios' : 'Publicar Reseña' ?>
                                </button>
                            </div>
                        </form>
                        
                        <?php if ($userReview): ?>
                            <!-- Formulario oculto para eliminar reseña -->
                            <form id="deleteReviewForm" action="" method="POST" style="display:none;">
                                <input type="hidden" name="action" value="eliminar_resena">
                            </form>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="login-prompt-card">
                        <p>
                            <i class="fa-solid fa-circle-info"></i> Debes <a href="../auth/login.php">iniciar sesión</a> para dejar una valoración o comentario.
                        </p>
                    </div>
                <?php endif; ?>
                
                <!-- Listado de Reseñas -->
                <div class="reviews-list">
                    <?php if (empty($resenas)): ?>
                        <p class="no-reviews-msg">
                            Nadie ha valorado este trailer todavía. ¡Sé el primero!
                        </p>
                    <?php else: ?>
                        <?php foreach ($resenas as $resena): ?>
                            <div class="review-item">
                                <!-- Avatar -->
                                <div class="review-avatar-container">
                                    <?php if (!empty($resena['avatar_url'])): ?>
                                        <img src="<?= htmlspecialchars($resena['avatar_url']) ?>" alt="Avatar">
                                    <?php else: ?>
                                        <div class="review-avatar-fallback">
                                            <i class="fa-solid fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Detalles de la reseña -->
                                <div class="review-details">
                                    <div class="review-header">
                                        <div>
                                            <span class="review-author-name"><?= htmlspecialchars($resena['nombre'] . ' ' . $resena['apellidos']) ?></span>
                                            <span class="review-author-username">@<?= htmlspecialchars($resena['username']) ?></span>
                                        </div>
                                        <span class="review-date">
                                            <i class="fa-regular fa-clock"></i> <?= date('d/m/Y H:i', strtotime($resena['fecha_alta'])) ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Estrellas -->
                                    <div class="review-stars">
                                        <?php for ($k = 1; $k <= 5; $k++): ?>
                                            <i class="<?= $k <= (int)$resena['valoracion'] ? 'fa-solid' : 'fa-regular' ?> fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                    
                                    <!-- Comentario -->
                                    <?php if (!empty($resena['comentario'])): ?>
                                        <p class="review-text"><?= htmlspecialchars($resena['comentario']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                </div> <!-- Closes #reviewsCollapsibleContent -->
            </div>
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Lógica para desplegar reseñas asíncronamente
            const toggleReviewsHeader = document.getElementById('toggleReviewsHeader');
            const reviewsContent = document.getElementById('reviewsCollapsibleContent');
            if (toggleReviewsHeader && reviewsContent) {
                toggleReviewsHeader.addEventListener('click', () => {
                    const isCollapsed = reviewsContent.style.display === 'none';
                    reviewsContent.style.display = isCollapsed ? 'block' : 'none';
                    if (isCollapsed) {
                        fetch('../badges/registrar_evento.php?action=leer_resenas&id_trailer=<?= $id ?>');
                    }
                });
            }

            // Lógica del Modo Cine (Apagar Luces)
            const cinemaBtn = document.getElementById('cinemaModeBtn');
            const backdrop = document.getElementById('cinemaBackdrop');

            if (cinemaBtn && backdrop) {
                function toggleCinemaMode() {
                    const isActive = document.body.classList.toggle('cinema-mode-active');
                    if (isActive) {
                        cinemaBtn.innerHTML = '<i class="fa-solid fa-sun"></i> <span>Encender Luces</span>';
                        fetch('../badges/registrar_evento.php?action=modo_cine');
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
