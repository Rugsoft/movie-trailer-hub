-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-07-2026 a las 12:25:01
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `movie_trailer_hub`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `trailers`
--

CREATE TABLE `trailers` (
  `id_trailer` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `director` varchar(255) DEFAULT NULL,
  `release_date` date NOT NULL,
  `genero` varchar(50) NOT NULL,
  `duracion` int(4) NOT NULL,
  `trailer_url` varchar(500) NOT NULL,
  `poster_url` varchar(500) DEFAULT NULL,
  `valoracion` decimal(3,1) NOT NULL DEFAULT 0.0,
  `sinopsis` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `trailers`
--

INSERT INTO `trailers` (`id_trailer`, `titulo`, `director`, `release_date`, `genero`, `duracion`, `trailer_url`, `poster_url`, `valoracion`, `sinopsis`) VALUES
(1, 'Interstellar', 'Christopher Nolan', '2014-11-07', 'Ciencia Ficcion', 169, 'https://www.youtube.com/watch?v=zSWdZAzkD40', 'https://images.unsplash.com/photo-1534447677768-be436bb09401?q=80&w=600&auto=format&fit=crop', 8.7, 'Un grupo de científicos y exploradores espaciales se embarcan en un viaje espacial para encontrar un nuevo hogar para la humanidad.'),
(3, 'The Dark Knight', 'Christopher Nolan', '2008-07-18', 'Acción', 152, 'https://www.youtube.com/watch?v=EXeTwQWrcwY', 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=600&auto=format&fit=crop', 9.0, 'Cuando la amenaza conocida como el Joker causa estragos y caos en Gotham, Batman debe aceptar una de las mayores pruebas psicológicas y físicas.'),
(4, 'La Odisea', 'Chistopher Nolan', '2026-07-17', 'Historica', 214, 'https://www.youtube.com/watch?v=X9nUrOnHlz0&t', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSGvILSjiHaJE5kg5zB6NRC09LvmxGGdXEHpYmvJe99Lw&s=10', 7.5, 'El viaje de ulises para volver a Itaca conn su mujer y su hijo'),
(5, 'Vaiana', 'Thomas Kail', '2026-07-08', 'Aventuras', 115, 'https://www.youtube.com/watch?v=oqS3Lpu1ZqI', 'https://es.web.img3.acsta.net/img/66/38/6638e87bae70da6d4a74a48ede52c7ca.jpg', 0.0, 'Vaiana (Catherine Lagaʻaia) responde a la llamada del océano y, por primera vez, viaja más allá del arrecife de su isla de Motunui con el semidiós Maui (Dwayne Johnson) en un viaje inolvidable para devolver la prosperidad a su pueblo.'),
(6, 'Posesión infernal: En llamas', 'Sébastien Vanicek', '2026-07-17', 'Terror', 110, 'https://www.youtube.com/watch?v=JSQRp_JPu-k', 'https://pics.filmaffinity.com/evil_dead_burn-680479495-large.jpg', 0.0, 'Película ambientada en el universo de la saga \"Posesión infernal\". Tras perder a su marido, una mujer busca consuelo junto a sus suegros en la apartada casa familiar. A medida que se transforman uno a uno en Deadites, convirtiendo la reunión en una reunión familiar infernal, ella descubre que los votos que hizo en vida perduran incluso después de la muerte.'),
(7, 'The Birthday Party', 'Miguel Ángel Jiménez', '2026-07-25', 'Drama', 101, 'https://www.youtube.com/watch?v=rlFEwzaPLrU', 'https://pics.filmaffinity.com/the_birthday_party-414763263-large.jpg', 5.0, 'Markos Timoleon, un magnate griego, celebra el 25 cumpleaños de su hija en su isla privada. Allí se enfrentará a una impredecible cadena de acontecimientos que amenazarán su dominio y sacudirán su propia existencia.'),
(8, 'Motor City', 'Potsy Ponciroli', '2026-07-24', 'Thriller', 103, 'https://www.youtube.com/watch?v=iw8JQn_2ZBE', 'https://pics.filmaffinity.com/motor_city-956946618-large.jpg', 0.0, 'En el Detroit de los años 70, el romántico de clase trabajadora John Miller es incriminado por un despiadado gángster tras enamorarse de su novia. Tras pasar años en prisión, regresa con una única misión: vengarse.'),
(9, 'Spider-Man: Brand New Day', 'Destin Cretton', '2026-07-29', 'Acción', 150, 'https://www.youtube.com/watch?v=o8EccyRIwQQ', 'https://pics.filmaffinity.com/spider_man_brand_new_day-219373036-large.jpg', 0.0, 'Han pasado cuatro años desde los acontecimientos de No Way Home, y Peter Parker ahora es un adulto que vive completamente solo, ha desaparecido voluntariamente de las vidas y recuerdos de quienes ama. Combatiendo el crimen en una Nueva York que ya no conoce su nombre, se ha dedicado por completo a proteger su ciudad—un Spider-Man a tiempo completo—, pero a medida que aumentan las exigencias sobre él, la presión desencadena una evolución física que amenaza su existencia, al mismo tiempo que un extraño nuevo patrón de crímenes da lugar a una de las amenazas más poderosas a las que se ha enfrentado.'),
(10, 'Mother Mary', 'David Lowery', '2026-07-31', 'Thriller', 112, 'https://www.youtube.com/watch?v=ATGaqU6Srcc', 'https://pics.filmaffinity.com/mother_mary-623833660-large.jpg', 0.0, 'La intensa relación entre la cantante de pop Mary y Sam, una antigua amiga suya, diseñadora de moda, que se vuelven a reunir tras la necesidad de la primera de un vestido para su nueva gira de conciertos.'),
(11, 'El final de Oak Street', 'David Robert Mitchell', '2026-08-14', 'Intriga', 100, 'https://www.youtube.com/watch?v=3oB9AxspVow', 'https://pics.filmaffinity.com/the_end_of_oak_street-561018092-large.jpg', 0.0, 'Después de que un misterioso fenómeno cósmico arranque Oak Street de su entorno suburbano y transporte a sus habitantes a un lugar desconocido, la familia Platt pronto descubre que su propia supervivencia depende de que permanezcan unidos mientras se orientan en un entorno que ya no reconocen.'),
(12, 'The Dog Stars', 'Ridley Scott', '2026-08-26', 'Ciencia Ficcion', 100, 'https://www.youtube.com/watch?v=cmzVY1goqwQ', 'https://pics.filmaffinity.com/the_dog_stars-989476429-large.jpg', 0.0, 'En un mundo postapocalíptico, un virus aniquila a prácticamente toda la humanidad. Los supervivientes se enfrentan a unos carroñeros errantes llamados \"Segadores\". El protagonista, Hig, un piloto, sobrevivió a la gripe pero perdió a su mujer. Adaptación de la aclamada novela \"La constelación del perro\", de Peter Heller.'),
(13, 'Street Fighter', 'Kitao Sakurai', '2026-10-16', 'Artes Marciales', 102, 'https://www.youtube.com/watch?v=-MJAfIMUQ5s', 'https://pics.filmaffinity.com/street_fighter-223580523-large.jpg', 0.0, 'Nueva película basada en la saga de videojuegos \"Street Fighter\". Ambientada en 1993, los alejados Street Fighters Ryu y Ken Masters son lanzados de nuevo al combate cuando la misteriosa Chun-Li los recluta para el próximo Torneo Mundial de Guerreros: un brutal choque de puños, destino y furia. Pero detrás de esta batalla se esconde una conspiración mortal que los obliga a enfrentarse entre ellos y a los demonios de sus pasados. Y si no lo hacen… ¡fin de la partida!');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `username` varchar(55) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `apellidos` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `rol` varchar(35) NOT NULL DEFAULT 'lector',
  `fecha_alta` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `username`, `password_hash`, `nombre`, `apellidos`, `email`, `telefono`, `rol`, `fecha_alta`) VALUES
(1, 'admin', '$2y$10$iXQf/UkaKu2YnVMQ3pO8X.7o7fCmnnGnQarS3gxfFiCR3bWfmhCe6', 'Administrador', 'del Sistema', 'admin@email.com', '600111222', 'admin', '2026-07-06'),
(2, 'user', '$2y$10$iXQf/UkaKu2YnVMQ3pO8X.7o7fCmnnGnQarS3gxfFiCR3bWfmhCe6', 'Usuario', 'Invitado', 'usuario@email.com', '600333444', 'lector', '2026-07-06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `visualizaciones`
--

CREATE TABLE `visualizaciones` (
  `id_visualizacion` int(11) NOT NULL,
  `id_trailer` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_visualizacion` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_direccion` varchar(45) DEFAULT NULL,
  `dispositivo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `trailers`
--
ALTER TABLE `trailers`
  ADD PRIMARY KEY (`id_trailer`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indices de la tabla `visualizaciones`
--
ALTER TABLE `visualizaciones`
  ADD PRIMARY KEY (`id_visualizacion`),
  ADD KEY `id_trailer` (`id_trailer`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `trailers`
--
ALTER TABLE `trailers`
  MODIFY `id_trailer` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `visualizaciones`
--
ALTER TABLE `visualizaciones`
  MODIFY `id_visualizacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `visualizaciones`
--
ALTER TABLE `visualizaciones`
  ADD CONSTRAINT `fk_visualizaciones_trailers` FOREIGN KEY (`id_trailer`) REFERENCES `trailers` (`id_trailer`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_visualizaciones_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
