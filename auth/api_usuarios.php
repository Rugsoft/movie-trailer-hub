<?php
// auth/api_usuarios.php
require_once "../config/conexion.php";
require_once __DIR__ . "/../includes/seguridad.php";

header('Content-Type: application/json; charset=utf-8');

// 1. Validar que el usuario esté autenticado y tenga rol de admin
require_admin();

// 2. Validar token CSRF para operaciones de modificación/creación/eliminación
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
    if (!$csrfToken || $csrfToken !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'Petición rechazada: token CSRF inválido o ausente.']);
        exit;
    }
}

// 3. Procesar Acciones
switch ($action) {
    case 'list':
        // Obtener todos los usuarios con estadísticas agregadas
        $sql = "SELECT u.id_usuario, u.username, u.nombre, u.apellidos, u.email, u.telefono, u.rol, u.fecha_alta, u.avatar_url,
                       (SELECT COUNT(*) FROM favoritos f WHERE f.id_usuario = u.id_usuario) as total_favoritos,
                       (SELECT COUNT(*) FROM resenas r WHERE r.id_usuario = u.id_usuario) as total_resenas,
                       (SELECT COUNT(*) FROM visualizaciones v WHERE v.id_usuario = u.id_usuario) as total_vistas
                FROM usuarios u
                ORDER BY u.id_usuario DESC";
        $stmt = mysqli_prepare($conexion, $sql);
        if ($stmt) {
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $users = [];
            while ($row = mysqli_fetch_assoc($res)) {
                $users[] = [
                    'id_usuario' => (int)$row['id_usuario'],
                    'username' => $row['username'],
                    'nombre' => $row['nombre'],
                    'apellidos' => $row['apellidos'],
                    'email' => $row['email'],
                    'telefono' => $row['telefono'],
                    'rol' => $row['rol'],
                    'fecha_alta' => $row['fecha_alta'],
                    'avatar_url' => $row['avatar_url'],
                    'stats' => [
                        'favoritos' => (int)$row['total_favoritos'],
                        'resenas' => (int)$row['total_resenas'],
                        'vistas' => (int)$row['total_vistas']
                    ]
                ];
            }
            mysqli_stmt_close($stmt);
            echo json_encode($users);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al consultar los usuarios.']);
        }
        break;

    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido.']);
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $avatar_url = trim($_POST['avatar_url'] ?? '');
        $rol = trim($_POST['rol'] ?? 'lector');
        $password = trim($_POST['password'] ?? '');

        // Validaciones básicas en el servidor
        if ($username === '' || $nombre === '' || $apellidos === '' || $email === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Por favor, rellena todos los campos obligatorios (*).']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'El formato del correo electrónico no es válido.']);
            exit;
        }

        if ($rol !== 'admin' && $rol !== 'editor' && $rol !== 'lector') {
            http_response_code(400);
            echo json_encode(['error' => 'El rol seleccionado no es válido.']);
            exit;
        }

        // Verificar si el username o el email ya existen
        $sqlCheck = "SELECT id_usuario FROM usuarios WHERE username = ? OR email = ? LIMIT 1";
        $stmtCheck = mysqli_prepare($conexion, $sqlCheck);
        if ($stmtCheck) {
            mysqli_stmt_bind_param($stmtCheck, "ss", $username, $email);
            mysqli_stmt_execute($stmtCheck);
            $resCheck = mysqli_stmt_get_result($stmtCheck);
            if (mysqli_num_rows($resCheck) > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'El nombre de usuario o el correo electrónico ya están en uso.']);
                mysqli_stmt_close($stmtCheck);
                exit;
            }
            mysqli_stmt_close($stmtCheck);
        }

        // Crear usuario
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $fecha_alta = date("Y-m-d");

        $sqlInsert = "INSERT INTO usuarios (username, password_hash, nombre, apellidos, email, telefono, rol, fecha_alta, avatar_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtInsert = mysqli_prepare($conexion, $sqlInsert);
        if ($stmtInsert) {
            $avatar_param = $avatar_url !== '' ? $avatar_url : null;
            $tel_param = $telefono !== '' ? $telefono : null;
            mysqli_stmt_bind_param($stmtInsert, "sssssssss", $username, $passwordHash, $nombre, $apellidos, $email, $tel_param, $rol, $fecha_alta, $avatar_param);
            if (mysqli_stmt_execute($stmtInsert)) {
                $newId = mysqli_insert_id($conexion);
                echo json_encode(['success' => 'Usuario creado correctamente.', 'id_usuario' => $newId]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al registrar el usuario en la base de datos.']);
            }
            mysqli_stmt_close($stmtInsert);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al preparar el registro de usuario.']);
        }
        break;

    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido.']);
            exit;
        }

        $id_usuario = isset($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : 0;
        $nombre = trim($_POST['nombre'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $avatar_url = trim($_POST['avatar_url'] ?? '');
        $rol = trim($_POST['rol'] ?? 'lector');
        $password = trim($_POST['password'] ?? '');

        if ($id_usuario <= 0 || $nombre === '' || $apellidos === '' || $email === '') {
            http_response_code(400);
            echo json_encode(['error' => 'ID de usuario o campos obligatorios faltantes.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'El formato del correo electrónico no es válido.']);
            exit;
        }

        if ($rol !== 'admin' && $rol !== 'editor' && $rol !== 'lector') {
            http_response_code(400);
            echo json_encode(['error' => 'El rol seleccionado no es válido.']);
            exit;
        }

        // Validación crítica: Un administrador no puede cambiarse su propio rol
        prevent_self_action($id_usuario, 'demote', $rol);

        // Verificar que el correo electrónico no esté registrado por otro usuario
        $sqlEmail = "SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ? LIMIT 1";
        $stmtEmail = mysqli_prepare($conexion, $sqlEmail);
        if ($stmtEmail) {
            mysqli_stmt_bind_param($stmtEmail, "si", $email, $id_usuario);
            mysqli_stmt_execute($stmtEmail);
            $resEmail = mysqli_stmt_get_result($stmtEmail);
            if (mysqli_num_rows($resEmail) > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'El correo electrónico ya está en uso por otro usuario.']);
                mysqli_stmt_close($stmtEmail);
                exit;
            }
            mysqli_stmt_close($stmtEmail);
        }

        // Preparar actualización
        $avatar_param = $avatar_url !== '' ? $avatar_url : null;
        $tel_param = $telefono !== '' ? $telefono : null;

        if ($password !== '') {
            // Actualizar con cambio de contraseña
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $sqlUpdate = "UPDATE usuarios SET nombre = ?, apellidos = ?, email = ?, telefono = ?, avatar_url = ?, rol = ?, password_hash = ? WHERE id_usuario = ?";
            $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
            if ($stmtUpdate) {
                mysqli_stmt_bind_param($stmtUpdate, "sssssssi", $nombre, $apellidos, $email, $tel_param, $avatar_param, $rol, $passwordHash, $id_usuario);
            }
        } else {
            // Actualizar sin cambio de contraseña
            $sqlUpdate = "UPDATE usuarios SET nombre = ?, apellidos = ?, email = ?, telefono = ?, avatar_url = ?, rol = ? WHERE id_usuario = ?";
            $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
            if ($stmtUpdate) {
                mysqli_stmt_bind_param($stmtUpdate, "ssssssi", $nombre, $apellidos, $email, $tel_param, $avatar_param, $rol, $id_usuario);
            }
        }

        if ($stmtUpdate) {
            if (mysqli_stmt_execute($stmtUpdate)) {
                // Si el administrador modificó su propio perfil, actualizamos sus variables de sesión
                if ($id_usuario === (int)$_SESSION['usuario_id']) {
                    $_SESSION['nombre'] = $nombre;
                    $_SESSION['avatar_url'] = $avatar_param;
                }
                echo json_encode(['success' => 'Usuario actualizado correctamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al actualizar los datos en la base de datos.']);
            }
            mysqli_stmt_close($stmtUpdate);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al preparar la actualización del usuario.']);
        }
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido.']);
            exit;
        }

        $id_usuario = isset($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : 0;

        if ($id_usuario <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de usuario no válido.']);
            exit;
        }

        // Validación crítica: Un administrador no puede eliminarse a sí mismo
        prevent_self_action($id_usuario, 'delete');

        $sqlDel = "DELETE FROM usuarios WHERE id_usuario = ?";
        $stmtDel = mysqli_prepare($conexion, $sqlDel);
        if ($stmtDel) {
            mysqli_stmt_bind_param($stmtDel, "i", $id_usuario);
            if (mysqli_stmt_execute($stmtDel)) {
                echo json_encode(['success' => 'Usuario eliminado correctamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al eliminar el usuario de la base de datos.']);
            }
            mysqli_stmt_close($stmtDel);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al preparar la eliminación del usuario.']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida.']);
        break;
}

mysqli_close($conexion);
?>
