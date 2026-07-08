<?php
require_once "../config/conexion.php";
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    $_SESSION['error'] = "Acceso denegado. Se requieren permisos de administrador.";
    header("Location: ../index.php");
    exit;
}
define('BASE_PATH', '../');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT * FROM directores WHERE id_director = ? LIMIT 1";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$director = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($stmt);

if (!$director) {
    echo "<h1>Director no encontrado</h1>";
    exit;
}
?>
<?php
$pageTitle = "Modificar Director";
$showNavbar = false;
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>
    <h1>Modificar Director</h1>
    <p>Actualizar la ficha de "<strong><?php echo htmlspecialchars($director['nombre'] . ' ' . $director['apellidos']); ?></strong>" en el sistema.</p>

    <form action="procesar_modificar_director.php" method="POST">
        <input type="hidden" name="id_director" value="<?php echo $director['id_director']; ?>">

        <label for="nombre">Nombre *</label>
        <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($director['nombre']); ?>">

        <label for="apellidos">Apellidos *</label>
        <input type="text" id="apellidos" name="apellidos" required value="<?php echo htmlspecialchars($director['apellidos']); ?>">

        <label for="edad">Edad (años):</label>
        <input type="number" id="edad" name="edad" min="1" max="120" value="<?php echo $director['edad'] ? htmlspecialchars((string)$director['edad']) : ''; ?>">

        <label for="pais">País de origen:</label>
        <input type="text" id="pais" name="pais" value="<?php echo htmlspecialchars($director['pais'] ?? ''); ?>">

        <button type="submit">Guardar Cambios</button>
    </form>

    <a class="volver" href="listar_directores.php">← Volver al catálogo de directores</a>
<?php
require_once $rootPath . 'includes/footer.php';
mysqli_close($conexion);
?>
