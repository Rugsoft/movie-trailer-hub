<?php
if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde la línea de comandos (CLI).\n");
}

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/tmdb_import_helper.php";

$name = $argv[1] ?? null;
$type = $argv[2] ?? null; // 'actor' o 'director'
$limit = isset($argv[3]) ? (int)$argv[3] : 10;

if (!$name) {
    echo "Uso: php import_by_person.php \"Nombre Persona\" [actor|director] [limite]\n";
    echo "Ejemplo: php import_by_person.php \"Keanu Reeves\" actor 5\n";
    echo "Ejemplo: php import_by_person.php \"Christopher Nolan\" director 5\n";
    exit(1);
}

if (!defined('TMDB_API_KEY') || TMDB_API_KEY === 'TU_API_KEY_AQUI' || empty(TMDB_API_KEY)) {
    echo "Error: La clave API de TMDB no está configurada.\n";
    exit(1);
}

// 1. Buscar la persona en TMDB
$urlSearch = "https://api.themoviedb.org/3/search/person?api_key=" . TMDB_API_KEY . "&language=" . TMDB_API_LANG . "&query=" . urlencode($name);
$searchData = makeTmdbRequest($urlSearch);

if (!$searchData || empty($searchData['results'])) {
    echo "No se encontró ninguna persona con el nombre '$name' en TMDB.\n";
    exit(1);
}

// Tomamos el primer resultado más relevante
$person = $searchData['results'][0];
$personId = $person['id'];
$fullName = $person['name'];
$knownFor = $person['known_for_department'];

echo "Encontrado en TMDB: $fullName (ID: $personId, Departamento: $knownFor)\n";

// Determinar el tipo si no se especificó o es inválido
if (!$type || !in_array(strtolower($type), ['actor', 'director'])) {
    if ($knownFor === 'Directing') {
        $type = 'director';
    } else {
        $type = 'actor';
    }
    echo "No se especificó rol (actor/director) o no es válido. Asumiendo '$type' basado en TMDB.\n";
} else {
    $type = strtolower($type);
}

// 2. Obtener créditos de películas
$urlCredits = "https://api.themoviedb.org/3/person/$personId/movie_credits?api_key=" . TMDB_API_KEY . "&language=" . TMDB_API_LANG;
$creditsData = makeTmdbRequest($urlCredits);

if (!$creditsData) {
    echo "No se pudieron obtener los créditos de películas para $fullName.\n";
    exit(1);
}

$moviesToImport = [];
if ($type === 'director') {
    if (!empty($creditsData['crew'])) {
        foreach ($creditsData['crew'] as $crewMember) {
            if ($crewMember['job'] === 'Director') {
                $moviesToImport[] = $crewMember;
            }
        }
    }
} else { // actor
    if (!empty($creditsData['cast'])) {
        $moviesToImport = $creditsData['cast'];
    }
}

if (empty($moviesToImport)) {
    echo "No se encontraron películas para $fullName con el rol '$type'.\n";
    exit(0);
}

// Ordenar las películas por popularidad descendente para importar las mejores/más conocidas primero
usort($moviesToImport, function($a, $b) {
    return ($b['popularity'] ?? 0) <=> ($a['popularity'] ?? 0);
});

// Limitar cantidad
$moviesToImport = array_slice($moviesToImport, 0, $limit);

echo "Procediendo a importar hasta $limit películas donde participa $fullName como $type:\n\n";

$successCount = 0;
$skippedCount = 0;

foreach ($moviesToImport as $index => $movie) {
    $num = $index + 1;
    $movieId = $movie['id'];
    $movieTitle = $movie['title'];
    $charInfo = ($type === 'actor' && !empty($movie['character'])) ? " (como '{$movie['character']}')" : "";
    
    echo "[$num/" . count($moviesToImport) . "] Importando '$movieTitle'$charInfo (ID: $movieId)...\n";
    
    try {
        $res = importMovieById($conexion, $movieId);
        echo "  -> ¡Éxito! Película añadida.\n";
        echo "     • Director: {$res['director']} ({$res['director_status']})\n";
        echo "     • Actores asociados: {$res['actores_count']}\n";
        echo "     • Géneros asociados: {$res['generos_count']}\n";
        $successCount++;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        if (strpos($msg, "ya existe") !== false) {
            echo "  -> Omitido: ya está en el catálogo.\n";
            $skippedCount++;
        } else {
            echo "  -> ERROR: " . $msg . "\n";
        }
    }
    echo "\n";
}

echo "=========================================\n";
echo "Importación completada para $fullName:\n";
echo "- Agregados con éxito: $successCount\n";
echo "- Omitidos/Ya existentes: $skippedCount\n";
echo "=========================================\n";

mysqli_close($conexion);
