<?php
require_once "../config/conexion.php";
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    $_SESSION['error'] = "Acceso denegado. Se requieren permisos de administrador.";
    header("Location: ../index.php");
    exit;
}
define('BASE_PATH', '../');

$id_director = isset($_POST["id_director"]) ? (int)$_POST["id_director"] : 0;
$nombre = trim($_POST["nombre"] ?? "");
$apellidos = trim($_POST["apellidos"] ?? "");
$edad = isset($_POST["edad"]) && $_POST["edad"] !== "" ? (int)$_POST["edad"] : null;
$pais = trim($_POST["pais"] ?? "");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesar Modificación de Director - Movie Trailer Hub</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <div class="feedback-container">
        
        <?php if ($id_director === 0 || $nombre == "" || $apellidos == ""): ?>
            <h1>Datos Incompletos</h1>
            <div class="alerta">
                <p>Faltan datos obligatorios. Nombre y Apellidos son requeridos.</p>
            </div>
            <a class="boton" href="listar_directores.php">Volver al catálogo</a>

        <?php else:
            // Validar si ya existe otro director con el mismo nombre y apellido
            $sqlExiste = "SELECT * FROM directores WHERE nombre = ? AND apellidos = ? AND id_director != ?";
            $stmtExiste = mysqli_prepare($conexion, $sqlExiste);
            if (!$stmtExiste) {
                die("Error al preparar la validación de existencia de director: " . mysqli_error($conexion));
            }
            mysqli_stmt_bind_param($stmtExiste, "ssi", $nombre, $apellidos, $id_director);
            mysqli_stmt_execute($stmtExiste);
            $resultadoExiste = mysqli_stmt_get_result($stmtExiste);

            if (mysqli_num_rows($resultadoExiste) > 0):
                mysqli_stmt_close($stmtExiste);
            ?>
                <h1>Director ya Registrado</h1>
                <div class="alerta">
                    <p>Otro director con el nombre "<strong><?php echo htmlspecialchars($nombre . ' ' . $apellidos); ?></strong>" ya existe en el sistema.</p>
                </div>
                <a class="boton" href="modificar_director.php?id=<?php echo $id_director; ?>">Volver al formulario</a>

            <?php else:
                mysqli_stmt_close($stmtExiste);
                $sqlActualizar = "UPDATE directores SET nombre = ?, apellidos = ?, edad = ?, pais = ? WHERE id_director = ?";
                $stmtActualizar = mysqli_prepare($conexion, $sqlActualizar);
                if (!$stmtActualizar) {
                    die("Error al preparar la modificación del director: " . mysqli_error($conexion));
                }
                mysqli_stmt_bind_param($stmtActualizar, "ssisi", $nombre, $apellidos, $edad, $pais, $id_director);
                
                if (mysqli_stmt_execute($stmtActualizar)):
                    mysqli_stmt_close($stmtActualizar);
                ?>
                    <h1>¡Director Actualizado!</h1>
                    <div class="alerta-exito">
                        <p>Los datos de "<strong><?php echo htmlspecialchars($nombre . ' ' . $apellidos); ?></strong>" han sido actualizados exitosamente.</p>
                    </div>
                    <a class="boton" href="listar_directores.php">Volver al catálogo de directores</a>
                <?php else:
                    $error_db = mysqli_stmt_error($stmtActualizar);
                    mysqli_stmt_close($stmtActualizar);
                ?>
                    <h1>Error de Modificación</h1>
                    <div class="alerta">
                        <p>Error al guardar cambios: <?php echo htmlspecialchars($error_db); ?></p>
                    </div>
                    <a class="boton" href="modificar_director.php?id=<?php echo $id_director; ?>">Volver al formulario</a>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</body>
</html>
<?php
mysqli_close($conexion);
?>
