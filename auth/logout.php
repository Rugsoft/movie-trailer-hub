<?php
require_once "../config/conexion.php";
$_SESSION = [];
session_destroy();
session_start();
$_SESSION["success"] = "Sesión cerrada correctamente.";
header("Location: ../index.php");
exit;
?>
