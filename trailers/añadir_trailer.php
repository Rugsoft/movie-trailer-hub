<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Nuevo Trailer</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>

<body>
<?php
require_once "../config/conexion.php";
define('BASE_PATH', '../');
?>
    <h1>Añadir Nuevo Trailer</h1>
    <p>Formulario para registrar una nueva película y su trailer en la base de datos.</p>

    <form action="procesar_trailer.php" method="POST">
        <label for="titulo">Título de la Película *</label>
        <input type="text" id="titulo" name="titulo" required placeholder="Ej: Interstellar">

        <label for="director">Director:</label>
        <input type="text" id="director" name="director" placeholder="Ej: Christopher Nolan">

        <label for="release_date">Fecha de Estreno *</label>
        <input type="date" id="release_date" name="release_date" required>

        <label for="genero">Género *</label>
        <input type="text" id="genero" name="genero" required placeholder="Ej: Sci-Fi, Acción, Drama...">

        <label for="duracion">Duración (minutos) *</label>
        <input type="number" id="duracion" name="duracion" required min="1" placeholder="Ej: 169">

        <label for="trailer_url">URL del Trailer *</label>
        <input type="url" id="trailer_url" name="trailer_url" required placeholder="Ej: https://www.youtube.com/watch?v=...">

        <label for="poster_url">URL de la Portada (Poster):</label>
        <input type="url" id="poster_url" name="poster_url" placeholder="Ej: https://enlace-imagen.jpg">

        <label for="valoracion">Valoración (0 a 10) *</label>
        <input type="number" id="valoracion" name="valoracion" required step="0.1" min="0" max="10" placeholder="Ej: 8.7">

        <label for="sinopsis">Sinopsis / Descripción:</label>
        <textarea id="sinopsis" name="sinopsis" rows="4" placeholder="Escribe un breve resumen de la película..."></textarea>

        <button type="submit">Añadir Trailer</button>
    </form>

    <a class="volver" href="../index.php">← Volver al inicio</a>

</body>

</html>
