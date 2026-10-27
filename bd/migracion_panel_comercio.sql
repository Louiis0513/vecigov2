-- =====================================================================
-- VeciGo · Migración half product: panel comercio + ítems de pedido
-- Uso (BD ya importada):
--   /Applications/XAMPP/xamppfiles/bin/mysql -u root vecigo < bd/migracion_panel_comercio.sql
-- Si ya corriste esto, ignora errores de "Duplicate column".
-- =====================================================================

USE vecigo;

-- Roles: admin (plataforma) · comercio (dueño del local)
ALTER TABLE usuario
  MODIFY COLUMN rol ENUM('admin','comercio') NOT NULL DEFAULT 'admin';

-- Vincular usuario comercio → local (nullable para admin)
ALTER TABLE usuario
  ADD COLUMN id_comercio INT UNSIGNED NULL AFTER rol;

ALTER TABLE usuario
  ADD CONSTRAINT fk_usuario_comercio
    FOREIGN KEY (id_comercio) REFERENCES comercio (id_comercio)
    ON DELETE SET NULL;

-- Ítems estructurados del pedido (detalle TEXT se mantiene como resumen)
CREATE TABLE IF NOT EXISTS pedido_item (
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

-- Usuarios demo de comercios (clave: comercio2024)
-- Hash bcrypt generado con password_hash('comercio2024', PASSWORD_DEFAULT)
INSERT INTO usuario (nombre, usuario, clave, rol, id_comercio)
SELECT 'Doña Rosa', 'donarosa', '$2y$10$2stwnpDKhOhIz/5UceHwE.oaup/tlbpbLmSBYWB69oadSdj1QOUou', 'comercio', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM usuario WHERE usuario = 'donarosa');

INSERT INTO usuario (nombre, usuario, clave, rol, id_comercio)
SELECT 'La Esquina', 'hamburguesas', '$2y$10$2stwnpDKhOhIz/5UceHwE.oaup/tlbpbLmSBYWB69oadSdj1QOUou', 'comercio', 3
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM usuario WHERE usuario = 'hamburguesas');

INSERT INTO usuario (nombre, usuario, clave, rol, id_comercio)
SELECT 'El Trigal', 'trigal', '$2y$10$2stwnpDKhOhIz/5UceHwE.oaup/tlbpbLmSBYWB69oadSdj1QOUou', 'comercio', 7
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM usuario WHERE usuario = 'trigal');
