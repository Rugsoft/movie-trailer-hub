<?php
require_once "../config/conexion.php";
define('BASE_PATH', '../');

$successMsg = $_SESSION['success'] ?? null;
$errorMsg = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// 1. Top 5 de Trailers Más Vistos
$sqlVistas = "SELECT t.id_trailer, t.titulo, t.poster_url, top.total_vistas
              FROM (
                  SELECT id_trailer, COUNT(*) AS total_vistas
                  FROM visualizaciones
                  GROUP BY id_trailer
                  ORDER BY total_vistas DESC
                  LIMIT 5
              ) top
              JOIN trailers t ON top.id_trailer = t.id_trailer
              ORDER BY top.total_vistas DESC, t.titulo ASC";
$resVistas = mysqli_query($conexion, $sqlVistas);
$topVistas = [];
while ($row = mysqli_fetch_assoc($resVistas)) {
    $topVistas[] = $row;
}
mysqli_free_result($resVistas);

// 2. Top 5 de Películas Más Favoritas
$sqlFavoritos = "SELECT t.id_trailer, t.titulo, t.poster_url, top.total_favoritos
                 FROM (
                     SELECT id_trailer, COUNT(*) AS total_favoritos
                     FROM favoritos
                     GROUP BY id_trailer
                     ORDER BY total_favoritos DESC
                     LIMIT 5
                 ) top
                 JOIN trailers t ON top.id_trailer = t.id_trailer
                 ORDER BY top.total_favoritos DESC, t.titulo ASC";
$resFavoritos = mysqli_query($conexion, $sqlFavoritos);
$topFavoritos = [];
while ($row = mysqli_fetch_assoc($resFavoritos)) {
    $topFavoritos[] = $row;
}
mysqli_free_result($resFavoritos);

// 3. Top 5 de Actores Más Populares (Vistas Acumuladas)
$sqlActores = "SELECT r.id_reparto, r.nombre, r.apellidos, r.foto_url, top.total_vistas
               FROM (
                   SELECT rt.id_reparto, COUNT(*) AS total_vistas
                   FROM visualizaciones v
                   JOIN reparto_trailers rt ON v.id_trailer = rt.id_trailer
                   GROUP BY rt.id_reparto
                   ORDER BY total_vistas DESC
                   LIMIT 5
               ) top
               JOIN reparto r ON top.id_reparto = r.id_reparto
               ORDER BY top.total_vistas DESC, r.nombre ASC, r.apellidos ASC";
$resActores = mysqli_query($conexion, $sqlActores);
$topActores = [];
while ($row = mysqli_fetch_assoc($resActores)) {
    $topActores[] = $row;
}
mysqli_free_result($resActores);

// 4. Top 5 de Géneros Más Vistos
$sqlGeneros = "SELECT g.id_genero, g.nombre, top.total_vistas
               FROM (
                   SELECT tg.id_genero, COUNT(*) AS total_vistas
                   FROM visualizaciones v
                   JOIN trailers_generos tg ON v.id_trailer = tg.id_trailer
                   GROUP BY tg.id_genero
                   ORDER BY total_vistas DESC
                   LIMIT 5
               ) top
               JOIN generos g ON top.id_genero = g.id_genero
               ORDER BY top.total_vistas DESC, g.nombre ASC";
$resGeneros = mysqli_query($conexion, $sqlGeneros);
$topGeneros = [];
while ($row = mysqli_fetch_assoc($resGeneros)) {
    $topGeneros[] = $row;
}
mysqli_free_result($resGeneros);
$topGeneros = array_slice($topGeneros, 0, 5);

// 5. Top 5 de Usuarios Más Activos
$sqlUsuarios = "SELECT u.id_usuario, u.username, top.total_vistas
                FROM (
                    SELECT id_usuario, COUNT(*) AS total_vistas
                    FROM visualizaciones
                    WHERE id_usuario IS NOT NULL
                    GROUP BY id_usuario
                    ORDER BY total_vistas DESC
                    LIMIT 5
                ) top
                JOIN usuarios u ON top.id_usuario = u.id_usuario
                ORDER BY top.total_vistas DESC, u.username ASC";
$resUsuarios = mysqli_query($conexion, $sqlUsuarios);
$topUsuarios = [];
while ($row = mysqli_fetch_assoc($resUsuarios)) {
    $topUsuarios[] = $row;
}
mysqli_free_result($resUsuarios);

mysqli_close($conexion);
?>
<?php
$pageTitle = "Estadísticas del Sitio";
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>

    <main class="app-container" style="margin-top: 30px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="margin-bottom: 8px;">Estadísticas del Sitio</h1>
            <p style="color: var(--text-muted); margin: 0;">Los tops y datos de reproducción de nuestra comunidad cinematográfica.</p>
        </div>

        <div class="stats-grid">
            <!-- 1. TOP 5 TRAILERS MÁS VISTOS -->
            <div class="stats-card">
                <div class="stats-card-header">
                    <i class="fa-solid fa-fire"></i>
                    <h2 class="stats-card-title">Trailers Más Vistos</h2>
                </div>
                <ul class="stats-list">
                    <?php if (!empty($topVistas)): ?>
                        <?php foreach ($topVistas as $index => $item): ?>
                            <li class="stats-item">
                                <span class="stats-rank stats-rank-<?= $index + 1 ?>"><?= $index + 1 ?></span>
                                <img class="stats-poster" src="<?= htmlspecialchars($item['poster_url'] ?? 'https://images.unsplash.com/photo-1534447677768-be436bb09401?q=80&w=200') ?>" alt="Poster" loading="lazy" decoding="async">
                                <div class="stats-info">
                                    <span class="stats-name"><?= htmlspecialchars($item['titulo']) ?></span>
                                </div>
                                <span class="stats-value"><?= $item['total_vistas'] ?> <?= $item['total_vistas'] == 1 ? 'vista' : 'vistas' ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="stats-empty">No hay datos de visualizaciones todavía.</div>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- 2. TOP 5 PELÍCULAS MÁS FAVORITAS -->
            <div class="stats-card">
                <div class="stats-card-header">
                    <i class="fa-solid fa-heart"></i>
                    <h2 class="stats-card-title">Más Guardadas en Favoritos</h2>
                </div>
                <ul class="stats-list">
                    <?php if (!empty($topFavoritos)): ?>
                        <?php foreach ($topFavoritos as $index => $item): ?>
                            <li class="stats-item">
                                <span class="stats-rank stats-rank-<?= $index + 1 ?>"><?= $index + 1 ?></span>
                                <img class="stats-poster" src="<?= htmlspecialchars($item['poster_url'] ?? 'https://images.unsplash.com/photo-1534447677768-be436bb09401?q=80&w=200') ?>" alt="Poster" loading="lazy" decoding="async">
                                <div class="stats-info">
                                    <span class="stats-name"><?= htmlspecialchars($item['titulo']) ?></span>
                                </div>
                                <span class="stats-value"><?= $item['total_favoritos'] ?> ❤️</span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="stats-empty">Nadie ha guardado favoritos aún.</div>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- 3. TOP 5 GÉNEROS MÁS VISTOS -->
            <div class="stats-card">
                <div class="stats-card-header">
                    <i class="fa-solid fa-masks-theater"></i>
                    <h2 class="stats-card-title">Top 5 Géneros Más Vistos</h2>
                </div>
                <ul class="stats-list">
                    <?php if (!empty($topGeneros)): ?>
                        <?php
                        $maxVistasGenero = max(array_column($topGeneros, 'total_vistas'));
                        $maxVistasGenero = $maxVistasGenero > 0 ? $maxVistasGenero : 1;
                        ?>
                        <?php foreach ($topGeneros as $index => $item): ?>
                            <?php $pct = round(($item['total_vistas'] / $maxVistasGenero) * 100); ?>
                            <li class="stats-item" style="flex-direction: column; align-items: stretch; gap: 4px;">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span class="stats-rank stats-rank-<?= $index + 1 ?>" style="font-size:0.95rem; width: 18px;"><?= $index + 1 ?></span>
                                        <span class="stats-name" style="font-size: 13px;"><?= htmlspecialchars($item['nombre']) ?></span>
                                    </div>
                                    <span class="stats-value" style="font-size: 11px; padding: 2px 6px;"><?= $item['total_vistas'] ?> vist.</span>
                                </div>
                                <div class="genre-bar-container">
                                    <div class="genre-bar" style="width: <?= $pct ?>%;"></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="stats-empty">No hay vistas registradas en los géneros.</div>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- 4. TOP 5 ACTORES MÁS POPULARES -->
            <div class="stats-card">
                <div class="stats-card-header">
                    <i class="fa-solid fa-star"></i>
                    <h2 class="stats-card-title">Actores Más Populares</h2>
                </div>
                <ul class="stats-list">
                    <?php if (!empty($topActores)): ?>
                        <?php foreach ($topActores as $index => $item): ?>
                            <li class="stats-item">
                                <span class="stats-rank stats-rank-<?= $index + 1 ?>"><?= $index + 1 ?></span>
                                <img class="stats-avatar" src="<?= htmlspecialchars($item['foto_url'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200') ?>" alt="Foto" loading="lazy" decoding="async">
                                <div class="stats-info">
                                    <span class="stats-name"><?= htmlspecialchars($item['nombre'] . ' ' . $item['apellidos']) ?></span>
                                    <span class="stats-subname">Vistas acumuladas</span>
                                </div>
                                <span class="stats-value"><?= $item['total_vistas'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="stats-empty">No hay datos de actores populares.</div>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- 5. TOP 5 USUARIOS MÁS ACTIVOS -->
            <div class="stats-card">
                <div class="stats-card-header">
                    <i class="fa-solid fa-users-viewfinder"></i>
                    <h2 class="stats-card-title">Usuarios Más Activos</h2>
                </div>
                <ul class="stats-list">
                    <?php if (!empty($topUsuarios)): ?>
                        <?php foreach ($topUsuarios as $index => $item): ?>
                            <li class="stats-item">
                                <span class="stats-rank stats-rank-<?= $index + 1 ?>"><?= $index + 1 ?></span>
                                <i class="fa-solid fa-circle-user" style="font-size: 24px; color: var(--text-muted); padding: 4px;"></i>
                                <div class="stats-info">
                                    <span class="stats-name"><?= htmlspecialchars($item['username']) ?></span>
                                    <span class="stats-subname">Trailers vistos</span>
                                </div>
                                <span class="stats-value"><?= $item['total_vistas'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="stats-empty">Ningún usuario registrado ha visto trailers.</div>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div style="text-align: center; margin-bottom: 40px;">
            <a class="volver" href="../index.php" style="display: inline-block; margin-top: 0;">← Volver al catálogo</a>
        </div>
    </main>

<?php
require_once $rootPath . 'includes/footer.php';
?>
