<?php
require_once "../config/conexion.php";
define('BASE_PATH', '../');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Query actor details
$sqlActor = "SELECT * FROM reparto WHERE id_reparto = ? LIMIT 1";
$stmtActor = mysqli_prepare($conexion, $sqlActor);
mysqli_stmt_bind_param($stmtActor, "i", $id);
mysqli_stmt_execute($stmtActor);
$resActor = mysqli_stmt_get_result($stmtActor);
$actor = mysqli_fetch_assoc($resActor);
mysqli_stmt_close($stmtActor);

if (!$actor) {
    echo "<h1>Actor/Actriz no encontrado</h1>";
    exit;
}

// Query movies they appeared in
$sqlMovies = "SELECT t.*, rt.personaje, GROUP_CONCAT(g.nombre SEPARATOR ', ') as genero
              FROM reparto_trailers rt
              JOIN trailers t ON rt.id_trailer = t.id_trailer
              LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
              LEFT JOIN generos g ON tg.id_genero = g.id_genero
              WHERE rt.id_reparto = ?
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
$pageTitle = "Filmografía: " . $actor['nombre'] . ' ' . $actor['apellidos'];
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>

    <main class="app-container">
        <h1>Perfil Artístico</h1>
        <p>Conoce la trayectoria de este miembro del elenco en nuestra plataforma.</p>

    <div class="actor-profile-card">
        <img src="<?php echo htmlspecialchars($actor['foto_url'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200'); ?>" alt="<?php echo htmlspecialchars($actor['nombre']); ?>" class="actor-avatar">
        <div class="actor-details">
            <h2><?php echo htmlspecialchars($actor['nombre'] . ' ' . $actor['apellidos']); ?></h2>
            <div class="actor-meta">
                <span>Edad: <strong><?php echo $actor['edad'] ? htmlspecialchars((string)$actor['edad']) . ' años' : 'Desconocida'; ?></strong></span>
                <span>País: <strong><?php echo htmlspecialchars($actor['pais'] !== '' ? $actor['pais'] : 'No especificado'); ?></strong></span>
            </div>
        </div>
    </div>

    <div class="movies-list-container">
        <h2 class="actor-filmography-title">Filmografía Registrada (<?php echo count($movies); ?>)</h2>
        
        <?php if (!empty($movies)): ?>
            <?php foreach ($movies as $movie): ?>
                <div class="movie-row-card" onclick="location.href='reproducir_trailer.php?id=<?php echo $movie['id_trailer']; ?>'">
                    <div class="movie-row-left">
                        <img src="<?php echo htmlspecialchars($movie['poster_url'] ?? 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=200'); ?>" alt="Poster" class="movie-row-poster">
                        <div class="movie-row-info">
                            <h3><?php echo htmlspecialchars($movie['titulo']); ?></h3>
                            <p>Interpreta a: <strong><?php echo htmlspecialchars($movie['personaje'] !== '' ? $movie['personaje'] : 'N/A'); ?></strong></p>
                        </div>
                    </div>
                    <div class="movie-row-right">
                        <div class="movie-row-meta">
                            <span class="rating">⭐ <?php echo htmlspecialchars((string)$movie['valoracion']); ?>/10</span>
                            <span>📅 Estreno: <?php echo date('d/m/Y', strtotime($movie['release_date'])); ?></span>
                            <span>🎬 Género: <?php echo htmlspecialchars($movie['genero']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alerta">
                <p>Este actor/actriz aún no ha sido asociado a ningún trailer en el catálogo.</p>
            </div>
        <?php endif; ?>
    </div>

    </main>

    <a class="volver" href="../index.php">← Volver al inicio</a>
</body>
</html>
