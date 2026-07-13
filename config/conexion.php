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

$servidor = "localhost";
$usuario = "root";
$password = "";
$base_datos = "movie_trailer_hub";

// Conexión a la base de datos MySQL
$conexion = mysqli_connect($servidor, $usuario, $password, $base_datos);

// Verificar la conexión
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}
mysqli_set_charset($conexion, "utf8mb4");
