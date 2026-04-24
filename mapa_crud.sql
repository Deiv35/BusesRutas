CREATE DATABASE mapa_crud;
USE mapa_crud;
CREATE TABLE marcadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    lat DECIMAL(10,8) NOT NULL,
    lng DECIMAL(11,8) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO marcadores (nombre, lat, lng) VALUES 
('Facatativá', 4.8097, -74.3545),
('Bogotá', 4.7110, -74.0721);
-- Tabla para rutas
CREATE TABLE IF NOT EXISTS rutas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    coordenadas JSON NOT NULL,  -- array de puntos [[lat,lng], ...]
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Si ya tienes la tabla, puedes modificar la columna (no es obligatorio, pero mejora la claridad)
ALTER TABLE rutas CHANGE coordenadas waypoints JSON NOT NULL;
ALTER TABLE marcadores ADD COLUMN cantidad INT DEFAULT 0;

DESCRIBE marcadores;

ALTER TABLE rutas ADD COLUMN valor INT DEFAULT 0;

ALTER TABLE rutas ADD COLUMN valor INT DEFAULT 0;
UPDATE rutas SET valor = 10; -- valores de ejemplo