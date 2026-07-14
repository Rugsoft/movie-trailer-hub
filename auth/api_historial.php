<?php
require_once "../config/conexion.php";

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(["error" => "No autorizado."]);
    exit;
}

$id_usuario = (int)$_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Método no permitido."]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $data['action'] ?? '';

if ($action === 'delete_entry') {
    $id_visualizacion = isset($data['id_visualizacion']) ? (int)$data['id_visualizacion'] : 0;
    
    if ($id_visualizacion <= 0) {
        echo json_encode(["error" => "ID de visualización no válido."]);
        exit;
    }
    
    $sql = "DELETE FROM visualizaciones WHERE id_visualizacion = ? AND id_usuario = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $id_visualizacion, $id_usuario);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["error" => "No se pudo eliminar el registro de la base de datos."]);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(["error" => "Error de preparación SQL."]);
    }
} elseif ($action === 'clear_history') {
    $sql = "DELETE FROM visualizaciones WHERE id_usuario = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_usuario);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["error" => "No se pudo limpiar el historial."]);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(["error" => "Error de preparación SQL."]);
    }
} else {
    echo json_encode(["error" => "Acción no válida."]);
}

mysqli_close($conexion);
