<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . '/../sessions/runtime';

    if (!is_dir($sessionPath) && !mkdir($sessionPath, 0755, true)) {
        http_response_code(500);
        exit('No se pudo inicializar la sesión.');
    }

    $isHttps = (
        !empty($_SERVER['HTTPS'])
        && strtolower((string)$_SERVER['HTTPS']) !== 'off'
    ) || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_save_path($sessionPath);
    session_name('movie_trailer_session');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    if (!session_start()) {
        http_response_code(500);
        exit('No se pudo inicializar la sesión.');
    }
}
