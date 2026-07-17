<?php
// includes/seguridad.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Obtiene el token CSRF de la sesión y lo crea cuando todavía no existe.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Genera el campo oculto que deben incluir los formularios protegidos.
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8')
        . '">';
}

/**
 * Rechaza cualquier método HTTP distinto de POST.
 */
function require_post(bool $isAjax = false): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        return;
    }

    header('Allow: POST');
    http_response_code(405);

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Método no permitido.']);
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Método no permitido.';
    }

    exit;
}

/**
 * Valida el token CSRF recibido por formulario o cabecera HTTP.
 */
function require_csrf(bool $isAjax = false): void {
    $receivedToken = $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? $_POST['csrf_token']
        ?? '';

    if (
        is_string($receivedToken)
        && $receivedToken !== ''
        && hash_equals(csrf_token(), $receivedToken)
    ) {
        return;
    }

    http_response_code(403);

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Token CSRF inválido o ausente.']);
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Petición rechazada: token CSRF inválido o ausente.';
    }

    exit;
}

/**
 * Comprueba si el usuario actual posee uno o varios de los roles especificados.
 */
function has_role($allowedRoles): bool {
    return isset($_SESSION['rol']) && in_array($_SESSION['rol'], (array)$allowedRoles, true);
}

/**
 * Exige que el usuario posea uno de los roles permitidos. 
 * En caso de denegación, detecta si es petición AJAX para retornar JSON 403, 
 * o redirige a la URL indicada con un mensaje de error.
 */
function require_role($allowedRoles, $redirectUrl = '../index.php', $errorMessage = "Acceso denegado. Permisos insuficientes.", $isAjax = null) {
    $userRole = $_SESSION['rol'] ?? null;
    
    if ($isAjax === null) {
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') 
                  || (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
                  || (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }

    if (!in_array($userRole, (array)$allowedRoles, true)) {
        if (isset($_SESSION['usuario_id'])) {
            global $conexion;
            if (!isset($conexion)) {
                $configPath = __DIR__ . '/../config/conexion.php';
                if (file_exists($configPath)) {
                    require_once $configPath;
                }
            }
            if (isset($conexion)) {
                $id_usuario = (int)$_SESSION['usuario_id'];
                mysqli_query($conexion, "INSERT INTO usuario_gamificacion_stats (id_usuario, intentos_fallidos_admin) 
                                         VALUES ($id_usuario, 1) 
                                         ON DUPLICATE KEY UPDATE intentos_fallidos_admin = 1");
            }
        }

        if ($isAjax) {
            http_response_code(403);
            echo json_encode(['error' => $errorMessage]);
            exit;
        } else {
            $_SESSION['error'] = $errorMessage;
            header("Location: " . $redirectUrl);
            exit;
        }
    }
}

/**
 * Exige rol estricto de Administrador.
 */
function require_admin($redirectUrl = '../index.php', $isAjax = null) {
    require_role(['admin'], $redirectUrl, "Acceso denegado. Se requieren permisos de administrador.", $isAjax);
}

/**
 * Exige rol de Administrador o Editor.
 */
function require_admin_or_editor($redirectUrl = '../index.php', $isAjax = null) {
    require_role(['admin', 'editor'], $redirectUrl, "Acceso denegado. Se requieren permisos de administrador o editor.", $isAjax);
}

/**
 * Exige que el usuario esté autenticado en el sistema.
 */
function require_login($redirectUrl = 'login.php', $errorMessage = "Debes iniciar sesión para acceder a esta sección.") {
    if (!isset($_SESSION['usuario_id'])) {
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
        if ($isAjax) {
            http_response_code(401);
            echo json_encode(['error' => $errorMessage]);
            exit;
        } else {
            $_SESSION['error'] = $errorMessage;
            header("Location: " . $redirectUrl);
            exit;
        }
    }
}

/**
 * Previene acciones sobre la propia cuenta (autobloqueo/autoborrado) para administradores.
 */
function prevent_self_action($target_id, $action = 'delete', $new_role = '') {
    if (isset($_SESSION['usuario_id']) && (int)$target_id === (int)$_SESSION['usuario_id']) {
        if ($action === 'delete') {
            http_response_code(403);
            echo json_encode(['error' => 'No puedes eliminar tu propia cuenta de administrador.']);
            exit;
        }
        if ($action === 'demote' && $new_role !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'No puedes cambiar tu propio rol de administrador para evitar bloquear tu acceso.']);
            exit;
        }
    }
}
