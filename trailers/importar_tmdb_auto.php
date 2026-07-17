<?php
$pageTitle = "Importador Automático TMDB";
$showNavbar = false;
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>
<?php
require_once "../config/conexion.php";
require_once __DIR__ . "/../includes/seguridad.php";
require_admin_or_editor('../index.php');
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
    const csrfToken = <?= json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
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
    const fallbackPoster = 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=342';

    function buildPosterUrl(path) {
        const safePath = String(path ?? '');
        return /^\/[a-zA-Z0-9._-]+$/.test(safePath)
            ? `https://image.tmdb.org/t/p/w342${safePath}`
            : fallbackPoster;
    }

    function renderGridMessage(message, options = {}) {
        const wrapper = document.createElement('div');
        wrapper.style.gridColumn = '1 / -1';
        wrapper.style.textAlign = 'center';
        wrapper.style.color = options.color ?? 'var(--text-muted)';
        wrapper.style.padding = options.padding ?? '50px';

        if (options.iconClass) {
            const icon = document.createElement('i');
            icon.className = `fa-solid ${options.iconClass}`;
            icon.style.fontSize = options.iconSize ?? '32px';
            icon.style.marginBottom = '10px';
            if (options.iconColor) {
                icon.style.color = options.iconColor;
            }
            wrapper.appendChild(icon);
        }

        const text = document.createElement('p');
        text.textContent = String(message ?? '');
        wrapper.appendChild(text);
        resultsGrid.replaceChildren(wrapper);
    }

    function renderModalMessage(message, options = {}) {
        const wrapper = document.createElement('div');
        if (options.color) {
            wrapper.style.color = options.color;
        }

        if (options.iconClass) {
            const icon = document.createElement('i');
            icon.className = `fa-solid ${options.iconClass}`;
            wrapper.append(icon, document.createTextNode(` ${String(message ?? '')}`));
        } else {
            wrapper.textContent = String(message ?? '');
        }

        modalMessage.replaceChildren(wrapper);
    }

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

        renderGridMessage('Buscando películas...', {
            iconClass: 'fa-circle-notch fa-spin',
            iconColor: 'var(--primary)'
        });

        fetch(`api_tmdb.php?action=search_movie&query=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    renderGridMessage(data.error, {
                        color: 'var(--secondary)',
                        padding: '30px',
                        iconClass: 'fa-circle-exclamation',
                        iconSize: '24px'
                    });
                    return;
                }

                if (!data.results || data.results.length === 0) {
                    renderGridMessage('No se encontraron resultados.', {
                        iconClass: 'fa-magnifying-glass-minus'
                    });
                    return;
                }

                resultsGrid.replaceChildren();
                data.results.forEach(movie => {
                    const movieId = Number.parseInt(movie.id, 10);
                    if (!Number.isInteger(movieId) || movieId <= 0) {
                        return;
                    }

                    const year = movie.release_date
                        ? String(movie.release_date).substring(0, 4)
                        : 'N/A';
                    const voteAverage = Number(movie.vote_average);
                    
                    const card = document.createElement('article');
                    card.className = 'movie-card';
                    card.style.display = 'flex';
                    card.style.flexDirection = 'column';

                    const posterContainer = document.createElement('div');
                    posterContainer.className = 'movie-poster-container';
                    posterContainer.style.aspectRatio = '2 / 3';

                    const poster = document.createElement('img');
                    poster.src = buildPosterUrl(movie.poster_path);
                    poster.alt = String(movie.title ?? '');
                    poster.className = 'movie-poster';

                    const ratingBadge = document.createElement('div');
                    ratingBadge.className = 'rating-badge';
                    const ratingIcon = document.createElement('i');
                    ratingIcon.className = 'fa-solid fa-star';
                    const ratingValue = document.createElement('span');
                    ratingValue.textContent = Number.isFinite(voteAverage)
                        ? voteAverage.toFixed(1)
                        : '0.0';
                    ratingBadge.append(ratingIcon, ratingValue);
                    posterContainer.append(poster, ratingBadge);

                    const movieInfo = document.createElement('div');
                    movieInfo.className = 'movie-info';
                    movieInfo.style.display = 'flex';
                    movieInfo.style.flexDirection = 'column';
                    movieInfo.style.flexGrow = '1';
                    movieInfo.style.padding = '12px';

                    const title = document.createElement('h3');
                    title.className = 'movie-title';
                    title.style.fontSize = '14px';
                    title.style.marginBottom = '4px';
                    title.style.overflow = 'hidden';
                    title.style.textOverflow = 'ellipsis';
                    title.style.whiteSpace = 'nowrap';
                    title.textContent = String(movie.title ?? '');

                    const metaRow = document.createElement('div');
                    metaRow.className = 'movie-meta-row';
                    metaRow.style.fontSize = '11px';
                    metaRow.style.marginBottom = '12px';
                    const yearWrapper = document.createElement('span');
                    const calendarIcon = document.createElement('i');
                    calendarIcon.className = 'fa-regular fa-calendar';
                    yearWrapper.append(calendarIcon, document.createTextNode(` ${year}`));
                    metaRow.appendChild(yearWrapper);

                    const importButton = document.createElement('button');
                    importButton.type = 'button';
                    importButton.className = 'btn btn-primary btn-importar';
                    importButton.style.width = '100%';
                    importButton.style.justifyContent = 'center';
                    importButton.style.fontSize = '12px';
                    importButton.style.padding = '8px 0';
                    importButton.style.marginTop = 'auto';
                    const importIcon = document.createElement('i');
                    importIcon.className = 'fa-solid fa-cloud-arrow-down';
                    importButton.append(importIcon, document.createTextNode(' Importar Todo'));
                    importButton.addEventListener('click', () => importMovie(movieId));

                    movieInfo.append(title, metaRow, importButton);
                    card.append(posterContainer, movieInfo);
                    
                    resultsGrid.appendChild(card);
                });
            })
            .catch(err => {
                renderGridMessage('Error de conexión con el servidor.', {
                    color: 'var(--secondary)',
                    padding: '30px'
                });
                console.error(err);
            });
    }

    function importMovie(id) {
        if (!Number.isInteger(id) || id <= 0) {
            renderGridMessage('La película seleccionada no es válida.', {
                color: 'var(--secondary)',
                padding: '30px',
                iconClass: 'fa-circle-exclamation',
                iconSize: '24px'
            });
            return;
        }

        statusModal.style.display = 'flex';
        modalTitle.innerText = "Procesando Importación";
        renderModalMessage('Conectando con TMDB para obtener metadatos...', {
            iconClass: 'fa-spinner fa-spin'
        });
        modalSpinner.style.display = 'block';
        btnCloseModal.style.display = 'none';
        modalActions.style.display = 'none';

        fetch('procesar_importar_tmdb.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({ id: String(id) }).toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                modalSpinner.style.display = 'none';
                modalTitle.innerText = "Error de Importación";
                renderModalMessage(data.error, {
                    color: '#ef4444',
                    iconClass: 'fa-triangle-exclamation'
                });
                btnCloseModal.innerText = "Cerrar";
                btnCloseModal.style.display = 'block';
                btnCloseModal.onclick = () => { statusModal.style.display = 'none'; };
                return;
            }

            modalSpinner.style.display = 'none';
            modalTitle.innerText = "¡Importación Completada!";

            const importLog = document.createDocumentFragment();

            const successLine = document.createElement('div');
            successLine.style.color = '#10b981';
            successLine.style.fontWeight = 'bold';
            successLine.style.marginBottom = '10px';
            const successIcon = document.createElement('i');
            successIcon.className = 'fa-solid fa-circle-check';
            successLine.append(
                successIcon,
                document.createTextNode(` "${String(data.titulo ?? '')}" añadida con éxito.`)
            );
            importLog.appendChild(successLine);

            const directorLine = document.createElement('div');
            const directorName = document.createElement('strong');
            directorName.textContent = String(data.director ?? '');
            directorLine.append(
                document.createTextNode('• Director: '),
                directorName,
                document.createTextNode(` (${String(data.director_status ?? '')})`)
            );
            importLog.appendChild(directorLine);

            const castLine = document.createElement('div');
            const castCount = document.createElement('strong');
            castCount.textContent = `${Number.parseInt(data.actores_count, 10) || 0} actores`;
            castLine.append(
                document.createTextNode('• Reparto importado: '),
                castCount,
                document.createTextNode(' asociados.')
            );
            importLog.appendChild(castLine);

            const genresLine = document.createElement('div');
            const genresCount = document.createElement('strong');
            genresCount.textContent = String(Number.parseInt(data.generos_count, 10) || 0);
            genresLine.append(document.createTextNode('• Géneros asociados: '), genresCount);
            importLog.appendChild(genresLine);
            
            if (data.nuevo_director_creado) {
                const newDirectorLine = document.createElement('div');
                newDirectorLine.style.fontSize = '11px';
                newDirectorLine.style.marginTop = '5px';
                newDirectorLine.style.color = 'var(--primary)';
                const directorIcon = document.createElement('i');
                directorIcon.className = 'fa-solid fa-user-plus';
                newDirectorLine.append(
                    directorIcon,
                    document.createTextNode(` Nuevo director registrado localmente: ${String(data.director ?? '')}`)
                );
                importLog.appendChild(newDirectorLine);
            }
            if (Array.isArray(data.nuevos_actores_creados) && data.nuevos_actores_creados.length > 0) {
                const newActorsLine = document.createElement('div');
                newActorsLine.style.fontSize = '11px';
                newActorsLine.style.marginTop = '5px';
                newActorsLine.style.color = 'var(--primary)';
                const actorsIcon = document.createElement('i');
                actorsIcon.className = 'fa-solid fa-user-plus';
                const actorNames = document.createElement('em');
                actorNames.textContent = data.nuevos_actores_creados
                    .map(name => String(name ?? ''))
                    .join(', ');
                newActorsLine.append(
                    actorsIcon,
                    document.createTextNode(' Actores creados localmente: '),
                    actorNames
                );
                importLog.appendChild(newActorsLine);
            }

            modalMessage.replaceChildren(importLog);
            
            btnCloseModal.style.display = 'none';
            modalActions.style.display = 'flex';
            
            btnGoCatalog.onclick = () => {
                window.location.href = '../index.php';
            };
            
            btnImportAnother.onclick = () => {
                statusModal.style.display = 'none';
                movieQuery.value = '';
                resultsGrid.replaceChildren();
            };
        })
        .catch(err => {
            modalSpinner.style.display = 'none';
            modalTitle.innerText = "Error Crítico";
            renderModalMessage('Error en la comunicación con el procesador.', {
                color: '#ef4444',
                iconClass: 'fa-circle-xmark'
            });
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
