<?php
require_once "../config/conexion.php";
define('BASE_PATH', '../');

$id_trailer = isset($_POST["id_trailer"]) ? (int)$_POST["id_trailer"] : 0;
$titulo = trim($_POST["titulo"] ?? "");
$director = trim($_POST["director"] ?? "");
$release_date = trim($_POST["release_date"] ?? "");
$genero = trim($_POST["genero"] ?? "");
$duracion = trim($_POST["duracion"] ?? "");
$trailer_url = trim($_POST["trailer_url"] ?? "");
$poster_url = trim($_POST["poster_url"] ?? "");
$valoracion = trim($_POST["valoracion"] ?? "");
$sinopsis = trim($_POST["sinopsis"] ?? "");

if (empty($poster_url)) {
    $poster_url = 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=600';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesar Modificación - Movie Trailer Hub</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <div class="feedback-container">
        
        <?php if ($id_trailer === 0 || $titulo == "" || $release_date == "" || $genero == "" || $duracion == "" || $trailer_url == "" || $valoracion == ""): ?>
            <h1>Datos Incompletos</h1>
            <div class="alerta">
                <p>Faltan datos obligatorios en el formulario de edición. Inténtalo de nuevo.</p>
            </div>
            <a class="boton" href="listar_trailers.php">Volver al catálogo</a>

        <?php else:
            $sqlActualizar = "UPDATE trailers SET titulo = ?, director = ?, release_date = ?, genero = ?, duracion = ?, trailer_url = ?, poster_url = ?, valoracion = ?, sinopsis = ? WHERE id_trailer = ?";
            $stmtActualizar = mysqli_prepare($conexion, $sqlActualizar);
            mysqli_stmt_bind_param($stmtActualizar, "ssssissdsi", $titulo, $director, $release_date, $genero, $duracion, $trailer_url, $poster_url, $valoracion, $sinopsis, $id_trailer);
            
            if (mysqli_stmt_execute($stmtActualizar)):
                mysqli_stmt_close($stmtActualizar);
            ?>
                <h1>¡Trailer Actualizado!</h1>
                <div class="alerta-exito">
                    <p>Los datos de "<strong><?php echo htmlspecialchars($titulo); ?></strong>" han sido modificados exitosamente en la base de datos.</p>
                </div>
                <a class="boton" href="listar_trailers.php">Ver catálogo completo</a>
            <?php else:
                $error_db = mysqli_stmt_error($stmtActualizar);
                mysqli_stmt_close($stmtActualizar);
            ?>
                <h1>Error de Actualización</h1>
                <div class="alerta">
                    <p>Error al modificar el trailer en la base de datos: <?php echo htmlspecialchars($error_db); ?></p>
                </div>
                <a class="boton" href="modificar_trailer.php?id=<?php echo $id_trailer; ?>">Volver a intentarlo</a>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</body>
</html>
<?php
mysqli_close($conexion);
?>
