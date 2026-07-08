<?php
require_once "../config/conexion.php";
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    $_SESSION['error'] = "Acceso denegado. Se requieren permisos de administrador.";
    header("Location: ../index.php");
    exit;
}
define('BASE_PATH', '../');
?>
<?php
$pageTitle = "Añadir Actor / Actriz";
$showNavbar = false;
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>
    <h1>Añadir Actor / Actriz</h1>
    <p>Registra un nuevo miembro del reparto para poder asociarlo a los trailers.</p>

    <form action="procesar_reparto.php" method="POST">
        <label for="nombre">Nombre *</label>
        <input type="text" id="nombre" name="nombre" required placeholder="Ej: Matthew">

        <label for="apellidos">Apellidos *</label>
        <input type="text" id="apellidos" name="apellidos" required placeholder="Ej: McConaughey">

        <label for="edad">Edad (años):</label>
        <input type="number" id="edad" name="edad" min="1" max="120" placeholder="Ej: 54">

        <label for="pais">País de origen:</label>
        <input type="text" id="pais" name="pais" placeholder="Ej: Estados Unidos">

        <label for="foto_url">URL de la Foto (Avatar/Retrato):</label>
        <input type="url" id="foto_url" name="foto_url" placeholder="Ej: https://enlace-imagen-actor.jpg">

        <button type="submit">Guardar Actor/Actriz</button>
    </form>

    <a class="volver" href="../index.php">← Volver al inicio</a>
<?php
require_once $rootPath . 'includes/footer.php';
?>
