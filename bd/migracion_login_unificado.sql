-- =====================================================================
-- VeciGo · Login unificado por correo
--   mysql -u root -h 127.0.0.1 vecigo < bd/migracion_login_unificado.sql
-- =====================================================================

USE vecigo;

ALTER TABLE usuario
  ADD COLUMN correo VARCHAR(120) NULL AFTER usuario;

UPDATE usuario SET correo = 'admin@vecigo.co' WHERE usuario = 'admin' AND (correo IS NULL OR correo = '');
UPDATE usuario SET correo = 'donarosa@vecigo.co' WHERE usuario = 'donarosa' AND (correo IS NULL OR correo = '');
UPDATE usuario SET correo = 'hamburguesas@vecigo.co' WHERE usuario = 'hamburguesas' AND (correo IS NULL OR correo = '');
UPDATE usuario SET correo = 'trigal@vecigo.co' WHERE usuario = 'trigal' AND (correo IS NULL OR correo = '');

-- Relleno genérico por si hay otros usuarios
UPDATE usuario
SET correo = CONCAT(usuario, '@vecigo.co')
WHERE correo IS NULL OR correo = '';

ALTER TABLE usuario
  MODIFY COLUMN correo VARCHAR(120) NOT NULL;

-- Índice único (ignorar error si ya existe)
ALTER TABLE usuario
  ADD UNIQUE KEY uq_usuario_correo (correo);
