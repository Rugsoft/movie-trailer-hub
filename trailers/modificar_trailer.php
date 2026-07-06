<?php
require_once "../config/conexion.php";
define('BASE_PATH', '../');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT * FROM trailers WHERE id_trailer = ? LIMIT 1";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$trailer = mysqli_fetch_assoc($resultado);

if (!$trailer) {
    echo "<h1>Trailer no encontrado</h1>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Trailer</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>

<body>
    <h1>Modificar Trailer</h1>
    <p>Actualizar la ficha de "<strong><?php echo htmlspecialchars($trailer['titulo']); ?></strong>" en el catálogo.</p>

    <form action="procesar_modificar_trailer.php" method="POST">
        <input type="hidden" name="id_trailer" value="<?php echo $trailer['id_trailer']; ?>">

        <label for="titulo">Título de la Película *</label>
        <input type="text" id="titulo" name="titulo" required value="<?php echo htmlspecialchars($trailer['titulo']); ?>">

        <label for="director">Director:</label>
        <input type="text" id="director" name="director" value="<?php echo htmlspecialchars($trailer['director'] ?? ''); ?>">

        <label for="release_date">Fecha de Estreno *</label>
        <input type="date" id="release_date" name="release_date" required value="<?php echo htmlspecialchars($trailer['release_date']); ?>">

        <label for="genero">Género *</label>
        <input type="text" id="genero" name="genero" required value="<?php echo htmlspecialchars($trailer['genero']); ?>">

        <label for="duracion">Duración (minutos) *</label>
        <input type="number" id="duracion" name="duracion" required min="1" value="<?php echo htmlspecialchars((string)$trailer['duracion']); ?>">

        <label for="trailer_url">URL del Trailer *</label>
        <input type="url" id="trailer_url" name="trailer_url" required value="<?php echo htmlspecialchars($trailer['trailer_url']); ?>">

        <label for="poster_url">URL de la Portada (Poster):</label>
        <input type="url" id="poster_url" name="poster_url" value="<?php echo htmlspecialchars($trailer['poster_url'] ?? ''); ?>">

        <label for="valoracion">Valoración (0 a 10) *</label>
        <input type="number" id="valoracion" name="valoracion" required step="0.1" min="0" max="10" value="<?php echo htmlspecialchars((string)$trailer['valoracion']); ?>">

        <label for="sinopsis">Sinopsis / Descripción:</label>
        <textarea id="sinopsis" name="sinopsis" rows="4"><?php echo htmlspecialchars($trailer['sinopsis'] ?? ''); ?></textarea>

        <button type="submit">Guardar Cambios</button>
    </form>

    <a class="volver" href="listar_trailers.php">← Volver al catálogo</a>

</body>

</html>
<?php
mysqli_stmt_close($stmt);
mysqli_close($conexion);
?>
