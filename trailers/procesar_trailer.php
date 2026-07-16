<?php
require_once "../config/conexion.php";
require_once __DIR__ . "/../includes/seguridad.php";
require_admin_or_editor('../index.php');
define('BASE_PATH', '../');

$titulo = trim($_POST["titulo"] ?? "");
$id_director = isset($_POST["id_director"]) && $_POST["id_director"] !== "" ? (int)$_POST["id_director"] : null;
$release_date = trim($_POST["release_date"] ?? "");
$generos_post = $_POST["generos"] ?? [];
$nuevo_genero = trim($_POST["nuevo_genero"] ?? "");
$duracion = isset($_POST["duracion"]) && $_POST["duracion"] !== "" ? (int)$_POST["duracion"] : 0;
$trailer_url = trim($_POST["trailer_url"] ?? "");
$poster_url = trim($_POST["poster_url"] ?? "");
$valoracion = isset($_POST["valoracion"]) && $_POST["valoracion"] !== "" ? (float)$_POST["valoracion"] : 0.0;
$sinopsis = trim($_POST["sinopsis"] ?? "");
$actores_post = $_POST["actores"] ?? [];
$personajes_post = $_POST["personajes"] ?? [];

// Procesar el nuevo género si se ha enviado
if ($nuevo_genero !== "") {
    // Verificar si ya existe en la base de datos
    $sqlExisteGen = "SELECT id_genero FROM generos WHERE nombre = ?";
    $stmtExisteGen = mysqli_prepare($conexion, $sqlExisteGen);
    mysqli_stmt_bind_param($stmtExisteGen, "s", $nuevo_genero);
    mysqli_stmt_execute($stmtExisteGen);
    $resExisteGen = mysqli_stmt_get_result($stmtExisteGen);
    if ($rowGen = mysqli_fetch_assoc($resExisteGen)) {
        $id_nuevo = $rowGen['id_genero'];
    } else {
        // Insertar nuevo género
        $sqlInsertGen = "INSERT INTO generos (nombre) VALUES (?)";
        $stmtInsertGen = mysqli_prepare($conexion, $sqlInsertGen);
        mysqli_stmt_bind_param($stmtInsertGen, "s", $nuevo_genero);
        mysqli_stmt_execute($stmtInsertGen);
        $id_nuevo = mysqli_insert_id($conexion);
        mysqli_stmt_close($stmtInsertGen);
    }
    mysqli_stmt_close($stmtExisteGen);
    
    if (!in_array($id_nuevo, $generos_post)) {
        $generos_post[] = $id_nuevo;
    }
}

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
                <?php if ($titulo == "" || $release_date == "" || empty($generos_post) || $duracion <= 0 || $trailer_url == "" || $valoracion < 0.0 || $valoracion > 10.0): ?>
            <h1>Datos Incompletos</h1>
            <div class="alerta">
                <p>Faltan datos obligatorios en el formulario o los valores ingresados no son válidos. Vuelve atrás y revisa todos los campos.</p>
            </div>
            <a class="boton" href="añadir_trailer.php">Volver al formulario</a>

        <?php else:
            // Validar si la película ya existe por título y fecha de estreno
            $sqlExiste = "SELECT * FROM trailers WHERE titulo = ? AND release_date = ?";
            $stmtExiste = mysqli_prepare($conexion, $sqlExiste);
            if (!$stmtExiste) {
                die("Error al preparar la validación de existencia: " . mysqli_error($conexion));
            }
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

                // Iniciar transacción
                mysqli_begin_transaction($conexion);
                $exito = true;
                $error_db = "";

                try {
                    $sqlInsertar = "INSERT INTO trailers (titulo, id_director, release_date, duracion, trailer_url, poster_url, valoracion, sinopsis) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmtInsertar = mysqli_prepare($conexion, $sqlInsertar);
                    if (!$stmtInsertar) {
                        throw new Exception("Error al preparar la creación del trailer: " . mysqli_error($conexion));
                    }
                    mysqli_stmt_bind_param($stmtInsertar, "sisissds", $titulo, $id_director, $release_date, $duracion, $trailer_url, $poster_url, $valoracion, $sinopsis);
                    if (!mysqli_stmt_execute($stmtInsertar)) {
                        throw new Exception("Error al insertar el trailer: " . mysqli_stmt_error($stmtInsertar));
                    }
                    $id_trailer = mysqli_insert_id($conexion);
                    mysqli_stmt_close($stmtInsertar);
                    
                    // Insertar asociaciones de géneros en trailers_generos
                    $sqlAssoc = "INSERT INTO trailers_generos (id_trailer, id_genero) VALUES (?, ?)";
                    $stmtAssoc = mysqli_prepare($conexion, $sqlAssoc);
                    if (!$stmtAssoc) {
                        throw new Exception("Error al preparar la asociación de géneros: " . mysqli_error($conexion));
                    }
                    foreach ($generos_post as $id_genero) {
                        mysqli_stmt_bind_param($stmtAssoc, "ii", $id_trailer, $id_genero);
                        if (!mysqli_stmt_execute($stmtAssoc)) {
                            throw new Exception("Error al asociar género: " . mysqli_stmt_error($stmtAssoc));
                        }
                    }
                    mysqli_stmt_close($stmtAssoc);
                    
                    // Insertar asociaciones de reparto en reparto_trailers
                    if (!empty($actores_post)) {
                        $sqlRepartoAssoc = "INSERT INTO reparto_trailers (id_trailer, id_reparto, personaje) VALUES (?, ?, ?)";
                        $stmtReparto = mysqli_prepare($conexion, $sqlRepartoAssoc);
                        if (!$stmtReparto) {
                            throw new Exception("Error al preparar la asociación de reparto: " . mysqli_error($conexion));
                        }
                        foreach ($actores_post as $id_reparto) {
                            $personaje = trim($personajes_post[$id_reparto] ?? "");
                            mysqli_stmt_bind_param($stmtReparto, "iis", $id_trailer, $id_reparto, $personaje);
                            if (!mysqli_stmt_execute($stmtReparto)) {
                                throw new Exception("Error al asociar actor: " . mysqli_stmt_error($stmtReparto));
                            }
                        }
                        mysqli_stmt_close($stmtReparto);
                    }

                    // Confirmar transacción
                    mysqli_commit($conexion);
                } catch (Exception $e) {
                    mysqli_rollback($conexion);
                    $exito = false;
                    $error_db = $e->getMessage();
                }

                if ($exito):
                ?>
                    <h1>¡Trailer Añadido!</h1>
                    <div class="alerta-exito">
                        <p>El trailer de "<strong><?php echo htmlspecialchars($titulo); ?></strong>" ha sido guardado exitosamente en la base de datos.</p>
                    </div>
                    <a class="boton" href="../index.php">Volver al inicio</a>
                <?php else: ?>
                    <h1>Error de Registro</h1>
                    <div class="alerta">
                        <p>Error en la operación de base de datos: <?php echo htmlspecialchars($error_db); ?></p>
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
