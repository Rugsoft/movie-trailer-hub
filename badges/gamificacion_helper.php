<?php
// badges/gamificacion_helper.php

// Ejecutar migraciones automáticas al incluir el archivo
function inicializar_gamificacion_db($conexion) {
    // 1. Tabla de Badges
    $sqlBadges = "CREATE TABLE IF NOT EXISTS badges (
        id_badge INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL UNIQUE,
        descripcion VARCHAR(255) NOT NULL,
        requisito_tipo VARCHAR(50) NOT NULL, -- 'registro', 'visualizaciones', 'minutos_vistos', 'reseñas', 'favoritos', 'racha_login'
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

    // 4. Seeder de Badges Iniciales
    $checkEmpty = mysqli_query($conexion, "SELECT COUNT(*) as total FROM badges");
    $row = mysqli_fetch_assoc($checkEmpty);
    if ($row['total'] == 0) {
        $badgesSemilla = [
            ['Pionero', 'Únete a nuestra comunidad y crea tu cuenta.', 'registro', 0, 'fa-user-plus'],
            ['Primer Vistazo', 'Reproduce tu primer trailer en la plataforma.', 'visualizaciones', 1, 'fa-circle-play'],
            ['Maratonista de Trailers', 'Acumula un mínimo de 30 minutos visualizando contenido.', 'minutos_vistos', 30, 'fa-stopwatch'],
            ['Crítico de Cine', 'Escribe y comparte 3 reseñas en tus trailers favoritos.', 'reseñas', 3, 'fa-comments'],
            ['Coleccionista de Joyas', 'Guarda 5 trailers en tu sección de favoritos.', 'favoritos', 5, 'fa-heart'],
            ['Espectador Constante', 'Visita la plataforma durante 3 días consecutivos.', 'racha_login', 3, 'fa-fire']
        ];
        
        $sqlSeed = "INSERT INTO badges (nombre, descripcion, requisito_tipo, requisito_valor, icono) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conexion, $sqlSeed);
        if ($stmt) {
            foreach ($badgesSemilla as $b) {
                mysqli_stmt_bind_param($stmt, "sssis", $b[0], $b[1], $b[2], $b[3], $b[4]);
                mysqli_stmt_execute($stmt);
            }
            mysqli_stmt_close($stmt);
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
            $sql = "SELECT COALESCE(SUM(t.duracion), 0) as total FROM visualizaciones v JOIN trailers t ON v.id_trailer = t.id_trailer WHERE v.id_usuario = ?";
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
