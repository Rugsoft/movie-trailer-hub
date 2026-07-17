<?php
require_once "../config/conexion.php";
require_once __DIR__ . "/../includes/seguridad.php";
require_admin('../index.php');
require_post();
require_csrf();
define('BASE_PATH', '../');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Trailer - Movie Trailer Hub</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <div class="feedback-container">
        <?php
        if ($id === 0) {
            ?>
            <h1>Error de Eliminación</h1>
            <div class="alerta">
                <p>ID del trailer no válido o no especificado.</p>
            </div>
            <a class="boton" href="listar_trailers.php">Volver al catálogo</a>
            <?php
            mysqli_close($conexion);
            exit;
        }

        $sql = "DELETE FROM trailers WHERE id_trailer = ?";
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            abortar_error_interno(
                'Error al preparar la eliminación del trailer',
                mysqli_error($conexion)
            );
        }
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            ?>
            <h1>¡Trailer Eliminado!</h1>
            <div class="alerta-exito">
                <p>El trailer ha sido eliminado exitosamente del catálogo.</p>
            </div>
            <a class="boton" href="listar_trailers.php">Volver al catálogo</a>
            <?php
        } else {
            $error_db = mysqli_stmt_error($stmt);
            registrar_error_interno('Error al eliminar el trailer', $error_db);
            mysqli_stmt_close($stmt);
            ?>
            <h1>Error de Eliminación</h1>
            <div class="alerta">
                <p>No se pudo eliminar el trailer. Inténtalo de nuevo.</p>
            </div>
            <a class="boton" href="listar_trailers.php">Volver al catálogo</a>
            <?php
        }

        mysqli_close($conexion);
        ?>
    </div>
</body>
</html>
