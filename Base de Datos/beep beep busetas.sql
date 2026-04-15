CREATE DATABASE buses;
GO

USE buses;
GO

-- =========================================
-- TABLA: Usuarios
-- =========================================
CREATE TABLE Usuarios (
    IdUsuario INT IDENTITY(1,1) PRIMARY KEY,
    TipoUsuario VARCHAR(20) NOT NULL,
    NombreUsuario VARCHAR(50) NOT NULL UNIQUE,
    Correo VARCHAR(100) NOT NULL UNIQUE,
    Contra VARCHAR(255) NOT NULL,
    FechaRegistro DATETIME NOT NULL DEFAULT GETDATE(),
    Estado BIT NOT NULL DEFAULT 1,

    CONSTRAINT CK_Usuarios_TipoUsuario
        CHECK (TipoUsuario IN ('admin', 'empresa'))
);
GO

-- =========================================
-- TABLA: Empresas
-- Un usuario de tipo empresa tiene un registro aquí
-- =========================================
CREATE TABLE Empresas (
    IdEmpresa INT IDENTITY(1,1) PRIMARY KEY,
    IdUsuario INT NOT NULL UNIQUE,
    NombreEmpresa VARCHAR(100) NOT NULL,
    NIT VARCHAR(30) NOT NULL UNIQUE,
    Direccion VARCHAR(150) NOT NULL,
    Telefono VARCHAR(20) NOT NULL,
    Ciudad VARCHAR(50) NOT NULL,
    CorreoEmpresa VARCHAR(100) NULL,
    NombreContacto VARCHAR(100) NULL,
    FechaRegistro DATETIME NOT NULL DEFAULT GETDATE(),
    Estado BIT NOT NULL DEFAULT 1,

    CONSTRAINT FK_Empresas_Usuarios
        FOREIGN KEY (IdUsuario)
        REFERENCES Usuarios(IdUsuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);
GO

-- =========================================
-- TABLA: Municipios
-- Catálogo administrado por el admin
-- =========================================
CREATE TABLE Municipios (
    IdMunicipio INT IDENTITY(1,1) PRIMARY KEY,
    NombreMunicipio VARCHAR(100) NOT NULL,
    Departamento VARCHAR(100) NOT NULL,
    Estado BIT NOT NULL DEFAULT 1,
    FechaRegistro DATETIME NOT NULL DEFAULT GETDATE(),

    CONSTRAINT UQ_Municipios_Nombre_Departamento
        UNIQUE (NombreMunicipio, Departamento)
);
GO

-- =========================================
-- TABLA: Rutas
-- Encabezado de cada ruta creada por una empresa
-- =========================================
CREATE TABLE Rutas (
    IdRuta INT IDENTITY(1,1) PRIMARY KEY,
    IdEmpresa INT NOT NULL,
    NombreRuta VARCHAR(150) NOT NULL,
    HoraInicio TIME NOT NULL,
    HoraFin TIME NOT NULL,
    Estado BIT NOT NULL DEFAULT 1,
    FechaRegistro DATETIME NOT NULL DEFAULT GETDATE(),

    CONSTRAINT FK_Rutas_Empresas
        FOREIGN KEY (IdEmpresa)
        REFERENCES Empresas(IdEmpresa)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT CK_Rutas_Horas
        CHECK (HoraInicio < HoraFin)
);
GO

-- =========================================
-- TABLA: RutaDetalle
-- Descripción de la ruta
-- Una ruta tiene una sola descripción general
-- =========================================
CREATE TABLE RutaDetalle (
    IdRutaDetalle INT IDENTITY(1,1) PRIMARY KEY,
    IdRuta INT NOT NULL UNIQUE,
    DescripcionRuta VARCHAR(500) NOT NULL,
    FechaRegistro DATETIME NOT NULL DEFAULT GETDATE(),

    CONSTRAINT FK_RutaDetalle_Rutas
        FOREIGN KEY (IdRuta)
        REFERENCES Rutas(IdRuta)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);
GO

-- =========================================
-- TABLA: RutaMunicipios
-- Define el recorrido por municipios y el orden
-- =========================================
CREATE TABLE RutaMunicipios (
    IdRutaMunicipio INT IDENTITY(1,1) PRIMARY KEY,
    IdRuta INT NOT NULL,
    IdMunicipio INT NOT NULL,
    OrdenRecorrido INT NOT NULL,
    FechaRegistro DATETIME NOT NULL DEFAULT GETDATE(),

    CONSTRAINT FK_RutaMunicipios_Rutas
        FOREIGN KEY (IdRuta)
        REFERENCES Rutas(IdRuta)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT FK_RutaMunicipios_Municipios
        FOREIGN KEY (IdMunicipio)
        REFERENCES Municipios(IdMunicipio)
        ON DELETE NO ACTION
        ON UPDATE CASCADE,

    CONSTRAINT UQ_RutaMunicipios_Ruta_Orden
        UNIQUE (IdRuta, OrdenRecorrido),

    CONSTRAINT UQ_RutaMunicipios_Ruta_Municipio
        UNIQUE (IdRuta, IdMunicipio)
);
GO

-- =========================================
-- TABLA: ParadasRuta
-- Paradas libres que la empresa escribe
-- Ej: Calle 13, Puente peatonal, Parque principal
-- =========================================
CREATE TABLE ParadasRuta (
    IdParadaRuta INT IDENTITY(1,1) PRIMARY KEY,
    IdRuta INT NOT NULL,
    NombreParada VARCHAR(150) NOT NULL,
    DireccionReferencia VARCHAR(255) NULL,
    Observaciones VARCHAR(500) NULL,
    OrdenParada INT NULL,
    Estado BIT NOT NULL DEFAULT 1,
    FechaRegistro DATETIME NOT NULL DEFAULT GETDATE(),

    CONSTRAINT FK_ParadasRuta_Rutas
        FOREIGN KEY (IdRuta)
        REFERENCES Rutas(IdRuta)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);
GO

-- Usuario admin
INSERT INTO Usuarios (TipoUsuario, NombreUsuario, Correo, Contra)
VALUES ('admin', 'admin01', 'admin@buses.com', 'admin123');
GO

-- Usuario empresa
INSERT INTO Usuarios (TipoUsuario, NombreUsuario, Correo, Contra)
VALUES ('empresa', 'transportes01', 'empresa@correo.com', 'empresa123');
GO

-- Empresa
INSERT INTO Empresas (
    IdUsuario,
    NombreEmpresa,
    NIT,
    Direccion,
    Telefono,
    Ciudad,
    CorreoEmpresa,
    NombreContacto
)
VALUES (
    2,
    'Transportes Sabana',
    '900123456-7',
    'Calle 10 #20-30',
    '3001234567',
    'Mosquera',
    'contacto@transportessabana.com',
    'Juan Pérez'
);
GO

-- Municipios
INSERT INTO Municipios (NombreMunicipio, Departamento)
VALUES
('Mosquera', 'Cundinamarca'),
('Funza', 'Cundinamarca'),
('Madrid', 'Cundinamarca'),
('Facatativá', 'Cundinamarca');
GO

-- Ruta
INSERT INTO Rutas (IdEmpresa, NombreRuta, HoraInicio, HoraFin)
VALUES (1, 'Ruta Mosquera - Madrid', '05:00:00', '22:00:00');
GO

-- Descripción de la ruta
INSERT INTO RutaDetalle (IdRuta, DescripcionRuta)
VALUES (1, 'Ruta intermunicipal que sale desde Mosquera, pasa por Funza y finaliza en Madrid.');
GO

-- Municipios de la ruta en orden
INSERT INTO RutaMunicipios (IdRuta, IdMunicipio, OrdenRecorrido)
VALUES
(1, 1, 1), -- Mosquera
(1, 2, 2), -- Funza
(1, 3, 3); -- Madrid
GO

-- Paradas de la ruta
INSERT INTO ParadasRuta (IdRuta, NombreParada, DireccionReferencia, Observaciones, OrdenParada)
VALUES
(1, 'Parque principal de Mosquera', 'Frente al parque principal', 'Se recoge gente desde temprano', 1),
(1, 'Calle 13 con carrera 9 en Funza', 'Esquina del semáforo', 'Parada frecuente en horas pico', 2),
(1, 'Terminal informal de Madrid', 'Cerca al puente peatonal', 'Última parada del recorrido', 3);
GO

SELECT
    r.IdRuta,
    e.NombreEmpresa,
    r.NombreRuta,
    r.HoraInicio,
    r.HoraFin,
    r.Estado
FROM Rutas r
INNER JOIN Empresas e
    ON r.IdEmpresa = e.IdEmpresa;

    SELECT
    r.NombreRuta,
    rd.DescripcionRuta
FROM Rutas r
INNER JOIN RutaDetalle rd
    ON r.IdRuta = rd.IdRuta
WHERE r.IdRuta = 1;

SELECT
    e.NombreEmpresa,
    r.IdRuta,
    r.NombreRuta,
    r.HoraInicio,
    r.HoraFin
FROM Empresas e
INNER JOIN Rutas r
    ON e.IdEmpresa = r.IdEmpresa
WHERE e.IdEmpresa = 1;