<?php
require_once "../config/conexion.php";
define('BASE_PATH', '../');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT t.*, GROUP_CONCAT(g.nombre SEPARATOR ', ') as genero
        FROM trailers t
        LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
        LEFT JOIN generos g ON tg.id_genero = g.id_genero
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reproduciendo: <?php echo htmlspecialchars($trailer['titulo']); ?></title>
    <link rel="stylesheet" href="../css/estilos.css">
    <style>
        .player-wrapper {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: var(--shadow-md);
            margin: 0 auto;
            max-width: 900px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .video-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 */
            height: 0;
            overflow: hidden;
            border-radius: var(--radius-md);
            background: #000;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
        }
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        .info-container h2 {
            font-family: 'Montserrat', sans-serif;
            color: #ffffff;
            margin-bottom: 8px;
            text-align: left;
            font-size: 1.8rem;
            font-weight: 800;
        }
        .info-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 0.95rem;
            color: var(--text-muted);
            margin-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 12px;
        }
        .info-meta span strong {
            color: var(--text-main);
        }
        .info-synopsis {
            font-size: 1.05rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Reproductor de Trailers</h1>
    <p>Disfruta del trailer oficial de la película seleccionada.</p>

    <div class="player-wrapper">
        <div class="video-container">
            <iframe src="<?php echo htmlspecialchars($embedUrl); ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>

        <div class="info-container">
            <h2><?php echo htmlspecialchars($trailer['titulo']); ?></h2>
            <div class="info-meta">
                <span>Director: <strong><?php echo htmlspecialchars($trailer['director'] ?? 'N/A'); ?></strong></span>
                <span>Fecha de Estreno: <strong><?php echo date('d/m/Y', strtotime($trailer['release_date'])); ?></strong></span>
                <span>Género: <strong><?php echo htmlspecialchars($trailer['genero']); ?></strong></span>
                <span>Duración: <strong><?php echo htmlspecialchars((string)$trailer['duracion']); ?> min</strong></span>
                <span>Valoración: <strong>⭐ <?php echo htmlspecialchars((string)$trailer['valoracion']); ?>/10</strong></span>
            </div>
            <div class="info-synopsis">
                <p><?php echo htmlspecialchars($trailer['sinopsis'] ?? 'Sin sinopsis o descripción disponible.'); ?></p>
            </div>
        </div>
    </div>

    <a class="volver" href="listar_trailers.php">← Volver al catálogo</a>
</body>
</html>
<?php
mysqli_stmt_close($stmt);
mysqli_close($conexion);
?>
