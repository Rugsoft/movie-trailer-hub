<?php
require_once "../config/conexion.php";
require_once __DIR__ . "/../includes/seguridad.php";
require_admin_or_editor('../index.php');
define('BASE_PATH', '../');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT * FROM reparto WHERE id_reparto = ? LIMIT 1";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$actor = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($stmt);

if (!$actor) {
    echo "<h1>Actor no encontrado</h1>";
    exit;
}
?>
<?php
$pageTitle = "Modificar Actor / Actriz";
$showNavbar = false;
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>
    <h1>Modificar Actor / Actriz</h1>
    <p>Actualizar la ficha de "<strong><?php echo htmlspecialchars($actor['nombre'] . ' ' . $actor['apellidos']); ?></strong>" en el sistema.</p>

    <form action="procesar_modificar_reparto.php" method="POST">
        <input type="hidden" name="id_reparto" value="<?php echo $actor['id_reparto']; ?>">

        <label for="nombre">Nombre *</label>
        <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($actor['nombre']); ?>">

        <label for="apellidos">Apellidos *</label>
        <input type="text" id="apellidos" name="apellidos" required value="<?php echo htmlspecialchars($actor['apellidos']); ?>">

        <label for="edad">Edad (años):</label>
        <input type="number" id="edad" name="edad" min="1" max="120" value="<?php echo $actor['edad'] ? htmlspecialchars((string)$actor['edad']) : ''; ?>">

        <label for="pais">País de origen:</label>
        <input type="text" id="pais" name="pais" value="<?php echo htmlspecialchars($actor['pais'] ?? ''); ?>">

        <label for="foto_url">URL de la Foto (Avatar/Retrato):</label>
        <input type="url" id="foto_url" name="foto_url" value="<?php echo htmlspecialchars($actor['foto_url'] ?? ''); ?>">

        <button type="submit">Guardar Cambios</button>
    </form>

    <a class="volver" href="listar_reparto.php">← Volver al catálogo de reparto</a>
<?php
require_once $rootPath . 'includes/footer.php';
mysqli_close($conexion);
?>
