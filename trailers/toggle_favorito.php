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
mysqli_stmt_bind_param($stmtCheck, "ii", $id_usuario, $id_trailer);
mysqli_stmt_execute($stmtCheck);
$resCheck = mysqli_stmt_get_result($stmtCheck);
$isFavorito = mysqli_num_rows($resCheck) > 0;
mysqli_stmt_close($stmtCheck);

if ($isFavorito) {
    // Quitar de favoritos
    $sqlDelete = "DELETE FROM favoritos WHERE id_usuario = ? AND id_trailer = ?";
    $stmtDelete = mysqli_prepare($conexion, $sqlDelete);
    mysqli_stmt_bind_param($stmtDelete, "ii", $id_usuario, $id_trailer);
    mysqli_stmt_execute($stmtDelete);
    mysqli_stmt_close($stmtDelete);
    $_SESSION['success'] = "Película eliminada de tus favoritos.";
} else {
    // Añadir a favoritos
    $sqlInsert = "INSERT INTO favoritos (id_usuario, id_trailer) VALUES (?, ?)";
    $stmtInsert = mysqli_prepare($conexion, $sqlInsert);
    mysqli_stmt_bind_param($stmtInsert, "ii", $id_usuario, $id_trailer);
    mysqli_stmt_execute($stmtInsert);
    mysqli_stmt_close($stmtInsert);
    $_SESSION['success'] = "Película añadida a tus favoritos.";
}

mysqli_close($conexion);

$referer = $_SERVER['HTTP_REFERER'] ?? "../index.php";
header("Location: " . $referer);
exit;
?>
