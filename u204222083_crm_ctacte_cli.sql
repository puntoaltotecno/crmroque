-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 07-04-2026 a las 01:50:58
-- Versión del servidor: 11.8.6-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u204222083_crm_ctacte_cli`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones`
--

CREATE TABLE `asignaciones` (
  `id` int(11) NOT NULL,
  `legajo` varchar(50) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `asignado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `l_entidad_id` int(11) DEFAULT NULL,
  `legajo` varchar(50) NOT NULL,
  `razon_social` varchar(150) NOT NULL,
  `nro_documento` varchar(50) DEFAULT NULL,
  `ultimo_pago` date DEFAULT NULL,
  `c_cuotas` int(11) DEFAULT 0,
  `localidad` varchar(100) DEFAULT NULL,
  `domicilio` varchar(200) DEFAULT NULL,
  `dias_atraso` int(11) DEFAULT 0,
  `total_vencido` decimal(10,2) DEFAULT 0.00,
  `vencimiento` date DEFAULT NULL,
  `sucursal` varchar(100) DEFAULT NULL,
  `telefonos` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comunicados`
--

CREATE TABLE `comunicados` (
  `id` int(11) NOT NULL,
  `usuario_destino_id` int(11) DEFAULT NULL,
  `mensaje` text NOT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `gestiones`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `gestiones` (
`legajo` varchar(50)
,`razon_social` varchar(150)
,`nro_documento` varchar(50)
,`ultimo_pago` date
,`c_cuotas` int(11)
,`usuario_id` int(11)
,`id` int(11)
,`sucursal` varchar(100)
,`nombre` varchar(100)
,`fecha_gestion` datetime
,`rn` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gestiones_historial`
--

CREATE TABLE `gestiones_historial` (
  `id` int(11) NOT NULL,
  `legajo` varchar(50) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `estado` enum('promesa','no_responde','no_corresponde','llamar','numero_baja','otro','al_dia','carta') DEFAULT 'promesa',
  `fecha_promesa` date DEFAULT NULL,
  `monto_promesa` decimal(10,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_gestion` datetime NOT NULL,
  `intentos` int(11) DEFAULT 0,
  `oculta` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','operador','colaborador') NOT NULL DEFAULT 'operador',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `legajo_asignado_unico` (`legajo`),
  ADD KEY `fk_usuario_asignacion` (`usuario_id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `legajo_unico` (`legajo`),
  ADD UNIQUE KEY `l_entidad_unico` (`l_entidad_id`);

--
-- Indices de la tabla `comunicados`
--
ALTER TABLE `comunicados`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `gestiones_historial`
--
ALTER TABLE `gestiones_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_legajo_gestion` (`legajo`),
  ADD KEY `fk_usuario_gestion` (`usuario_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_unico` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `comunicados`
--
ALTER TABLE `comunicados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `gestiones_historial`
--
ALTER TABLE `gestiones_historial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Estructura para la vista `gestiones`
--
DROP TABLE IF EXISTS `gestiones`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u204222083_roque`@`181.104.4.107` SQL SECURITY DEFINER VIEW `gestiones`  AS SELECT `t`.`legajo` AS `legajo`, `t`.`razon_social` AS `razon_social`, `t`.`nro_documento` AS `nro_documento`, `t`.`ultimo_pago` AS `ultimo_pago`, `t`.`c_cuotas` AS `c_cuotas`, `t`.`usuario_id` AS `usuario_id`, `t`.`id` AS `id`, `t`.`sucursal` AS `sucursal`, `t`.`nombre` AS `nombre`, `t`.`fecha_gestion` AS `fecha_gestion`, `t`.`rn` AS `rn` FROM (select `gh`.`legajo` AS `legajo`,`c`.`razon_social` AS `razon_social`,`c`.`nro_documento` AS `nro_documento`,`c`.`ultimo_pago` AS `ultimo_pago`,`c`.`c_cuotas` AS `c_cuotas`,`gh`.`usuario_id` AS `usuario_id`,`u`.`id` AS `id`,`c`.`sucursal` AS `sucursal`,`u`.`nombre` AS `nombre`,`gh`.`fecha_gestion` AS `fecha_gestion`,row_number() over ( partition by `gh`.`legajo` order by `gh`.`fecha_gestion` desc) AS `rn` from ((`gestiones_historial` `gh` join `clientes` `c` on(`gh`.`legajo` = `c`.`legajo`)) join `usuarios` `u` on(`gh`.`usuario_id` = `u`.`id`))) AS `t` WHERE `t`.`rn` = 1 ORDER BY `t`.`legajo` ASC ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  ADD CONSTRAINT `fk_legajo_asignacion` FOREIGN KEY (`legajo`) REFERENCES `clientes` (`legajo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuario_asignacion` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `gestiones_historial`
--
ALTER TABLE `gestiones_historial`
  ADD CONSTRAINT `fk_legajo_gestion` FOREIGN KEY (`legajo`) REFERENCES `clientes` (`legajo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuario_gestion` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
