<?php
require_once "../config/conexion.php";
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    $_SESSION['error'] = "Acceso denegado. Se requieren permisos de administrador.";
    header("Location: ../index.php");
    exit;
}
define('BASE_PATH', '../');

$nombre = trim($_POST["nombre"] ?? "");
$apellidos = trim($_POST["apellidos"] ?? "");
$edad = isset($_POST["edad"]) && $_POST["edad"] !== "" ? (int)$_POST["edad"] : null;
$pais = trim($_POST["pais"] ?? "");
$foto_url = trim($_POST["foto_url"] ?? "");

if (empty($foto_url)) {
    // Foto por defecto si no se pasa ninguna
    $foto_url = 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesar Reparto - Movie Trailer Hub</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <div class="feedback-container">
        
        <?php if ($nombre == "" || $apellidos == ""): ?>
            <h1>Datos Incompletos</h1>
            <div class="alerta">
                <p>Faltan datos obligatorios. Nombre y Apellidos son requeridos.</p>
            </div>
            <a class="boton" href="añadir_reparto.php">Volver al formulario</a>

        <?php else:
            // Validar si el actor ya existe
            $sqlExiste = "SELECT * FROM reparto WHERE nombre = ? AND apellidos = ?";
            $stmtExiste = mysqli_prepare($conexion, $sqlExiste);
            mysqli_stmt_bind_param($stmtExiste, "ss", $nombre, $apellidos);
            mysqli_stmt_execute($stmtExiste);
            $resultadoExiste = mysqli_stmt_get_result($stmtExiste);

            if (mysqli_num_rows($resultadoExiste) > 0):
                mysqli_stmt_close($stmtExiste);
            ?>
                <h1>Actor/Actriz ya Registrado</h1>
                <div class="alerta">
                    <p>El actor/actriz "<strong><?php echo htmlspecialchars($nombre . ' ' . $apellidos); ?></strong>" ya se encuentra registrado en el sistema.</p>
                </div>
                <a class="boton" href="añadir_reparto.php">Volver al formulario</a>

            <?php else:
                mysqli_stmt_close($stmtExiste);
                $sqlInsertar = "INSERT INTO reparto (nombre, apellidos, edad, pais, foto_url) VALUES (?, ?, ?, ?, ?)";
                $stmtInsertar = mysqli_prepare($conexion, $sqlInsertar);
                mysqli_stmt_bind_param($stmtInsertar, "ssiss", $nombre, $apellidos, $edad, $pais, $foto_url);
                
                if (mysqli_stmt_execute($stmtInsertar)):
                    mysqli_stmt_close($stmtInsertar);
                ?>
                    <h1>¡Actor/Actriz Añadido!</h1>
                    <div class="alerta-exito">
                        <p>El actor/actriz "<strong><?php echo htmlspecialchars($nombre . ' ' . $apellidos); ?></strong>" ha sido registrado exitosamente.</p>
                    </div>
                    <a class="boton" href="../index.php">Volver al inicio</a>
                <?php else:
                    $error_db = mysqli_stmt_error($stmtInsertar);
                    mysqli_stmt_close($stmtInsertar);
                ?>
                    <h1>Error de Registro</h1>
                    <div class="alerta">
                        <p>Error al guardar en la base de datos: <?php echo htmlspecialchars($error_db); ?></p>
                    </div>
                    <a class="boton" href="añadir_reparto.php">Volver al formulario</a>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</body>
</html>
<?php
mysqli_close($conexion);
?>
