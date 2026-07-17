<?php
require_once "../config/conexion.php";
require_once "../config/tmdb_config.php";

require_once __DIR__ . "/../includes/seguridad.php";
require_admin_or_editor(null, true);

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

if (!defined('TMDB_API_KEY') || TMDB_API_KEY === 'TU_API_KEY_AQUI' || empty(TMDB_API_KEY)) {
    echo json_encode(["error" => "La clave API de TMDB no está configurada. Edita el archivo config/tmdb_config.php."]);
    exit;
}

/**
 * Realiza una petición cURL a TMDB y retorna la respuesta decodificada
 */
function makeTmdbRequest(string $url): array {
    $ch = curl_init($url);

    if ($ch === false) {
        registrar_error_interno('No se pudo inicializar cURL para TMDB');
        http_response_code(502);
        return ['error' => 'No se pudo conectar con el servicio de películas.'];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);

    $response = curl_exec($ch);
    $curlErrorNumber = curl_errno($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        registrar_error_interno(
            'Error en la conexión segura con TMDB',
            "cURL $curlErrorNumber: $curlError"
        );
        http_response_code(502);
        return ['error' => 'No se pudo conectar con el servicio de películas.'];
    }

    if ($httpCode !== 200) {
        registrar_error_interno(
            'TMDB devolvió un estado HTTP inesperado',
            (string)$httpCode
        );
        http_response_code(502);
        return ['error' => 'El servicio de películas no está disponible temporalmente.'];
    }

    try {
        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        registrar_error_interno('TMDB devolvió una respuesta JSON inválida', $exception);
        http_response_code(502);
        return ['error' => 'El servicio de películas devolvió una respuesta inválida.'];
    }

    if (!is_array($data)) {
        registrar_error_interno('TMDB devolvió una respuesta con formato inesperado');
        http_response_code(502);
        return ['error' => 'El servicio de películas devolvió una respuesta inválida.'];
    }

    return $data;
}

switch ($action) {
    case 'search_movie':
        $query = trim($_GET['query'] ?? '');
        if ($query === '') {
            echo json_encode(["error" => "El término de búsqueda de película está vacío."]);
            exit;
        }
        $url = "https://api.themoviedb.org/3/search/movie?api_key=" . TMDB_API_KEY . "&query=" . urlencode($query) . "&language=" . TMDB_API_LANG;
        $data = makeTmdbRequest($url);
        echo json_encode($data);
        break;

    case 'movie_details':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(["error" => "ID de película inválido."]);
            exit;
        }
        // append_to_response=videos,credits permite traer detalles, trailer de youtube y reparto en una sola llamada
        $url = "https://api.themoviedb.org/3/movie/$id?api_key=" . TMDB_API_KEY . "&language=" . TMDB_API_LANG . "&append_to_response=videos,credits";
        $data = makeTmdbRequest($url);
        echo json_encode($data);
        break;

    case 'search_person':
        $query = trim($_GET['query'] ?? '');
        if ($query === '') {
            echo json_encode(["error" => "El término de búsqueda de persona está vacío."]);
            exit;
        }
        $url = "https://api.themoviedb.org/3/search/person?api_key=" . TMDB_API_KEY . "&query=" . urlencode($query) . "&language=" . TMDB_API_LANG;
        $data = makeTmdbRequest($url);
        echo json_encode($data);
        break;

    case 'person_details':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(["error" => "ID de persona inválido."]);
            exit;
        }
        $url = "https://api.themoviedb.org/3/person/$id?api_key=" . TMDB_API_KEY . "&language=" . TMDB_API_LANG;
        $data = makeTmdbRequest($url);
        echo json_encode($data);
        break;

    default:
        echo json_encode(["error" => "Acción no válida."]);
        break;
}
