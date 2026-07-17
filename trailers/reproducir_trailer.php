<?php
require_once "../config/conexion.php";
require_once __DIR__ . "/../includes/seguridad.php";
define('BASE_PATH', '../');

csrf_token();

// Auto-migración: Crear tabla de reseñas si no existe
$sqlMigrateResenas = "CREATE TABLE IF NOT EXISTS resenas (
    id_resena INT AUTO_INCREMENT PRIMARY KEY,
    id_trailer INT NOT NULL,
    id_usuario INT NOT NULL,
    valoracion DECIMAL(2,1) NOT NULL,
    comentario TEXT DEFAULT NULL,
    fecha_alta DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_trailer_usuario (id_trailer, id_usuario),
    CONSTRAINT fk_resenas_trailers FOREIGN KEY (id_trailer) REFERENCES trailers(id_trailer) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_resenas_usuarios FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
mysqli_query($conexion, $sqlMigrateResenas);

// Asegurar que la columna valoracion en resenas sea DECIMAL(2,1)
$checkColType = mysqli_query($conexion, "SHOW COLUMNS FROM resenas LIKE 'valoracion'");
if ($checkColType && $rowCol = mysqli_fetch_assoc($checkColType)) {
    if (stripos($rowCol['Type'], 'int') !== false) {
        mysqli_query($conexion, "ALTER TABLE resenas MODIFY COLUMN valoracion DECIMAL(2,1) NOT NULL");
    }
}
// Verificar si la columna estado existe en resenas, si no, crearla
$checkColEstado = mysqli_query($conexion, "SHOW COLUMNS FROM resenas LIKE 'estado'");
if (mysqli_num_rows($checkColEstado) == 0) {
    mysqli_query($conexion, "ALTER TABLE resenas ADD COLUMN estado VARCHAR(20) NOT NULL DEFAULT 'aprobada'");
}

$successMsg = $_SESSION['success'] ?? null;
$errorMsg = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT t.*, GROUP_CONCAT(g.nombre SEPARATOR ', ') as genero,
               d.nombre as director_nombre, d.apellidos as director_apellidos, d.id_director,
               COALESCE((SELECT ROUND(AVG(valoracion), 1) FROM resenas WHERE id_trailer = t.id_trailer), 0) as promedio_resenas
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

    $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
        || (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
        || (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    require_csrf($isAjax);
    
    $id_usuario = (int)$_SESSION['usuario_id'];
    
    if ($_POST['action'] === 'guardar_resena') {
        $valoracion = isset($_POST['valoracion']) ? (float)$_POST['valoracion'] : 0.0;
        $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';
        
        $commentLength = mb_strlen($comentario);
        if ($commentLength > 0 && ($commentLength < 25 || $commentLength > 2000)) {
            if ($isAjax) {
                http_response_code(400);
                echo json_encode(['error' => "La reseña debe tener entre 25 y 2000 caracteres."]);
                exit;
            }
            $_SESSION['error'] = "La reseña debe tener entre 25 y 2000 caracteres.";
            header("Location: reproducir_trailer.php?id=" . $id);
            exit;
        }
        
        if ($valoracion < 0.5 || $valoracion > 5.0) {
            if ($isAjax) {
                http_response_code(400);
                echo json_encode(['error' => "Por favor selecciona una valoración entre 0.5 y 5 estrellas."]);
                exit;
            }
            $_SESSION['error'] = "Por favor selecciona una valoración entre 0.5 y 5 estrellas.";
            header("Location: reproducir_trailer.php?id=" . $id);
            exit;
        }
        
        // Obtener el estado del comentario anterior para evitar re-moderar comentarios idénticos
        $prevComment = '';
        $prevStatus = 'aprobada';
        $sqlPrev = "SELECT comentario, estado FROM resenas WHERE id_trailer = ? AND id_usuario = ? LIMIT 1";
        $stmtPrev = mysqli_prepare($conexion, $sqlPrev);
        if ($stmtPrev) {
            mysqli_stmt_bind_param($stmtPrev, "ii", $id, $id_usuario);
            mysqli_stmt_execute($stmtPrev);
            $resPrev = mysqli_stmt_get_result($stmtPrev);
            if ($rowPrev = mysqli_fetch_assoc($resPrev)) {
                $prevComment = $rowPrev['comentario'] ?? '';
                $prevStatus = $rowPrev['estado'];
            }
            mysqli_stmt_close($stmtPrev);
        }

        $nuevoEstado = $prevStatus;
        if ($comentario !== $prevComment) {
            $nuevoEstado = (empty($comentario)) ? 'aprobada' : 'pendiente';
        }

        $sqlSave = "INSERT INTO resenas (id_trailer, id_usuario, valoracion, comentario, estado, fecha_alta) 
                    VALUES (?, ?, ?, ?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE valoracion = VALUES(valoracion), comentario = VALUES(comentario), estado = VALUES(estado), fecha_alta = NOW()";
        $stmtSave = mysqli_prepare($conexion, $sqlSave);
        if ($stmtSave) {
            mysqli_stmt_bind_param($stmtSave, "iidss", $id, $id_usuario, $valoracion, $comentario, $nuevoEstado);
            if (mysqli_stmt_execute($stmtSave)) {
                // Recalcular promedio de la comunidad para la respuesta AJAX
                $newAvg = 0;
                $sqlNewAvg = "SELECT AVG(valoracion) as avg_val FROM resenas WHERE id_trailer = ?";
                $stmtNewAvg = mysqli_prepare($conexion, $sqlNewAvg);
                if ($stmtNewAvg) {
                    mysqli_stmt_bind_param($stmtNewAvg, "i", $id);
                    mysqli_stmt_execute($stmtNewAvg);
                    $resNewAvg = mysqli_stmt_get_result($stmtNewAvg);
                    if ($rowNewAvg = mysqli_fetch_assoc($resNewAvg)) {
                        $newAvg = round((float)$rowNewAvg['avg_val'], 1);
                    }
                    mysqli_stmt_close($stmtNewAvg);
                }

                if ($isAjax) {
                    echo json_encode(['success' => true, 'avgRating' => $newAvg]);
                    exit;
                }
                $_SESSION['success'] = "¡Tu reseña ha sido guardada con éxito!";
            } else {
                if ($isAjax) {
                    http_response_code(500);
                    echo json_encode(['error' => "Error al guardar en la base de datos."]);
                    exit;
                }
                $_SESSION['error'] = "Error al guardar la reseña. Inténtalo de nuevo.";
            }
            mysqli_stmt_close($stmtSave);
        } else {
            if ($isAjax) {
                http_response_code(500);
                echo json_encode(['error' => "Error de preparación de consulta."]);
                exit;
            }
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
                   ($scoreExpr) as recommendation_score,
                   COALESCE((SELECT ROUND(AVG(valoracion), 1) FROM resenas WHERE id_trailer = t.id_trailer), 0) as promedio_resenas
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
$myListStatus = null; // 'por_ver', 'vista', o null
$myPrivateComment = '';
if (isset($_SESSION['usuario_id'])) {
    $id_usuario = (int)$_SESSION['usuario_id'];
    foreach ($resenas as $r) {
        if ((int)$r['id_usuario'] === $id_usuario) {
            $userReview = $r;
            break;
        }
    }

    // Estado de la lista
    $sqlStatus = "SELECT estado FROM listas_personales WHERE id_usuario = ? AND id_trailer = ? LIMIT 1";
    $stmtStatus = mysqli_prepare($conexion, $sqlStatus);
    if ($stmtStatus) {
        mysqli_stmt_bind_param($stmtStatus, "ii", $id_usuario, $id);
        mysqli_stmt_execute($stmtStatus);
        $resStatus = mysqli_stmt_get_result($stmtStatus);
        if ($rowStatus = mysqli_fetch_assoc($resStatus)) {
            $myListStatus = $rowStatus['estado'];
        }
        mysqli_stmt_close($stmtStatus);
    }
    
    // Comentario privado
    $sqlPrivComment = "SELECT comentario FROM comentarios_privados WHERE id_usuario = ? AND id_trailer = ? LIMIT 1";
    $stmtPrivComment = mysqli_prepare($conexion, $sqlPrivComment);
    if ($stmtPrivComment) {
        mysqli_stmt_bind_param($stmtPrivComment, "ii", $id_usuario, $id);
        mysqli_stmt_execute($stmtPrivComment);
        $resPrivComment = mysqli_stmt_get_result($stmtPrivComment);
        if ($rowPrivComment = mysqli_fetch_assoc($resPrivComment)) {
            $myPrivateComment = $rowPrivComment['comentario'];
        }
        mysqli_stmt_close($stmtPrivComment);
    }
}

// Calcular el promedio de valoración
$avgRating = 0;
if (!empty($resenas)) {
    $totalRating = 0;
    foreach ($resenas as $r) {
        $totalRating += (float)$r['valoracion'];
    }
    $avgRating = round($totalRating / count($resenas), 1);
}
?>
<?php
$pageTitle = "Reproduciendo: " . $trailer['titulo'];
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>

    <main class="app-container" style="max-width: 1450px; width: 95%;">
        <div class="reproducer-header">
            <h1>Reproductor de Trailers</h1>
            <p>Disfruta del trailer oficial de la película seleccionada.</p>
        </div>

    <div class="cinema-player-layout" <?= !isset($_SESSION['usuario_id']) ? 'style="grid-template-columns: 1fr;"' : '' ?>>
        <!-- Columna Izquierda: Reproductor -->
        <div class="cinema-player-column">
            <div class="player-wrapper" style="margin-bottom: 0; padding: 0; background: transparent !important; border: none; box-shadow: none; max-width: 100%;">
                <div class="player-toolbar">
                    <button type="button" id="cinemaModeBtn" class="btn btn-secondary btn-cinema-mode">
                        <i class="fa-solid fa-moon"></i> <span>Modo Cine</span>
                    </button>
                </div>
                <div class="video-container" style="margin-bottom: 0;">
                    <iframe src="<?php echo htmlspecialchars($embedUrl); ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Bitácora Personal -->
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <div class="cinema-bitacora-column">
                <!-- Tarjeta: Mi Lista -->
                <div class="bitacora-card">
                    <h3 class="bitacora-title">Mi Lista</h3>
                    <div class="list-status-tiles">
                        <button type="button" class="tile-btn btn-inline-list-status <?= $myListStatus === null ? 'active' : '' ?>" data-status="none">
                            <i class="fa-solid fa-circle-plus"></i>
                            <span class="status-label"><?= $myListStatus === null ? 'No en lista' : 'En la lista' ?></span>
                        </button>
                        <button type="button" class="tile-btn btn-inline-list-status <?= $myListStatus === 'por_ver' ? 'active' : '' ?>" data-status="por_ver">
                            <i class="fa-solid fa-bookmark"></i>
                            <span>Por Ver</span>
                        </button>
                        <button type="button" class="tile-btn btn-inline-list-status <?= $myListStatus === 'vista' ? 'active' : '' ?>" data-status="vista">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Vista</span>
                        </button>
                    </div>
                </div>

                <!-- Tarjeta: Nota Privada -->
                <div class="bitacora-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 class="bitacora-title">Nota Privada</h3>
                        <i class="fa-solid fa-lock" style="color: var(--text-muted); opacity: 0.6; font-size: 14px;"></i>
                    </div>
                    <textarea id="privateNoteTextarea" class="private-note-textarea" placeholder="Escribe tu análisis privado sobre el tráiler o la película aquí..."><?= htmlspecialchars($myPrivateComment) ?></textarea>
                    
                    <button type="button" id="btnSavePrivateNote" class="btn-save-note-full">
                        <i class="fa-solid fa-save"></i> Guardar Nota
                    </button>
                </div>

                <!-- Acordeón: Historial de Cambios -->
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div id="btnTogglePrivateHistory" class="history-accordion-bar">
                        <span>
                            <i class="fa-solid fa-history"></i> Historial de Cambios
                        </span>
                        <i class="fa-solid fa-chevron-down toggle-arrow"></i>
                    </div>
                    
                    <!-- Historial colapsable -->
                    <div id="privateNoteHistoryContainer" style="display: none; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 15px; background: rgba(8, 20, 37, 0.2);">
                        <div id="privateNoteHistoryList" style="display: flex; flex-direction: column; gap: 8px;"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Info Container below layout -->
    <div class="info-container" style="width: 100%;">
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
            <span>Valoración TMDB: <strong>⭐ <?php echo htmlspecialchars((string)$trailer['valoracion']); ?>/10</strong></span>
            <span id="communityRatingMeta" style="<?= (isset($trailer['promedio_resenas']) && $trailer['promedio_resenas'] > 0) ? '' : 'display: none;' ?>">
                Valoración Comunidad: <strong><i class="fa-solid fa-comments"></i> <span id="communityRatingValue"><?= htmlspecialchars((string)$trailer['promedio_resenas']) ?></span>/5</strong>
            </span>
        </div>
        
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <div class="text-center mb-24" style="margin-top: 15px; margin-bottom: 20px;">
                <?php if ($isTrailerFavorito): ?>
                    <form action="toggle_favorito.php" method="POST" class="favorite-toggle-form" style="display: inline-flex; margin: 0;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$trailer['id_trailer'] ?>">
                        <button type="submit" class="btn btn-secondary btn-toggle-favorito-detail btn-active-favorito-reproductor" data-id="<?= (int)$trailer['id_trailer'] ?>">
                            <i class="fa-solid fa-heart"></i> Quitar de Favoritos
                        </button>
                    </form>
                <?php else: ?>
                    <form action="toggle_favorito.php" method="POST" class="favorite-toggle-form" style="display: inline-flex; margin: 0;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$trailer['id_trailer'] ?>">
                        <button type="submit" class="btn btn-secondary btn-toggle-favorito-detail btn-inline-flex" data-id="<?= (int)$trailer['id_trailer'] ?>">
                            <i class="fa-regular fa-heart"></i> Añadir a Favoritos
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="info-synopsis">
            <p><?php echo htmlspecialchars($trailer['sinopsis'] ?? 'Sin sinopsis o descripción disponible.'); ?></p>
        </div>

        <?php if (!empty($reparto)): ?>
            <div class="info-cast" style="margin-bottom: 30px;">
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
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="guardar_resena">
                            
                            <!-- Selector de estrellas interactivo -->
                            <div class="star-rating-container">
                                <span>Tu valoración:</span>
                                <div class="star-rating">
                                    <?php 
                                    $userRating = $userReview ? (float)$userReview['valoracion'] : 0.0;
                                    for ($i = 50; $i >= 5; $i -= 5): 
                                        $val = $i / 10;
                                        $isHalf = (fmod($val, 1) !== 0.0);
                                        $class = $isHalf ? 'star-half-left' : 'star-half-right';
                                    ?>
                                        <input type="radio" id="star-<?= $val ?>" name="valoracion" value="<?= $val ?>" style="display: none;" <?= abs((float)$userRating - (float)$val) < 0.01 ? 'checked' : '' ?> required>
                                        <label for="star-<?= $val ?>" class="<?= $class ?>" title="<?= $val ?> estrellas">
                                            <i class="fa-solid fa-star"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <textarea name="comentario" id="reviewComentario" rows="3" placeholder="Escribe tu reseña u opinión sobre este trailer (opcional, mín. 25 y máx. 2000 caracteres)..." minlength="25" maxlength="2000"><?= $userReview ? htmlspecialchars($userReview['comentario']) : '' ?></textarea>
                            
                            <?php 
                            $currentLength = $userReview ? mb_strlen($userReview['comentario']) : 0;
                            ?>
                            <div class="review-char-counter" id="reviewCharCounter"><?= $currentLength ?> / 2000</div>
                            
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
                                <?= csrf_field() ?>
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
                                        <?php 
                                        $rating = (float)$resena['valoracion'];
                                        for ($k = 1; $k <= 5; $k++): 
                                            if ($rating >= $k) {
                                                echo '<i class="fa-solid fa-star"></i>';
                                            } elseif ($rating >= $k - 0.5) {
                                                echo '<i class="fa-solid fa-star-half-stroke"></i>';
                                            } else {
                                                echo '<i class="fa-regular fa-star"></i>';
                                            }
                                        endfor; 
                                        ?>
                                    </div>
                                    
                                    <!-- Comentario -->
                                    <?php if (!empty($resena['comentario'])): ?>
                                        <?php if ($resena['estado'] === 'aprobada' || (isset($_SESSION['usuario_id']) && (int)$resena['id_usuario'] === (int)$_SESSION['usuario_id'])): ?>
                                            <p class="review-text"><?= htmlspecialchars($resena['comentario']) ?></p>
                                            <?php if ($resena['estado'] === 'pendiente'): ?>
                                                <span class="badge" style="font-size: 10px; background: rgba(245,158,11,0.15); color: var(--primary); border: 1px solid rgba(245,158,11,0.3); padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px;">
                                                    <i class="fa-solid fa-hourglass-half"></i> Comentario pendiente de aprobación
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                </div> <!-- Closes #reviewsCollapsibleContent -->
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

                            <div class="rating-badge" title="Valoración TMDB / Comunidad">
                                <i class="fa-solid fa-star"></i>
                                <span><?= htmlspecialchars((string)$rec['valoracion']) ?></span>
                                <?php if (isset($rec['promedio_resenas']) && $rec['promedio_resenas'] > 0): ?>
                                    <span style="border-left: 1px solid rgba(216, 195, 173, 0.25); padding-left: 4px; margin-left: 2px;">
                                        <i class="fa-solid fa-comments"></i> <?= htmlspecialchars((string)$rec['promedio_resenas']) ?>
                                    </span>
                                <?php endif; ?>
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
            const csrfTokenVal = <?= json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

            // Lógica para desplegar reseñas asíncronamente
            const toggleReviewsHeader = document.getElementById('toggleReviewsHeader');
            const reviewsContent = document.getElementById('reviewsCollapsibleContent');
            if (toggleReviewsHeader && reviewsContent) {
                toggleReviewsHeader.addEventListener('click', () => {
                    const isCollapsed = reviewsContent.style.display === 'none';
                    reviewsContent.style.display = isCollapsed ? 'block' : 'none';
                    if (isCollapsed) {
                        fetch('../badges/registrar_evento.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': csrfTokenVal,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                action: 'leer_resenas',
                                id_trailer: <?= (int)$id ?>
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.nuevos_logros && data.nuevos_logros.length > 0) {
                                data.nuevos_logros.forEach(logro => {
                                    showToast(`🏆 ¡Logro desbloqueado: ${logro.nombre}! - ${logro.descripcion}`, 'success');
                                });
                            }
                        })
                        .catch(err => console.error(err));
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
                        fetch('../badges/registrar_evento.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': csrfTokenVal,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ action: 'modo_cine' })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.nuevos_logros && data.nuevos_logros.length > 0) {
                                data.nuevos_logros.forEach(logro => {
                                    showToast(`🏆 ¡Logro desbloqueado: ${logro.nombre}! - ${logro.descripcion}`, 'success');
                                });
                            }
                        })
                        .catch(err => console.error(err));
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

            // Lógica para el contador de caracteres y validación de reseñas
            const reviewTextarea = document.getElementById('reviewComentario');
            const reviewCounter = document.getElementById('reviewCharCounter');
            const submitBtn = document.querySelector('.write-review-card button[type="submit"]:not([name="delete_btn"])');

            if (reviewTextarea && reviewCounter && submitBtn) {
                const updateCounter = () => {
                    const length = reviewTextarea.value.length;
                    reviewCounter.textContent = `${length} / 2000`;
                    
                    if (length === 0) {
                        reviewCounter.className = 'review-char-counter';
                        submitBtn.disabled = false;
                    } else if (length < 25 || length > 2000) {
                        reviewCounter.className = 'review-char-counter invalid';
                        submitBtn.disabled = true;
                    } else {
                        reviewCounter.className = 'review-char-counter valid';
                        submitBtn.disabled = false;
                    }
                };

                reviewTextarea.addEventListener('input', updateCounter);
                // Ejecución inicial para textos pre-cargados (editar reseña)
                updateCounter();
            }

            // Guardado automático al hacer clic en las estrellas
            const starInputs = document.querySelectorAll('.star-rating input[type="radio"]');
            if (starInputs.length > 0) {
                starInputs.forEach(input => {
                    input.addEventListener('change', () => {
                        const ratingValue = input.value;
                        const trailerId = <?= $id ?>;
                        const commentText = reviewTextarea ? reviewTextarea.value : '';

                        const formData = new FormData();
                        formData.append('action', 'guardar_resena');
                        formData.append('valoracion', ratingValue);
                        formData.append('comentario', commentText);

                        fetch('reproducir_trailer.php?id=' + trailerId, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-Token': csrfTokenVal
                            },
                            body: formData
                        })
                        .then(response => {
                            if (response.status === 401) {
                                window.location.href = '../auth/login.php';
                                return;
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data && data.success) {
                                showToast('⭐ ¡Valoración guardada!', 'success');
                                
                                // Actualizar dinámicamente el promedio comunitario en la interfaz
                                const metaContainer = document.getElementById('communityRatingMeta');
                                const valueContainer = document.getElementById('communityRatingValue');
                                if (metaContainer && valueContainer) {
                                    valueContainer.textContent = data.avgRating;
                                    metaContainer.style.display = 'inline';
                                }
                            } else if (data && data.error) {
                                showToast(data.error, 'error');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            showToast('Error al conectar con el servidor', 'error');
                        });
                    });
                });
            }
            // === Lógica de la Bitácora Personal (Inline) ===
            const currentTrailerId = <?= $id ?>;

            // Cambiar estado en las listas personales
            const listStatusButtons = document.querySelectorAll('.btn-inline-list-status');
            listStatusButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const status = btn.getAttribute('data-status');
                    let action = 'add_to_list';
                    let targetStatus = status;

                    if (status === 'none') {
                        // El primer botón actúa como toggle:
                        // Si está activo (el elemento NO está en la lista), al hacer clic lo AÑADE como 'por_ver'
                        if (btn.classList.contains('active')) {
                            action = 'add_to_list';
                            targetStatus = 'por_ver';
                        } else {
                            // Si NO está activo (el elemento ya está en alguna lista), al hacer clic lo QUITA de la lista
                            action = 'remove_from_list';
                            targetStatus = 'none';
                        }
                    }

                    fetch('../auth/api_listas.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrfTokenVal
                        },
                        body: JSON.stringify({
                            action: action,
                            id_trailer: currentTrailerId,
                            estado: targetStatus
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast('¡Lista actualizada!', 'success');
                            
                            // Actualizar clases activas de los botones de forma inteligente
                            listStatusButtons.forEach(b => b.classList.remove('active'));
                            
                            const firstBtn = document.querySelector('.btn-inline-list-status[data-status="none"]');
                            const firstBtnLabel = firstBtn ? firstBtn.querySelector('span') : null;
                            const porVerBtn = document.querySelector('.btn-inline-list-status[data-status="por_ver"]');
                            const vistaBtn = document.querySelector('.btn-inline-list-status[data-status="vista"]');

                            if (action === 'remove_from_list') {
                                if (firstBtn) firstBtn.classList.add('active');
                                if (firstBtnLabel) firstBtnLabel.textContent = 'No en lista';
                            } else {
                                // Se añadió a la lista
                                if (firstBtnLabel) firstBtnLabel.textContent = 'En la lista';
                                if (targetStatus === 'por_ver' && porVerBtn) {
                                    porVerBtn.classList.add('active');
                                } else if (targetStatus === 'vista' && vistaBtn) {
                                    vistaBtn.classList.add('active');
                                }
                            }
                        } else {
                            showToast(data.error, 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        showToast('Error al actualizar la lista.', 'error');
                    });
                });
            });

            // Guardar Nota Privada
            const btnSavePrivateNote = document.getElementById('btnSavePrivateNote');
            const privateNoteTextarea = document.getElementById('privateNoteTextarea');
            if (btnSavePrivateNote && privateNoteTextarea) {
                btnSavePrivateNote.addEventListener('click', () => {
                    const commentText = privateNoteTextarea.value;

                    fetch('../auth/api_listas.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrfTokenVal
                        },
                        body: JSON.stringify({
                            action: 'save_comment',
                            id_trailer: currentTrailerId,
                            comentario: commentText
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast('¡Nota privada guardada!', 'success');
                            // Ocultar historial para forzar recarga de nuevas versiones
                            document.getElementById('privateNoteHistoryContainer').style.display = 'none';
                            if (btnTogglePrivateHistory) btnTogglePrivateHistory.classList.remove('active');
                        } else {
                            showToast(data.error, 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        showToast('Error al guardar la nota privada.', 'error');
                    });
                });
            }

            // Ver historial de notas privadas
            const btnTogglePrivateHistory = document.getElementById('btnTogglePrivateHistory');
            const privateNoteHistoryContainer = document.getElementById('privateNoteHistoryContainer');
            const privateNoteHistoryList = document.getElementById('privateNoteHistoryList');
            if (btnTogglePrivateHistory && privateNoteHistoryContainer && privateNoteHistoryList) {
                btnTogglePrivateHistory.addEventListener('click', () => {
                    if (privateNoteHistoryContainer.style.display === 'none') {
                        fetch('../auth/api_listas.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': csrfTokenVal
                            },
                            body: JSON.stringify({
                                action: 'get_history',
                                id_trailer: currentTrailerId
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                privateNoteHistoryList.innerHTML = '';
                                if (data.history.length === 0) {
                                    privateNoteHistoryList.innerHTML = '<p style="color: var(--text-muted); font-size: 11px; margin: 0;">No hay cambios registrados en tu nota.</p>';
                                } else {
                                    data.history.forEach(h => {
                                        const entry = document.createElement('div');
                                        entry.style.background = 'rgba(255, 255, 255, 0.02)';
                                        entry.style.padding = '8px';
                                        entry.style.borderLeft = '2px solid var(--primary)';
                                        entry.style.borderRadius = 'var(--radius-sm)';
                                        entry.style.fontSize = '11px';
                                        
                                        // Escapar texto para prevenir XSS
                                        const safeComment = h.comentario_anterior
                                            .replace(/&/g, "&amp;")
                                            .replace(/</g, "&lt;")
                                            .replace(/>/g, "&gt;")
                                            .replace(/"/g, "&quot;")
                                            .replace(/'/g, "&#039;");
                                            
                                        entry.innerHTML = `
                                            <div style="color: var(--text-muted); font-size: 10px; margin-bottom: 4px;">Edición: ${h.fecha_cambio}</div>
                                            <div style="color: var(--text-primary); white-space: pre-wrap;">${safeComment}</div>
                                        `;
                                        privateNoteHistoryList.appendChild(entry);
                                    });
                                }
                                privateNoteHistoryContainer.style.display = 'block';
                                btnTogglePrivateHistory.classList.add('active');
                            } else {
                                showToast(data.error, 'error');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            showToast('Error al cargar historial.', 'error');
                        });
                    } else {
                        privateNoteHistoryContainer.style.display = 'none';
                        btnTogglePrivateHistory.classList.remove('active');
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
