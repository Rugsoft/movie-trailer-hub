<?php
$pageTitle = "Importador Automático TMDB";
$showNavbar = false;
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>
<?php
require_once "../config/conexion.php";
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'editor')) {
    $_SESSION['error'] = "Acceso denegado. Se requieren permisos de administrador o editor.";
    header("Location: ../index.php");
    exit;
}
define('BASE_PATH', '../');
?>

<main class="app-container" style="margin-top: 30px; margin-bottom: 50px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="margin-bottom: 8px;"><i class="fa-solid fa-cloud-arrow-down" style="color: var(--primary);"></i> Importador Automático TMDB</h1>
        <p style="color: var(--text-muted); margin: 0;">Busca cualquier película en The Movie Database e impórtala con su trailer, director, reparto y géneros automáticamente.</p>
    </div>

    <!-- Caja de Búsqueda -->
    <div class="tmdb-autocomplete-card" style="max-width: 800px; margin: 0 auto 30px auto; padding: 24px;">
        <h3 style="margin-bottom: 12px;"><i class="fa-solid fa-magnifying-glass"></i> Buscar Película en TMDB</h3>
        <div class="tmdb-search-row" style="display: flex; gap: 12px;">
            <input type="text" id="movieQuery" placeholder="Escribe el nombre de la película (ej. Gladiator II, Avatar...)" style="flex: 1; padding: 12px 16px; font-size: 15px;">
            <button type="button" id="btnSearch" class="btn btn-primary" style="padding: 0 24px; font-size: 15px;"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
        </div>
    </div>

    <!-- Resultados -->
    <div id="resultsGrid" class="recommendations-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; max-width: 1000px; margin: 0 auto;">
        <!-- Se rellenará por JS -->
    </div>

    <!-- Estado de Carga / Logs -->
    <div id="statusModal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; align-items: center; justify-content: center; z-index: 1000; background: rgba(8, 20, 37, 0.9); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">
        <div class="write-review-card" style="width: 90%; max-width: 500px; padding: 30px; border-radius: var(--radius-lg); text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.5); border: 1px solid var(--border-color-focus);">
            <div id="modalSpinner">
                <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 48px; color: var(--primary); margin-bottom: 20px;"></i>
            </div>
            <h3 id="modalTitle" style="margin-bottom: 16px;">Procesando Importación</h3>
            <div id="modalMessage" style="text-align: left; background: var(--bg-surface-lowest); padding: 15px; border-radius: var(--radius-md); font-size: 13px; max-height: 250px; overflow-y: auto; margin-bottom: 20px; line-height: 1.6; border: 1px solid var(--border-color);">
                Iniciando descarga...
            </div>
            <button type="button" id="btnCloseModal" class="btn btn-secondary" style="display: none; margin: 0 auto;">Cerrar</button>
            <div id="modalActions" style="display: none; justify-content: center; gap: 12px; margin-top: 15px;">
                <button type="button" id="btnGoCatalog" class="btn btn-secondary">Ver Catálogo</button>
                <button type="button" id="btnImportAnother" class="btn btn-primary">Añadir otro trailer</button>
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-top: 40px;">
        <a class="volver" href="../index.php" style="display: inline-block; margin-top: 0;">← Volver al inicio</a>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const movieQuery = document.getElementById('movieQuery');
    const btnSearch = document.getElementById('btnSearch');
    const resultsGrid = document.getElementById('resultsGrid');
    
    const statusModal = document.getElementById('statusModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalSpinner = document.getElementById('modalSpinner');
    const btnCloseModal = document.getElementById('btnCloseModal');
    const modalActions = document.getElementById('modalActions');
    const btnGoCatalog = document.getElementById('btnGoCatalog');
    const btnImportAnother = document.getElementById('btnImportAnother');

    movieQuery.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchMovies();
        }
    });

    btnSearch.addEventListener('click', searchMovies);

    function searchMovies() {
        const query = movieQuery.value.trim();
        if (query === '') {
            alert('Por favor, ingresa el título de una película.');
            return;
        }

        resultsGrid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size: 32px; color: var(--primary); margin-bottom: 10px;"></i><p style="color: var(--text-muted);">Buscando películas...</p></div>';

        fetch(`api_tmdb.php?action=search_movie&query=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    resultsGrid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; color: var(--secondary); padding: 30px;"><i class="fa-solid fa-circle-exclamation" style="font-size: 24px; margin-bottom: 10px;"></i><p>${data.error}</p></div>`;
                    return;
                }

                if (!data.results || data.results.length === 0) {
                    resultsGrid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 50px;"><i class="fa-solid fa-magnifying-glass-minus" style="font-size: 32px; margin-bottom: 10px;"></i><p>No se encontraron resultados.</p></div>';
                    return;
                }

                resultsGrid.innerHTML = '';
                data.results.forEach(movie => {
                    const year = movie.release_date ? movie.release_date.substring(0, 4) : 'N/A';
                    const imgUrl = movie.poster_path ? `https://image.tmdb.org/t/p/w342${movie.poster_path}` : 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=342';
                    
                    const card = document.createElement('article');
                    card.className = 'movie-card';
                    card.style.display = 'flex';
                    card.style.flexDirection = 'column';
                    
                    card.innerHTML = `
                        <div class="movie-poster-container" style="aspect-ratio: 2/3;">
                            <img src="${imgUrl}" alt="${movie.title}" class="movie-poster">
                            <div class="rating-badge">
                                <i class="fa-solid fa-star"></i>
                                <span>${movie.vote_average ? movie.vote_average.toFixed(1) : '0.0'}</span>
                            </div>
                        </div>
                        <div class="movie-info" style="display: flex; flex-direction: column; flex-grow: 1; padding: 12px;">
                            <h3 class="movie-title" style="font-size: 14px; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${movie.title}</h3>
                            <div class="movie-meta-row" style="font-size: 11px; margin-bottom: 12px;">
                                <span><i class="fa-regular fa-calendar"></i> ${year}</span>
                            </div>
                            <button type="button" class="btn btn-primary btn-importar" data-id="${movie.id}" style="width: 100%; justify-content: center; font-size: 12px; padding: 8px 0; margin-top: auto;">
                                <i class="fa-solid fa-cloud-arrow-down"></i> Importar Todo
                            </button>
                        </div>
                    `;
                    
                    resultsGrid.appendChild(card);
                });

                // Registrar eventos de los botones de importar
                document.querySelectorAll('.btn-importar').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const id = btn.getAttribute('data-id');
                        importMovie(id);
                    });
                });
            })
            .catch(err => {
                resultsGrid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: var(--secondary); padding: 30px;"><p>Error de conexión con el servidor.</p></div>';
                console.error(err);
            });
    }

    function importMovie(id) {
        statusModal.style.display = 'flex';
        modalTitle.innerText = "Procesando Importación";
        modalMessage.innerHTML = '<div><i class="fa-solid fa-spinner fa-spin"></i> Conectando con TMDB para obtener metadatos...</div>';
        modalSpinner.style.display = 'block';
        btnCloseModal.style.display = 'none';
        modalActions.style.display = 'none';

        fetch('procesar_importar_tmdb.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                modalSpinner.style.display = 'none';
                modalTitle.innerText = "Error de Importación";
                modalMessage.innerHTML = `<div style="color: #ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> ${data.error}</div>`;
                btnCloseModal.innerText = "Cerrar";
                btnCloseModal.style.display = 'block';
                btnCloseModal.onclick = () => { statusModal.style.display = 'none'; };
                return;
            }

            modalSpinner.style.display = 'none';
            modalTitle.innerText = "¡Importación Completada!";
            
            let logHtml = `<div style="color: #10b981; font-weight: bold; margin-bottom: 10px;"><i class="fa-solid fa-circle-check"></i> "${data.titulo}" añadida con éxito.</div>`;
            logHtml += `<div>• Director: <strong>${data.director}</strong> (${data.director_status})</div>`;
            logHtml += `<div>• Reparto importado: <strong>${data.actores_count} actores</strong> asociados.</div>`;
            logHtml += `<div>• Géneros asociados: <strong>${data.generos_count}</strong></div>`;
            
            if (data.nuevo_director_creado) {
                logHtml += `<div style="font-size: 11px; margin-top: 5px; color: var(--primary);"><i class="fa-solid fa-user-plus"></i> Nuevo director registrado localmente: ${data.director}</div>`;
            }
            if (data.nuevos_actores_creados && data.nuevos_actores_creados.length > 0) {
                logHtml += `<div style="font-size: 11px; margin-top: 5px; color: var(--primary);"><i class="fa-solid fa-user-plus"></i> Actores creados localmente: <em>${data.nuevos_actores_creados.join(', ')}</em></div>`;
            }

            modalMessage.innerHTML = logHtml;
            
            btnCloseModal.style.display = 'none';
            modalActions.style.display = 'flex';
            
            btnGoCatalog.onclick = () => {
                window.location.href = '../index.php';
            };
            
            btnImportAnother.onclick = () => {
                statusModal.style.display = 'none';
                movieQuery.value = '';
                resultsGrid.innerHTML = '';
            };
        })
        .catch(err => {
            modalSpinner.style.display = 'none';
            modalTitle.innerText = "Error Crítico";
            modalMessage.innerHTML = '<div style="color: #ef4444;"><i class="fa-solid fa-circle-xmark"></i> Error en la comunicación con el procesador.</div>';
            btnCloseModal.innerText = "Cerrar";
            btnCloseModal.style.display = 'block';
            btnCloseModal.onclick = () => { statusModal.style.display = 'none'; };
            console.error(err);
        });
    }
});
</script>
<?php
require_once $rootPath . 'includes/footer.php';
?>
