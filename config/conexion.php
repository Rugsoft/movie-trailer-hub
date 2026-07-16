<?php
if (session_status() === PHP_SESSION_NONE) {
    // Usar carpeta propia para las sesiones (compatible con InfinityFree y hosting compartido)
    $sessionPath = __DIR__ . '/../sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0755, true);
    }
    session_save_path($sessionPath);

    // Configurar cookie de sesión para que funcione bien en producción
    session_set_cookie_params([
        'lifetime' => 0,           // Sesión dura hasta cerrar el navegador
        'path'     => '/',         // Disponible en toda la web
        'secure'   => false,       // Cambiar a true si usas HTTPS
        'httponly' => true,        // No accesible desde JavaScript
        'samesite' => 'Lax',       // Protección CSRF básica
    ]);

    session_start();
}

// Detectar de forma dinámica si estamos en entorno local (CLI o localhost)
$isLocal = (php_sapi_name() === 'cli' || (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1')));

if ($isLocal) {
    $servidor = "localhost";
    $usuario = "root";
    $password = "";
    $base_datos = "movie_trailer_hub";
} else {
    $servidor = "sql108.infinityfree.com";
    $usuario = "if0_42320411";
    $password = "KiraKireta3";
    $base_datos = "if0_42320411_movie_trailer_hub";
}

// Conexión a la base de datos MySQL
$conexion = mysqli_connect($servidor, $usuario, $password, $base_datos);

if ($conexion) {
    if (!defined('DB_HOST')) define('DB_HOST', $servidor);
    if (!defined('DB_USER')) define('DB_USER', $usuario);
    if (!defined('DB_PASS')) define('DB_PASS', $password);
    if (!defined('DB_NAME')) define('DB_NAME', $base_datos);
}

// Verificar la conexión
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}
mysqli_set_charset($conexion, "utf8mb4");

// Configurar la zona horaria del proyecto (España)
date_default_timezone_set('Europe/Madrid');
mysqli_query($conexion, "SET time_zone = '+02:00'");

if (isset($_SESSION['usuario_id'])) {
    // Optimización: verificar racha solo una vez por día en la sesión
    if (!isset($_SESSION['racha_verificada_hoy']) || $_SESSION['racha_verificada_hoy'] !== date('Y-m-d')) {
        require_once __DIR__ . '/../badges/gamificacion_helper.php';
        actualizar_racha_login($conexion, $_SESSION['usuario_id']);
        $_SESSION['racha_verificada_hoy'] = date('Y-m-d');
    }
}

