<?php
// badges/registrar_evento.php
require_once "../config/conexion.php";
require_once "../includes/seguridad.php";
require_once "gamificacion_helper.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$id_usuario = (int)$_SESSION['usuario_id'];
$action = $_GET['action'] ?? '';

if ($action === 'modo_cine') {
    $sql = "INSERT INTO usuario_gamificacion_stats (id_usuario, modo_cine_activado) 
            VALUES (?, 1) 
            ON DUPLICATE KEY UPDATE modo_cine_activado = 1";
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_usuario);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        procesar_y_obtener_badges($conexion, $id_usuario);
        $nuevosLogros = $_SESSION['nuevos_logros_desbloqueados'] ?? [];
        unset($_SESSION['nuevos_logros_desbloqueados']);
        
        echo json_encode([
            'success' => true,
            'nuevos_logros' => $nuevosLogros
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error de base de datos']);
    }
    exit;
}

if ($action === 'leer_resenas') {
    $id_trailer = isset($_GET['id_trailer']) ? (int)$_GET['id_trailer'] : 0;
    if ($id_trailer > 0) {
        $sql = "INSERT IGNORE INTO usuario_lectura_resenas (id_usuario, id_trailer) VALUES (?, ?)";
        $stmt = mysqli_prepare($conexion, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $id_usuario, $id_trailer);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            procesar_y_obtener_badges($conexion, $id_usuario);
            $nuevosLogros = $_SESSION['nuevos_logros_desbloqueados'] ?? [];
            unset($_SESSION['nuevos_logros_desbloqueados']);
            
            echo json_encode([
                'success' => true,
                'nuevos_logros' => $nuevosLogros
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error de base de datos']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'ID de trailer inválido']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Acción inválida']);
exit;
?>
