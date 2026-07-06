<?php
if (session_status() === PHP_SESSION_NONE) {
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
?>
