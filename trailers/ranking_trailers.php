<?php
include "../config/conexion.php";
define('BASE_PATH', '../');

$sql = "SELECT t.*, GROUP_CONCAT(g.nombre SEPARATOR ', ') as genero
        FROM trailers t
        LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
        LEFT JOIN generos g ON tg.id_genero = g.id_genero
        GROUP BY t.id_trailer
        ORDER BY t.valoracion DESC, t.titulo ASC LIMIT 10";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
?>
<?php
$pageTitle = "Ranking de Trailers";
$showNavbar = false;
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>
    <h1>Ranking de Popularidad</h1>
    <p>Los 10 trailers mejor valorados por nuestros usuarios en la plataforma.</p>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 60px; text-align: center;">Puesto</th>
                    <th>Portada</th>
                    <th>Título</th>
                    <th>Director</th>
                    <th>Fecha de Estreno</th>
                    <th>Género</th>
                    <th>Valoración</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $puesto = 1;
                while ($trailer = mysqli_fetch_assoc($resultado)) { 
                ?>
                    <tr>
                        <td class="text-center"><span class="rank-number">#<?php echo $puesto++; ?></span></td>
                        <td>
                            <img src="<?php echo htmlspecialchars($trailer["poster_url"] ?? 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=600'); ?>" alt="Poster" class="poster-mini">
                        </td>
                        <td><strong><?php echo htmlspecialchars($trailer["titulo"]); ?></strong></td>
                        <td><?php echo htmlspecialchars($trailer["director"] ?? 'N/A'); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($trailer["release_date"])); ?></td>
                        <td><?php echo htmlspecialchars($trailer["genero"]); ?></td>
                        <td>⭐ <strong><?php echo htmlspecialchars((string)$trailer["valoracion"]); ?></strong>/10</td>
                        <td class="text-center nowrap">
                            <a class="btn-tabla btn-devolver" href="reproducir_trailer.php?id=<?php echo $trailer['id_trailer']; ?>">Ver</a>
                            <a class="btn-tabla btn-modificar" href="modificar_trailer.php?id=<?php echo $trailer['id_trailer']; ?>">Modificar</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <a class="volver" href="../index.php">← Volver al inicio</a>
</body>
</html>
<?php
mysqli_stmt_close($stmt);
mysqli_close($conexion);
?>
