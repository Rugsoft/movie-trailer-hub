<?php
include "../config/conexion.php";
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'editor')) {
    $_SESSION['error'] = "Acceso denegado. Se requieren permisos de administrador o editor.";
    header("Location: ../index.php");
    exit;
}
define('BASE_PATH', '../');

$sql = "SELECT * FROM directores ORDER BY id_director DESC";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
?>
<?php
$pageTitle = "Administrar Directores";
$showNavbar = false;
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>
<main class="app-container table-compact-container">
    <h1>Administrar Directores</h1>
    <p>Gestiona los directores y cineastas guardados en el sistema.</p>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nombre Completo</th>
                    <th>Edad</th>
                    <th>País</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($director = mysqli_fetch_assoc($resultado)) { ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($director["nombre"] . ' ' . $director["apellidos"]); ?></strong></td>
                        <td><?php echo $director["edad"] ? htmlspecialchars((string)$director["edad"]) . ' años' : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($director["pais"] !== '' ? $director["pais"] : 'N/A'); ?></td>
                        <td class="text-center nowrap">
                            <a class="btn-tabla btn-devolver" href="director_peliculas.php?id=<?php echo $director['id_director']; ?>">Ver Perfil</a>
                            <a class="btn-tabla btn-modificar" href="modificar_director.php?id=<?php echo $director['id_director']; ?>">Modificar</a>
                            <?php if ($_SESSION['rol'] === 'admin'): ?>
                                <a class="btn-tabla btn-eliminar" href="eliminar_director.php?id=<?php echo $director['id_director']; ?>" onclick="return confirm('¿Estás seguro de que deseas eliminar este director? Se desvinculará de todos sus trailers.');">Eliminar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <a class="volver" href="../index.php">← Volver al inicio</a>
</main>
<?php
require_once $rootPath . 'includes/footer.php';
mysqli_stmt_close($stmt);
mysqli_close($conexion);
?>
