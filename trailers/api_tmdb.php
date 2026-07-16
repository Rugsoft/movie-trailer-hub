<?php
require_once "../config/conexion.php";
require_once "../config/tmdb_config.php";

// Restringir el acceso a administradores o editores autenticados
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'editor')) {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado. Se requieren permisos de administrador o editor."]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

if (!defined('TMDB_API_KEY') || TMDB_API_KEY === 'TU_API_KEY_AQUI' || empty(TMDB_API_KEY)) {
    echo json_encode(["error" => "La clave API de TMDB no está configurada. Edita el archivo config/tmdb_config.php."]);
    exit;
}

/**
 * Realiza una petición cURL a TMDB y retorna la respuesta decodificada
 */
function makeTmdbRequest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Evitar problemas de certificados SSL en entornos locales (como XAMPP)
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ["error" => "Error de la API de TMDB (Código de estado HTTP: $httpCode)"];
    }
    
    return json_decode($response, true);
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
