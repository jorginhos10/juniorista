-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 19-09-2023 a las 04:25:34
-- Versión del servidor: 10.4.24-MariaDB
-- Versión de PHP: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `bendey`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulo`
--

CREATE TABLE `articulo` (
  `idarticulo` int(11) NOT NULL,
  `idcategoria` int(11) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `descripcion` varchar(256) DEFAULT NULL,
  `imagen` varchar(50) DEFAULT NULL,
  `condicion` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `articulo`
--

INSERT INTO `articulo` (`idarticulo`, `idcategoria`, `codigo`, `nombre`, `stock`, `descripcion`, `imagen`, `condicion`) VALUES
(1, 1, 'e43ref', 'teclado', 17, 'teclado gamer', '1692071813.png', 1),
(2, 1, '6575uty', 'parlante', 17, 'alto parlante', '1692071748.png', 1),
(3, 1, '7894646', 'articulo de prueba', 29, 'teclado gamer', 'default.png', 1),
(4, 1, 'e43ref', 'PARLANTES', 18, 'GAMER', 'default.png', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `idcategoria` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(256) DEFAULT NULL,
  `condicion` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `categoria`
--

INSERT INTO `categoria` (`idcategoria`, `nombre`, `descripcion`, `condicion`) VALUES
(1, 'categoria1', 'descripcion de la categoria', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comp_pago`
--

CREATE TABLE `comp_pago` (
  `id_comp_pago` int(11) NOT NULL,
  `nombre` varchar(45) NOT NULL,
  `letra_serie` varchar(3) NOT NULL,
  `serie_comprobante` varchar(3) NOT NULL,
  `num_comprobante` varchar(7) NOT NULL,
  `condicion` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `comp_pago`
--

INSERT INTO `comp_pago` (`id_comp_pago`, `nombre`, `letra_serie`, `serie_comprobante`, `num_comprobante`, `condicion`) VALUES
(6, 'Factura', 'F', '001', '5', 1),
(7, 'Boleta', 'B', '001', '0000001', 1),
(8, 'Ticket', 'T', '001', '0000002', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `datos_negocio`
--

CREATE TABLE `datos_negocio` (
  `id_negocio` int(11) NOT NULL,
  `nombre` varchar(80) CHARACTER SET utf8 NOT NULL,
  `ndocumento` varchar(20) NOT NULL,
  `documento` int(11) NOT NULL,
  `direccion` varchar(100) CHARACTER SET utf8 NOT NULL,
  `telefono` int(20) NOT NULL,
  `email` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
  `logo` varchar(50) CHARACTER SET utf8 DEFAULT NULL,
  `pais` varchar(50) CHARACTER SET utf8 DEFAULT NULL,
  `ciudad` varchar(50) CHARACTER SET utf8 DEFAULT NULL,
  `nombre_impuesto` varchar(10) NOT NULL,
  `monto_impuesto` float(4,2) NOT NULL,
  `moneda` varchar(10) NOT NULL,
  `simbolo` varchar(10) NOT NULL,
  `condicion` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `datos_negocio`
--

INSERT INTO `datos_negocio` (`id_negocio`, `nombre`, `ndocumento`, `documento`, `direccion`, `telefono`, `email`, `logo`, `pais`, `ciudad`, `nombre_impuesto`, `monto_impuesto`, `moneda`, `simbolo`, `condicion`) VALUES
(1, 'CompartiendoCódigos', 'RUC', 2332112, 'Monjitas 550 - Of.19', 993321337, 'victor@conceptoweb.cl', '1695005764.png', 'Perú', 'Lima', 'IGV', 18.00, 'SOLES', '$/.', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ingreso`
--

CREATE TABLE `detalle_ingreso` (
  `iddetalle_ingreso` int(11) NOT NULL,
  `idingreso` int(11) NOT NULL,
  `idarticulo` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_compra` decimal(11,2) NOT NULL,
  `precio_venta` decimal(11,2) NOT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `detalle_ingreso`
--

INSERT INTO `detalle_ingreso` (`iddetalle_ingreso`, `idingreso`, `idarticulo`, `cantidad`, `precio_compra`, `precio_venta`, `estado`) VALUES
(1, 1, 1, 100, '15.00', '20.00', 0),
(2, 1, 2, 200, '20.00', '25.00', 0),
(3, 1, 3, 300, '25.00', '30.00', 0),
(4, 2, 1, 100, '25.00', '35.00', 0),
(6, 3, 3, 20, '25.00', '30.00', 1),
(7, 4, 1, 20, '20.00', '25.00', 1),
(8, 4, 2, 20, '20.00', '25.00', 1),
(9, 4, 4, 20, '20.00', '25.00', 1),
(10, 4, 3, 20, '20.00', '25.00', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `iddetalle_venta` int(11) NOT NULL,
  `idventa` int(11) NOT NULL,
  `idarticulo` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_compra` decimal(11,2) NOT NULL,
  `precio_venta` decimal(11,2) NOT NULL,
  `descuento` decimal(11,2) NOT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `detalle_venta`
--

INSERT INTO `detalle_venta` (`iddetalle_venta`, `idventa`, `idarticulo`, `cantidad`, `precio_compra`, `precio_venta`, `descuento`, `estado`) VALUES
(1, 1, 1, 10, '15.00', '20.00', '0.00', 0),
(2, 2, 2, 20, '20.00', '25.00', '0.00', 0),
(3, 3, 1, 10, '15.00', '35.00', '0.00', 0),
(4, 4, 3, 5, '25.00', '30.00', '0.00', 1),
(7, 5, 3, 5, '0.00', '30.00', '25.00', 1),
(8, 6, 4, 2, '20.00', '25.00', '5.00', 1),
(9, 6, 3, 1, '25.00', '25.00', '0.00', 1),
(10, 6, 2, 1, '20.00', '25.00', '0.00', 1),
(11, 6, 1, 1, '15.00', '25.00', '0.00', 1),
(12, 7, 2, 1, '20.00', '25.00', '0.00', 1),
(13, 7, 1, 1, '15.00', '25.00', '0.00', 1),
(14, 8, 1, 1, '15.00', '25.00', '0.00', 1),
(15, 8, 2, 1, '20.00', '25.00', '0.00', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingreso`
--

CREATE TABLE `ingreso` (
  `idingreso` int(11) NOT NULL,
  `idproveedor` int(11) NOT NULL,
  `idusuario` int(11) DEFAULT NULL,
  `tipo_comprobante` varchar(20) NOT NULL,
  `serie_comprobante` varchar(7) DEFAULT NULL,
  `num_comprobante` varchar(10) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `impuesto` decimal(4,2) NOT NULL,
  `total_compra` decimal(11,2) NOT NULL,
  `estado` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `ingreso`
--

INSERT INTO `ingreso` (`idingreso`, `idproveedor`, `idusuario`, `tipo_comprobante`, `serie_comprobante`, `num_comprobante`, `fecha_hora`, `impuesto`, `total_compra`, `estado`) VALUES
(1, 1, 1, 'Factura', '15003', '0000002', '2023-09-16 00:00:00', '0.00', '13000.00', 'Anulado'),
(2, 1, 1, 'Factura', '15003', '0000002', '2023-09-16 00:00:00', '0.00', '2500.00', 'Anulado'),
(3, 1, 1, 'Factura', '15003', '4', '2023-09-16 00:00:00', '0.00', '500.00', 'Aceptado'),
(4, 1, 1, 'Boleta', '1', '0000002', '2023-09-17 00:00:00', '0.00', '1600.00', 'Aceptado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permiso`
--

CREATE TABLE `permiso` (
  `idpermiso` int(11) NOT NULL,
  `nombre` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `permiso`
--

INSERT INTO `permiso` (`idpermiso`, `nombre`) VALUES
(1, 'Escritorio'),
(2, 'Almacen'),
(3, 'Compras'),
(4, 'Ventas'),
(5, 'Acceso'),
(6, 'Consulta Compras'),
(7, 'Consulta Ventas'),
(8, 'Configuracion');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `persona`
--

CREATE TABLE `persona` (
  `idpersona` int(11) NOT NULL,
  `tipo_persona` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo_documento` varchar(20) DEFAULT NULL,
  `num_documento` varchar(20) DEFAULT NULL,
  `direccion` varchar(70) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `persona`
--

INSERT INTO `persona` (`idpersona`, `tipo_persona`, `nombre`, `tipo_documento`, `num_documento`, `direccion`, `telefono`, `email`) VALUES
(1, 'Proveedor', 'proveedor1', 'DNI', '12345678', 'calle nueva', '987456321', 'proveedor@gmail.com'),
(2, 'Cliente', 'cliente1', 'DNI', '3214566', 'calle nueva', '963258741', 'cliente@cliente.com'),
(5, 'Cliente', 'cliente 2', 'DNI', '76329102', 'Calle los alpes 210', '45230796', 'jaime@gmail.com'),
(6, 'Cliente', 'cliente 3', 'DNI', '1546548', 'calle piura', '123154', 'cliente@cliente.com');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `idusuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo_documento` varchar(20) NOT NULL,
  `num_documento` varchar(20) NOT NULL,
  `direccion` varchar(70) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `cargo` varchar(20) DEFAULT NULL,
  `login` varchar(20) NOT NULL,
  `clave` varchar(64) NOT NULL,
  `imagen` varchar(50) NOT NULL,
  `descripcion` varchar(254) NOT NULL,
  `biografia` text NOT NULL,
  `condicion` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`idusuario`, `nombre`, `tipo_documento`, `num_documento`, `direccion`, `telefono`, `email`, `cargo`, `login`, `clave`, `imagen`, `descripcion`, `biografia`, `condicion`) VALUES
(1, 'CompartiendoCódigos', ' DNI', '30221540', 'Calle los alpes 210', '952555984', 'admin@gmail.com', 'Administrador', 'admin', '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918', '1628040701.jpg', 'Encargado de administrar el sistema de ventas con permisos de control de todo los módulos', 'Desarrollo de sistemas web', 1),
(5, 'juan', ' DNI', '12345678', 'Calle Octavio Muñoz Najar 220', '223366', 'info@compartiendocodigos.com', 'Gerente', 'juan', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', '1615405929.jpg', '', '', 1),
(6, 'Administrador', 'RUC', '72618793', 'Calle Octavio Muñoz Najar 220', '967047441', 'admin@gmail.com', 'Asesor', 'mejia', '835d6dc88b708bc646d6db82c853ef4182fabbd4a8de59c213f2b5ab3ae7d9be', '1615405857.jpg', '', '', 1),
(9, 'Ticket', 'CEDULA', '72618793', 'Calle los alpes 210', '223366', 'admin@gmail.com', 'Asesor comercial', 'luz', 'c6ce5f796115921afe158021f045e7c6d6820383191907ff6add8b3f502082a1', '1615405090.jpg', '', '', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_permiso`
--

CREATE TABLE `usuario_permiso` (
  `idusuario_permiso` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `idpermiso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `usuario_permiso`
--

INSERT INTO `usuario_permiso` (`idusuario_permiso`, `idusuario`, `idpermiso`) VALUES
(84, 1, 1),
(85, 1, 2),
(86, 1, 3),
(87, 1, 4),
(88, 1, 5),
(89, 1, 6),
(90, 1, 7),
(91, 1, 8),
(92, 9, 1),
(93, 9, 2),
(94, 9, 3),
(95, 9, 4),
(96, 9, 5),
(97, 5, 1),
(98, 5, 2),
(99, 5, 3),
(100, 5, 4),
(101, 5, 5),
(102, 5, 6),
(103, 5, 7),
(104, 5, 8);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta`
--

CREATE TABLE `venta` (
  `idventa` int(11) NOT NULL,
  `idcliente` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `tipo_comprobante` varchar(45) NOT NULL,
  `serie_comprobante` varchar(7) DEFAULT NULL,
  `num_comprobante` varchar(10) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `impuesto` decimal(4,2) DEFAULT NULL,
  `total_venta` decimal(11,2) DEFAULT NULL,
  `tipo_pago` varchar(45) NOT NULL,
  `num_transac` varchar(45) DEFAULT NULL,
  `estado` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `venta`
--

INSERT INTO `venta` (`idventa`, `idcliente`, `idusuario`, `tipo_comprobante`, `serie_comprobante`, `num_comprobante`, `fecha_hora`, `impuesto`, `total_venta`, `tipo_pago`, `num_transac`, `estado`) VALUES
(1, 2, 1, 'Factura', '001', '0000006', '2023-09-16 00:00:00', '0.00', '200.00', 'Efectivo', '', 'Anulado'),
(2, 2, 1, 'Ticket', '001', '0000003', '2023-09-16 00:00:00', '0.00', '500.00', 'Efectivo', '', 'Anulado'),
(3, 2, 1, 'Factura', '001', '0000007', '2023-09-16 00:00:00', '0.00', '350.00', 'Efectivo', '', 'Anulado'),
(4, 6, 1, 'Factura', '001', '0000008', '2023-09-16 00:00:00', '0.00', '150.00', 'Efectivo', '', 'Aceptado'),
(5, 5, 1, 'Boleta', '001', '0000002', '2023-09-16 00:00:00', '0.00', '125.00', 'Depósito', '587421', 'Aceptado'),
(6, 6, 1, 'Factura', '001', '0000009', '2023-09-17 00:00:00', '18.00', '141.60', 'Depósito', '777', 'Aceptado'),
(7, 2, 1, 'Factura', '001', '0000010', '2023-09-17 00:00:00', '18.00', '59.00', 'Depósito', '555', 'Aceptado'),
(8, 6, 1, 'Ticket', '001', '0000004', '2023-09-17 00:00:00', '0.00', '59.00', 'Efectivo', '', 'Aceptado');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `articulo`
--
ALTER TABLE `articulo`
  ADD PRIMARY KEY (`idarticulo`),
  ADD UNIQUE KEY `nombre_UNIQUE` (`nombre`),
  ADD KEY `fk_articulo_categoria_idx` (`idcategoria`);

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`idcategoria`),
  ADD UNIQUE KEY `nombre_UNIQUE` (`nombre`);

--
-- Indices de la tabla `comp_pago`
--
ALTER TABLE `comp_pago`
  ADD PRIMARY KEY (`id_comp_pago`);

--
-- Indices de la tabla `datos_negocio`
--
ALTER TABLE `datos_negocio`
  ADD PRIMARY KEY (`id_negocio`);

--
-- Indices de la tabla `detalle_ingreso`
--
ALTER TABLE `detalle_ingreso`
  ADD PRIMARY KEY (`iddetalle_ingreso`),
  ADD KEY `fk_detalle_ingreso_idx` (`idingreso`),
  ADD KEY `fk_detalle_articulo_idx` (`idarticulo`);

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`iddetalle_venta`),
  ADD KEY `fk_detalle_venta_venta_idx` (`idventa`),
  ADD KEY `fk_detalle_venta_articulo_idx` (`idarticulo`);

--
-- Indices de la tabla `ingreso`
--
ALTER TABLE `ingreso`
  ADD PRIMARY KEY (`idingreso`),
  ADD KEY `fk_ingreso_persona_idx` (`idproveedor`),
  ADD KEY `fk_ingreso_usuario_idx` (`idusuario`);

--
-- Indices de la tabla `permiso`
--
ALTER TABLE `permiso`
  ADD PRIMARY KEY (`idpermiso`);

--
-- Indices de la tabla `persona`
--
ALTER TABLE `persona`
  ADD PRIMARY KEY (`idpersona`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`idusuario`),
  ADD UNIQUE KEY `login_UNIQUE` (`login`);

--
-- Indices de la tabla `usuario_permiso`
--
ALTER TABLE `usuario_permiso`
  ADD PRIMARY KEY (`idusuario_permiso`),
  ADD KEY `fk_u_permiso_usuario_idx` (`idusuario`),
  ADD KEY `fk_usuario_permiso_idx` (`idpermiso`);

--
-- Indices de la tabla `venta`
--
ALTER TABLE `venta`
  ADD PRIMARY KEY (`idventa`),
  ADD KEY `fk_venta_persona_idx` (`idcliente`),
  ADD KEY `fk_venta_usuario_idx` (`idusuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `articulo`
--
ALTER TABLE `articulo`
  MODIFY `idarticulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `idcategoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `comp_pago`
--
ALTER TABLE `comp_pago`
  MODIFY `id_comp_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `datos_negocio`
--
ALTER TABLE `datos_negocio`
  MODIFY `id_negocio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `detalle_ingreso`
--
ALTER TABLE `detalle_ingreso`
  MODIFY `iddetalle_ingreso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `iddetalle_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `ingreso`
--
ALTER TABLE `ingreso`
  MODIFY `idingreso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `permiso`
--
ALTER TABLE `permiso`
  MODIFY `idpermiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `persona`
--
ALTER TABLE `persona`
  MODIFY `idpersona` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `idusuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `usuario_permiso`
--
ALTER TABLE `usuario_permiso`
  MODIFY `idusuario_permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT de la tabla `venta`
--
ALTER TABLE `venta`
  MODIFY `idventa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `articulo`
--
ALTER TABLE `articulo`
  ADD CONSTRAINT `fk_articulo_categoria` FOREIGN KEY (`idcategoria`) REFERENCES `categoria` (`idcategoria`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `detalle_ingreso`
--
ALTER TABLE `detalle_ingreso`
  ADD CONSTRAINT `fk_detalle_articulo` FOREIGN KEY (`idarticulo`) REFERENCES `articulo` (`idarticulo`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_detalle_ingreso` FOREIGN KEY (`idingreso`) REFERENCES `ingreso` (`idingreso`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `fk_detalle_venta_articulo` FOREIGN KEY (`idarticulo`) REFERENCES `articulo` (`idarticulo`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_detalle_venta_venta` FOREIGN KEY (`idventa`) REFERENCES `venta` (`idventa`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `ingreso`
--
ALTER TABLE `ingreso`
  ADD CONSTRAINT `fk_ingreso_persona` FOREIGN KEY (`idproveedor`) REFERENCES `persona` (`idpersona`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_ingreso_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuario` (`idusuario`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `usuario_permiso`
--
ALTER TABLE `usuario_permiso`
  ADD CONSTRAINT `fk_u_permiso_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuario` (`idusuario`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_usuario_permiso` FOREIGN KEY (`idpermiso`) REFERENCES `permiso` (`idpermiso`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `venta`
--
ALTER TABLE `venta`
  ADD CONSTRAINT `fk_venta_persona` FOREIGN KEY (`idcliente`) REFERENCES `persona` (`idpersona`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_venta_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuario` (`idusuario`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
