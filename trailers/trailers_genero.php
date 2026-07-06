<?php
include "../config/conexion.php";
define('BASE_PATH', '../');

// Obtener todos los géneros disponibles
$sqlGeneros = "SELECT DISTINCT genero FROM trailers ORDER BY genero ASC";
$resGeneros = mysqli_query($conexion, $sqlGeneros);

$genero_seleccionado = trim($_GET["genero"] ?? "");
$resultado = null;

if ($genero_seleccionado !== "") {
    $sql = "SELECT * FROM trailers WHERE genero = ? ORDER BY release_date DESC";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "s", $genero_seleccionado);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trailers por Género</title>
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
    <h1>Trailers por Género</h1>
    <p>Filtra los trailers disponibles por su género cinematográfico.</p>

    <form action="trailers_genero.php" method="GET">
        <label for="genero">Selecciona un Género:</label>
        <select id="genero" name="genero" required onchange="this.form.submit()">
            <option value="">-- Elige un género --</option>
            <?php while ($g = mysqli_fetch_assoc($resGeneros)) { ?>
                <option value="<?php echo htmlspecialchars($g['genero']); ?>" <?php echo ($genero_seleccionado === $g['genero']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($g['genero']); ?>
                </option>
            <?php } ?>
        </select>
        <button type="submit">Filtrar</button>
    </form>

    <?php if ($genero_seleccionado !== ""): ?>
        <h2 class="section-title">Trailers del género "<?php echo htmlspecialchars($genero_seleccionado); ?>"</h2>
        
        <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Portada</th>
                            <th>Título</th>
                            <th>Director</th>
                            <th>Fecha de Estreno</th>
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
            <?php mysqli_stmt_close($stmt); ?>
        <?php else: ?>
            <div class="alerta">
                <p>No se encontraron trailers para este género.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <a class="volver" href="../index.php">← Volver al inicio</a>
</body>
</html>
<?php
mysqli_free_result($resGeneros);
mysqli_close($conexion);
?>
