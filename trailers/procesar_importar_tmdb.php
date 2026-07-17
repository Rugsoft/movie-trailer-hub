<?php
require_once "../config/conexion.php";
require_once "tmdb_import_helper.php";

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/../includes/seguridad.php";
require_admin_or_editor(null, true);
require_post(true);
require_csrf(true);

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(["error" => "ID de película de TMDB inválido."]);
    exit;
}

try {
    $result = importMovieById($conexion, $id);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}

mysqli_close($conexion);
