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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filmografía: <?php echo htmlspecialchars($actor['nombre'] . ' ' . $actor['apellidos']); ?></title>
    <link rel="icon" type="image/png" href="../images/logo movie trailer hub (1) (1).png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/estilos.css">
    <style>
        .actor-profile-card {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: var(--shadow-md);
            margin: 0 auto 40px auto;
            max-width: 900px;
            display: flex;
            gap: 30px;
            align-items: center;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .actor-avatar {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            box-shadow: 0 0 20px var(--primary-glow);
        }
        .actor-details h2 {
            font-family: 'Montserrat', sans-serif;
            color: #ffffff;
            margin-bottom: 12px;
            font-size: 2.2rem;
            font-weight: 800;
        }
        .actor-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            font-size: 1rem;
            color: var(--text-muted);
        }
        .actor-meta span strong {
            color: var(--text-primary);
        }
        .movies-list-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .movie-row-card {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 16px 20px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            transition: var(--transition-smooth);
        }
        .movie-row-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        .movie-row-left {
            display: flex;
            align-items: center;
            gap: 16px;
            overflow: hidden;
        }
        .movie-row-poster {
            width: 50px;
            height: 70px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .movie-row-info h3 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.15rem;
            color: #ffffff;
            margin-bottom: 4px;
        }
        .movie-row-info p {
            font-size: 13px;
            color: var(--text-muted);
        }
        .movie-row-info p strong {
            color: var(--primary);
        }
        .movie-row-right {
            display: flex;
            align-items: center;
            gap: 20px;
            white-space: nowrap;
        }
        .movie-row-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            font-size: 12px;
            color: var(--text-muted);
        }
        .movie-row-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .movie-row-meta .rating {
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 4px;
        }
        @media (max-width: 600px) {
            .actor-profile-card {
                flex-direction: column;
                text-align: center;
            }
            .movie-row-card {
                flex-direction: column;
                align-items: stretch;
            }
            .movie-row-right {
                justify-content: space-between;
                border-top: 1px dashed var(--border-color);
                padding-top: 12px;
            }
            .movie-row-meta {
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Navegación principal -->
    <header class="navbar">
        <div class="app-container navbar-content">
            <a href="../index.php" class="brand">
                <img src="../images/logo movie trailer hub (1) (1).png" alt="Logo" class="brand-icon">
                <h1 class="brand-name">Movie Trailer Hub</h1>
            </a>
            <div class="nav-actions">
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <a href="favoritos.php" class="btn btn-secondary" style="border-color: rgba(220, 38, 38, 0.3); color: var(--secondary);">
                        <i class="fa-solid fa-heart"></i> Mis Favoritos
                    </a>

                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                        <a href="añadir_reparto.php" class="btn btn-secondary">
                            <i class="fa-solid fa-user-plus"></i> Añadir Actor
                        </a>
                        <a href="listar_trailers.php" class="btn btn-secondary">
                            <i class="fa-solid fa-list"></i> Administrar
                        </a>
                        <a href="añadir_trailer.php" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Añadir
                        </a>
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
        <h2 style="font-family: var(--font-headline); font-size: 1.5rem; font-weight: 800; color: #ffffff; margin-bottom: 20px;">Filmografía Registrada (<?php echo count($movies); ?>)</h2>
        
        <?php if (!empty($movies)): ?>
            <?php foreach ($movies as $movie): ?>
                <div class="movie-row-card" onclick="location.href='reproducir_trailer.php?id=<?php echo $movie['id_trailer']; ?>'" style="cursor: pointer;">
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
            <div class="alerta" style="max-width: none;">
                <p>Este actor/actriz aún no ha sido asociado a ningún trailer en el catálogo.</p>
            </div>
        <?php endif; ?>
    </div>

    </main>

    <a class="volver" href="../index.php">← Volver al inicio</a>
</body>
</html>
