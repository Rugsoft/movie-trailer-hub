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
    <title>Eliminar Actor/Actriz - Movie Trailer Hub</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <div class="feedback-container">
        <?php
        if ($id === 0) {
            ?>
            <h1>Error de Eliminación</h1>
            <div class="alerta">
                <p>ID de actor no válido o no especificado.</p>
            </div>
            <a class="boton" href="listar_reparto.php">Volver al catálogo</a>
            <?php
            mysqli_close($conexion);
            exit;
        }

        // Obtener el nombre para el mensaje de éxito
        $sqlInfo = "SELECT nombre, apellidos FROM reparto WHERE id_reparto = ?";
        $stmtInfo = mysqli_prepare($conexion, $sqlInfo);
        if (!$stmtInfo) {
            abortar_error_interno(
                'Error al preparar la consulta del actor',
                mysqli_error($conexion)
            );
        }
        mysqli_stmt_bind_param($stmtInfo, "i", $id);
        if (!mysqli_stmt_execute($stmtInfo)) {
            $error_db = mysqli_stmt_error($stmtInfo);
            mysqli_stmt_close($stmtInfo);
            abortar_error_interno('Error al consultar el actor', $error_db);
        }
        $resInfo = mysqli_stmt_get_result($stmtInfo);
        $actor = mysqli_fetch_assoc($resInfo);
        mysqli_stmt_close($stmtInfo);

        if (!$actor) {
            ?>
            <h1>Error de Eliminación</h1>
            <div class="alerta">
                <p>El actor/actriz especificado no existe en el sistema.</p>
            </div>
            <a class="boton" href="listar_reparto.php">Volver al catálogo</a>
            <?php
            mysqli_close($conexion);
            exit;
        }

        $nombreCompleto = $actor['nombre'] . ' ' . $actor['apellidos'];

        $sql = "DELETE FROM reparto WHERE id_reparto = ?";
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            abortar_error_interno(
                'Error al preparar la eliminación del actor',
                mysqli_error($conexion)
            );
        }
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            ?>
            <h1>¡Actor/Actriz Eliminado!</h1>
            <div class="alerta-exito">
                <p>El actor/actriz "<strong><?php echo htmlspecialchars($nombreCompleto); ?></strong>" ha sido eliminado exitosamente del catálogo (y desvinculado de todas sus películas).</p>
            </div>
            <a class="boton" href="listar_reparto.php">Volver al catálogo</a>
            <?php
        } else {
            $error_db = mysqli_stmt_error($stmt);
            registrar_error_interno('Error al eliminar el actor', $error_db);
            mysqli_stmt_close($stmt);
            ?>
            <h1>Error de Eliminación</h1>
            <div class="alerta">
                <p>No se pudo eliminar el actor. Inténtalo de nuevo.</p>
            </div>
            <a class="boton" href="listar_reparto.php">Volver al catálogo</a>
            <?php
        }

        mysqli_close($conexion);
        ?>
    </div>
</body>
</html>
