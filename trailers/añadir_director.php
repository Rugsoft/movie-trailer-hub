<?php
require_once "../config/conexion.php";
require_once __DIR__ . "/../includes/seguridad.php";
require_admin_or_editor('../index.php');
define('BASE_PATH', '../');
?>
<?php
$pageTitle = "Añadir Director";
$showNavbar = false;
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>
    <h1>Añadir Director</h1>
    <p>Registra un nuevo director para poder asociarlo a los trailers.</p>

    <!-- SECCIÓN TMDB AUTOCOMPLETAR -->
    <div class="tmdb-autocomplete-card">
        <h3><i class="fa-solid fa-wand-magic-sparkles"></i> Buscar en TMDB</h3>
        <p>Busca al director en TMDB para auto-completar su nombre, apellidos, edad y país de origen.</p>
        <div class="tmdb-search-row">
            <input type="text" id="tmdb_person_query" placeholder="Ej: Christopher Nolan, Steven Spielberg...">
            <button type="button" id="btn_tmdb_person_search" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
        </div>
        <div id="tmdb_person_results" class="tmdb-movie-results"></div>
        <div id="tmdb_status_box" class="tmdb-status-box"></div>
    </div>

    <form action="procesar_director.php" method="POST">
        <?= csrf_field() ?>
        <label for="nombre">Nombre *</label>
        <input type="text" id="nombre" name="nombre" required placeholder="Ej: Christopher">

        <label for="apellidos">Apellidos *</label>
        <input type="text" id="apellidos" name="apellidos" required placeholder="Ej: Nolan">

        <label for="edad">Edad (años):</label>
        <input type="number" id="edad" name="edad" min="1" max="120" placeholder="Ej: 53">

        <label for="pais">País de origen:</label>
        <input type="text" id="pais" name="pais" placeholder="Ej: Reino Unido">

        <button type="submit">Guardar Director</button>
    </form>

    <a class="volver" href="../index.php">← Volver al inicio</a>

    <!-- Script de Integración con TMDB -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tmdbQuery = document.getElementById('tmdb_person_query');
            const btnSearch = document.getElementById('btn_tmdb_person_search');
            const resultsBox = document.getElementById('tmdb_person_results');
            const statusBox = document.getElementById('tmdb_status_box');
            const fallbackImage = 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=185';

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

            function buildProfileUrl(profilePath) {
                const safePath = String(profilePath ?? '');
                return /^\/[a-zA-Z0-9._-]+$/.test(safePath)
                    ? `https://image.tmdb.org/t/p/w185${safePath}`
                    : fallbackImage;
            }

            tmdbQuery.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchPerson();
                }
            });

            btnSearch.addEventListener('click', searchPerson);

            function searchPerson() {
                const query = tmdbQuery.value.trim();
                if (query === '') {
                    alert('Por favor escribe el nombre del director.');
                    return;
                }

                resultsBox.style.display = 'flex';
                renderMessage(resultsBox, 'Buscando en TMDB...', { iconClass: 'fa-spinner fa-spin' });
                statusBox.style.display = 'none';

                fetch(`api_tmdb.php?action=search_person&query=${encodeURIComponent(query)}`)
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
                            renderMessage(resultsBox, 'No se encontraron personas con ese nombre.');
                            return;
                        }

                        resultsBox.replaceChildren();
                        data.results.forEach(person => {
                            const personId = Number.parseInt(person.id, 10);
                            if (!Number.isInteger(personId) || personId <= 0) {
                                return;
                            }

                            const row = document.createElement('div');
                            row.className = 'tmdb-result-row';

                            const info = document.createElement('div');
                            info.className = 'tmdb-result-row-info';

                            const image = document.createElement('img');
                            image.src = buildProfileUrl(person.profile_path);
                            image.alt = '';
                            image.style.width = '38px';
                            image.style.height = '38px';
                            image.style.borderRadius = '50%';

                            const textWrapper = document.createElement('div');
                            const name = document.createElement('strong');
                            name.className = 'tmdb-result-row-title';
                            name.textContent = String(person.name ?? '');

                            const department = document.createElement('div');
                            department.className = 'tmdb-result-row-year';
                            department.textContent = person.known_for_department
                                ? ` (${String(person.known_for_department)})`
                                : '';

                            const selectButton = document.createElement('button');
                            selectButton.type = 'button';
                            selectButton.className = 'btn btn-secondary btn-sm';
                            selectButton.style.fontSize = '12px';
                            selectButton.style.padding = '4px 10px';
                            selectButton.textContent = 'Seleccionar';

                            textWrapper.append(name, department);
                            info.append(image, textWrapper);
                            row.append(info, selectButton);
                            row.addEventListener('click', () => selectPerson(personId));
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

            function selectPerson(id) {
                if (!Number.isInteger(id) || id <= 0) {
                    renderMessage(resultsBox, 'La persona seleccionada no es válida.', {
                        color: 'var(--danger)'
                    });
                    return;
                }

                renderMessage(resultsBox, 'Cargando detalles...', { iconClass: 'fa-spinner fa-spin' });
                
                fetch(`api_tmdb.php?action=person_details&id=${encodeURIComponent(id)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            renderMessage(resultsBox, data.error, { color: 'var(--danger)' });
                            return;
                        }

                        resultsBox.style.display = 'none';

                        const nameParts = (data.name || '').trim().split(/\s+/);
                        if (nameParts.length > 1) {
                            document.getElementById('nombre').value = nameParts.shift();
                            document.getElementById('apellidos').value = nameParts.join(' ');
                        } else {
                            document.getElementById('nombre').value = data.name || '';
                            document.getElementById('apellidos').value = '';
                        }

                        if (data.birthday) {
                            const birthDate = new Date(data.birthday);
                            const endDate = data.deathday ? new Date(data.deathday) : new Date();
                            let age = endDate.getFullYear() - birthDate.getFullYear();
                            const monthDiff = endDate.getMonth() - birthDate.getMonth();
                            if (monthDiff < 0 || (monthDiff === 0 && endDate.getDate() < birthDate.getDate())) {
                                age--;
                            }
                            document.getElementById('edad').value = age;
                        } else {
                            document.getElementById('edad').value = '';
                        }

                        if (data.place_of_birth) {
                            const parts = data.place_of_birth.split(',');
                            const country = parts[parts.length - 1].trim();
                            document.getElementById('pais').value = country;
                        } else {
                            document.getElementById('pais').value = '';
                        }

                        statusBox.style.display = 'flex';
                        const statusMessage = document.createElement('div');
                        statusMessage.style.color = '#10b981';
                        statusMessage.style.fontWeight = '600';

                        const statusIcon = document.createElement('i');
                        statusIcon.className = 'fa-solid fa-circle-check';
                        statusMessage.append(
                            statusIcon,
                            document.createTextNode(` Ficha cargada: "${String(data.name ?? '')}"`)
                        );
                        statusBox.replaceChildren(statusMessage);
                    })
                    .catch(err => {
                        renderMessage(resultsBox, 'Error al cargar detalles de la persona.', {
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
