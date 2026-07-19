
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 10-07-2026 a las 13:20:40
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
-- Estructura de tabla para la tabla `directores`
--

CREATE TABLE `directores` (
  `id_director` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(150) NOT NULL,
  `edad` int(3) DEFAULT NULL,
  `pais` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `directores`
--

INSERT INTO `directores` (`id_director`, `nombre`, `apellidos`, `edad`, `pais`) VALUES
(1, 'Christopher', 'Nolan', 55, 'Reino Unido'),
(3, 'Thomas', 'Kail', 49, 'Estados Unidos'),
(4, 'Sébastien', 'Vanicek', NULL, 'Francia'),
(5, 'Miguel', 'Ángel Jiménez', 47, 'España'),
(6, 'Potsy', 'Ponciroli', 45, 'Estados Unidos'),
(7, 'Destin', 'Cretton', 47, 'Estados Unidos'),
(8, 'David', 'Lowery', 45, 'Estados Unidos'),
(9, 'David', 'Robert Mitchell', 51, 'Estados Unidos'),
(10, 'Ridley', 'Scott', 88, 'Reino Unido'),
(11, 'Kitao', 'Sakurai', 43, 'Japón'),
(12, 'Jon', 'Watts', 45, 'Estados Unidos'),
(13, 'Peter', 'Jackson', 64, 'Nueva Zelanda'),
(14, 'Quentin', 'Tarantino', 63, 'USA'),
(15, 'Steven', 'Spielberg', 79, 'USA'),
(16, 'Zack', 'Snyder', 60, 'USA'),
(17, 'Michael', 'Sarnoski', NULL, 'USA'),
(18, 'Sun', 'Haipeng', 46, 'China'),
(19, 'Renny', 'Harlin', 67, 'Finlandia'),
(20, 'Curry', 'Barker', 26, 'USA'),
(21, 'Natalie', 'Erika James', 36, 'Estados Unidos'),
(22, 'Cal', 'McMau', NULL, 'Reino Unido'),
(23, 'Bàrbara', 'Farré', 32, 'España'),
(24, 'Guy', 'Ritchie', 57, 'UK'),
(25, 'Denis', 'Villeneuve', 58, 'Canada'),
(26, 'Michał', 'Kwieciński', 75, 'Polonia'),
(27, 'Haruo', 'Sotozaki', NULL, 'Japan'),
(28, 'Damian', 'McCarthy', 45, 'Irlanda'),
(29, 'Tyler', 'Atkins', NULL, 'Australia'),
(30, 'Kristoffer', 'Borgli', 40, 'Norway');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `favoritos`
--

CREATE TABLE `favoritos` (
  `id_usuario` int(11) NOT NULL,
  `id_trailer` int(11) NOT NULL,
  `fecha_adicion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `favoritos`
--

-- Datos de favoritos omitidos para proteger la privacidad.

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `generos`
--

CREATE TABLE `generos` (
  `id_genero` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `generos`
--

INSERT INTO `generos` (`id_genero`, `nombre`) VALUES
(2, 'Acción'),
(11, 'Animación'),
(9, 'Artes Marciales'),
(4, 'Aventuras'),
(10, 'Bélica'),
(1, 'Ciencia Ficcion'),
(16, 'Comedia'),
(6, 'Drama'),
(13, 'Fantasia'),
(3, 'Historica'),
(8, 'Intriga'),
(14, 'Musical'),
(15, 'Romance'),
(12, 'Suspense'),
(5, 'Terror'),
(7, 'Thriller');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reparto`
--

CREATE TABLE `reparto` (
  `id_reparto` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(150) NOT NULL,
  `edad` int(3) DEFAULT NULL,
  `pais` varchar(100) DEFAULT NULL,
  `foto_url` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reparto`
--

INSERT INTO `reparto` (`id_reparto`, `nombre`, `apellidos`, `edad`, `pais`, `foto_url`) VALUES
(1, 'Tom', 'Holland', 30, 'Estados Unidos', 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f6/Tom_Holland_during_pro-am_Wentworth_golf_club_2023-2_%28cropped%29.jpg/960px-Tom_Holland_during_pro-am_Wentworth_golf_club_2023-2_%28cropped%29.jpg'),
(2, 'Zendaya', 'Coleman', 29, 'Estados Unidos', 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/28/Zendaya_-_2019_by_Glenn_Francis.jpg/960px-Zendaya_-_2019_by_Glenn_Francis.jpg'),
(3, 'Anne', 'Hathaway', 43, 'Estados Unidos', 'https://www.unwomen.org/sites/default/files/Headquarters/Images/Sections/Partnerships/GoodwillAmbassadors/UNwomen_AH_Photo_400px.jpg'),
(4, 'Matt', 'Damon', 55, 'Estados Unidos', 'https://upload.wikimedia.org/wikipedia/commons/5/52/MKr347638_Matt_Damon_%28Small_Things_Like_These%2C_Berlinale_2024%29.jpg'),
(5, 'Robert', 'Pattinson', 40, 'Reino Unido', 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ad/Robert_Pattinson_H%C3%B4tel_de_Crillon_2009.jpg/250px-Robert_Pattinson_H%C3%B4tel_de_Crillon_2009.jpg'),
(6, 'Lupita', 'Nyong\'o', 43, 'Kenia', 'https://upload.wikimedia.org/wikipedia/commons/4/4c/Lupita_Nyong%27o_by_Gage_Skidmore_4.jpg'),
(7, 'Charlize', 'Theron', 50, 'Sudáfrica', 'https://upload.wikimedia.org/wikipedia/commons/5/5d/Charlize-theron-IMG_6045.jpg'),
(8, 'Jon', 'Bernthal', 49, 'Estados Unidos', 'https://image.tmdb.org/t/p/w500/o0t6EVkJOrFAjESDilZUlf46IbQ.jpg'),
(9, 'John', 'Leguizamo', 65, 'Colombia', 'https://static.wikia.nocookie.net/doblaje/images/1/13/John_leguizamo_1.jpg/revision/latest/scale-to-width-down/1200?cb=20190825135836&path-prefix=es'),
(10, 'Benny', 'Safdie', 40, 'Estados Unidos', 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a1/Benny_Safdie-1868_%28cropped%29.jpg/960px-Benny_Safdie-1868_%28cropped%29.jpg'),
(11, 'Samantha', 'Morton', 49, 'Reino Unido', 'https://image.tmdb.org/t/p/w500/v84b7MENeD9rwX6xTD7fSdhSOC9.jpg'),
(12, 'Elliot', 'Page', 39, 'Canadá', 'https://cdn.britannica.com/41/249341-050-E5F7039C/Actor-Elliot-Page-2022.jpg'),
(13, 'Elijah', 'Wood', 45, 'USA', 'https://image.tmdb.org/t/p/h632/ayARmqAe9Aab1zg6FjJG0u9MEBo.jpg'),
(14, 'Sean', 'Astin', 55, 'USA', 'https://image.tmdb.org/t/p/h632/As3ctGUtBYmG4zj4Ifyrcqd71HP.jpg'),
(15, 'Viggo', 'Mortensen', 67, 'USA', 'https://image.tmdb.org/t/p/h632/vH5gVSpHAMhDaFWfh0Q7BG61O1y.jpg'),
(16, 'Ian', 'McKellen', 87, 'UK', 'https://image.tmdb.org/t/p/h632/coWjgMEYJjk2OrNddlXCBm8EIr3.jpg'),
(17, 'Orlando', 'Bloom', 49, 'UK', 'https://image.tmdb.org/t/p/h632/lwQoA0qJTCZ6l2FH6PjmhRQjiaB.jpg'),
(18, 'Ian', 'Holm', 88, 'UK', 'https://image.tmdb.org/t/p/h632/cOJDgvgj4nMec6Inzj1H5nugTO5.jpg'),
(19, 'Liv', 'Tyler', 49, 'USA', 'https://image.tmdb.org/t/p/h632/hXFKqlOPVjtfBSPlmQwFnJvhvmU.jpg'),
(20, 'Christopher', 'Lee', 93, 'UK', 'https://image.tmdb.org/t/p/h632/dA26fBr3t7mKqjn3OYW6kbD1LXM.jpg'),
(21, 'Dominic', 'Monaghan', 49, 'Germany', 'https://image.tmdb.org/t/p/h632/h3VEhi6cMaEdmmvoH3keWTv4vEb.jpg'),
(22, 'Sean', 'Bean', 67, 'UK', 'https://image.tmdb.org/t/p/h632/kTjiABk3TJ3yI0Cto5RsvyT6V3o.jpg'),
(23, 'Billy', 'Boyd', 57, 'UK', 'https://image.tmdb.org/t/p/h632/jluumxuDr7rm2f97NFf9LydBdaZ.jpg'),
(24, 'Andy', 'Serkis', 62, 'UK', 'https://image.tmdb.org/t/p/h632/eNGqhebQ4cDssjVeNFrKtUvweV5.jpg'),
(25, 'John', 'Rhys-Davies', 82, 'UK', 'https://image.tmdb.org/t/p/h632/qpXl1YnqQsUKYEDm3BB2zK5wbhe.jpg'),
(26, 'Bernard', 'Hill', 79, 'UK', 'https://image.tmdb.org/t/p/h632/5i8bj2nsTrFU2ddSynleOjapxor.jpg'),
(27, 'Miranda', 'Otto', 58, 'Australia', 'https://image.tmdb.org/t/p/h632/mLFg0ptqHc2l30adCmOgrG5yklS.jpg'),
(28, 'Karl', 'Urban', 54, 'New Zealand', 'https://image.tmdb.org/t/p/h632/7Y96dAfg0HcFrcLjlD5eD9N0uj4.jpg'),
(29, 'John', 'Noble', 77, 'Australia', 'https://image.tmdb.org/t/p/h632/t9dB8uU27sQDaEEFMiQvp5sbrXU.jpg'),
(30, 'David', 'Wenham', 60, 'Australia', 'https://image.tmdb.org/t/p/h632/F7CWSqUE75HtrcdqIQ7UMZ9aTX.jpg'),
(31, 'Gerard', 'Butler', 56, 'UK', 'https://image.tmdb.org/t/p/h632/i54XoxYieuff2w6MwyfwVUBvmR0.jpg'),
(32, 'Lena', 'Headey', 52, 'Bermuda', 'https://image.tmdb.org/t/p/h632/cDyZLf8ddz0EgoUjpv4jjzy7qxA.jpg'),
(33, 'Dominic', 'West', 56, 'UK', 'https://image.tmdb.org/t/p/h632/6y2M3EWslBPwPlugEFg8XDHfSJ0.jpg'),
(34, 'Vincent', 'Regan', 61, 'Wales', 'https://image.tmdb.org/t/p/h632/7VHu79GciN5QlQTmWdBS86eE2mu.jpg'),
(35, 'Michael', 'Fassbender', 49, 'Alemania', 'https://image.tmdb.org/t/p/h632/xvbnUiB2ZBR3QIt595OzNy657Vw.jpg'),
(36, 'Tom', 'Wisdom', 53, 'Inglaterra', 'https://image.tmdb.org/t/p/h632/84jgVW6GGeha5T3FsFwN6BQ4qOu.jpg'),
(37, 'Andrew', 'Pleavin', 58, 'UK', 'https://image.tmdb.org/t/p/h632/hp20HveWeBveVYtE87DPwmXEpD2.jpg'),
(38, 'Andrew', 'Tiernan', 60, 'UK', 'https://image.tmdb.org/t/p/h632/dD32tMcONax0lmLw9nwyAf43mlN.jpg'),
(39, 'Rodrigo', 'Santoro', 50, 'Brasil', 'https://image.tmdb.org/t/p/h632/d3MaF9SPHDn2PMYHuqdnO0Csik6.jpg'),
(40, 'Hugh', 'Jackman', 57, 'Australia', 'https://image.tmdb.org/t/p/h632/4Xujtewxqt6aU0Y81tsS9gkjizk.jpg'),
(41, 'Jodie', 'Comer', 33, 'UK', 'https://image.tmdb.org/t/p/h632/AfsBpnfw0E2h8NZK4zkFcOjYlEb.jpg'),
(42, 'Bill', 'Skarsgård', 35, 'Suecia', 'https://image.tmdb.org/t/p/h632/yGML6E9OtHQ5PFSvxoIj901tvsL.jpg'),
(43, 'Murray', 'Bartlett', 55, 'Australia', 'https://image.tmdb.org/t/p/h632/eN20zfcRB2F51bmUbTK9byQCpb9.jpg'),
(44, 'Noah', 'Jupe', 21, 'UK', 'https://image.tmdb.org/t/p/h632/xbswZN0SHgeCbyo5GQ0xfZKH3vs.jpg'),
(45, 'Jade', 'Croot', 27, 'Gales', 'https://image.tmdb.org/t/p/h632/j4DuuFPDdnEcDWFWCyBUuhhHkxg.jpg'),
(46, 'Faith', 'Delaney', 12, 'UK', 'https://image.tmdb.org/t/p/h632/jIKjTzr4dCoybQgNhyREahh9Aym.jpg'),
(47, 'Tabitha', 'Smyth', 26, 'Irlanda', 'https://image.tmdb.org/t/p/h632/uREVbfIi4rD2W29zWbtzNqIhP4u.jpg'),
(48, 'Alfie', 'Lawless', 13, 'UK', 'https://image.tmdb.org/t/p/h632/761sH9H88m86M3iUuxMJpLQ2GE2.jpg'),
(49, 'Aaron', 'Eckhart', 58, 'USA', 'https://image.tmdb.org/t/p/h632/u5JjnRMr9zKEVvOP7k3F6gdcwT6.jpg'),
(50, 'Ben', 'Kingsley', 82, 'UK', 'https://image.tmdb.org/t/p/h632/k3Dmu49B2akwDvgqy52MOxznI59.jpg'),
(51, 'Molly', 'Belle Wright', 11, 'UK', 'https://image.tmdb.org/t/p/h632/sjG5KfNuvSyv7ztNFNcuCulnuCS.jpg'),
(52, 'Angus', 'Sampson', 47, 'Australia', 'https://image.tmdb.org/t/p/h632/8EX6ul2zyVUg91oDvI3RpYS5szV.jpg'),
(53, 'Elijah', 'Tamati', 10, 'Nueva Zelanda', 'https://image.tmdb.org/t/p/h632/rFGB97FgZDQ7t01NtMdb4n6vkgg.jpg'),
(54, 'Zhao', 'Simei', 31, 'China', 'https://image.tmdb.org/t/p/h632/39WrPp7nICluex1fGNhtPaUhTFI.jpg'),
(55, '李汶翰', 'Wenhan', 31, 'China', 'https://image.tmdb.org/t/p/h632/eUi1yeqa7YIl4LIwz1rDasteJ27.jpg'),
(56, '那尔那茜', 'Nashi', 36, 'China', 'https://image.tmdb.org/t/p/h632/i2oD8NEMmCSXDVtJq0bxOF0cJ4V.jpg'),
(57, 'Richard', 'Crouchley', 28, 'Nueva Zelanda', 'https://image.tmdb.org/t/p/h632/8AIFy9NYA4PCNkVtDj03B2hXucA.jpg'),
(58, 'Madeleine', 'West', 46, 'Australia', 'https://image.tmdb.org/t/p/h632/buJ3lN6iDQBuuP9FwGgpT9WQxLt.jpg'),
(59, 'Michael', 'Johnston', 30, 'USA', 'https://image.tmdb.org/t/p/h632/fbpcCkBzu43kMdlXxEAMuLhseL8.jpg'),
(60, 'Inde', 'Navarrette', 25, 'USA', 'https://image.tmdb.org/t/p/h632/8mYBaOximzwBgXOYRzbS6eUnoMX.jpg'),
(61, 'Cooper', 'Tomlinson', 27, 'Estados Unidos', 'https://image.tmdb.org/t/p/h632/vBMQbYT1DyWPCUp11dIiqZR9zhd.jpg'),
(62, 'Megan', 'Lawless', 26, 'USA', 'https://image.tmdb.org/t/p/h632/6qW63YEgB1qro01sM7T2HvhtFkh.jpg'),
(63, 'Andy', 'Richter', 59, 'USA', 'https://image.tmdb.org/t/p/h632/5Qr7N6TzC8cI0ULxDm6EC5GpZ4C.jpg'),
(64, 'Haley', 'Fitzgerald', 32, 'Estados Unidos', 'https://image.tmdb.org/t/p/h632/xbKqZ5Epz0IaSCPLXDnByDACw2X.jpg'),
(65, 'Darin', 'Toonder', 52, 'Estados Unidos', 'https://image.tmdb.org/t/p/h632/bD7Y9T7Y6XwDXjGJ8Suabyt1ZGo.jpg'),
(66, 'Anthony', 'Pavone', 29, '', 'https://image.tmdb.org/t/p/h632/hxiqY9gHgwXGYnXSD11Wacm5Nmi.jpg'),
(67, 'Justice', 'Pate', 25, 'Estados Unidos', 'https://image.tmdb.org/t/p/h632/YFxKf2HHJvyavQf54NO5wXFOJX.jpg'),
(68, 'Anthony', 'Casabianca', 25, 'Estados Unidos', 'https://image.tmdb.org/t/p/h632/xNJUKqKQhMeyZ74erQvETr3AqV0.jpg'),
(69, 'Midori', 'Francis', 32, 'USA', 'https://image.tmdb.org/t/p/h632/we9Z3uiLUi06NxKXYNpcexrYOJx.jpg'),
(70, 'Danielle', 'Macdonald', 35, 'Australia', 'https://image.tmdb.org/t/p/h632/pPaypOzZ4oldd7KJHvDEfPSynSi.jpg'),
(71, 'Madeleine', 'Madden', 29, 'Australia', 'https://image.tmdb.org/t/p/h632/oOZ1EJeq2DHxNNLAkLEyYYOlqlM.jpg'),
(72, 'Joseph', 'Baldwin', 56, 'Estados Unidos', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200'),
(73, 'Robert', 'Taylor', 62, 'Australia', 'https://image.tmdb.org/t/p/h632/wjeEGFarZNyvrqLL4W52eXnAnXe.jpg'),
(74, 'Emily', 'Milledge', 30, 'Australia', 'https://image.tmdb.org/t/p/h632/zJDJkoW87uyU3sv3CYHbZv475jZ.jpg'),
(75, 'Lisa', 'Crittenden', 64, 'Australia', 'https://image.tmdb.org/t/p/h632/mV3Tnb4WjgQIFNU3UwUj6Vq9K3J.jpg'),
(76, 'Lucy', 'Goleby', 40, 'Aus', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200'),
(77, 'Showko', 'Showfukutei', 40, 'Japon', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSZ2GPWi-_Y8VFLuH0EbSfZEz1XYRUjGKb8V_2EYOYE1K0xw02bwp5vLg_H&s=10'),
(78, 'Anna', 'Adams', NULL, '', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200'),
(79, 'David', 'Jonsson', 32, 'UK', 'https://image.tmdb.org/t/p/h632/2ZZNGZw57KKMrVIr27g7W16G0jV.jpg'),
(80, 'Tom', 'Blyth', 31, 'UK', 'https://image.tmdb.org/t/p/h632/q8bkN1GvXqjs1ZGFYWViH9o2WDq.jpg'),
(81, 'Alex', 'Hassell', 45, 'UK', 'https://image.tmdb.org/t/p/h632/ssL09IDQBkw1BtRcvvZnm6OJMSm.jpg'),
(82, 'Neil', 'Linpow', NULL, '', 'https://image.tmdb.org/t/p/h632/1be5xAB1L4gCdo6cd01pYfSZMc0.jpg'),
(83, 'Paul', 'Hilton', NULL, '', 'https://image.tmdb.org/t/p/h632/qbvVlXTwvtEiHJB9C6CagBdMiWt.jpg'),
(84, 'Corin', 'Silva', NULL, '', 'https://image.tmdb.org/t/p/h632/qSF5NmUrxmsfAsN1LAOOlXFYb4L.jpg'),
(85, 'Layton', 'Blake', NULL, '', 'https://image.tmdb.org/t/p/h632/hto5PX2oPEjbZeuIg6EhjSVy44t.jpg'),
(86, 'Jack', 'Barker', NULL, '', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200'),
(87, 'Fred', 'Muthui', 26, '', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200'),
(88, 'Lunga', 'Skosana', NULL, '', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200'),
(89, 'María', 'Schwinning', 17, 'España', 'https://image.tmdb.org/t/p/h632/l29ezQEXE0X6PMHr4UXvXdQK1tR.jpg'),
(90, 'Iria', 'del Río', 39, 'España', 'https://image.tmdb.org/t/p/h632/z7Dqvpnb8R13P6B5pmUCDxb2Jvv.jpg'),
(91, 'Roger', 'Casamajor', 49, 'España', 'https://image.tmdb.org/t/p/h632/1UQozr5Y4C9HlFAWvsjsLPvThLF.jpg'),
(92, 'Mauro', 'Vélez Díaz', 19, 'España', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200'),
(93, 'Mariona', 'Pagès', NULL, 'España', 'https://image.tmdb.org/t/p/h632/nji2gk7rif5mgTer5WqyQ83VFTG.jpg'),
(94, 'Irene', 'Balmes', NULL, '', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200'),
(95, 'Henry', 'Cavill', 43, 'Islas Channel', 'https://image.tmdb.org/t/p/h632/kN3A5oLgtKYAxa9lAkpsIGYKYVo.jpg'),
(96, 'Jake', 'Gyllenhaal', 45, 'USA', 'https://image.tmdb.org/t/p/h632/j2Yahha9C0zN5DRaTDzYA7WtdOT.jpg'),
(97, 'Eiza', 'González', 36, 'Mexico', 'https://image.tmdb.org/t/p/h632/9kwfAiMeRUGR6cpZyY8FCuP5MSX.jpg'),
(98, 'Carlos', 'Bardem', 63, 'España', 'https://image.tmdb.org/t/p/h632/4eVSnaYe4O06BNZjuw8UHnWaqB5.jpg'),
(99, 'Michael', 'Vu', NULL, 'UK', 'https://image.tmdb.org/t/p/h632/rHtIIqQvViPHovSJkDzDegBU6nb.jpg'),
(100, 'Fisher', 'Stevens', 62, 'USA', 'https://image.tmdb.org/t/p/h632/wWC6wbLV56RJGtyytMmmGvi054t.jpg'),
(101, 'Rosamund', 'Pike', 47, 'UK', 'https://image.tmdb.org/t/p/h632/8ObNklHDi2hjdz0ayzJFB9jtqzm.jpg'),
(102, 'Mohammed', 'Al Turki', 40, 'Saudi Arabia', 'https://image.tmdb.org/t/p/h632/u9tO2CaUajPbkErAvZCAUYrORkc.jpg'),
(103, 'Kojo', 'Attah', NULL, 'Estados Unidos', 'https://image.tmdb.org/t/p/h632/xtE8Nd5sVskymf3haYUWixFwv4g.jpg'),
(104, 'Jason', 'Wong', 40, 'UK', 'https://image.tmdb.org/t/p/h632/tt3D1PAoB8Yf4jPCovnhlP4pvL3.jpg'),
(105, 'Timothée', 'Chalamet', 30, 'USA', 'https://image.tmdb.org/t/p/h632/dFxpwRpmzpVfP1zjluH68DeQhyj.jpg'),
(106, 'Jason', 'Momoa', 46, 'USA', 'https://image.tmdb.org/t/p/h632/3troAR6QbSb6nUFMDu61YCCWLKa.jpg'),
(107, 'Florence', 'Pugh', 30, 'Inglaterra', 'https://image.tmdb.org/t/p/h632/1Uvfh7xL4U2evkhs0M3C7BbBYFf.jpg'),
(108, 'Rebecca', 'Ferguson', 42, 'Suecia', 'https://image.tmdb.org/t/p/h632/lJloTOheuQSirSLXNA3JHsrMNfH.jpg'),
(109, 'Isaach', 'de Bankolé', 68, 'Costa de Marfil', 'https://image.tmdb.org/t/p/h632/aGjABQBgTA3tduuh54hzWx0aQ44.jpg'),
(110, 'Charlotte', 'Rampling', 80, 'UK', 'https://image.tmdb.org/t/p/h632/Htvl9mN6mlf2a18RAFzNXF3RiG.jpg'),
(111, 'Anya', 'Taylor-Joy', 30, 'USA', 'https://image.tmdb.org/t/p/h632/qYNofOjlRke2MlJVihmJmEdQI4v.jpg'),
(112, 'Javier', 'Bardem', 57, 'España', 'https://image.tmdb.org/t/p/h632/p5xjCovj1uzvA2SXrWLH78Nh1Jf.jpg'),
(113, 'Eryk', 'Kulm', 35, 'Polonia', 'https://image.tmdb.org/t/p/h632/tPOFtVbhLX5QmTwa6QnCoYtywem.jpg'),
(114, 'Joséphine', 'de la Baume', 41, 'Francia', 'https://image.tmdb.org/t/p/h632/r4IhMoiYFsLIFU1R5ilzFOlrEdO.jpg'),
(115, 'Victor', 'Meutelet', 27, 'Francia', 'https://image.tmdb.org/t/p/h632/zROH5MBP1XCjLUaeBSmCyVi27i9.jpg'),
(116, 'Lambert', 'Wilson', 67, 'Francia', 'https://image.tmdb.org/t/p/h632/pFuLPZtgrKsnRXJCKWILQ69k6Ta.jpg'),
(117, 'Theo', 'Grundmann Brechet', NULL, '', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200'),
(118, 'Przemysław', 'Kowalski', NULL, '', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200'),
(119, 'Karolina', 'Gruszka', 45, 'Polonia', 'https://image.tmdb.org/t/p/h632/3QFVPgTVGKZ76U8LzdQWIVUBkQe.jpg'),
(120, 'Kamil', 'Szeptycki', 35, 'Alemania', 'https://image.tmdb.org/t/p/h632/xWKYWTnkth3lwefaACb5iXHkTJD.jpg'),
(121, 'Michał', 'Pawlik', 32, '', 'https://image.tmdb.org/t/p/h632/loDhKENuDQ6VCijcWVq9WUBvuX9.jpg'),
(122, 'Claudia', 'Fortunato', NULL, 'Francia', 'https://image.tmdb.org/t/p/h632/dwyUQnUN0TXuFgxnXSK9x3aqxAD.jpg'),
(123, 'Natsuki', 'Hanae', 35, 'Japan', 'https://image.tmdb.org/t/p/h632/alTb0DlcPIbcwM08WSmxFai58sd.jpg'),
(124, 'Takahiro', 'Sakurai', 52, 'Japan', 'https://image.tmdb.org/t/p/h632/8s8owcKmpRAuhzEGjSdRpztthUg.jpg'),
(125, 'Akira', 'Ishida', 58, 'Japon', 'https://image.tmdb.org/t/p/h632/jnW2Gn2NlR2uwOCeyOuzypnTmkH.jpg'),
(126, 'Hiro', 'Shimono', 46, 'Japon', 'https://image.tmdb.org/t/p/h632/yrSDcgFefHtWkFmLnTrcw2t0MV.jpg'),
(127, 'Yoshimasa', 'Hosoya', 44, 'Japon', 'https://image.tmdb.org/t/p/h632/lUR5oN1LrqGgp25IOcI1qOH1Ud5.jpg'),
(128, 'Saori', 'Hayami', 35, 'Japan', 'https://image.tmdb.org/t/p/h632/gLv9lO7dlUbIsmyJUvgegqAAXki.jpg'),
(129, 'Mamoru', 'Miyano', 43, 'Japon', 'https://image.tmdb.org/t/p/h632/nuok8ueG7k9hPZ09Tpr8e7Qn0ah.jpg'),
(130, 'Reina', 'Ueda', 32, 'Japan', 'https://image.tmdb.org/t/p/h632/2WV61uVU7y6XGYqNHLMpP0sApdu.jpg'),
(131, 'Yuichi', 'Nakamura', 46, 'Japan', 'https://image.tmdb.org/t/p/h632/wb8behVKjBHX9XXrEydvNINCYwH.jpg'),
(132, 'Lynn', 'N/A', 34, 'Japan', 'https://image.tmdb.org/t/p/h632/eJ2NqgzpnzNbT6Nt9EpDfzqNeZM.jpg'),
(133, 'Adam', 'Scott', 53, 'USA', 'https://image.tmdb.org/t/p/h632/b82C29R6fGiPoqIglQ4lzS6q2YX.jpg'),
(134, 'Peter', 'Coonan', NULL, '', 'https://image.tmdb.org/t/p/h632/4CIPj53v871Gwwyean91BhXesfW.jpg'),
(135, 'David', 'Wilmot', NULL, 'Ireland', 'https://image.tmdb.org/t/p/h632/epb46N0iJyAmSOguMdFn6qQGSsy.jpg'),
(136, 'Florence', 'Ordesh', NULL, '', 'https://image.tmdb.org/t/p/h632/vOSd53O4QOjZUl9U8LIP84lrvGk.jpg'),
(137, 'Will', 'O\'Connell', NULL, '', 'https://image.tmdb.org/t/p/h632/9aQlrstSFoKQdsCigFWQibyfZhW.jpg'),
(138, 'Michael', 'Patric', NULL, '', 'https://image.tmdb.org/t/p/h632/mHbEIuUbc9wuqDRhVgLn4hnxVvn.jpg'),
(139, 'Brendan', 'Conroy', 78, 'Ireland', 'https://image.tmdb.org/t/p/h632/pUyjqFWqBERIW0wIJkxwgvfq1VY.jpg'),
(140, 'Austin', 'Amelio', 38, 'USA', 'https://image.tmdb.org/t/p/h632/y4QTXuSSmD99n8dinMiTRBkUnAp.jpg'),
(141, 'Ezra', 'Carlisle', NULL, '', 'https://image.tmdb.org/t/p/h632/9R5EmE7JOU703wl43si2wMwp0MP.jpg'),
(142, 'Mallory', 'Adams', NULL, '', 'https://image.tmdb.org/t/p/h632/c5CdKuNTubd0kZUA7oNizY7my8Z.jpg'),
(143, 'Daniel', 'MacPherson', 46, 'Australia', 'https://image.tmdb.org/t/p/h632/aBvJnrSlzXeU8K3glUa6XQ6nbuK.jpg'),
(144, 'Luke', 'Hemsworth', 45, 'Australia', 'https://image.tmdb.org/t/p/h632/djq5j4VVUxpnCvrTnqaax4n3pqD.jpg'),
(145, 'Russell', 'Crowe', 62, 'New Zealand', 'https://image.tmdb.org/t/p/h632/uxiXuVH4vNWrKlJMVVPG1sxAJFe.jpg'),
(146, 'Mojean', 'Aria', NULL, 'Australia', 'https://image.tmdb.org/t/p/h632/v5W5lpuAWSDVMokU0RKGkHDT2rj.jpg'),
(147, 'Kelly', 'Gale', 31, 'Sweden', 'https://image.tmdb.org/t/p/h632/bU9w5FITuN7TUa9VxFH3whhXFkP.jpg'),
(148, 'George', 'Burgess', 34, 'UK', 'https://image.tmdb.org/t/p/h632/fxTvtnpfjU5uKFKu4GoAVD1o7i0.jpg'),
(149, 'Nathan', 'Phillips', 46, 'Australia', 'https://image.tmdb.org/t/p/h632/9t54w1XE7u7fOWQv83IP0xYAWkh.jpg'),
(150, 'Sol', 'Nc Carrico', NULL, '', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200'),
(151, 'Bren', 'Foster', 49, 'UK', 'https://image.tmdb.org/t/p/h632/qRCsPgki64JVreidPI6KAexZxpk.jpg'),
(152, 'Saphira', 'Moran', NULL, 'Australia', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200'),
(153, 'Mamoudou', 'Athie', 37, 'Mauritania', 'https://image.tmdb.org/t/p/h632/ycUbhfZRKC8MtNK9oMwscRsl3uM.jpg'),
(154, 'Alana', 'Haim', 34, 'USA', 'https://image.tmdb.org/t/p/h632/uOU4uueRxH5BYhzNjzPxJOxZStJ.jpg'),
(155, 'Jordyn', 'Curet', 17, 'USA', 'https://image.tmdb.org/t/p/h632/sUGk173dPdlR6OXQEape7EW2XT1.jpg'),
(156, 'Hailey', 'Benton Gates', 36, 'USA', 'https://image.tmdb.org/t/p/h632/6gwH5IN15L4PXtLRwZYED6mO5xp.jpg'),
(157, 'Michael', 'Abbott Jr.', 48, 'USA', 'https://image.tmdb.org/t/p/h632/lL6epnepASAxQolqXeNve8hEcaR.jpg'),
(158, 'Hannah', 'Gross', 33, 'Canada', 'https://image.tmdb.org/t/p/h632/p94oyYrrywfSH3vkimTL1cbaWQt.jpg'),
(159, 'Sydney', 'Lemmon', 35, 'USA', 'https://image.tmdb.org/t/p/h632/heGyUuth1HInFdywZ6y8gqjf0q6.jpg'),
(160, 'Zoë', 'Winters', 41, 'USA', 'https://image.tmdb.org/t/p/h632/3Ej5luqqvdD3hZXAzFZbdRxj7CQ.jpg'),
(161, 'Sydney', 'Sweeney', 28, 'USA', 'https://image.tmdb.org/t/p/h632/qYiaSl0Eb7G3VaxOg8PxExCFwon.jpg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reparto_trailers`
--

CREATE TABLE `reparto_trailers` (
  `id_reparto_trailer` int(11) NOT NULL,
  `id_trailer` int(11) NOT NULL,
  `id_reparto` int(11) NOT NULL,
  `personaje` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reparto_trailers`
--

INSERT INTO `reparto_trailers` (`id_reparto_trailer`, `id_trailer`, `id_reparto`, `personaje`) VALUES
(9, 10, 3, 'Mother Mary'),
(24, 9, 1, 'Peter Parker/Spider-man'),
(25, 9, 2, 'Mary Jane Wattson'),
(36, 14, 23, 'Pippin'),
(37, 14, 20, 'Saruman'),
(38, 14, 21, 'Merry'),
(39, 14, 13, 'Frodo'),
(40, 14, 18, 'Bilbo'),
(41, 14, 16, 'Gandalf'),
(42, 14, 19, 'Arwen'),
(43, 14, 17, 'Legolas'),
(44, 14, 14, 'Sam'),
(45, 14, 22, 'Boromir'),
(46, 14, 15, 'Aragorn'),
(47, 15, 24, 'Gollum'),
(48, 15, 26, 'Théoden'),
(49, 15, 23, 'Pippin'),
(50, 15, 20, 'Saruman'),
(51, 15, 21, 'Merry'),
(52, 15, 13, 'Frodo'),
(53, 15, 18, 'Bilbo'),
(54, 15, 16, 'Gandalf'),
(55, 15, 25, 'Gimli / Treebeard (voice)'),
(56, 15, 28, 'Eomer'),
(57, 15, 19, 'Arwen'),
(58, 15, 27, 'Éowyn'),
(59, 15, 17, 'Legolas'),
(60, 15, 14, 'Sam'),
(61, 15, 15, 'Aragorn'),
(62, 16, 24, 'Gollum / Smeagol'),
(63, 16, 26, 'Théoden'),
(64, 16, 23, 'Pippin'),
(65, 16, 30, 'Faramir'),
(66, 16, 21, 'Merry'),
(67, 16, 13, 'Frodo'),
(68, 16, 18, 'Bilbo'),
(69, 16, 16, 'Gandalf'),
(70, 16, 29, 'Denethor'),
(71, 16, 25, 'Gimli / Treebeard (voice)'),
(72, 16, 28, 'Eomer'),
(73, 16, 19, 'Arwen'),
(74, 16, 27, 'Éowyn'),
(75, 16, 17, 'Legolas'),
(76, 16, 14, 'Sam'),
(77, 16, 15, 'Aragorn'),
(78, 17, 37, 'Daxos'),
(79, 17, 38, 'Ephialtes'),
(80, 17, 30, 'Dilios/Narrator'),
(81, 17, 33, 'Theron'),
(82, 17, 31, 'King Leonidas'),
(83, 17, 32, 'Gorgo'),
(84, 17, 35, 'Stelios'),
(85, 17, 39, 'Xerxes'),
(86, 17, 36, 'Astinos'),
(87, 17, 34, 'Captain'),
(88, 18, 48, 'Hendrie'),
(89, 18, 42, 'Little John'),
(90, 18, 46, 'Little Margaret'),
(91, 18, 40, 'Robin Hood'),
(92, 18, 45, 'Wainwright'),
(93, 18, 41, 'Sister Brigid'),
(94, 18, 43, 'The Leper'),
(95, 18, 44, 'Arthur / Godwyn'),
(96, 18, 47, 'Sarah'),
(97, 21, 49, 'Ben'),
(98, 21, 52, 'Dan'),
(99, 21, 50, 'Rich'),
(100, 21, 53, 'Finn'),
(101, 21, 58, 'Martine'),
(102, 21, 51, 'Cora'),
(103, 21, 57, 'Matt'),
(104, 21, 54, 'Lilly'),
(105, 21, 55, 'Sam'),
(106, 21, 56, 'Zoe'),
(107, 22, 63, 'Carter'),
(108, 22, 68, 'Chris'),
(109, 22, 66, 'Reggie'),
(110, 22, 61, 'Ian'),
(111, 22, 65, 'Harry'),
(112, 22, 64, 'Viola'),
(113, 22, 60, 'Nikki'),
(114, 22, 67, 'Joe'),
(115, 22, 62, 'Sarah'),
(116, 22, 59, 'Bear'),
(117, 23, 78, ''),
(118, 23, 70, 'Josie'),
(119, 23, 74, 'Georgie'),
(120, 23, 72, 'Ryan'),
(121, 23, 75, 'Helen'),
(122, 23, 76, 'Lucy Childs'),
(123, 23, 71, 'Alanya'),
(124, 23, 69, 'Hana'),
(125, 23, 73, 'Travis'),
(126, 23, 77, 'Kimie'),
(127, 24, 81, 'Paul'),
(128, 24, 84, 'Gaz'),
(129, 24, 79, 'Taylor'),
(130, 24, 87, 'Prisoner in Cell'),
(131, 24, 86, 'Cook up Prisoner'),
(132, 24, 85, 'Maxi'),
(133, 24, 88, 'Nurse'),
(134, 24, 82, 'Robby'),
(135, 24, 83, 'Browning'),
(136, 24, 80, 'Dee'),
(137, 25, 94, ''),
(138, 25, 90, ''),
(139, 25, 89, 'Atenea'),
(140, 25, 93, ''),
(141, 25, 92, 'Aran'),
(142, 25, 91, ''),
(143, 26, 98, 'Manny Salazar'),
(144, 26, 97, 'Rachel WIld'),
(145, 26, 100, 'William Horowitz'),
(146, 26, 95, 'Sid'),
(147, 26, 96, 'Bronco'),
(148, 26, 104, 'Gucci Reyes'),
(149, 26, 103, 'Andre Baker'),
(150, 26, 99, 'Ed Glover'),
(151, 26, 102, 'Wolfgang Klose'),
(152, 26, 101, 'Bobby Sheen'),
(153, 4, 3, 'Penélope'),
(154, 4, 4, 'Odiseo'),
(155, 4, 1, 'Telémaco'),
(156, 4, 2, 'Atenea'),
(157, 27, 111, 'Alia Atreides'),
(158, 27, 110, 'Reverend Mother Mohiam'),
(159, 27, 107, 'Princess Irulan'),
(160, 27, 109, 'Farok'),
(161, 27, 106, 'Hayt'),
(162, 27, 112, 'Stilgar'),
(163, 27, 108, 'Lady Jessica'),
(164, 27, 5, 'Scytale'),
(165, 27, 105, 'Paul Atreides'),
(166, 27, 2, 'Chani'),
(167, 28, 122, 'Matuszyńska'),
(168, 28, 113, 'Fryderyk Chopin'),
(169, 28, 114, 'George Sand'),
(170, 28, 120, 'Julian Fontana'),
(171, 28, 119, 'Delfina Potocka'),
(172, 28, 116, 'King Louis Philippe'),
(173, 28, 121, 'Jan Matuszyński'),
(174, 28, 118, 'Joseph'),
(175, 28, 117, 'Carl Fritsch'),
(176, 28, 115, 'Ferenc Liszt'),
(177, 29, 125, 'Akaza (voice)'),
(178, 29, 126, 'Zenitsu Agatsuma (voice)'),
(179, 29, 132, 'Koyuki (voice)'),
(180, 29, 129, 'Doma (voice)'),
(181, 29, 123, 'Tanjiro Kamado (voice)'),
(182, 29, 130, 'Kanao Tsuyuri (voice)'),
(183, 29, 128, 'Shinobu Kocho (voice)'),
(184, 29, 124, 'Giyu Tomioka (voice)'),
(185, 29, 127, 'Kaigaku (voice)'),
(186, 29, 131, 'Keizo (voice)'),
(187, 30, 133, 'Ohm Bauman'),
(188, 30, 140, 'Conquistador'),
(189, 30, 139, 'Mr. Cobb'),
(190, 30, 135, 'Jerry'),
(191, 30, 141, 'Boy'),
(192, 30, 136, 'Fiona'),
(193, 30, 142, 'Delia Bauman'),
(194, 30, 138, 'Fergal'),
(195, 30, 134, 'Mal'),
(196, 30, 137, 'Alby / Jack the Jackass'),
(197, 31, 151, 'Xavier Grau'),
(198, 31, 143, 'Patton'),
(199, 31, 148, 'Neal'),
(200, 31, 147, 'Luciana'),
(201, 31, 144, 'Gabriel'),
(202, 31, 146, 'Malon James'),
(203, 31, 149, 'Skipper'),
(204, 31, 145, 'Sammy'),
(205, 31, 152, 'Nadine'),
(206, 31, 150, 'Maddie'),
(217, 32, 154, 'Rachel'),
(218, 32, 156, 'Misha'),
(219, 32, 158, 'Alice'),
(220, 32, 155, 'Young Emma'),
(221, 32, 153, 'Mike'),
(222, 32, 157, 'Blake'),
(223, 32, 5, 'Charlie Thompson'),
(224, 32, 159, 'Pauline'),
(225, 32, 2, 'Emma Harwood'),
(226, 32, 160, 'Frances');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `trailers`
--

CREATE TABLE `trailers` (
  `id_trailer` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `release_date` date NOT NULL,
  `duracion` int(4) NOT NULL,
  `trailer_url` varchar(500) NOT NULL,
  `poster_url` varchar(500) DEFAULT NULL,
  `valoracion` decimal(3,1) NOT NULL DEFAULT 0.0,
  `sinopsis` text DEFAULT NULL,
  `id_director` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `trailers`
--

INSERT INTO `trailers` (`id_trailer`, `titulo`, `release_date`, `duracion`, `trailer_url`, `poster_url`, `valoracion`, `sinopsis`, `id_director`) VALUES
(1, 'Interstellar', '2014-11-07', 169, 'https://www.youtube.com/watch?v=zSWdZAzkD40', 'https://www.tuposter.com/pub/media/catalog/product/cache/6da4eda1419e7e16c3b352a67de449ee/i/n/interstellar_poster.png', 8.7, 'Un grupo de científicos y exploradores espaciales se embarcan en un viaje espacial para encontrar un nuevo hogar para la humanidad.', 1),
(3, 'The Dark Knight', '2008-07-18', 152, 'https://www.youtube.com/watch?v=EXeTwQWrcwY', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRlQtmdVARbewBxEfL_XiPhafeNCPeuKSHDVBe5CH24isGNDPHln-U9DM4Q&s=10', 9.0, 'Cuando la amenaza conocida como el Joker causa estragos y caos en Gotham, Batman debe aceptar una de las mayores pruebas psicológicas y físicas.', 1),
(4, 'La Odisea', '2026-07-17', 214, 'https://youtu.be/X9nUrOnHlz0', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSpxl0KPF9Jg6DTu-Z1kgL66PzQA7Z58tKt7dm62MF9YtIVvL4Liaqb_ak&s=10', 7.5, 'Epopeya mitológica que sigue la historia de Odiseo y su largo viaje a casa, de 10 años de duración, tras la guerra de Troya.', 1),
(5, 'Vaiana', '2026-07-08', 115, 'https://www.youtube.com/watch?v=oqS3Lpu1ZqI', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS0DzMJMmMOlagtWmPP8TCqkziCMdzbUSy5zBRpHTCCUcr4HRoBcMIBByd5&s=10', 0.0, 'Vaiana (Catherine Lagaʻaia) responde a la llamada del océano y, por primera vez, viaja más allá del arrecife de su isla de Motunui con el semidiós Maui (Dwayne Johnson) en un viaje inolvidable para devolver la prosperidad a su pueblo.', 3),
(6, 'Posesión infernal: En llamas', '2026-07-17', 110, 'https://www.youtube.com/watch?v=JSQRp_JPu-k', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR_P_9Ib3oUkVoOC4s355rW8y8BNm5dq_Czc0Lhv-KwaacKZEjEAGa82jVa&s=10', 0.0, 'Película ambientada en el universo de la saga \"Posesión infernal\". Tras perder a su marido, una mujer busca consuelo junto a sus suegros en la apartada casa familiar. A medida que se transforman uno a uno en Deadites, convirtiendo la reunión en una reunión familiar infernal, ella descubre que los votos que hizo en vida perduran incluso después de la muerte.', 4),
(7, 'The Birthday Party', '2026-07-25', 101, 'https://www.youtube.com/watch?v=rlFEwzaPLrU', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQn1GhrTIScwkPIzZ1MqikoWpMayj4vqGEiMy-JCHPLV9rIdN_UNxyEcbk&s=10', 5.0, 'Markos Timoleon, un magnate griego, celebra el 25 cumpleaños de su hija en su isla privada. Allí se enfrentará a una impredecible cadena de acontecimientos que amenazarán su dominio y sacudirán su propia existencia.', 5),
(8, 'Motor City', '2026-07-24', 103, 'https://www.youtube.com/watch?v=iw8JQn_2ZBE', 'https://alkazarmulticines.com/wp-content/uploads/2026/06/motor_city_109112.jpg', 0.0, 'En el Detroit de los años 70, el romántico de clase trabajadora John Miller es incriminado por un despiadado gángster tras enamorarse de su novia. Tras pasar años en prisión, regresa con una única misión: vengarse.', 6),
(9, 'Spider-Man: Brand New Day', '2026-07-29', 150, 'https://www.youtube.com/watch?v=o8EccyRIwQQ', 'https://i.ebayimg.com/00/s/MTM1MFgxMDgw/z/xQoAAeSwD7RpwhEC/$_57.PNG?set_id=880000500F', 0.0, 'Han pasado cuatro años desde los acontecimientos de No Way Home, y Peter Parker ahora es un adulto que vive completamente solo, ha desaparecido voluntariamente de las vidas y recuerdos de quienes ama. Combatiendo el crimen en una Nueva York que ya no conoce su nombre, se ha dedicado por completo a proteger su ciudad—un Spider-Man a tiempo completo—, pero a medida que aumentan las exigencias sobre él, la presión desencadena una evolución física que amenaza su existencia, al mismo tiempo que un extraño nuevo patrón de crímenes da lugar a una de las amenazas más poderosas a las que se ha enfrentado.', 12),
(10, 'Mother Mary', '2026-07-31', 112, 'https://www.youtube.com/watch?v=ATGaqU6Srcc', 'https://m.media-amazon.com/images/M/MV5BZWQwNjYzNjctZDEzMC00ZjRmLWEwYjItYzBjYjY1N2JmMjc3XkEyXkFqcGc@._V1_.jpg', 0.0, 'La intensa relación entre la cantante de pop Mary y Sam, una antigua amiga suya, diseñadora de moda, que se vuelven a reunir tras la necesidad de la primera de un vestido para su nueva gira de conciertos.', 8),
(11, 'El final de Oak Street', '2026-08-14', 100, 'https://www.youtube.com/watch?v=3oB9AxspVow', 'https://es.web.img3.acsta.net/c_310_420/img/4a/39/4a3985c3ba68da4c2bd9b3eaa1e8023d.jpg', 0.0, 'Después de que un misterioso fenómeno cósmico arranque Oak Street de su entorno suburbano y transporte a sus habitantes a un lugar desconocido, la familia Platt pronto descubre que su propia supervivencia depende de que permanezcan unidos mientras se orientan en un entorno que ya no reconocen.', 9),
(12, 'The Dog Stars', '2026-08-26', 100, 'https://www.youtube.com/watch?v=cmzVY1goqwQ', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRKZ9C8ibUJr-EAjBgq47ZUZ2C_gNpXt2OjKUtiVuC7XmFsBJ4vig_URG8&s=10', 0.0, 'En un mundo postapocalíptico, un virus aniquila a prácticamente toda la humanidad. Los supervivientes se enfrentan a unos carroñeros errantes llamados \"Segadores\". El protagonista, Hig, un piloto, sobrevivió a la gripe pero perdió a su mujer. Adaptación de la aclamada novela \"La constelación del perro\", de Peter Heller.', 10),
(13, 'Street Fighter', '2026-10-16', 102, 'https://www.youtube.com/watch?v=-MJAfIMUQ5s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR_v7kxzcIivUZWQMKHVnR_qofMpgNa9H3jpM_BsUySl7-GmTgzYyShpu9E&s=10', 0.0, 'Nueva película basada en la saga de videojuegos \"Street Fighter\". Ambientada en 1993, los alejados Street Fighters Ryu y Ken Masters son lanzados de nuevo al combate cuando la misteriosa Chun-Li los recluta para el próximo Torneo Mundial de Guerreros: un brutal choque de puños, destino y furia. Pero detrás de esta batalla se esconde una conspiración mortal que los obliga a enfrentarse entre ellos y a los demonios de sus pasados. Y si no lo hacen… ¡fin de la partida!', 11),
(14, 'El señor de los anillos: La comunidad del anillo', '2001-12-18', 179, 'https://www.youtube.com/watch?v=3GJp6p_mgPo', 'https://image.tmdb.org/t/p/w500/9xtH1RmAzQ0rrMBNUMXstb2s3er.jpg', 8.4, 'En la Tierra Media, el Señor Oscuro Saurón creó los Grandes Anillos de Poder, forjados por los herreros Elfos. Tres para los reyes Elfos, siete para los Señores Enanos, y nueve para los Hombres Mortales. Secretamente, Saurón también forjó un anillo maestro, el Anillo Único, que contiene en sí el poder para esclavizar a toda la Tierra Media. Con la ayuda de un grupo de amigos y de valientes aliados, Frodo emprende un peligroso viaje con la misión de destruir el Anillo Único. Pero el Señor Oscuro Sauron, quien creara el Anillo, envía a sus servidores para perseguir al grupo. Si Sauron lograra recuperar el Anillo, sería el final de la Tierra Media.', 13),
(15, 'El señor de los anillos: Las dos torres', '2002-12-18', 180, 'https://www.youtube.com/watch?v=h-9RYiqyqjk', 'https://image.tmdb.org/t/p/w500/up6gIHZlfEQZkHIfQwcOOaGOzOt.jpg', 8.4, 'La Compañía del Anillo se ha disuelto. El portador del anillo Frodo y su fiel amigo Sam se dirigen hacia Mordor para destruir el Anillo Único y acabar con el poder de Sauron. Mientras, y tras la dura batalla contra los orcos donde cayó Boromir, el hombre Aragorn, el elfo Legolas y el enano Gimli intentan rescatar a los medianos Merry y Pipin, secuestrados por los ogros de Mordor. Por su parte, Saurón y el traidor Sarumán continúan con sus planes en Mordor, en espera de la guerra contra las razas libres de la Tierra Media.', 13),
(16, 'El señor de los anillos: El retorno del rey', '2003-12-17', 202, 'https://www.youtube.com/watch?v=h-9RYiqyqjk', 'https://image.tmdb.org/t/p/w500/mWuFbQrXyLk2kMBKF9TUPtDwuPx.jpg', 8.5, 'Las fuerzas de Saruman han sido destruidas, y su fortaleza sitiada. Ha llegado el momento de que se decida el destino de la Tierra Media, y por primera vez en mucho tiempo, parece que hay una pequeña esperanza. La atención del señor oscuro Sauron se centra ahora en Gondor, el último reducto de los hombres, y del cual Aragorn tendrá que reclamar el trono para ocupar su puesto de rey. Pero las fuerzas de Sauron ya se preparan para lanzar el último y definitivo ataque contra el reino de Gondor, la batalla que decidirá el destino de todos. Mientras tanto, Frodo y Sam continuan su camino hacia Mordor, a la espera de que Sauron no repare en que dos pequeños Hobbits se acercan cada día más al final de su camino, el Monte del Destino.', 13),
(17, '300', '2007-03-07', 117, 'https://www.youtube.com/watch?v=_pYDMTwQUFA', 'https://image.tmdb.org/t/p/w500/h7Lcio0c9ohxPhSZg42eTlKIVVY.jpg', 7.2, 'Adaptación del cómic de Frank Miller (autor del cómic \'Sin City\') sobre la famosa batalla de las Termópilas (480 a.C.). El objetivo de Jerjes, emperador de Persia, era la conquista de Grecia, lo que desencadenó las Guerras Médicas. Dada la gravedad de la situación, el rey Leónidas de Esparta (Gerard Butler) y 300 espartanos se enfrentaron a un ejército persa que era inmensamente superior.', 16),
(18, 'La muerte de Robin Hood', '2026-06-18', 123, 'https://www.youtube.com/watch?v=PJqvdmKERw8', 'https://image.tmdb.org/t/p/w500/7qLBTkJ3SPSi0s2uCj0E6WfR309.jpg', 6.7, 'Robin Hood intenta lidiar con sus demonios tras una larga vida de crímenes y asesinatos. Cuando en una sangrienta batalla resulta herido de gravedad, es enviado a un misterioso castillo para que curen sus heridas. Allí conocerá a una mujer que le ofrecerá una última oportunidad de redención.', 17),
(19, 'El chico león', '2021-12-11', 104, 'https://www.youtube.com/watch?v=sDOul6weX-s', 'https://image.tmdb.org/t/p/w500/8Ae66on9Eo9Oi7lx11Fqo1MNf5s.jpg', 7.3, 'Sigue a Yuen, un joven humilde de origen rural que se enamora de la tradicional danza del león. Junto a sus dos mejores amigos, decide entrenar duro para participar en el campeonato nacional, superando todo tipo de obstáculos, burlas y el clasismo de su entorno. Para lograrlo, contarán con la ayuda de un exbailarín profesional convertido en vendedor de pescado', 18),
(20, 'La danza del león: Maestros del combate', '2024-12-07', 133, 'https://youtu.be/Sp4_IbC5oh8', 'https://image.tmdb.org/t/p/w500/ziv1feLHah9qzrMno0btZyxJJfO.jpg', 7.7, 'Narra la historia de un joven de pueblo que viaja a Shanghái para pagar el tratamiento médico de su padre. Decidido, se inscribe en un prestigioso torneo de artes marciales enfrentando a luchadores modernos mediante un estilo tradicional inspirado en la danza del león', 18),
(21, 'En mar abierto', '2026-04-30', 107, 'https://youtu.be/yHIV7BO1dkk', 'https://image.tmdb.org/t/p/w500/2yXnZvLxfi0paO0HnjpO4rLfocr.jpg', 7.2, 'Un avión comercial en ruta de Los Ángeles a Shanghai se ve obligado a realizar un aterrizaje de emergencia en aguas infestadas de tiburones.', 19),
(22, 'Obsesión', '2026-05-13', 108, 'https://www.youtube.com/watch?v=5MBu6Xhuj38', 'https://image.tmdb.org/t/p/w500/ohi9xvbBUymM4SuIOSlt1xbLRQQ.jpg', 8.3, 'El anhelo romántico desesperado de un chico por su amor platónico de toda la vida desencadena un siniestro hechizo: Niki se vuelve irracionalmente obsesiva hasta convertirse en la sombra de Bear. Una fantasía aparentemente inofensiva que se convertirá en una perturbadora pesadilla.', 20),
(23, 'Insaciable', '2026-05-22', 113, 'https://youtu.be/uIY13LD3RUY', 'https://image.tmdb.org/t/p/w500/zMYFsfuHttGtHnXquWkIWI9Min8.jpg', 6.1, 'Hana, una estudiante de medicina con una fuerte dismorfia corporal, se somete a una moda de adelgazamiento tan absurda como macabra: comer cenizas humanas. A partir de ese momento, comienza a ser acosada por un fantasma hambriento, una manifestación literal del mito del \'hungry ghost\'.', 21),
(24, 'Hombres de acero', '2026-02-20', 91, 'https://www.youtube.com/watch?v=fuW13VqaUl4', 'https://image.tmdb.org/t/p/w500/lKK9ImwpoTCwDZKgYpjIIJCnlf0.jpg', 7.2, 'Taylor (David Jonsson) es un recluso que, tras una larga condena, está a punto de obtener la libertad condicional. A pesar de ser tildado como un \"wasteman\" (alguien sin futuro o \"desperdiciado\"), ha logrado mantenerse alejado de problemas dentro de prisión con la esperanza de reencontrarse con su hijo distanciado.', 22),
(25, 'Mala bestia', '2026-07-31', 94, 'https://youtu.be/arjdcoyPYRE', 'https://image.tmdb.org/t/p/w500/4NjHPW0ZttQTkrJZrmFRISq2d5V.jpg', 0.0, 'Atenea vive en un internado con otros huérfanos donde crecer significa desvanecerse, por lo que se aferra a la idea de no hacerse mayor. Cuando una pareja la acoge, descubre la posibilidad de un hogar real, pero el miedo a ser rechazada la lleva a tomar medidas extremas para quedarse', 23),
(26, 'En la zona gris', '2026-05-13', 97, 'https://www.youtube.com/watch?v=Unsa8AcHo0A', 'https://image.tmdb.org/t/p/w500/iT2XPcPFhjZPNdLydXYKVJBCvb7.jpg', 7.2, 'Un equipo encubierto de agentes de élite viven en la sombra, tan cómodos manejando el poder y la influencia como armas automáticas y explosivos de gran potencia. Cuando un déspota roba una fortuna de mil millones de dólares, son enviados a recuperarla en lo que para cualquier otro sería una misión suicida. Lo que comienza como un atraco imposible empeora aún más y se convierte en una guerra total de estrategia, engaño y supervivencia.', 24),
(27, 'Dune: Parte tres', '2026-12-16', 140, 'https://www.youtube.com/watch?v=qdfXYN5kMBY', 'https://image.tmdb.org/t/p/w500/ceKywESF1WPVlfdYRTrvZbTkSXV.jpg', 0.0, 'Paul Atreides, ahora Emperador Muad\'Dib, se desenvuelve en su inmenso poder mientras lucha contra enemigos políticos y una conspiración dentro de su círculo. Mientras la Casa Atreides se enfrenta al colapso, surge el verdadero peligro para la amante de Paul, Chani, y su heredero nonato.', 25),
(28, 'Chopin', '2025-10-10', 133, 'https://youtu.be/noi55KOvrew', 'https://image.tmdb.org/t/p/w500/f4cLh2vVzKH8WCbplPFo6aCMpRC.jpg', 6.9, 'París, 1835. Aclamado como una verdadera estrella de la música, el joven y carismático pianista Frédéric Chopin es el centro de todas las miradas en las noches decadentes de la ciudad. Sin embargo, cuando sus pulmones empiezan a sangrar, sabe que el tiempo corre en su contra y la composición se convierte en su única obsesión', 26),
(29, 'Kimetsu no Yaiba La fortaleza infinita', '2025-07-18', 155, 'https://www.youtube.com/watch?v=kTVn1Rbi6_8', 'https://image.tmdb.org/t/p/w500/iWLV12z9oexSRLz2WKyqCZbKoPA.jpg', 7.7, 'El Cuerpo de Cazadores de Demonios se enfrenta a los Doce Kizuki restantes antes de enfrentarse a Muzan en el Castillo del Infinito para derrotarlo de una vez por todas.', 27),
(30, 'Hokum', '2026-04-29', 108, 'https://www.youtube.com/watch?v=6cHc23cGDIc', 'https://image.tmdb.org/t/p/w500/hzOJEzqrOtwXbPw1U5nZMvTaljQ.jpg', 6.9, 'Un escritor especializado en historias de terror decide viajar a una posada de Irlanda con la intención de esparcir en la zona las cenizas de sus difuntos padres. Lo que no sabe es que la posada es un lugar maldito habitado por una bruja. O eso cuentan los lugareños que viven cerca del lugar...', 28),
(31, 'Beast (La bestia)', '2026-04-10', 115, 'https://www.youtube.com/watch?v=CyY8LrvqzGc', 'https://image.tmdb.org/t/p/w500/3S32pzyZMoOJ3ADrnT2GbV4tiX4.jpg', 6.3, 'Con la ayuda de su antiguo entrenador, un campeón de MMA retirado e invicto regresa a la jaula para vengar la muerte de su hermano y resolver sus problemas financieros.', 29),
(32, 'El drama', '2026-04-01', 105, 'https://www.youtube.com/watch?v=pn8-dhxHVX8', 'https://image.tmdb.org/t/p/w500/zgfBYGYCNcOZ51JDbr6tm45H8y0.jpg', 6.9, 'Una pareja, en los días previos a su boda, se enfrenta a una crisis cuando unas inesperadas revelaciones desbaratan lo que uno de ellos creía saber sobre el otro.', 30);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `trailers_generos`
--

CREATE TABLE `trailers_generos` (
  `id_trailer` int(11) NOT NULL,
  `id_genero` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `trailers_generos`
--

INSERT INTO `trailers_generos` (`id_trailer`, `id_genero`) VALUES
(1, 1),
(3, 2),
(4, 3),
(4, 4),
(4, 13),
(5, 4),
(6, 5),
(7, 6),
(8, 7),
(9, 1),
(9, 2),
(10, 7),
(11, 8),
(12, 1),
(13, 9),
(14, 4),
(15, 4),
(16, 4),
(17, 2),
(17, 4),
(17, 10),
(18, 4),
(18, 6),
(19, 6),
(19, 11),
(20, 2),
(20, 11),
(21, 5),
(21, 7),
(22, 5),
(22, 8),
(23, 5),
(24, 6),
(24, 8),
(24, 12),
(25, 6),
(26, 2),
(26, 12),
(27, 1),
(27, 2),
(27, 4),
(28, 3),
(28, 6),
(28, 14),
(29, 2),
(29, 11),
(29, 13),
(30, 5),
(31, 2),
(31, 6),
(32, 6),
(32, 15),
(32, 16);

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
  `fecha_alta` date NOT NULL,
  `avatar_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

-- Datos de usuarios omitidos para proteger credenciales e información personal.

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `intentos_login`
--

CREATE TABLE IF NOT EXISTS `intentos_login` (
  `clave_intento` char(64) NOT NULL,
  `intentos_fallidos` tinyint unsigned NOT NULL DEFAULT 0,
  `inicio_ventana` bigint unsigned NOT NULL,
  `bloqueado_hasta` bigint unsigned DEFAULT NULL,
  `actualizado_en` bigint unsigned NOT NULL,
  PRIMARY KEY (`clave_intento`),
  KEY `idx_intentos_login_actualizado` (`actualizado_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `visualizaciones`
--

CREATE TABLE `visualizaciones` (
  `id_visualizacion` int(11) NOT NULL,
  `id_trailer` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `fecha_visualizacion` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_direccion` varchar(45) DEFAULT NULL,
  `dispositivo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `visualizaciones`
--

-- Historial de visualizaciones omitido para proteger datos de actividad.

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `badges`
--

CREATE TABLE `badges` (
  `id_badge` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `requisito_tipo` varchar(50) NOT NULL,
  `requisito_valor` int(11) NOT NULL,
  `icono` varchar(100) NOT NULL,
  PRIMARY KEY (`id_badge`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Los badges se inicializan de forma idempotente desde la aplicacion.

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_badges`
--

CREATE TABLE `usuario_badges` (
  `id_usuario` int(11) NOT NULL,
  `id_badge` int(11) NOT NULL,
  `fecha_desbloqueo` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`,`id_badge`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insignias desbloqueadas omitidas para proteger datos de actividad.

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_rachas`
--

CREATE TABLE `usuario_rachas` (
  `id_usuario` int(11) NOT NULL,
  `fecha_ultimo_login` date NOT NULL,
  `racha_actual` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Rachas de usuarios omitidas para proteger datos de actividad.

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_gamificacion_stats`
--

CREATE TABLE `usuario_gamificacion_stats` (
  `id_usuario` int(11) NOT NULL,
  `modo_cine_activado` tinyint(4) DEFAULT 0,
  `intentos_fallidos_admin` tinyint(4) DEFAULT 0,
  `busquedas_fecha_actual` tinyint(4) DEFAULT 0,
  `registro_invitacion` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estadisticas de gamificacion omitidas para proteger datos de actividad.

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_lectura_resenas`
--

CREATE TABLE `usuario_lectura_resenas` (
  `id_usuario` int(11) NOT NULL,
  `id_trailer` int(11) NOT NULL,
  `fecha_lectura` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`,`id_trailer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Lecturas de resenas omitidas para proteger datos de actividad.

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `listas_personales`
--

CREATE TABLE `listas_personales` (
  `id_lista` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_trailer` int(11) NOT NULL,
  `estado` varchar(20) NOT NULL,
  `fecha_adicion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_lista`),
  UNIQUE KEY `uq_usuario_trailer_lista` (`id_usuario`,`id_trailer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Listas personales omitidas para proteger datos de usuario.

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios_privados`
--

CREATE TABLE `comentarios_privados` (
  `id_comentario_privado` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_trailer` int(11) NOT NULL,
  `comentario` text NOT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_comentario_privado`),
  UNIQUE KEY `uq_usuario_trailer_comentario` (`id_usuario`,`id_trailer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Comentarios privados omitidos para proteger datos de usuario.

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_comentarios_privados`
--

CREATE TABLE `historial_comentarios_privados` (
  `id_historial` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_trailer` int(11) NOT NULL,
  `comentario_anterior` text NOT NULL,
  `fecha_cambio` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_historial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Historial de comentarios privados omitido para proteger datos de usuario.

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resenas`
--

CREATE TABLE `resenas` (
  `id_resena` int(11) NOT NULL AUTO_INCREMENT,
  `id_trailer` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `valoracion` decimal(2,1) NOT NULL,
  `comentario` text DEFAULT NULL,
  `fecha_alta` datetime DEFAULT current_timestamp(),
  `estado` varchar(20) NOT NULL DEFAULT 'aprobada',
  PRIMARY KEY (`id_resena`),
  UNIQUE KEY `uq_trailer_usuario` (`id_trailer`,`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Resenas omitidas para proteger datos de usuario.

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migraciones_esquema`
--

CREATE TABLE `migraciones_esquema` (
  `version` int unsigned NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `aplicada_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `directores`
--
ALTER TABLE `directores`
  ADD PRIMARY KEY (`id_director`);

--
-- Indices de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`id_usuario`,`id_trailer`),
  ADD KEY `id_trailer` (`id_trailer`),
  ADD KEY `idx_favoritos_usuario_fecha` (`id_usuario`,`fecha_adicion`);

--
-- Indices de la tabla `generos`
--
ALTER TABLE `generos`
  ADD PRIMARY KEY (`id_genero`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `reparto`
--
ALTER TABLE `reparto`
  ADD PRIMARY KEY (`id_reparto`);

--
-- Indices de la tabla `reparto_trailers`
--
ALTER TABLE `reparto_trailers`
  ADD PRIMARY KEY (`id_reparto_trailer`),
  ADD KEY `id_trailer` (`id_trailer`),
  ADD KEY `id_reparto` (`id_reparto`);

--
-- Indices de la tabla `trailers`
--
ALTER TABLE `trailers`
  ADD PRIMARY KEY (`id_trailer`),
  ADD KEY `fk_trailers_directores` (`id_director`),
  ADD KEY `idx_trailers_release_id` (`release_date`,`id_trailer`);

--
-- Indices de la tabla `trailers_generos`
--
ALTER TABLE `trailers_generos`
  ADD PRIMARY KEY (`id_trailer`,`id_genero`),
  ADD KEY `fk_trailers_generos_generos` (`id_genero`);

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
  ADD KEY `idx_visualizaciones_trailer` (`id_trailer`),
  ADD KEY `idx_visualizaciones_usuario` (`id_usuario`),
  ADD KEY `idx_visualizaciones_usuario_fecha` (`id_usuario`,`fecha_visualizacion`),
  ADD KEY `idx_visualizaciones_usuario_trailer_fecha` (`id_usuario`,`id_trailer`,`fecha_visualizacion`);

--
-- Indices de la tabla `listas_personales`
--
ALTER TABLE `listas_personales`
  ADD KEY `idx_listas_usuario_fecha` (`id_usuario`,`fecha_adicion`);

--
-- Indices de la tabla `comentarios_privados`
--
ALTER TABLE `comentarios_privados`
  ADD KEY `idx_comentarios_privados_usuario_id` (`id_usuario`,`id_comentario_privado`);

--
-- Indices de la tabla `historial_comentarios_privados`
--
ALTER TABLE `historial_comentarios_privados`
  ADD KEY `idx_historial_usuario_trailer_fecha` (`id_usuario`,`id_trailer`,`fecha_cambio`);

--
-- Indices de la tabla `resenas`
--
ALTER TABLE `resenas`
  ADD KEY `idx_resenas_trailer_fecha` (`id_trailer`,`fecha_alta`),
  ADD KEY `idx_resenas_usuario_fecha` (`id_usuario`,`fecha_alta`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `directores`
--
ALTER TABLE `directores`
  MODIFY `id_director` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `generos`
--
ALTER TABLE `generos`
  MODIFY `id_genero` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `reparto`
--
ALTER TABLE `reparto`
  MODIFY `id_reparto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;

--
-- AUTO_INCREMENT de la tabla `reparto_trailers`
--
ALTER TABLE `reparto_trailers`
  MODIFY `id_reparto_trailer` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=227;

--
-- AUTO_INCREMENT de la tabla `trailers`
--
ALTER TABLE `trailers`
  MODIFY `id_trailer` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `visualizaciones`
--
ALTER TABLE `visualizaciones`
  MODIFY `id_visualizacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `fk_favoritos_trailers` FOREIGN KEY (`id_trailer`) REFERENCES `trailers` (`id_trailer`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_favoritos_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `reparto_trailers`
--
ALTER TABLE `reparto_trailers`
  ADD CONSTRAINT `fk_reparto_trailers_reparto` FOREIGN KEY (`id_reparto`) REFERENCES `reparto` (`id_reparto`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reparto_trailers_trailers` FOREIGN KEY (`id_trailer`) REFERENCES `trailers` (`id_trailer`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `trailers`
--
ALTER TABLE `trailers`
  ADD CONSTRAINT `fk_trailers_directores` FOREIGN KEY (`id_director`) REFERENCES `directores` (`id_director`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `trailers_generos`
--
ALTER TABLE `trailers_generos`
  ADD CONSTRAINT `fk_trailers_generos_generos` FOREIGN KEY (`id_genero`) REFERENCES `generos` (`id_genero`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_trailers_generos_trailers` FOREIGN KEY (`id_trailer`) REFERENCES `trailers` (`id_trailer`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `visualizaciones`
--
ALTER TABLE `visualizaciones`
  ADD CONSTRAINT `fk_visualizaciones_trailers` FOREIGN KEY (`id_trailer`) REFERENCES `trailers` (`id_trailer`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_visualizaciones_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restricciones para la tabla `usuario_badges`
--
ALTER TABLE `usuario_badges`
  ADD CONSTRAINT `fk_ub_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ub_badges` FOREIGN KEY (`id_badge`) REFERENCES `badges` (`id_badge`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restricciones para la tabla `usuario_rachas`
--
ALTER TABLE `usuario_rachas`
  ADD CONSTRAINT `fk_ur_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restricciones para la tabla `usuario_gamificacion_stats`
--
ALTER TABLE `usuario_gamificacion_stats`
  ADD CONSTRAINT `fk_ugs_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restricciones para la tabla `usuario_lectura_resenas`
--
ALTER TABLE `usuario_lectura_resenas`
  ADD CONSTRAINT `fk_ulr_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ulr_trailers` FOREIGN KEY (`id_trailer`) REFERENCES `trailers` (`id_trailer`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restricciones para la tabla `listas_personales`
--
ALTER TABLE `listas_personales`
  ADD CONSTRAINT `fk_listas_trailers` FOREIGN KEY (`id_trailer`) REFERENCES `trailers` (`id_trailer`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_listas_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Restricciones para la tabla `comentarios_privados`
--
ALTER TABLE `comentarios_privados`
  ADD CONSTRAINT `fk_comentarios_priv_trailers` FOREIGN KEY (`id_trailer`) REFERENCES `trailers` (`id_trailer`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comentarios_priv_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Restricciones para la tabla `historial_comentarios_privados`
--
ALTER TABLE `historial_comentarios_privados`
  ADD CONSTRAINT `fk_historial_trailers` FOREIGN KEY (`id_trailer`) REFERENCES `trailers` (`id_trailer`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_historial_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Restricciones para la tabla `resenas`
--
ALTER TABLE `resenas`
  ADD CONSTRAINT `fk_resenas_trailers` FOREIGN KEY (`id_trailer`) REFERENCES `trailers` (`id_trailer`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resenas_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Versión inicial del esquema consolidado
--
INSERT INTO `migraciones_esquema` (`version`, `nombre`)
VALUES (1, 'Estructura base consolidada');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
