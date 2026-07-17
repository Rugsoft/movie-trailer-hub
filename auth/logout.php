<?php
require_once "../config/conexion.php";
require_once __DIR__ . "/../includes/seguridad.php";

require_post();
require_csrf();

$_SESSION = [];
session_destroy();
session_start();
$_SESSION["success"] = "Sesión cerrada correctamente.";
header("Location: ../index.php");
exit;
?>
