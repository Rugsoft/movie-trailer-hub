<?php
if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde la línea de comandos (CLI).\n");
}

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/tmdb_import_helper.php";

$monthYear = $argv[1] ?? null; // Formato: YYYY-MM
$limit = isset($argv[2]) ? (int)$argv[2] : 10; // Límite de películas a importar

if (!$monthYear || !preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
    echo "Uso: php import_by_date.php YYYY-MM [limite]\n";
    echo "Ejemplo: php import_by_date.php 2025-01 10\n";
    exit(1);
}

list($year, $month) = explode('-', $monthYear);
$startDate = "$year-$month-01";
$endDate = date("Y-m-t", strtotime($startDate));

echo "Buscando películas populares estrenadas entre $startDate y $endDate...\n";

if (!defined('TMDB_API_KEY') || TMDB_API_KEY === 'TU_API_KEY_AQUI' || empty(TMDB_API_KEY)) {
    echo "Error: La clave API de TMDB no está configurada.\n";
    exit(1);
}

// Descubrir películas en TMDB
$urlDiscover = "https://api.themoviedb.org/3/discover/movie?api_key=" . TMDB_API_KEY . 
               "&language=" . TMDB_API_LANG . 
               "&primary_release_date.gte=" . $startDate . 
               "&primary_release_date.lte=" . $endDate . 
               "&sort_by=popularity.desc&page=1";

$discoverData = makeTmdbRequest($urlDiscover);

if (!$discoverData || empty($discoverData['results'])) {
    echo "No se encontraron películas para el período especificado.\n";
    exit(0);
}

$movies = array_slice($discoverData['results'], 0, $limit);
echo "Encontradas " . count($discoverData['results']) . " películas. Procediendo a importar las " . count($movies) . " más populares:\n\n";

$successCount = 0;
$skippedCount = 0;

foreach ($movies as $index => $movie) {
    $num = $index + 1;
    $tmdbId = $movie['id'];
    $title = $movie['title'];
    
    echo "[$num/" . count($movies) . "] Importando '$title' (ID: $tmdbId)...\n";
    
    try {
        $res = importMovieById($conexion, $tmdbId);
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
echo "Importación completada:\n";
echo "- Agregados con éxito: $successCount\n";
echo "- Omitidos/Ya existentes: $skippedCount\n";
echo "=========================================\n";

mysqli_close($conexion);
