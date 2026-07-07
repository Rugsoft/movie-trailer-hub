<?php
include "config/conexion.php";
define('BASE_PATH', '');

// Mensajes de éxito o error redireccionados
$successMsg = $_SESSION['success'] ?? null;
$errorMsg = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Consultar todos los trailers con sus géneros
$sql = "SELECT t.*, GROUP_CONCAT(g.nombre SEPARATOR ', ') as genero
        FROM trailers t
        LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
        LEFT JOIN generos g ON tg.id_genero = g.id_genero
        GROUP BY t.id_trailer
        ORDER BY t.id_trailer DESC";
$res = mysqli_query($conexion, $sql);
$trailers = [];
while ($row = mysqli_fetch_assoc($res)) {
    $trailers[] = $row;
}
mysqli_free_result($res);

// Consultar géneros de la tabla generos
$sqlGenres = "SELECT nombre FROM generos ORDER BY nombre ASC";
$resGenres = mysqli_query($conexion, $sqlGenres);
$genres = [];
while ($row = mysqli_fetch_assoc($resGenres)) {
    $genres[] = $row['nombre'];
}
mysqli_free_result($resGenres);
mysqli_close($conexion);

// Destacado: primer trailer
$featured = !empty($trailers) ? $trailers[0] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Trailer Hub - Stitch Edition</title>
    
    <meta name="description" content="Guarda, organiza y disfruta de los mejores trailers de tus películas favoritas. Tu hub centralizado de cine.">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

    <!-- Navegación principal -->
    <header class="navbar">
        <div class="app-container navbar-content">
            <a href="index.php" class="brand">
                <i class="fa-solid fa-clapperboard brand-icon"></i>
                <h1 class="brand-name">Movie Trailer Hub</h1>
            </a>
            <div class="nav-actions">
                <a href="trailers/listar_trailers.php" class="btn btn-secondary">
                    <i class="fa-solid fa-list"></i> Administrar
                </a>
                <a href="trailers/añadir_trailer.php" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Añadir
                </a>
            </div>
        </div>
    </header>

    <main class="app-container">

        <!-- Notificaciones -->
        <div class="alerts-container">
            <?php if ($successMsg): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?= htmlspecialchars($successMsg) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($errorMsg) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Banner Destacado (Hero) -->
        <?php if ($featured): ?>
            <section class="hero">
                <img src="<?= htmlspecialchars($featured['poster_url'] ?? 'https://images.unsplash.com/photo-1534447677768-be436bb09401?q=80&w=600') ?>" alt="Banner destacado" class="hero-banner">
                <div class="hero-overlay">
                    <span class="hero-tag">Destacado</span>
                    <h2 class="hero-title"><?= htmlspecialchars($featured['titulo']) ?></h2>
                    <p class="hero-desc"><?= htmlspecialchars($featured['sinopsis'] ?? 'Sin descripción disponible.') ?></p>
                    <div class="hero-meta">
                        <span><i class="fa-solid fa-film"></i> <?= htmlspecialchars($featured['genero']) ?></span>
                        <span><i class="fa-solid fa-calendar"></i> <?= date('d/m/Y', strtotime($featured['release_date'])) ?></span>
                        <span><i class="fa-solid fa-star"></i> <?= htmlspecialchars((string)$featured['valoracion']) ?>/10</span>
                        <span><i class="fa-solid fa-clock"></i> <?= htmlspecialchars((string)$featured['duracion']) ?> min</span>
                    </div>
                    <div>
                        <a href="trailers/reproducir_trailer.php?id=<?= $featured['id_trailer'] ?>" class="btn btn-primary">
                            <i class="fa-solid fa-play"></i> Reproducir Trailer
                        </a>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <section class="hero" style="background: linear-gradient(135deg, var(--bg-surface) 0%, var(--bg-surface-lowest) 100%);">
                <div class="hero-overlay" style="align-items: center; justify-content: center; text-align: center; height: 100%;">
                    <i class="fa-solid fa-video-slash" style="font-size: 64px; color: var(--primary); margin-bottom: 20px;"></i>
                    <h2 class="hero-title">No hay trailers registrados</h2>
                    <p class="hero-desc">¡Empieza añadiendo tu primer trailer usando el botón superior!</p>
                </div>
            </section>
        <?php endif; ?>

        <!-- Sección de Filtros y Búsqueda -->
        <section class="search-filter-section">
            <div class="search-bar-container" style="display: flex; gap: 14px; flex-wrap: wrap;">
                <div style="position: relative; flex: 2; min-width: 300px;">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="Buscar por título, descripción o director...">
                </div>
                <div class="date-filter-container" style="display: flex; align-items: center; gap: 10px; flex: 2; min-width: 300px; background: var(--bg-surface-lowest); padding: 6px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                    <i class="fa-solid fa-calendar-days" style="color: var(--text-muted); font-size: 16px; margin-right: 4px;"></i>
                    <span style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Desde:</span>
                    <input type="date" id="dateStart" class="search-input" style="padding: 6px 10px; min-width: 120px; border: none; background: transparent; height: auto; box-shadow: none;" placeholder="Fecha inicio">
                    <span style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Hasta:</span>
                    <input type="date" id="dateEnd" class="search-input" style="padding: 6px 10px; min-width: 120px; border: none; background: transparent; height: auto; box-shadow: none;" placeholder="Fecha fin">
                    <button type="button" id="clearDateBtn" class="btn btn-secondary" style="padding: 8px 12px; font-size: 11px; margin-left: auto;"><i class="fa-solid fa-xmark"></i> Limpiar</button>
                </div>
            </div>
            
            <div class="filters-container">
                <span class="filter-label">Filtrar por género:</span>
                <button class="genre-tag active" data-genre="Todos">Todos</button>
                <?php foreach ($genres as $genre): ?>
                    <button class="genre-tag" data-genre="<?= htmlspecialchars($genre) ?>"><?= htmlspecialchars($genre) ?></button>
                <?php endforeach; ?>
            </div>

            <div class="upcoming-filter-container" style="display: flex; align-items: center; gap: 8px; margin-top: 14px; border-top: 1px solid var(--border-color); padding-top: 14px; width: 100%;">
                <input type="checkbox" id="upcomingFilter" style="width: auto; min-width: auto; height: auto; cursor: pointer; transform: scale(1.25); accent-color: var(--primary);">
                <label for="upcomingFilter" style="font-size: 13px; color: var(--text-primary); font-weight: 600; cursor: pointer; user-select: none; margin: 0;">Próximos Estrenos (Mostrar solo lanzamientos futuros ordenados cronológicamente)</label>
            </div>
        </section>

        <!-- Listado en Grilla de Películas -->
        <div style="margin-bottom: 24px;">
            <h2 class="section-title" style="border: none; margin: 0; padding: 0;">Catálogo de Películas</h2>
        </div>

        <section class="trailers-grid" id="trailersGrid">
            <?php foreach ($trailers as $trailer): ?>
                <article class="movie-card" 
                         data-id="<?= htmlspecialchars((string)$trailer['id_trailer']) ?>"
                         data-title="<?= htmlspecialchars($trailer['titulo']) ?>"
                         data-synopsis="<?= htmlspecialchars($trailer['sinopsis'] ?? '') ?>"
                         data-director="<?= htmlspecialchars($trailer['director'] ?? '') ?>"
                         data-genre="<?= htmlspecialchars($trailer['genero']) ?>"
                         data-release-date="<?= htmlspecialchars($trailer['release_date']) ?>">
                    
                    <div class="movie-poster-container" onclick="location.href='trailers/reproducir_trailer.php?id=<?= $trailer['id_trailer'] ?>'" style="cursor: pointer;">
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
                    </div>

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
                            <a class="btn btn-secondary btn-modificar" style="text-transform: uppercase;" href="trailers/modificar_trailer.php?id=<?= $trailer['id_trailer'] ?>">
                                <i class="fa-solid fa-pen-to-square"></i> Editar
                            </a>
                            <a class="btn btn-danger btn-eliminar" style="text-transform: uppercase;" href="trailers/eliminar_trailer.php?id=<?= $trailer['id_trailer'] ?>" onclick="return confirm('¿Estás seguro de que deseas eliminar este trailer?');">
                                <i class="fa-solid fa-trash"></i> Borrar
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <!-- Sin Resultados -->
        <div id="emptyState" class="empty-state" style="display: none;">
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
            const movieCards = document.querySelectorAll('.movie-card');
            const emptyState = document.getElementById('emptyState');
            
            const upcomingFilter = document.getElementById('upcomingFilter');
            const trailersGrid = document.getElementById('trailersGrid');
            
            // Calcular hoy en formato YYYY-MM-DD
            const localDate = new Date();
            const year = localDate.getFullYear();
            const month = String(localDate.getMonth() + 1).padStart(2, '0');
            const day = String(localDate.getDate()).padStart(2, '0');
            const today = `${year}-${month}-${day}`;
            
            let activeGenre = 'Todos';
            let searchQuery = '';
            let activeStartDate = '';
            let activeEndDate = '';

            searchInput.addEventListener('input', (e) => {
                searchQuery = e.target.value.toLowerCase().trim();
                filterMovies();
            });

            dateStart.addEventListener('change', (e) => {
                activeStartDate = e.target.value;
                filterMovies();
            });

            dateEnd.addEventListener('change', (e) => {
                activeEndDate = e.target.value;
                filterMovies();
            });

            upcomingFilter.addEventListener('change', () => {
                sortCards();
                filterMovies();
            });

            clearDateBtn.addEventListener('click', () => {
                dateStart.value = '';
                dateEnd.value = '';
                activeStartDate = '';
                activeEndDate = '';
                filterMovies();
            });

            genreTags.forEach(tag => {
                tag.addEventListener('click', () => {
                    genreTags.forEach(t => t.classList.remove('active'));
                    tag.classList.add('active');
                    activeGenre = tag.getAttribute('data-genre');
                    filterMovies();
                });
            });

            function sortCards() {
                const cardsArray = Array.from(movieCards);

                cardsArray.sort((a, b) => {
                    if (upcomingFilter.checked) {
                        const dateA = a.getAttribute('data-release-date');
                        const dateB = b.getAttribute('data-release-date');
                        return dateA.localeCompare(dateB); // Ascendente
                    } else {
                        const idA = parseInt(a.getAttribute('data-id'));
                        const idB = parseInt(b.getAttribute('data-id'));
                        return idB - idA; // Descendente por ID (orden original)
                    }
                });

                cardsArray.forEach(card => trailersGrid.appendChild(card));
            }

            function filterMovies() {
                let visibleCount = 0;
                const isUpcomingChecked = upcomingFilter.checked;

                movieCards.forEach(card => {
                    const title = card.getAttribute('data-title').toLowerCase();
                    const synopsis = card.getAttribute('data-synopsis').toLowerCase();
                    const director = card.getAttribute('data-director').toLowerCase();
                    const genre = card.getAttribute('data-genre');
                    const releaseDate = card.getAttribute('data-release-date');

                    const matchesSearch = title.includes(searchQuery) || 
                                          synopsis.includes(searchQuery) || 
                                          director.includes(searchQuery);
                                          
                    const matchesGenre = activeGenre === 'Todos' || (genre && genre.split(', ').map(g => g.trim()).includes(activeGenre));
                    
                    let matchesDate = true;
                    if (activeStartDate && releaseDate < activeStartDate) {
                        matchesDate = false;
                    }
                    if (activeEndDate && releaseDate > activeEndDate) {
                        matchesDate = false;
                    }

                    const matchesUpcoming = !isUpcomingChecked || releaseDate >= today;

                    if (matchesSearch && matchesGenre && matchesDate && matchesUpcoming) {
                        card.style.display = 'flex';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (visibleCount === 0) {
                    emptyState.style.display = 'flex';
                } else {
                    emptyState.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>
