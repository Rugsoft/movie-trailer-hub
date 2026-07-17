<?php

declare(strict_types=1);

function cargar_secretos_locales(): array
{
    static $secretos = null;

    if (is_array($secretos)) {
        return $secretos;
    }

    $secretos = [];
    $rutaLocal = __DIR__ . '/secretos.local.php';

    if (is_file($rutaLocal)) {
        $contenido = require $rutaLocal;

        if (is_array($contenido)) {
            $secretos = $contenido;
        }
    }

    return $secretos;
}

function obtener_secreto(
    string $variableEntorno,
    string $claveLocal,
    string $valorPredeterminado = ''
): string {
    $valorEntorno = getenv($variableEntorno);

    if (is_string($valorEntorno) && $valorEntorno !== '') {
        return $valorEntorno;
    }

    $secretos = cargar_secretos_locales();
    $valorLocal = $secretos[$claveLocal] ?? $valorPredeterminado;

    return is_scalar($valorLocal)
        ? (string) $valorLocal
        : $valorPredeterminado;
}
