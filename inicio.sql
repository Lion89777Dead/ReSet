-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 13-08-2025 a las 17:49:07
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
-- Base de datos: `inicio`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones_juegos`
--

CREATE TABLE `asignaciones_juegos` (
  `id` int(11) NOT NULL,
  `id_nino` int(11) NOT NULL,
  `juego` varchar(50) NOT NULL,
  `dificultad` int(11) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asignaciones_juegos`
--

INSERT INTO `asignaciones_juegos` (`id`, `id_nino`, `juego`, `dificultad`, `fecha_asignacion`) VALUES
(16, 3, 'memorama', 1, '2025-08-13 15:15:38'),
(17, 3, 'rompecabezas', 1, '2025-08-13 15:15:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluaciones_psicologicas`
--

CREATE TABLE `evaluaciones_psicologicas` (
  `id` int(11) NOT NULL,
  `id_nino` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `puntaje` int(11) NOT NULL,
  `conclusion` varchar(255) DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `evaluaciones_psicologicas`
--

INSERT INTO `evaluaciones_psicologicas` (`id`, `id_nino`, `fecha`, `puntaje`, `conclusion`, `observaciones`) VALUES
(1, 3, '2025-08-13', 100, 'Fue una exelente semana', 'Sus registros quedaron impecables'),
(2, 3, '2025-08-13', 100, 'Fue una exelente semana', 'aleluyaaa'),
(3, 3, '2025-08-13', 100, 'Fue una exelente semana', 'aleluyaaa'),
(4, 3, '2025-08-13', 100, 'Fue una exelente semana', 'aleluyaaa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inicio`
--

CREATE TABLE `inicio` (
  `ID` int(11) NOT NULL,
  `Usuario` varchar(100) NOT NULL,
  `Contrasena` varchar(60) NOT NULL,
  `RFC` varchar(13) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inicio`
--

INSERT INTO `inicio` (`ID`, `Usuario`, `Contrasena`, `RFC`) VALUES
(1, 'Janemir', '$2y$10$wLfTL8urL2Hav5mzrjv6q.cv6k5a/IDavNXtvcrSGbqkNkQ0k84jS', ''),
(2, 'Sirdley', '$2y$10$niUivej6CgdzWFN9ciOBpuYxFK5jOpUFTf8pvPERnKKMTAIaaXD1i', ''),
(3, 'Sam', '$2y$10$kITnBCMwP69OW9vHOuy9d.ecHdiwdlhc3uDEuEqwasPHqT9Joty8i', ''),
(4, 'Alexa', '$2y$10$y0Ksh3bRl4Rp5bGcPLBJXOlGjKtu4ZzY0R3nJidhxTuVyEBoS6H7y', ''),
(5, 'Leo', '$2y$10$0o18FoKU.pNeMhqAqRI9pujNe.GMTq2qCBX.y4k9eNEJMR78LiBF2', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inicio_niños`
--

CREATE TABLE `inicio_niños` (
  `ID` int(11) NOT NULL,
  `Nombre` varchar(60) NOT NULL,
  `Contraseña` varchar(300) NOT NULL,
  `Genero` varchar(20) NOT NULL,
  `Edad` int(2) NOT NULL,
  `Padre` varchar(60) NOT NULL,
  `Madre` varchar(60) NOT NULL,
  `CURP` varchar(18) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inicio_niños`
--

INSERT INTO `inicio_niños` (`ID`, `Nombre`, `Contraseña`, `Genero`, `Edad`, `Padre`, `Madre`, `CURP`) VALUES
(3, 'Beto', '121416', 'Niño', 10, 'Axel', 'Abril', 'AURJ051216HHGGSNA1'),
(4, 'Pedro', '121416', 'Niño', 6, 'Axel', 'Abril', ''),
(5, 'Bryan', '121416', 'Niño', 15, 'Edwin', 'Abril', ''),
(6, 'Bryan', '121416', 'Niño', 15, 'Edwin', 'Abril', ''),
(7, 'Bryan', '121416', 'Niño', 15, 'Edwin', 'Abril', ''),
(8, 'Bryan', '121416', 'Niño', 15, 'Edwin', 'Abril', ''),
(9, 'Bryan', '121416', 'Niño', 15, 'Edwin', 'Abril', ''),
(10, 'Bryan', '121416', 'Niño', 15, 'Edwin', 'Abril', ''),
(11, 'Fer', '121416', 'Niña', 12, 'Edwin', 'Maria', 'AERJ850612HDFLRS09'),
(12, 'Fer', '121416', 'Niña', 12, 'Edwin', 'Maria', 'AERJ850612HDFLRS09'),
(13, 'Fer', '121416', 'Niña', 12, 'Edwin', 'Maria', 'AERJ850612HDFLRS09'),
(14, 'Fer', '121416', 'Niña', 12, 'Edwin', 'Maria', 'AERJ850612HDFLRS09'),
(15, 'Fer', '121416', 'Niña', 12, 'Edwin', 'Maria', 'AERJ850612HDFLRS09'),
(16, 'Adrian', '121416', 'Niño', 15, 'Edwin', 'Maria', 'AERJ850612HDFLRS09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inicio_padres`
--

CREATE TABLE `inicio_padres` (
  `ID` int(11) NOT NULL,
  `Nombre` varchar(60) NOT NULL,
  `Contraseña` varchar(40) NOT NULL,
  `Genero` varchar(20) NOT NULL,
  `Edad` int(2) NOT NULL,
  `Codigo_Postal` int(5) NOT NULL,
  `Telefono` int(10) NOT NULL,
  `Calle` varchar(30) NOT NULL,
  `Colonia` varchar(30) NOT NULL,
  `Numero_de_Vivienda` varchar(4) NOT NULL,
  `CURP` varchar(18) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inicio_padres`
--

INSERT INTO `inicio_padres` (`ID`, `Nombre`, `Contraseña`, `Genero`, `Edad`, `Codigo_Postal`, `Telefono`, `Calle`, `Colonia`, `Numero_de_Vivienda`, `CURP`) VALUES
(2, 'Axel', '121416', 'Padre', 25, 43630, 2147483647, 'Durango', 'Vicente Guerrero', '702', ''),
(4, 'Abril', '121416', 'Madre', 26, 43630, 2147483647, 'Durango', 'Vicente Guerrero', '702', ''),
(5, 'Edwin', '123456', 'Padre', 30, 43600, 2147483647, 'Doria', 'Vicente Guerrero', '702', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `juegos`
--

CREATE TABLE `juegos` (
  `ID` int(11) NOT NULL,
  `Nombre` varchar(50) NOT NULL,
  `Puntaje` int(3) NOT NULL,
  `Tiempo` time(6) DEFAULT NULL,
  `Dificultad` varchar(20) NOT NULL,
  `Juego` varchar(25) NOT NULL,
  `fecha_asignacion` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `juegos`
--

INSERT INTO `juegos` (`ID`, `Nombre`, `Puntaje`, `Tiempo`, `Dificultad`, `Juego`, `fecha_asignacion`) VALUES
(7, 'Beto', 740, '00:00:34.000000', '4', 'memorama', '2025-08-12'),
(8, 'Beto', 720, '00:00:48.000000', '4', 'memorama', '2025-08-12'),
(9, 'Beto', 225, '00:00:35.000000', '3', 'rompecabezas', '2025-08-13'),
(10, 'Beto', 225, '00:00:01.000000', '1', 'puzzle', NULL),
(11, 'Fer', 700, '00:00:02.000000', '2', 'memorama', NULL),
(12, 'Beto', 265, '00:00:31.000000', '3', 'rompecabezas', '2025-08-13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `niños`
--

CREATE TABLE `niños` (
  `ID` int(11) NOT NULL,
  `Nombre` varchar(60) NOT NULL,
  `Nota` text NOT NULL,
  `Dibujo` varchar(255) NOT NULL,
  `Fecha` date DEFAULT NULL,
  `Hora` time(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `niños`
--

INSERT INTO `niños` (`ID`, `Nombre`, `Nota`, `Dibujo`, `Fecha`, `Hora`) VALUES
(3, 'Beto', '[2025-08-12 16:19:55]\nbiennn\n---\n[2025-08-12 16:20:06]\nbiennn\n---\n[2025-08-12 16:20:50]\nbiennn\n---\n[2025-08-12 16:21:02]\nbiennn\n---\n[2025-08-12 16:23:24]\nbiennn\n---\n[2025-08-12 16:26:45]\nbiennn\n---\n[2025-08-12 16:27:47]\nbiennn\n---\n[2025-08-13 17:19:37]\nHoy es un buen día!!!\n---\n[2025-08-13 17:21:48]\nHoy es un buen día!!!\n---\n[2025-08-13 17:22:45]\nHoy es un buen día!!!\n---\n', 'diario/dibujos/dibujo_1755008401_689b4d918a6c9.png,diario/dibujos/dibujo_1755098501_689cad8595734.png', NULL, NULL),
(4, 'Pedro', '', '', NULL, NULL),
(5, 'Bryan', '', '', NULL, NULL),
(6, 'Bryan', '', '', NULL, NULL),
(7, 'Bryan', '', '', NULL, NULL),
(8, 'Bryan', '', '', NULL, NULL),
(9, 'Bryan', '', '', NULL, NULL),
(10, 'Bryan', '', '', NULL, NULL),
(11, 'Fer', '', '', NULL, NULL),
(12, 'Fer', '', '', NULL, NULL),
(13, 'Fer', '', '', NULL, NULL),
(14, 'Fer', '', '', NULL, NULL),
(15, 'Fer', '', '', NULL, NULL),
(16, 'Adrian', '', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `id_niños` int(11) DEFAULT NULL,
  `id_padres` int(11) DEFAULT NULL,
  `mensaje` text DEFAULT NULL,
  `leido` tinyint(1) DEFAULT 0,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `padres`
--

CREATE TABLE `padres` (
  `ID` int(11) NOT NULL,
  `Nombre` varchar(60) NOT NULL,
  `Notas` varchar(300) NOT NULL,
  `Fecha` date DEFAULT NULL,
  `Hora` time(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `padres`
--

INSERT INTO `padres` (`ID`, `Nombre`, `Notas`, `Fecha`, `Hora`) VALUES
(2, 'Axel', '[{\"texto\":\"Holaaa\",\"fecha\":1755098167,\"remitente\":\"Especialista\"},{\"texto\":\"hello\",\"fecha\":1755098624,\"remitente\":\"Padre\",\"respuesta\":\"\"},{\"texto\":\"Hiii\",\"fecha\":1755098698,\"remitente\":\"Padre\",\"respuesta\":\"\"}]', NULL, NULL),
(4, 'Abril', '', NULL, NULL),
(5, 'Edwin', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `relacion_nino_padre`
--

CREATE TABLE `relacion_nino_padre` (
  `id` int(11) NOT NULL,
  `id_niños` int(11) DEFAULT NULL,
  `id_padres` int(11) DEFAULT NULL,
  `tipo` varchar(25) NOT NULL,
  `mensaje` varchar(300) NOT NULL,
  `fecha` date NOT NULL,
  `leida` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `relacion_nino_padre`
--

INSERT INTO `relacion_nino_padre` (`id`, `id_niños`, `id_padres`, `tipo`, `mensaje`, `fecha`, `leida`) VALUES
(3, 6, 5, 'Padre', '', '0000-00-00', 0),
(4, 6, 4, 'Madre', '', '0000-00-00', 0),
(5, 7, 5, 'Padre', '', '0000-00-00', 0),
(11, 10, 5, 'Padre', '', '0000-00-00', 0),
(16, 14, 5, 'Padre', '', '0000-00-00', 0),
(17, 15, 5, 'Padre', '', '0000-00-00', 0),
(18, 16, 5, 'Padre', '', '0000-00-00', 0);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asignaciones_juegos`
--
ALTER TABLE `asignaciones_juegos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_nino` (`id_nino`);

--
-- Indices de la tabla `evaluaciones_psicologicas`
--
ALTER TABLE `evaluaciones_psicologicas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_nino` (`id_nino`);

--
-- Indices de la tabla `inicio`
--
ALTER TABLE `inicio`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `inicio_niños`
--
ALTER TABLE `inicio_niños`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `inicio_padres`
--
ALTER TABLE `inicio_padres`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `juegos`
--
ALTER TABLE `juegos`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `niños`
--
ALTER TABLE `niños`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_niños` (`id_niños`),
  ADD KEY `id_padres` (`id_padres`);

--
-- Indices de la tabla `padres`
--
ALTER TABLE `padres`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `relacion_nino_padre`
--
ALTER TABLE `relacion_nino_padre`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_niños` (`id_niños`),
  ADD KEY `id_padres` (`id_padres`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asignaciones_juegos`
--
ALTER TABLE `asignaciones_juegos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `evaluaciones_psicologicas`
--
ALTER TABLE `evaluaciones_psicologicas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `inicio`
--
ALTER TABLE `inicio`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `inicio_niños`
--
ALTER TABLE `inicio_niños`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `inicio_padres`
--
ALTER TABLE `inicio_padres`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `juegos`
--
ALTER TABLE `juegos`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `niños`
--
ALTER TABLE `niños`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `padres`
--
ALTER TABLE `padres`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `relacion_nino_padre`
--
ALTER TABLE `relacion_nino_padre`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asignaciones_juegos`
--
ALTER TABLE `asignaciones_juegos`
  ADD CONSTRAINT `asignaciones_juegos_ibfk_1` FOREIGN KEY (`id_nino`) REFERENCES `inicio_niños` (`ID`) ON DELETE CASCADE;

--
-- Filtros para la tabla `evaluaciones_psicologicas`
--
ALTER TABLE `evaluaciones_psicologicas`
  ADD CONSTRAINT `evaluaciones_psicologicas_ibfk_1` FOREIGN KEY (`id_nino`) REFERENCES `inicio_niños` (`ID`) ON DELETE CASCADE;

--
-- Filtros para la tabla `niños`
--
ALTER TABLE `niños`
  ADD CONSTRAINT `niños_ibfk_1` FOREIGN KEY (`ID`) REFERENCES `inicio_niños` (`ID`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_niños`) REFERENCES `inicio_niños` (`ID`),
  ADD CONSTRAINT `notificaciones_ibfk_2` FOREIGN KEY (`id_padres`) REFERENCES `inicio_padres` (`ID`);

--
-- Filtros para la tabla `relacion_nino_padre`
--
ALTER TABLE `relacion_nino_padre`
  ADD CONSTRAINT `relacion_nino_padre_ibfk_1` FOREIGN KEY (`id_niños`) REFERENCES `inicio_niños` (`ID`),
  ADD CONSTRAINT `relacion_nino_padre_ibfk_2` FOREIGN KEY (`id_padres`) REFERENCES `inicio_padres` (`ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
