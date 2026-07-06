<?php
require_once "../config/conexion.php";
define('BASE_PATH', '../');

$titulo = trim($_POST["titulo"] ?? "");
$director = trim($_POST["director"] ?? "");
$release_date = trim($_POST["release_date"] ?? "");
$genero = trim($_POST["genero"] ?? "");
$duracion = trim($_POST["duracion"] ?? "");
$trailer_url = trim($_POST["trailer_url"] ?? "");
$poster_url = trim($_POST["poster_url"] ?? "");
$valoracion = trim($_POST["valoracion"] ?? "");
$sinopsis = trim($_POST["sinopsis"] ?? "");

// Imagen por defecto si no se pasa ninguna
if (empty($poster_url)) {
    $poster_url = 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=600';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesar Nuevo Trailer - Movie Trailer Hub</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <div class="feedback-container">
        
        <?php if ($titulo == "" || $release_date == "" || $genero == "" || $duracion == "" || $trailer_url == "" || $valoracion == ""): ?>
            <h1>Datos Incompletos</h1>
            <div class="alerta">
                <p>Faltan datos obligatorios en el formulario. Vuelve atrás y revisa todos los campos.</p>
            </div>
            <a class="boton" href="añadir_trailer.php">Volver al formulario</a>

        <?php else:
            // Validar si la película ya existe por título y fecha de estreno
            $sqlExiste = "SELECT * FROM trailers WHERE titulo = ? AND release_date = ?";
            $stmtExiste = mysqli_prepare($conexion, $sqlExiste);
            mysqli_stmt_bind_param($stmtExiste, "ss", $titulo, $release_date);
            mysqli_stmt_execute($stmtExiste);
            $resultadoExiste = mysqli_stmt_get_result($stmtExiste);

            if (mysqli_num_rows($resultadoExiste) > 0):
                mysqli_stmt_close($stmtExiste);
            ?>
                <h1>Película Ya Registrada</h1>
                <div class="alerta">
                    <p>La película "<strong><?php echo htmlspecialchars($titulo); ?></strong>" estrenada el <strong><?php echo date('d/m/Y', strtotime($release_date)); ?></strong> ya está registrada en el catálogo.</p>
                </div>
                <a class="boton" href="añadir_trailer.php">Volver al formulario</a>

            <?php else:
                mysqli_stmt_close($stmtExiste);
                $sqlInsertar = "INSERT INTO trailers (titulo, director, release_date, genero, duracion, trailer_url, poster_url, valoracion, sinopsis) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtInsertar = mysqli_prepare($conexion, $sqlInsertar);
                mysqli_stmt_bind_param($stmtInsertar, "ssssissds", $titulo, $director, $release_date, $genero, $duracion, $trailer_url, $poster_url, $valoracion, $sinopsis);
                
                if (mysqli_stmt_execute($stmtInsertar)):
                    mysqli_stmt_close($stmtInsertar);
                ?>
                    <h1>¡Trailer Añadido!</h1>
                    <div class="alerta-exito">
                        <p>El trailer de "<strong><?php echo htmlspecialchars($titulo); ?></strong>" ha sido guardado exitosamente en la base de datos.</p>
                    </div>
                    <a class="boton" href="../index.php">Volver al inicio</a>
                <?php else:
                    $error_db = mysqli_stmt_error($stmtInsertar);
                    mysqli_stmt_close($stmtInsertar);
                ?>
                    <h1>Error de Registro</h1>
                    <div class="alerta">
                        <p>Error al añadir el trailer en la base de datos: <?php echo htmlspecialchars($error_db); ?></p>
                    </div>
                    <a class="boton" href="añadir_trailer.php">Volver al formulario</a>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</body>
</html>
<?php
mysqli_close($conexion);
?>
