<?php
require_once "../config/conexion.php";
define('BASE_PATH', '../');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Query director details
$sqlDirector = "SELECT * FROM directores WHERE id_director = ? LIMIT 1";
$stmtDirector = mysqli_prepare($conexion, $sqlDirector);
mysqli_stmt_bind_param($stmtDirector, "i", $id);
mysqli_stmt_execute($stmtDirector);
$resDirector = mysqli_stmt_get_result($stmtDirector);
$director = mysqli_fetch_assoc($resDirector);
mysqli_stmt_close($stmtDirector);

if (!$director) {
    echo "<h1>Director no encontrado</h1>";
    exit;
}

// Query movies they directed
$sqlMovies = "SELECT t.*, GROUP_CONCAT(g.nombre SEPARATOR ', ') as genero,
                     COALESCE((SELECT ROUND(AVG(valoracion), 1) FROM resenas WHERE id_trailer = t.id_trailer), 0) as promedio_resenas
              FROM trailers t
              LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
              LEFT JOIN generos g ON tg.id_genero = g.id_genero
              WHERE t.id_director = ?
              GROUP BY t.id_trailer
              ORDER BY t.release_date DESC";
$stmtMovies = mysqli_prepare($conexion, $sqlMovies);
mysqli_stmt_bind_param($stmtMovies, "i", $id);
mysqli_stmt_execute($stmtMovies);
$resMovies = mysqli_stmt_get_result($stmtMovies);
$movies = [];
while ($row = mysqli_fetch_assoc($resMovies)) {
    $movies[] = $row;
}
mysqli_stmt_close($stmtMovies);
mysqli_close($conexion);
?>
<?php
$pageTitle = "Director: " . $director['nombre'] . ' ' . $director['apellidos'];
$rootPath = "../";
require $rootPath . 'includes/navbar.php';
?>
    <main class="app-container">
        <h1 style="text-align:center;">Perfil del Director</h1>
        <p style="text-align:center;">Conoce la trayectoria de este cineasta en nuestra plataforma.</p>

        <div class="actor-profile-card">
            <div class="director-avatar-placeholder">
                <i class="fa-solid fa-clapperboard"></i>
            </div>
            <div class="actor-details">
                <h2><?php echo htmlspecialchars($director['nombre'] . ' ' . $director['apellidos']); ?></h2>
                <div class="actor-meta">
                    <span>Edad: <strong><?php echo $director['edad'] ? htmlspecialchars((string)$director['edad']) . ' años' : 'Desconocida'; ?></strong></span>
                    <span>País: <strong><?php echo htmlspecialchars($director['pais'] !== '' ? $director['pais'] : 'No especificado'); ?></strong></span>
                </div>
            </div>
        </div>

        <div class="movies-list-container">
            <h2 class="actor-filmography-title">Filmografía Dirigida (<?php echo count($movies); ?>)</h2>
            
            <?php if (!empty($movies)): ?>
                <?php foreach ($movies as $movie): ?>
                    <a class="movie-row-card" href="reproducir_trailer.php?id=<?php echo $movie['id_trailer']; ?>">
                        <div class="movie-row-left">
                            <img src="<?php echo htmlspecialchars($movie['poster_url'] ?? 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=200'); ?>" alt="Poster" class="movie-row-poster">
                            <div class="movie-row-info">
                                <h3><?php echo htmlspecialchars($movie['titulo']); ?></h3>
                                <p>Director Principal</p>
                            </div>
                        </div>
                        <div class="movie-row-right">
                            <div class="movie-row-meta">
                                <span class="rating" style="display: inline-flex; flex-direction: column; gap: 2px; align-items: flex-start; line-height: 1.2;">
                                    <span>⭐ TMDB: <?php echo htmlspecialchars((string)$movie['valoracion']); ?>/10</span>
                                    <?php if (isset($movie['promedio_resenas']) && $movie['promedio_resenas'] > 0): ?>
                                        <span><i class="fa-solid fa-comments"></i> Comunidad: <?php echo htmlspecialchars((string)$movie['promedio_resenas']); ?>/5</span>
                                    <?php endif; ?>
                                </span>
                                <span>📅 Estreno: <?php echo date('d/m/Y', strtotime($movie['release_date'])); ?></span>
                                <span>🎬 Género: <?php echo htmlspecialchars($movie['genero']); ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alerta">
                    <p>Este director aún no tiene ningún trailer asociado en el catálogo.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <a class="volver" href="../index.php">← Volver al inicio</a>
<?php
require $rootPath . 'includes/footer.php';
?>
