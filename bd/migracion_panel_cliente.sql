-- =====================================================================
-- VeciGo · Panel cliente (cuenta, perfil, pedidos vinculados)
--   mysql -u root -h 127.0.0.1 vecigo < bd/migracion_panel_cliente.sql
-- =====================================================================

USE vecigo;

ALTER TABLE usuario
  MODIFY COLUMN rol ENUM('admin','comercio','cliente') NOT NULL DEFAULT 'admin';

ALTER TABLE usuario
  ADD COLUMN telefono VARCHAR(15) NULL AFTER correo,
  ADD COLUMN direccion VARCHAR(150) NULL AFTER telefono;

ALTER TABLE pedido
  ADD COLUMN id_usuario INT UNSIGNED NULL AFTER id_comercio;

ALTER TABLE pedido
  ADD CONSTRAINT fk_pedido_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
    ON DELETE SET NULL;

-- Demo cliente: carlos@vecigo.co / cliente2024 (celular de pedido demo)
INSERT INTO usuario (nombre, usuario, correo, telefono, direccion, clave, rol, id_comercio)
SELECT 'Carlos Pérez', 'carlos', 'carlos@vecigo.co', '3101112233', 'Calle 14 # 9-20 apto 301',
       '$2y$10$Fo1itILHq2Cl.91orK2hUeZEr0my5cTuL8Ze.xil5L/6oNunJN2Pm', 'cliente', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM usuario WHERE correo = 'carlos@vecigo.co');

UPDATE pedido p
JOIN usuario u ON u.correo = 'carlos@vecigo.co' AND u.rol = 'cliente'
SET p.id_usuario = u.id_usuario
WHERE p.telefono_cliente = u.telefono AND p.id_usuario IS NULL;
