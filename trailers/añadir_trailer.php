<?php
$pageTitle = "Añadir Nuevo Trailer";
$showNavbar = false;
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>
<?php
require_once "../config/conexion.php";
require_once __DIR__ . "/../includes/seguridad.php";
require_admin_or_editor('../index.php');
define('BASE_PATH', '../');

$sqlGeneros = "SELECT * FROM generos ORDER BY nombre ASC";
$resGeneros = mysqli_query($conexion, $sqlGeneros);

$sqlReparto = "SELECT * FROM reparto ORDER BY nombre ASC, apellidos ASC";
$resReparto = mysqli_query($conexion, $sqlReparto);

$sqlDirectores = "SELECT * FROM directores ORDER BY nombre ASC, apellidos ASC";
$resDirectores = mysqli_query($conexion, $sqlDirectores);
?>
    <h1>Añadir Nuevo Trailer</h1>
    <p>Formulario para registrar una nueva película y su trailer en la base de datos.</p>

    <!-- SECCIÓN TMDB AUTOCOMPLETAR -->
    <div class="tmdb-autocomplete-card">
        <h3><i class="fa-solid fa-wand-magic-sparkles"></i> Autocompletar con TMDB (Recomendado)</h3>
        <p>Introduce el título de la película para rellenar automáticamente la ficha de datos, sinopsis, portada, trailer oficial y asociar reparto/director de tu base de datos.</p>
        <div class="tmdb-search-row">
            <input type="text" id="tmdb_movie_query" placeholder="Ej: Interstellar, Batman, Gladiator...">
            <button type="button" id="btn_tmdb_movie_search" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
        </div>
        <div id="tmdb_movie_results" class="tmdb-movie-results"></div>
        <div id="tmdb_status_box" class="tmdb-status-box"></div>
    </div>

    <form action="procesar_trailer.php" method="POST">
        <?= csrf_field() ?>
        <label for="titulo">Título de la Película *</label>
        <input type="text" id="titulo" name="titulo" required placeholder="Ej: Interstellar">

        <label for="id_director">Director:</label>
        <div class="select-action-row">
            <select id="id_director" name="id_director">
                <option value="">-- No especificado --</option>
                <?php while ($d = mysqli_fetch_assoc($resDirectores)) { ?>
                    <option value="<?php echo $d['id_director']; ?>">
                        <?php echo htmlspecialchars($d['nombre'] . ' ' . $d['apellidos']); ?>
                    </option>
                <?php } ?>
            </select>
            <a href="añadir_director.php" target="_blank" class="btn btn-secondary btn-inline-flex" title="Registrar nuevo director">
                <i class="fa-solid fa-user-plus"></i> Nuevo
            </a>
        </div>

        <label for="release_date">Fecha de Estreno *</label>
        <input type="date" id="release_date" name="release_date" required>

        <label>Género(s) (Selecciona al menos uno) *</label>
        <div class="genres-checkbox-group" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; margin-bottom: 18px; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius-md); max-height: 150px; overflow-y: auto; background-color: var(--bg-surface-lowest, #1e293b);">
            <?php while ($g = mysqli_fetch_assoc($resGeneros)) { ?>
                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer; margin: 0;">
                    <input type="checkbox" name="generos[]" value="<?php echo $g['id_genero']; ?>" style="width: auto; height: auto; cursor: pointer; transform: scale(1.1); accent-color: var(--primary);">
                    <?php echo htmlspecialchars($g['nombre']); ?>
                </label>
            <?php } ?>
        </div>

        <label for="nuevo_genero">¿Añadir otro género nuevo?</label>
        <input type="text" id="nuevo_genero" name="nuevo_genero" placeholder="Ej: Musical, Romance...">

        <label for="duracion">Duración (minutos) *</label>
        <input type="number" id="duracion" name="duracion" required min="1" placeholder="Ej: 169">

        <label for="trailer_url">URL del Trailer *</label>
        <input type="url" id="trailer_url" name="trailer_url" required placeholder="Ej: https://www.youtube.com/watch?v=...">

        <label for="poster_url">URL de la Portada (Poster):</label>
        <input type="url" id="poster_url" name="poster_url" placeholder="Ej: https://enlace-imagen.jpg">

        <label for="valoracion">Valoración (0 a 10) *</label>
        <input type="number" id="valoracion" name="valoracion" required step="0.1" min="0" max="10" placeholder="Ej: 8.7">

        <label style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
            <span>Reparto (Selecciona los actores que participan y asigna su personaje)</span>
            <a href="añadir_reparto.php" target="_blank" style="font-size: 12px; font-weight: 600; color: var(--primary); text-decoration: none; display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border: 1px solid rgba(245,158,11,0.35); border-radius: 20px; background: rgba(245,158,11,0.08); transition: background 0.2s ease;" onmouseover="this.style.background='rgba(245,158,11,0.18)'" onmouseout="this.style.background='rgba(245,158,11,0.08)'">
                <i class="fa-solid fa-user-plus" style="font-size: 11px;"></i> Añadir nuevo actor
            </a>
        </label>
        <div class="reparto-selection-group" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 18px; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius-md); max-height: 250px; overflow-y: auto; background-color: var(--bg-surface-lowest, #1e293b);">
            <?php if (mysqli_num_rows($resReparto) > 0) {
                while ($actor = mysqli_fetch_assoc($resReparto)) { ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 6px 0; border-bottom: 1px dashed rgba(216, 195, 173, 0.05);">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer; margin: 0; flex: 1;">
                            <input type="checkbox" name="actores[]" value="<?php echo $actor['id_reparto']; ?>" onchange="toggleActorInput(this)" style="width: auto; height: auto; cursor: pointer; transform: scale(1.1); accent-color: var(--primary);">
                            <img src="<?php echo htmlspecialchars($actor['foto_url'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200'); ?>" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover; margin-left: 5px; margin-right: 5px;">
                            <span><?php echo htmlspecialchars($actor['nombre'] . ' ' . $actor['apellidos']); ?></span>
                        </label>
                        <input type="text" name="personajes[<?php echo $actor['id_reparto']; ?>]" placeholder="Nombre del personaje..." disabled style="flex: 1; max-width: 250px; padding: 6px 12px; font-size: 13px; height: auto;">
                    </div>
                <?php }
            } else { ?>
                <p style="color: var(--text-muted); font-size: 13px; margin: 0; padding: 10px 0;">No hay actores registrados. <a href="añadir_reparto.php" style="color: var(--primary); text-decoration: underline;">Registra un actor primero</a>.</p>
            <?php } ?>
        </div>

        <label for="sinopsis">Sinopsis / Descripción:</label>
        <textarea id="sinopsis" name="sinopsis" rows="4" placeholder="Escribe un breve resumen de la película..."></textarea>

        <button type="submit">Añadir Trailer</button>
    </form>

    <a class="volver" href="../index.php">← Volver al inicio</a>

    <!-- Script de Integración con TMDB -->
    <script>
        function toggleActorInput(checkbox) {
            const row = checkbox.closest('div');
            if (row) {
                const textInput = row.querySelector('input[type="text"]');
                if (textInput) {
                    textInput.disabled = !checkbox.checked;
                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const tmdbQuery = document.getElementById('tmdb_movie_query');
            const btnSearch = document.getElementById('btn_tmdb_movie_search');
            const resultsBox = document.getElementById('tmdb_movie_results');
            const statusBox = document.getElementById('tmdb_status_box');
            const fallbackPoster = 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=92';

            function renderMessage(container, message, options = {}) {
                const messageNode = document.createElement('div');
                messageNode.style.color = options.color ?? 'var(--text-muted)';
                messageNode.style.textAlign = 'center';
                messageNode.style.padding = '10px';

                if (options.iconClass) {
                    const iconNode = document.createElement('i');
                    iconNode.className = `fa-solid ${options.iconClass}`;
                    messageNode.append(iconNode, document.createTextNode(` ${String(message ?? '')}`));
                } else {
                    messageNode.textContent = String(message ?? '');
                }

                container.replaceChildren(messageNode);
            }

            function isValidTmdbPath(path) {
                return /^\/[a-zA-Z0-9._-]+$/.test(String(path ?? ''));
            }

            function buildPosterUrl(path, size, fallback = '') {
                const allowedSizes = new Set(['w92', 'w500']);
                return allowedSizes.has(size) && isValidTmdbPath(path)
                    ? `https://image.tmdb.org/t/p/${size}${String(path)}`
                    : fallback;
            }

            function createStatusIcon(iconClass, color) {
                const icon = document.createElement('i');
                icon.className = `fa-solid ${iconClass}`;
                icon.style.color = color;
                return icon;
            }

            tmdbQuery.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchMovie();
                }
            });

            btnSearch.addEventListener('click', searchMovie);

            function searchMovie() {
                const query = tmdbQuery.value.trim();
                if (query === '') {
                    alert('Por favor escribe el título de la película.');
                    return;
                }

                resultsBox.style.display = 'flex';
                renderMessage(resultsBox, 'Buscando en TMDB...', { iconClass: 'fa-spinner fa-spin' });
                statusBox.style.display = 'none';

                fetch(`api_tmdb.php?action=search_movie&query=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            renderMessage(resultsBox, data.error, {
                                color: 'var(--danger, #ef4444)',
                                iconClass: 'fa-circle-exclamation'
                            });
                            return;
                        }

                        if (!data.results || data.results.length === 0) {
                            renderMessage(resultsBox, 'No se encontraron películas con ese título.');
                            return;
                        }

                        resultsBox.replaceChildren();
                        data.results.forEach(movie => {
                            const movieId = Number.parseInt(movie.id, 10);
                            if (!Number.isInteger(movieId) || movieId <= 0) {
                                return;
                            }

                            const year = movie.release_date
                                ? String(movie.release_date).substring(0, 4)
                                : 'N/A';
                            
                            const row = document.createElement('div');
                            row.className = 'tmdb-result-row';

                            const info = document.createElement('div');
                            info.className = 'tmdb-result-row-info';

                            const image = document.createElement('img');
                            image.src = buildPosterUrl(movie.poster_path, 'w92', fallbackPoster);
                            image.alt = '';

                            const textWrapper = document.createElement('div');
                            const title = document.createElement('strong');
                            title.className = 'tmdb-result-row-title';
                            title.textContent = String(movie.title ?? '');

                            const yearNode = document.createElement('div');
                            yearNode.className = 'tmdb-result-row-year';
                            yearNode.textContent = year;

                            const selectButton = document.createElement('button');
                            selectButton.type = 'button';
                            selectButton.className = 'btn btn-secondary btn-sm';
                            selectButton.style.fontSize = '12px';
                            selectButton.style.padding = '4px 10px';
                            selectButton.textContent = 'Seleccionar';

                            textWrapper.append(title, yearNode);
                            info.append(image, textWrapper);
                            row.append(info, selectButton);
                            row.addEventListener('click', () => selectMovie(movieId));
                            resultsBox.appendChild(row);
                        });
                    })
                    .catch(err => {
                        renderMessage(resultsBox, 'Error al conectar con el servidor.', {
                            color: 'var(--danger)'
                        });
                        console.error(err);
                    });
            }

            function selectMovie(id) {
                if (!Number.isInteger(id) || id <= 0) {
                    renderMessage(resultsBox, 'La película seleccionada no es válida.', {
                        color: 'var(--danger)'
                    });
                    return;
                }

                renderMessage(resultsBox, 'Cargando ficha completa...', { iconClass: 'fa-spinner fa-spin' });
                
                fetch(`api_tmdb.php?action=movie_details&id=${encodeURIComponent(id)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            renderMessage(resultsBox, data.error, { color: 'var(--danger)' });
                            return;
                        }

                        resultsBox.style.display = 'none';

                        document.getElementById('titulo').value = data.title || '';
                        document.getElementById('release_date').value = data.release_date || '';
                        document.getElementById('duracion').value = data.runtime || '';
                        document.getElementById('sinopsis').value = data.overview || '';
                        const voteAverage = Number(data.vote_average);
                        document.getElementById('valoracion').value = Number.isFinite(voteAverage)
                            ? voteAverage.toFixed(1)
                            : '0.0';
                        
                        if (data.poster_path) {
                            document.getElementById('poster_url').value = buildPosterUrl(data.poster_path, 'w500');
                        }

                        let trailerUrlFound = '';
                        if (data.videos && data.videos.results) {
                            const trailer = data.videos.results.find(v => v.site === 'YouTube' && (v.type === 'Trailer' || v.type === 'Teaser'));
                            const trailerKey = String(trailer?.key ?? '');
                            if (/^[a-zA-Z0-9_-]{6,20}$/.test(trailerKey)) {
                                trailerUrlFound = `https://www.youtube.com/watch?v=${trailerKey}`;
                                document.getElementById('trailer_url').value = trailerUrlFound;
                            }
                        }

                        let directorName = '';
                        let localDirectorFound = false;
                        if (data.credits && data.credits.crew) {
                            const directorObj = data.credits.crew.find(c => c.job === 'Director');
                            if (directorObj) {
                                directorName = directorObj.name;
                                const directorSelect = document.getElementById('id_director');
                                
                                for (let i = 0; i < directorSelect.options.length; i++) {
                                    const option = directorSelect.options[i];
                                    const optionTextNormalized = option.text.toLowerCase().trim().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                                    const directorNormalized = directorName.toLowerCase().trim().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                                    
                                    if (optionTextNormalized.includes(directorNormalized) || directorNormalized.includes(optionTextNormalized)) {
                                        directorSelect.selectedIndex = i;
                                        localDirectorFound = true;
                                        break;
                                    }
                                }
                            }
                        }

                        let missingActors = [];
                        let matchedCount = 0;

                        const actorCheckboxes = document.querySelectorAll('input[name="actores[]"]');
                        actorCheckboxes.forEach(cb => {
                            cb.checked = false;
                            const charInput = document.getElementsByName(`personajes[${cb.value}]`)[0];
                            if (charInput) {
                                charInput.value = '';
                                charInput.disabled = true;
                            }
                        });

                        if (data.credits && data.credits.cast) {
                            const topCast = data.credits.cast.slice(0, 10);

                            topCast.forEach(castMember => {
                                let actorFound = false;
                                const castNameNorm = castMember.name.toLowerCase().trim().normalize("NFD").replace(/[\u0300-\u036f]/g, "");

                                actorCheckboxes.forEach(cb => {
                                    const label = cb.closest('label');
                                    if (label) {
                                        const actorLabelText = label.innerText.toLowerCase().trim().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                                        if (actorLabelText.includes(castNameNorm) || castNameNorm.includes(actorLabelText)) {
                                            cb.checked = true;
                                            actorFound = true;
                                            matchedCount++;
                                            
                                            const charInput = document.getElementsByName(`personajes[${cb.value}]`)[0];
                                            if (charInput) {
                                                charInput.value = castMember.character || '';
                                                charInput.disabled = false;
                                            }
                                        }
                                    }
                                });

                                if (!actorFound) {
                                    missingActors.push(castMember.name);
                                }
                            });
                        }

                        statusBox.style.display = 'flex';
                        const statusFragment = document.createDocumentFragment();

                        const loadedLine = document.createElement('div');
                        loadedLine.style.color = '#10b981';
                        loadedLine.style.fontWeight = '600';
                        loadedLine.style.marginBottom = '4px';
                        loadedLine.append(
                            createStatusIcon('fa-circle-check', '#10b981'),
                            document.createTextNode(` Ficha cargada: "${String(data.title ?? '')}"`)
                        );
                        statusFragment.appendChild(loadedLine);

                        if (directorName) {
                            const directorLine = document.createElement('div');
                            const directorLabel = document.createElement('strong');
                            directorLabel.textContent = String(directorName);

                            if (localDirectorFound) {
                                directorLine.append(
                                    createStatusIcon('fa-user-tie', '#10b981'),
                                    document.createTextNode(' Director: '),
                                    directorLabel,
                                    document.createTextNode(' (asociado localmente)')
                                );
                            } else {
                                const missingLabel = document.createElement('span');
                                missingLabel.style.color = '#f59e0b';
                                missingLabel.textContent = 'no encontrado en BD local';

                                const createDirectorLink = document.createElement('a');
                                createDirectorLink.href = 'añadir_director.php';
                                createDirectorLink.target = '_blank';
                                createDirectorLink.rel = 'noopener noreferrer';
                                createDirectorLink.style.color = 'var(--primary)';
                                createDirectorLink.style.textDecoration = 'underline';
                                createDirectorLink.textContent = 'Crear Director';

                                directorLine.append(
                                    createStatusIcon('fa-user-tie', '#f59e0b'),
                                    document.createTextNode(' Director: '),
                                    directorLabel,
                                    document.createTextNode(' ('),
                                    missingLabel,
                                    document.createTextNode('). '),
                                    createDirectorLink
                                );
                            }

                            statusFragment.appendChild(directorLine);
                        }

                        const matchedLine = document.createElement('div');
                        const matchedValue = document.createElement('strong');
                        matchedValue.textContent = String(matchedCount);
                        matchedLine.append(
                            createStatusIcon('fa-users', '#10b981'),
                            document.createTextNode(' Actores asociados localmente: '),
                            matchedValue
                        );
                        statusFragment.appendChild(matchedLine);
                        
                        if (missingActors.length > 0) {
                            const missingLine = document.createElement('div');
                            missingLine.style.marginTop = '4px';
                            missingLine.style.fontSize = '12px';
                            missingLine.style.color = 'var(--text-muted)';

                            const missingNames = document.createElement('em');
                            missingNames.textContent = missingActors.map(name => String(name ?? '')).join(', ');

                            const addActorLink = document.createElement('a');
                            addActorLink.href = 'añadir_reparto.php';
                            addActorLink.target = '_blank';
                            addActorLink.rel = 'noopener noreferrer';
                            addActorLink.style.color = 'var(--primary)';
                            addActorLink.style.textDecoration = 'underline';
                            addActorLink.textContent = 'Añadir Actor';

                            missingLine.append(
                                createStatusIcon('fa-triangle-exclamation', ''),
                                document.createTextNode(' Actores en TMDB no creados localmente: '),
                                missingNames,
                                document.createTextNode('. '),
                                addActorLink
                            );
                            statusFragment.appendChild(missingLine);
                        }

                        statusBox.replaceChildren(statusFragment);
                    })
                    .catch(err => {
                        renderMessage(resultsBox, 'Error al cargar detalles de la película.', {
                            color: 'var(--danger)'
                        });
                        console.error(err);
                    });
            }
        });
    </script>
<?php
require_once $rootPath . 'includes/footer.php';
?>
