<?php
include "../config/conexion.php";
require_once __DIR__ . "/../includes/seguridad.php";
require_admin_or_editor('../index.php');
define('BASE_PATH', '../');

$sql = "SELECT * FROM reparto ORDER BY id_reparto DESC";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
?>
<?php
$pageTitle = "Administrar Actores / Actrices";
$showNavbar = false;
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>
<main class="app-container table-compact-container">
    <h1>Administrar Actores y Actrices</h1>
    <p>Gestiona los miembros del reparto guardados en el sistema.</p>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nombre Completo</th>
                    <th>Edad</th>
                    <th>País</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($actor = mysqli_fetch_assoc($resultado)) { ?>
                    <tr>
                        <td>
                            <img src="<?php echo htmlspecialchars($actor["foto_url"] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200'); ?>" alt="Foto" class="actor-table-thumb">
                        </td>
                        <td><strong><?php echo htmlspecialchars($actor["nombre"] . ' ' . $actor["apellidos"]); ?></strong></td>
                        <td><?php echo $actor["edad"] ? htmlspecialchars((string)$actor["edad"]) . ' años' : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($actor["pais"] !== '' ? $actor["pais"] : 'N/A'); ?></td>
                        <td class="text-center nowrap">
                            <a class="btn-tabla btn-devolver" href="actor_peliculas.php?id=<?php echo $actor['id_reparto']; ?>">Ver Perfil</a>
                            <a class="btn-tabla btn-modificar" href="modificar_reparto.php?id=<?php echo $actor['id_reparto']; ?>">Modificar</a>
                            <?php if ($_SESSION['rol'] === 'admin'): ?>
                                <form action="eliminar_reparto.php" method="POST" class="table-action-form" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este actor? Se desvinculará de todos los trailers.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$actor['id_reparto'] ?>">
                                    <button type="submit" class="btn-tabla btn-eliminar">Eliminar</button>
                                </form>
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
