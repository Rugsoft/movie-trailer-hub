<?php
include "config/conexion.php";
define('BASE_PATH', '');

// Mensajes de éxito o error redireccionados
$successMsg = $_SESSION['success'] ?? null;
$errorMsg = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// 1. Consultar géneros de la tabla generos (lo necesitamos para validar el filtro de géneros)
$sqlGenres = "SELECT nombre FROM generos ORDER BY nombre ASC";
$resGenres = mysqli_query($conexion, $sqlGenres);
$genres = [];
while ($row = mysqli_fetch_assoc($resGenres)) {
    $genres[] = $row['nombre'];
}
mysqli_free_result($resGenres);

// 2. Obtener y validar parámetros de filtros y búsqueda desde $_GET
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$selectedGenres = [];
if (isset($_GET['genres'])) {
    $selectedGenres = (array)$_GET['genres'];
} elseif (isset($_GET['genre']) && $_GET['genre'] !== 'Todos' && $_GET['genre'] !== '') {
    $selectedGenres = [$_GET['genre']];
}
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$upcoming = (isset($_GET['upcoming']) && $_GET['upcoming'] === '1') ? 1 : 0;

// Validar que el género exista
$selectedGenres = array_filter($selectedGenres, function($g) use ($genres) {
    return in_array($g, $genres);
});

// Validar formato básico de fechas
function isValidDateString($dateStr) {
    if (empty($dateStr)) return false;
    $d = DateTime::createFromFormat('Y-m-d', $dateStr);
    return $d && $d->format('Y-m-d') === $dateStr;
}
if (!empty($start_date) && !isValidDateString($start_date)) {
    $start_date = '';
}
if (!empty($end_date) && !isValidDateString($end_date)) {
    $end_date = '';
}

// Construir condiciones SQL (WHERE) para filtros dinámicos
$whereClauses = [];
$params = [];
$paramTypes = "";

if ($search !== '') {
    $whereClauses[] = "(t.titulo LIKE ? OR t.sinopsis LIKE ? OR CONCAT(d.nombre, ' ', d.apellidos) LIKE ? OR t.id_trailer IN (
        SELECT rt2.id_trailer 
        FROM reparto_trailers rt2 
        JOIN reparto r2 ON rt2.id_reparto = r2.id_reparto 
        WHERE CONCAT(r2.nombre, ' ', r2.apellidos) LIKE ?
    ))";
    $searchWildcard = '%' . $search . '%';
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $paramTypes .= "ssss";
}

if (!empty($selectedGenres)) {
    $placeholders = implode(',', array_fill(0, count($selectedGenres), '?'));
    $whereClauses[] = "t.id_trailer IN (
        SELECT tg2.id_trailer 
        FROM trailers_generos tg2 
        JOIN generos g2 ON tg2.id_genero = g2.id_genero 
        WHERE g2.nombre IN ($placeholders)
    )";
    foreach ($selectedGenres as $g) {
        $params[] = $g;
        $paramTypes .= "s";
    }
}

if ($start_date !== '') {
    $whereClauses[] = "t.release_date >= ?";
    $params[] = $start_date;
    $paramTypes .= "s";
}

if ($end_date !== '') {
    $whereClauses[] = "t.release_date <= ?";
    $params[] = $end_date;
    $paramTypes .= "s";
}

if ($upcoming === 1) {
    $whereClauses[] = "t.release_date >= CURDATE()";
}

$whereSql = "";
if (!empty($whereClauses)) {
    $whereSql = "WHERE " . implode(" AND ", $whereClauses);
}

// 3. Obtener el conteo total para la paginación
$countSql = "SELECT COUNT(DISTINCT t.id_trailer) as total 
             FROM trailers t
             LEFT JOIN directores d ON t.id_director = d.id_director
             $whereSql";

$totalRecords = 0;
$stmtCount = mysqli_prepare($conexion, $countSql);
if ($stmtCount) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmtCount, $paramTypes, ...$params);
    }
    mysqli_stmt_execute($stmtCount);
    $resCount = mysqli_stmt_get_result($stmtCount);
    if ($rowCount = mysqli_fetch_assoc($resCount)) {
        $totalRecords = (int)$rowCount['total'];
    }
    mysqli_stmt_close($stmtCount);
}

// Configuración de Paginación (Límite de 15 trailers)
$limit = 15;
$totalPages = ceil($totalRecords / $limit);
if ($totalPages < 1) {
    $totalPages = 1;
}

$page = isset($_GET['page']) ? $_GET['page'] : 1;
if (!filter_var($page, FILTER_VALIDATE_INT) || $page < 1) {
    $page = 1;
} else {
    $page = (int)$page;
}
if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $limit;

// Ordenación coherente
$orderSql = "ORDER BY t.id_trailer DESC";
if ($start_date !== '' || $end_date !== '' || $upcoming === 1) {
    $orderSql = "ORDER BY t.release_date ASC, t.id_trailer DESC";
}

// 4. Consultar los trailers de la página actual con LIMIT y OFFSET
$sql = "SELECT t.*, 
               GROUP_CONCAT(DISTINCT g.nombre SEPARATOR ', ') as genero,
               CONCAT(d.nombre, ' ', d.apellidos) as director,
               GROUP_CONCAT(DISTINCT CONCAT(r.nombre, ' ', r.apellidos) SEPARATOR ', ') as reparto
        FROM trailers t
        LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
        LEFT JOIN generos g ON tg.id_genero = g.id_genero
        LEFT JOIN directores d ON t.id_director = d.id_director
        LEFT JOIN reparto_trailers rt ON t.id_trailer = rt.id_trailer
        LEFT JOIN reparto r ON rt.id_reparto = r.id_reparto
        $whereSql
        GROUP BY t.id_trailer
        $orderSql
        LIMIT ? OFFSET ?";

$trailers = [];
$stmtMain = mysqli_prepare($conexion, $sql);
if ($stmtMain) {
    $mainParams = $params;
    $mainParams[] = $limit;
    $mainParams[] = $offset;
    $mainParamTypes = $paramTypes . "ii";
    mysqli_stmt_bind_param($stmtMain, $mainParamTypes, ...$mainParams);
    mysqli_stmt_execute($stmtMain);
    $resMain = mysqli_stmt_get_result($stmtMain);
    while ($row = mysqli_fetch_assoc($resMain)) {
        $trailers[] = $row;
    }
    mysqli_stmt_close($stmtMain);
}

// Helper para construir las URLs de paginación conservando los filtros actuales
function buildPaginationUrl($pageNum) {
    $params = $_GET;
    $params['page'] = $pageNum;
    return 'index.php?' . http_build_query($params);
}

// 5. Consultar favoritos y vistos recientemente si está logueado
$userFavorites = [];
$recentlyViewed = [];
if (isset($_SESSION['usuario_id'])) {
    $id_usuario = $_SESSION['usuario_id'];
    
    // Favoritos
    $sqlFavs = "SELECT id_trailer FROM favoritos WHERE id_usuario = ?";
    $stmtFavs = mysqli_prepare($conexion, $sqlFavs);
    if ($stmtFavs) {
        mysqli_stmt_bind_param($stmtFavs, "i", $id_usuario);
        mysqli_stmt_execute($stmtFavs);
        $resFavs = mysqli_stmt_get_result($stmtFavs);
        while ($row = mysqli_fetch_assoc($resFavs)) {
            $userFavorites[] = (int)$row['id_trailer'];
        }
        mysqli_stmt_close($stmtFavs);
    }

    // Vistos recientemente (últimos 5 trailers vistos sin duplicar película)
    $sqlRecent = "SELECT t.id_trailer, t.titulo, t.poster_url, t.valoracion, t.release_date, t.duracion,
                         GROUP_CONCAT(DISTINCT g.nombre SEPARATOR ', ') as genero,
                         MAX(v.fecha_visualizacion) as ultima_vista
                  FROM visualizaciones v
                  JOIN trailers t ON v.id_trailer = t.id_trailer
                  LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
                  LEFT JOIN generos g ON tg.id_genero = g.id_genero
                  WHERE v.id_usuario = ?
                  GROUP BY t.id_trailer
                  ORDER BY ultima_vista DESC
                  LIMIT 5";
    $stmtRecent = mysqli_prepare($conexion, $sqlRecent);
    if ($stmtRecent) {
        mysqli_stmt_bind_param($stmtRecent, "i", $id_usuario);
        mysqli_stmt_execute($stmtRecent);
        $resRecent = mysqli_stmt_get_result($stmtRecent);
        while ($row = mysqli_fetch_assoc($resRecent)) {
            $recentlyViewed[] = $row;
        }
        mysqli_stmt_close($stmtRecent);
    }
}

// 6. Intentar traer los 5 trailers más próximos a estrenarse para el banner (independiente de filtros)
$sqlFeatured = "SELECT t.*, GROUP_CONCAT(DISTINCT g.nombre SEPARATOR ', ') as genero,
                       CONCAT(d.nombre, ' ', d.apellidos) as director
                FROM trailers t
                LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
                LEFT JOIN generos g ON tg.id_genero = g.id_genero
                LEFT JOIN directores d ON t.id_director = d.id_director
                WHERE t.release_date >= CURDATE()
                GROUP BY t.id_trailer
                ORDER BY t.release_date ASC
                LIMIT 5";
$resFeatured = mysqli_query($conexion, $sqlFeatured);
$featuredTrailers = [];
while ($row = mysqli_fetch_assoc($resFeatured)) {
    $featuredTrailers[] = $row;
}
mysqli_free_result($resFeatured);

// Si no hay suficientes próximos a estrenarse, rellenar con los últimos agregados
if (count($featuredTrailers) < 5) {
    $needed = 5 - count($featuredTrailers);
    $excludeIds = !empty($featuredTrailers) ? implode(',', array_column($featuredTrailers, 'id_trailer')) : '0';
    $sqlFallback = "SELECT t.*, GROUP_CONCAT(DISTINCT g.nombre SEPARATOR ', ') as genero,
                           CONCAT(d.nombre, ' ', d.apellidos) as director
                    FROM trailers t
                    LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
                    LEFT JOIN generos g ON tg.id_genero = g.id_genero
                    LEFT JOIN directores d ON t.id_director = d.id_director
                    WHERE t.id_trailer NOT IN ($excludeIds)
                    GROUP BY t.id_trailer
                    ORDER BY t.id_trailer DESC
                    LIMIT $needed";
    $resFallback = mysqli_query($conexion, $sqlFallback);
    while ($row = mysqli_fetch_assoc($resFallback)) {
        $featuredTrailers[] = $row;
    }
    mysqli_free_result($resFallback);
}

mysqli_close($conexion);
?>
<?php
$pageTitle = "Movie Trailer Hub - Stitch Edition";
$rootPath = "./";
require_once $rootPath . 'includes/navbar.php';
?>

<main class="app-container">

    <!-- Notificaciones eliminadas de aquí (ahora se renderizan como Toasts abajo a la derecha) -->

    <!-- Banner Destacado (Hero) / Carrusel -->
    <?php if (!empty($featuredTrailers)): ?>
        <section class="hero">
            <div class="carousel-container" id="heroCarousel">
                <div class="carousel-track">
                    <?php foreach ($featuredTrailers as $index => $item): ?>
                        <div class="carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-slide-index="<?php echo $index; ?>">
                            <img src="<?= htmlspecialchars($item['poster_url'] ?? 'https://images.unsplash.com/photo-1534447677768-be436bb09401?q=80&w=600') ?>" alt="<?= htmlspecialchars($item['titulo']) ?>" class="hero-banner">
                            <div class="hero-overlay">
                                <span class="hero-tag">Estreno Próximo</span>
                                <h2 class="hero-title"><?= htmlspecialchars($item['titulo']) ?></h2>
                                <p class="hero-desc"><?= htmlspecialchars($item['sinopsis'] ?? 'Sin descripción disponible.') ?></p>
                                <div class="hero-meta">
                                    <span><i class="fa-solid fa-film"></i> <?= htmlspecialchars($item['genero']) ?></span>
                                    <span><i class="fa-solid fa-calendar"></i> <?= date('d/m/Y', strtotime($item['release_date'])) ?></span>
                                    <span><i class="fa-solid fa-star"></i> <?= htmlspecialchars((string)$item['valoracion']) ?>/10</span>
                                    <span><i class="fa-solid fa-clock"></i> <?= htmlspecialchars((string)$item['duracion']) ?> min</span>
                                </div>
                                <div>
                                    <a href="trailers/reproducir_trailer.php?id=<?= $item['id_trailer'] ?>" class="btn btn-primary">
                                        <i class="fa-solid fa-play"></i> Reproducir Trailer
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Carousel Indicators / Dots -->
                <div class="carousel-dots">
                    <?php foreach ($featuredTrailers as $index => $item): ?>
                        <button class="carousel-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-dot-index="<?php echo $index; ?>" aria-label="Diapositiva <?php echo $index + 1; ?>"></button>
                    <?php endforeach; ?>
                </div>

                <!-- Carousel Nav Arrows -->
                <button type="button" class="carousel-control prev" id="carouselPrevBtn" aria-label="Anterior">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <button type="button" class="carousel-control next" id="carouselNextBtn" aria-label="Siguiente">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </section>
    <?php else: ?>
        <section class="hero hero-empty">
            <div class="hero-overlay">
                <i class="fa-solid fa-video-slash hero-empty-icon"></i>
                <h2 class="hero-title">No hay trailers registrados</h2>
                <p class="hero-desc">¡Empieza añadiendo tu primer trailer usando el botón superior!</p>
            </div>
        </section>
    <?php endif; ?>

    <!-- Sección Vistos Recientemente -->
    <?php if (!empty($recentlyViewed)): ?>
        <section class="recently-viewed-section" style="margin-top: 30px; margin-bottom: 30px;">
            <div class="catalog-title-wrapper" style="margin-bottom: 16px;">
                <h2 class="section-title"><i class="fa-solid fa-clock-rotate-left"></i> Visto Recientemente</h2>
            </div>
            <div class="recommendations-grid">
                <?php foreach ($recentlyViewed as $recent): ?>
                    <article class="movie-card" style="display: flex; flex-direction: column;">
                        <a class="movie-poster-container" href="trailers/reproducir_trailer.php?id=<?= $recent['id_trailer'] ?>">
                            <img src="<?= htmlspecialchars($recent['poster_url'] ?? 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=600') ?>" alt="<?= htmlspecialchars($recent['titulo']) ?>" class="movie-poster">

                            <div class="card-play-overlay">
                                <div class="play-icon-circle">
                                    <i class="fa-solid fa-play"></i>
                                </div>
                            </div>

                            <div class="rating-badge">
                                <i class="fa-solid fa-star"></i>
                                <span><?= htmlspecialchars((string)$recent['valoracion']) ?></span>
                            </div>

                            <div class="genre-badge">
                                <?= htmlspecialchars($recent['genero']) ?>
                            </div>
                        </a>

                        <div class="movie-info" style="display: flex; flex-direction: column; flex-grow: 1; padding: 12px;">
                            <h3 class="movie-title" style="font-size: 14px; margin-bottom: 4px;"><?= htmlspecialchars($recent['titulo']) ?></h3>
                            <div class="movie-meta-row" style="font-size: 11px; margin-bottom: 8px;">
                                <span><i class="fa-regular fa-calendar"></i> <?= date('Y', strtotime($recent['release_date'])) ?></span>
                                <span><i class="fa-regular fa-clock"></i> <?= htmlspecialchars((string)$recent['duracion']) ?> min</span>
                            </div>
                            <div class="movie-actions" style="margin-top: auto; padding-top: 8px;">
                                <a class="btn btn-secondary" href="trailers/reproducir_trailer.php?id=<?= $recent['id_trailer'] ?>" style="width: 100%; justify-content: center; font-size: 11px; padding: 6px 10px;">
                                    <i class="fa-solid fa-play"></i> Ver Ficha
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Sección de Filtros y Búsqueda -->
    <section class="search-filter-section">
        <div class="search-bar-container">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon" style="cursor: pointer;"></i>
                <input type="text" id="searchInput" class="search-input" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por título, descripción, actor/actriz o director...">
            </div>
            <div class="date-filter-container">
                <i class="fa-solid fa-calendar-days"></i>
                <span>Desde:</span>
                <input type="date" id="dateStart" class="search-input" value="<?= htmlspecialchars($start_date) ?>" placeholder="Fecha inicio">
                <span>Hasta:</span>
                <input type="date" id="dateEnd" class="search-input" value="<?= htmlspecialchars($end_date) ?>" placeholder="Fecha fin">
                <button type="button" id="clearDateBtn" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> Limpiar</button>
            </div>
        </div>

        <div class="filters-container">
            <span class="filter-label">Filtrar por género:</span>
            <button class="genre-tag <?php echo empty($selectedGenres) ? 'active' : ''; ?>" data-genre="Todos">Todos</button>
            <?php foreach ($genres as $g): ?>
                <button class="genre-tag <?php echo in_array($g, $selectedGenres) ? 'active' : ''; ?>" data-genre="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></button>
            <?php endforeach; ?>
        </div>

        <div class="upcoming-filter-container">
            <input type="checkbox" id="upcomingFilter" <?php echo $upcoming === 1 ? 'checked' : ''; ?>>
            <label for="upcomingFilter">Próximos Estrenos (Mostrar solo lanzamientos futuros ordenados cronológicamente)</label>
        </div>
    </section>

    <!-- Listado en Grilla de Películas -->
    <div class="catalog-title-wrapper">
        <h2 class="section-title">Catálogo de Películas</h2>
    </div>

    <section class="trailers-grid" id="trailersGrid">
        <?php foreach ($trailers as $trailer): ?>
            <article class="movie-card"
                data-id="<?= htmlspecialchars((string)$trailer['id_trailer']) ?>"
                data-title="<?= htmlspecialchars($trailer['titulo']) ?>"
                data-synopsis="<?= htmlspecialchars($trailer['sinopsis'] ?? '') ?>"
                data-director="<?= htmlspecialchars($trailer['director'] ?? '') ?>"
                data-actors="<?= htmlspecialchars($trailer['reparto'] ?? '') ?>"
                data-genre="<?= htmlspecialchars($trailer['genero']) ?>"
                data-release-date="<?= htmlspecialchars($trailer['release_date']) ?>">

                <a class="movie-poster-container" href="trailers/reproducir_trailer.php?id=<?= $trailer['id_trailer'] ?>">
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
                        <a class="btn btn-secondary" href="trailers/reproducir_trailer.php?id=<?= $trailer['id_trailer'] ?>">
                            <i class="fa-solid fa-play"></i> Ver
                        </a>

                        <?php if (isset($_SESSION['usuario_id'])): ?>
                            <?php if (in_array((int)$trailer['id_trailer'], $userFavorites)): ?>
                                <a class="btn btn-secondary btn-toggle-favorito btn-active-favorito" href="trailers/toggle_favorito.php?id=<?= $trailer['id_trailer'] ?>" data-id="<?= $trailer['id_trailer'] ?>" title="Quitar de favoritos">
                                    <i class="fa-solid fa-heart"></i>
                                </a>
                            <?php else: ?>
                                <a class="btn btn-secondary btn-toggle-favorito" href="trailers/toggle_favorito.php?id=<?= $trailer['id_trailer'] ?>" data-id="<?= $trailer['id_trailer'] ?>" title="Añadir a favoritos">
                                    <i class="fa-regular fa-heart"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'editor')): ?>
                                <a class="btn btn-secondary btn-modificar" href="trailers/modificar_trailer.php?id=<?= $trailer['id_trailer'] ?>">
                                    <i class="fa-solid fa-pen-to-square"></i> Editar
                                </a>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                                <a class="btn btn-danger btn-eliminar" href="trailers/eliminar_trailer.php?id=<?= $trailer['id_trailer'] ?>" onclick="return confirm('¿Estás seguro de que deseas eliminar este trailer?');">
                                    <i class="fa-solid fa-trash"></i> Borrar
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <!-- Contenedor de Paginación -->
    <div id="paginationContainer" class="pagination-container">
        <?php if ($totalPages > 1): ?>
            <!-- Botón Primero -->
            <?php if ($page > 1): ?>
                <a href="<?= buildPaginationUrl(1) ?>" class="btn btn-secondary" style="padding: 8px 16px;" title="Ir al principio">
                    <i class="fa-solid fa-angles-left"></i>
                </a>
            <?php else: ?>
                <button class="btn btn-secondary" style="padding: 8px 16px;" disabled>
                    <i class="fa-solid fa-angles-left"></i>
                </button>
            <?php endif; ?>

            <!-- Botón Anterior -->
            <?php if ($page > 1): ?>
                <a href="<?= buildPaginationUrl($page - 1) ?>" class="btn btn-secondary" style="padding: 8px 16px;" title="Anterior">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
            <?php else: ?>
                <button class="btn btn-secondary" style="padding: 8px 16px;" disabled>
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
            <?php endif; ?>

            <!-- Números de página con elisión -->
            <?php
            $pagesToRender = [1, 2, 3];
            if ($page > 3 && $page < $totalPages - 2) {
                $pagesToRender[] = $page - 1;
                $pagesToRender[] = $page;
                $pagesToRender[] = $page + 1;
            }
            $pagesToRender[] = $totalPages - 2;
            $pagesToRender[] = $totalPages - 1;
            $pagesToRender[] = $totalPages;

            // Limpiar valores fuera de rango (por ejemplo, si hay menos de 6 páginas totales)
            $pagesToRender = array_filter($pagesToRender, function($p) use ($totalPages) {
                return $p >= 1 && $p <= $totalPages;
            });
            $pagesToRender = array_unique($pagesToRender);
            sort($pagesToRender);

            $lastPagePrinted = 0;
            foreach ($pagesToRender as $p):
                if ($lastPagePrinted > 0 && $p - $lastPagePrinted > 1):
            ?>
                <span class="pagination-ellipsis">...</span>
            <?php
                endif;
            ?>
                <a href="<?= buildPaginationUrl($p) ?>" class="pagination-num-btn <?= $page === $p ? 'active' : '' ?>">
                    <?= $p ?>
                </a>
            <?php
                $lastPagePrinted = $p;
            endforeach;
            ?>

            <!-- Botón Siguiente -->
            <?php if ($page < $totalPages): ?>
                <a href="<?= buildPaginationUrl($page + 1) ?>" class="btn btn-secondary" style="padding: 8px 16px;" title="Siguiente">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <button class="btn btn-secondary" style="padding: 8px 16px;" disabled>
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            <?php endif; ?>

            <!-- Botón Último -->
            <?php if ($page < $totalPages): ?>
                <a href="<?= buildPaginationUrl($totalPages) ?>" class="btn btn-secondary" style="padding: 8px 16px;" title="Ir al final">
                    <i class="fa-solid fa-angles-right"></i>
                </a>
            <?php else: ?>
                <button class="btn btn-secondary" style="padding: 8px 16px;" disabled>
                    <i class="fa-solid fa-angles-right"></i>
                </button>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Sin Resultados -->
    <div id="emptyState" class="empty-state" style="display: <?= empty($trailers) ? 'flex' : 'none' ?>;">
        <i class="fa-solid fa-magnifying-glass-minus empty-icon"></i>
        <h3 class="empty-title">Sin Resultados</h3>
        <p>No se encontraron películas con los criterios indicados.</p>
    </div>

</main>

<!-- Script de búsqueda y filtrado interactivo -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('searchInput');
        const genreTags = document.querySelectorAll('.genre-tag');
        const upcomingFilter = document.getElementById('upcomingFilter');
        const dateStart = document.getElementById('dateStart');
        const dateEnd = document.getElementById('dateEnd');
        const clearDateBtn = document.getElementById('clearDateBtn');

        function updateFilters() {
            const urlParams = new URLSearchParams();
            
            const searchVal = searchInput.value.trim();
            if (searchVal !== '') {
                urlParams.set('search', searchVal);
            }

            const activeGenreTags = document.querySelectorAll('.genre-tag.active');
            activeGenreTags.forEach(tag => {
                const genreVal = tag.getAttribute('data-genre');
                if (genreVal !== 'Todos' && genreVal !== '') {
                    urlParams.append('genres[]', genreVal);
                }
            });

            const dateStartVal = dateStart.value;
            if (dateStartVal !== '') {
                urlParams.set('start_date', dateStartVal);
            }

            const dateEndVal = dateEnd.value;
            if (dateEndVal !== '') {
                urlParams.set('end_date', dateEndVal);
            }

            if (upcomingFilter.checked) {
                urlParams.set('upcoming', '1');
            }

            // Al cambiar filtros, volvemos a la página 1
            urlParams.set('page', '1');

            window.location.href = 'index.php?' + urlParams.toString();
        }

        // Buscador: debounce de 800ms
        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(updateFilters, 800);
        });

        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                clearTimeout(searchTimeout);
                updateFilters();
            }
        });

        // Icono de lupa interactivo
        const searchIcon = document.querySelector('.search-icon');
        if (searchIcon) {
            searchIcon.style.cursor = 'pointer';
            searchIcon.addEventListener('click', () => {
                clearTimeout(searchTimeout);
                updateFilters();
            });
        }

        // Mantener el cursor al escribir después de recargar
        if (searchInput.value !== '') {
            searchInput.focus();
            const length = searchInput.value.length;
            searchInput.setSelectionRange(length, length);
        }

        // Tags de Género
        genreTags.forEach(tag => {
            tag.addEventListener('click', () => {
                const genreVal = tag.getAttribute('data-genre');
                if (genreVal === 'Todos') {
                    genreTags.forEach(t => t.classList.remove('active'));
                    tag.classList.add('active');
                } else {
                    const todosTag = document.querySelector('.genre-tag[data-genre="Todos"]');
                    if (todosTag) todosTag.classList.remove('active');
                    
                    tag.classList.toggle('active');
                    
                    const activeTags = document.querySelectorAll('.genre-tag.active');
                    if (activeTags.length === 0 && todosTag) {
                        todosTag.classList.add('active');
                    }
                }
                updateFilters();
            });
        });

        // Rango de Fechas
        dateStart.addEventListener('change', updateFilters);
        dateEnd.addEventListener('change', updateFilters);

        clearDateBtn.addEventListener('click', () => {
            dateStart.value = '';
            dateEnd.value = '';
            updateFilters();
        });

        // Checkbox Próximos Estrenos
        upcomingFilter.addEventListener('change', updateFilters);

        // Scroll inteligente según interacción
        const urlParams = new URLSearchParams(window.location.search);
        if (window.location.search !== '') {
            if (urlParams.has('search')) {
                const searchSection = document.querySelector('.search-filter-section');
                if (searchSection) {
                    searchSection.scrollIntoView({ behavior: 'auto', block: 'start' });
                }
            } else if (urlParams.has('page') || urlParams.has('genre') || urlParams.has('genres[]') || urlParams.has('start_date') || urlParams.has('end_date') || urlParams.has('upcoming')) {
                const catalogTitle = document.querySelector('.catalog-title-wrapper');
                if (catalogTitle) {
                    catalogTitle.scrollIntoView({ behavior: 'auto', block: 'start' });
                }
            }
        }

        // --- Lógica del Carrusel del Hero ---
        const carousel = document.getElementById('heroCarousel');
        if (carousel) {
            const slides = carousel.querySelectorAll('.carousel-slide');
            const dots = carousel.querySelectorAll('.carousel-dot');
            const prevBtn = document.getElementById('carouselPrevBtn');
            const nextBtn = document.getElementById('carouselNextBtn');
            let currentIndex = 0;
            let autoplayTimer = null;

            function showSlide(index) {
                if (slides.length === 0) return;

                // Asegurar límites circulares
                if (index >= slides.length) {
                    currentIndex = 0;
                } else if (index < 0) {
                    currentIndex = slides.length - 1;
                } else {
                    currentIndex = index;
                }

                // Actualizar clases active en slides y dots
                slides.forEach((slide, i) => {
                    if (i === currentIndex) {
                        slide.classList.add('active');
                    } else {
                        slide.classList.remove('active');
                    }
                });

                dots.forEach((dot, i) => {
                    if (i === currentIndex) {
                        dot.classList.add('active');
                    } else {
                        dot.classList.remove('active');
                    }
                });
            }

            function nextSlide() {
                showSlide(currentIndex + 1);
            }

            function prevSlide() {
                showSlide(currentIndex - 1);
            }

            function startAutoplay() {
                stopAutoplay();
                autoplayTimer = setInterval(nextSlide, 5000); // Rotar cada 5 segundos
            }

            function stopAutoplay() {
                if (autoplayTimer) {
                    clearInterval(autoplayTimer);
                    autoplayTimer = null;
                }
            }

            // Eventos de botones
            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    nextSlide();
                    startAutoplay(); // Resetear temporizador al interactuar
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    prevSlide();
                    startAutoplay(); // Resetear temporizador al interactuar
                });
            }

            // Eventos de indicadores (dots)
            dots.forEach(dot => {
                dot.addEventListener('click', () => {
                    const index = parseInt(dot.getAttribute('data-dot-index'));
                    showSlide(index);
                    startAutoplay(); // Resetear temporizador al interactuar
                });
            });

            // Pausar autoplay al pasar el ratón por encima del carrusel
            carousel.addEventListener('mouseenter', stopAutoplay);
            carousel.addEventListener('mouseleave', startAutoplay);

            // Inicializar autoplay
            startAutoplay();
        }

        // Inicializar paginación al cargar
        filterMovies(true);
    });
</script>

<?php
require_once $rootPath . 'includes/footer.php';
?>