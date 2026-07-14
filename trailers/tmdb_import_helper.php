<?php
require_once __DIR__ . "/../config/tmdb_config.php";

/**
 * Helper para hacer peticiones a TMDB
 */
function makeTmdbRequest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return null;
    }
    return json_decode($response, true);
}

/**
 * Divide el nombre completo de una persona en nombre y apellidos
 */
function splitName($fullName) {
    $fullName = trim($fullName);
    $parts = explode(' ', $fullName);
    if (count($parts) > 1) {
        $nombre = $parts[0];
        $apellidos = implode(' ', array_slice($parts, 1));
    } else {
        $nombre = $fullName;
        $apellidos = '';
    }
    return [$nombre, $apellidos];
}

/**
 * Calcula la edad a partir de la fecha de nacimiento
 */
function calculateAge($birthday) {
    if (empty($birthday)) return null;
    try {
        $birthDate = new DateTime($birthday);
        $today = new DateTime();
        return $today->diff($birthDate)->y;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Traduce o devuelve el país
 */
function extractCountry($placeOfBirth) {
    if (empty($placeOfBirth)) return null;
    $parts = explode(',', $placeOfBirth);
    $rawCountry = trim(end($parts));
    
    $translations = [
        'USA' => 'Estados Unidos',
        'United States' => 'Estados Unidos',
        'UK' => 'Reino Unido',
        'United Kingdom' => 'Reino Unido',
        'Spain' => 'España',
        'France' => 'Francia',
        'Germany' => 'Alemania',
        'Italy' => 'Italia',
        'Canada' => 'Canadá',
        'Japan' => 'Japón',
        'China' => 'China',
        'Mexico' => 'México',
        'Australia' => 'Australia',
        'Sweden' => 'Suecia',
        'Ireland' => 'Irlanda'
    ];
    return $translations[$rawCountry] ?? $rawCountry;
}

/**
 * Importa una película completa desde TMDB y la guarda en la base de datos
 */
function importMovieById($conexion, $id) {
    if (!defined('TMDB_API_KEY') || TMDB_API_KEY === 'TU_API_KEY_AQUI' || empty(TMDB_API_KEY)) {
        throw new Exception("La clave API de TMDB no está configurada.");
    }

    $urlMovie = "https://api.themoviedb.org/3/movie/$id?api_key=" . TMDB_API_KEY . "&language=" . TMDB_API_LANG . "&append_to_response=videos,credits";
    $movieData = makeTmdbRequest($urlMovie);

    if (!$movieData) {
        throw new Exception("No se pudo obtener información detallada de la película desde TMDB.");
    }

    $titulo = $movieData['title'] ?? '';
    $release_date = $movieData['release_date'] ?? '';

    if ($titulo === '' || $release_date === '') {
        throw new Exception("La película seleccionada carece de título o fecha de estreno obligatorios.");
    }

    // Verificar duplicado en catálogo
    $sqlCheck = "SELECT id_trailer FROM trailers WHERE titulo = ? AND release_date = ? LIMIT 1";
    $stmtCheck = mysqli_prepare($conexion, $sqlCheck);
    mysqli_stmt_bind_param($stmtCheck, "ss", $titulo, $release_date);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    if (mysqli_num_rows($resCheck) > 0) {
        mysqli_stmt_close($stmtCheck);
        throw new Exception("La película '$titulo' ($release_date) ya existe en tu catálogo local.");
    }
    mysqli_stmt_close($stmtCheck);

    // Extraer campos principales
    $duracion = isset($movieData['runtime']) ? (int)$movieData['runtime'] : 120;
    if ($duracion <= 0) $duracion = 120;

    $sinopsis = $movieData['overview'] ?? '';
    $valoracion = isset($movieData['vote_average']) ? (float)$movieData['vote_average'] : 0.0;
    $poster_url = $movieData['poster_path'] ? "https://image.tmdb.org/t/p/w500" . $movieData['poster_path'] : "https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=500";

    // Resolver Trailer URL
    $trailer_url = "";
    if (!empty($movieData['videos']['results'])) {
        foreach ($movieData['videos']['results'] as $video) {
            if ($video['site'] === 'YouTube' && ($video['type'] === 'Trailer' || $video['type'] === 'Teaser')) {
                $trailer_url = "https://www.youtube.com/watch?v=" . $video['key'];
                break;
            }
        }
    }
    if (empty($trailer_url) && !empty($movieData['videos']['results'])) {
        foreach ($movieData['videos']['results'] as $video) {
            if ($video['site'] === 'YouTube') {
                $trailer_url = "https://www.youtube.com/watch?v=" . $video['key'];
                break;
            }
        }
    }
    if (empty($trailer_url)) {
        $trailer_url = "https://www.youtube.com/results?search_query=" . urlencode($titulo . " trailer oficial");
    }

    // Iniciar transacción
    mysqli_begin_transaction($conexion);
    $director_status = "No especificado";
    $nuevo_director_creado = false;
    $nuevos_actores_creados = [];
    $actores_asociados_count = 0;
    $generos_asociados_count = 0;

    try {
        // Procesar Director
        $id_director = null;
        $director_name = "";
        if (!empty($movieData['credits']['crew'])) {
            $directorData = null;
            foreach ($movieData['credits']['crew'] as $crewMember) {
                if ($crewMember['job'] === 'Director') {
                    $directorData = $crewMember;
                    break;
                }
            }
            
            if ($directorData) {
                $director_name = $directorData['name'];
                list($dNombre, $dApellidos) = splitName($director_name);
                
                // Buscar si ya existe localmente
                $sqlD = "SELECT id_director FROM directores WHERE nombre = ? AND apellidos = ? LIMIT 1";
                $stmtD = mysqli_prepare($conexion, $sqlD);
                mysqli_stmt_bind_param($stmtD, "ss", $dNombre, $dApellidos);
                mysqli_stmt_execute($stmtD);
                $resD = mysqli_stmt_get_result($stmtD);
                
                if ($rowD = mysqli_fetch_assoc($resD)) {
                    $id_director = $rowD['id_director'];
                    $director_status = "Asociado (Ya existía)";
                } else {
                    // Descargar datos adicionales de la persona
                    $urlPerson = "https://api.themoviedb.org/3/person/{$directorData['id']}?api_key=" . TMDB_API_KEY . "&language=" . TMDB_API_LANG;
                    $personDetails = makeTmdbRequest($urlPerson);
                    
                    $dEdad = $personDetails ? calculateAge($personDetails['birthday']) : null;
                    $dPais = $personDetails ? extractCountry($personDetails['place_of_birth']) : null;
                    
                    $sqlCreateD = "INSERT INTO directores (nombre, apellidos, edad, pais) VALUES (?, ?, ?, ?)";
                    $stmtCreateD = mysqli_prepare($conexion, $sqlCreateD);
                    mysqli_stmt_bind_param($stmtCreateD, "ssis", $dNombre, $dApellidos, $dEdad, $dPais);
                    mysqli_stmt_execute($stmtCreateD);
                    $id_director = mysqli_insert_id($conexion);
                    mysqli_stmt_close($stmtCreateD);
                    
                    $director_status = "Creado automáticamente";
                    $nuevo_director_creado = true;
                }
                mysqli_stmt_close($stmtD);
            }
        }

        // Insertar el Trailer
        $sqlInsertTrailer = "INSERT INTO trailers (titulo, id_director, release_date, duracion, trailer_url, poster_url, valoracion, sinopsis) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtInsertTrailer = mysqli_prepare($conexion, $sqlInsertTrailer);
        mysqli_stmt_bind_param($stmtInsertTrailer, "sisissds", $titulo, $id_director, $release_date, $duracion, $trailer_url, $poster_url, $valoracion, $sinopsis);
        mysqli_stmt_execute($stmtInsertTrailer);
        $id_trailer = mysqli_insert_id($conexion);
        mysqli_stmt_close($stmtInsertTrailer);

        // Procesar Géneros
        if (!empty($movieData['genres'])) {
            foreach ($movieData['genres'] as $gen) {
                $genName = trim($gen['name']);
                
                $sqlG = "SELECT id_genero FROM generos WHERE nombre = ? LIMIT 1";
                $stmtG = mysqli_prepare($conexion, $sqlG);
                mysqli_stmt_bind_param($stmtG, "s", $genName);
                mysqli_stmt_execute($stmtG);
                $resG = mysqli_stmt_get_result($stmtG);
                
                if ($rowG = mysqli_fetch_assoc($resG)) {
                    $id_genero = $rowG['id_genero'];
                } else {
                    $sqlCreateG = "INSERT INTO generos (nombre) VALUES (?)";
                    $stmtCreateG = mysqli_prepare($conexion, $sqlCreateG);
                    mysqli_stmt_bind_param($stmtCreateG, "s", $genName);
                    mysqli_stmt_execute($stmtCreateG);
                    $id_genero = mysqli_insert_id($conexion);
                    mysqli_stmt_close($stmtCreateG);
                }
                mysqli_stmt_close($stmtG);

                $sqlAssocG = "INSERT INTO trailers_generos (id_trailer, id_genero) VALUES (?, ?)";
                $stmtAssocG = mysqli_prepare($conexion, $sqlAssocG);
                mysqli_stmt_bind_param($stmtAssocG, "ii", $id_trailer, $id_genero);
                mysqli_stmt_execute($stmtAssocG);
                mysqli_stmt_close($stmtAssocG);
                $generos_asociados_count++;
            }
        }

        // Procesar Reparto (Top 5 actores)
        if (!empty($movieData['credits']['cast'])) {
            $topCast = array_slice($movieData['credits']['cast'], 0, 5);
            foreach ($topCast as $castMember) {
                $actorName = $castMember['name'];
                $personaje = trim($castMember['character'] ?? '');
                list($aNombre, $aApellidos) = splitName($actorName);
                
                $sqlA = "SELECT id_reparto FROM reparto WHERE nombre = ? AND apellidos = ? LIMIT 1";
                $stmtA = mysqli_prepare($conexion, $sqlA);
                mysqli_stmt_bind_param($stmtA, "ss", $aNombre, $aApellidos);
                mysqli_stmt_execute($stmtA);
                $resA = mysqli_stmt_get_result($stmtA);
                
                if ($rowA = mysqli_fetch_assoc($resA)) {
                    $id_reparto = $rowA['id_reparto'];
                } else {
                    $urlActor = "https://api.themoviedb.org/3/person/{$castMember['id']}?api_key=" . TMDB_API_KEY . "&language=" . TMDB_API_LANG;
                    $actorDetails = makeTmdbRequest($urlActor);
                    
                    $aEdad = $actorDetails ? calculateAge($actorDetails['birthday']) : null;
                    $aPais = $actorDetails ? extractCountry($actorDetails['place_of_birth']) : null;
                    $aFoto = $castMember['profile_path'] ? "https://image.tmdb.org/t/p/h632" . $castMember['profile_path'] : "https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200";
                    
                    $sqlCreateA = "INSERT INTO reparto (nombre, apellidos, edad, pais, foto_url) VALUES (?, ?, ?, ?, ?)";
                    $stmtCreateA = mysqli_prepare($conexion, $sqlCreateA);
                    mysqli_stmt_bind_param($stmtCreateA, "ssiss", $aNombre, $aApellidos, $aEdad, $aPais, $aFoto);
                    mysqli_stmt_execute($stmtCreateA);
                    $id_reparto = mysqli_insert_id($conexion);
                    mysqli_stmt_close($stmtCreateA);
                    
                    $nuevos_actores_creados[] = $actorName;
                }
                mysqli_stmt_close($stmtA);

                $sqlAssocA = "INSERT INTO reparto_trailers (id_trailer, id_reparto, personaje) VALUES (?, ?, ?)";
                $stmtAssocA = mysqli_prepare($conexion, $sqlAssocA);
                mysqli_stmt_bind_param($stmtAssocA, "iis", $id_trailer, $id_reparto, $personaje);
                mysqli_stmt_execute($stmtAssocA);
                mysqli_stmt_close($stmtAssocA);
                $actores_asociados_count++;
            }
        }

        mysqli_commit($conexion);
        return [
            "success" => true,
            "titulo" => $titulo,
            "director" => $director_name ?: "No asignado",
            "director_status" => $director_status,
            "nuevo_director_creado" => $nuevo_director_creado,
            "actores_count" => $actores_asociados_count,
            "nuevos_actores_creados" => $nuevos_actores_creados,
            "generos_count" => $generos_asociados_count
        ];
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        throw $e;
    }
}
