DROP DATABASE IF EXISTS BusesRemake;
GO

CREATE DATABASE BusesRemake;
GO

USE BusesRemake;
GO

/* =========================
   TIPOS DE USUARIO
========================= */
CREATE TABLE dbo.TiposUsuario (
    IdTipoUsuario INT IDENTITY(1,1) PRIMARY KEY,
    NombreTipo VARCHAR(50) NOT NULL UNIQUE
);
GO

INSERT INTO dbo.TiposUsuario (NombreTipo)
VALUES 
('Administrador'),
('Empresa'),
('Usuario Comun');
GO

/* =========================
   CATEGORÍAS EMPRESA
========================= */
CREATE TABLE dbo.CategoriasEmpresa (
    IdCategoriaEmpresa INT IDENTITY(1,1) PRIMARY KEY,
    NombreCategoria VARCHAR(50) NOT NULL UNIQUE
);
GO

INSERT INTO dbo.CategoriasEmpresa (NombreCategoria)
VALUES
('Informativo'),
('Contador');
GO

/* =========================
   USUARIOS
========================= */
CREATE TABLE dbo.Usuarios (
    IdUsuario INT IDENTITY(1,1) PRIMARY KEY,
    NombreUsuario VARCHAR(50) NOT NULL UNIQUE,
    Correo VARCHAR(100) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,

    IdTipoUsuario INT NOT NULL,
    IdCategoriaEmpresa INT NULL,

    NombreEmpresa VARCHAR(100) NULL,
    NitEmpresa VARCHAR(50) NULL,
    DireccionEmpresa VARCHAR(150) NULL,
    TelefonoEmpresa VARCHAR(30) NULL,
    CiudadEmpresa VARCHAR(100) NULL,
    CorreoEmpresa VARCHAR(100) NULL,
    NombreContacto VARCHAR(100) NULL,

    FechaRegistro DATETIME DEFAULT GETDATE(),
    Estado BIT DEFAULT 1,

    CONSTRAINT FK_Usuarios_TiposUsuario
    FOREIGN KEY (IdTipoUsuario) 
    REFERENCES dbo.TiposUsuario(IdTipoUsuario),

    CONSTRAINT FK_Usuarios_CategoriasEmpresa
    FOREIGN KEY (IdCategoriaEmpresa)
    REFERENCES dbo.CategoriasEmpresa(IdCategoriaEmpresa)
);
GO

/* =========================
   MUNICIPIOS
========================= */
CREATE TABLE dbo.Municipios (
    IdMunicipio INT IDENTITY(1,1) PRIMARY KEY,
    NombreMunicipio VARCHAR(100) NOT NULL,
    Departamento VARCHAR(100) NOT NULL,
    Estado BIT DEFAULT 1
);
GO

/* =========================
   RUTAS
========================= */
CREATE TABLE dbo.Rutas (
    IdRuta INT IDENTITY(1,1) PRIMARY KEY,
    IdEmpresa INT NOT NULL,
    NombreRuta VARCHAR(100) NOT NULL,
    HoraInicio TIME NOT NULL,
    HoraFin TIME NOT NULL,
    PrecioRuta DECIMAL(10,2) NOT NULL DEFAULT 0,
    Estado BIT DEFAULT 1,

    CONSTRAINT FK_Rutas_Usuarios
    FOREIGN KEY (IdEmpresa)
    REFERENCES dbo.Usuarios(IdUsuario)
);
GO

/* =========================
   DETALLE DE RUTA
========================= */
CREATE TABLE dbo.RutaDetalle (
    IdRutaDetalle INT IDENTITY(1,1) PRIMARY KEY,
    IdRuta INT NOT NULL,
    DescripcionRuta VARCHAR(500) NULL,

    CONSTRAINT FK_RutaDetalle_Rutas
    FOREIGN KEY (IdRuta)
    REFERENCES dbo.Rutas(IdRuta)
    ON DELETE CASCADE
);
GO

/* =========================
   PARADAS DE RUTA
========================= */
CREATE TABLE dbo.RutaParadas (
    IdRutaParada INT IDENTITY(1,1) PRIMARY KEY,
    IdRuta INT NOT NULL,
    IdMunicipio INT NOT NULL,
    OrdenParada INT NOT NULL,
    NombreParada VARCHAR(100) NULL,
    DireccionParada VARCHAR(200) NOT NULL,

    Lat DECIMAL(10,7) NULL,
    Lng DECIMAL(10,7) NULL,

    CONSTRAINT FK_RutaParadas_Rutas
    FOREIGN KEY (IdRuta)
    REFERENCES dbo.Rutas(IdRuta)
    ON DELETE CASCADE,

    CONSTRAINT FK_RutaParadas_Municipios
    FOREIGN KEY (IdMunicipio)
    REFERENCES dbo.Municipios(IdMunicipio),

    CONSTRAINT UQ_RutaParadas_Ruta_Orden
    UNIQUE (IdRuta, OrdenParada)
);
GO

/* =========================
   HORARIOS DE SALIDA
========================= */
CREATE TABLE dbo.RutaSalidas (
    IdSalida INT IDENTITY(1,1) PRIMARY KEY,
    IdRuta INT NOT NULL,
    HoraSalida TIME NOT NULL,
    LugarSalida VARCHAR(200) NOT NULL,
    OrdenSalida INT NOT NULL,

    CONSTRAINT FK_RutaSalidas_Rutas
    FOREIGN KEY (IdRuta)
    REFERENCES dbo.Rutas(IdRuta)
    ON DELETE CASCADE,

    CONSTRAINT UQ_RutaSalidas_Ruta_Orden
    UNIQUE (IdRuta, OrdenSalida)
);
GO

/* =========================
   PUNTOS DE CONTROL
========================= */
CREATE TABLE dbo.PuntosControl (
    IdPuntoControl INT IDENTITY(1,1) PRIMARY KEY,
    IdEmpresa INT NOT NULL,
    IdRuta INT NULL,

    NombrePunto VARCHAR(100) NOT NULL,
    Descripcion VARCHAR(200) NULL,

    Lat DECIMAL(10,7) NOT NULL,
    Lng DECIMAL(10,7) NOT NULL,

    CodigoAcceso CHAR(8) NOT NULL UNIQUE,

    Estado BIT DEFAULT 1,
    FechaRegistro DATETIME DEFAULT GETDATE(),

    CONSTRAINT FK_PuntosControl_Empresa
    FOREIGN KEY (IdEmpresa)
    REFERENCES dbo.Usuarios(IdUsuario),

    CONSTRAINT FK_PuntosControl_Ruta
    FOREIGN KEY (IdRuta)
    REFERENCES dbo.Rutas(IdRuta)
    ON DELETE SET NULL
);
GO

/* =========================
   CONTADORES DE EMPRESA
========================= */
CREATE TABLE dbo.ContadoresEmpresa (
    IdContador INT IDENTITY(1,1) PRIMARY KEY,

    IdUsuarioContador INT NOT NULL UNIQUE,
    IdEmpresa INT NOT NULL,
    IdPuntoControl INT NULL,

    NombreContador VARCHAR(100) NOT NULL,
    CedulaContador VARCHAR(30) NOT NULL UNIQUE,
    CodigoAcceso CHAR(8) NOT NULL UNIQUE,

    FechaRegistro DATETIME DEFAULT GETDATE(),
    Estado BIT DEFAULT 1,

    CONSTRAINT FK_ContadoresEmpresa_UsuarioContador
    FOREIGN KEY (IdUsuarioContador)
    REFERENCES dbo.Usuarios(IdUsuario),

    CONSTRAINT FK_ContadoresEmpresa_Empresa
    FOREIGN KEY (IdEmpresa)
    REFERENCES dbo.Usuarios(IdUsuario),

    CONSTRAINT FK_ContadoresEmpresa_PuntoControl
    FOREIGN KEY (IdPuntoControl)
    REFERENCES dbo.PuntosControl(IdPuntoControl)
);
GO

/* =========================
   REGISTROS DE TIEMPO DEL CONTADOR
   UNA SALIDA POR DÍA
========================= */
CREATE TABLE dbo.RegistrosContador (
    IdRegistro INT IDENTITY(1,1) PRIMARY KEY,

    IdContador INT NOT NULL,
    IdEmpresa INT NOT NULL,
    IdPuntoControl INT NULL,

    IdRuta INT NOT NULL,
    IdSalida INT NOT NULL,

    HoraProgramada TIME NOT NULL,
    FechaRegistro DATE NOT NULL DEFAULT CAST(GETDATE() AS DATE),
    FechaHoraRegistro DATETIME NOT NULL DEFAULT GETDATE(),
    DiferenciaSegundos INT NOT NULL,

    CONSTRAINT FK_RegistrosContador_Contador
    FOREIGN KEY (IdContador)
    REFERENCES dbo.ContadoresEmpresa(IdContador),

    CONSTRAINT FK_RegistrosContador_Empresa
    FOREIGN KEY (IdEmpresa)
    REFERENCES dbo.Usuarios(IdUsuario),

    CONSTRAINT FK_RegistrosContador_PuntoControl
    FOREIGN KEY (IdPuntoControl)
    REFERENCES dbo.PuntosControl(IdPuntoControl),

    CONSTRAINT FK_RegistrosContador_Ruta
    FOREIGN KEY (IdRuta)
    REFERENCES dbo.Rutas(IdRuta),

    CONSTRAINT FK_RegistrosContador_Salida
    FOREIGN KEY (IdSalida)
    REFERENCES dbo.RutaSalidas(IdSalida),

    CONSTRAINT UQ_RegistrosContador_Contador_Salida_Fecha
    UNIQUE (IdContador, IdSalida, FechaRegistro)
);
GO

/* =========================
   TABLAS DEL MAPA ANTERIOR
   OPCIONALES
========================= */
CREATE TABLE dbo.MapaMarcadores (
    IdMarcador INT IDENTITY(1,1) PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Lat DECIMAL(10,7) NOT NULL,
    Lng DECIMAL(10,7) NOT NULL,
    Cantidad INT NOT NULL DEFAULT 0,
    FechaRegistro DATETIME DEFAULT GETDATE(),
    Estado BIT DEFAULT 1
);
GO

CREATE TABLE dbo.MapaRutas (
    IdMapaRuta INT IDENTITY(1,1) PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Valor INT NOT NULL DEFAULT 0,
    FechaRegistro DATETIME DEFAULT GETDATE(),
    Estado BIT DEFAULT 1
);
GO

CREATE TABLE dbo.MapaRutaWaypoints (
    IdWaypoint INT IDENTITY(1,1) PRIMARY KEY,
    IdMapaRuta INT NOT NULL,
    Lat DECIMAL(10,7) NOT NULL,
    Lng DECIMAL(10,7) NOT NULL,
    OrdenWaypoint INT NOT NULL,

    CONSTRAINT FK_MapaRutaWaypoints_MapaRutas
    FOREIGN KEY (IdMapaRuta)
    REFERENCES dbo.MapaRutas(IdMapaRuta)
    ON DELETE CASCADE,

    CONSTRAINT UQ_MapaRutaWaypoints_Ruta_Orden
    UNIQUE (IdMapaRuta, OrdenWaypoint)
);
GO

/* =========================
   RUTAS FAVORITAS DE USUARIOS
========================= */
CREATE TABLE dbo.RutasFavoritas (
    IdFavorito INT IDENTITY(1,1) PRIMARY KEY,

    IdUsuario INT NOT NULL,
    IdRuta INT NOT NULL,

    FechaGuardado DATETIME NOT NULL DEFAULT GETDATE(),
    Estado BIT NOT NULL DEFAULT 1,

    CONSTRAINT FK_RutasFavoritas_Usuarios
    FOREIGN KEY (IdUsuario)
    REFERENCES dbo.Usuarios(IdUsuario),

    CONSTRAINT FK_RutasFavoritas_Rutas
    FOREIGN KEY (IdRuta)
    REFERENCES dbo.Rutas(IdRuta),

    CONSTRAINT UQ_RutasFavoritas_Usuario_Ruta
    UNIQUE (IdUsuario, IdRuta)
);
GO

/* =========================
   CONSULTAS DE PRUEBA
========================= */
SELECT * FROM dbo.TiposUsuario;
SELECT * FROM dbo.CategoriasEmpresa;
SELECT * FROM dbo.Usuarios;
SELECT * FROM dbo.Rutas;
SELECT * FROM dbo.RutaSalidas;
SELECT * FROM dbo.PuntosControl;
SELECT * FROM dbo.ContadoresEmpresa;
SELECT * FROM dbo.RegistrosContador;
GO