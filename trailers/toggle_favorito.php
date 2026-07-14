<?php
require_once "../config/conexion.php";
define('BASE_PATH', '../');

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['error'] = "Debes iniciar sesión para añadir trailers a tus favoritos.";
    header("Location: ../auth/login.php");
    exit;
}

$id_usuario = $_SESSION['usuario_id'];
$id_trailer = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_trailer <= 0) {
    header("Location: ../index.php");
    exit;
}

// Verificar si ya es favorito
$sqlCheck = "SELECT 1 FROM favoritos WHERE id_usuario = ? AND id_trailer = ? LIMIT 1";
$stmtCheck = mysqli_prepare($conexion, $sqlCheck);
if (!$stmtCheck) {
    die("Error al preparar la verificación de favoritos: " . mysqli_error($conexion));
}
mysqli_stmt_bind_param($stmtCheck, "ii", $id_usuario, $id_trailer);
mysqli_stmt_execute($stmtCheck);
$resCheck = mysqli_stmt_get_result($stmtCheck);
$isFavorito = mysqli_num_rows($resCheck) > 0;
mysqli_stmt_close($stmtCheck);

if ($isFavorito) {
    // Quitar de favoritos
    $sqlDelete = "DELETE FROM favoritos WHERE id_usuario = ? AND id_trailer = ?";
    $stmtDelete = mysqli_prepare($conexion, $sqlDelete);
    if (!$stmtDelete) {
        die("Error al preparar la eliminación de favoritos: " . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtDelete, "ii", $id_usuario, $id_trailer);
    mysqli_stmt_execute($stmtDelete);
    mysqli_stmt_close($stmtDelete);
    $_SESSION['success'] = "Película eliminada de tus favoritos.";
} else {
    // Añadir a favoritos
    $sqlInsert = "INSERT INTO favoritos (id_usuario, id_trailer) VALUES (?, ?)";
    $stmtInsert = mysqli_prepare($conexion, $sqlInsert);
    if (!$stmtInsert) {
        die("Error al preparar la inserción de favoritos: " . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtInsert, "ii", $id_usuario, $id_trailer);
    mysqli_stmt_execute($stmtInsert);
    mysqli_stmt_close($stmtInsert);
    $_SESSION['success'] = "Película añadida a tus favoritos.";
}

mysqli_close($conexion);

// Detectar si la petición es AJAX
$isAjax = (isset($_GET['ajax']) && $_GET['ajax'] == 1) || 
          (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        "success" => true,
        "isFavorito" => !$isFavorito,
        "message" => !$isFavorito ? "Película añadida a tus favoritos." : "Película eliminada de tus favoritos."
    ]);
    exit;
}

$referer = $_SERVER['HTTP_REFERER'] ?? "../index.php";
header("Location: " . $referer);
exit;
?>
