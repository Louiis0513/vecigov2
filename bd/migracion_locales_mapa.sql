-- =====================================================================
-- VeciGo · Coordenadas + direcciones demo (mapa)
--   mysql -u root -h 127.0.0.1 vecigo < bd/migracion_locales_mapa.sql
-- =====================================================================

USE vecigo;

-- Columnas (ignorar error si ya existen)
ALTER TABLE comercio
  ADD COLUMN latitud DECIMAL(10,7) NULL AFTER direccion;
ALTER TABLE comercio
  ADD COLUMN longitud DECIMAL(10,7) NULL AFTER latitud;

UPDATE comercio SET
  direccion = 'Calle 12 # 8-30, La Soledad, Bogotá',
  latitud = 4.6301200, longitud = -74.0698500
WHERE id_comercio = 1;

UPDATE comercio SET
  direccion = 'Carrera 9 # 14-22, La Candelaria, Bogotá',
  latitud = 4.5981200, longitud = -74.0760500
WHERE id_comercio = 2;

UPDATE comercio SET
  direccion = 'Calle 15 # 10-05, Las Nieves, Bogotá',
  latitud = 4.6054800, longitud = -74.0729100
WHERE id_comercio = 3;

UPDATE comercio SET
  direccion = 'Carrera 11 # 13-40, La Macarena, Bogotá',
  latitud = 4.6152100, longitud = -74.0654300
WHERE id_comercio = 4;

UPDATE comercio SET
  direccion = 'Calle 10 # 7-18, Paloquemao, Bogotá',
  latitud = 4.6168900, longitud = -74.0871200
WHERE id_comercio = 5;

UPDATE comercio SET
  direccion = 'Carrera 8 # 12-50, Centro Internacional, Bogotá',
  latitud = 4.6124500, longitud = -74.0702800
WHERE id_comercio = 6;

UPDATE comercio SET
  direccion = 'Calle 13 # 9-12, Santa Fe, Bogotá',
  latitud = 4.6039700, longitud = -74.0786400
WHERE id_comercio = 7;

UPDATE comercio SET
  direccion = 'Carrera 10 # 11-33, Las Cruces, Bogotá',
  latitud = 4.5908600, longitud = -74.0815200
WHERE id_comercio = 8;
