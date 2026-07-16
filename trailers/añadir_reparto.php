<?php
require_once "../config/conexion.php";
require_once __DIR__ . "/../includes/seguridad.php";
require_admin_or_editor('../index.php');
define('BASE_PATH', '../');
?>
<?php
$pageTitle = "Añadir Actor / Actriz";
$showNavbar = false;
$rootPath = "../";
require_once $rootPath . 'includes/navbar.php';
?>
    <h1>Añadir Actor / Actriz</h1>
    <p>Registra un nuevo miembro del reparto para poder asociarlo a los trailers.</p>

    <!-- SECCIÓN TMDB AUTOCOMPLETAR -->
    <div class="tmdb-autocomplete-card">
        <h3><i class="fa-solid fa-wand-magic-sparkles"></i> Buscar en TMDB</h3>
        <p>Busca al actor o actriz en TMDB para auto-completar su nombre, apellidos, edad, país y retrato oficial.</p>
        <div class="tmdb-search-row">
            <input type="text" id="tmdb_person_query" placeholder="Ej: Matthew McConaughey, Anne Hathaway...">
            <button type="button" id="btn_tmdb_person_search" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
        </div>
        <div id="tmdb_person_results" class="tmdb-movie-results"></div>
        <div id="tmdb_status_box" class="tmdb-status-box"></div>
    </div>

    <form action="procesar_reparto.php" method="POST">
        <label for="nombre">Nombre *</label>
        <input type="text" id="nombre" name="nombre" required placeholder="Ej: Matthew">

        <label for="apellidos">Apellidos *</label>
        <input type="text" id="apellidos" name="apellidos" required placeholder="Ej: McConaughey">

        <label for="edad">Edad (años):</label>
        <input type="number" id="edad" name="edad" min="1" max="120" placeholder="Ej: 54">

        <label for="pais">País de origen:</label>
        <input type="text" id="pais" name="pais" placeholder="Ej: Estados Unidos">

        <label for="foto_url">URL de la Foto (Avatar/Retrato):</label>
        <input type="url" id="foto_url" name="foto_url" placeholder="Ej: https://enlace-imagen-actor.jpg">

        <button type="submit">Guardar Actor/Actriz</button>
    </form>

    <a class="volver" href="../index.php">← Volver al inicio</a>

    <!-- Script de Integración con TMDB -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tmdbQuery = document.getElementById('tmdb_person_query');
            const btnSearch = document.getElementById('btn_tmdb_person_search');
            const resultsBox = document.getElementById('tmdb_person_results');
            const statusBox = document.getElementById('tmdb_status_box');

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
                    alert('Por favor escribe el nombre del actor/actriz.');
                    return;
                }

                resultsBox.style.display = 'flex';
                resultsBox.innerHTML = '<div style="color: var(--text-muted); text-align: center; padding: 10px;"><i class="fa-solid fa-spinner fa-spin"></i> Buscando en TMDB...</div>';
                statusBox.style.display = 'none';

                fetch(`api_tmdb.php?action=search_person&query=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            resultsBox.innerHTML = `<div style="color: var(--danger, #ef4444); text-align: center; padding: 10px;"><i class="fa-solid fa-circle-exclamation"></i> ${data.error}</div>`;
                            return;
                        }

                        if (!data.results || data.results.length === 0) {
                            resultsBox.innerHTML = '<div style="color: var(--text-muted); text-align: center; padding: 10px;">No se encontraron personas con ese nombre.</div>';
                            return;
                        }

                        resultsBox.innerHTML = '';
                        data.results.forEach(person => {
                            const imgUrl = person.profile_path ? `https://image.tmdb.org/t/p/w185${person.profile_path}` : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=185';
                            const dep = person.known_for_department ? ` (${person.known_for_department})` : '';

                            const row = document.createElement('div');
                            row.className = 'tmdb-result-row';
                            
                            row.innerHTML = `
                                <div class="tmdb-result-row-info">
                                    <img src="${imgUrl}" style="width: 38px; height: 38px; border-radius: 50%;">
                                    <div>
                                        <strong class="tmdb-result-row-title">${person.name}</strong>
                                        <div class="tmdb-result-row-year">${dep}</div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm" style="font-size: 12px; padding: 4px 10px;">Seleccionar</button>
                            `;

                            row.addEventListener('click', () => selectPerson(person.id));
                            resultsBox.appendChild(row);
                        });
                    })
                    .catch(err => {
                        resultsBox.innerHTML = `<div style="color: var(--danger); text-align: center; padding: 10px;">Error al conectar con el servidor.</div>`;
                        console.error(err);
                    });
            }

            function selectPerson(id) {
                resultsBox.innerHTML = '<div style="color: var(--text-muted); text-align: center; padding: 10px;"><i class="fa-solid fa-spinner fa-spin"></i> Cargando detalles...</div>';
                
                fetch(`api_tmdb.php?action=person_details&id=${id}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            resultsBox.innerHTML = `<div style="color: var(--danger); text-align: center; padding: 10px;">${data.error}</div>`;
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

                        if (data.profile_path) {
                            document.getElementById('foto_url').value = `https://image.tmdb.org/t/p/h632${data.profile_path}`;
                        } else {
                            document.getElementById('foto_url').value = '';
                        }

                        statusBox.style.display = 'flex';
                        statusBox.innerHTML = `<div style="color: #10b981; font-weight: 600;"><i class="fa-solid fa-circle-check"></i> Ficha cargada: "${data.name}"</div>`;
                    })
                    .catch(err => {
                        resultsBox.innerHTML = `<div style="color: var(--danger); text-align: center; padding: 10px;">Error al cargar detalles de la persona.</div>`;
                        console.error(err);
                    });
            }
        });
    </script>
<?php
require_once $rootPath . 'includes/footer.php';
?>
