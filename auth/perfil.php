<?php
require_once "../config/conexion.php";
require_once __DIR__ . "/../includes/seguridad.php";

// 1. Validar que el usuario esté autenticado
require_login('login.php', "Debes iniciar sesión para acceder a tu perfil.");

define('BASE_PATH', '../');

// 2. Automigración de la base de datos (Añadir columna avatar_url si no existe)
$checkCol = mysqli_query($conexion, "SHOW COLUMNS FROM usuarios LIKE 'avatar_url'");
if (mysqli_num_rows($checkCol) == 0) {
    mysqli_query($conexion, "ALTER TABLE usuarios ADD COLUMN avatar_url VARCHAR(255) DEFAULT NULL");
}

// Crear tabla listas_personales si no existe
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS listas_personales (
    id_lista INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_trailer INT NOT NULL,
    estado VARCHAR(20) NOT NULL,
    fecha_adicion DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuario_trailer_lista (id_usuario, id_trailer),
    CONSTRAINT fk_listas_trailers FOREIGN KEY (id_trailer) REFERENCES trailers(id_trailer) ON DELETE CASCADE,
    CONSTRAINT fk_listas_usuarios FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Crear tabla comentarios_privados si no existe
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS comentarios_privados (
    id_comentario_privado INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_trailer INT NOT NULL,
    comentario TEXT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuario_trailer_comentario (id_usuario, id_trailer),
    CONSTRAINT fk_comentarios_priv_trailers FOREIGN KEY (id_trailer) REFERENCES trailers(id_trailer) ON DELETE CASCADE,
    CONSTRAINT fk_comentarios_priv_usuarios FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Crear tabla historial_comentarios_privados si no existe
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS historial_comentarios_privados (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_trailer INT NOT NULL,
    comentario_anterior TEXT NOT NULL,
    fecha_cambio DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_historial_trailers FOREIGN KEY (id_trailer) REFERENCES trailers(id_trailer) ON DELETE CASCADE,
    CONSTRAINT fk_historial_usuarios FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$successMsg = $_SESSION['success'] ?? null;
$errorMsg = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$user_id = $_SESSION['usuario_id'];
$error = null;

// 3. Procesar el formulario de edición
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $apellidos = trim($_POST["apellidos"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $avatar_url = trim($_POST["avatar_url"] ?? "");
    
    $password_actual = trim($_POST["password_actual"] ?? "");
    $password_nueva = trim($_POST["password_nueva"] ?? "");
    $password_confirm = trim($_POST["password_confirm"] ?? "");

    // Validar datos básicos
    if ($nombre === "" || $apellidos === "" || $email === "") {
        $error = "Nombre, Apellidos y Correo Electrónico son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El correo electrónico no tiene un formato válido.";
    } else {
        // Verificar que el correo no esté ocupado por otro usuario
        $sqlEmail = "SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ? LIMIT 1";
        $stmtEmail = mysqli_prepare($conexion, $sqlEmail);
        if ($stmtEmail) {
            mysqli_stmt_bind_param($stmtEmail, "si", $email, $user_id);
            mysqli_stmt_execute($stmtEmail);
            $resEmail = mysqli_stmt_get_result($stmtEmail);
            if (mysqli_num_rows($resEmail) > 0) {
                $error = "El correo electrónico ya está registrado por otro usuario.";
            }
            mysqli_stmt_close($stmtEmail);
        } else {
            $error = "Error al verificar el correo electrónico en la base de datos.";
        }

        // Si no hay error con el correo, evaluar la contraseña
        if (!$error) {
            $cambiar_pass = false;
            $pass_hash = "";

            if ($password_actual !== "" || $password_nueva !== "" || $password_confirm !== "") {
                if ($password_actual === "" || $password_nueva === "" || $password_confirm === "") {
                    $error = "Para cambiar tu contraseña debes rellenar la actual, la nueva y su confirmación.";
                } elseif ($password_nueva !== $password_confirm) {
                    $error = "La nueva contraseña y la confirmación no coinciden.";
                } else {
                    // Obtener la contraseña actual de la DB para verificarla
                    $sqlHash = "SELECT password_hash FROM usuarios WHERE id_usuario = ? LIMIT 1";
                    $stmtHash = mysqli_prepare($conexion, $sqlHash);
                    if ($stmtHash) {
                        mysqli_stmt_bind_param($stmtHash, "i", $user_id);
                        mysqli_stmt_execute($stmtHash);
                        $resHash = mysqli_stmt_get_result($stmtHash);
                        $rowHash = mysqli_fetch_assoc($resHash);
                        mysqli_stmt_close($stmtHash);

                        if ($rowHash && password_verify($password_actual, $rowHash['password_hash'])) {
                            $cambiar_pass = true;
                            $pass_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
                        } else {
                            $error = "La contraseña actual es incorrecta.";
                        }
                    } else {
                        $error = "Error al verificar la contraseña en la base de datos.";
                    }
                }
            }
        }

        // Proceder con la actualización si no hay errores
        if (!$error) {
            if ($cambiar_pass) {
                $sqlUpdate = "UPDATE usuarios SET nombre = ?, apellidos = ?, email = ?, telefono = ?, avatar_url = ?, password_hash = ? WHERE id_usuario = ?";
                $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
                if ($stmtUpdate) {
                    mysqli_stmt_bind_param($stmtUpdate, "ssssssi", $nombre, $apellidos, $email, $telefono, $avatar_url, $pass_hash, $user_id);
                }
            } else {
                $sqlUpdate = "UPDATE usuarios SET nombre = ?, apellidos = ?, email = ?, telefono = ?, avatar_url = ? WHERE id_usuario = ?";
                $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
                if ($stmtUpdate) {
                    mysqli_stmt_bind_param($stmtUpdate, "sssssi", $nombre, $apellidos, $email, $telefono, $avatar_url, $user_id);
                }
            }

            if ($stmtUpdate) {
                if (mysqli_stmt_execute($stmtUpdate)) {
                    $_SESSION["success"] = "Perfil actualizado correctamente.";
                    $_SESSION["nombre"] = $nombre;
                    $_SESSION["avatar_url"] = $avatar_url !== "" ? $avatar_url : null;
                    mysqli_stmt_close($stmtUpdate);
                    header("Location: perfil.php");
                    exit;
                } else {
                    $error = "Error al actualizar los datos en la base de datos.";
                }
                mysqli_stmt_close($stmtUpdate);
            } else {
                $error = "Error de preparación SQL al actualizar el perfil.";
            }
        }
    }
    
    if ($error) {
        $_SESSION["error"] = $error;
        header("Location: perfil.php");
        exit;
    }
}

// 4. Obtener datos actuales del usuario para llenar el formulario
$sqlUser = "SELECT * FROM usuarios WHERE id_usuario = ? LIMIT 1";
$stmtUser = mysqli_prepare($conexion, $sqlUser);
if (!$stmtUser) {
    die("Error al preparar la consulta de datos del usuario: " . mysqli_error($conexion));
}
mysqli_stmt_bind_param($stmtUser, "i", $user_id);
mysqli_stmt_execute($stmtUser);
$resUser = mysqli_stmt_get_result($stmtUser);
$user = mysqli_fetch_assoc($resUser);
mysqli_stmt_close($stmtUser);

if (!$user) {
    die("Usuario no encontrado en el sistema.");
}

// Obtener el historial de visualizaciones del usuario
$history = [];
$sqlHistory = "SELECT v.id_visualizacion, v.fecha_visualizacion, v.dispositivo, t.id_trailer, t.titulo, t.poster_url 
               FROM visualizaciones v
               JOIN trailers t ON v.id_trailer = t.id_trailer
               WHERE v.id_usuario = ?
               ORDER BY v.fecha_visualizacion DESC
               LIMIT 20";
$stmtHistory = mysqli_prepare($conexion, $sqlHistory);
if ($stmtHistory) {
    mysqli_stmt_bind_param($stmtHistory, "i", $user_id);
    mysqli_stmt_execute($stmtHistory);
    $resHistory = mysqli_stmt_get_result($stmtHistory);
    while ($row = mysqli_fetch_assoc($resHistory)) {
        $history[] = $row;
    }
    mysqli_stmt_close($stmtHistory);
}

// === CONSULTAS DE ESTADÍSTICAS PARA EL GRÁFICO (Wrapped) ===
// 1. Estadísticas generales: total vistas y tiempo acumulado
$sqlStatsGeneral = "SELECT COUNT(v.id_visualizacion) as total_vistas, COALESCE(SUM(t.duracion), 0) as total_minutos 
                    FROM visualizaciones v 
                    JOIN trailers t ON v.id_trailer = t.id_trailer 
                    WHERE v.id_usuario = ?";
$stmtSG = mysqli_prepare($conexion, $sqlStatsGeneral);
$totalVistas = 0;
$totalMinutos = 0;
if ($stmtSG) {
    mysqli_stmt_bind_param($stmtSG, "i", $user_id);
    mysqli_stmt_execute($stmtSG);
    $resSG = mysqli_stmt_get_result($stmtSG);
    if ($rowSG = mysqli_fetch_assoc($resSG)) {
        $totalVistas = (int)$rowSG['total_vistas'];
        $totalMinutos = (int)$rowSG['total_minutos'];
    }
    mysqli_stmt_close($stmtSG);
}

// Convertir minutos a formato legible (Largometrajes)
$totalHoras = floor($totalMinutos / 60);
$restoMinutos = $totalMinutos % 60;
$tiempoReproduccion = "";
if ($totalHoras > 0) {
    $tiempoReproduccion = $totalHoras . " h " . $restoMinutos . " min";
} else {
    $tiempoReproduccion = $totalMinutos . " min";
}

// Estimar tiempo real viendo trailers (2.5 minutos de promedio por visualización)
$minutosTrailerEstimados = round($totalVistas * 2.5);
$horasTrailer = floor($minutosTrailerEstimados / 60);
$restoMinutosTrailer = $minutosTrailerEstimados % 60;
$tiempoTrailer = "";
if ($horasTrailer > 0) {
    $tiempoTrailer = $horasTrailer . " h " . $restoMinutosTrailer . " min";
} else {
    $tiempoTrailer = $minutosTrailerEstimados . " min";
}

// 2. Distribución de géneros (Top 5 para el gráfico)
$genresData = [];
$sqlStatsGenres = "SELECT g.nombre as genero, COUNT(*) as cantidad 
                   FROM visualizaciones v 
                   JOIN trailers_generos tg ON v.id_trailer = tg.id_trailer 
                   JOIN generos g ON tg.id_genero = g.id_genero 
                   WHERE v.id_usuario = ? 
                   GROUP BY g.id_genero 
                   ORDER BY cantidad DESC 
                   LIMIT 5";
$stmtSGenres = mysqli_prepare($conexion, $sqlStatsGenres);
if ($stmtSGenres) {
    mysqli_stmt_bind_param($stmtSGenres, "i", $user_id);
    mysqli_stmt_execute($stmtSGenres);
    $resSGenres = mysqli_stmt_get_result($stmtSGenres);
    while ($rowSG = mysqli_fetch_assoc($resSGenres)) {
        $genresData[] = $rowSG;
    }
    mysqli_stmt_close($stmtSGenres);
}

$generoFavorito = "Ninguno";
if (!empty($genresData)) {
    $generoFavorito = $genresData[0]['genero'];
}

// 3. Distribución horaria (inicializar 24 slots)
$hourlyData = array_fill(0, 24, 0);
$sqlStatsHourly = "SELECT HOUR(v.fecha_visualizacion) as hora, COUNT(*) as cantidad 
                   FROM visualizaciones v 
                   WHERE v.id_usuario = ? 
                   GROUP BY HOUR(v.fecha_visualizacion)";
$stmtSHourly = mysqli_prepare($conexion, $sqlStatsHourly);
if ($stmtSHourly) {
    mysqli_stmt_bind_param($stmtSHourly, "i", $user_id);
    mysqli_stmt_execute($stmtSHourly);
    $resSHourly = mysqli_stmt_get_result($stmtSHourly);
    while ($rowSHourly = mysqli_fetch_assoc($resSHourly)) {
        $h = (int)$rowSHourly['hora'];
        $hourlyData[$h] = (int)$rowSHourly['cantidad'];
    }
    mysqli_stmt_close($stmtSHourly);
}

// 4. Director favorito (Insight)
$directorFavorito = "No identificado";
$sqlStatsDirector = "SELECT CONCAT(d.nombre, ' ', d.apellidos) as director_nombre, COUNT(*) as cantidad 
                     FROM visualizaciones v 
                     JOIN trailers t ON v.id_trailer = t.id_trailer 
                     JOIN directores d ON t.id_director = d.id_director 
                     WHERE v.id_usuario = ? 
                     GROUP BY t.id_director 
                     ORDER BY cantidad DESC 
                     LIMIT 1";
$stmtSDirector = mysqli_prepare($conexion, $sqlStatsDirector);
if ($stmtSDirector) {
    mysqli_stmt_bind_param($stmtSDirector, "i", $user_id);
    mysqli_stmt_execute($stmtSDirector);
    $resSDirector = mysqli_stmt_get_result($stmtSDirector);
    if ($rowSD = mysqli_fetch_assoc($resSDirector)) {
        $directorFavorito = $rowSD['director_nombre'];
    }
    mysqli_stmt_close($stmtSDirector);
}

// Consultar las películas en las listas personales del usuario
$myList = [];
$sqlMyList = "SELECT lp.*, t.titulo, t.poster_url, cp.comentario
              FROM listas_personales lp
              JOIN trailers t ON lp.id_trailer = t.id_trailer
              LEFT JOIN comentarios_privados cp ON lp.id_trailer = cp.id_trailer AND cp.id_usuario = lp.id_usuario
              WHERE lp.id_usuario = ?
              ORDER BY lp.fecha_adicion DESC";
$stmtMyList = mysqli_prepare($conexion, $sqlMyList);
if ($stmtMyList) {
    mysqli_stmt_bind_param($stmtMyList, "i", $user_id);
    mysqli_stmt_execute($stmtMyList);
    $resMyList = mysqli_stmt_get_result($stmtMyList);
    while ($row = mysqli_fetch_assoc($resMyList)) {
        $myList[] = $row;
    }
    mysqli_stmt_close($stmtMyList);
}

// Consultar todas las películas no añadidas aún para el buscador del panel
$allTrailersOption = [];
$sqlAllTrailers = "SELECT id_trailer, titulo 
                   FROM trailers 
                   WHERE id_trailer NOT IN (SELECT id_trailer FROM listas_personales WHERE id_usuario = ?) 
                   ORDER BY titulo ASC";
$stmtAllTrailers = mysqli_prepare($conexion, $sqlAllTrailers);
if ($stmtAllTrailers) {
    mysqli_stmt_bind_param($stmtAllTrailers, "i", $user_id);
    mysqli_stmt_execute($stmtAllTrailers);
    $resAllTrailers = mysqli_stmt_get_result($stmtAllTrailers);
    while ($row = mysqli_fetch_assoc($resAllTrailers)) {
        $allTrailersOption[] = $row;
    }
    mysqli_stmt_close($stmtAllTrailers);
}

$pageTitle = "Mi Perfil - Movie Trailer Hub";
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>

<main class="app-container" style="margin-top: 30px; margin-bottom: 50px;">
    <!-- Cargar Chart.js de forma local y global para asegurar su inicialización -->
    <script src="<?= BASE_PATH ?>js/chart.js"></script>
    
    <div style="text-align: center; margin-bottom: 25px;">
        <h1 style="margin-bottom: 8px;">Configuración de la Cuenta</h1>
        <p style="color: var(--text-muted); margin: 0;">Administra tus datos personales, avatar y revisa tus estadísticas cinematográficas.</p>
    </div>

    <!-- Pestañas del Perfil (Tabs) -->
    <div class="profile-tabs">
        <button class="profile-tab-btn active" data-tab="config">
            <i class="fa-solid fa-user-gear"></i> Mis Datos
        </button>
        <button class="profile-tab-btn" data-tab="movies">
            <i class="fa-solid fa-clapperboard"></i> Mis Películas
        </button>
        <button class="profile-tab-btn" data-tab="stats">
            <i class="fa-solid fa-chart-pie"></i> Mis Estadísticas
        </button>
        <button class="profile-tab-btn" data-tab="badges">
            <i class="fa-solid fa-trophy"></i> Mis Logros
        </button>
    </div>

    <!-- Pestaña 1: Configuración de Cuenta e Historial -->
    <div id="tab-config" class="tab-content active">
        <div class="profile-layout">
            
            <!-- Tarjeta Lateral Izquierda (Resumen) -->
            <aside class="profile-sidebar">
                <div id="avatarPreviewContainer" class="profile-avatar-preview">
                    <?php if (!empty($user['avatar_url'])): ?>
                        <img id="avatarImg" src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <i id="avatarIcon" class="fa-solid fa-user"></i>
                        <img id="avatarImg" src="" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; display: none;">
                    <?php endif; ?>
                </div>
                
                <h3 class="profile-username"><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellidos']); ?></h3>
                <span class="profile-role-badge"><?php echo htmlspecialchars($user['rol'] === 'admin' ? 'Administrador' : 'Lector'); ?></span>
                
                <div class="profile-meta-info">
                    <div class="profile-meta-row">
                        <span class="profile-meta-label">Usuario:</span>
                        <span class="profile-meta-value">@<?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="profile-meta-row">
                        <span class="profile-meta-label">Miembro desde:</span>
                        <span class="profile-meta-value"><?php echo date('d/m/Y', strtotime($user['fecha_alta'])); ?></span>
                    </div>
                </div>
            </aside>

            <!-- Formulario de Configuración -->
            <section class="profile-form-container">
                <form action="perfil.php" method="POST" autocomplete="off">
                    
                    <h3 class="profile-section-title"><i class="fa-solid fa-id-card"></i> Datos Personales</h3>
                    
                    <div class="profile-form-grid">
                        <div>
                            <label for="nombre">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($user['nombre']); ?>">
                        </div>
                        <div>
                            <label for="apellidos">Apellidos *</label>
                            <input type="text" id="apellidos" name="apellidos" required value="<?php echo htmlspecialchars($user['apellidos']); ?>">
                        </div>
                    </div>

                    <div class="profile-form-grid" style="margin-top: 15px;">
                        <div>
                            <label for="email">Correo Electrónico *</label>
                            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                        <div>
                            <label for="telefono">Teléfono</label>
                            <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>" placeholder="Ej: 600123456">
                        </div>
                    </div>

                    <div class="profile-form-grid full-width" style="margin-top: 15px; margin-bottom: 30px;">
                        <div>
                            <label for="avatar_url">URL de la Imagen de Avatar</label>
                            <input type="url" id="avatar_url" name="avatar_url" value="<?php echo htmlspecialchars($user['avatar_url'] ?? ''); ?>" placeholder="Ej: https://enlace-de-imagen.jpg/avatar.png">
                        </div>
                    </div>

                    <h3 class="profile-section-title"><i class="fa-solid fa-lock"></i> Seguridad y Acceso</h3>
                    
                    <div class="profile-form-grid full-width">
                        <div>
                            <label for="password_actual">Contraseña Actual (Requerida solo si vas a cambiarla)</label>
                            <input type="password" id="password_actual" name="password_actual" placeholder="Escribe tu contraseña actual" autocomplete="new-password">
                        </div>
                    </div>

                    <div class="profile-form-grid" style="margin-top: 15px; margin-bottom: 25px;">
                        <div>
                            <label for="password_nueva">Nueva Contraseña</label>
                            <input type="password" id="password_nueva" name="password_nueva" placeholder="Mínimo 6 caracteres">
                        </div>
                        <div>
                            <label for="password_confirm">Confirmar Nueva Contraseña</label>
                            <input type="password" id="password_confirm" name="password_confirm" placeholder="Repite la nueva contraseña">
                        </div>
                    </div>

                    <button type="submit">Guardar Cambios</button>
                </form>
            </section>

        </div>

        <!-- Sección de Historial de Reproducción -->
        <section class="watch-history-section" style="margin-top: 40px;">
            <div class="watch-history-header">
                <h3 class="profile-section-title watch-history-title"><i class="fa-solid fa-clock-rotate-left"></i> Mi Historial de Reproducción</h3>
                <?php if (!empty($history)): ?>
                    <button id="btnClearHistory" class="btn btn-secondary btn-clear-history">
                        <i class="fa-solid fa-trash-can"></i> Limpiar Historial
                    </button>
                <?php endif; ?>
            </div>

            <?php if (empty($history)): ?>
                <div class="history-empty-container">
                    <i class="fa-solid fa-video-slash history-empty-icon"></i>
                    <p>No tienes trailers en tu historial de reproducción.</p>
                </div>
            <?php else: ?>
                <div class="history-list">
                    <?php foreach ($history as $item): ?>
                        <div class="history-item" id="history-item-<?= $item['id_visualizacion'] ?>">
                            <div class="history-item-left">
                                <img src="<?= htmlspecialchars($item['poster_url'] ?? 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=100') ?>" alt="<?= htmlspecialchars($item['titulo']) ?>" class="history-item-poster">
                                <div class="history-item-details">
                                    <h4 class="history-item-title">
                                        <a href="../trailers/reproducir_trailer.php?id=<?= $item['id_trailer'] ?>">
                                            <?= htmlspecialchars($item['titulo']) ?>
                                        </a>
                                    </h4>
                                    <div class="history-item-meta">
                                        <span><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($item['fecha_visualizacion'])) ?></span>
                                    </div>
                                </div>
                            </div>
                            <button class="btn-delete-history" data-id="<?= $item['id_visualizacion'] ?>" title="Eliminar de mi historial">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Pestaña: Mis Películas (Listas Personales y Notas Privadas) -->
    <div id="tab-movies" class="tab-content">
        <!-- Selector para añadir películas -->
        <div class="write-review-card" style="background: var(--bg-surface-elevated); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 30px;">
            <h3 class="profile-section-title" style="margin-bottom: 12px;"><i class="fa-solid fa-square-plus" style="color: var(--primary);"></i> Añadir Película a mis Listas</h3>
            <form id="addMovieForm" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; margin: 0;">
                <input type="hidden" name="csrf_token" id="moviesCsrfToken" value="<?= $_SESSION['csrf_token'] ?>">
                <div style="flex: 1; min-width: 200px;">
                    <label for="addMovieSelect" style="display: block; margin-bottom: 6px; font-size: 13px; color: var(--text-muted);">Selecciona una película del catálogo:</label>
                    <select id="addMovieSelect" required style="width: 100%; padding: 10px; background: var(--bg-base); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-family: var(--font-body);">
                        <option value="">-- Elige una película --</option>
                        <?php foreach ($allTrailersOption as $tOpt): ?>
                            <option value="<?= $tOpt['id_trailer'] ?>"><?= htmlspecialchars($tOpt['titulo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="min-width: 150px;">
                    <label for="addMovieStatus" style="display: block; margin-bottom: 6px; font-size: 13px; color: var(--text-muted);">Estado:</label>
                    <select id="addMovieStatus" style="width: 100%; padding: 10px; background: var(--bg-base); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-family: var(--font-body);">
                        <option value="por_ver">Por Ver (Watchlist)</option>
                        <option value="vista">Vista (Watched)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 10px 20px; border-radius: var(--radius-sm); height: 40px; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; cursor: pointer; border: none; background: var(--primary); color: #000;">
                    <i class="fa-solid fa-plus"></i> Añadir
                </button>
            </form>
        </div>

        <!-- Listas separadas en pestañas secundarias -->
        <div class="movie-list-tabs" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
            <button class="btn btn-secondary subtab-btn active" data-subtab="por-ver" style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); padding: 8px 16px; border-radius: var(--radius-sm); color: var(--text-primary); cursor: pointer;">
                <i class="fa-regular fa-clock"></i> Por Ver (Watchlist)
            </button>
            <button class="btn btn-secondary subtab-btn" data-subtab="vistas" style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); padding: 8px 16px; border-radius: var(--radius-sm); color: var(--text-primary); cursor: pointer;">
                <i class="fa-solid fa-circle-check"></i> Vistas (Watched)
            </button>
        </div>

        <!-- Contenido Subtab: Por Ver -->
        <div id="subtab-por-ver" class="subtab-content active-subtab">
            <div class="movies-list-container" style="display: grid; gap: 15px;">
                <?php 
                $porVerList = array_filter($myList, fn($item) => $item['estado'] === 'por_ver');
                if (empty($porVerList)): 
                ?>
                    <div class="login-prompt-card" style="padding: 30px; text-align: center; border: 1px dashed var(--border-color); border-radius: var(--radius-md); background: rgba(255, 255, 255, 0.01);">
                        <p style="color: var(--text-muted); margin: 0;"><i class="fa-solid fa-info-circle" style="color: var(--primary); margin-right: 6px;"></i> No tienes películas en tu lista de "Por Ver".</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($porVerList as $item): ?>
                        <div class="movie-row-card" style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); padding: 15px; border-radius: var(--radius-md); display: flex; gap: 20px; flex-wrap: wrap;" id="movie-item-<?= $item['id_trailer'] ?>">
                            <img src="<?= htmlspecialchars($item['poster_url'] ?? 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=200') ?>" style="width: 80px; height: 120px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                            <div style="flex: 1; min-width: 250px;">
                                <h4 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 700;">
                                    <a href="../trailers/reproducir_trailer.php?id=<?= $item['id_trailer'] ?>" style="color: var(--text-primary); text-decoration: none;">
                                        <?= htmlspecialchars($item['titulo']) ?>
                                    </a>
                                </h4>
                                <span style="font-size: 11px; color: var(--text-muted); display: block; margin-bottom: 12px;">Añadida el: <?= date('d/m/Y H:i', strtotime($item['fecha_adicion'])) ?></span>
                                
                                <!-- Comentario privado form -->
                                <div style="margin-bottom: 10px;">
                                    <textarea class="private-comment-textarea" placeholder="Escribe un comentario privado sobre esta película..." style="width: 100%; height: 50px; padding: 8px; background: var(--bg-base); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 12px; resize: vertical;"><?= htmlspecialchars($item['comentario'] ?? '') ?></textarea>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                    <div style="display: flex; gap: 8px;">
                                        <button class="btn btn-primary btn-save-comment" data-id="<?= $item['id_trailer'] ?>" style="padding: 6px 12px; font-size: 11px; font-weight: 600; cursor: pointer; border-radius: var(--radius-sm); background: var(--primary); border: none; color: #000;">
                                            <i class="fa-solid fa-save"></i> Guardar Nota
                                        </button>
                                        <button class="btn btn-secondary btn-view-history" data-id="<?= $item['id_trailer'] ?>" style="padding: 6px 12px; font-size: 11px; font-weight: 600; cursor: pointer; border-radius: var(--radius-sm); background: var(--bg-base); border: 1px solid var(--border-color); color: var(--text-primary);">
                                            <i class="fa-solid fa-history"></i> Historial
                                        </button>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <button class="btn btn-secondary btn-change-status" data-id="<?= $item['id_trailer'] ?>" data-status="vista" style="padding: 6px 12px; font-size: 11px; font-weight: 600; cursor: pointer; border-radius: var(--radius-sm); background: var(--bg-base); border: 1px solid var(--border-color); color: var(--text-primary);">
                                            <i class="fa-solid fa-check"></i> Marcar Vista
                                        </button>
                                        <button class="btn btn-secondary btn-remove-list" data-id="<?= $item['id_trailer'] ?>" style="padding: 6px 12px; font-size: 11px; font-weight: 600; cursor: pointer; border-radius: var(--radius-sm); background: rgba(220, 38, 38, 0.1); border: 1px solid rgba(220, 38, 38, 0.3); color: #ef4444;">
                                            <i class="fa-solid fa-trash"></i> Quitar
                                        </button>
                                    </div>
                                </div>
                                <!-- Historial colapsable -->
                                <div class="comment-history-container" id="history-container-<?= $item['id_trailer'] ?>" style="display: none; margin-top: 15px; border-top: 1px solid rgba(216, 195, 173, 0.1); padding-top: 12px;">
                                    <h5 style="margin: 0 0 8px 0; font-size: 12px; color: var(--text-muted);"><i class="fa-solid fa-history"></i> Versiones anteriores:</h5>
                                    <div class="history-list-box" style="display: flex; flex-direction: column; gap: 8px;"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contenido Subtab: Vistas -->
        <div id="subtab-vistas" class="subtab-content">
            <div class="movies-list-container" style="display: grid; gap: 15px;">
                <?php 
                $vistasList = array_filter($myList, fn($item) => $item['estado'] === 'vista');
                if (empty($vistasList)): 
                ?>
                    <div class="login-prompt-card" style="padding: 30px; text-align: center; border: 1px dashed var(--border-color); border-radius: var(--radius-md); background: rgba(255, 255, 255, 0.01);">
                        <p style="color: var(--text-muted); margin: 0;"><i class="fa-solid fa-info-circle" style="color: var(--primary); margin-right: 6px;"></i> No tienes películas en tu lista de "Vistas".</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($vistasList as $item): ?>
                        <div class="movie-row-card" style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); padding: 15px; border-radius: var(--radius-md); display: flex; gap: 20px; flex-wrap: wrap;" id="movie-item-<?= $item['id_trailer'] ?>">
                            <img src="<?= htmlspecialchars($item['poster_url'] ?? 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=200') ?>" style="width: 80px; height: 120px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                            <div style="flex: 1; min-width: 250px;">
                                <h4 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 700;">
                                    <a href="../trailers/reproducir_trailer.php?id=<?= $item['id_trailer'] ?>" style="color: var(--text-primary); text-decoration: none;">
                                        <?= htmlspecialchars($item['titulo']) ?>
                                    </a>
                                </h4>
                                <span style="font-size: 11px; color: var(--text-muted); display: block; margin-bottom: 12px;">Añadida el: <?= date('d/m/Y H:i', strtotime($item['fecha_adicion'])) ?></span>
                                
                                <!-- Comentario privado form -->
                                <div style="margin-bottom: 10px;">
                                    <textarea class="private-comment-textarea" placeholder="Escribe un comentario privado sobre esta película..." style="width: 100%; height: 50px; padding: 8px; background: var(--bg-base); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 12px; resize: vertical;"><?= htmlspecialchars($item['comentario'] ?? '') ?></textarea>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                    <div style="display: flex; gap: 8px;">
                                        <button class="btn btn-primary btn-save-comment" data-id="<?= $item['id_trailer'] ?>" style="padding: 6px 12px; font-size: 11px; font-weight: 600; cursor: pointer; border-radius: var(--radius-sm); background: var(--primary); border: none; color: #000;">
                                            <i class="fa-solid fa-save"></i> Guardar Nota
                                        </button>
                                        <button class="btn btn-secondary btn-view-history" data-id="<?= $item['id_trailer'] ?>" style="padding: 6px 12px; font-size: 11px; font-weight: 600; cursor: pointer; border-radius: var(--radius-sm); background: var(--bg-base); border: 1px solid var(--border-color); color: var(--text-primary);">
                                            <i class="fa-solid fa-history"></i> Historial
                                        </button>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <button class="btn btn-secondary btn-change-status" data-id="<?= $item['id_trailer'] ?>" data-status="por_ver" style="padding: 6px 12px; font-size: 11px; font-weight: 600; cursor: pointer; border-radius: var(--radius-sm); background: var(--bg-base); border: 1px solid var(--border-color); color: var(--text-primary);">
                                            <i class="fa-solid fa-history"></i> Marcar Por Ver
                                        </button>
                                        <button class="btn btn-secondary btn-remove-list" data-id="<?= $item['id_trailer'] ?>" style="padding: 6px 12px; font-size: 11px; font-weight: 600; cursor: pointer; border-radius: var(--radius-sm); background: rgba(220, 38, 38, 0.1); border: 1px solid rgba(220, 38, 38, 0.3); color: #ef4444;">
                                            <i class="fa-solid fa-trash"></i> Quitar
                                        </button>
                                    </div>
                                </div>
                                <!-- Historial colapsable -->
                                <div class="comment-history-container" id="history-container-<?= $item['id_trailer'] ?>" style="display: none; margin-top: 15px; border-top: 1px solid rgba(216, 195, 173, 0.1); padding-top: 12px;">
                                    <h5 style="margin: 0 0 8px 0; font-size: 12px; color: var(--text-muted);"><i class="fa-solid fa-history"></i> Versiones anteriores:</h5>
                                    <div class="history-list-box" style="display: flex; flex-direction: column; gap: 8px;"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pestaña 3: Mis Logros (Badges de Gamificación) -->
    <div id="tab-badges" class="tab-content">
        <div class="badges-info-header" style="margin-bottom: 25px; text-align: center;">
            <h3 class="profile-section-title" style="justify-content: center; margin-bottom: 8px;"><i class="fa-solid fa-medal"></i> Vitrina de Insignias</h3>
            <p style="color: var(--text-muted); margin: 0;">Desbloquea logros interactuando con la web, viendo trailers y aportando reseñas.</p>
        </div>
        <div class="badges-grid" id="badgesContainer">
            <!-- Renderizado dinámico de badges con JavaScript -->
        </div>
    </div>

    <!-- Pestaña 2: Estadísticas Cinemáticas (Chart.js) -->
    <div id="tab-stats" class="tab-content">
        <!-- Tarjetas de Resumen (Key Stats) -->
        <div class="user-stats-cards-grid">
            <div class="user-stats-card">
                <div class="user-stats-card-icon"><i class="fa-solid fa-eye"></i></div>
                <div class="user-stats-card-info">
                    <span class="user-stats-card-value"><?= $totalVistas ?></span>
                    <span class="user-stats-card-label">Trailers Vistos</span>
                </div>
            </div>
            <div class="user-stats-card">
                <div class="user-stats-card-icon"><i class="fa-solid fa-clock"></i></div>
                <div class="user-stats-card-info">
                    <span class="user-stats-card-value"><?= $tiempoTrailer ?></span>
                    <span class="user-stats-card-label">Tiempo Viendo Trailers</span>
                </div>
            </div>
            <div class="user-stats-card">
                <div class="user-stats-card-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="user-stats-card-info">
                    <span class="user-stats-card-value"><?= $tiempoReproduccion ?></span>
                    <span class="user-stats-card-label">Metraje Cine Descubierto</span>
                </div>
            </div>
            <div class="user-stats-card">
                <div class="user-stats-card-icon"><i class="fa-solid fa-film"></i></div>
                <div class="user-stats-card-info">
                    <span class="user-stats-card-value" style="font-size: 16px; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;" title="<?= htmlspecialchars($generoFavorito) ?>"><?= htmlspecialchars($generoFavorito) ?></span>
                    <span class="user-stats-card-label">Género Favorito</span>
                </div>
            </div>
        </div>

        <?php if ($totalVistas === 0): ?>
            <div class="history-empty-container" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 50px 20px; text-align: center;">
                <i class="fa-solid fa-chart-line history-empty-icon" style="font-size: 48px; color: var(--text-muted); opacity: 0.3; margin-bottom: 15px;"></i>
                <p style="color: var(--text-muted); margin: 0; font-size: 15px;">No hay suficientes datos de visualizaciones para calcular tus estadísticas de consumo.</p>
                <p style="color: var(--text-muted); margin-top: 5px; font-size: 13px;">¡Comienza a ver trailers en el catálogo para generar tu infografía personal!</p>
            </div>
        <?php else: ?>
            <!-- Cuadrícula de Gráficos -->
            <div class="user-stats-charts-grid">
                <!-- Gráfico de Rosca: Distribución de Géneros -->
                <div class="user-chart-card">
                    <h4 class="user-chart-card-title"><i class="fa-solid fa-chart-pie"></i> Mis Géneros Preferidos</h4>
                    <div class="user-chart-container-wrapper">
                        <canvas id="genresChart"></canvas>
                    </div>
                </div>

                <!-- Gráfico de Líneas: Actividad por Hora del Día -->
                <div class="user-chart-card">
                    <h4 class="user-chart-card-title"><i class="fa-solid fa-clock"></i> Momentos de Cine (Por Hora)</h4>
                    <div class="user-chart-container-wrapper">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Panel de Datos Clave e Insights -->
            <div class="user-stats-insights-card">
                <h3 class="profile-section-title" style="margin-bottom: 20px;"><i class="fa-solid fa-lightbulb" style="color: var(--primary);"></i> Cinephile Insights</h3>
                
                <div class="user-insight-item">
                    <div class="user-insight-icon"><i class="fa-solid fa-user-tie"></i></div>
                    <div class="user-insight-content">
                        <h4>Director más reproducido</h4>
                        <p>Tu director favorito de cine es <strong><?= htmlspecialchars($directorFavorito) ?></strong>.</p>
                    </div>
                </div>

                <div class="user-insight-item">
                    <div class="user-insight-icon"><i class="fa-solid fa-mug-hot"></i></div>
                    <div class="user-insight-content">
                        <h4>Patrón de Visualización</h4>
                        <?php
                        // Analizar el pico de actividad horaria
                        $maxHourVal = -1;
                        $peakHour = 0;
                        foreach ($hourlyData as $hour => $count) {
                            if ($count > $maxHourVal) {
                                $maxHourVal = $count;
                                $peakHour = $hour;
                            }
                        }
                        
                        $patternLabel = "";
                        if ($peakHour >= 6 && $peakHour < 12) {
                            $patternLabel = "Matutino. Te encanta empezar el día enterándote de los próximos estrenos.";
                        } elseif ($peakHour >= 12 && $peakHour < 17) {
                            $patternLabel = "De Sobremesa. Disfrutas de tus trailers preferidos en el descanso de la tarde.";
                        } elseif ($peakHour >= 17 && $peakHour < 21) {
                            $patternLabel = "Vespertino. Eres de los que desconecta del trabajo o estudios explorando el cine.";
                        } else {
                            $patternLabel = "Nocturno. Eres un verdadero búho cinéfilo que busca qué ver a altas horas de la noche.";
                        }
                        ?>
                        <h4>Tu momento favorito del día</h4>
                        <p>Tu pico de visualizaciones ocurre a las <strong><?= sprintf('%02d:00', $peakHour) ?> h</strong>, lo que define tu patrón de consumo como <strong><?= $patternLabel ?></strong></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div style="margin-top: 30px;">
        <a class="volver" href="../index.php">← Volver al inicio</a>
    </div>

</main>

<script>
function closeToast(id) {
    const toast = document.getElementById(id);
    if (toast) {
        toast.classList.remove('show');
        toast.classList.add('hide');
        setTimeout(() => {
            toast.remove();
        }, 400);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // === Lógica de Pestañas (Tabs) ===
    const tabButtons = document.querySelectorAll('.profile-tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    let chartsInitialized = false;

    function initCharts() {
        if (typeof Chart === 'undefined') {
            console.error("Error: La biblioteca Chart.js no se ha cargado correctamente en el cliente.");
            return;
        }
        if (chartsInitialized) return;

        // 1. Gráfico de Rosca de Géneros
        const genresCtx = document.getElementById('genresChart');
        if (genresCtx) {
            <?php
            $genreLabels = array_column($genresData, 'genero');
            $genreCounts = array_column($genresData, 'cantidad');
            ?>
            new Chart(genresCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($genreLabels) ?>,
                    datasets: [{
                        label: 'Visualizaciones',
                        data: <?= json_encode($genreCounts) ?>,
                        backgroundColor: [
                            '#f59e0b', // Amber (Primary)
                            '#dc2626', // Crimson (Secondary)
                            '#3b82f6', // Azul
                            '#10b981', // Esmeralda
                            '#8b5cf6'  // Violeta
                        ],
                        borderWidth: 1,
                        borderColor: '#152031' // Combina con --card-bg
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#d8e3fb', // --text-primary
                                font: {
                                    family: 'Montserrat'
                                }
                            }
                        }
                    }
                }
            });
        }

        // 2. Gráfico de Actividad por Horas (Línea suavizada con relleno)
        const hourlyCtx = document.getElementById('hourlyChart');
        if (hourlyCtx) {
            const hours = Array.from({length: 24}, (_, i) => `${String(i).padStart(2, '0')}:00`);
            new Chart(hourlyCtx, {
                type: 'line',
                data: {
                    labels: hours,
                    datasets: [{
                        label: 'Visualizaciones',
                        data: <?= json_encode($hourlyData) ?>,
                        borderColor: '#f59e0b', // Amber
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointBackgroundColor: '#f59e0b',
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(216, 195, 173, 0.05)'
                            },
                            ticks: {
                                color: '#a08e7a' // --text-muted
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(216, 195, 173, 0.05)'
                            },
                            ticks: {
                                color: '#a08e7a',
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        chartsInitialized = true;
    }

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.getAttribute('data-tab');

            // Actualizar botones
            tabButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Actualizar contenidos
            tabContents.forEach(content => {
                if (content.id === `tab-${targetTab}`) {
                    content.classList.add('active');
                } else {
                    content.classList.remove('active');
                }
            });

            // Si se activa la pestaña de estadísticas, inicializar gráficos tras un brevísimo retardo
            if (targetTab === 'stats') {
                setTimeout(initCharts, 50);
            }

            // Si se activa la pestaña de logros, cargar insignias
            if (targetTab === 'badges') {
                cargarBadges();
            }
        });
    });

    // Inicializar toasts
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach((toast) => {
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        setTimeout(() => {
            closeToast(toast.id);
        }, 4000);
    });

    const avatarInput = document.getElementById('avatar_url');
    const avatarImg = document.getElementById('avatarImg');
    const avatarIcon = document.getElementById('avatarIcon');

    // Escuchar cambios en la URL del avatar para previsualización inmediata
    avatarInput.addEventListener('input', () => {
        const url = avatarInput.value.trim();
        if (url !== "") {
            avatarImg.src = url;
            avatarImg.style.display = 'block';
            if (avatarIcon) avatarIcon.style.display = 'none';
        } else {
            avatarImg.src = "";
            avatarImg.style.display = 'none';
            if (avatarIcon) avatarIcon.style.display = 'block';
        }
    });

    // Validar que si intentan cambiar contraseña completen todos los campos
    const form = document.querySelector('form');
    const passActual = document.getElementById('password_actual');
    const passNueva = document.getElementById('password_nueva');
    const passConfirm = document.getElementById('password_confirm');

    form.addEventListener('submit', (e) => {
        const valActual = passActual.value.trim();
        const valNueva = passNueva.value.trim();
        const valConfirm = passConfirm.value.trim();

        if (valActual !== "" || valNueva !== "" || valConfirm !== "") {
            if (valActual === "" || valNueva === "" || valConfirm === "") {
                e.preventDefault();
                alert("Por favor completa los tres campos de contraseña (actual, nueva y confirmación).");
                return;
            }
            if (valNueva !== valConfirm) {
                e.preventDefault();
                alert("La nueva contraseña y la confirmación no coinciden.");
                return;
            }
            if (valNueva.length < 6) {
                e.preventDefault();
                alert("La nueva contraseña debe tener al menos 6 caracteres.");
                return;
            }
        }
    });

    // Manejar eliminación individual de historial
    document.querySelectorAll('.btn-delete-history').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (confirm('¿Estás seguro de que quieres eliminar esta visualización de tu historial?')) {
                fetch('api_historial.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete_entry',
                        id_visualizacion: id
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const row = document.getElementById(`history-item-${id}`);
                        if (row) {
                            row.style.opacity = '0';
                            row.style.transform = 'translateX(20px)';
                            setTimeout(() => {
                                row.remove();
                                const remaining = document.querySelectorAll('.history-item');
                                if (remaining.length === 0) {
                                    window.location.reload();
                                }
                            }, 300);
                        }
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Error al intentar eliminar el registro.');
                });
            }
        });
    });

    // Manejar limpieza completa del historial
    const btnClearHistory = document.getElementById('btnClearHistory');
    if (btnClearHistory) {
        btnClearHistory.addEventListener('click', function() {
            if (confirm('¿Estás completamente seguro de que deseas vaciar todo tu historial de reproducción? Esta acción no se puede deshacer.')) {
                fetch('api_historial.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'clear_history'
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Error al intentar limpiar el historial.');
                });
            }
        });
    }

    // === Lógica de sub-pestañas de películas ===
    const subtabButtons = document.querySelectorAll('.subtab-btn');
    const subtabContents = document.querySelectorAll('.subtab-content');
    subtabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-subtab');
            subtabButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            subtabContents.forEach(c => {
                if (c.id === `subtab-${target}`) {
                    c.style.display = 'grid';
                } else {
                    c.style.display = 'none';
                }
            });
        });
    });

    const addMovieForm = document.getElementById('addMovieForm');
    if (addMovieForm) {
        addMovieForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const id_trailer = document.getElementById('addMovieSelect').value;
            const estado = document.getElementById('addMovieStatus').value;
            const csrfToken = document.getElementById('moviesCsrfToken').value;

            fetch('api_listas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    action: 'add_to_list',
                    id_trailer: id_trailer,
                    estado: estado
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error al agregar la película.');
            });
        });
    }

    document.querySelectorAll('.btn-save-comment').forEach(btn => {
        btn.addEventListener('click', function() {
            const id_trailer = this.getAttribute('data-id');
            const card = document.getElementById(`movie-item-${id_trailer}`);
            const commentText = card.querySelector('.private-comment-textarea').value;
            const csrfToken = document.getElementById('moviesCsrfToken').value;

            fetch('api_listas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    action: 'save_comment',
                    id_trailer: id_trailer,
                    comentario: commentText
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('¡Comentario privado guardado!');
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error al guardar comentario.');
            });
        });
    });

    document.querySelectorAll('.btn-change-status').forEach(btn => {
        btn.addEventListener('click', function() {
            const id_trailer = this.getAttribute('data-id');
            const nextStatus = this.getAttribute('data-status');
            const csrfToken = document.getElementById('moviesCsrfToken').value;

            fetch('api_listas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    action: 'update_status',
                    id_trailer: id_trailer,
                    estado: nextStatus
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error al cambiar de lista.');
            });
        });
    });

    document.querySelectorAll('.btn-remove-list').forEach(btn => {
        btn.addEventListener('click', function() {
            const id_trailer = this.getAttribute('data-id');
            const csrfToken = document.getElementById('moviesCsrfToken').value;

            if (confirm('¿Estás seguro de que quieres quitar esta película de tus listas?')) {
                fetch('api_listas.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        action: 'remove_from_list',
                        id_trailer: id_trailer
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Error al eliminar de la lista.');
                });
            }
        });
    });

    document.querySelectorAll('.btn-view-history').forEach(btn => {
        btn.addEventListener('click', function() {
            const id_trailer = this.getAttribute('data-id');
            const historyContainer = document.getElementById(`history-container-${id_trailer}`);
            const historyList = historyContainer.querySelector('.history-list-box');
            const csrfToken = document.getElementById('moviesCsrfToken').value;

            if (historyContainer.style.display === 'none') {
                fetch('api_listas.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        action: 'get_history',
                        id_trailer: id_trailer
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        historyList.innerHTML = '';
                        if (data.history.length === 0) {
                            historyList.innerHTML = '<p style="color: var(--text-muted); font-size: 11px; margin: 0;">No hay cambios registrados en tu nota.</p>';
                        } else {
                            data.history.forEach(h => {
                                const entry = document.createElement('div');
                                entry.style.background = 'rgba(255, 255, 255, 0.02)';
                                entry.style.padding = '8px';
                                entry.style.borderLeft = '2px solid var(--primary)';
                                entry.style.borderRadius = 'var(--radius-sm)';
                                entry.style.fontSize = '11px';
                                entry.innerHTML = `
                                    <div style="color: var(--text-muted); font-size: 10px; margin-bottom: 4px;">Edición: ${h.fecha_cambio}</div>
                                    <div style="color: var(--text-primary); white-space: pre-wrap;">${escapeHtml(h.comentario_anterior)}</div>
                                `;
                                historyList.appendChild(entry);
                            });
                        }
                        historyContainer.style.display = 'block';
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Error al cargar historial.');
                });
            } else {
                historyContainer.style.display = 'none';
            }
        });
    });

    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // --- Lógica del Sistema de Gamificación (Badges) ---
    const badgesContainer = document.getElementById('badgesContainer');
    let badgesLoaded = false;

    function cargarBadges() {
        if (badgesLoaded) return;
        
        badgesContainer.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 10px;"></i>
                <p>Cargando tus logros...</p>
            </div>
        `;
        
        fetch('../badges/api_badges.php')
            .then(response => {
                if (!response.ok) throw new Error('Error al cargar badges');
                return response.json();
            })
            .then(data => {
                badgesContainer.innerHTML = '';
                if (data.length === 0) {
                    badgesContainer.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: var(--text-muted);">No hay badges configurados en el sistema.</p>';
                    return;
                }
                
                data.forEach(badge => {
                    const card = document.createElement('div');
                    card.className = `badge-card ${badge.desbloqueado ? 'unlocked' : 'locked'}`;
                    
                    const progressPercentage = Math.min(100, Math.max(0, (badge.progreso_actual / badge.requisito_valor) * 100));
                    
                    card.innerHTML = `
                        <div class="badge-icon-circle">
                            <i class="fa-solid ${badge.icono}"></i>
                        </div>
                        <span class="badge-title-label">${badge.nombre}</span>
                        
                        <div class="badge-tooltip">
                            <div class="tooltip-title">${badge.nombre}</div>
                            <div class="tooltip-status ${badge.desbloqueado ? 'status-unlocked' : 'status-locked'}">
                                ${badge.desbloqueado ? '<i class="fa-solid fa-circle-check"></i> Conseguido' : '<i class="fa-solid fa-lock"></i> Bloqueado'}
                            </div>
                            <div class="tooltip-desc">${badge.descripcion}</div>
                            <div class="tooltip-progress-section">
                                <div class="tooltip-progress-label">
                                    <span>Progreso</span>
                                    <span>${badge.progreso_actual} / ${badge.requisito_valor}</span>
                                </div>
                                <div class="tooltip-progress-bar">
                                    <div class="tooltip-progress-fill" style="width: ${progressPercentage}%"></div>
                                </div>
                            </div>
                            ${badge.desbloqueado ? `<div class="tooltip-date">Desbloqueado el: ${new Date(badge.fecha_desbloqueo).toLocaleDateString()}</div>` : ''}
                        </div>
                    `;
                    badgesContainer.appendChild(card);
                });
                badgesLoaded = true;
            })
            .catch(err => {
                console.error(err);
                badgesContainer.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: var(--secondary);">Error al conectar con la API de Gamificación.</p>';
            });
    }
});
</script>

<?php
require_once $rootPath . 'includes/footer.php';
mysqli_close($conexion);
?>
