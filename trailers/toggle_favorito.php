<?php
require_once "../config/conexion.php";
require_once __DIR__ . "/../includes/seguridad.php";
define('BASE_PATH', '../');

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

require_login('../auth/login.php', "Debes iniciar sesión para añadir trailers a tus favoritos.");
require_post($isAjax);
require_csrf($isAjax);

$id_usuario = (int)$_SESSION['usuario_id'];
$id_trailer = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id_trailer <= 0) {
    if ($isAjax) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'ID de trailer inválido.']);
        exit;
    }

    header("Location: ../index.php");
    exit;
}

// Verificar si ya es favorito
$sqlCheck = "SELECT 1 FROM favoritos WHERE id_usuario = ? AND id_trailer = ? LIMIT 1";
$stmtCheck = mysqli_prepare($conexion, $sqlCheck);
if (!$stmtCheck) {
    abortar_error_interno(
        'Error al preparar la verificación de favoritos',
        mysqli_error($conexion),
        $isAjax
    );
}
mysqli_stmt_bind_param($stmtCheck, "ii", $id_usuario, $id_trailer);
if (!mysqli_stmt_execute($stmtCheck)) {
    $error_db = mysqli_stmt_error($stmtCheck);
    mysqli_stmt_close($stmtCheck);
    abortar_error_interno('Error al verificar favoritos', $error_db, $isAjax);
}
$resCheck = mysqli_stmt_get_result($stmtCheck);
$isFavorito = mysqli_num_rows($resCheck) > 0;
mysqli_stmt_close($stmtCheck);

if ($isFavorito) {
    // Quitar de favoritos
    $sqlDelete = "DELETE FROM favoritos WHERE id_usuario = ? AND id_trailer = ?";
    $stmtDelete = mysqli_prepare($conexion, $sqlDelete);
    if (!$stmtDelete) {
        abortar_error_interno(
            'Error al preparar la eliminación de favoritos',
            mysqli_error($conexion),
            $isAjax
        );
    }
    mysqli_stmt_bind_param($stmtDelete, "ii", $id_usuario, $id_trailer);
    if (!mysqli_stmt_execute($stmtDelete)) {
        $error_db = mysqli_stmt_error($stmtDelete);
        mysqli_stmt_close($stmtDelete);
        abortar_error_interno('Error al eliminar el favorito', $error_db, $isAjax);
    }
    mysqli_stmt_close($stmtDelete);
    $_SESSION['success'] = "Película eliminada de tus favoritos.";
} else {
    // Añadir a favoritos
    $sqlInsert = "INSERT INTO favoritos (id_usuario, id_trailer) VALUES (?, ?)";
    $stmtInsert = mysqli_prepare($conexion, $sqlInsert);
    if (!$stmtInsert) {
        abortar_error_interno(
            'Error al preparar la inserción de favoritos',
            mysqli_error($conexion),
            $isAjax
        );
    }
    mysqli_stmt_bind_param($stmtInsert, "ii", $id_usuario, $id_trailer);
    if (!mysqli_stmt_execute($stmtInsert)) {
        $error_db = mysqli_stmt_error($stmtInsert);
        mysqli_stmt_close($stmtInsert);
        abortar_error_interno('Error al insertar el favorito', $error_db, $isAjax);
    }
    mysqli_stmt_close($stmtInsert);
    $_SESSION['success'] = "Película añadida a tus favoritos.";
}

require_once __DIR__ . '/../badges/gamificacion_helper.php';
marcar_recalculo_badges_pendiente();
procesar_y_obtener_badges($conexion, $id_usuario);
$_SESSION['movie_app_badges_last_check_at'] = time();
unset($_SESSION['movie_app_badges_force_check']);

mysqli_close($conexion);

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    $nuevosLogros = $_SESSION['nuevos_logros_desbloqueados'] ?? [];
    unset($_SESSION['nuevos_logros_desbloqueados']);
    echo json_encode([
        "success" => true,
        "isFavorito" => !$isFavorito,
        "message" => !$isFavorito ? "Película añadida a tus favoritos." : "Película eliminada de tus favoritos.",
        "nuevos_logros" => $nuevosLogros
    ]);
    exit;
}

$referer = $_SERVER['HTTP_REFERER'] ?? "../index.php";
header("Location: " . $referer);
exit;
?>
