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

// Consultar favoritos si está logueado
$userFavorites = [];
if (isset($_SESSION['usuario_id'])) {
    $id_usuario = $_SESSION['usuario_id'];
    $sqlFavs = "SELECT id_trailer FROM favoritos WHERE id_usuario = ?";
    $stmtFavs = mysqli_prepare($conexion, $sqlFavs);
    mysqli_stmt_bind_param($stmtFavs, "i", $id_usuario);
    mysqli_stmt_execute($stmtFavs);
    $resFavs = mysqli_stmt_get_result($stmtFavs);
    while ($row = mysqli_fetch_assoc($resFavs)) {
        $userFavorites[] = (int)$row['id_trailer'];
    }
    mysqli_stmt_close($stmtFavs);
}

// 1. Intentar traer los 5 trailers más próximos a estrenarse (fecha >= hoy)
$sqlFeatured = "SELECT t.*, GROUP_CONCAT(g.nombre SEPARATOR ', ') as genero
                FROM trailers t
                LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
                LEFT JOIN generos g ON tg.id_genero = g.id_genero
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

// 2. Si no hay suficientes próximos a estrenarse, rellenar con los últimos agregados
if (count($featuredTrailers) < 5) {
    $needed = 5 - count($featuredTrailers);
    $excludeIds = !empty($featuredTrailers) ? implode(',', array_column($featuredTrailers, 'id_trailer')) : '0';
    $sqlFallback = "SELECT t.*, GROUP_CONCAT(g.nombre SEPARATOR ', ') as genero
                    FROM trailers t
                    LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
                    LEFT JOIN generos g ON tg.id_genero = g.id_genero
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
                <a href="trailers/estadisticas.php" class="btn btn-secondary">
                    <i class="fa-solid fa-chart-simple"></i> Estadísticas
                </a>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <span class="user-greeting" style="font-size: 14px; font-weight: 600; color: #ffffff; margin-right: 8px;">
                        <i class="fa-solid fa-circle-user" style="color: var(--primary); margin-right: 5px;"></i>Hola, <?= htmlspecialchars($_SESSION['username']) ?>
                    </span>
                    
                    <a href="trailers/favoritos.php" class="btn btn-secondary" style="border-color: rgba(220, 38, 38, 0.3); color: var(--secondary);">
                        <i class="fa-solid fa-heart"></i> Mis Favoritos
                    </a>

                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                        <a href="trailers/añadir_reparto.php" class="btn btn-secondary">
                            <i class="fa-solid fa-user-plus"></i> Añadir Actor
                        </a>
                        <a href="trailers/listar_trailers.php" class="btn btn-secondary">
                            <i class="fa-solid fa-list"></i> Administrar
                        </a>
                        <a href="trailers/añadir_trailer.php" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Añadir
                        </a>
                    <?php endif; ?>
                    
                    <a href="auth/logout.php" class="btn btn-secondary">
                        <i class="fa-solid fa-right-from-bracket"></i> Salir
                    </a>
                <?php else: ?>
                    <a href="auth/login.php" class="btn btn-secondary">
                        <i class="fa-solid fa-right-to-bracket"></i> Iniciar Sesión
                    </a>
                    <a href="auth/registro.php" class="btn btn-primary">
                        <i class="fa-solid fa-user-plus"></i> Registrarse
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

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
                            
                            <?php if (isset($_SESSION['usuario_id'])): ?>
                                <?php if (in_array((int)$trailer['id_trailer'], $userFavorites)): ?>
                                    <a class="btn btn-secondary" style="color: var(--secondary);" href="trailers/toggle_favorito.php?id=<?= $trailer['id_trailer'] ?>" title="Quitar de favoritos">
                                        <i class="fa-solid fa-heart"></i>
                                    </a>
                                <?php else: ?>
                                    <a class="btn btn-secondary" href="trailers/toggle_favorito.php?id=<?= $trailer['id_trailer'] ?>" title="Añadir a favoritos">
                                        <i class="fa-regular fa-heart"></i>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                                <a class="btn btn-secondary btn-modificar" style="text-transform: uppercase;" href="trailers/modificar_trailer.php?id=<?= $trailer['id_trailer'] ?>">
                                    <i class="fa-solid fa-pen-to-square"></i> Editar
                                </a>
                                <a class="btn btn-danger btn-eliminar" style="text-transform: uppercase;" href="trailers/eliminar_trailer.php?id=<?= $trailer['id_trailer'] ?>" onclick="return confirm('¿Estás seguro de que deseas eliminar este trailer?');">
                                    <i class="fa-solid fa-trash"></i> Borrar
                                </a>
                            <?php endif; ?>
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
        });

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
</body>
</html>
