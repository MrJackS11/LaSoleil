-- ===================================================
-- Base de Datos para Panadería y Repostería: La Soleil
-- ===================================================

CREATE DATABASE IF NOT EXISTS `lasoleil` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lasoleil`;

-- 1. Tabla de Productos
CREATE TABLE IF NOT EXISTS `productos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(255) NOT NULL,
    `descripcion` TEXT NOT NULL,
    `precio` DECIMAL(10,2) NOT NULL,
    `categoria` VARCHAR(100) NOT NULL,
    `stock` INT NOT NULL DEFAULT 0,
    `imagen` VARCHAR(255) NOT NULL DEFAULT 'default.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabla de Proveedores
CREATE TABLE IF NOT EXISTS `proveedores` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre_empresa` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabla de Ingredientes (Inventario)
CREATE TABLE IF NOT EXISTS `ingredientes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(255) NOT NULL,
    `unidad_medida` VARCHAR(50) NOT NULL,
    `stock_actual` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `stock_minimo` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `proveedor_id` INT NULL,
    CONSTRAINT `fk_ingredientes_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabla de Recetas (Relación producto - ingrediente)
CREATE TABLE IF NOT EXISTS `recetas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `producto_id` INT NOT NULL,
    `ingrediente_id` INT NOT NULL,
    `cantidad_necesaria` DECIMAL(10,2) NOT NULL,
    CONSTRAINT `fk_recetas_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_recetas_ingrediente` FOREIGN KEY (`ingrediente_id`) REFERENCES `ingredientes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabla de Ventas (Historial para estadísticas y reportes)
CREATE TABLE IF NOT EXISTS `ventas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `producto_id` INT NOT NULL,
    `cantidad` INT NOT NULL,
    `total` DECIMAL(10,2) NOT NULL,
    `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ventas_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================================
-- Datos iniciales (Semillas)
-- ===================================================

-- Proveedores iniciales
INSERT INTO `proveedores` (`id`, `nombre_empresa`) VALUES
(1, 'Molino Real S.A.'),
(2, 'Lácteos del Valle'),
(3, 'Distribuidora Dulce Azúcar')
ON DUPLICATE KEY UPDATE `nombre_empresa` = VALUES(`nombre_empresa`);

-- Ingredientes iniciales
INSERT INTO `ingredientes` (`id`, `nombre`, `unidad_medida`, `stock_actual`, `stock_minimo`, `proveedor_id`) VALUES
(1, 'Harina de Trigo', 'kg', 100.00, 20.00, 1),
(2, 'Levadura Fresca', 'kg', 15.00, 5.00, 1),
(3, 'Mantequilla Artesanal', 'kg', 25.00, 5.00, 2),
(4, 'Azúcar', 'kg', 50.00, 10.00, 3),
(5, 'Huevos', 'unidades', 120.00, 30.00, 2),
(6, 'Fresas Frescas', 'kg', 10.00, 3.00, 2),
(7, 'Crema de Leche', 'litros', 20.00, 5.00, 2)
ON DUPLICATE KEY UPDATE `stock_actual` = VALUES(`stock_actual`);

-- Productos con las imágenes ya existentes en el repositorio
INSERT INTO `productos` (`id`, `nombre`, `descripcion`, `precio`, `categoria`, `stock`, `imagen`) VALUES
(1, 'Pan Marraqueta', 'Pan tradicional boliviano, crocante por fuera y suave por dentro.', 0.80, 'Panes', 50, '1794327196_pan_marraqueta.png'),
(2, 'Pan Francés', 'Clásico pan francés dorado, ideal para acompañar el desayuno o la cena.', 0.80, 'Panes', 40, '1794327377_pan_frances.png'),
(3, 'Pan Cauca', 'Pan tradicional elaborado con manteca y queso fresco.', 1.00, 'Panes', 35, '1794327268_pan_cauca.png'),
(4, 'Pan Coliza', 'Pan esponjoso tradicional con semillas de sésamo.', 1.00, 'Panes', 30, '1794327349_pan_coliza.png'),
(5, 'Pan Chama', 'Pan rústico tradicional de textura suave horneado a la perfección.', 1.00, 'Panes', 30, '1794327319_pan_chama.png'),
(6, 'Pan Cachito', 'Cachito hojaldrado con mantequilla, suave y aromático.', 1.50, 'Masitas', 25, '1794327234_pan_cachito.png'),
(7, 'Pan Tortilla', 'Pan plano artesanal suave con toques tradicionales.', 1.20, 'Panes', 25, '1794325386_pan_tortilla.png'),
(8, 'Pan Galleta', 'Pan crocante y tostado, perfecto para té o café.', 1.00, 'Galletas', 40, '1794327399_pan_galleta.png'),
(9, 'Pastel de Fresa', 'Exquisito pastel artesanal relleno de fresas seleccionadas y crema.', 45.00, 'Postres', 10, '1793963355_pastel_de_fresa.jpg')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Recetas de ejemplo
INSERT INTO `recetas` (`producto_id`, `ingrediente_id`, `cantidad_necesaria`) VALUES
(1, 1, 0.08), -- Marraqueta: 80g de harina
(1, 2, 0.01), -- Marraqueta: 10g de levadura
(9, 1, 0.30), -- Pastel de Fresa: 300g harina
(9, 4, 0.20), -- Pastel de Fresa: 200g azúcar
(9, 5, 4.00), -- Pastel de Fresa: 4 huevos
(9, 6, 0.40), -- Pastel de Fresa: 400g fresas
(9, 7, 0.25)  -- Pastel de Fresa: 250ml crema
ON DUPLICATE KEY UPDATE `cantidad_necesaria` = VALUES(`cantidad_necesaria`);
