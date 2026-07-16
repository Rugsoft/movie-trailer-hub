<?php
// badges/api_badges.php
require_once "../config/conexion.php";
require_once "gamificacion_helper.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

$id_usuario = (int)$_SESSION['usuario_id'];
$resultado = procesar_y_obtener_badges($conexion, $id_usuario);

echo json_encode($resultado);
mysqli_close($conexion);
?>
