<?php
// badges/gamificacion_helper.php

// Ejecutar migraciones automáticas al incluir el archivo
function inicializar_gamificacion_db($conexion) {
    // 1. Tabla de Badges
    $sqlBadges = "CREATE TABLE IF NOT EXISTS badges (
        id_badge INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL UNIQUE,
        descripcion VARCHAR(255) NOT NULL,
        requisito_tipo VARCHAR(50) NOT NULL,
        requisito_valor INT NOT NULL,
        icono VARCHAR(100) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conexion, $sqlBadges);

    // 2. Tabla de Relación Usuario-Badges
    $sqlUserBadges = "CREATE TABLE IF NOT EXISTS usuario_badges (
        id_usuario INT NOT NULL,
        id_badge INT NOT NULL,
        fecha_desbloqueo DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id_usuario, id_badge),
        CONSTRAINT fk_ub_usuarios FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_ub_badges FOREIGN KEY (id_badge) REFERENCES badges(id_badge) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conexion, $sqlUserBadges);

    // 3. Tabla de Racha de Logins
    $sqlRachas = "CREATE TABLE IF NOT EXISTS usuario_rachas (
        id_usuario INT PRIMARY KEY,
        fecha_ultimo_login DATE NOT NULL,
        racha_actual INT NOT NULL DEFAULT 1,
        CONSTRAINT fk_ur_usuarios FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conexion, $sqlRachas);

    // 4. Tabla de Stats de Gamificación
    $sqlStats = "CREATE TABLE IF NOT EXISTS usuario_gamificacion_stats (
        id_usuario INT PRIMARY KEY,
        modo_cine_activado TINYINT DEFAULT 0,
        intentos_fallidos_admin TINYINT DEFAULT 0,
        busquedas_fecha_actual TINYINT DEFAULT 0,
        registro_invitacion TINYINT DEFAULT 0,
        CONSTRAINT fk_ugs_usuarios FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conexion, $sqlStats);

    // 5. Tabla de Lectura de Reseñas
    $sqlLecturas = "CREATE TABLE IF NOT EXISTS usuario_lectura_resenas (
        id_usuario INT NOT NULL,
        id_trailer INT NOT NULL,
        fecha_lectura DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id_usuario, id_trailer),
        CONSTRAINT fk_ulr_usuarios FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_ulr_trailers FOREIGN KEY (id_trailer) REFERENCES trailers(id_trailer) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conexion, $sqlLecturas);

    // 6. Modificar tabla favoritos para añadir fecha_adicion si no existe
    $checkCol = mysqli_query($conexion, "SHOW COLUMNS FROM favoritos LIKE 'fecha_adicion'");
    if (mysqli_num_rows($checkCol) == 0) {
        mysqli_query($conexion, "ALTER TABLE favoritos ADD COLUMN fecha_adicion DATETIME DEFAULT CURRENT_TIMESTAMP");
    }

    // 7. Seeder de Badges (Idempotente)
    $badgesSemilla = [
        ['Pionero', 'Únete a nuestra comunidad y crea tu cuenta.', 'registro', 0, 'fa-user-plus'],
        ['Primer Vistazo', 'Reproduce tu primer trailer en la plataforma.', 'visualizaciones', 1, 'fa-circle-play'],
        ['Maratonista de Trailers', 'Acumula un mínimo de 30 minutos visualizando contenido.', 'minutos_vistos', 30, 'fa-stopwatch'],
        ['Crítico de Cine', 'Escribe y comparte 3 reseñas en tus trailers favoritos.', 'reseñas', 3, 'fa-comments'],
        ['Coleccionista de Joyas', 'Guarda 5 trailers en tu sección de favoritos.', 'favoritos', 5, 'fa-heart'],
        ['Espectador Constante', 'Visita la plataforma durante 3 días consecutivos.', 'racha_login', 3, 'fa-fire'],
        
        ['Maratonista Experto', 'Acumula un mínimo de 60 minutos (1 hora) visualizando trailers.', 'minutos_vistos', 60, 'fa-business-time'],
        ['Proyeccionista', 'Acumula un mínimo de 120 minutos (2 horas) visualizando trailers.', 'minutos_vistos', 120, 'fa-compact-disc'],
        ['Maratón Leyenda', 'Acumula un mínimo de 300 minutos (5 horas) visualizando trailers.', 'minutos_vistos', 300, 'fa-crown'],
        
        ['Explorador de Cine', 'Visualiza 5 trailers diferentes.', 'visualizaciones', 5, 'fa-ticket'],
        ['Cinéfilo Consagrado', 'Visualiza 10 trailers diferentes.', 'visualizaciones', 10, 'fa-film'],
        ['Leyenda del Tráiler', 'Visualiza 25 trailers diferentes.', 'visualizaciones', 25, 'fa-award'],
        
        ['Coleccionista Avanzado', 'Guarda 10 trailers en tu sección de favoritos.', 'favoritos', 10, 'fa-heart-circle-check'],
        ['Guardián del Tesoro', 'Guarda 25 trailers en tu sección de favoritos.', 'favoritos', 25, 'fa-gem'],
        
        ['Comentador Habitual', 'Escribe y comparte 5 comentarios en las reseñas.', 'comentarios', 5, 'fa-message'],
        
        ['Cazador de Sombras', 'Visualiza 5 trailers de terror o suspense.', 'genero_terror', 5, 'fa-ghost'],
        ['Corazón de Oro', 'Visualiza 5 trailers de películas románticas o dramáticas.', 'genero_romance', 5, 'fa-heart-pulse'],
        ['Explorador de Mundos', 'Visualiza 5 trailers de ciencia ficción o fantasía.', 'genero_scifi', 5, 'fa-shuttle-space'],
        ['Adrenalina Pura', 'Visualiza 5 trailers de acción o aventuras.', 'genero_accion', 5, 'fa-bolt'],
        ['Cineasta Erudito', 'Visualiza 5 trailers de películas estrenadas antes del año 2000.', 'clasicos', 5, 'fa-calendar-minus'],
        
        ['Luz, Cámara, Acción', 'Activa el Modo Cine (apagar luces) por primera vez.', 'modo_cine', 1, 'fa-video'],
        ['Crítico Imparcial', 'Valora películas con calificaciones de 1 estrella y de 5 estrellas.', 'critico_imparcial', 1, 'fa-scale-balanced'],
        ['Lector de Críticas', 'Despliega y lee la sección de reseñas en 10 películas diferentes.', 'lector_criticas', 10, 'fa-book-open-reader'],
        ['Escritor de Culto', 'Escribe una reseña detallada de más de 200 caracteres.', 'resena_larga', 1, 'fa-pen-nib'],
        
        ['Cinéfilo Nocturno', 'Visualiza un trailer o publica una reseña entre las 00:00h y las 05:00h.', 'cinefilo_nocturno', 1, 'fa-moon'],
        ['Fiebre de Fin de Semana', 'Inicia sesión y ve trailers tanto sábado como domingo del mismo fin de semana.', 'fin_de_semana', 1, 'fa-calendar-days'],
        ['Racha Imparable', 'Visita la plataforma durante 7 días consecutivos.', 'racha_login', 7, 'fa-fire-flame-curved'],
        
        ['El Viajero del Tiempo', 'Busca una fecha de estreno exacta del año actual (2026) en el buscador.', 'viajero_tiempo', 1, 'fa-hourglass-end'],
        ['¡No Puedes Pasar!', 'Intenta acceder al panel de gestión de usuarios sin poseer permisos.', 'acceso_denegado', 1, 'fa-hand'],
        ['Un Anillo para Gobernarlos', 'Mantén un trailer en tus favoritos durante más de 30 días seguidos.', 'favorito_duradero', 1, 'fa-ring'],
        ['La Comunidad del Tráiler', 'Regístrate usando invitación o el mismo día que otros 5 usuarios.', 'comunidad_trailer', 1, 'fa-users-line']
    ];

    foreach ($badgesSemilla as $b) {
        $checkBadge = mysqli_prepare($conexion, "SELECT id_badge FROM badges WHERE nombre = ? LIMIT 1");
        if ($checkBadge) {
            mysqli_stmt_bind_param($checkBadge, "s", $b[0]);
            mysqli_stmt_execute($checkBadge);
            $resBadge = mysqli_stmt_get_result($checkBadge);
            if (mysqli_num_rows($resBadge) === 0) {
                $sqlSeed = "INSERT INTO badges (nombre, descripcion, requisito_tipo, requisito_valor, icono) VALUES (?, ?, ?, ?, ?)";
                $stmtSeed = mysqli_prepare($conexion, $sqlSeed);
                if ($stmtSeed) {
                    mysqli_stmt_bind_param($stmtSeed, "sssis", $b[0], $b[1], $b[2], $b[3], $b[4]);
                    mysqli_stmt_execute($stmtSeed);
                    mysqli_stmt_close($stmtSeed);
                }
            }
            mysqli_stmt_close($checkBadge);
        }
    }
}

// Actualizar la racha de logins consecutivos
function actualizar_racha_login($conexion, $id_usuario) {
    inicializar_gamificacion_db($conexion);

    $hoy = date('Y-m-d');
    $ayer = date('Y-m-d', strtotime('-1 day'));

    $sql = "SELECT fecha_ultimo_login, racha_actual FROM usuario_rachas WHERE id_usuario = ? LIMIT 1";
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_usuario);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$row) {
            // Primer registro
            $sqlIns = "INSERT INTO usuario_rachas (id_usuario, fecha_ultimo_login, racha_actual) VALUES (?, ?, 1)";
            $stmtIns = mysqli_prepare($conexion, $sqlIns);
            if ($stmtIns) {
                mysqli_stmt_bind_param($stmtIns, "is", $id_usuario, $hoy);
                mysqli_stmt_execute($stmtIns);
                mysqli_stmt_close($stmtIns);
            }
        } else {
            $ultimoLogin = $row['fecha_ultimo_login'];
            $rachaActual = (int)$row['racha_actual'];

            if ($ultimoLogin === $ayer) {
                // Login consecutivo: incrementamos
                $nuevaRacha = $rachaActual + 1;
                $sqlUpd = "UPDATE usuario_rachas SET fecha_ultimo_login = ?, racha_actual = ? WHERE id_usuario = ?";
                $stmtUpd = mysqli_prepare($conexion, $sqlUpd);
                if ($stmtUpd) {
                    mysqli_stmt_bind_param($stmtUpd, "sii", $hoy, $nuevaRacha, $id_usuario);
                    mysqli_stmt_execute($stmtUpd);
                    mysqli_stmt_close($stmtUpd);
                }
            } elseif ($ultimoLogin !== $hoy) {
                // Perdió la racha: reset a 1
                $sqlUpd = "UPDATE usuario_rachas SET fecha_ultimo_login = ?, racha_actual = 1 WHERE id_usuario = ?";
                $stmtUpd = mysqli_prepare($conexion, $sqlUpd);
                if ($stmtUpd) {
                    mysqli_stmt_bind_param($stmtUpd, "si", $hoy, $id_usuario);
                    mysqli_stmt_execute($stmtUpd);
                    mysqli_stmt_close($stmtUpd);
                }
            }
        }
    }
}

// Evaluar un requisito de badge y retornar [conseguido, actual, requerido]
function evaluar_requisito($conexion, $id_usuario, $tipo, $valor) {
    switch ($tipo) {
        case 'registro':
            return [true, 1, 1];
            
        case 'visualizaciones':
            $sql = "SELECT COUNT(DISTINCT id_trailer) as total FROM visualizaciones WHERE id_usuario = ?";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = (int)($row['total'] ?? 0);
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'minutos_vistos':
            $sql = "SELECT COUNT(*) as total FROM visualizaciones WHERE id_usuario = ?";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $total_vistas = (int)($row['total'] ?? 0);
                $actual = (int)round($total_vistas * 2.5);
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'reseñas':
            $sql = "SELECT COUNT(*) as total FROM resenas WHERE id_usuario = ?";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = (int)($row['total'] ?? 0);
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'favoritos':
            $sql = "SELECT COUNT(*) as total FROM favoritos WHERE id_usuario = ?";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = (int)($row['total'] ?? 0);
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'racha_login':
            $sql = "SELECT racha_actual FROM usuario_rachas WHERE id_usuario = ? LIMIT 1";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = (int)($row['racha_actual'] ?? 0);
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'comentarios':
            $sql = "SELECT COUNT(*) as total FROM resenas WHERE id_usuario = ? AND comentario IS NOT NULL AND TRIM(comentario) != ''";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = (int)($row['total'] ?? 0);
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'genero_terror':
            $sql = "SELECT COUNT(DISTINCT v.id_trailer) as total 
                    FROM visualizaciones v 
                    JOIN trailers_generos tg ON v.id_trailer = tg.id_trailer 
                    WHERE v.id_usuario = ? AND tg.id_genero IN (5, 12)";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = (int)($row['total'] ?? 0);
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'genero_romance':
            $sql = "SELECT COUNT(DISTINCT v.id_trailer) as total 
                    FROM visualizaciones v 
                    JOIN trailers_generos tg ON v.id_trailer = tg.id_trailer 
                    WHERE v.id_usuario = ? AND tg.id_genero IN (6, 15)";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = (int)($row['total'] ?? 0);
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'genero_scifi':
            $sql = "SELECT COUNT(DISTINCT v.id_trailer) as total 
                    FROM visualizaciones v 
                    JOIN trailers_generos tg ON v.id_trailer = tg.id_trailer 
                    WHERE v.id_usuario = ? AND tg.id_genero IN (1, 13)";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = (int)($row['total'] ?? 0);
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'genero_accion':
            $sql = "SELECT COUNT(DISTINCT v.id_trailer) as total 
                    FROM visualizaciones v 
                    JOIN trailers_generos tg ON v.id_trailer = tg.id_trailer 
                    WHERE v.id_usuario = ? AND tg.id_genero IN (2, 4)";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = (int)($row['total'] ?? 0);
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'clasicos':
            $sql = "SELECT COUNT(DISTINCT v.id_trailer) as total 
                    FROM visualizaciones v 
                    JOIN trailers t ON v.id_trailer = t.id_trailer 
                    WHERE v.id_usuario = ? AND YEAR(t.release_date) < 2000";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = (int)($row['total'] ?? 0);
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'modo_cine':
            $sql = "SELECT COALESCE(modo_cine_activado, 0) as total FROM usuario_gamificacion_stats WHERE id_usuario = ?";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = (int)($row['total'] ?? 0);
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'critico_imparcial':
            $sql = "SELECT 
                      (SELECT COUNT(*) FROM resenas WHERE id_usuario = ? AND valoracion = 1) as rating_1,
                      (SELECT COUNT(*) FROM resenas WHERE id_usuario = ? AND valoracion = 5) as rating_5";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $id_usuario, $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = ((int)$row['rating_1'] > 0 && (int)$row['rating_5'] > 0) ? 1 : 0;
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'lector_criticas':
            $sql = "SELECT COUNT(*) as total FROM usuario_lectura_resenas WHERE id_usuario = ?";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = (int)($row['total'] ?? 0);
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'resena_larga':
            $sql = "SELECT COUNT(*) as total FROM resenas WHERE id_usuario = ? AND CHAR_LENGTH(comentario) > 200";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = (int)($row['total'] ?? 0);
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'cinefilo_nocturno':
            $sql = "SELECT (
                      SELECT COUNT(*) FROM visualizaciones WHERE id_usuario = ? AND HOUR(fecha_visualizacion) >= 0 AND HOUR(fecha_visualizacion) < 5
                    ) + (
                      SELECT COUNT(*) FROM resenas WHERE id_usuario = ? AND HOUR(fecha_alta) >= 0 AND HOUR(fecha_alta) < 5
                    ) as total";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $id_usuario, $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = (int)$row['total'] > 0 ? 1 : 0;
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'fin_de_semana':
            $sql = "SELECT COUNT(*) as total FROM visualizaciones v1 
                    JOIN visualizaciones v2 ON v1.id_usuario = v2.id_usuario 
                    WHERE v1.id_usuario = ? 
                      AND WEEKDAY(v1.fecha_visualizacion) = 5 
                      AND WEEKDAY(v2.fecha_visualizacion) = 6 
                      AND YEARWEEK(v1.fecha_visualizacion) = YEARWEEK(v2.fecha_visualizacion)";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = (int)$row['total'] > 0 ? 1 : 0;
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'viajero_tiempo':
            $sql = "SELECT COALESCE(busquedas_fecha_actual, 0) as total FROM usuario_gamificacion_stats WHERE id_usuario = ?";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = (int)($row['total'] ?? 0);
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'acceso_denegado':
            $sql = "SELECT COALESCE(intentos_fallidos_admin, 0) as total FROM usuario_gamificacion_stats WHERE id_usuario = ?";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = (int)($row['total'] ?? 0);
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'favorito_duradero':
            $sql = "SELECT COUNT(*) as total FROM favoritos WHERE id_usuario = ? AND DATEDIFF(NOW(), fecha_adicion) >= 30";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = (int)($row['total'] ?? 0);
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        case 'comunidad_trailer':
            $sql = "SELECT (
                      SELECT COUNT(*) FROM usuario_gamificacion_stats WHERE id_usuario = ? AND registro_invitacion = 1
                    ) as invitacion, (
                      SELECT COUNT(*) FROM usuarios u1 JOIN usuarios u2 ON u1.fecha_alta = u2.fecha_alta WHERE u1.id_usuario = ?
                    ) as grupo_registro";
            $stmt = mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $id_usuario, $id_usuario);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                $actual = ((int)$row['invitacion'] > 0 || (int)$row['grupo_registro'] >= 6) ? 1 : 0;
                return [$actual >= $valor, $actual, $valor];
            }
            return [false, 0, $valor];

        default:
            return [false, 0, $valor];
    }
}

// Analizar badges e insertar automáticamente los nuevos desbloqueados
function procesar_y_obtener_badges($conexion, $id_usuario) {
    inicializar_gamificacion_db($conexion);
    
    // Obtener todos los badges del sistema
    $sqlBadges = "SELECT * FROM badges ORDER BY id_badge ASC";
    $res = mysqli_query($conexion, $sqlBadges);
    $badges = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $badges[] = $row;
    }
    
    // Obtener badges ya desbloqueados por el usuario
    $sqlUnlocked = "SELECT id_badge, fecha_desbloqueo FROM usuario_badges WHERE id_usuario = ?";
    $stmt = mysqli_prepare($conexion, $sqlUnlocked);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_usuario);
        mysqli_stmt_execute($stmt);
        $resUnlocked = mysqli_stmt_get_result($stmt);
        $unlockedMap = [];
        while ($row = mysqli_fetch_assoc($resUnlocked)) {
            $unlockedMap[(int)$row['id_badge']] = $row['fecha_desbloqueo'];
        }
        mysqli_stmt_close($stmt);
    } else {
        $unlockedMap = [];
    }
    
    $badgesConProgreso = [];
    
    foreach ($badges as $badge) {
        $idBadge = (int)$badge['id_badge'];
        $tipo = $badge['requisito_tipo'];
        $valRequerido = (int)$badge['requisito_valor'];
        
        list($cumpleRequisito, $valorActual, $valorRequerido) = evaluar_requisito($conexion, $id_usuario, $tipo, $valRequerido);
        
        $desbloqueado = isset($unlockedMap[$idBadge]);
        $fechaDesbloqueo = $desbloqueado ? $unlockedMap[$idBadge] : null;
        
        if ($cumpleRequisito && !$desbloqueado) {
            // Desbloqueo de badge por primera vez
            $sqlIns = "INSERT INTO usuario_badges (id_usuario, id_badge, fecha_desbloqueo) VALUES (?, ?, NOW()) 
                       ON DUPLICATE KEY UPDATE fecha_desbloqueo = NOW()";
            $stmtIns = mysqli_prepare($conexion, $sqlIns);
            if ($stmtIns) {
                mysqli_stmt_bind_param($stmtIns, "ii", $id_usuario, $idBadge);
                mysqli_stmt_execute($stmtIns);
                mysqli_stmt_close($stmtIns);
            }
            
            $desbloqueado = true;
            $fechaDesbloqueo = date('Y-m-d H:i:s');
        }
        
        $badgesConProgreso[] = [
            'id_badge' => $idBadge,
            'nombre' => $badge['nombre'],
            'descripcion' => $badge['descripcion'],
            'requisito_tipo' => $tipo,
            'requisito_valor' => $valorRequerido,
            'icono' => $badge['icono'],
            'desbloqueado' => $desbloqueado,
            'fecha_desbloqueo' => $fechaDesbloqueo,
            'progreso_actual' => $valorActual
        ];
    }
    
    return $badgesConProgreso;
}
?>
