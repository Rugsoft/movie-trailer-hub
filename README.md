# Movie Trailer Hub 🎬

**Movie Trailer Hub** es una plataforma web completa e interactiva diseñada en PHP y JavaScript Vanilla para descubrir, organizar, visualizar y calificar trailers de películas. Además, incorpora mecánicas de gamificación (rachas e insignias) y un robusto panel de control administrativo integrado con la API externa de TMDB.

---

## 🛠️ Stack Tecnológico

El proyecto está diseñado bajo un enfoque libre de frameworks para maximizar el rendimiento, la compatibilidad y la simplicidad arquitectónica:

* **Backend**: PHP 8.x (Vanilla).
* **Frontend**: HTML5 Semántico, CSS3 Personalizado (sin frameworks de diseño) y JavaScript Vanilla (interacciones asíncronas, modales y gráficos).
* **Base de Datos**: MySQL / MariaDB.
* **Sesiones**: Almacenamiento local en el directorio `/sessions` para compatibilidad en entornos compartidos.
* **Integraciones**: Conexión nativa con la API v3 de **The Movie Database (TMDB)** mediante peticiones cURL en el backend.
* **Librerías Externas**:
  * [FontAwesome v6](https://fontawesome.com/) (Iconografía).
  * [Chart.js](https://www.chartjs.org/) (Gráficos estadísticos del perfil).

---

## 📂 Arquitectura y Estructura del Código

El proyecto se distribuye en módulos limpios y estructurados:

* **`/config`**:
  * [conexion.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/config/conexion.php): Inicialización de la sesión segura, detección de entornos (local vs. producción) y disparador diario del control de rachas de login.
  * [tmdb_config.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/config/tmdb_config.php): Credenciales y claves de acceso para la API de TMDB.
* **`/auth`**:
  * [login.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/auth/login.php), [registro.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/auth/registro.php), [logout.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/auth/logout.php): Flujo de autenticación seguro.
  * [perfil.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/auth/perfil.php): Perfil interactivo del usuario con historial de vistas, panel de insignias y gráficos dinámicos con la distribución de horas y géneros más consumidos.
  * [gestion_usuarios.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/auth/gestion_usuarios.php) & [api_usuarios.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/auth/api_usuarios.php): Panel de administración (CRUD) de usuarios con protecciones críticas (autobloqueo).
* **`/trailers`**:
  * [reproducir_trailer.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/trailers/reproducir_trailer.php): Reproductor con Modo Cine, reseñas con puntuación por estrellas y motor inteligente de recomendación.
  * [estadisticas.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/trailers/estadisticas.php): Dashboard analítico de tops (trailers, favoritos, géneros, actores y usuarios activos).
  * [listar_trailers.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/trailers/listar_trailers.php), [añadir_trailer.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/trailers/a%C3%B1adir_trailer.php), [modificar_trailer.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/trailers/modificar_trailer.php), [eliminar_trailer.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/trailers/eliminar_trailer.php): CRUD de películas.
  * [listar_reparto.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/trailers/listar_reparto.php), [listar_directores.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/trailers/listar_directores.php): CRUD de actores y directores.
  * [importar_tmdb_auto.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/trailers/importar_tmdb_auto.php) & [tmdb_import_helper.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/trailers/tmdb_import_helper.php): Importador semiautomático conectado a la API de TMDB.
* **`/badges`**:
  * [gamificacion_helper.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/badges/gamificacion_helper.php): Lógica de asignación de logros, auto-migración de tablas de insignias y control de logins consecutivos.
  * [api_badges.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/badges/api_badges.php): Endpoint JSON para el progreso y estado de logros.
  * [registrar_evento.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/badges/registrar_evento.php): Endpoint asíncrono para registrar interacciones del frontend que desbloquean insignias.
* **`/includes`**:
  * [navbar.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/includes/navbar.php) & [footer.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/includes/footer.php): Componentes comunes del layout del sitio.
  * [seguridad.php](file:///C:/xampp/htdocs/curso-soc-php/movieTrailerWeb/includes/seguridad.php): Módulo central de seguridad y roles de usuario.

---

## 🚀 Funcionalidades Principales

### 1. Catálogo y Búsqueda Interactiva
* Paginación interactiva de **15 trailers por página** en el catálogo principal.
* Filtrado dinámico client-side y server-side combinando texto (título, sinopsis, directores, actores), géneros y rangos de fechas de estreno.
* Carrusel interactivo en el banner principal con los próximos estrenos en taquilla.
* **Doble Puntuación Simultánea:** Visualización unificada del rating importado de TMDB (escala 0-10) y la valoración de la comunidad (escala 0.5-5.0) en posters, grids, tablas de administración y perfiles.

### 2. Reproductor y Modo Cine
* Conversor de URLs de YouTube y Vimeo a formato seguro incrustado (`/embed/`) con auto-reproducción.
* **Modo Cine (Apagar Luces)**: Capa de oscuridad interactiva en JavaScript que atenúa la interfaz destacando el video del trailer.

### 3. Motor Inteligente de Recomendaciones
Calcula un puntaje de afinidad en tiempo real al reproducir un trailer para sugerir 5 películas similares:
* **Mismo Director**: +3 puntos.
* **Actores en Común**: +2 puntos por actor.
* **Géneros en Común**: +1 punto por género.

### 4. Sistema de Reseñas y Valoraciones
* Selector dinámico e interactivo con pasos de **0.5 estrellas (medias estrellas)** de 0.5 a 5.0.
* **Contador de Caracteres Reactivo:** Cuadro de texto para comentarios con contador visual (`X / 2000`), imponiendo límites mínimos (25 caracteres) y máximos (2000 caracteres) tanto en cliente como en servidor.
* Promedio dinámico de calificación de la película actualizado de forma instantánea.
* Restricciones únicas en BD para garantizar que cada usuario pueda dejar solo una valoración por película (con opción de editarla o eliminarla).

### 5. Gamificación e Insignias (Logros)
El sistema genera dinámicamente sus tablas y semilla inicial para premiar la retención del usuario con un catálogo completo de **31 insignias** organizadas en 5 categorías:
1. **Hitos de Visualización, Trailers y Favoritos**: Insignias por tiempo acumulado (1, 2, 5 horas), conteo de trailers vistos (5, 10, 25) y favoritos (5, 10, 25).
2. **Especialización de Géneros**: Insignias por ver 5 trailers de categorías específicas (Terror/Suspense, Romance/Drama, Ciencia Ficción/Fantasía, Acción/Aventura, y películas clásicas pre-2000).
3. **Comunidad e Interacción**: Insignias por activar el Modo Cine, valorar con 1 y 5 estrellas, leer reseñas en 10 películas diferentes, y escribir reseñas de más de 200 caracteres.
4. **Fidelidad y Tiempo**: Insignias por ver trailers en horario nocturno (00:00 - 05:00), inicios de sesión en fin de semana (sábado y domingo), y rachas de login consecutivas (hasta 7 días).
5. **Easter Eggs y Hitos Especiales**: Desbloqueos divertidos por buscar fechas exactas del año actual (2026), intentos de acceso no autorizado al panel administrativo, mantener favoritos por más de 30 días, o registrarse en grupo.

* **Notificaciones en Tiempo Real (Toasts)**: El sistema detecta y notifica al instante mediante alertas flotantes en pantalla cuando el usuario consigue desbloquear cualquiera de los logros, tanto en recargas tradicionales de página como en peticiones AJAX interactivas.

### 6. Panel de Administración y Herramientas TMDB
* **Gestor de Usuarios**: CRUD completo para que los administradores modifiquen perfiles, actualicen contraseñas, cambien roles o eliminen usuarios.
* **Importador Automático TMDB**: Buscador que descarga instantáneamente toda la ficha técnica de una película desde TMDB, incluyendo directores y reparto completo, creándolos de forma automática en la base de datos si no existen previamente.

### 7. Sistema de Roles y Permisos
La plataforma define tres niveles de acceso basados en literales de texto en la base de datos:
* **`lector` (Lector)**: Rol por defecto. Permite explorar el catálogo, reproducir trailers (con Modo Cine), guardar favoritos, publicar reseñas y consultar estadísticas personales.
* **`editor` (Editor)**: Permite añadir y modificar trailers, directores y repartos, y utilizar el importador TMDB. **Tiene bloqueada la capacidad de eliminar contenido y el acceso al panel de gestión de usuarios.**
* **`admin` (Administrador)**: Permisos totales. Acceso a todas las operaciones CRUD (incluyendo eliminación de contenido), gestión de usuarios y cambio de roles del sistema.

---

## 🔒 Seguridad e Integridad del Sistema
1. **Prepared Statements**: Protección estricta contra inyecciones SQL en todas las consultas mysqli dependientes del cliente.
2. **Control de CSRF**: Uso de tokens CSRF asíncronos (`X-CSRF-Token`) en cabeceras HTTP para operaciones críticas de creación, modificación y eliminación en el panel administrativo.
3. **Restricción de Autobloqueo**: Salvaguarda en cliente y servidor para evitar que los usuarios administrativos (administradores y editores) puedan degradar su propio rol o eliminarse accidentalmente a sí mismos.
4. **Claves Ajenas (Cascada)**: Integridad referencial que limpia automáticamente todos los favoritos, reseñas y visualizaciones del usuario cuando su cuenta es dada de baja.
