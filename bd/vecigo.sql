-- =====================================================================
-- VeciGo · Tu barrio pide juntos
-- Base de datos completa + datos demo para presentación
--
-- Uso (phpMyAdmin): pestaña SQL → pegar TODO → Continuar
-- Uso (terminal):
--   /Applications/XAMPP/xamppfiles/bin/mysql -u root < bd/vecigo.sql
-- =====================================================================

DROP DATABASE IF EXISTS vecigo;
CREATE DATABASE vecigo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE vecigo;

-- ---------------------------------------------------------------------
CREATE TABLE categoria_comercio (
  id_categoria_comercio INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL UNIQUE,
  slug VARCHAR(60) NOT NULL UNIQUE,
  foto_url VARCHAR(255) DEFAULT NULL,
  color VARCHAR(20) DEFAULT '#FF6B4A'
) ENGINE=InnoDB;

CREATE TABLE comercio (
  id_comercio INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  id_categoria_comercio INT UNSIGNED NOT NULL,
  direccion VARCHAR(150) NOT NULL,
  latitud DECIMAL(10,7) NULL,
  longitud DECIMAL(10,7) NULL,
  telefono VARCHAR(15) NOT NULL,
  descripcion VARCHAR(255) DEFAULT NULL,
  horario VARCHAR(80) DEFAULT 'Lun–Sáb 8:00–20:00',
  foto_url VARCHAR(255) DEFAULT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_comercio_categoria
    FOREIGN KEY (id_categoria_comercio)
    REFERENCES categoria_comercio (id_categoria_comercio)
) ENGINE=InnoDB;

CREATE TABLE producto (
  id_producto INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  precio DECIMAL(10,2) NOT NULL,
  descripcion TEXT DEFAULT NULL,
  disponible TINYINT(1) NOT NULL DEFAULT 1,
  foto_url VARCHAR(255) DEFAULT NULL,
  id_comercio INT UNSIGNED NOT NULL,
  CONSTRAINT fk_producto_comercio
    FOREIGN KEY (id_comercio)
    REFERENCES comercio (id_comercio)
) ENGINE=InnoDB;

CREATE TABLE pedido (
  id_pedido INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre_cliente VARCHAR(100) NOT NULL,
  telefono_cliente VARCHAR(15) NOT NULL,
  direccion_entrega VARCHAR(150) NOT NULL,
  detalle TEXT NOT NULL,
  estado ENUM('nuevo','confirmado','entregado','cancelado') NOT NULL DEFAULT 'nuevo',
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_comercio INT UNSIGNED NOT NULL,
  id_usuario INT UNSIGNED NULL,
  CONSTRAINT fk_pedido_comercio
    FOREIGN KEY (id_comercio)
    REFERENCES comercio (id_comercio)
) ENGINE=InnoDB;

CREATE TABLE pedido_item (
  id_pedido_item INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_pedido INT UNSIGNED NOT NULL,
  id_producto INT UNSIGNED NULL,
  nombre VARCHAR(100) NOT NULL,
  cantidad INT UNSIGNED NOT NULL DEFAULT 1,
  precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_item_pedido
    FOREIGN KEY (id_pedido) REFERENCES pedido (id_pedido)
    ON DELETE CASCADE,
  CONSTRAINT fk_item_producto
    FOREIGN KEY (id_producto) REFERENCES producto (id_producto)
    ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE usuario (
  id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  correo VARCHAR(120) NOT NULL UNIQUE,
  telefono VARCHAR(15) NULL,
  direccion VARCHAR(150) NULL,
  clave VARCHAR(255) NOT NULL,
  rol ENUM('admin','comercio','cliente') NOT NULL DEFAULT 'admin',
  id_comercio INT UNSIGNED NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuario_comercio
    FOREIGN KEY (id_comercio) REFERENCES comercio (id_comercio)
    ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- DATOS DEMO — login unificado por correo
--   admin@vecigo.co / vecigo2024
--   donarosa@vecigo.co | hamburguesas@vecigo.co | trigal@vecigo.co / comercio2024
--   carlos@vecigo.co / cliente2024
-- =====================================================================
INSERT INTO usuario (nombre, usuario, correo, telefono, direccion, clave, rol, id_comercio) VALUES
('Admin VeciGo', 'admin', 'admin@vecigo.co', NULL, NULL, '$2y$10$rHCH/NB2zsOEtMdGD5e6ZeddqFwQqriH5SuXYReymv2M70/YszH6u', 'admin', NULL);

INSERT INTO categoria_comercio (nombre, slug, foto_url, color) VALUES
('Restaurantes',  'restaurantes', 'img/categorias/restaurantes.jpg', '#FF6B4A'),
('Comida rápida', 'rapida',       'img/categorias/rapida.jpg',       '#F5C518'),
('Surtifruver',   'fruta',        'img/categorias/fruta.jpg',        '#3DDC97'),
('Droguerías',    'droga',        'img/categorias/droga.jpg',        '#5B8DEF'),
('Panaderías',    'pan',          'img/categorias/pan.jpg',          '#E8A87C'),
('Misceláneas',   'misc',         'img/categorias/misc.jpg',         '#9B8CFF');

INSERT INTO comercio (nombre, id_categoria_comercio, direccion, latitud, longitud, telefono, descripcion, horario, foto_url) VALUES
('Restaurante Doña Rosa',     1, 'Calle 12 # 8-30, La Soledad, Bogotá',           4.6301200, -74.0698500, '3001234567', 'Comida casera del barrio desde 1987',           'Lun–Dom 11:00–16:00', 'img/comercios/dona-rosa.jpg'),
('Corrientazo El Buen Sabor', 1, 'Carrera 9 # 14-22, La Candelaria, Bogotá',      4.5981200, -74.0760500, '3012345678', 'Almuerzos rápidos y ración generosa',           'Lun–Sáb 11:00–15:00', 'img/comercios/buen-sabor.jpg'),
('Hamburguesas La Esquina',   2, 'Calle 15 # 10-05, Las Nieves, Bogotá',          4.6054800, -74.0729100, '3023456789', 'Burgers a la parrilla y papas crujientes',      'Mar–Dom 12:00–22:00', 'img/comercios/hamburguesas.jpg'),
('Perros El Parche',          2, 'Carrera 11 # 13-40, La Macarena, Bogotá',       4.6152100, -74.0654300, '3034567890', 'Perros calientes del parche, salsas de la casa', 'Lun–Dom 16:00–23:00', 'img/comercios/perros.jpg'),
('Surtifruver La Cosecha',    3, 'Calle 10 # 7-18, Paloquemao, Bogotá',           4.6168900, -74.0871200, '3045678901', 'Frutas y verduras frescas del mercado',         'Lun–Sáb 7:00–19:00',  'img/comercios/cosecha.jpg'),
('Droguería Salud Total',     4, 'Carrera 8 # 12-50, Centro Internacional, Bogotá', 4.6124500, -74.0702800, '3056789012', 'Medicamentos y aseo, domicilio en el barrio',   'Lun–Sáb 8:00–20:00',  'img/comercios/salud.jpg'),
('Panadería El Trigal',       5, 'Calle 13 # 9-12, Santa Fe, Bogotá',             4.6039700, -74.0786400, '3067890123', 'Pan caliente todas las mañanas',                'Lun–Dom 6:00–20:00',  'img/comercios/trigal.jpg'),
('Miscelánea Todo a Mil',     6, 'Carrera 10 # 11-33, Las Cruces, Bogotá',        4.5908600, -74.0815200, '3078901234', 'Útiles, dulces y lo que se te olvide',          'Lun–Sáb 8:00–19:00',  'img/comercios/todoamil.jpg');

INSERT INTO producto (nombre, precio, descripcion, disponible, foto_url, id_comercio) VALUES
('Almuerzo ejecutivo',   15000, 'Sopa, seco, jugo y postre',                    1, 'img/productos/almuerzo.jpg', 1),
('Bandeja paisa',        25000, 'Fríjoles, arroz, carne, chicharrón y huevo',   1, 'img/productos/bandeja.jpg', 1),
('Ajiaco santafereño',   20000, 'Con pollo, alcaparras y crema',               0, 'img/productos/ajiaco.jpg', 1),
('Corrientazo del día',  12000, 'Sopa y seco del día',                         1, 'img/productos/corrientazo.jpg', 2),
('Sancocho de gallina',  18000, 'Porción familiar con yuca y plátano',         1, 'img/productos/ajiaco.jpg', 2),
('Hamburguesa sencilla', 14000, 'Carne, queso, lechuga y tomate',              1, 'img/productos/burger.jpg', 3),
('Hamburguesa doble',    20000, 'Doble carne y doble queso',                   1, 'img/productos/burger2.jpg', 3),
('Papas a la francesa',   6000, 'Porción mediana',                             1, 'img/productos/papas.jpg', 3),
('Perro sencillo',        8000, 'Salchicha, papita y salsas',                  1, 'img/productos/perro.jpg', 4),
('Perro especial',       12000, 'Con queso, tocineta y huevo de codorniz',     1, 'img/productos/perro2.jpg', 4),
('Libra de tomate',       3500, 'Tomate chonto fresco',                        1, 'img/productos/tomate.jpg', 5),
('Kilo de papa pastusa',  4000, 'Ideal para sopas y frituras',                 1, 'img/productos/papa.jpg', 5),
('Racimo de bananos',     5000, 'Aprox. 8 unidades',                           0, 'img/productos/banano.jpg', 5),
('Acetaminofén x 10',     3000, 'Tabletas de 500 mg',                          1, 'img/productos/pastillas.jpg', 6),
('Alcohol antiséptico',   7500, 'Frasco de 350 ml',                            1, 'img/productos/alcohol.jpg', 6),
('Pan aliñado',           1000, 'Recién horneado',                             1, 'img/productos/pan.jpg', 7),
('Roscón de arequipe',    2500, 'Relleno de arequipe casero',                  1, 'img/productos/roscon.jpg', 7),
('Torta de chocolate',   35000, 'Para 10 porciones, por encargo',              1, 'img/productos/torta.jpg', 7),
('Cuaderno cuadriculado', 4500, '100 hojas',                                   1, 'img/productos/cuaderno.jpg', 8),
('Pegante en barra',      2000, '40 g',                                        1, 'img/productos/pegante.jpg', 8);

INSERT INTO pedido (nombre_cliente, telefono_cliente, direccion_entrega, detalle, estado, id_comercio) VALUES
('Carlos Pérez',   '3101112233', 'Calle 14 # 9-20 apto 301', '2 × Almuerzo ejecutivo',              'entregado',  1),
('Luisa Martínez', '3114445566', 'Carrera 10 # 12-08',       '1 × Hamburguesa doble · 1 × Papas',   'confirmado', 3),
('Jorge Ramírez',  '3127778899', 'Calle 11 # 8-15',          '6 × Pan aliñado · 2 × Roscón',        'nuevo',      7),
('Ana Gómez',      '3138889900', 'Calle 12 # 10-05',         '1 × Perro especial',                  'nuevo',      4),
('Diego Torres',   '3145556677', 'Carrera 8 # 15-20',        '1 libra de tomate · 1 kilo de papa',  'confirmado', 5);

-- Dueños de local (panel comercio) · clave: comercio2024
INSERT INTO usuario (nombre, usuario, correo, telefono, direccion, clave, rol, id_comercio) VALUES
('Doña Rosa',  'donarosa',     'donarosa@vecigo.co',     '3001234567', NULL, '$2y$10$2stwnpDKhOhIz/5UceHwE.oaup/tlbpbLmSBYWB69oadSdj1QOUou', 'comercio', 1),
('La Esquina', 'hamburguesas', 'hamburguesas@vecigo.co', '3023456789', NULL, '$2y$10$2stwnpDKhOhIz/5UceHwE.oaup/tlbpbLmSBYWB69oadSdj1QOUou', 'comercio', 3),
('El Trigal',  'trigal',       'trigal@vecigo.co',       '3067890123', NULL, '$2y$10$2stwnpDKhOhIz/5UceHwE.oaup/tlbpbLmSBYWB69oadSdj1QOUou', 'comercio', 7);

-- Cliente demo · clave: cliente2024
INSERT INTO usuario (nombre, usuario, correo, telefono, direccion, clave, rol, id_comercio) VALUES
('Carlos Pérez', 'carlos', 'carlos@vecigo.co', '3101112233', 'Calle 14 # 9-20 apto 301',
 '$2y$10$Fo1itILHq2Cl.91orK2hUeZEr0my5cTuL8Ze.xil5L/6oNunJN2Pm', 'cliente', NULL);

UPDATE pedido SET id_usuario = (SELECT id_usuario FROM usuario WHERE correo = 'carlos@vecigo.co' LIMIT 1)
WHERE telefono_cliente = '3101112233';

ALTER TABLE pedido
  ADD CONSTRAINT fk_pedido_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
    ON DELETE SET NULL;

INSERT INTO pedido_item (id_pedido, id_producto, nombre, cantidad, precio_unitario) VALUES
(1, 1, 'Almuerzo ejecutivo', 2, 15000),
(2, 7, 'Hamburguesa doble', 1, 20000),
(2, 8, 'Papas a la francesa', 1, 6000),
(3, 16, 'Pan aliñado', 6, 1000),
(3, 17, 'Roscón de arequipe', 2, 2500),
(4, 10, 'Perro especial', 1, 12000),
(5, 11, 'Libra de tomate', 1, 3500),
(5, 12, 'Kilo de papa pastusa', 1, 4000);
