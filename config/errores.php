<?php

declare(strict_types=1);

require_once __DIR__ . '/secretos_loader.php';

$debugValue = strtolower(
    obtener_secreto('MOVIE_APP_DEBUG', 'app_debug', 'false')
);

$appDebug = in_array($debugValue, ['1', 'true', 'yes', 'on'], true);

error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors', $appDebug ? '1' : '0');
ini_set('display_startup_errors', $appDebug ? '1' : '0');

/**
 * Registra el detalle técnico sin mostrarlo al visitante.
 */
function registrar_error_interno(
    string $contexto,
    string|Throwable|null $detalle = null
): void {
    $mensaje = $contexto;

    if ($detalle instanceof Throwable) {
        $detalle = $detalle->getMessage();
    }

    if (is_string($detalle) && $detalle !== '') {
        $mensaje .= ': ' . $detalle;
    }

    error_log($mensaje);
}

/**
 * Detiene una operación con una respuesta pública genérica.
 */
function abortar_error_interno(
    string $contexto,
    string|Throwable|null $detalle = null,
    bool $esJson = false
): never {
    registrar_error_interno($contexto, $detalle);
    http_response_code(500);

    if ($esJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => 'No se pudo completar la operación.',
        ]);
    } else {
        echo 'No se pudo completar la operación.';
    }

    exit;
}
