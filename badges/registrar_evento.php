<?php
// badges/registrar_evento.php
require_once "../config/conexion.php";
require_once "../includes/seguridad.php";
require_once "gamificacion_helper.php";

header('Content-Type: application/json; charset=utf-8');

require_login('../auth/login.php');
require_post(true);
require_csrf(true);

$id_usuario = (int)$_SESSION['usuario_id'];
$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $data['action'] ?? '';

if ($action === 'modo_cine') {
    $sql = "INSERT INTO usuario_gamificacion_stats (id_usuario, modo_cine_activado) 
            VALUES (?, 1) 
            ON DUPLICATE KEY UPDATE modo_cine_activado = 1";
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_usuario);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        marcar_recalculo_badges_pendiente();
        procesar_y_obtener_badges($conexion, $id_usuario);
        $_SESSION['movie_app_badges_last_check_at'] = time();
        unset($_SESSION['movie_app_badges_force_check']);
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
    $id_trailer = isset($data['id_trailer']) ? (int)$data['id_trailer'] : 0;
    if ($id_trailer > 0) {
        $sql = "INSERT IGNORE INTO usuario_lectura_resenas (id_usuario, id_trailer) VALUES (?, ?)";
        $stmt = mysqli_prepare($conexion, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $id_usuario, $id_trailer);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            marcar_recalculo_badges_pendiente();
            procesar_y_obtener_badges($conexion, $id_usuario);
            $_SESSION['movie_app_badges_last_check_at'] = time();
            unset($_SESSION['movie_app_badges_force_check']);
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
