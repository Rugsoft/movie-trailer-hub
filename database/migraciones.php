<?php

declare(strict_types=1);

const MOVIE_APP_SCHEMA_VERSION = 2;
const MOVIE_APP_MIGRATION_LOCK = 'movie_trailer_hub_schema_migrations';

/**
 * Ejecuta una sentencia de migración y convierte el error en una excepción.
 */
function ejecutar_sql_migracion(
    mysqli $conexion,
    string $sql,
    string $contexto
): void {
    try {
        $resultado = mysqli_query($conexion, $sql);
    } catch (Throwable $error) {
        throw new RuntimeException($contexto, 0, $error);
    }

    if ($resultado === false) {
        throw new RuntimeException($contexto . ': ' . mysqli_error($conexion));
    }
}

/**
 * Comprueba la existencia de una tabla en el esquema seleccionado.
 */
function tabla_esquema_existe(mysqli $conexion, string $tabla): bool {
    $sql = 'SELECT 1
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
            LIMIT 1';
    $stmt = mysqli_prepare($conexion, $sql);

    if (!$stmt) {
        throw new RuntimeException(
            'No se pudo comprobar la tabla ' . $tabla . ': ' . mysqli_error($conexion)
        );
    }

    mysqli_stmt_bind_param($stmt, 's', $tabla);

    if (!mysqli_stmt_execute($stmt)) {
        $detalle = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new RuntimeException('No se pudo comprobar la tabla ' . $tabla . ': ' . $detalle);
    }

    $resultado = mysqli_stmt_get_result($stmt);
    $existe = $resultado !== false && mysqli_fetch_row($resultado) !== null;
    mysqli_stmt_close($stmt);

    return $existe;
}

/**
 * Obtiene la definición de una columna o null cuando todavía no existe.
 *
 * @return array<string, string|null>|null
 */
function obtener_columna_esquema(
    mysqli $conexion,
    string $tabla,
    string $columna
): ?array {
    $sql = 'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            LIMIT 1';
    $stmt = mysqli_prepare($conexion, $sql);

    if (!$stmt) {
        throw new RuntimeException(
            'No se pudo comprobar la columna ' . $tabla . '.' . $columna
            . ': ' . mysqli_error($conexion)
        );
    }

    mysqli_stmt_bind_param($stmt, 'ss', $tabla, $columna);

    if (!mysqli_stmt_execute($stmt)) {
        $detalle = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new RuntimeException(
            'No se pudo comprobar la columna ' . $tabla . '.' . $columna . ': ' . $detalle
        );
    }

    $resultado = mysqli_stmt_get_result($stmt);
    $definicion = $resultado ? mysqli_fetch_assoc($resultado) : null;
    mysqli_stmt_close($stmt);

    return $definicion ?: null;
}

/**
 * Comprueba un índice por su nombre.
 */
function indice_esquema_existe(
    mysqli $conexion,
    string $tabla,
    string $indice
): bool {
    $sql = 'SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?
            LIMIT 1';
    $stmt = mysqli_prepare($conexion, $sql);

    if (!$stmt) {
        throw new RuntimeException(
            'No se pudo comprobar el índice ' . $tabla . '.' . $indice
            . ': ' . mysqli_error($conexion)
        );
    }

    mysqli_stmt_bind_param($stmt, 'ss', $tabla, $indice);

    if (!mysqli_stmt_execute($stmt)) {
        $detalle = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new RuntimeException(
            'No se pudo comprobar el índice ' . $tabla . '.' . $indice . ': ' . $detalle
        );
    }

    $resultado = mysqli_stmt_get_result($stmt);
    $existe = $resultado !== false && mysqli_fetch_row($resultado) !== null;
    mysqli_stmt_close($stmt);

    return $existe;
}

/**
 * Comprueba una restricción por su nombre.
 */
function restriccion_esquema_existe(
    mysqli $conexion,
    string $tabla,
    string $restriccion
): bool {
    $sql = 'SELECT 1
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = ?
            LIMIT 1';
    $stmt = mysqli_prepare($conexion, $sql);

    if (!$stmt) {
        throw new RuntimeException(
            'No se pudo comprobar la restricción ' . $tabla . '.' . $restriccion
            . ': ' . mysqli_error($conexion)
        );
    }

    mysqli_stmt_bind_param($stmt, 'ss', $tabla, $restriccion);

    if (!mysqli_stmt_execute($stmt)) {
        $detalle = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new RuntimeException(
            'No se pudo comprobar la restricción ' . $tabla . '.' . $restriccion
            . ': ' . $detalle
        );
    }

    $resultado = mysqli_stmt_get_result($stmt);
    $existe = $resultado !== false && mysqli_fetch_row($resultado) !== null;
    mysqli_stmt_close($stmt);

    return $existe;
}

/**
 * Añade un índice solamente cuando no existe.
 */
function asegurar_indice_esquema(
    mysqli $conexion,
    string $tabla,
    string $indice,
    string $sql
): void {
    if (!indice_esquema_existe($conexion, $tabla, $indice)) {
        ejecutar_sql_migracion(
            $conexion,
            $sql,
            'No se pudo crear el índice ' . $tabla . '.' . $indice
        );
    }
}

/**
 * Añade una restricción solamente cuando no existe.
 */
function asegurar_restriccion_esquema(
    mysqli $conexion,
    string $tabla,
    string $restriccion,
    string $sql
): void {
    if (!restriccion_esquema_existe($conexion, $tabla, $restriccion)) {
        ejecutar_sql_migracion(
            $conexion,
            $sql,
            'No se pudo crear la restricción ' . $tabla . '.' . $restriccion
        );
    }
}

/**
 * Crea la tabla que registra las migraciones aplicadas.
 */
function asegurar_tabla_migraciones(mysqli $conexion): void {
    ejecutar_sql_migracion(
        $conexion,
        "CREATE TABLE IF NOT EXISTS migraciones_esquema (
            version INT UNSIGNED NOT NULL,
            nombre VARCHAR(150) NOT NULL,
            aplicada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        'No se pudo crear la tabla de control de migraciones'
    );
}

/**
 * Devuelve la última versión registrada sin crear estructuras nuevas.
 */
function obtener_version_esquema(mysqli $conexion): int {
    if (!tabla_esquema_existe($conexion, 'migraciones_esquema')) {
        return 0;
    }

    try {
        $resultado = mysqli_query(
            $conexion,
            'SELECT COALESCE(MAX(version), 0) AS version FROM migraciones_esquema'
        );
    } catch (Throwable $error) {
        throw new RuntimeException('No se pudo consultar la versión del esquema', 0, $error);
    }

    if ($resultado === false) {
        throw new RuntimeException(
            'No se pudo consultar la versión del esquema: ' . mysqli_error($conexion)
        );
    }

    $fila = mysqli_fetch_assoc($resultado);

    return (int) ($fila['version'] ?? 0);
}

/**
 * Impide que dos peticiones ejecuten migraciones simultáneamente.
 */
function adquirir_bloqueo_migraciones(mysqli $conexion): void {
    $nombreBloqueo = MOVIE_APP_MIGRATION_LOCK;
    $esperaSegundos = 10;
    $stmt = mysqli_prepare($conexion, 'SELECT GET_LOCK(?, ?) AS adquirido');

    if (!$stmt) {
        throw new RuntimeException(
            'No se pudo preparar el bloqueo de migraciones: ' . mysqli_error($conexion)
        );
    }

    mysqli_stmt_bind_param($stmt, 'si', $nombreBloqueo, $esperaSegundos);

    if (!mysqli_stmt_execute($stmt)) {
        $detalle = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new RuntimeException('No se pudo solicitar el bloqueo de migraciones: ' . $detalle);
    }

    $resultado = mysqli_stmt_get_result($stmt);
    $fila = $resultado ? mysqli_fetch_assoc($resultado) : null;
    mysqli_stmt_close($stmt);

    if ((int) ($fila['adquirido'] ?? 0) !== 1) {
        throw new RuntimeException('No se pudo obtener el bloqueo de migraciones.');
    }
}

/**
 * Libera el bloqueo de migraciones sin ocultar un error anterior.
 */
function liberar_bloqueo_migraciones(mysqli $conexion): void {
    $nombreBloqueo = MOVIE_APP_MIGRATION_LOCK;

    try {
        $stmt = mysqli_prepare($conexion, 'SELECT RELEASE_LOCK(?)');
        if (!$stmt) {
            throw new RuntimeException(mysqli_error($conexion));
        }

        mysqli_stmt_bind_param($stmt, 's', $nombreBloqueo);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } catch (Throwable $error) {
        error_log('No se pudo liberar el bloqueo de migraciones: ' . $error->getMessage());
    }
}

/**
 * Registra una versión solamente después de aplicar todos sus cambios.
 */
function registrar_version_esquema(
    mysqli $conexion,
    int $version,
    string $nombre
): void {
    $sql = 'INSERT INTO migraciones_esquema (version, nombre) VALUES (?, ?)';
    $stmt = mysqli_prepare($conexion, $sql);

    if (!$stmt) {
        throw new RuntimeException(
            'No se pudo preparar el registro de la migración: ' . mysqli_error($conexion)
        );
    }

    mysqli_stmt_bind_param($stmt, 'is', $version, $nombre);

    if (!mysqli_stmt_execute($stmt)) {
        $detalle = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new RuntimeException('No se pudo registrar la migración: ' . $detalle);
    }

    mysqli_stmt_close($stmt);
}

/**
 * Consolida todas las estructuras que antes se creaban desde páginas normales.
 */
function migrar_esquema_v1(mysqli $conexion): void {
    $tablas = [
        "CREATE TABLE IF NOT EXISTS badges (
            id_badge INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL UNIQUE,
            descripcion VARCHAR(255) NOT NULL,
            requisito_tipo VARCHAR(50) NOT NULL,
            requisito_valor INT NOT NULL,
            icono VARCHAR(100) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        "CREATE TABLE IF NOT EXISTS usuario_badges (
            id_usuario INT NOT NULL,
            id_badge INT NOT NULL,
            fecha_desbloqueo DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_usuario, id_badge),
            CONSTRAINT fk_ub_usuarios FOREIGN KEY (id_usuario)
                REFERENCES usuarios(id_usuario) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_ub_badges FOREIGN KEY (id_badge)
                REFERENCES badges(id_badge) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        "CREATE TABLE IF NOT EXISTS usuario_rachas (
            id_usuario INT PRIMARY KEY,
            fecha_ultimo_login DATE NOT NULL,
            racha_actual INT NOT NULL DEFAULT 1,
            CONSTRAINT fk_ur_usuarios FOREIGN KEY (id_usuario)
                REFERENCES usuarios(id_usuario) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        "CREATE TABLE IF NOT EXISTS usuario_gamificacion_stats (
            id_usuario INT PRIMARY KEY,
            modo_cine_activado TINYINT DEFAULT 0,
            intentos_fallidos_admin TINYINT DEFAULT 0,
            busquedas_fecha_actual TINYINT DEFAULT 0,
            registro_invitacion TINYINT DEFAULT 0,
            CONSTRAINT fk_ugs_usuarios FOREIGN KEY (id_usuario)
                REFERENCES usuarios(id_usuario) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        "CREATE TABLE IF NOT EXISTS usuario_lectura_resenas (
            id_usuario INT NOT NULL,
            id_trailer INT NOT NULL,
            fecha_lectura DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_usuario, id_trailer),
            CONSTRAINT fk_ulr_usuarios FOREIGN KEY (id_usuario)
                REFERENCES usuarios(id_usuario) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_ulr_trailers FOREIGN KEY (id_trailer)
                REFERENCES trailers(id_trailer) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        "CREATE TABLE IF NOT EXISTS listas_personales (
            id_lista INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            id_trailer INT NOT NULL,
            estado VARCHAR(20) NOT NULL,
            fecha_adicion DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_usuario_trailer_lista (id_usuario, id_trailer),
            CONSTRAINT fk_listas_trailers FOREIGN KEY (id_trailer)
                REFERENCES trailers(id_trailer) ON DELETE CASCADE,
            CONSTRAINT fk_listas_usuarios FOREIGN KEY (id_usuario)
                REFERENCES usuarios(id_usuario) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        "CREATE TABLE IF NOT EXISTS comentarios_privados (
            id_comentario_privado INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            id_trailer INT NOT NULL,
            comentario TEXT NOT NULL,
            fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_usuario_trailer_comentario (id_usuario, id_trailer),
            CONSTRAINT fk_comentarios_priv_trailers FOREIGN KEY (id_trailer)
                REFERENCES trailers(id_trailer) ON DELETE CASCADE,
            CONSTRAINT fk_comentarios_priv_usuarios FOREIGN KEY (id_usuario)
                REFERENCES usuarios(id_usuario) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        "CREATE TABLE IF NOT EXISTS historial_comentarios_privados (
            id_historial INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            id_trailer INT NOT NULL,
            comentario_anterior TEXT NOT NULL,
            fecha_cambio DATETIME DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_historial_trailers FOREIGN KEY (id_trailer)
                REFERENCES trailers(id_trailer) ON DELETE CASCADE,
            CONSTRAINT fk_historial_usuarios FOREIGN KEY (id_usuario)
                REFERENCES usuarios(id_usuario) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        "CREATE TABLE IF NOT EXISTS resenas (
            id_resena INT AUTO_INCREMENT PRIMARY KEY,
            id_trailer INT NOT NULL,
            id_usuario INT NOT NULL,
            valoracion DECIMAL(2,1) NOT NULL,
            comentario TEXT DEFAULT NULL,
            fecha_alta DATETIME DEFAULT CURRENT_TIMESTAMP,
            estado VARCHAR(20) NOT NULL DEFAULT 'aprobada',
            UNIQUE KEY uq_trailer_usuario (id_trailer, id_usuario),
            CONSTRAINT fk_resenas_trailers FOREIGN KEY (id_trailer)
                REFERENCES trailers(id_trailer) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_resenas_usuarios FOREIGN KEY (id_usuario)
                REFERENCES usuarios(id_usuario) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        "CREATE TABLE IF NOT EXISTS intentos_login (
            clave_intento CHAR(64) PRIMARY KEY,
            intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
            inicio_ventana BIGINT UNSIGNED NOT NULL,
            bloqueado_hasta BIGINT UNSIGNED DEFAULT NULL,
            actualizado_en BIGINT UNSIGNED NOT NULL,
            INDEX idx_intentos_login_actualizado (actualizado_en)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    ];

    foreach ($tablas as $sqlTabla) {
        ejecutar_sql_migracion(
            $conexion,
            $sqlTabla,
            'No se pudo consolidar una tabla de la aplicación'
        );
    }

    if (obtener_columna_esquema($conexion, 'usuarios', 'avatar_url') === null) {
        ejecutar_sql_migracion(
            $conexion,
            'ALTER TABLE usuarios ADD COLUMN avatar_url VARCHAR(255) DEFAULT NULL',
            'No se pudo añadir usuarios.avatar_url'
        );
    }

    if (obtener_columna_esquema($conexion, 'favoritos', 'fecha_adicion') === null) {
        ejecutar_sql_migracion(
            $conexion,
            'ALTER TABLE favoritos ADD COLUMN fecha_adicion DATETIME DEFAULT CURRENT_TIMESTAMP',
            'No se pudo añadir favoritos.fecha_adicion'
        );
    }

    $valoracion = obtener_columna_esquema($conexion, 'resenas', 'valoracion');
    if ($valoracion === null) {
        ejecutar_sql_migracion(
            $conexion,
            'ALTER TABLE resenas ADD COLUMN valoracion DECIMAL(2,1) NOT NULL DEFAULT 0.0',
            'No se pudo añadir resenas.valoracion'
        );
        $valoracion = ['COLUMN_TYPE' => 'decimal(2,1)'];
    }

    if (strtolower((string) ($valoracion['COLUMN_TYPE'] ?? '')) !== 'decimal(2,1)') {
        ejecutar_sql_migracion(
            $conexion,
            'ALTER TABLE resenas MODIFY COLUMN valoracion DECIMAL(2,1) NOT NULL',
            'No se pudo normalizar resenas.valoracion'
        );
    }

    if (obtener_columna_esquema($conexion, 'resenas', 'estado') === null) {
        ejecutar_sql_migracion(
            $conexion,
            "ALTER TABLE resenas
             ADD COLUMN estado VARCHAR(20) NOT NULL DEFAULT 'aprobada'",
            'No se pudo añadir resenas.estado'
        );
    }

    $indices = [
        ['badges', 'nombre', 'ALTER TABLE badges ADD UNIQUE KEY nombre (nombre)'],
        [
            'listas_personales',
            'uq_usuario_trailer_lista',
            'ALTER TABLE listas_personales
             ADD UNIQUE KEY uq_usuario_trailer_lista (id_usuario, id_trailer)',
        ],
        [
            'comentarios_privados',
            'uq_usuario_trailer_comentario',
            'ALTER TABLE comentarios_privados
             ADD UNIQUE KEY uq_usuario_trailer_comentario (id_usuario, id_trailer)',
        ],
        [
            'resenas',
            'uq_trailer_usuario',
            'ALTER TABLE resenas ADD UNIQUE KEY uq_trailer_usuario (id_trailer, id_usuario)',
        ],
        [
            'intentos_login',
            'idx_intentos_login_actualizado',
            'ALTER TABLE intentos_login
             ADD INDEX idx_intentos_login_actualizado (actualizado_en)',
        ],
    ];

    foreach ($indices as [$tabla, $indice, $sqlIndice]) {
        asegurar_indice_esquema($conexion, $tabla, $indice, $sqlIndice);
    }

    $restricciones = [
        [
            'usuario_badges',
            'fk_ub_usuarios',
            'ALTER TABLE usuario_badges ADD CONSTRAINT fk_ub_usuarios
             FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
             ON DELETE CASCADE ON UPDATE CASCADE',
        ],
        [
            'usuario_badges',
            'fk_ub_badges',
            'ALTER TABLE usuario_badges ADD CONSTRAINT fk_ub_badges
             FOREIGN KEY (id_badge) REFERENCES badges(id_badge)
             ON DELETE CASCADE ON UPDATE CASCADE',
        ],
        [
            'usuario_rachas',
            'fk_ur_usuarios',
            'ALTER TABLE usuario_rachas ADD CONSTRAINT fk_ur_usuarios
             FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
             ON DELETE CASCADE ON UPDATE CASCADE',
        ],
        [
            'usuario_gamificacion_stats',
            'fk_ugs_usuarios',
            'ALTER TABLE usuario_gamificacion_stats ADD CONSTRAINT fk_ugs_usuarios
             FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
             ON DELETE CASCADE ON UPDATE CASCADE',
        ],
        [
            'usuario_lectura_resenas',
            'fk_ulr_usuarios',
            'ALTER TABLE usuario_lectura_resenas ADD CONSTRAINT fk_ulr_usuarios
             FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
             ON DELETE CASCADE ON UPDATE CASCADE',
        ],
        [
            'usuario_lectura_resenas',
            'fk_ulr_trailers',
            'ALTER TABLE usuario_lectura_resenas ADD CONSTRAINT fk_ulr_trailers
             FOREIGN KEY (id_trailer) REFERENCES trailers(id_trailer)
             ON DELETE CASCADE ON UPDATE CASCADE',
        ],
        [
            'listas_personales',
            'fk_listas_trailers',
            'ALTER TABLE listas_personales ADD CONSTRAINT fk_listas_trailers
             FOREIGN KEY (id_trailer) REFERENCES trailers(id_trailer) ON DELETE CASCADE',
        ],
        [
            'listas_personales',
            'fk_listas_usuarios',
            'ALTER TABLE listas_personales ADD CONSTRAINT fk_listas_usuarios
             FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE',
        ],
        [
            'comentarios_privados',
            'fk_comentarios_priv_trailers',
            'ALTER TABLE comentarios_privados ADD CONSTRAINT fk_comentarios_priv_trailers
             FOREIGN KEY (id_trailer) REFERENCES trailers(id_trailer) ON DELETE CASCADE',
        ],
        [
            'comentarios_privados',
            'fk_comentarios_priv_usuarios',
            'ALTER TABLE comentarios_privados ADD CONSTRAINT fk_comentarios_priv_usuarios
             FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE',
        ],
        [
            'historial_comentarios_privados',
            'fk_historial_trailers',
            'ALTER TABLE historial_comentarios_privados ADD CONSTRAINT fk_historial_trailers
             FOREIGN KEY (id_trailer) REFERENCES trailers(id_trailer) ON DELETE CASCADE',
        ],
        [
            'historial_comentarios_privados',
            'fk_historial_usuarios',
            'ALTER TABLE historial_comentarios_privados ADD CONSTRAINT fk_historial_usuarios
             FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE',
        ],
        [
            'resenas',
            'fk_resenas_trailers',
            'ALTER TABLE resenas ADD CONSTRAINT fk_resenas_trailers
             FOREIGN KEY (id_trailer) REFERENCES trailers(id_trailer)
             ON DELETE CASCADE ON UPDATE CASCADE',
        ],
        [
            'resenas',
            'fk_resenas_usuarios',
            'ALTER TABLE resenas ADD CONSTRAINT fk_resenas_usuarios
             FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
             ON DELETE CASCADE ON UPDATE CASCADE',
        ],
    ];

    foreach ($restricciones as [$tabla, $restriccion, $sqlRestriccion]) {
        asegurar_restriccion_esquema(
            $conexion,
            $tabla,
            $restriccion,
            $sqlRestriccion
        );
    }
}

/**
 * Añade índices compuestos para las consultas frecuentes de lectura.
 */
function migrar_esquema_v2(mysqli $conexion): void {
    $indices = [
        [
            'visualizaciones',
            'idx_visualizaciones_usuario_fecha',
            'ALTER TABLE visualizaciones
             ADD INDEX idx_visualizaciones_usuario_fecha (id_usuario, fecha_visualizacion)',
        ],
        [
            'visualizaciones',
            'idx_visualizaciones_usuario_trailer_fecha',
            'ALTER TABLE visualizaciones
             ADD INDEX idx_visualizaciones_usuario_trailer_fecha (id_usuario, id_trailer, fecha_visualizacion)',
        ],
        [
            'favoritos',
            'idx_favoritos_usuario_fecha',
            'ALTER TABLE favoritos
             ADD INDEX idx_favoritos_usuario_fecha (id_usuario, fecha_adicion)',
        ],
        [
            'listas_personales',
            'idx_listas_usuario_fecha',
            'ALTER TABLE listas_personales
             ADD INDEX idx_listas_usuario_fecha (id_usuario, fecha_adicion)',
        ],
        [
            'comentarios_privados',
            'idx_comentarios_privados_usuario_id',
            'ALTER TABLE comentarios_privados
             ADD INDEX idx_comentarios_privados_usuario_id (id_usuario, id_comentario_privado)',
        ],
        [
            'historial_comentarios_privados',
            'idx_historial_usuario_trailer_fecha',
            'ALTER TABLE historial_comentarios_privados
             ADD INDEX idx_historial_usuario_trailer_fecha (id_usuario, id_trailer, fecha_cambio)',
        ],
        [
            'resenas',
            'idx_resenas_trailer_fecha',
            'ALTER TABLE resenas
             ADD INDEX idx_resenas_trailer_fecha (id_trailer, fecha_alta)',
        ],
        [
            'resenas',
            'idx_resenas_usuario_fecha',
            'ALTER TABLE resenas
             ADD INDEX idx_resenas_usuario_fecha (id_usuario, fecha_alta)',
        ],
        [
            'trailers',
            'idx_trailers_release_id',
            'ALTER TABLE trailers
             ADD INDEX idx_trailers_release_id (release_date, id_trailer)',
        ],
    ];

    foreach ($indices as [$tabla, $indice, $sqlIndice]) {
        asegurar_indice_esquema($conexion, $tabla, $indice, $sqlIndice);
    }
}

/**
 * Ejecuta, en orden, las migraciones pendientes del proyecto.
 */
function ejecutar_migraciones(mysqli $conexion): void {
    $versionSesion = (int) ($_SESSION['movie_app_schema_version'] ?? 0);
    if ($versionSesion >= MOVIE_APP_SCHEMA_VERSION) {
        return;
    }

    $versionInstalada = obtener_version_esquema($conexion);
    if ($versionInstalada >= MOVIE_APP_SCHEMA_VERSION) {
        $_SESSION['movie_app_schema_version'] = $versionInstalada;
        return;
    }

    adquirir_bloqueo_migraciones($conexion);

    try {
        asegurar_tabla_migraciones($conexion);
        $versionInstalada = obtener_version_esquema($conexion);

        $migraciones = [
            1 => [
                'nombre' => 'Estructura base consolidada',
                'ejecutar' => 'migrar_esquema_v1',
            ],
            2 => [
                'nombre' => 'Indices de rendimiento',
                'ejecutar' => 'migrar_esquema_v2',
            ],
        ];

        foreach ($migraciones as $version => $migracion) {
            if ($version <= $versionInstalada) {
                continue;
            }

            $ejecutar = $migracion['ejecutar'];
            if (!is_callable($ejecutar)) {
                throw new RuntimeException('La migración ' . $version . ' no es ejecutable.');
            }

            $ejecutar($conexion);
            registrar_version_esquema($conexion, $version, $migracion['nombre']);
            $versionInstalada = $version;
        }

        $_SESSION['movie_app_schema_version'] = $versionInstalada;
    } finally {
        liberar_bloqueo_migraciones($conexion);
    }
}
