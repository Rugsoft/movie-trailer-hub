<?php
require_once "../config/conexion.php";
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    $_SESSION['error'] = "Acceso denegado. Se requieren permisos de administrador.";
    header("Location: ../index.php");
    exit;
}
define('BASE_PATH', '../');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT * FROM trailers WHERE id_trailer = ? LIMIT 1";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$trailer = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($stmt);

if (!$trailer) {
    echo "<h1>Trailer no encontrado</h1>";
    exit;
}

// Obtener todos los géneros disponibles
$sqlGeneros = "SELECT * FROM generos ORDER BY nombre ASC";
$resGeneros = mysqli_query($conexion, $sqlGeneros);

// Obtener los IDs de los géneros asociados a este trailer
$sqlSelected = "SELECT id_genero FROM trailers_generos WHERE id_trailer = ?";
$stmtSelected = mysqli_prepare($conexion, $sqlSelected);
mysqli_stmt_bind_param($stmtSelected, "i", $id);
mysqli_stmt_execute($stmtSelected);
$resSelected = mysqli_stmt_get_result($stmtSelected);
$selectedGenres = [];
while ($row = mysqli_fetch_assoc($resSelected)) {
    $selectedGenres[] = (int)$row['id_genero'];
}
mysqli_stmt_close($stmtSelected);

// Obtener todos los actores
$sqlReparto = "SELECT * FROM reparto ORDER BY nombre ASC, apellidos ASC";
$resReparto = mysqli_query($conexion, $sqlReparto);

// Obtener los actores ya vinculados al trailer y sus personajes
$sqlSelectedReparto = "SELECT id_reparto, personaje FROM reparto_trailers WHERE id_trailer = ?";
$stmtSelectedReparto = mysqli_prepare($conexion, $sqlSelectedReparto);
mysqli_stmt_bind_param($stmtSelectedReparto, "i", $id);
mysqli_stmt_execute($stmtSelectedReparto);
$resSelectedReparto = mysqli_stmt_get_result($stmtSelectedReparto);
$selectedActors = [];
$actorCharacters = [];
while ($row = mysqli_fetch_assoc($resSelectedReparto)) {
    $selectedActors[] = (int)$row['id_reparto'];
    $actorCharacters[(int)$row['id_reparto']] = $row['personaje'];
}
mysqli_stmt_close($stmtSelectedReparto);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Trailer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        <label>Género(s) (Selecciona al menos uno) *</label>
        <div class="genres-checkbox-group" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; margin-bottom: 18px; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius-md); max-height: 150px; overflow-y: auto; background-color: var(--bg-surface-lowest, #1e293b);">
            <?php while ($g = mysqli_fetch_assoc($resGeneros)) { 
                $isChecked = in_array((int)$g['id_genero'], $selectedGenres) ? 'checked' : '';
            ?>
                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer; margin: 0;">
                    <input type="checkbox" name="generos[]" value="<?php echo $g['id_genero']; ?>" <?php echo $isChecked; ?> style="width: auto; height: auto; cursor: pointer; transform: scale(1.1); accent-color: var(--primary);">
                    <?php echo htmlspecialchars($g['nombre']); ?>
                </label>
            <?php } ?>
        </div>

        <label for="nuevo_genero">¿Añadir otro género nuevo?</label>
        <input type="text" id="nuevo_genero" name="nuevo_genero" placeholder="Ej: Musical, Romance...">

        <label for="duracion">Duración (minutos) *</label>
        <input type="number" id="duracion" name="duracion" required min="1" value="<?php echo htmlspecialchars((string)$trailer['duracion']); ?>">

        <label for="trailer_url">URL del Trailer *</label>
        <input type="url" id="trailer_url" name="trailer_url" required value="<?php echo htmlspecialchars($trailer['trailer_url']); ?>">

        <label for="poster_url">URL de la Portada (Poster):</label>
        <input type="url" id="poster_url" name="poster_url" value="<?php echo htmlspecialchars($trailer['poster_url'] ?? ''); ?>">

        <label for="valoracion">Valoración (0 a 10) *</label>
        <input type="number" id="valoracion" name="valoracion" required step="0.1" min="0" max="10" value="<?php echo htmlspecialchars((string)$trailer['valoracion']); ?>">

        <label>Reparto (Selecciona los actores que participan y asigna su personaje)</label>
        <div class="reparto-selection-group" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 18px; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius-md); max-height: 250px; overflow-y: auto; background-color: var(--bg-surface-lowest, #1e293b);">
            <?php if (mysqli_num_rows($resReparto) > 0) {
                while ($actor = mysqli_fetch_assoc($resReparto)) { 
                    $actorId = (int)$actor['id_reparto'];
                    $isChecked = in_array($actorId, $selectedActors) ? 'checked' : '';
                    $personajeVal = isset($actorCharacters[$actorId]) ? htmlspecialchars($actorCharacters[$actorId]) : '';
                ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 6px 0; border-bottom: 1px dashed rgba(216, 195, 173, 0.05);">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer; margin: 0; flex: 1;">
                            <input type="checkbox" name="actores[]" value="<?php echo $actorId; ?>" <?php echo $isChecked; ?> style="width: auto; height: auto; cursor: pointer; transform: scale(1.1); accent-color: var(--primary);">
                            <img src="<?php echo htmlspecialchars($actor['foto_url'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200'); ?>" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover; margin-left: 5px; margin-right: 5px;">
                            <span><?php echo htmlspecialchars($actor['nombre'] . ' ' . $actor['apellidos']); ?></span>
                        </label>
                        <input type="text" name="personajes[<?php echo $actorId; ?>]" value="<?php echo $personajeVal; ?>" placeholder="Nombre del personaje..." style="flex: 1; max-width: 250px; padding: 6px 12px; font-size: 13px; height: auto;">
                    </div>
                <?php }
            } else { ?>
                <p style="color: var(--text-muted); font-size: 13px; margin: 0; padding: 10px 0;">No hay actores registrados. <a href="añadir_reparto.php" style="color: var(--primary); text-decoration: underline;">Registra un actor primero</a>.</p>
            <?php } ?>
        </div>

        <label for="sinopsis">Sinopsis / Descripción:</label>
        <textarea id="sinopsis" name="sinopsis" rows="4"><?php echo htmlspecialchars($trailer['sinopsis'] ?? ''); ?></textarea>

        <button type="submit">Guardar Cambios</button>
    </form>

    <a class="volver" href="listar_trailers.php">← Volver al catálogo</a>

</body>

</html>
<?php
mysqli_close($conexion);
?>
