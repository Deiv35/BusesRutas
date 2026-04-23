<?php
session_start();
require_once(__DIR__ . "/conexion.php");

if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'admin') {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$error = "";

function limpiar($dato) {
    return htmlspecialchars(trim((string)$dato), ENT_QUOTES, 'UTF-8');
}

$usuarioSeleccionado = null;
$empresaSeleccionada = null;
$rutaSeleccionada = null;
$municipioSeleccionado = null;

$idUsuarioSeleccionado = isset($_GET['usuario']) ? (int)$_GET['usuario'] : 0;
$idRutaSeleccionada = isset($_GET['ruta']) ? (int)$_GET['ruta'] : 0;
$idMunicipioSeleccionado = isset($_GET['municipio']) ? (int)$_GET['municipio'] : 0;

try {

    // =========================================
    // CREAR USUARIO (+ EMPRESA SI APLICA)
    // =========================================
    if (isset($_POST['crear_usuario'])) {
        $tipoUsuario = trim($_POST['tipo_usuario']);
        $nombreUsuario = trim($_POST['nombre_usuario']);
        $correo = trim($_POST['correo']);
        $contra = trim($_POST['contra']);
        $estado = isset($_POST['estado']) ? 1 : 0;

        if ($tipoUsuario !== 'admin' && $tipoUsuario !== 'empresa') {
            throw new Exception("Tipo de usuario inválido.");
        }

        if ($nombreUsuario === "" || $correo === "" || $contra === "") {
            throw new Exception("Completa los datos obligatorios del usuario.");
        }

        $conn->beginTransaction();

        $sqlInsertUsuario = "INSERT INTO Usuarios (TipoUsuario, NombreUsuario, Correo, Contra, Estado)
                             VALUES (:tipo, :nombre, :correo, :contra, :estado)";
        $stmtInsertUsuario = $conn->prepare($sqlInsertUsuario);
        $stmtInsertUsuario->bindParam(':tipo', $tipoUsuario);
        $stmtInsertUsuario->bindParam(':nombre', $nombreUsuario);
        $stmtInsertUsuario->bindParam(':correo', $correo);
        $stmtInsertUsuario->bindParam(':contra', $contra);
        $stmtInsertUsuario->bindParam(':estado', $estado, PDO::PARAM_INT);
        $stmtInsertUsuario->execute();

        $stmtIdUsuario = $conn->query("SELECT SCOPE_IDENTITY() AS IdUsuario");
        $idNuevoUsuario = (int)$stmtIdUsuario->fetch(PDO::FETCH_ASSOC)['IdUsuario'];

        if ($tipoUsuario === 'empresa') {
            $nombreEmpresa = trim($_POST['nombre_empresa']);
            $nit = trim($_POST['nit']);
            $direccion = trim($_POST['direccion']);
            $telefono = trim($_POST['telefono']);
            $ciudad = trim($_POST['ciudad']);
            $correoEmpresa = trim($_POST['correo_empresa']);
            $nombreContacto = trim($_POST['nombre_contacto']);
            $estadoEmpresa = isset($_POST['estado_empresa']) ? 1 : 0;

            if ($nombreEmpresa === "" || $nit === "" || $direccion === "" || $telefono === "" || $ciudad === "") {
                throw new Exception("Completa los datos obligatorios de la empresa.");
            }

            $sqlInsertEmpresa = "INSERT INTO Empresas (
                                    IdUsuario, NombreEmpresa, NIT, Direccion, Telefono, Ciudad,
                                    CorreoEmpresa, NombreContacto, Estado
                                 )
                                 VALUES (
                                    :id_usuario, :nombre_empresa, :nit, :direccion, :telefono, :ciudad,
                                    :correo_empresa, :nombre_contacto, :estado_empresa
                                 )";
            $stmtInsertEmpresa = $conn->prepare($sqlInsertEmpresa);
            $stmtInsertEmpresa->bindParam(':id_usuario', $idNuevoUsuario, PDO::PARAM_INT);
            $stmtInsertEmpresa->bindParam(':nombre_empresa', $nombreEmpresa);
            $stmtInsertEmpresa->bindParam(':nit', $nit);
            $stmtInsertEmpresa->bindParam(':direccion', $direccion);
            $stmtInsertEmpresa->bindParam(':telefono', $telefono);
            $stmtInsertEmpresa->bindParam(':ciudad', $ciudad);
            $stmtInsertEmpresa->bindParam(':correo_empresa', $correoEmpresa);
            $stmtInsertEmpresa->bindParam(':nombre_contacto', $nombreContacto);
            $stmtInsertEmpresa->bindParam(':estado_empresa', $estadoEmpresa, PDO::PARAM_INT);
            $stmtInsertEmpresa->execute();
        }

        $conn->commit();
        $mensaje = "Usuario creado correctamente.";
    }

    // =========================================
    // ACTUALIZAR USUARIO (+ EMPRESA SI APLICA)
    // =========================================
    if (isset($_POST['actualizar_usuario'])) {
        $idUsuario = (int)$_POST['id_usuario'];
        $tipoUsuario = trim($_POST['tipo_usuario']);
        $nombreUsuario = trim($_POST['nombre_usuario']);
        $correo = trim($_POST['correo']);
        $contra = trim($_POST['contra']);
        $estado = isset($_POST['estado']) ? 1 : 0;

        if ($idUsuario <= 0) {
            throw new Exception("Usuario inválido.");
        }

        if ($tipoUsuario !== 'admin' && $tipoUsuario !== 'empresa') {
            throw new Exception("Tipo de usuario inválido.");
        }

        if ($nombreUsuario === "" || $correo === "") {
            throw new Exception("Completa los datos obligatorios del usuario.");
        }

        $conn->beginTransaction();

        if ($contra !== "") {
            $sqlUpdateUsuario = "UPDATE Usuarios
                                 SET TipoUsuario = :tipo,
                                     NombreUsuario = :nombre,
                                     Correo = :correo,
                                     Contra = :contra,
                                     Estado = :estado
                                 WHERE IdUsuario = :id_usuario";
            $stmtUpdateUsuario = $conn->prepare($sqlUpdateUsuario);
            $stmtUpdateUsuario->bindParam(':tipo', $tipoUsuario);
            $stmtUpdateUsuario->bindParam(':nombre', $nombreUsuario);
            $stmtUpdateUsuario->bindParam(':correo', $correo);
            $stmtUpdateUsuario->bindParam(':contra', $contra);
            $stmtUpdateUsuario->bindParam(':estado', $estado, PDO::PARAM_INT);
            $stmtUpdateUsuario->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
            $stmtUpdateUsuario->execute();
        } else {
            $sqlUpdateUsuario = "UPDATE Usuarios
                                 SET TipoUsuario = :tipo,
                                     NombreUsuario = :nombre,
                                     Correo = :correo,
                                     Estado = :estado
                                 WHERE IdUsuario = :id_usuario";
            $stmtUpdateUsuario = $conn->prepare($sqlUpdateUsuario);
            $stmtUpdateUsuario->bindParam(':tipo', $tipoUsuario);
            $stmtUpdateUsuario->bindParam(':nombre', $nombreUsuario);
            $stmtUpdateUsuario->bindParam(':correo', $correo);
            $stmtUpdateUsuario->bindParam(':estado', $estado, PDO::PARAM_INT);
            $stmtUpdateUsuario->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
            $stmtUpdateUsuario->execute();
        }

        // revisar si ya existe empresa asociada
        $sqlBuscarEmpresa = "SELECT IdEmpresa FROM Empresas WHERE IdUsuario = :id_usuario";
        $stmtBuscarEmpresa = $conn->prepare($sqlBuscarEmpresa);
        $stmtBuscarEmpresa->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmtBuscarEmpresa->execute();
        $empresaExistente = $stmtBuscarEmpresa->fetch(PDO::FETCH_ASSOC);

        if ($tipoUsuario === 'empresa') {
            $nombreEmpresa = trim($_POST['nombre_empresa']);
            $nit = trim($_POST['nit']);
            $direccion = trim($_POST['direccion']);
            $telefono = trim($_POST['telefono']);
            $ciudad = trim($_POST['ciudad']);
            $correoEmpresa = trim($_POST['correo_empresa']);
            $nombreContacto = trim($_POST['nombre_contacto']);
            $estadoEmpresa = isset($_POST['estado_empresa']) ? 1 : 0;

            if ($nombreEmpresa === "" || $nit === "" || $direccion === "" || $telefono === "" || $ciudad === "") {
                throw new Exception("Completa los datos obligatorios de la empresa.");
            }

            if ($empresaExistente) {
                $sqlUpdateEmpresa = "UPDATE Empresas
                                     SET NombreEmpresa = :nombre_empresa,
                                         NIT = :nit,
                                         Direccion = :direccion,
                                         Telefono = :telefono,
                                         Ciudad = :ciudad,
                                         CorreoEmpresa = :correo_empresa,
                                         NombreContacto = :nombre_contacto,
                                         Estado = :estado_empresa
                                     WHERE IdUsuario = :id_usuario";
                $stmtUpdateEmpresa = $conn->prepare($sqlUpdateEmpresa);
                $stmtUpdateEmpresa->bindParam(':nombre_empresa', $nombreEmpresa);
                $stmtUpdateEmpresa->bindParam(':nit', $nit);
                $stmtUpdateEmpresa->bindParam(':direccion', $direccion);
                $stmtUpdateEmpresa->bindParam(':telefono', $telefono);
                $stmtUpdateEmpresa->bindParam(':ciudad', $ciudad);
                $stmtUpdateEmpresa->bindParam(':correo_empresa', $correoEmpresa);
                $stmtUpdateEmpresa->bindParam(':nombre_contacto', $nombreContacto);
                $stmtUpdateEmpresa->bindParam(':estado_empresa', $estadoEmpresa, PDO::PARAM_INT);
                $stmtUpdateEmpresa->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
                $stmtUpdateEmpresa->execute();
            } else {
                $sqlInsertEmpresa = "INSERT INTO Empresas (
                                        IdUsuario, NombreEmpresa, NIT, Direccion, Telefono, Ciudad,
                                        CorreoEmpresa, NombreContacto, Estado
                                     )
                                     VALUES (
                                        :id_usuario, :nombre_empresa, :nit, :direccion, :telefono, :ciudad,
                                        :correo_empresa, :nombre_contacto, :estado_empresa
                                     )";
                $stmtInsertEmpresa = $conn->prepare($sqlInsertEmpresa);
                $stmtInsertEmpresa->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
                $stmtInsertEmpresa->bindParam(':nombre_empresa', $nombreEmpresa);
                $stmtInsertEmpresa->bindParam(':nit', $nit);
                $stmtInsertEmpresa->bindParam(':direccion', $direccion);
                $stmtInsertEmpresa->bindParam(':telefono', $telefono);
                $stmtInsertEmpresa->bindParam(':ciudad', $ciudad);
                $stmtInsertEmpresa->bindParam(':correo_empresa', $correoEmpresa);
                $stmtInsertEmpresa->bindParam(':nombre_contacto', $nombreContacto);
                $stmtInsertEmpresa->bindParam(':estado_empresa', $estadoEmpresa, PDO::PARAM_INT);
                $stmtInsertEmpresa->execute();
            }
        } else {
            // si pasó a admin y existía empresa, la eliminamos
            if ($empresaExistente) {
                $sqlDeleteEmpresa = "DELETE FROM Empresas WHERE IdUsuario = :id_usuario";
                $stmtDeleteEmpresa = $conn->prepare($sqlDeleteEmpresa);
                $stmtDeleteEmpresa->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
                $stmtDeleteEmpresa->execute();
            }
        }

        $conn->commit();
        $mensaje = "Usuario actualizado correctamente.";
        $idUsuarioSeleccionado = $idUsuario;
    }

    // =========================================
    // ELIMINAR USUARIO
    // =========================================
    if (isset($_POST['eliminar_usuario'])) {
        $idUsuario = (int)$_POST['id_usuario'];

        $sqlDeleteUsuario = "DELETE FROM Usuarios WHERE IdUsuario = :id_usuario";
        $stmtDeleteUsuario = $conn->prepare($sqlDeleteUsuario);
        $stmtDeleteUsuario->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmtDeleteUsuario->execute();

        $mensaje = "Usuario eliminado correctamente.";
        if ($idUsuarioSeleccionado === $idUsuario) {
            $idUsuarioSeleccionado = 0;
        }
    }

    // =========================================
    // CREAR MUNICIPIO
    // =========================================
    if (isset($_POST['crear_municipio'])) {
        $nombreMunicipio = trim($_POST['nombre_municipio']);
        $departamento = trim($_POST['departamento']);
        $estadoMunicipio = isset($_POST['estado_municipio']) ? 1 : 0;

        $sqlInsertMunicipio = "INSERT INTO Municipios (NombreMunicipio, Departamento, Estado)
                               VALUES (:nombre, :departamento, :estado)";
        $stmtInsertMunicipio = $conn->prepare($sqlInsertMunicipio);
        $stmtInsertMunicipio->bindParam(':nombre', $nombreMunicipio);
        $stmtInsertMunicipio->bindParam(':departamento', $departamento);
        $stmtInsertMunicipio->bindParam(':estado', $estadoMunicipio, PDO::PARAM_INT);
        $stmtInsertMunicipio->execute();

        $mensaje = "Municipio creado correctamente.";
    }

    // =========================================
    // ACTUALIZAR MUNICIPIO
    // =========================================
    if (isset($_POST['actualizar_municipio'])) {
        $idMunicipio = (int)$_POST['id_municipio'];
        $nombreMunicipio = trim($_POST['nombre_municipio']);
        $departamento = trim($_POST['departamento']);
        $estadoMunicipio = isset($_POST['estado_municipio']) ? 1 : 0;

        $sqlUpdateMunicipio = "UPDATE Municipios
                               SET NombreMunicipio = :nombre,
                                   Departamento = :departamento,
                                   Estado = :estado
                               WHERE IdMunicipio = :id_municipio";
        $stmtUpdateMunicipio = $conn->prepare($sqlUpdateMunicipio);
        $stmtUpdateMunicipio->bindParam(':nombre', $nombreMunicipio);
        $stmtUpdateMunicipio->bindParam(':departamento', $departamento);
        $stmtUpdateMunicipio->bindParam(':estado', $estadoMunicipio, PDO::PARAM_INT);
        $stmtUpdateMunicipio->bindParam(':id_municipio', $idMunicipio, PDO::PARAM_INT);
        $stmtUpdateMunicipio->execute();

        $mensaje = "Municipio actualizado correctamente.";
        $idMunicipioSeleccionado = $idMunicipio;
    }

    // =========================================
    // ELIMINAR MUNICIPIO
    // =========================================
    if (isset($_POST['eliminar_municipio'])) {
        $idMunicipio = (int)$_POST['id_municipio'];

        $sqlDeleteMunicipio = "DELETE FROM Municipios WHERE IdMunicipio = :id_municipio";
        $stmtDeleteMunicipio = $conn->prepare($sqlDeleteMunicipio);
        $stmtDeleteMunicipio->bindParam(':id_municipio', $idMunicipio, PDO::PARAM_INT);
        $stmtDeleteMunicipio->execute();

        $mensaje = "Municipio eliminado correctamente.";
        if ($idMunicipioSeleccionado === $idMunicipio) {
            $idMunicipioSeleccionado = 0;
        }
    }

    // =========================================
    // CREAR RUTA
    // =========================================
    if (isset($_POST['crear_ruta_admin'])) {
        $idEmpresaRuta = (int)$_POST['id_empresa_ruta'];
        $nombreRuta = trim($_POST['nombre_ruta']);
        $horaInicio = trim($_POST['hora_inicio']);
        $horaFin = trim($_POST['hora_fin']);
        $descripcionRuta = trim($_POST['descripcion_ruta']);

        if ($idEmpresaRuta <= 0) {
            throw new Exception("Debes seleccionar una empresa.");
        }

        if ($nombreRuta === "" || $horaInicio === "" || $horaFin === "") {
            throw new Exception("Completa los datos obligatorios de la ruta.");
        }

        $conn->beginTransaction();

        $sqlInsertRuta = "INSERT INTO Rutas (IdEmpresa, NombreRuta, HoraInicio, HoraFin, Estado)
                          VALUES (:id_empresa, :nombre_ruta, :hora_inicio, :hora_fin, 1)";
        $stmtInsertRuta = $conn->prepare($sqlInsertRuta);
        $stmtInsertRuta->bindParam(':id_empresa', $idEmpresaRuta, PDO::PARAM_INT);
        $stmtInsertRuta->bindParam(':nombre_ruta', $nombreRuta);
        $stmtInsertRuta->bindParam(':hora_inicio', $horaInicio);
        $stmtInsertRuta->bindParam(':hora_fin', $horaFin);
        $stmtInsertRuta->execute();

        $stmtIdRuta = $conn->query("SELECT SCOPE_IDENTITY() AS IdRuta");
        $idNuevaRuta = (int)$stmtIdRuta->fetch(PDO::FETCH_ASSOC)['IdRuta'];

        $sqlInsertDetalle = "INSERT INTO RutaDetalle (IdRuta, DescripcionRuta)
                             VALUES (:id_ruta, :descripcion)";
        $stmtInsertDetalle = $conn->prepare($sqlInsertDetalle);
        $stmtInsertDetalle->bindParam(':id_ruta', $idNuevaRuta, PDO::PARAM_INT);
        $stmtInsertDetalle->bindParam(':descripcion', $descripcionRuta);
        $stmtInsertDetalle->execute();

        $conn->commit();
        $mensaje = "Ruta creada correctamente.";
    }

    // =========================================
    // ACTUALIZAR RUTA
    // =========================================
    if (isset($_POST['actualizar_ruta_admin'])) {
        $idRuta = (int)$_POST['id_ruta'];
        $idEmpresaRuta = (int)$_POST['id_empresa_ruta'];
        $nombreRuta = trim($_POST['nombre_ruta']);
        $horaInicio = trim($_POST['hora_inicio']);
        $horaFin = trim($_POST['hora_fin']);
        $descripcionRuta = trim($_POST['descripcion_ruta']);
        $estadoRuta = isset($_POST['estado_ruta']) ? 1 : 0;

        $conn->beginTransaction();

        $sqlUpdateRuta = "UPDATE Rutas
                          SET IdEmpresa = :id_empresa,
                              NombreRuta = :nombre_ruta,
                              HoraInicio = :hora_inicio,
                              HoraFin = :hora_fin,
                              Estado = :estado_ruta
                          WHERE IdRuta = :id_ruta";
        $stmtUpdateRuta = $conn->prepare($sqlUpdateRuta);
        $stmtUpdateRuta->bindParam(':id_empresa', $idEmpresaRuta, PDO::PARAM_INT);
        $stmtUpdateRuta->bindParam(':nombre_ruta', $nombreRuta);
        $stmtUpdateRuta->bindParam(':hora_inicio', $horaInicio);
        $stmtUpdateRuta->bindParam(':hora_fin', $horaFin);
        $stmtUpdateRuta->bindParam(':estado_ruta', $estadoRuta, PDO::PARAM_INT);
        $stmtUpdateRuta->bindParam(':id_ruta', $idRuta, PDO::PARAM_INT);
        $stmtUpdateRuta->execute();

        $sqlExisteDetalle = "SELECT IdRutaDetalle FROM RutaDetalle WHERE IdRuta = :id_ruta";
        $stmtExisteDetalle = $conn->prepare($sqlExisteDetalle);
        $stmtExisteDetalle->bindParam(':id_ruta', $idRuta, PDO::PARAM_INT);
        $stmtExisteDetalle->execute();
        $detalleExiste = $stmtExisteDetalle->fetch(PDO::FETCH_ASSOC);

        if ($detalleExiste) {
            $sqlUpdateDetalle = "UPDATE RutaDetalle
                                 SET DescripcionRuta = :descripcion
                                 WHERE IdRuta = :id_ruta";
            $stmtUpdateDetalle = $conn->prepare($sqlUpdateDetalle);
            $stmtUpdateDetalle->bindParam(':descripcion', $descripcionRuta);
            $stmtUpdateDetalle->bindParam(':id_ruta', $idRuta, PDO::PARAM_INT);
            $stmtUpdateDetalle->execute();
        } else {
            $sqlInsertDetalle = "INSERT INTO RutaDetalle (IdRuta, DescripcionRuta)
                                 VALUES (:id_ruta, :descripcion)";
            $stmtInsertDetalle = $conn->prepare($sqlInsertDetalle);
            $stmtInsertDetalle->bindParam(':id_ruta', $idRuta, PDO::PARAM_INT);
            $stmtInsertDetalle->bindParam(':descripcion', $descripcionRuta);
            $stmtInsertDetalle->execute();
        }

        $conn->commit();
        $mensaje = "Ruta actualizada correctamente.";
        $idRutaSeleccionada = $idRuta;
    }

    // =========================================
    // ELIMINAR RUTA
    // =========================================
    if (isset($_POST['eliminar_ruta_admin'])) {
        $idRuta = (int)$_POST['id_ruta'];

        $sqlDeleteRuta = "DELETE FROM Rutas WHERE IdRuta = :id_ruta";
        $stmtDeleteRuta = $conn->prepare($sqlDeleteRuta);
        $stmtDeleteRuta->bindParam(':id_ruta', $idRuta, PDO::PARAM_INT);
        $stmtDeleteRuta->execute();

        $mensaje = "Ruta eliminada correctamente.";
        if ($idRutaSeleccionada === $idRuta) {
            $idRutaSeleccionada = 0;
        }
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $error = "Error en base de datos: " . $e->getMessage();
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $error = $e->getMessage();
}

// =========================================
// CARGAR LISTADOS
// =========================================
$sqlUsuarios = "SELECT IdUsuario, TipoUsuario, NombreUsuario, Correo, Estado
                FROM Usuarios
                ORDER BY TipoUsuario, NombreUsuario";
$stmtUsuarios = $conn->query($sqlUsuarios);
$usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

$sqlMunicipios = "SELECT IdMunicipio, NombreMunicipio, Departamento, Estado
                  FROM Municipios
                  ORDER BY NombreMunicipio";
$stmtMunicipios = $conn->query($sqlMunicipios);
$municipios = $stmtMunicipios->fetchAll(PDO::FETCH_ASSOC);

$sqlEmpresas = "SELECT e.IdEmpresa, e.IdUsuario, e.NombreEmpresa, e.NIT, e.Ciudad, e.Telefono, e.Estado,
                       u.NombreUsuario
                FROM Empresas e
                INNER JOIN Usuarios u ON e.IdUsuario = u.IdUsuario
                ORDER BY e.NombreEmpresa";
$stmtEmpresas = $conn->query($sqlEmpresas);
$empresas = $stmtEmpresas->fetchAll(PDO::FETCH_ASSOC);

// =========================================
// CARGAR USUARIO SELECCIONADO
// =========================================
if ($idUsuarioSeleccionado > 0) {
    $sqlUsuarioSel = "SELECT IdUsuario, TipoUsuario, NombreUsuario, Correo, Contra, Estado
                      FROM Usuarios
                      WHERE IdUsuario = :id_usuario";
    $stmtUsuarioSel = $conn->prepare($sqlUsuarioSel);
    $stmtUsuarioSel->bindParam(':id_usuario', $idUsuarioSeleccionado, PDO::PARAM_INT);
    $stmtUsuarioSel->execute();
    $usuarioSeleccionado = $stmtUsuarioSel->fetch(PDO::FETCH_ASSOC);

    if ($usuarioSeleccionado && $usuarioSeleccionado['TipoUsuario'] === 'empresa') {
        $sqlEmpresaSel = "SELECT *
                          FROM Empresas
                          WHERE IdUsuario = :id_usuario";
        $stmtEmpresaSel = $conn->prepare($sqlEmpresaSel);
        $stmtEmpresaSel->bindParam(':id_usuario', $idUsuarioSeleccionado, PDO::PARAM_INT);
        $stmtEmpresaSel->execute();
        $empresaSeleccionada = $stmtEmpresaSel->fetch(PDO::FETCH_ASSOC);
    }
}

// =========================================
// CARGAR MUNICIPIO SELECCIONADO
// =========================================
if ($idMunicipioSeleccionado > 0) {
    $sqlMunicipioSel = "SELECT IdMunicipio, NombreMunicipio, Departamento, Estado
                        FROM Municipios
                        WHERE IdMunicipio = :id_municipio";
    $stmtMunicipioSel = $conn->prepare($sqlMunicipioSel);
    $stmtMunicipioSel->bindParam(':id_municipio', $idMunicipioSeleccionado, PDO::PARAM_INT);
    $stmtMunicipioSel->execute();
    $municipioSeleccionado = $stmtMunicipioSel->fetch(PDO::FETCH_ASSOC);
}

// =========================================
// CARGAR RUTAS DE EMPRESA SELECCIONADA
// =========================================
$rutasEmpresaSeleccionada = [];
if ($empresaSeleccionada) {
    $sqlRutasEmpresa = "SELECT r.IdRuta, r.NombreRuta, r.HoraInicio, r.HoraFin, r.Estado, rd.DescripcionRuta
                        FROM Rutas r
                        LEFT JOIN RutaDetalle rd ON r.IdRuta = rd.IdRuta
                        WHERE r.IdEmpresa = :id_empresa
                        ORDER BY r.IdRuta DESC";
    $stmtRutasEmpresa = $conn->prepare($sqlRutasEmpresa);
    $stmtRutasEmpresa->bindParam(':id_empresa', $empresaSeleccionada['IdEmpresa'], PDO::PARAM_INT);
    $stmtRutasEmpresa->execute();
    $rutasEmpresaSeleccionada = $stmtRutasEmpresa->fetchAll(PDO::FETCH_ASSOC);
}

// =========================================
// CARGAR RUTA SELECCIONADA
// =========================================
if ($idRutaSeleccionada > 0) {
    $sqlRutaSel = "SELECT r.IdRuta, r.IdEmpresa, r.NombreRuta, r.HoraInicio, r.HoraFin, r.Estado,
                          rd.DescripcionRuta
                   FROM Rutas r
                   LEFT JOIN RutaDetalle rd ON r.IdRuta = rd.IdRuta
                   WHERE r.IdRuta = :id_ruta";
    $stmtRutaSel = $conn->prepare($sqlRutaSel);
    $stmtRutaSel->bindParam(':id_ruta', $idRutaSeleccionada, PDO::PARAM_INT);
    $stmtRutaSel->execute();
    $rutaSeleccionada = $stmtRutaSel->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Administrador</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #121212;
            color: white;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background: #1e1e1e;
        }

        .logo {
            height: 60px;
        }

        .container {
            padding: 25px;
        }

        h1, h2, h3 {
            color: #4da6ff;
        }

        .mensaje {
            background: #1f5f2c;
            color: #d4ffd9;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error {
            background: #6b1d1d;
            color: #ffd4d4;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .layout {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 20px;
        }

        .panel {
            background: #1e1e1e;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.35);
        }

        .lista {
            max-height: 650px;
            overflow-y: auto;
        }

        .item {
            background: #2a2a2a;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
        }

        .item a {
            text-decoration: none;
            color: white;
        }

        .item small {
            color: #bbb;
        }

        .bloques {
            display: grid;
            gap: 20px;
        }

        .bloque {
            background: #1e1e1e;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.35);
        }

        form {
            margin-top: 10px;
        }

        label {
            display: block;
            margin: 8px 0 4px;
            color: #ccc;
            font-size: 14px;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 8px;
            border: none;
            border-radius: 8px;
            background: #2a2a2a;
            color: white;
            box-sizing: border-box;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        .btn {
            margin-top: 8px;
            padding: 10px 14px;
            background: #4da6ff;
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
        }

        .btn-danger {
            background: #d9534f;
        }

        .btn-warning {
            background: #f0ad4e;
            color: #111;
        }

        .acciones {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .fila-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .separador {
            margin: 25px 0 10px;
            border-top: 1px solid #333;
        }

        .tabla-simple .item {
            margin-bottom: 12px;
        }

        @media (max-width: 900px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .fila-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="navbar">
    <img src="logo.jpg" class="logo" alt="Logo">
    <div>
        <a href="index.php"><button class="btn" type="button">Inicio</button></a>
        <a href="logout.php"><button class="btn btn-danger" type="button">Cerrar sesión</button></a>
    </div>
</div>

<div class="container">
    <h1>Panel de Administrador</h1>

    <?php if ($mensaje !== ""): ?>
        <div class="mensaje"><?php echo limpiar($mensaje); ?></div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="error"><?php echo limpiar($error); ?></div>
    <?php endif; ?>

    <div class="layout">

        <!-- LISTADO -->
        <div class="panel">
            <h3>Usuarios</h3>
            <div class="lista">
                <?php foreach ($usuarios as $u): ?>
                    <div class="item">
                        <a href="admin.php?usuario=<?php echo $u['IdUsuario']; ?>">
                            <strong><?php echo limpiar($u['NombreUsuario']); ?></strong><br>
                            <small><?php echo limpiar($u['TipoUsuario']); ?> - <?php echo limpiar($u['Correo']); ?></small>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="separador"></div>

            <h3>Municipios</h3>
            <div class="lista">
                <?php foreach ($municipios as $m): ?>
                    <div class="item">
                        <a href="admin.php?municipio=<?php echo $m['IdMunicipio']; ?>">
                            <strong><?php echo limpiar($m['NombreMunicipio']); ?></strong><br>
                            <small><?php echo limpiar($m['Departamento']); ?></small>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- CONTENIDO -->
        <div class="bloques">

            <!-- CRUD USUARIO / EMPRESA -->
            <div class="bloque">
                <h2><?php echo $usuarioSeleccionado ? 'Editar usuario' : 'Crear usuario'; ?></h2>

                <form method="POST">
                    <?php if ($usuarioSeleccionado): ?>
                        <input type="hidden" name="id_usuario" value="<?php echo $usuarioSeleccionado['IdUsuario']; ?>">
                    <?php endif; ?>

                    <div class="fila-2">
                        <div>
                            <label for="tipo_usuario">Tipo de usuario</label>
                            <select name="tipo_usuario" id="tipo_usuario" required>
                                <option value="">Seleccione</option>
                                <option value="admin" <?php echo ($usuarioSeleccionado && $usuarioSeleccionado['TipoUsuario'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="empresa" <?php echo ($usuarioSeleccionado && $usuarioSeleccionado['TipoUsuario'] === 'empresa') ? 'selected' : ''; ?>>Empresa</option>
                            </select>
                        </div>
                        <div>
                            <label for="estado">Estado del usuario</label>
                            <input type="checkbox" name="estado" id="estado" <?php echo (!$usuarioSeleccionado || (int)$usuarioSeleccionado['Estado'] === 1) ? 'checked' : ''; ?>>
                        </div>
                    </div>

                    <label for="nombre_usuario">Nombre de usuario</label>
                    <input type="text" name="nombre_usuario" id="nombre_usuario" required
                           value="<?php echo $usuarioSeleccionado ? limpiar($usuarioSeleccionado['NombreUsuario']) : ''; ?>">

                    <label for="correo">Correo</label>
                    <input type="email" name="correo" id="correo" required
                           value="<?php echo $usuarioSeleccionado ? limpiar($usuarioSeleccionado['Correo']) : ''; ?>">

                    <label for="contra">Contraseña <?php echo $usuarioSeleccionado ? '(déjala vacía si no quieres cambiarla)' : ''; ?></label>
                    <input type="text" name="contra" id="contra"
                           <?php echo $usuarioSeleccionado ? '' : 'required'; ?>
                           value="">

                    <div class="separador"></div>
                    <h3>Datos de empresa</h3>

                    <div class="fila-2">
                        <div>
                            <label for="nombre_empresa">Nombre de la empresa</label>
                            <input type="text" name="nombre_empresa" id="nombre_empresa"
                                   value="<?php echo $empresaSeleccionada ? limpiar($empresaSeleccionada['NombreEmpresa']) : ''; ?>">
                        </div>
                        <div>
                            <label for="nit">NIT</label>
                            <input type="text" name="nit" id="nit"
                                   value="<?php echo $empresaSeleccionada ? limpiar($empresaSeleccionada['NIT']) : ''; ?>">
                        </div>
                    </div>

                    <label for="direccion">Dirección</label>
                    <input type="text" name="direccion" id="direccion"
                           value="<?php echo $empresaSeleccionada ? limpiar($empresaSeleccionada['Direccion']) : ''; ?>">

                    <div class="fila-2">
                        <div>
                            <label for="telefono">Teléfono</label>
                            <input type="text" name="telefono" id="telefono"
                                   value="<?php echo $empresaSeleccionada ? limpiar($empresaSeleccionada['Telefono']) : ''; ?>">
                        </div>
                        <div>
                            <label for="ciudad">Ciudad</label>
                            <input type="text" name="ciudad" id="ciudad"
                                   value="<?php echo $empresaSeleccionada ? limpiar($empresaSeleccionada['Ciudad']) : ''; ?>">
                        </div>
                    </div>

                    <div class="fila-2">
                        <div>
                            <label for="correo_empresa">Correo empresa</label>
                            <input type="email" name="correo_empresa" id="correo_empresa"
                                   value="<?php echo $empresaSeleccionada ? limpiar($empresaSeleccionada['CorreoEmpresa']) : ''; ?>">
                        </div>
                        <div>
                            <label for="nombre_contacto">Nombre de contacto</label>
                            <input type="text" name="nombre_contacto" id="nombre_contacto"
                                   value="<?php echo $empresaSeleccionada ? limpiar($empresaSeleccionada['NombreContacto']) : ''; ?>">
                        </div>
                    </div>

                    <label for="estado_empresa">Estado de la empresa</label>
                    <input type="checkbox" name="estado_empresa" id="estado_empresa"
                           <?php echo (!$empresaSeleccionada || (int)($empresaSeleccionada['Estado'] ?? 1) === 1) ? 'checked' : ''; ?>>

                    <div class="acciones">
                        <?php if ($usuarioSeleccionado): ?>
                            <button class="btn" type="submit" name="actualizar_usuario">Actualizar usuario</button>
                            <button class="btn btn-warning" type="reset">Limpiar campos</button>

                            <button class="btn btn-warning" type="button" onclick="window.location.href='admin.php';">Nuevo</button>

                            <button class="btn btn-danger" type="submit" name="eliminar_usuario"
                                    onclick="return confirm('¿Seguro que deseas eliminar este usuario?');">
                                Eliminar usuario
                            </button>
                        <?php else: ?>
                            <button class="btn" type="submit" name="crear_usuario">Crear usuario</button>
                            <button class="btn btn-warning" type="reset">Limpiar campos</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- CRUD RUTAS DE EMPRESA SELECCIONADA -->
            <?php if ($empresaSeleccionada): ?>
                <div class="bloque">
                    <h2>Rutas de <?php echo limpiar($empresaSeleccionada['NombreEmpresa']); ?></h2>

                    <form method="POST">
                        <?php if ($rutaSeleccionada): ?>
                            <input type="hidden" name="id_ruta" value="<?php echo $rutaSeleccionada['IdRuta']; ?>">
                        <?php endif; ?>

                        <input type="hidden" name="id_empresa_ruta" value="<?php echo $empresaSeleccionada['IdEmpresa']; ?>">

                        <label for="nombre_ruta">Nombre de la ruta</label>
                        <input type="text" name="nombre_ruta" id="nombre_ruta" required
                               value="<?php echo $rutaSeleccionada ? limpiar($rutaSeleccionada['NombreRuta']) : ''; ?>">

                        <div class="fila-2">
                            <div>
                                <label for="hora_inicio">Hora de inicio</label>
                                <input type="time" name="hora_inicio" id="hora_inicio" required
                                       value="<?php echo $rutaSeleccionada ? substr((string)$rutaSeleccionada['HoraInicio'], 0, 5) : ''; ?>">
                            </div>
                            <div>
                                <label for="hora_fin">Hora de fin</label>
                                <input type="time" name="hora_fin" id="hora_fin" required
                                       value="<?php echo $rutaSeleccionada ? substr((string)$rutaSeleccionada['HoraFin'], 0, 5) : ''; ?>">
                            </div>
                        </div>

                        <label for="descripcion_ruta">Descripción</label>
                        <textarea name="descripcion_ruta" id="descripcion_ruta"><?php echo $rutaSeleccionada ? limpiar($rutaSeleccionada['DescripcionRuta']) : ''; ?></textarea>

                        <?php if ($rutaSeleccionada): ?>
                            <label for="estado_ruta">Estado de la ruta</label>
                            <input type="checkbox" name="estado_ruta" id="estado_ruta"
                                   <?php echo ((int)$rutaSeleccionada['Estado'] === 1) ? 'checked' : ''; ?>>
                        <?php endif; ?>

                        <div class="acciones">
                            <?php if ($rutaSeleccionada): ?>
                                <button class="btn" type="submit" name="actualizar_ruta_admin">Actualizar ruta</button>
                                <button class="btn btn-warning" type="reset">Limpiar campos</button>

                                <button class="btn btn-warning" type="button"
                                        onclick="window.location.href='admin.php?usuario=<?php echo $usuarioSeleccionado['IdUsuario']; ?>';">
                                    Nueva ruta
                                </button>

                                <button class="btn btn-danger" type="submit" name="eliminar_ruta_admin"
                                        onclick="return confirm('¿Seguro que deseas eliminar esta ruta?');">
                                    Eliminar ruta
                                </button>
                            <?php else: ?>
                                <button class="btn" type="submit" name="crear_ruta_admin">Crear ruta</button>
                                <button class="btn btn-warning" type="reset">Limpiar campos</button>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="separador"></div>
                    <h3>Listado de rutas</h3>

                    <div class="tabla-simple">
                        <?php if (count($rutasEmpresaSeleccionada) > 0): ?>
                            <?php foreach ($rutasEmpresaSeleccionada as $r): ?>
                                <div class="item">
                                    <a href="admin.php?usuario=<?php echo $usuarioSeleccionado['IdUsuario']; ?>&ruta=<?php echo $r['IdRuta']; ?>">
                                        <strong><?php echo limpiar($r['NombreRuta']); ?></strong><br>
                                        <small>
                                            <?php echo limpiar(substr((string)$r['HoraInicio'], 0, 5)); ?> -
                                            <?php echo limpiar(substr((string)$r['HoraFin'], 0, 5)); ?>
                                        </small>
                                    </a>
                                    <p><?php echo limpiar($r['DescripcionRuta'] ?: 'Sin descripción'); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No hay rutas para esta empresa.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- CRUD MUNICIPIOS -->
            <div class="bloque">
                <h2><?php echo $municipioSeleccionado ? 'Editar municipio' : 'Crear municipio'; ?></h2>

                <form method="POST">
                    <?php if ($municipioSeleccionado): ?>
                        <input type="hidden" name="id_municipio" value="<?php echo $municipioSeleccionado['IdMunicipio']; ?>">
                    <?php endif; ?>

                    <label for="nombre_municipio">Nombre del municipio</label>
                    <input type="text" name="nombre_municipio" id="nombre_municipio" required
                           value="<?php echo $municipioSeleccionado ? limpiar($municipioSeleccionado['NombreMunicipio']) : ''; ?>">

                    <label for="departamento">Departamento</label>
                    <input type="text" name="departamento" id="departamento" required
                           value="<?php echo $municipioSeleccionado ? limpiar($municipioSeleccionado['Departamento']) : ''; ?>">

                    <label for="estado_municipio">Estado</label>
                    <input type="checkbox" name="estado_municipio" id="estado_municipio"
                           <?php echo (!$municipioSeleccionado || (int)$municipioSeleccionado['Estado'] === 1) ? 'checked' : ''; ?>>

                    <div class="acciones">
                        <?php if ($municipioSeleccionado): ?>
                            <button class="btn" type="submit" name="actualizar_municipio">Actualizar municipio</button>
                            <button class="btn btn-warning" type="reset">Limpiar campos</button>

                            <button class="btn btn-warning" type="button"
                                    onclick="window.location.href='admin.php<?php echo $usuarioSeleccionado ? '?usuario=' . $usuarioSeleccionado['IdUsuario'] : ''; ?>';">
                                Nuevo
                            </button>

                            <button class="btn btn-danger" type="submit" name="eliminar_municipio"
                                    onclick="return confirm('¿Seguro que deseas eliminar este municipio?');">
                                Eliminar municipio
                            </button>
                        <?php else: ?>
                            <button class="btn" type="submit" name="crear_municipio">Crear municipio</button>
                            <button class="btn btn-warning" type="reset">Limpiar campos</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

</body>
</html>