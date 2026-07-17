<?php
require_once "../config/conexion.php";
require_once __DIR__ . "/../includes/seguridad.php";
require_admin_or_editor('../index.php');
require_post();
require_csrf();
define('BASE_PATH', '../');

$id_reparto = isset($_POST["id_reparto"]) ? (int)$_POST["id_reparto"] : 0;
$nombre = trim($_POST["nombre"] ?? "");
$apellidos = trim($_POST["apellidos"] ?? "");
$edad = isset($_POST["edad"]) && $_POST["edad"] !== "" ? (int)$_POST["edad"] : null;
$pais = trim($_POST["pais"] ?? "");
$foto_url = trim($_POST["foto_url"] ?? "");

if (empty($foto_url)) {
    $foto_url = 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesar Modificación de Reparto - Movie Trailer Hub</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <div class="feedback-container">
        
        <?php if ($id_reparto === 0 || $nombre == "" || $apellidos == ""): ?>
            <h1>Datos Incompletos</h1>
            <div class="alerta">
                <p>Faltan datos obligatorios. Nombre y Apellidos son requeridos.</p>
            </div>
            <a class="boton" href="listar_reparto.php">Volver al catálogo</a>

        <?php else:
            // Validar si ya existe otro actor con el mismo nombre y apellido
            $sqlExiste = "SELECT * FROM reparto WHERE nombre = ? AND apellidos = ? AND id_reparto != ?";
            $stmtExiste = mysqli_prepare($conexion, $sqlExiste);
            if (!$stmtExiste) {
                abortar_error_interno(
                    'Error al preparar la validación de existencia del actor',
                    mysqli_error($conexion)
                );
            }
            mysqli_stmt_bind_param($stmtExiste, "ssi", $nombre, $apellidos, $id_reparto);
            mysqli_stmt_execute($stmtExiste);
            $resultadoExiste = mysqli_stmt_get_result($stmtExiste);

            if (mysqli_num_rows($resultadoExiste) > 0):
                mysqli_stmt_close($stmtExiste);
            ?>
                <h1>Actor/Actriz ya Registrado</h1>
                <div class="alerta">
                    <p>Otro actor/actriz con el nombre "<strong><?php echo htmlspecialchars($nombre . ' ' . $apellidos); ?></strong>" ya existe en el sistema.</p>
                </div>
                <a class="boton" href="modificar_reparto.php?id=<?php echo $id_reparto; ?>">Volver al formulario</a>

            <?php else:
                mysqli_stmt_close($stmtExiste);
                $sqlActualizar = "UPDATE reparto SET nombre = ?, apellidos = ?, edad = ?, pais = ?, foto_url = ? WHERE id_reparto = ?";
                $stmtActualizar = mysqli_prepare($conexion, $sqlActualizar);
                if (!$stmtActualizar) {
                    abortar_error_interno(
                        'Error al preparar la modificación del actor',
                        mysqli_error($conexion)
                    );
                }
                mysqli_stmt_bind_param($stmtActualizar, "ssissi", $nombre, $apellidos, $edad, $pais, $foto_url, $id_reparto);
                
                if (mysqli_stmt_execute($stmtActualizar)):
                    mysqli_stmt_close($stmtActualizar);
                ?>
                    <h1>¡Actor/Actriz Actualizado!</h1>
                    <div class="alerta-exito">
                        <p>Los datos de "<strong><?php echo htmlspecialchars($nombre . ' ' . $apellidos); ?></strong>" han sido actualizados exitosamente.</p>
                    </div>
                    <a class="boton" href="listar_reparto.php">Volver al catálogo de reparto</a>
                <?php else:
                    $error_db = mysqli_stmt_error($stmtActualizar);
                    registrar_error_interno('Error al modificar el actor', $error_db);
                    mysqli_stmt_close($stmtActualizar);
                ?>
                    <h1>Error de Modificación</h1>
                    <div class="alerta">
                        <p>No se pudieron guardar los cambios. Inténtalo de nuevo.</p>
                    </div>
                    <a class="boton" href="modificar_reparto.php?id=<?php echo $id_reparto; ?>">Volver al formulario</a>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</body>
</html>
<?php
mysqli_close($conexion);
?>
