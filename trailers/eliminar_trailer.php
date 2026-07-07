<?php
require_once "../config/conexion.php";
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    $_SESSION['error'] = "Acceso denegado. Se requieren permisos de administrador.";
    header("Location: ../index.php");
    exit;
}
define('BASE_PATH', '../');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
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
            mysqli_stmt_close($stmt);
            ?>
            <h1>Error de Eliminación</h1>
            <div class="alerta">
                <p>No se pudo eliminar el trailer de la base de datos: <?php echo htmlspecialchars($error_db); ?></p>
            </div>
            <a class="boton" href="listar_trailers.php">Volver al catálogo</a>
            <?php
        }

        mysqli_close($conexion);
        ?>
    </div>
</body>
</html>
