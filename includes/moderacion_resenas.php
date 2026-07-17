<?php
require_once "../config/conexion.php";
require_once __DIR__ . "/seguridad.php";
require_admin_or_editor('../index.php');

// Inicializar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$successMsg = $_SESSION['success'] ?? null;
$errorMsg = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Procesar aprobación o rechazo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!$csrfToken || $csrfToken !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Token CSRF inválido.";
        header("Location: moderacion_resenas.php");
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    $id_resena = isset($_POST['id_resena']) ? (int)$_POST['id_resena'] : 0;
    
    if ($id_resena > 0 && ($action === 'aprobar' || $action === 'rechazar')) {
        if ($action === 'aprobar') {
            $stmt = mysqli_prepare($conexion, "UPDATE resenas SET estado = 'aprobada' WHERE id_resena = ?");
            mysqli_stmt_bind_param($stmt, "i", $id_resena);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = "La reseña ha sido aprobada con éxito.";
            } else {
                $_SESSION['error'] = "Error al aprobar la reseña.";
            }
            mysqli_stmt_close($stmt);
        } elseif ($action === 'rechazar') {
            // Rechazar: Poner el comentario a NULL y el estado a 'aprobada' (manteniendo la puntuación de estrellas activa)
            $stmt = mysqli_prepare($conexion, "UPDATE resenas SET comentario = NULL, estado = 'aprobada' WHERE id_resena = ?");
            mysqli_stmt_bind_param($stmt, "i", $id_resena);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = "La reseña ha sido rechazada. El texto se ha eliminado, pero la valoración de estrellas se mantiene.";
            } else {
                $_SESSION['error'] = "Error al rechazar la reseña.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    header("Location: moderacion_resenas.php");
    exit;
}

// Consultar todas las reseñas en estado 'pendiente'
$pendingReviews = [];
$sqlPending = "SELECT r.*, u.username, u.nombre as user_nombre, u.apellidos as user_apellidos, u.avatar_url,
                      t.titulo as movie_titulo, t.poster_url as movie_poster 
               FROM resenas r 
               JOIN usuarios u ON r.id_usuario = u.id_usuario 
               JOIN trailers t ON r.id_trailer = t.id_trailer 
               WHERE r.estado = 'pendiente' 
               ORDER BY r.fecha_alta ASC";
$resPending = mysqli_query($conexion, $sqlPending);
if ($resPending) {
    while ($row = mysqli_fetch_assoc($resPending)) {
        $pendingReviews[] = $row;
    }
    mysqli_free_result($resPending);
}

mysqli_close($conexion);

$pageTitle = "Moderación de Reseñas - Movie Trailer Hub";
$rootPath = "../";
require_once "navbar.php";
?>

<main class="app-container">
    <div class="mb-24" style="margin-top: 24px;">
        <h2 class="section-title m-0">Moderación de Reseñas</h2>
        <p class="text-muted-helper">Aquí puedes revisar y moderar los comentarios redactados por los usuarios antes de que sean visibles públicamente.</p>
    </div>

    <!-- Mensajes de éxito / error -->
    <?php if ($successMsg): ?>
        <div class="alerta" style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: #22c55e; padding: 12px; border-radius: var(--radius-sm); margin-bottom: 20px;">
            <p><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($successMsg) ?></p>
        </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="alerta" style="background: rgba(220, 38, 38, 0.1); border: 1px solid rgba(220, 38, 38, 0.3); color: var(--secondary); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 20px;">
            <p><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($errorMsg) ?></p>
        </div>
    <?php endif; ?>

    <!-- Listado de reseñas pendientes -->
    <?php if (empty($pendingReviews)): ?>
        <div class="login-prompt-card" style="padding: 30px; text-align: center; border: 1px dashed var(--border-color); border-radius: var(--radius-md); background: rgba(255, 255, 255, 0.01);">
            <p style="color: var(--text-muted); font-size: 14px;">
                <i class="fa-solid fa-circle-info" style="color: var(--primary); margin-right: 6px;"></i> No hay comentarios pendientes de moderación en este momento.
            </p>
        </div>
    <?php else: ?>
        <div class="moderation-list">
            <?php foreach ($pendingReviews as $rev): ?>
                <div class="write-review-card" style="background: var(--bg-surface-elevated); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid rgba(216, 195, 173, 0.1); padding-bottom: 12px; margin-bottom: 12px; flex-wrap: wrap; gap: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <?php if (!empty($rev['avatar_url'])): ?>
                                <img src="<?= htmlspecialchars($rev['avatar_url']) ?>" alt="Avatar" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-color);">
                            <?php else: ?>
                                <div style="width: 38px; height: 38px; border-radius: 50%; background: var(--bg-base); display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-color);">
                                    <i class="fa-solid fa-user" style="color: var(--text-muted); font-size: 16px;"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <strong style="color: var(--text-primary); font-size: 14px;"><?= htmlspecialchars($rev['user_nombre'] . ' ' . $rev['user_apellidos']) ?></strong>
                                <span style="font-size: 11px; color: var(--text-muted); display: block;">@<?= htmlspecialchars($rev['username']) ?></span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <strong style="color: var(--primary); font-size: 14px;"><?= htmlspecialchars($rev['movie_titulo']) ?></strong>
                            <span style="font-size: 11px; color: var(--text-muted); display: block;">Valoración: ⭐ <?= htmlspecialchars((string)$rev['valoracion']) ?>/5</span>
                        </div>
                    </div>
                    
                    <div style="background: rgba(0, 0, 0, 0.2); padding: 14px; border-radius: var(--radius-sm); border: 1px solid rgba(216, 195, 173, 0.05); margin-bottom: 16px;">
                        <p style="color: var(--text-primary); font-size: 13px; line-height: 1.6; white-space: pre-wrap; margin: 0;"><?= htmlspecialchars($rev['comentario']) ?></p>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 12px;">
                        <form action="" method="POST" style="margin:0;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="id_resena" value="<?= $rev['id_resena'] ?>">
                            <input type="hidden" name="action" value="rechazar">
                            <button type="submit" class="btn btn-secondary" onclick="return confirm('¿Rechazar este comentario? El texto se eliminará de forma permanente, pero la valoración de estrellas se mantendrá.');" style="background: transparent; color: var(--secondary); border: 1px solid var(--secondary); display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; font-size: 12px; font-weight: 600; cursor: pointer; border-radius: var(--radius-sm);">
                                <i class="fa-solid fa-xmark"></i> Rechazar Texto
                            </button>
                        </form>
                        
                        <form action="" method="POST" style="margin:0;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="id_resena" value="<?= $rev['id_resena'] ?>">
                            <input type="hidden" name="action" value="aprobar">
                            <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; font-size: 12px; font-weight: 600; cursor: pointer; border-radius: var(--radius-sm);">
                                <i class="fa-solid fa-check"></i> Aprobar Comentario
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<a class="volver" href="../index.php" style="margin-left: 24px; margin-bottom: 24px; display: inline-block;">← Volver al inicio</a>

<?php
require_once "footer.php";
?>
