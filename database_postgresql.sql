-- =====================================================================
-- Base de Datos PostgreSQL para Panadería y Repostería: La Soleil
-- Compatible con DBeaver y PostgreSQL 12+
-- =====================================================================

-- 1. Tabla de Productos
CREATE TABLE IF NOT EXISTS productos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT DEFAULT '',
    precio NUMERIC(10,2) NOT NULL,
    categoria VARCHAR(100) DEFAULT 'Panes',
    stock INT NOT NULL DEFAULT 0,
    imagen VARCHAR(255) DEFAULT 'default.jpg'
);

-- Por si la tabla ya existía anteriormente sin estas columnas:
ALTER TABLE productos ADD COLUMN IF NOT EXISTS descripcion TEXT DEFAULT '';
ALTER TABLE productos ADD COLUMN IF NOT EXISTS categoria VARCHAR(100) DEFAULT 'Panes';

-- 2. Tabla de Proveedores
CREATE TABLE IF NOT EXISTS proveedores (
    id SERIAL PRIMARY KEY,
    nombre_empresa VARCHAR(255) NOT NULL
);

-- 3. Tabla de Ingredientes (Inventario)
CREATE TABLE IF NOT EXISTS ingredientes (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    unidad_medida VARCHAR(50) NOT NULL,
    stock_actual NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    stock_minimo NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    proveedor_id INT NULL,
    CONSTRAINT fk_ingredientes_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL
);

-- 4. Tabla de Recetas
CREATE TABLE IF NOT EXISTS recetas (
    id SERIAL PRIMARY KEY,
    producto_id INT NOT NULL,
    ingrediente_id INT NOT NULL,
    cantidad_necesaria NUMERIC(10,2) NOT NULL,
    CONSTRAINT fk_recetas_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    CONSTRAINT fk_recetas_ingrediente FOREIGN KEY (ingrediente_id) REFERENCES ingredientes(id) ON DELETE CASCADE
);

-- 5. Tabla de Ventas (Historial para facturas y estadísticas)
CREATE TABLE IF NOT EXISTS ventas (
    id SERIAL PRIMARY KEY,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    total NUMERIC(10,2) NOT NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ventas_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- =====================================================================
-- DATOS INICIALES (SEMILLAS)
-- =====================================================================

-- Proveedores
INSERT INTO proveedores (id, nombre_empresa) VALUES
(1, 'Molino Real S.A.'),
(2, 'Lácteos del Valle'),
(3, 'Distribuidora Dulce Azúcar')
ON CONFLICT (id) DO UPDATE SET nombre_empresa = EXCLUDED.nombre_empresa;

-- Ingredientes
INSERT INTO ingredientes (id, nombre, unidad_medida, stock_actual, stock_minimo, proveedor_id) VALUES
(1, 'Harina de Trigo', 'kg', 100.00, 20.00, 1),
(2, 'Levadura Fresca', 'kg', 15.00, 5.00, 1),
(3, 'Mantequilla Artesanal', 'kg', 25.00, 5.00, 2),
(4, 'Azúcar', 'kg', 50.00, 10.00, 3),
(5, 'Huevos', 'unidades', 120.00, 30.00, 2),
(6, 'Fresas Frescas', 'kg', 10.00, 3.00, 2),
(7, 'Crema de Leche', 'litros', 20.00, 5.00, 2)
ON CONFLICT (id) DO UPDATE SET stock_actual = EXCLUDED.stock_actual;

-- Productos vinculados con las imágenes en assets/img
INSERT INTO productos (id, nombre, descripcion, precio, categoria, stock, imagen) VALUES
(1, 'Pan Marraqueta', 'Pan tradicional boliviano, crocante por fuera y suave por dentro.', 0.80, 'Panes', 50, '1794327196_pan_marraqueta.png'),
(2, 'Pan Francés', 'Clásico pan francés dorado, ideal para acompañar el desayuno o la cena.', 0.80, 'Panes', 40, '1794327377_pan_frances.png'),
(3, 'Pan Cauca', 'Pan tradicional elaborado con manteca y queso fresco.', 1.00, 'Panes', 35, '1794327268_pan_cauca.png'),
(4, 'Pan Coliza', 'Pan esponjoso tradicional con semillas de sésamo.', 1.00, 'Panes', 30, '1794327349_pan_coliza.png'),
(5, 'Pan Chama', 'Pan rústico tradicional de textura suave horneado a la perfección.', 1.00, 'Panes', 30, '1794327319_pan_chama.png'),
(6, 'Pan Cachito', 'Cachito hojaldrado con mantequilla, suave y aromático.', 1.50, 'Masitas', 25, '1794327234_pan_cachito.png'),
(7, 'Pan Tortilla', 'Pan plano artesanal suave con toques tradicionales.', 1.20, 'Panes', 25, '1794325386_pan_tortilla.png'),
(8, 'Pan Galleta', 'Pan crocante y tostado, perfecto para té o café.', 1.00, 'Galletas', 40, '1794327399_pan_galleta.png'),
(9, 'Pastel de Fresa', 'Exquisito pastel artesanal relleno de fresas seleccionadas y crema.', 45.00, 'Postres', 10, '1793963355_pastel_de_fresa.jpg')
ON CONFLICT (id) DO UPDATE SET 
    nombre = EXCLUDED.nombre,
    descripcion = EXCLUDED.descripcion,
    precio = EXCLUDED.precio,
    categoria = EXCLUDED.categoria,
    stock = EXCLUDED.stock,
    imagen = EXCLUDED.imagen;

-- Recetas
INSERT INTO recetas (producto_id, ingrediente_id, cantidad_necesaria) VALUES
(1, 1, 0.08),
(1, 2, 0.01),
(9, 1, 0.30),
(9, 4, 0.20),
(9, 5, 4.00),
(9, 6, 0.40),
(9, 7, 0.25)
ON CONFLICT DO NOTHING;

-- Sincronizar secuencias automáticas
SELECT setval('productos_id_seq', (SELECT COALESCE(MAX(id), 1) FROM productos));
SELECT setval('proveedores_id_seq', (SELECT COALESCE(MAX(id), 1) FROM proveedores));
SELECT setval('ingredientes_id_seq', (SELECT COALESCE(MAX(id), 1) FROM ingredientes));
SELECT setval('recetas_id_seq', (SELECT COALESCE(MAX(id), 1) FROM recetas));
SELECT setval('ventas_id_seq', (SELECT COALESCE(MAX(id), 1) FROM ventas));
