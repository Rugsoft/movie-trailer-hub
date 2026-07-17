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
$sqlMyList = "SELECT lp.*, t.titulo, t.poster_url, t.release_date, cp.comentario,
                     GROUP_CONCAT(g.nombre SEPARATOR ', ') as genero
              FROM listas_personales lp
              JOIN trailers t ON lp.id_trailer = t.id_trailer
              LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
              LEFT JOIN generos g ON tg.id_genero = g.id_genero
              LEFT JOIN comentarios_privados cp ON lp.id_trailer = cp.id_trailer AND cp.id_usuario = lp.id_usuario
              WHERE lp.id_usuario = ?
              GROUP BY lp.id_lista
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

// Consultar las películas favoritas del usuario
$myFavorites = [];
$sqlMyFavorites = "SELECT f.*, t.titulo, t.poster_url, t.release_date, lp.estado, cp.comentario,
                          GROUP_CONCAT(g.nombre SEPARATOR ', ') as genero
                   FROM favoritos f
                   JOIN trailers t ON f.id_trailer = t.id_trailer
                   LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
                   LEFT JOIN generos g ON tg.id_genero = g.id_genero
                   LEFT JOIN listas_personales lp ON f.id_trailer = lp.id_trailer AND lp.id_usuario = f.id_usuario
                   LEFT JOIN comentarios_privados cp ON f.id_trailer = cp.id_trailer AND cp.id_usuario = f.id_usuario
                   WHERE f.id_usuario = ?
                   GROUP BY f.id_trailer
                   ORDER BY f.fecha_adicion DESC";
$stmtMyFavorites = mysqli_prepare($conexion, $sqlMyFavorites);
if ($stmtMyFavorites) {
    mysqli_stmt_bind_param($stmtMyFavorites, "i", $user_id);
    mysqli_stmt_execute($stmtMyFavorites);
    $resMyFavorites = mysqli_stmt_get_result($stmtMyFavorites);
    while ($row = mysqli_fetch_assoc($resMyFavorites)) {
        $myFavorites[] = $row;
    }
    mysqli_stmt_close($stmtMyFavorites);
}

// Consultar todos los comentarios privados del usuario
$myComments = [];
$sqlMyComments = "SELECT cp.*, t.titulo, t.poster_url, t.release_date, lp.estado,
                         GROUP_CONCAT(g.nombre SEPARATOR ', ') as genero
                  FROM comentarios_privados cp
                  JOIN trailers t ON cp.id_trailer = t.id_trailer
                  LEFT JOIN trailers_generos tg ON t.id_trailer = tg.id_trailer
                  LEFT JOIN generos g ON tg.id_genero = g.id_genero
                  LEFT JOIN listas_personales lp ON cp.id_trailer = lp.id_trailer AND lp.id_usuario = cp.id_usuario
                  WHERE cp.id_usuario = ?
                  GROUP BY cp.id_comentario_privado
                  ORDER BY cp.id_comentario_privado DESC";
$stmtMyComments = mysqli_prepare($conexion, $sqlMyComments);
if ($stmtMyComments) {
    mysqli_stmt_bind_param($stmtMyComments, "i", $user_id);
    mysqli_stmt_execute($stmtMyComments);
    $resMyComments = mysqli_stmt_get_result($stmtMyComments);
    while ($row = mysqli_fetch_assoc($resMyComments)) {
        $myComments[] = $row;
    }
    mysqli_stmt_close($stmtMyComments);
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

    <!-- Pestaña: Mis Películas (Mi Gestión) -->
    <div id="tab-movies" class="tab-content">

        <!-- Estructura de Dos Columnas: Sidebar + Contenidos de Gestión -->
        <div class="management-layout">
            <!-- Sidebar Izquierdo -->
            <aside class="management-sidebar">
                <div class="sidebar-header">
                    <h4>MI GESTIÓN</h4>
                    <span>Listas y Notas</span>
                </div>
                <nav class="sidebar-nav">
                    <button type="button" class="sidebar-tab-btn active" data-subtab="biblioteca">
                        <i class="fa-solid fa-folder-open"></i> MI BIBLIOTECA
                    </button>
                    <button type="button" class="sidebar-tab-btn" data-subtab="por-ver">
                        <i class="fa-solid fa-clock"></i> POR VER
                    </button>
                    <button type="button" class="sidebar-tab-btn" data-subtab="vistas">
                        <i class="fa-solid fa-eye"></i> VISTAS
                    </button>
                    <button type="button" class="sidebar-tab-btn" data-subtab="favoritos">
                        <i class="fa-solid fa-heart"></i> FAVORITOS
                    </button>
                    <button type="button" class="sidebar-tab-btn" data-subtab="notas">
                        <i class="fa-solid fa-note-sticky"></i> NOTAS PRIVADAS
                    </button>
                </nav>
            </aside>

            <!-- Área de Contenido Derecha -->
            <div class="management-content">
                
                <!-- Subpestaña 1: Mi Biblioteca -->
                <div id="subtab-content-biblioteca" class="management-subtab-content active">
                    <h2>Mi Biblioteca</h2>
                    <p class="subtab-desc">Gestiona tus películas favoritas, marca las que has visto y organiza tus maratones.</p>
                    
                    <div class="management-grid">
                        <?php if (empty($myList)): ?>
                            <div class="login-prompt-card" style="grid-column: 1 / -1; padding: 30px; text-align: center; border: 1px dashed var(--border-color); border-radius: var(--radius-md); background: rgba(255, 255, 255, 0.01); width: 100%;">
                                <p style="color: var(--text-muted); margin: 0;"><i class="fa-solid fa-info-circle" style="color: var(--primary); margin-right: 6px;"></i> Tu biblioteca está vacía. Selecciona una película arriba para añadirla.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($myList as $movie): ?>
                                <div class="management-card" id="movie-card-<?= $movie['id_trailer'] ?>">
                                    <div class="card-media-container">
                                        <img src="<?= htmlspecialchars($movie['poster_url'] ?? 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=200') ?>" alt="<?= htmlspecialchars($movie['titulo']) ?>">
                                        
                                        <!-- Badge de estado -->
                                        <span class="card-status-badge <?= $movie['estado'] ?>">
                                            <?= $movie['estado'] === 'por_ver' ? 'Por Ver' : 'Vista' ?>
                                        </span>

                                        <!-- Barra de herramientas flotante -->
                                        <div class="card-media-overlay-bar">
                                            <a href="../trailers/reproducir_trailer.php?id=<?= $movie['id_trailer'] ?>" class="overlay-bar-btn" title="Reproducir"><i class="fa-solid fa-play"></i></a>
                                            <div class="overlay-bar-right">
                                                <button type="button" class="overlay-bar-btn btn-open-note-modal" data-id="<?= $movie['id_trailer'] ?>" data-title="<?= htmlspecialchars($movie['titulo']) ?>" data-comment="<?= htmlspecialchars($movie['comentario'] ?? '') ?>" title="Nota Privada">@</button>
                                                <button type="button" class="overlay-bar-btn btn-remove-list-item" data-id="<?= $movie['id_trailer'] ?>" title="Quitar de la lista"><i class="fa-solid fa-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-movie-info">
                                        <h4 class="card-movie-title" title="<?= htmlspecialchars($movie['titulo']) ?>"><?= htmlspecialchars($movie['titulo']) ?></h4>
                                        <p class="card-movie-meta"><?= date('Y', strtotime($movie['release_date'])) ?> • <?= htmlspecialchars($movie['genero']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Subpestaña 2: Por Ver -->
                <div id="subtab-content-por-ver" class="management-subtab-content">
                    <h2>Por Ver</h2>
                    <p class="subtab-desc">Tu lista de reproducción de películas pendientes por ver.</p>
                    
                    <div class="management-grid">
                        <?php 
                        $porVerList = array_filter($myList, fn($item) => $item['estado'] === 'por_ver');
                        if (empty($porVerList)): 
                        ?>
                            <div class="login-prompt-card" style="grid-column: 1 / -1; padding: 30px; text-align: center; border: 1px dashed var(--border-color); border-radius: var(--radius-md); background: rgba(255, 255, 255, 0.01); width: 100%;">
                                <p style="color: var(--text-muted); margin: 0;"><i class="fa-solid fa-info-circle" style="color: var(--primary); margin-right: 6px;"></i> No tienes películas pendientes en tu lista "Por Ver".</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($porVerList as $movie): ?>
                                <div class="management-card" id="movie-card-por-ver-<?= $movie['id_trailer'] ?>">
                                    <div class="card-media-container">
                                        <img src="<?= htmlspecialchars($movie['poster_url'] ?? 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=200') ?>" alt="<?= htmlspecialchars($movie['titulo']) ?>">
                                        
                                        <span class="card-status-badge por_ver">Por Ver</span>

                                        <div class="card-media-overlay-bar">
                                            <a href="../trailers/reproducir_trailer.php?id=<?= $movie['id_trailer'] ?>" class="overlay-bar-btn" title="Reproducir"><i class="fa-solid fa-play"></i></a>
                                            <div class="overlay-bar-right">
                                                <button type="button" class="overlay-bar-btn btn-open-note-modal" data-id="<?= $movie['id_trailer'] ?>" data-title="<?= htmlspecialchars($movie['titulo']) ?>" data-comment="<?= htmlspecialchars($movie['comentario'] ?? '') ?>" title="Nota Privada">@</button>
                                                <button type="button" class="overlay-bar-btn btn-remove-list-item" data-id="<?= $movie['id_trailer'] ?>" title="Quitar de la lista"><i class="fa-solid fa-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-movie-info">
                                        <h4 class="card-movie-title" title="<?= htmlspecialchars($movie['titulo']) ?>"><?= htmlspecialchars($movie['titulo']) ?></h4>
                                        <p class="card-movie-meta"><?= date('Y', strtotime($movie['release_date'])) ?> • <?= htmlspecialchars($movie['genero']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Subpestaña 3: Vistas -->
                <div id="subtab-content-vistas" class="management-subtab-content">
                    <h2>Vistas</h2>
                    <p class="subtab-desc">Películas que ya has completado y valorado.</p>
                    
                    <div class="management-grid">
                        <?php 
                        $vistasList = array_filter($myList, fn($item) => $item['estado'] === 'vista');
                        if (empty($vistasList)): 
                        ?>
                            <div class="login-prompt-card" style="grid-column: 1 / -1; padding: 30px; text-align: center; border: 1px dashed var(--border-color); border-radius: var(--radius-md); background: rgba(255, 255, 255, 0.01); width: 100%;">
                                <p style="color: var(--text-muted); margin: 0;"><i class="fa-solid fa-info-circle" style="color: var(--primary); margin-right: 6px;"></i> No tienes ninguna película registrada como "Vista".</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($vistasList as $movie): ?>
                                <div class="management-card" id="movie-card-vistas-<?= $movie['id_trailer'] ?>">
                                    <div class="card-media-container">
                                        <img src="<?= htmlspecialchars($movie['poster_url'] ?? 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=200') ?>" alt="<?= htmlspecialchars($movie['titulo']) ?>">
                                        
                                        <span class="card-status-badge vista">Vista</span>

                                        <div class="card-media-overlay-bar">
                                            <a href="../trailers/reproducir_trailer.php?id=<?= $movie['id_trailer'] ?>" class="overlay-bar-btn" title="Reproducir"><i class="fa-solid fa-play"></i></a>
                                            <div class="overlay-bar-right">
                                                <button type="button" class="overlay-bar-btn btn-open-note-modal" data-id="<?= $movie['id_trailer'] ?>" data-title="<?= htmlspecialchars($movie['titulo']) ?>" data-comment="<?= htmlspecialchars($movie['comentario'] ?? '') ?>" title="Nota Privada">@</button>
                                                <button type="button" class="overlay-bar-btn btn-remove-list-item" data-id="<?= $movie['id_trailer'] ?>" title="Quitar de la lista"><i class="fa-solid fa-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-movie-info">
                                        <h4 class="card-movie-title" title="<?= htmlspecialchars($movie['titulo']) ?>"><?= htmlspecialchars($movie['titulo']) ?></h4>
                                        <p class="card-movie-meta"><?= date('Y', strtotime($movie['release_date'])) ?> • <?= htmlspecialchars($movie['genero']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Subpestaña 4: Favoritos -->
                <div id="subtab-content-favoritos" class="management-subtab-content">
                    <h2>Favoritos</h2>
                    <p class="subtab-desc">Tus películas favoritas marcadas en el portal de trailers.</p>
                    
                    <div class="management-grid">
                        <?php if (empty($myFavorites)): ?>
                            <div class="login-prompt-card" style="grid-column: 1 / -1; padding: 30px; text-align: center; border: 1px dashed var(--border-color); border-radius: var(--radius-md); background: rgba(255, 255, 255, 0.01); width: 100%;">
                                <p style="color: var(--text-muted); margin: 0;"><i class="fa-solid fa-info-circle" style="color: var(--primary); margin-right: 6px;"></i> No has añadido ninguna película a favoritos todavía.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($myFavorites as $movie): ?>
                                <div class="management-card" id="movie-card-fav-<?= $movie['id_trailer'] ?>">
                                    <div class="card-media-container">
                                        <img src="<?= htmlspecialchars($movie['poster_url'] ?? 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=200') ?>" alt="<?= htmlspecialchars($movie['titulo']) ?>">
                                        
                                        <?php if ($movie['estado']): ?>
                                            <span class="card-status-badge <?= $movie['estado'] ?>">
                                                <?= $movie['estado'] === 'por_ver' ? 'Por Ver' : 'Vista' ?>
                                            </span>
                                        <?php endif; ?>

                                        <div class="card-media-overlay-bar">
                                            <a href="../trailers/reproducir_trailer.php?id=<?= $movie['id_trailer'] ?>" class="overlay-bar-btn" title="Reproducir"><i class="fa-solid fa-play"></i></a>
                                            <div class="overlay-bar-right">
                                                <button type="button" class="overlay-bar-btn btn-open-note-modal" data-id="<?= $movie['id_trailer'] ?>" data-title="<?= htmlspecialchars($movie['titulo']) ?>" data-comment="<?= htmlspecialchars($movie['comentario'] ?? '') ?>" title="Nota Privada">@</button>
                                                <button type="button" class="overlay-bar-btn btn-remove-favorite" data-id="<?= $movie['id_trailer'] ?>" title="Quitar de favoritos"><i class="fa-solid fa-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-movie-info">
                                        <h4 class="card-movie-title" title="<?= htmlspecialchars($movie['titulo']) ?>"><?= htmlspecialchars($movie['titulo']) ?></h4>
                                        <p class="card-movie-meta"><?= date('Y', strtotime($movie['release_date'])) ?> • <?= htmlspecialchars($movie['genero']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Subpestaña 5: Notas Privadas -->
                <div id="subtab-content-notas" class="management-subtab-content">
                    <h2>Notas Privadas</h2>
                    <p class="subtab-desc">Tus anotaciones personales e historial de ediciones de cada película.</p>
                    
                    <div class="management-grid">
                        <?php if (empty($myComments)): ?>
                            <div class="login-prompt-card" style="grid-column: 1 / -1; padding: 30px; text-align: center; border: 1px dashed var(--border-color); border-radius: var(--radius-md); background: rgba(255, 255, 255, 0.01); width: 100%;">
                                <p style="color: var(--text-muted); margin: 0;"><i class="fa-solid fa-info-circle" style="color: var(--primary); margin-right: 6px;"></i> No has redactado notas privadas en tus películas.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($myComments as $movie): ?>
                                <div class="management-card" id="movie-card-note-<?= $movie['id_trailer'] ?>">
                                    <div class="card-media-container">
                                        <img src="<?= htmlspecialchars($movie['poster_url'] ?? 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=200') ?>" alt="<?= htmlspecialchars($movie['titulo']) ?>">
                                        
                                        <?php if ($movie['estado']): ?>
                                            <span class="card-status-badge <?= $movie['estado'] ?>">
                                                <?= $movie['estado'] === 'por_ver' ? 'Por Ver' : 'Vista' ?>
                                            </span>
                                        <?php endif; ?>

                                        <div class="card-media-overlay-bar">
                                            <a href="../trailers/reproducir_trailer.php?id=<?= $movie['id_trailer'] ?>" class="overlay-bar-btn" title="Reproducir"><i class="fa-solid fa-play"></i></a>
                                            <div class="overlay-bar-right">
                                                <button type="button" class="overlay-bar-btn btn-open-note-modal" data-id="<?= $movie['id_trailer'] ?>" data-title="<?= htmlspecialchars($movie['titulo']) ?>" data-comment="<?= htmlspecialchars($movie['comentario'] ?? '') ?>" title="Nota Privada">@</button>
                                                <button type="button" class="overlay-bar-btn btn-remove-comment" data-id="<?= $movie['id_trailer'] ?>" title="Eliminar nota"><i class="fa-solid fa-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-movie-info">
                                        <h4 class="card-movie-title" title="<?= htmlspecialchars($movie['titulo']) ?>"><?= htmlspecialchars($movie['titulo']) ?></h4>
                                        <p class="card-movie-meta" style="color: var(--primary); margin-bottom: 6px;"><i class="fa-solid fa-comment-dots"></i> Nota Guardada</p>
                                        <p class="card-movie-meta" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($movie['comentario']) ?>"><?= htmlspecialchars($movie['comentario']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

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
<!-- Backdrop del Panel Lateral -->
<div id="drawerBackdrop" class="drawer-backdrop"></div>

<!-- Side Drawer para Detalle y Gestión de Películas -->
<div id="movieDrawer" class="side-drawer">
    <div class="drawer-content">
        <input type="hidden" id="moviesCsrfToken" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="drawer-header">
            <h3 id="drawerMovieTitle">Título de la Película</h3>
            <button type="button" class="close-drawer-btn" id="closeDrawerBtn">&times;</button>
        </div>
        <span class="drawer-badge">Película</span>

        <!-- Botones de conmutación de estado -->
        <div class="drawer-status-toggle">
            <button type="button" class="status-toggle-btn" id="btnTogglePorVer">
                <i class="fa-solid fa-clock"></i> POR VER
            </button>
            <button type="button" class="status-toggle-btn" id="btnToggleVistas">
                <i class="fa-solid fa-eye"></i> VISTAS
            </button>
        </div>

        <!-- Sección de Notas Privadas -->
        <div class="drawer-section-title">
            <span><i class="fa-solid fa-note-sticky"></i> Notas Privadas (Bitácora)</span>
            <span id="charCounter" style="color: var(--text-muted); font-size: 11px;">0 / 2000</span>
        </div>
        <input type="hidden" id="drawerMovieId" value="">
        <textarea id="drawerNoteTextarea" class="drawer-textarea" placeholder="Escribe tus impresiones sobre el tráiler o la película aquí..." maxlength="2000"></textarea>

        <button type="button" id="btnSaveDrawerNote" class="drawer-save-btn">
            <i class="fa-solid fa-save"></i> GUARDAR NOTA
        </button>

        <!-- Acordeón del Historial de Cambios -->
        <div class="drawer-history-accordion" id="drawerHistoryAccordion">
            <div class="accordion-header" id="accordionHeaderBtn">
                <h4><i class="fa-solid fa-history"></i> HISTORIAL DE CAMBIOS</h4>
                <i class="fa-solid fa-chevron-down arrow-icon"></i>
            </div>
            <div class="accordion-content">
                <div id="drawerHistoryList" style="display: flex; flex-direction: column;">
                    <!-- Se carga dinámicamente por JS -->
                </div>
            </div>
        </div>
    </div>
</div>

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

    // === Lógica de sub-pestañas de películas (Mi Gestión Sidebar) ===
    const sidebarBtns = document.querySelectorAll('.sidebar-tab-btn');
    const subtabContents = document.querySelectorAll('.management-subtab-content');

    sidebarBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            sidebarBtns.forEach(b => b.classList.remove('active'));
            subtabContents.forEach(c => c.classList.remove('active'));

            this.classList.add('active');
            const targetSubtab = this.getAttribute('data-subtab');
            const targetContent = document.getElementById(`subtab-content-${targetSubtab}`);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });



    // Side Drawer de Película y Gestión de Notas
    const movieDrawer = document.getElementById('movieDrawer');
    const drawerBackdrop = document.getElementById('drawerBackdrop');
    const drawerMovieTitle = document.getElementById('drawerMovieTitle');
    const drawerMovieId = document.getElementById('drawerMovieId');
    const drawerNoteTextarea = document.getElementById('drawerNoteTextarea');
    const charCounter = document.getElementById('charCounter');
    const btnTogglePorVer = document.getElementById('btnTogglePorVer');
    const btnToggleVistas = document.getElementById('btnToggleVistas');
    const btnSaveDrawerNote = document.getElementById('btnSaveDrawerNote');
    const drawerHistoryAccordion = document.getElementById('drawerHistoryAccordion');
    const accordionHeaderBtn = document.getElementById('accordionHeaderBtn');
    const drawerHistoryList = document.getElementById('drawerHistoryList');
    const closeDrawerBtn = document.getElementById('closeDrawerBtn');
    const moviesCsrfToken = document.getElementById('moviesCsrfToken').value;

    function openMovieDrawer(id_trailer, titulo, status, currentComment) {
        drawerMovieId.value = id_trailer;
        drawerMovieTitle.textContent = titulo;
        drawerNoteTextarea.value = currentComment;
        charCounter.textContent = `${currentComment.length} / 2000`;
        
        // Resetear acordeón
        drawerHistoryAccordion.classList.remove('open');
        drawerHistoryList.innerHTML = '<p style="color: var(--text-muted); font-size: 11px; padding: 12px 16px; margin: 0;">Cargando historial...</p>';

        // Activar botón del estado correcto
        if (status === 'por_ver') {
            btnTogglePorVer.classList.add('active');
            btnToggleVistas.classList.remove('active');
        } else {
            btnTogglePorVer.classList.remove('active');
            btnToggleVistas.classList.add('active');
        }

        // Mostrar Drawer
        movieDrawer.classList.add('open');
        drawerBackdrop.classList.add('show');

        // Cargar historial asíncronamente
        fetch('api_listas.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': moviesCsrfToken
            },
            body: JSON.stringify({
                action: 'get_history',
                id_trailer: id_trailer
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                drawerHistoryList.innerHTML = '';
                if (data.history.length === 0) {
                    drawerHistoryList.innerHTML = '<p style="color: var(--text-muted); font-size: 11px; padding: 12px 16px; margin: 0;">No hay cambios registrados en tu nota.</p>';
                } else {
                    data.history.forEach(h => {
                        const entry = document.createElement('div');
                        entry.className = 'history-entry-box';
                        entry.style.borderLeft = '2px solid var(--primary)';
                        entry.innerHTML = `
                            <div class="history-entry-date">Edición: ${h.fecha_cambio}</div>
                            <div class="history-entry-text">${escapeHtml(h.comentario_anterior)}</div>
                        `;
                        drawerHistoryList.appendChild(entry);
                    });
                }
            } else {
                drawerHistoryList.innerHTML = '<p style="color: #ef4444; font-size: 11px; padding: 12px 16px; margin: 0;">Error al cargar historial.</p>';
            }
        })
        .catch(err => {
            console.error(err);
            drawerHistoryList.innerHTML = '<p style="color: #ef4444; font-size: 11px; padding: 12px 16px; margin: 0;">Error de conexión.</p>';
        });
    }

    function closeMovieDrawer() {
        movieDrawer.classList.remove('open');
        drawerBackdrop.classList.remove('show');
    }

    // Delegación de clic en las tarjetas de película
    document.addEventListener('click', function(e) {
        const card = e.target.closest('.management-card');
        const actionBtn = e.target.closest('.overlay-bar-btn, .btn-remove-list-item, .btn-remove-favorite, .btn-remove-comment');
        
        // Si hace clic en la tarjeta pero no en una acción directa
        if (card && !actionBtn) {
            const id_trailer = card.getAttribute('id').replace(/[^\d]/g, '');
            const title = card.querySelector('.card-movie-title').textContent;
            
            // Determinar estado a partir del badge o buscar
            let status = 'por_ver';
            const badge = card.querySelector('.card-status-badge');
            if (badge) {
                status = badge.classList.contains('vista') ? 'vista' : 'por_ver';
            }
            
            // Buscar comentario en la propia tarjeta o en los datos del modal nota anterior si existiera
            let comment = '';
            const oldNoteBtn = card.querySelector('.btn-open-note-modal');
            if (oldNoteBtn) {
                comment = oldNoteBtn.getAttribute('data-comment') || '';
            }
            
            openMovieDrawer(id_trailer, title, status, comment);
        }
    });

    if (closeDrawerBtn) {
        closeDrawerBtn.addEventListener('click', closeMovieDrawer);
    }
    if (drawerBackdrop) {
        drawerBackdrop.addEventListener('click', closeMovieDrawer);
    }

    // Cerrar también con la tecla Esc
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeMovieDrawer();
        }
    });

    // Contador de caracteres dinámico
    if (drawerNoteTextarea) {
        drawerNoteTextarea.addEventListener('input', function() {
            charCounter.textContent = `${this.value.length} / 2000`;
        });
    }

    // Acordeón del Historial
    if (accordionHeaderBtn) {
        accordionHeaderBtn.addEventListener('click', () => {
            drawerHistoryAccordion.classList.toggle('open');
        });
    }

    // Guardar Nota desde el Drawer
    if (btnSaveDrawerNote) {
        btnSaveDrawerNote.addEventListener('click', () => {
            const id_trailer = drawerMovieId.value;
            const commentText = drawerNoteTextarea.value;

            fetch('api_listas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': moviesCsrfToken
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
                    closeMovieDrawer();
                    showToast('¡Nota guardada con éxito!', 'success');
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    showToast(data.error, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error al guardar comentario.', 'error');
            });
        });
    }

    // Conmutación rápida de estado (watchlist vs watched)
    function changeMovieStatus(nextStatus) {
        const id_trailer = drawerMovieId.value;
        fetch('api_listas.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': moviesCsrfToken
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
                showToast('¡Estado de la película actualizado!', 'success');
                if (nextStatus === 'por_ver') {
                    btnTogglePorVer.classList.add('active');
                    btnToggleVistas.classList.remove('active');
                } else {
                    btnTogglePorVer.classList.remove('active');
                    btnToggleVistas.classList.add('active');
                }
                setTimeout(() => window.location.reload(), 800);
            } else {
                showToast(data.error, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Error al cambiar de lista.', 'error');
        });
    }

    if (btnTogglePorVer) {
        btnTogglePorVer.addEventListener('click', () => {
            if (!btnTogglePorVer.classList.contains('active')) {
                changeMovieStatus('por_ver');
            }
        });
    }
    if (btnToggleVistas) {
        btnToggleVistas.addEventListener('click', () => {
            if (!btnToggleVistas.classList.contains('active')) {
                changeMovieStatus('vista');
            }
        });
    }

    // Eliminar película de la lista
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-remove-list-item');
        if (btn) {
            const id_trailer = btn.getAttribute('data-id');
            if (confirm('¿Estás seguro de que quieres quitar esta película de tus listas?')) {
                fetch('api_listas.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': moviesCsrfToken
                    },
                    body: JSON.stringify({
                        action: 'remove_from_list',
                        id_trailer: id_trailer
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast('¡Película eliminada de tus listas!', 'success');
                        setTimeout(() => window.location.reload(), 800);
                    } else {
                        showToast(data.error, 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Error al eliminar de la lista.', 'error');
                });
            }
        }
    });

    // Eliminar película de favoritos
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-remove-favorite');
        if (btn) {
            const id_trailer = btn.getAttribute('data-id');
            if (confirm('¿Estás seguro de que quieres quitar esta película de tus favoritos?')) {
                fetch(`../trailers/toggle_favorito.php?id=${id_trailer}&ajax=1`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast('¡Película eliminada de tus favoritos!', 'success');
                        setTimeout(() => window.location.reload(), 800);
                    } else {
                        showToast('Error al quitar de favoritos.', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Error de conexión.', 'error');
                });
            }
        }
    });

    // Eliminar nota privada
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-remove-comment');
        if (btn) {
            const id_trailer = btn.getAttribute('data-id');
            if (confirm('¿Estás seguro de que quieres eliminar tu nota privada de esta película?')) {
                fetch('api_listas.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': moviesCsrfToken
                    },
                    body: JSON.stringify({
                        action: 'save_comment',
                        id_trailer: id_trailer,
                        comentario: ''
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast('¡Nota privada eliminada!', 'success');
                        setTimeout(() => window.location.reload(), 800);
                    } else {
                        showToast(data.error, 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Error al eliminar la nota.', 'error');
                });
            }
        }
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
