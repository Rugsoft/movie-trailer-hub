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
$pageTitle = "Añadir Director";
$showNavbar = false;
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>
    <h1>Añadir Director</h1>
    <p>Registra un nuevo director para poder asociarlo a los trailers.</p>

    <form action="procesar_director.php" method="POST">
        <label for="nombre">Nombre *</label>
        <input type="text" id="nombre" name="nombre" required placeholder="Ej: Christopher">

        <label for="apellidos">Apellidos *</label>
        <input type="text" id="apellidos" name="apellidos" required placeholder="Ej: Nolan">

        <label for="edad">Edad (años):</label>
        <input type="number" id="edad" name="edad" min="1" max="120" placeholder="Ej: 53">

        <label for="pais">País de origen:</label>
        <input type="text" id="pais" name="pais" placeholder="Ej: Reino Unido">

        <button type="submit">Guardar Director</button>
    </form>

    <a class="volver" href="../index.php">← Volver al inicio</a>
<?php
require_once $rootPath . 'includes/footer.php';
?>
