<?php
include "../config/conexion.php";
define('BASE_PATH', '../');

$buscar = trim($_GET["buscar"] ?? "");
$resultado = null;

if ($buscar !== "") {
    $sql = "SELECT t.*, GROUP_CONCAT(g.nombre SEPARATOR ', ') as genero,
                   CONCAT(d.nombre, ' ', d.apellidos) as director
            FROM trailers t
            LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
            LEFT JOIN generos g ON tg.id_genero = g.id_genero
            LEFT JOIN directores d ON t.id_director = d.id_director
            WHERE t.titulo LIKE ? OR CONCAT(d.nombre, ' ', d.apellidos) LIKE ?
            GROUP BY t.id_trailer
            ORDER BY t.id_trailer DESC";
    $stmt = mysqli_prepare($conexion, $sql);
    $param_buscar = "%" . $buscar . "%";
    mysqli_stmt_bind_param($stmt, "ss", $param_buscar, $param_buscar);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
}
?>
<?php
$pageTitle = "Buscar Trailers";
$showNavbar = false;
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>
    <h1>Buscar Trailers</h1>
    <p>Encuentra tus trailers favoritos buscando por título o director.</p>

    <form action="buscar_trailers.php" method="GET">
        <label for="buscar">Buscar película:</label>
        <input type="text" id="buscar" name="buscar" required value="<?php echo htmlspecialchars($buscar); ?>" placeholder="Ej: Nolan, Interstellar...">
        <button type="submit">Buscar</button>
    </form>

    <?php if ($buscar !== ""): ?>
        <h2 class="section-title">Resultados de búsqueda para "<?php echo htmlspecialchars($buscar); ?>"</h2>
        
        <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Portada</th>
                            <th>Título</th>
                            <th>Director</th>
                            <th>Fecha de Estreno</th>
                            <th>Género</th>
                            <th>Duración</th>
                            <th>Valoración</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($trailer = mysqli_fetch_assoc($resultado)) { ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($trailer["poster_url"] ?? 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=600'); ?>" alt="Poster" class="poster-mini">
                                </td>
                                <td><strong><?php echo htmlspecialchars($trailer["titulo"]); ?></strong></td>
                                <td><?php echo htmlspecialchars($trailer["director"] ?? 'N/A'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($trailer["release_date"])); ?></td>
                                <td><?php echo htmlspecialchars($trailer["genero"]); ?></td>
                                <td><?php echo htmlspecialchars((string)$trailer["duracion"]); ?> min</td>
                                <td>⭐ <?php echo htmlspecialchars((string)$trailer["valoracion"]); ?>/10</td>
                                <td class="text-center nowrap">
                                    <a class="btn-tabla btn-devolver" href="reproducir_trailer.php?id=<?php echo $trailer['id_trailer']; ?>">Ver</a>
                                    <a class="btn-tabla btn-modificar" href="modificar_trailer.php?id=<?php echo $trailer['id_trailer']; ?>">Modificar</a>
                                    <a class="btn-tabla btn-eliminar" href="eliminar_trailer.php?id=<?php echo $trailer['id_trailer']; ?>" onclick="return confirm('¿Estás seguro de que deseas eliminar este trailer?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php mysqli_stmt_close($stmt); ?>
        <?php else: ?>
            <div class="alerta">
                <p>No se encontraron trailers que coincidan con la búsqueda.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <a class="volver" href="../index.php">← Volver al inicio</a>
<?php
require_once $rootPath . 'includes/footer.php';
?>
<?php
mysqli_close($conexion);
?>
