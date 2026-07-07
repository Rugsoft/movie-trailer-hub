<?php
require_once "../config/conexion.php";
define('BASE_PATH', '../');

$id_trailer = isset($_POST["id_trailer"]) ? (int)$_POST["id_trailer"] : 0;
$titulo = trim($_POST["titulo"] ?? "");
$director = trim($_POST["director"] ?? "");
$release_date = trim($_POST["release_date"] ?? "");
$generos_post = $_POST["generos"] ?? [];
$nuevo_genero = trim($_POST["nuevo_genero"] ?? "");
$duracion = trim($_POST["duracion"] ?? "");
$trailer_url = trim($_POST["trailer_url"] ?? "");
$poster_url = trim($_POST["poster_url"] ?? "");
$valoracion = trim($_POST["valoracion"] ?? "");
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
        
        <?php if ($id_trailer === 0 || $titulo == "" || $release_date == "" || empty($generos_post) || $duracion == "" || $trailer_url == "" || $valoracion == ""): ?>
            <h1>Datos Incompletos</h1>
            <div class="alerta">
                <p>Faltan datos obligatorios en el formulario de edición. Inténtalo de nuevo.</p>
            </div>
            <a class="boton" href="listar_trailers.php">Volver al catálogo</a>

        <?php else:
            $sqlActualizar = "UPDATE trailers SET titulo = ?, director = ?, release_date = ?, duracion = ?, trailer_url = ?, poster_url = ?, valoracion = ?, sinopsis = ? WHERE id_trailer = ?";
            $stmtActualizar = mysqli_prepare($conexion, $sqlActualizar);
            mysqli_stmt_bind_param($stmtActualizar, "sssissdsi", $titulo, $director, $release_date, $duracion, $trailer_url, $poster_url, $valoracion, $sinopsis, $id_trailer);
            
            if (mysqli_stmt_execute($stmtActualizar)):
                mysqli_stmt_close($stmtActualizar);
                
                // Eliminar asociaciones de géneros anteriores en trailers_generos
                $sqlDeleteAssoc = "DELETE FROM trailers_generos WHERE id_trailer = ?";
                $stmtDelete = mysqli_prepare($conexion, $sqlDeleteAssoc);
                mysqli_stmt_bind_param($stmtDelete, "i", $id_trailer);
                mysqli_stmt_execute($stmtDelete);
                mysqli_stmt_close($stmtDelete);
                
                // Insertar las nuevas asociaciones en trailers_generos
                $sqlInsertAssoc = "INSERT INTO trailers_generos (id_trailer, id_genero) VALUES (?, ?)";
                $stmtInsert = mysqli_prepare($conexion, $sqlInsertAssoc);
                foreach ($generos_post as $id_genero) {
                    mysqli_stmt_bind_param($stmtInsert, "ii", $id_trailer, $id_genero);
                    mysqli_stmt_execute($stmtInsert);
                }
                mysqli_stmt_close($stmtInsert);
                
                // Eliminar asociaciones de reparto anteriores en reparto_trailers
                $sqlDeleteReparto = "DELETE FROM reparto_trailers WHERE id_trailer = ?";
                $stmtDeleteReparto = mysqli_prepare($conexion, $sqlDeleteReparto);
                mysqli_stmt_bind_param($stmtDeleteReparto, "i", $id_trailer);
                mysqli_stmt_execute($stmtDeleteReparto);
                mysqli_stmt_close($stmtDeleteReparto);
                
                // Insertar las nuevas asociaciones en reparto_trailers
                if (!empty($actores_post)) {
                    $sqlInsertReparto = "INSERT INTO reparto_trailers (id_trailer, id_reparto, personaje) VALUES (?, ?, ?)";
                    $stmtInsertReparto = mysqli_prepare($conexion, $sqlInsertReparto);
                    foreach ($actores_post as $id_reparto) {
                        $personaje = trim($personajes_post[$id_reparto] ?? "");
                        mysqli_stmt_bind_param($stmtInsertReparto, "iis", $id_trailer, $id_reparto, $personaje);
                        mysqli_stmt_execute($stmtInsertReparto);
                    }
                    mysqli_stmt_close($stmtInsertReparto);
                }
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
