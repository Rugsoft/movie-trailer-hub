<?php
include "../config/conexion.php";
define('BASE_PATH', '../');

$sql = "SELECT t.*, GROUP_CONCAT(g.nombre SEPARATOR ', ') as genero
        FROM trailers t
        LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
        LEFT JOIN generos g ON tg.id_genero = g.id_genero
        GROUP BY t.id_trailer
        ORDER BY t.id_trailer DESC";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Trailers</title>
    <link rel="stylesheet" href="../css/estilos.css">
    <style>
        .poster-mini {
            width: 60px;
            height: 40px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
        }
    </style>
</head>
<body>
    <h1>Catálogo de Trailers</h1>
    <p>Todos los trailers guardados y disponibles en el sistema.</p>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Portada</th>
                    <th>Título</th>
                    <th>Director</th>
                    <th>Fecha de Estreno</th>
                    <th>Género</th>
                    <th>Duración</th>
                    <th>Valoración</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($trailer = mysqli_fetch_assoc($resultado)) { ?>
                    <tr>
                        <td>
                            <img src="<?php echo htmlspecialchars($trailer["poster_url"] ?? 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=600'); ?>" alt="Poster" class="poster-mini">
                        </td>
                        <td><strong><?php echo htmlspecialchars($trailer["titulo"]); ?></strong></td>
                        <td><?php echo htmlspecialchars($trailer["director"] ?? 'N/A'); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($trailer["release_date"])); ?></td>
                        <td><?php echo htmlspecialchars($trailer["genero"]); ?></td>
                        <td><?php echo htmlspecialchars((string)$trailer["duracion"]); ?> min</td>
                        <td>⭐ <?php echo htmlspecialchars((string)$trailer["valoracion"]); ?>/10</td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a class="btn-tabla btn-devolver" href="reproducir_trailer.php?id=<?php echo $trailer['id_trailer']; ?>">Ver</a>
                            <a class="btn-tabla btn-modificar" href="modificar_trailer.php?id=<?php echo $trailer['id_trailer']; ?>">Modificar</a>
                            <a class="btn-tabla btn-eliminar" href="eliminar_trailer.php?id=<?php echo $trailer['id_trailer']; ?>" onclick="return confirm('¿Estás seguro de que deseas eliminar este trailer?');">Eliminar</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <a class="volver" href="../index.php">← Volver al inicio</a>
</body>
</html>
<?php
mysqli_stmt_close($stmt);
mysqli_close($conexion);
?>
