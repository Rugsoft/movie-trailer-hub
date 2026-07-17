<?php
require_once __DIR__ . '/secretos_loader.php';

// Configuración de la API de TMDB
if (!defined('TMDB_API_KEY')) {
    define(
        'TMDB_API_KEY',
        obtener_secreto('TMDB_API_KEY', 'tmdb_api_key')
    );
}
if (!defined('TMDB_API_LANG')) {
    define('TMDB_API_LANG', 'es-ES'); // Idioma para los textos y sinopsis
}
