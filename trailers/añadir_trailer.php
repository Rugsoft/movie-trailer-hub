<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Nuevo Trailer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/estilos.css">
</head>

<body>
<?php
require_once "../config/conexion.php";
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    $_SESSION['error'] = "Acceso denegado. Se requieren permisos de administrador.";
    header("Location: ../index.php");
    exit;
}
define('BASE_PATH', '../');

$sqlGeneros = "SELECT * FROM generos ORDER BY nombre ASC";
$resGeneros = mysqli_query($conexion, $sqlGeneros);

$sqlReparto = "SELECT * FROM reparto ORDER BY nombre ASC, apellidos ASC";
$resReparto = mysqli_query($conexion, $sqlReparto);
?>
    <h1>Añadir Nuevo Trailer</h1>
    <p>Formulario para registrar una nueva película y su trailer en la base de datos.</p>

    <form action="procesar_trailer.php" method="POST">
        <label for="titulo">Título de la Película *</label>
        <input type="text" id="titulo" name="titulo" required placeholder="Ej: Interstellar">

        <label for="director">Director:</label>
        <input type="text" id="director" name="director" placeholder="Ej: Christopher Nolan">

        <label for="release_date">Fecha de Estreno *</label>
        <input type="date" id="release_date" name="release_date" required>

        <label>Género(s) (Selecciona al menos uno) *</label>
        <div class="genres-checkbox-group" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; margin-bottom: 18px; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius-md); max-height: 150px; overflow-y: auto; background-color: var(--bg-surface-lowest, #1e293b);">
            <?php while ($g = mysqli_fetch_assoc($resGeneros)) { ?>
                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer; margin: 0;">
                    <input type="checkbox" name="generos[]" value="<?php echo $g['id_genero']; ?>" style="width: auto; height: auto; cursor: pointer; transform: scale(1.1); accent-color: var(--primary);">
                    <?php echo htmlspecialchars($g['nombre']); ?>
                </label>
            <?php } ?>
        </div>

        <label for="nuevo_genero">¿Añadir otro género nuevo?</label>
        <input type="text" id="nuevo_genero" name="nuevo_genero" placeholder="Ej: Musical, Romance...">

        <label for="duracion">Duración (minutos) *</label>
        <input type="number" id="duracion" name="duracion" required min="1" placeholder="Ej: 169">

        <label for="trailer_url">URL del Trailer *</label>
        <input type="url" id="trailer_url" name="trailer_url" required placeholder="Ej: https://www.youtube.com/watch?v=...">

        <label for="poster_url">URL de la Portada (Poster):</label>
        <input type="url" id="poster_url" name="poster_url" placeholder="Ej: https://enlace-imagen.jpg">

        <label for="valoracion">Valoración (0 a 10) *</label>
        <input type="number" id="valoracion" name="valoracion" required step="0.1" min="0" max="10" placeholder="Ej: 8.7">

        <label>Reparto (Selecciona los actores que participan y asigna su personaje)</label>
        <div class="reparto-selection-group" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 18px; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius-md); max-height: 250px; overflow-y: auto; background-color: var(--bg-surface-lowest, #1e293b);">
            <?php if (mysqli_num_rows($resReparto) > 0) {
                while ($actor = mysqli_fetch_assoc($resReparto)) { ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 6px 0; border-bottom: 1px dashed rgba(216, 195, 173, 0.05);">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer; margin: 0; flex: 1;">
                            <input type="checkbox" name="actores[]" value="<?php echo $actor['id_reparto']; ?>" style="width: auto; height: auto; cursor: pointer; transform: scale(1.1); accent-color: var(--primary);">
                            <img src="<?php echo htmlspecialchars($actor['foto_url'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200'); ?>" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover; margin-left: 5px; margin-right: 5px;">
                            <span><?php echo htmlspecialchars($actor['nombre'] . ' ' . $actor['apellidos']); ?></span>
                        </label>
                        <input type="text" name="personajes[<?php echo $actor['id_reparto']; ?>]" placeholder="Nombre del personaje..." style="flex: 1; max-width: 250px; padding: 6px 12px; font-size: 13px; height: auto;">
                    </div>
                <?php }
            } else { ?>
                <p style="color: var(--text-muted); font-size: 13px; margin: 0; padding: 10px 0;">No hay actores registrados. <a href="añadir_reparto.php" style="color: var(--primary); text-decoration: underline;">Registra un actor primero</a>.</p>
            <?php } ?>
        </div>

        <label for="sinopsis">Sinopsis / Descripción:</label>
        <textarea id="sinopsis" name="sinopsis" rows="4" placeholder="Escribe un breve resumen de la película..."></textarea>

        <button type="submit">Añadir Trailer</button>
    </form>

    <a class="volver" href="../index.php">← Volver al inicio</a>

</body>

</html>
