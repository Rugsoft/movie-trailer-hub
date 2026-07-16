<?php
require_once "../config/conexion.php";
require_once __DIR__ . "/../includes/seguridad.php";
require_admin_or_editor('../index.php');
define('BASE_PATH', '../');

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
    <title>Procesar Director - Movie Trailer Hub</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <div class="feedback-container">
        
        <?php if ($nombre == "" || $apellidos == ""): ?>
            <h1>Datos Incompletos</h1>
            <div class="alerta">
                <p>Faltan datos obligatorios. Nombre y Apellidos son requeridos.</p>
            </div>
            <a class="boton" href="añadir_director.php">Volver al formulario</a>

        <?php else:
            // Validar si el director ya existe
            $sqlExiste = "SELECT * FROM directores WHERE nombre = ? AND apellidos = ?";
            $stmtExiste = mysqli_prepare($conexion, $sqlExiste);
            if (!$stmtExiste) {
                die("Error al preparar la validación de existencia de director: " . mysqli_error($conexion));
            }
            mysqli_stmt_bind_param($stmtExiste, "ss", $nombre, $apellidos);
            mysqli_stmt_execute($stmtExiste);
            $resultadoExiste = mysqli_stmt_get_result($stmtExiste);

            if (mysqli_num_rows($resultadoExiste) > 0):
                mysqli_stmt_close($stmtExiste);
            ?>
                <h1>Director ya Registrado</h1>
                <div class="alerta">
                    <p>El director "<strong><?php echo htmlspecialchars($nombre . ' ' . $apellidos); ?></strong>" ya se encuentra registrado en el sistema.</p>
                </div>
                <a class="boton" href="añadir_director.php">Volver al formulario</a>

            <?php else:
                mysqli_stmt_close($stmtExiste);
                $sqlInsertar = "INSERT INTO directores (nombre, apellidos, edad, pais) VALUES (?, ?, ?, ?)";
                $stmtInsertar = mysqli_prepare($conexion, $sqlInsertar);
                if (!$stmtInsertar) {
                    die("Error al preparar el registro del director: " . mysqli_error($conexion));
                }
                mysqli_stmt_bind_param($stmtInsertar, "ssis", $nombre, $apellidos, $edad, $pais);
                
                if (mysqli_stmt_execute($stmtInsertar)):
                    mysqli_stmt_close($stmtInsertar);
                ?>
                    <h1>¡Director Añadido!</h1>
                    <div class="alerta-exito">
                        <p>El director "<strong><?php echo htmlspecialchars($nombre . ' ' . $apellidos); ?></strong>" ha sido registrado exitosamente.</p>
                    </div>
                    <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-top: 20px;">
                        <a class="boton" href="añadir_director.php">Añadir otro director</a>
                        <a class="boton boton-secundario" href="../index.php">Volver al inicio</a>
                    </div>
                <?php else:
                    $error_db = mysqli_stmt_error($stmtInsertar);
                    mysqli_stmt_close($stmtInsertar);
                ?>
                    <h1>Error de Registro</h1>
                    <div class="alerta">
                        <p>Error al guardar en la base de datos: <?php echo htmlspecialchars($error_db); ?></p>
                    </div>
                    <a class="boton" href="añadir_director.php">Volver al formulario</a>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</body>
</html>
<?php
mysqli_close($conexion);
?>
