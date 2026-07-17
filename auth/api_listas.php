<?php
require_once "../config/conexion.php";
require_once __DIR__ . "/../includes/seguridad.php";

header('Content-Type: application/json; charset=utf-8');

// 1. Validar autenticación
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Debes iniciar sesión para realizar esta acción.']);
    exit;
}

$id_usuario = (int)$_SESSION['usuario_id'];

require_post(true);
require_csrf(true);

// 2. Obtener los parámetros de entrada (POST form o JSON)
$contentType = $_SERVER["CONTENT_TYPE"] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $rawData = file_get_contents("php://input");
    $data = json_decode($rawData, true) ?? [];
} else {
    $data = $_POST;
}

$action = $data['action'] ?? '';
$id_trailer = isset($data['id_trailer']) ? (int)$data['id_trailer'] : 0;

if ($id_trailer <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de película inválido.']);
    exit;
}

switch ($action) {
    case 'add_to_list':
    case 'update_status':
        $estado = $data['estado'] ?? 'por_ver';
        if ($estado !== 'por_ver' && $estado !== 'vista') {
            http_response_code(400);
            echo json_encode(['error' => 'Estado inválido.']);
            exit;
        }
        
        $sql = "INSERT INTO listas_personales (id_usuario, id_trailer, estado, fecha_adicion) 
                VALUES (?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE estado = VALUES(estado)";
        $stmt = mysqli_prepare($conexion, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iis", $id_usuario, $id_trailer, $estado);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true, 'message' => 'Lista actualizada correctamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al guardar en la base de datos.']);
            }
            mysqli_stmt_close($stmt);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error de preparación SQL.']);
        }
        break;

    case 'remove_from_list':
        $sql = "DELETE FROM listas_personales WHERE id_usuario = ? AND id_trailer = ?";
        $stmt = mysqli_prepare($conexion, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $id_usuario, $id_trailer);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true, 'message' => 'Eliminado de la lista.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al eliminar de la base de datos.']);
            }
            mysqli_stmt_close($stmt);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error de preparación SQL.']);
        }
        break;

    case 'save_comment':
        $comentario = isset($data['comentario']) ? trim($data['comentario']) : '';
        
        // Obtener el comentario previo si existe para ver si cambió
        $prevComment = null;
        $sqlPrev = "SELECT comentario FROM comentarios_privados WHERE id_usuario = ? AND id_trailer = ? LIMIT 1";
        $stmtPrev = mysqli_prepare($conexion, $sqlPrev);
        if ($stmtPrev) {
            mysqli_stmt_bind_param($stmtPrev, "ii", $id_usuario, $id_trailer);
            mysqli_stmt_execute($stmtPrev);
            $resPrev = mysqli_stmt_get_result($stmtPrev);
            if ($rowPrev = mysqli_fetch_assoc($resPrev)) {
                $prevComment = $rowPrev['comentario'];
            }
            mysqli_stmt_close($stmtPrev);
        }

        // Si es vacío y no había comentario previo, no hacemos nada o limpiamos.
        // Si hay comentario previo y cambió, lo guardamos en el historial antes de actualizar.
        if ($prevComment !== null && $prevComment !== $comentario) {
            $sqlHist = "INSERT INTO historial_comentarios_privados (id_usuario, id_trailer, comentario_anterior, fecha_cambio) 
                        VALUES (?, ?, ?, NOW())";
            $stmtHist = mysqli_prepare($conexion, $sqlHist);
            if ($stmtHist) {
                mysqli_stmt_bind_param($stmtHist, "iis", $id_usuario, $id_trailer, $prevComment);
                mysqli_stmt_execute($stmtHist);
                mysqli_stmt_close($stmtHist);
            }
        }

        // Guardar comentario en la tabla principal
        $sqlSave = "INSERT INTO comentarios_privados (id_usuario, id_trailer, comentario, fecha_creacion, fecha_actualizacion) 
                    VALUES (?, ?, ?, NOW(), NOW()) 
                    ON DUPLICATE KEY UPDATE comentario = VALUES(comentario), fecha_actualizacion = NOW()";
        $stmtSave = mysqli_prepare($conexion, $sqlSave);
        if ($stmtSave) {
            mysqli_stmt_bind_param($stmtSave, "iis", $id_usuario, $id_trailer, $comentario);
            if (mysqli_stmt_execute($stmtSave)) {
                echo json_encode(['success' => true, 'message' => 'Comentario guardado.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al guardar el comentario.']);
            }
            mysqli_stmt_close($stmtSave);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error de preparación SQL.']);
        }
        break;

    case 'get_history':
        $history = [];
        $sqlHistList = "SELECT comentario_anterior, fecha_cambio 
                        FROM historial_comentarios_privados 
                        WHERE id_usuario = ? AND id_trailer = ? 
                        ORDER BY fecha_cambio DESC";
        $stmtHistList = mysqli_prepare($conexion, $sqlHistList);
        if ($stmtHistList) {
            mysqli_stmt_bind_param($stmtHistList, "ii", $id_usuario, $id_trailer);
            mysqli_stmt_execute($stmtHistList);
            $resHistList = mysqli_stmt_get_result($stmtHistList);
            while ($row = mysqli_fetch_assoc($resHistList)) {
                $history[] = [
                    'comentario_anterior' => $row['comentario_anterior'],
                    'fecha_cambio' => date('d/m/Y H:i', strtotime($row['fecha_cambio']))
                ];
            }
            mysqli_stmt_close($stmtHistList);
            echo json_encode(['success' => true, 'history' => $history]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error de preparación SQL.']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción inválida.']);
        break;
}

mysqli_close($conexion);
