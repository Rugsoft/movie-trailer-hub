<?php
include "../config/conexion.php";
define('BASE_PATH', '../');

$sql = "SELECT * FROM trailers ORDER BY valoracion DESC, titulo ASC LIMIT 10";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking de Trailers</title>
    <link rel="stylesheet" href="../css/estilos.css">
    <style>
        .poster-mini {
            width: 60px;
            height: 40px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
        }
        .rank-number {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            color: var(--primary-color);
            font-size: 1.2rem;
            text-shadow: 0 0 10px rgba(245, 158, 11, 0.3);
        }
    </style>
</head>
<body>
    <h1>Ranking de Popularidad</h1>
    <p>Los 10 trailers mejor valorados por nuestros usuarios en la plataforma.</p>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 60px; text-align: center;">Puesto</th>
                    <th>Portada</th>
                    <th>Título</th>
                    <th>Director</th>
                    <th>Fecha de Estreno</th>
                    <th>Género</th>
                    <th>Valoración</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $puesto = 1;
                while ($trailer = mysqli_fetch_assoc($resultado)) { 
                ?>
                    <tr>
                        <td style="text-align: center;"><span class="rank-number">#<?php echo $puesto++; ?></span></td>
                        <td>
                            <img src="<?php echo htmlspecialchars($trailer["poster_url"] ?? 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=600'); ?>" alt="Poster" class="poster-mini">
                        </td>
                        <td><strong><?php echo htmlspecialchars($trailer["titulo"]); ?></strong></td>
                        <td><?php echo htmlspecialchars($trailer["director"] ?? 'N/A'); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($trailer["release_date"])); ?></td>
                        <td><?php echo htmlspecialchars($trailer["genero"]); ?></td>
                        <td>⭐ <strong><?php echo htmlspecialchars((string)$trailer["valoracion"]); ?></strong>/10</td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a class="btn-tabla btn-devolver" href="reproducir_trailer.php?id=<?php echo $trailer['id_trailer']; ?>">Ver</a>
                            <a class="btn-tabla btn-modificar" href="modificar_trailer.php?id=<?php echo $trailer['id_trailer']; ?>">Modificar</a>
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
