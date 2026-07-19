<?php
// badges/gamificacion_helper.php

const MOVIE_APP_BADGES_SEED_VERSION = 1;
const MOVIE_APP_BADGES_CHECK_INTERVAL = 300;

/**
 * Inserta únicamente las insignias que todavía no existen.
 */
function sembrar_badges(mysqli $conexion): void {
    $versionSesion = (int) ($_SESSION['movie_app_badges_seed_version'] ?? 0);
    if ($versionSesion >= MOVIE_APP_BADGES_SEED_VERSION) {
        return;
    }

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

    $resultadoExistentes = mysqli_query($conexion, 'SELECT nombre FROM badges');
    if ($resultadoExistentes === false) {
        throw new RuntimeException(
            'No se pudieron consultar las insignias existentes: ' . mysqli_error($conexion)
        );
    }

    $nombresExistentes = [];
    while ($badgeExistente = mysqli_fetch_assoc($resultadoExistentes)) {
        $nombresExistentes[(string) $badgeExistente['nombre']] = true;
    }

    $sqlInsertar = 'INSERT INTO badges (
                        nombre,
                        descripcion,
                        requisito_tipo,
                        requisito_valor,
                        icono
                    ) VALUES (?, ?, ?, ?, ?)';
    $stmtInsertar = mysqli_prepare($conexion, $sqlInsertar);

    if (!$stmtInsertar) {
        throw new RuntimeException(
            'No se pudo preparar la siembra de insignias: ' . mysqli_error($conexion)
        );
    }

    foreach ($badgesSemilla as $badge) {
        $nombre = $badge[0];
        if (isset($nombresExistentes[$nombre])) {
            continue;
        }

        $descripcion = $badge[1];
        $requisitoTipo = $badge[2];
        $requisitoValor = $badge[3];
        $icono = $badge[4];

        mysqli_stmt_bind_param(
            $stmtInsertar,
            'sssis',
            $nombre,
            $descripcion,
            $requisitoTipo,
            $requisitoValor,
            $icono
        );

        if (!mysqli_stmt_execute($stmtInsertar)) {
            $codigoError = mysqli_stmt_errno($stmtInsertar);
            $detalle = mysqli_stmt_error($stmtInsertar);

            if ($codigoError === 1062) {
                $nombresExistentes[$nombre] = true;
                continue;
            }

            mysqli_stmt_close($stmtInsertar);
            throw new RuntimeException('No se pudo insertar la insignia ' . $nombre . ': ' . $detalle);
        }

        $nombresExistentes[$nombre] = true;
    }

    mysqli_stmt_close($stmtInsertar);
    $_SESSION['movie_app_badges_seed_version'] = MOVIE_APP_BADGES_SEED_VERSION;
}

function marcar_recalculo_badges_pendiente(): void {
    if (session_status() === PHP_SESSION_ACTIVE || session_status() === PHP_SESSION_NONE) {
        $_SESSION['movie_app_badges_force_check'] = true;
    }
}

function procesar_badges_si_corresponde(mysqli $conexion, int $id_usuario): void {
    $ahora = time();
    $ultimoChequeo = (int) ($_SESSION['movie_app_badges_last_check_at'] ?? 0);
    $forzarChequeo = !empty($_SESSION['movie_app_badges_force_check']);

    if (!$forzarChequeo && ($ahora - $ultimoChequeo) < MOVIE_APP_BADGES_CHECK_INTERVAL) {
        return;
    }

    procesar_y_obtener_badges($conexion, $id_usuario);

    $_SESSION['movie_app_badges_last_check_at'] = $ahora;
    unset($_SESSION['movie_app_badges_force_check']);
}

// Actualizar la racha de logins consecutivos
function actualizar_racha_login($conexion, $id_usuario) {
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
                if (mysqli_stmt_execute($stmtIns)) {
                    marcar_recalculo_badges_pendiente();
                }
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
                    if (mysqli_stmt_execute($stmtUpd)) {
                        marcar_recalculo_badges_pendiente();
                    }
                    mysqli_stmt_close($stmtUpd);
                }
            } elseif ($ultimoLogin !== $hoy) {
                // Perdió la racha: reset a 1
                $sqlUpd = "UPDATE usuario_rachas SET fecha_ultimo_login = ?, racha_actual = 1 WHERE id_usuario = ?";
                $stmtUpd = mysqli_prepare($conexion, $sqlUpd);
                if ($stmtUpd) {
                    mysqli_stmt_bind_param($stmtUpd, "si", $hoy, $id_usuario);
                    if (mysqli_stmt_execute($stmtUpd)) {
                        marcar_recalculo_badges_pendiente();
                    }
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
    sembrar_badges($conexion);
    
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

            if (session_status() === PHP_SESSION_ACTIVE || session_status() === PHP_SESSION_NONE) {
                $_SESSION['nuevos_logros_desbloqueados'][] = [
                    'nombre' => $badge['nombre'],
                    'descripcion' => $badge['descripcion']
                ];
            }
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
