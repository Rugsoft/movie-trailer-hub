<?php
require_once "../config/conexion.php";
define('BASE_PATH', '../');

$successMsg = $_SESSION['success'] ?? null;
$errorMsg = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// 1. Top 5 de Trailers Más Vistos
$sqlVistas = "SELECT t.id_trailer, t.titulo, t.poster_url, COUNT(v.id_visualizacion) AS total_vistas
              FROM trailers t
              LEFT JOIN visualizaciones v ON t.id_trailer = v.id_trailer
              GROUP BY t.id_trailer
              ORDER BY total_vistas DESC, t.titulo ASC
              LIMIT 5";
$resVistas = mysqli_query($conexion, $sqlVistas);
$topVistas = [];
while ($row = mysqli_fetch_assoc($resVistas)) {
    $topVistas[] = $row;
}
mysqli_free_result($resVistas);

// 2. Top 5 de Películas Más Favoritas
$sqlFavoritos = "SELECT t.id_trailer, t.titulo, t.poster_url, COUNT(f.id_usuario) AS total_favoritos
                 FROM trailers t
                 LEFT JOIN favoritos f ON t.id_trailer = f.id_trailer
                 GROUP BY t.id_trailer
                 ORDER BY total_favoritos DESC, t.titulo ASC
                 LIMIT 5";
$resFavoritos = mysqli_query($conexion, $sqlFavoritos);
$topFavoritos = [];
while ($row = mysqli_fetch_assoc($resFavoritos)) {
    $topFavoritos[] = $row;
}
mysqli_free_result($resFavoritos);

// 3. Top 5 de Actores Más Populares (Vistas Acumuladas)
$sqlActores = "SELECT r.id_reparto, r.nombre, r.apellidos, r.foto_url, COUNT(v.id_visualizacion) AS total_vistas
               FROM reparto r
               JOIN reparto_trailers rt ON r.id_reparto = rt.id_reparto
               JOIN visualizaciones v ON rt.id_trailer = v.id_trailer
               GROUP BY r.id_reparto
               ORDER BY total_vistas DESC, r.nombre ASC, r.apellidos ASC
               LIMIT 5";
$resActores = mysqli_query($conexion, $sqlActores);
$topActores = [];
while ($row = mysqli_fetch_assoc($resActores)) {
    $topActores[] = $row;
}
mysqli_free_result($resActores);

// 4. Top 5 de Géneros Más Vistos
$sqlGeneros = "SELECT g.id_genero, g.nombre, COUNT(v.id_visualizacion) AS total_vistas
               FROM generos g
               JOIN trailers_generos tg ON g.id_genero = tg.id_genero
               JOIN visualizaciones v ON tg.id_trailer = v.id_trailer
               GROUP BY g.id_genero
               ORDER BY total_vistas DESC, g.nombre ASC
               LIMIT 5";
$resGeneros = mysqli_query($conexion, $sqlGeneros);
$topGeneros = [];
while ($row = mysqli_fetch_assoc($resGeneros)) {
    $topGeneros[] = $row;
}
mysqli_free_result($resGeneros);

// 5. Top 5 de Usuarios Más Activos
$sqlUsuarios = "SELECT u.id_usuario, u.username, COUNT(v.id_visualizacion) AS total_vistas
                FROM usuarios u
                JOIN visualizaciones v ON u.id_usuario = v.id_usuario
                GROUP BY u.id_usuario
                ORDER BY total_vistas DESC, u.username ASC
                LIMIT 5";
$resUsuarios = mysqli_query($conexion, $sqlUsuarios);
$topUsuarios = [];
while ($row = mysqli_fetch_assoc($resUsuarios)) {
    $topUsuarios[] = $row;
}
mysqli_free_result($resUsuarios);

mysqli_close($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas del Sitio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/estilos.css">
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
                    <a href="favoritos.php" class="btn btn-secondary" style="border-color: rgba(220, 38, 38, 0.3); color: var(--secondary);">
                        <i class="fa-solid fa-heart"></i> Mis Favoritos
                    </a>

                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle">
                                <i class="fa-solid fa-gear"></i> Gestión
                            </button>
                            <div class="dropdown-menu">
                                <a href="listar_trailers.php" class="dropdown-item">
                                    <i class="fa-solid fa-list"></i> Administrar Trailers
                                </a>
                                <a href="añadir_trailer.php" class="dropdown-item">
                                    <i class="fa-solid fa-plus"></i> Añadir Trailer
                                </a>
                                <a href="añadir_reparto.php" class="dropdown-item">
                                    <i class="fa-solid fa-user-plus"></i> Añadir Actor
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <span class="user-greeting">
                        <i class="fa-solid fa-circle-user"></i>Hola, <?= htmlspecialchars($_SESSION['username']) ?>
                    </span>

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
                                <img class="stats-poster" src="<?= htmlspecialchars($item['poster_url'] ?? 'https://images.unsplash.com/photo-1534447677768-be436bb09401?q=80&w=200') ?>" alt="Poster">
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
                                <img class="stats-poster" src="<?= htmlspecialchars($item['poster_url'] ?? 'https://images.unsplash.com/photo-1534447677768-be436bb09401?q=80&w=200') ?>" alt="Poster">
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
                    <h2 class="stats-card-title">Géneros Más Vistos</h2>
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
                                <img class="stats-avatar" src="<?= htmlspecialchars($item['foto_url'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200') ?>" alt="Foto">
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
