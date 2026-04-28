<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION["idUsuario"]) || $_SESSION["tipo"] != "Empresa") {
    header("Location: IniciarSesion.php");
    exit();
}

if (($_SESSION["categoriaEmpresa"] ?? "") == "Contador") {
    header("Location: Contador.php");
    exit();
}

$mensaje = "";
$error = "";

$idEmpresaSesion = (int)$_SESSION["idUsuario"];
$idRutaEditar = isset($_GET["ruta"]) ? (int)$_GET["ruta"] : 0;
$idContadorEditar = isset($_GET["contador"]) ? (int)$_GET["contador"] : 0;

$contadorEditar = null;
$rutaEditar = null;
$paradasRuta = [];
$salidasRuta = [];

function limpiar($dato) {
    return htmlspecialchars(trim((string)$dato), ENT_QUOTES, "UTF-8");
}

function generarCodigoAcceso($conexion) {
    do {
        $codigo = str_pad((string)random_int(0, 99999999), 8, "0", STR_PAD_LEFT);

        $stmt = $conexion->prepare("
            SELECT 
                (
                    SELECT COUNT(*)
                    FROM dbo.ContadoresEmpresa
                    WHERE CodigoAcceso = :codigo1
                )
                +
                (
                    SELECT COUNT(*)
                    FROM dbo.PuntosControl
                    WHERE CodigoAcceso = :codigo2
                )
        ");

        $stmt->execute([
            ":codigo1" => $codigo,
            ":codigo2" => $codigo
        ]);

        $existe = (int)$stmt->fetchColumn();
    } while ($existe > 0);

    return $codigo;
}
try {

    /* =========================
       CREAR CONTADOR
    ========================= */
    if (isset($_POST["crearContador"])) {
        $nombreContador = trim($_POST["nombreContador"]);
        $usuarioContador = trim($_POST["usuarioContador"]);
        $correoContador = trim($_POST["correoContador"]);
        $cedulaContador = trim($_POST["cedulaContador"]);
        $contrasenaContador = $_POST["contrasenaContador"];
        $idPuntoControl = !empty($_POST["idPuntoControl"]) ? (int)$_POST["idPuntoControl"] : null;

        if ($nombreContador == "" || $usuarioContador == "" || $correoContador == "" || $cedulaContador == "" || $contrasenaContador == "") {
            throw new Exception("Todos los campos del contador son obligatorios.");
        }

        $hash = password_hash($contrasenaContador, PASSWORD_DEFAULT);
        $codigoAcceso = generarCodigoAcceso($conexion);

        $conexion->beginTransaction();

        $stmt = $conexion->prepare("
            INSERT INTO dbo.Usuarios (
                NombreUsuario,
                Correo,
                PasswordHash,
                IdTipoUsuario,
                IdCategoriaEmpresa,
                NombreEmpresa,
                Estado
            )
            OUTPUT INSERTED.IdUsuario
            VALUES (
                :usuario,
                :correo,
                :hash,
                2,
                2,
                :nombreEmpresa,
                1
            )
        ");

        $stmt->execute([
            ":usuario" => $usuarioContador,
            ":correo" => $correoContador,
            ":hash" => $hash,
            ":nombreEmpresa" => "Contador - " . $nombreContador
        ]);

        $idUsuarioContador = (int)$stmt->fetchColumn();

        $stmt = $conexion->prepare("
            INSERT INTO dbo.ContadoresEmpresa (
                IdUsuarioContador,
                IdEmpresa,
                IdPuntoControl,
                NombreContador,
                CedulaContador,
                CodigoAcceso,
                Estado
            )
            VALUES (
                :idUsuarioContador,
                :idEmpresa,
                :idPuntoControl,
                :nombreContador,
                :cedulaContador,
                :codigoAcceso,
                1
            )
        ");

        $stmt->execute([
            ":idUsuarioContador" => $idUsuarioContador,
            ":idEmpresa" => $idEmpresaSesion,
            ":idPuntoControl" => $idPuntoControl,
            ":nombreContador" => $nombreContador,
            ":cedulaContador" => $cedulaContador,
            ":codigoAcceso" => $codigoAcceso
        ]);

        $conexion->commit();

        $mensaje = "Contador creado correctamente. Código de acceso: " . $codigoAcceso;
    }

    /* =========================
       ACTUALIZAR CONTADOR
    ========================= */
    if (isset($_POST["actualizarContador"])) {
        $idContador = (int)$_POST["idContador"];
        $idUsuarioContador = (int)$_POST["idUsuarioContador"];

        $nombreContador = trim($_POST["nombreContador"]);
        $usuarioContador = trim($_POST["usuarioContador"]);
        $correoContador = trim($_POST["correoContador"]);
        $cedulaContador = trim($_POST["cedulaContador"]);
        $contrasenaContador = $_POST["contrasenaContador"];
        $idPuntoControl = !empty($_POST["idPuntoControl"]) ? (int)$_POST["idPuntoControl"] : null;
        $estadoContador = isset($_POST["estadoContador"]) ? (int)$_POST["estadoContador"] : 1;

        if ($nombreContador == "" || $usuarioContador == "" || $correoContador == "" || $cedulaContador == "") {
            throw new Exception("Nombre, usuario, correo y cédula son obligatorios.");
        }

        $conexion->beginTransaction();

        if (!empty($contrasenaContador)) {
            $hash = password_hash($contrasenaContador, PASSWORD_DEFAULT);

            $stmt = $conexion->prepare("
                UPDATE dbo.Usuarios
                SET 
                    NombreUsuario = :usuario,
                    Correo = :correo,
                    PasswordHash = :hash,
                    NombreEmpresa = :nombreContador,
                    Estado = :estado
                WHERE IdUsuario = :idUsuarioContador
                  AND IdUsuario IN (
                      SELECT IdUsuarioContador
                      FROM dbo.ContadoresEmpresa
                      WHERE IdEmpresa = :idEmpresa
                  )
            ");

            $stmt->execute([
                ":usuario" => $usuarioContador,
                ":correo" => $correoContador,
                ":hash" => $hash,
                ":nombreContador" => "Contador - " . $nombreContador,
                ":estado" => $estadoContador,
                ":idUsuarioContador" => $idUsuarioContador,
                ":idEmpresa" => $idEmpresaSesion
            ]);
        } else {
            $stmt = $conexion->prepare("
                UPDATE dbo.Usuarios
                SET 
                    NombreUsuario = :usuario,
                    Correo = :correo,
                    NombreEmpresa = :nombreContador,
                    Estado = :estado
                WHERE IdUsuario = :idUsuarioContador
                  AND IdUsuario IN (
                      SELECT IdUsuarioContador
                      FROM dbo.ContadoresEmpresa
                      WHERE IdEmpresa = :idEmpresa
                  )
            ");

            $stmt->execute([
                ":usuario" => $usuarioContador,
                ":correo" => $correoContador,
                ":nombreContador" => "Contador - " . $nombreContador,
                ":estado" => $estadoContador,
                ":idUsuarioContador" => $idUsuarioContador,
                ":idEmpresa" => $idEmpresaSesion
            ]);
        }

        $stmt = $conexion->prepare("
            UPDATE dbo.ContadoresEmpresa
            SET
                NombreContador = :nombreContador,
                CedulaContador = :cedulaContador,
                IdPuntoControl = :idPuntoControl,
                Estado = :estado
            WHERE IdContador = :idContador
              AND IdEmpresa = :idEmpresa
        ");

        $stmt->execute([
            ":nombreContador" => $nombreContador,
            ":cedulaContador" => $cedulaContador,
            ":idPuntoControl" => $idPuntoControl,
            ":estado" => $estadoContador,
            ":idContador" => $idContador,
            ":idEmpresa" => $idEmpresaSesion
        ]);

        $conexion->commit();

        $mensaje = "Contador actualizado correctamente.";
        $idContadorEditar = $idContador;
    }

    /* =========================
       ELIMINAR / DESACTIVAR CONTADOR
    ========================= */
    if (isset($_GET["eliminarContador"])) {
        $idContador = (int)$_GET["eliminarContador"];

        $conexion->beginTransaction();

        $stmt = $conexion->prepare("
            SELECT IdUsuarioContador
            FROM dbo.ContadoresEmpresa
            WHERE IdContador = :idContador
              AND IdEmpresa = :idEmpresa
        ");

        $stmt->execute([
            ":idContador" => $idContador,
            ":idEmpresa" => $idEmpresaSesion
        ]);

        $contador = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($contador) {
            $stmt = $conexion->prepare("
                UPDATE dbo.ContadoresEmpresa
                SET Estado = 0
                WHERE IdContador = :idContador
                  AND IdEmpresa = :idEmpresa
            ");

            $stmt->execute([
                ":idContador" => $idContador,
                ":idEmpresa" => $idEmpresaSesion
            ]);

            $stmt = $conexion->prepare("
                UPDATE dbo.Usuarios
                SET Estado = 0
                WHERE IdUsuario = :idUsuario
            ");

            $stmt->execute([
                ":idUsuario" => $contador["IdUsuarioContador"]
            ]);

            $mensaje = "Contador desactivado correctamente.";
        }

        $conexion->commit();
    }

    /* =========================
       CREAR RUTA
    ========================= */
    if (isset($_POST["crearRuta"])) {
        $nombreRuta = trim($_POST["nombreRuta"]);
        $precioRuta = (float)$_POST["precioRuta"];
        $horaInicio = $_POST["horaInicio"];
        $horaFin = $_POST["horaFin"];
        $descripcionRuta = trim($_POST["descripcionRuta"] ?? "");

        $municipios = $_POST["municipio"] ?? [];
        $nombresParada = $_POST["nombreParada"] ?? [];
        $direccionesParada = $_POST["direccionParada"] ?? [];

        $horasSalida = $_POST["horaSalida"] ?? [];
        $lugaresSalida = $_POST["lugarSalida"] ?? [];

        if ($nombreRuta == "" || $horaInicio == "" || $horaFin == "") {
            throw new Exception("Completa los datos principales de la ruta.");
        }

        if (count($municipios) < 2) {
            throw new Exception("La ruta debe tener mínimo una parada de inicio y una parada final.");
        }

        if (count($horasSalida) < 1) {
            throw new Exception("La ruta debe tener mínimo una salida.");
        }

        $conexion->beginTransaction();

        $stmt = $conexion->prepare("
            INSERT INTO dbo.Rutas (
                IdEmpresa,
                NombreRuta,
                HoraInicio,
                HoraFin,
                PrecioRuta,
                Estado
            )
            OUTPUT INSERTED.IdRuta
            VALUES (
                :idEmpresa,
                :nombreRuta,
                :horaInicio,
                :horaFin,
                :precioRuta,
                1
            )
        ");

        $stmt->execute([
            ":idEmpresa" => $idEmpresaSesion,
            ":nombreRuta" => $nombreRuta,
            ":horaInicio" => $horaInicio,
            ":horaFin" => $horaFin,
            ":precioRuta" => $precioRuta
        ]);

        $idRuta = (int)$stmt->fetchColumn();

        $stmt = $conexion->prepare("
            INSERT INTO dbo.RutaDetalle (
                IdRuta,
                DescripcionRuta
            )
            VALUES (
                :idRuta,
                :descripcionRuta
            )
        ");

        $stmt->execute([
            ":idRuta" => $idRuta,
            ":descripcionRuta" => $descripcionRuta
        ]);

        $stmtParada = $conexion->prepare("
            INSERT INTO dbo.RutaParadas (
                IdRuta,
                IdMunicipio,
                OrdenParada,
                NombreParada,
                DireccionParada
            )
            VALUES (
                :idRuta,
                :idMunicipio,
                :ordenParada,
                :nombreParada,
                :direccionParada
            )
        ");

        for ($i = 0; $i < count($municipios); $i++) {
            $idMunicipio = (int)$municipios[$i];
            $nombreParada = trim($nombresParada[$i] ?? "");
            $direccionParada = trim($direccionesParada[$i] ?? "");

            if ($idMunicipio <= 0 || $direccionParada == "") {
                throw new Exception("Todas las paradas deben tener municipio y dirección.");
            }

            $stmtParada->execute([
                ":idRuta" => $idRuta,
                ":idMunicipio" => $idMunicipio,
                ":ordenParada" => $i + 1,
                ":nombreParada" => $nombreParada,
                ":direccionParada" => $direccionParada
            ]);
        }

        $stmtSalida = $conexion->prepare("
            INSERT INTO dbo.RutaSalidas (
                IdRuta,
                OrdenSalida,
                HoraSalida,
                LugarSalida
            )
            VALUES (
                :idRuta,
                :ordenSalida,
                :horaSalida,
                :lugarSalida
            )
        ");

        for ($i = 0; $i < count($horasSalida); $i++) {
            $horaSalida = trim($horasSalida[$i]);
            $lugarSalida = trim($lugaresSalida[$i] ?? "");

            if ($horaSalida == "" || $lugarSalida == "") {
                throw new Exception("Todas las salidas deben tener hora y lugar.");
            }

            $stmtSalida->execute([
                ":idRuta" => $idRuta,
                ":ordenSalida" => $i + 1,
                ":horaSalida" => $horaSalida,
                ":lugarSalida" => $lugarSalida
            ]);
        }

        $conexion->commit();

        $mensaje = "Ruta creada correctamente.";
    }

    /* =========================
       ACTUALIZAR RUTA
    ========================= */
    if (isset($_POST["actualizarRuta"])) {
        $idRuta = (int)$_POST["idRuta"];
        $nombreRuta = trim($_POST["nombreRuta"]);
        $precioRuta = (float)$_POST["precioRuta"];
        $horaInicio = $_POST["horaInicio"];
        $horaFin = $_POST["horaFin"];
        $estadoRuta = isset($_POST["estadoRuta"]) ? (int)$_POST["estadoRuta"] : 1;
        $descripcionRuta = trim($_POST["descripcionRuta"] ?? "");

        $municipios = $_POST["municipio"] ?? [];
        $nombresParada = $_POST["nombreParada"] ?? [];
        $direccionesParada = $_POST["direccionParada"] ?? [];

        $horasSalida = $_POST["horaSalida"] ?? [];
        $lugaresSalida = $_POST["lugarSalida"] ?? [];

        if ($nombreRuta == "" || $horaInicio == "" || $horaFin == "") {
            throw new Exception("Completa los datos principales de la ruta.");
        }

        if (count($municipios) < 2) {
            throw new Exception("La ruta debe tener mínimo una parada de inicio y una parada final.");
        }

        if (count($horasSalida) < 1) {
            throw new Exception("La ruta debe tener mínimo una salida.");
        }

        $conexion->beginTransaction();

        $stmt = $conexion->prepare("
            UPDATE dbo.Rutas
            SET
                NombreRuta = :nombreRuta,
                HoraInicio = :horaInicio,
                HoraFin = :horaFin,
                PrecioRuta = :precioRuta,
                Estado = :estadoRuta
            WHERE IdRuta = :idRuta
              AND IdEmpresa = :idEmpresa
        ");

        $stmt->execute([
            ":nombreRuta" => $nombreRuta,
            ":horaInicio" => $horaInicio,
            ":horaFin" => $horaFin,
            ":precioRuta" => $precioRuta,
            ":estadoRuta" => $estadoRuta,
            ":idRuta" => $idRuta,
            ":idEmpresa" => $idEmpresaSesion
        ]);

        $stmt = $conexion->prepare("
            SELECT COUNT(*)
            FROM dbo.RutaDetalle
            WHERE IdRuta = :idRuta
        ");
        $stmt->execute([":idRuta" => $idRuta]);
        $existeDetalle = (int)$stmt->fetchColumn();

        if ($existeDetalle > 0) {
            $stmt = $conexion->prepare("
                UPDATE dbo.RutaDetalle
                SET DescripcionRuta = :descripcionRuta
                WHERE IdRuta = :idRuta
            ");
        } else {
            $stmt = $conexion->prepare("
                INSERT INTO dbo.RutaDetalle (
                    IdRuta,
                    DescripcionRuta
                )
                VALUES (
                    :idRuta,
                    :descripcionRuta
                )
            ");
        }

        $stmt->execute([
            ":idRuta" => $idRuta,
            ":descripcionRuta" => $descripcionRuta
        ]);

        $stmt = $conexion->prepare("
            DELETE FROM dbo.RutaParadas
            WHERE IdRuta = :idRuta
        ");
        $stmt->execute([":idRuta" => $idRuta]);

        $stmt = $conexion->prepare("
            DELETE FROM dbo.RutaSalidas
            WHERE IdRuta = :idRuta
        ");
        $stmt->execute([":idRuta" => $idRuta]);

        $stmtParada = $conexion->prepare("
            INSERT INTO dbo.RutaParadas (
                IdRuta,
                IdMunicipio,
                OrdenParada,
                NombreParada,
                DireccionParada
            )
            VALUES (
                :idRuta,
                :idMunicipio,
                :ordenParada,
                :nombreParada,
                :direccionParada
            )
        ");

        for ($i = 0; $i < count($municipios); $i++) {
            $idMunicipio = (int)$municipios[$i];
            $nombreParada = trim($nombresParada[$i] ?? "");
            $direccionParada = trim($direccionesParada[$i] ?? "");

            if ($idMunicipio <= 0 || $direccionParada == "") {
                throw new Exception("Todas las paradas deben tener municipio y dirección.");
            }

            $stmtParada->execute([
                ":idRuta" => $idRuta,
                ":idMunicipio" => $idMunicipio,
                ":ordenParada" => $i + 1,
                ":nombreParada" => $nombreParada,
                ":direccionParada" => $direccionParada
            ]);
        }

        $stmtSalida = $conexion->prepare("
            INSERT INTO dbo.RutaSalidas (
                IdRuta,
                OrdenSalida,
                HoraSalida,
                LugarSalida
            )
            VALUES (
                :idRuta,
                :ordenSalida,
                :horaSalida,
                :lugarSalida
            )
        ");

        for ($i = 0; $i < count($horasSalida); $i++) {
            $horaSalida = trim($horasSalida[$i]);
            $lugarSalida = trim($lugaresSalida[$i] ?? "");

            if ($horaSalida == "" || $lugarSalida == "") {
                throw new Exception("Todas las salidas deben tener hora y lugar.");
            }

            $stmtSalida->execute([
                ":idRuta" => $idRuta,
                ":ordenSalida" => $i + 1,
                ":horaSalida" => $horaSalida,
                ":lugarSalida" => $lugarSalida
            ]);
        }

        $conexion->commit();

        $mensaje = "Ruta actualizada correctamente.";
        $idRutaEditar = $idRuta;
    }

    /* =========================
       ELIMINAR / DESACTIVAR RUTA
    ========================= */
    if (isset($_GET["eliminarRuta"])) {
        $idRuta = (int)$_GET["eliminarRuta"];

        $stmt = $conexion->prepare("
            UPDATE dbo.Rutas
            SET Estado = 0
            WHERE IdRuta = :idRuta
              AND IdEmpresa = :idEmpresa
        ");

        $stmt->execute([
            ":idRuta" => $idRuta,
            ":idEmpresa" => $idEmpresaSesion
        ]);

        $mensaje = "Ruta desactivada correctamente.";
    }

} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    $error = "Error: " . $e->getMessage();
}

/* =========================
   INFORMACIÓN EMPRESA
========================= */
$stmt = $conexion->prepare("
    SELECT 
        U.IdUsuario,
        U.NombreUsuario,
        U.Correo,
        T.NombreTipo,
        CE.NombreCategoria,
        U.NombreEmpresa,
        U.NitEmpresa,
        U.DireccionEmpresa,
        U.TelefonoEmpresa,
        U.CiudadEmpresa,
        U.CorreoEmpresa,
        U.NombreContacto,
        U.FechaRegistro,
        U.Estado
    FROM dbo.Usuarios U
    INNER JOIN dbo.TiposUsuario T
        ON U.IdTipoUsuario = T.IdTipoUsuario
    LEFT JOIN dbo.CategoriasEmpresa CE
        ON U.IdCategoriaEmpresa = CE.IdCategoriaEmpresa
    WHERE U.IdUsuario = :idUsuario
");

$stmt->execute([":idUsuario" => $idEmpresaSesion]);
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================
   MUNICIPIOS
========================= */
$municipios = $conexion->query("
    SELECT *
    FROM dbo.Municipios
    WHERE Estado = 1
    ORDER BY NombreMunicipio
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   LISTAR RUTAS
========================= */
$stmt = $conexion->prepare("
    SELECT 
        R.IdRuta,
        R.NombreRuta,
        R.HoraInicio,
        R.HoraFin,
        R.PrecioRuta,
        R.Estado,
        RD.DescripcionRuta,

        M1.NombreMunicipio AS MunicipioInicio,
        M1.Departamento AS DepartamentoInicio,

        M2.NombreMunicipio AS MunicipioFin,
        M2.Departamento AS DepartamentoFin,

        (
            SELECT COUNT(*)
            FROM dbo.RutaSalidas RS
            WHERE RS.IdRuta = R.IdRuta
        ) AS TotalSalidas

    FROM dbo.Rutas R

    LEFT JOIN dbo.RutaDetalle RD
        ON R.IdRuta = RD.IdRuta

    LEFT JOIN dbo.RutaParadas P1
        ON R.IdRuta = P1.IdRuta 
       AND P1.OrdenParada = 1

    LEFT JOIN dbo.Municipios M1
        ON P1.IdMunicipio = M1.IdMunicipio

    LEFT JOIN dbo.RutaParadas P2
        ON R.IdRuta = P2.IdRuta 
       AND P2.OrdenParada = (
            SELECT MAX(OrdenParada)
            FROM dbo.RutaParadas
            WHERE IdRuta = R.IdRuta
        )

    LEFT JOIN dbo.Municipios M2
        ON P2.IdMunicipio = M2.IdMunicipio

    WHERE R.IdEmpresa = :idEmpresa
    ORDER BY R.IdRuta DESC
");

$stmt->execute([":idEmpresa" => $idEmpresaSesion]);
$rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   PUNTOS DE CONTROL DE ESTA EMPRESA
========================= */
$stmt = $conexion->prepare("
    SELECT 
        PC.IdPuntoControl,
        PC.NombrePunto,
        PC.CodigoAcceso,
        R.NombreRuta
    FROM dbo.PuntosControl PC
    LEFT JOIN dbo.Rutas R
        ON PC.IdRuta = R.IdRuta
    WHERE PC.IdEmpresa = :idEmpresa
      AND PC.Estado = 1
    ORDER BY PC.NombrePunto
");

$stmt->execute([":idEmpresa" => $idEmpresaSesion]);
$puntosControlEmpresa = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   CONTADORES DE ESTA EMPRESA
========================= */
$stmt = $conexion->prepare("
    SELECT
        CE.IdContador,
        CE.IdUsuarioContador,
        CE.IdEmpresa,
        CE.IdPuntoControl,
        CE.NombreContador,
        CE.CedulaContador,
        CE.CodigoAcceso,
        CE.Estado,
        U.NombreUsuario,
        U.Correo,
        PC.NombrePunto,
        PC.CodigoAcceso AS CodigoPunto
    FROM dbo.ContadoresEmpresa CE
    INNER JOIN dbo.Usuarios U
        ON CE.IdUsuarioContador = U.IdUsuario
    LEFT JOIN dbo.PuntosControl PC
        ON CE.IdPuntoControl = PC.IdPuntoControl
    WHERE CE.IdEmpresa = :idEmpresa
    ORDER BY CE.IdContador DESC
");

$stmt->execute([":idEmpresa" => $idEmpresaSesion]);
$contadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   CARGAR CONTADOR EDITAR
========================= */
if ($idContadorEditar > 0) {
    $stmt = $conexion->prepare("
        SELECT
            CE.IdContador,
            CE.IdUsuarioContador,
            CE.IdPuntoControl,
            CE.NombreContador,
            CE.CedulaContador,
            CE.CodigoAcceso,
            CE.Estado,
            U.NombreUsuario,
            U.Correo
        FROM dbo.ContadoresEmpresa CE
        INNER JOIN dbo.Usuarios U
            ON CE.IdUsuarioContador = U.IdUsuario
        WHERE CE.IdContador = :idContador
          AND CE.IdEmpresa = :idEmpresa
    ");

    $stmt->execute([
        ":idContador" => $idContadorEditar,
        ":idEmpresa" => $idEmpresaSesion
    ]);

    $contadorEditar = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* =========================
   CARGAR RUTA EDITAR
========================= */
if ($idRutaEditar > 0) {
    $stmt = $conexion->prepare("
        SELECT 
            R.IdRuta,
            R.NombreRuta,
            R.HoraInicio,
            R.HoraFin,
            R.PrecioRuta,
            R.Estado,
            RD.DescripcionRuta
        FROM dbo.Rutas R
        LEFT JOIN dbo.RutaDetalle RD
            ON R.IdRuta = RD.IdRuta
        WHERE R.IdRuta = :idRuta
          AND R.IdEmpresa = :idEmpresa
    ");

    $stmt->execute([
        ":idRuta" => $idRutaEditar,
        ":idEmpresa" => $idEmpresaSesion
    ]);

    $rutaEditar = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($rutaEditar) {
        $stmt = $conexion->prepare("
            SELECT 
                RP.*,
                M.NombreMunicipio,
                M.Departamento
            FROM dbo.RutaParadas RP
            INNER JOIN dbo.Municipios M
                ON RP.IdMunicipio = M.IdMunicipio
            WHERE RP.IdRuta = :idRuta
            ORDER BY RP.OrdenParada
        ");

        $stmt->execute([":idRuta" => $idRutaEditar]);
        $paradasRuta = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conexion->prepare("
            SELECT *
            FROM dbo.RutaSalidas
            WHERE IdRuta = :idRuta
            ORDER BY OrdenSalida
        ");

        $stmt->execute([":idRuta" => $idRutaEditar]);
        $salidasRuta = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Empresa</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background:
                radial-gradient(circle at top left, #0b3a66 0%, transparent 35%),
                linear-gradient(135deg, #05070d, #07111f 55%, #02040a);
            color: #e8f1ff;
            min-height: 100vh;
        }

        .topbar {
            width: 100%;
            padding: 16px 5%;
            background: rgba(3, 10, 20, 0.95);
            border-bottom: 1px solid rgba(88, 166, 255, 0.28);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.35);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(12px);
        }

        .topbar-user {
            color: #d7e9ff;
            font-size: 15px;
        }

        .topbar-user strong {
            color: #58a6ff;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-actions form {
            margin: 0;
        }

        .container {
            width: 95%;
            max-width: 1400px;
            margin: auto;
            padding: 30px 0;
        }

        .header {
            background: rgba(7, 17, 31, 0.9);
            border: 1px solid rgba(42, 130, 255, 0.3);
            border-radius: 22px;
            padding: 28px;
            margin-bottom: 25px;
            box-shadow: 0 0 35px rgba(0, 94, 255, 0.18);
        }

        h1, h2, h3 {
            margin-top: 0;
            color: #ffffff;
        }

        h1 {
            font-size: 36px;
            color: #58a6ff;
        }

        h2 {
            border-left: 5px solid #1f8bff;
            padding-left: 12px;
            margin-top: 35px;
        }

        .card {
            background: rgba(8, 18, 34, 0.92);
            border: 1px solid rgba(80, 150, 255, 0.22);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 25px;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.35);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 14px;
        }

        .info-box {
            background: #0b1628;
            border: 1px solid #173d66;
            border-radius: 14px;
            padding: 14px;
        }

        .info-box strong {
            color: #58a6ff;
            display: block;
            margin-bottom: 5px;
        }

        label {
            color: #9ecbff;
            font-weight: bold;
            font-size: 14px;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 14px;
            margin-top: 6px;
            border-radius: 12px;
            border: 1px solid #234b77;
            background: #08111f;
            color: #ffffff;
            outline: none;
            transition: 0.2s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #1f8bff;
            box-shadow: 0 0 0 3px rgba(31, 139, 255, 0.18);
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        button, .btn {
            border: none;
            border-radius: 12px;
            padding: 11px 18px;
            background: linear-gradient(135deg, #006eff, #003d99);
            color: white;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: 0.2s;
        }

        button:hover, .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 110, 255, 0.35);
        }

        .btn-secondary {
            background: #1b2638;
            border: 1px solid #365b86;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff3b3b, #9b111e);
        }

        .btn-success {
            background: linear-gradient(135deg, #00b894, #006b5a);
        }

        .message {
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .message.success {
            background: rgba(0, 184, 148, 0.15);
            border: 1px solid #00b894;
            color: #4dffd8;
        }

        .message.error {
            background: rgba(255, 59, 59, 0.15);
            border: 1px solid #ff3b3b;
            color: #ff9b9b;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
        }

        .full {
            grid-column: 1 / -1;
        }

        fieldset {
            border: 1px solid rgba(88, 166, 255, 0.35);
            background: rgba(6, 15, 29, 0.8);
            border-radius: 18px;
            padding: 18px;
            margin-top: 18px;
        }

        legend {
            color: #58a6ff;
            font-weight: bold;
            padding: 0 8px;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
            border-radius: 18px;
            border: 1px solid rgba(88, 166, 255, 0.25);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
            background: #07111f;
        }

        th {
            background: #0b3a66;
            color: #ffffff;
            padding: 14px;
            text-align: left;
            white-space: nowrap;
        }

        td {
            padding: 13px;
            border-bottom: 1px solid rgba(88, 166, 255, 0.15);
            color: #d7e9ff;
        }

        tr:hover td {
            background: rgba(31, 139, 255, 0.08);
        }

        a {
            color: #58a6ff;
            font-weight: bold;
            text-decoration: none;
        }

        a:hover {
            color: #9ecbff;
            text-decoration: underline;
        }

        .acciones {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .badge {
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: bold;
            display: inline-block;
        }

        .activo {
            background: rgba(0, 184, 148, 0.18);
            color: #4dffd8;
            border: 1px solid #00b894;
        }

        .inactivo {
            background: rgba(255, 59, 59, 0.18);
            color: #ff9b9b;
            border: 1px solid #ff3b3b;
        }

        .sub-card {
            margin-top: 16px;
            padding: 18px;
            border-radius: 18px;
            background: rgba(6, 15, 29, 0.65);
            border: 1px solid rgba(88, 166, 255, 0.22);
        }

        small {
            color: #9ecbff;
        }

        @media (max-width: 700px) {
            .topbar {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }

            .topbar-actions {
                width: 100%;
                flex-wrap: wrap;
            }

            .topbar-actions .btn,
            .topbar-actions button {
                width: 100%;
                text-align: center;
            }

            h1 {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>

<div class="topbar">
    <div class="topbar-user">
        Bienvenido, <strong><?php echo limpiar($empresa["NombreUsuario"] ?? "Empresa"); ?></strong>
    </div>

    <div class="topbar-actions">
        <a href="index.php" class="btn btn-secondary">Volver al Inicio</a>

        <form action="CerrarSesion.php" method="post">
            <button type="submit" class="btn-danger">Cerrar sesión</button>
        </form>
    </div>
</div>

<div class="container">

    <div class="header">
        <h1>Panel Empresa</h1>
        <p>Gestiona las rutas, paradas, horarios de salida y contadores de tu empresa.</p>
    </div>

    <?php if ($mensaje != ""): ?>
        <div class="message success"><?php echo limpiar($mensaje); ?></div>
    <?php endif; ?>

    <?php if ($error != ""): ?>
        <div class="message error"><?php echo limpiar($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <h3>Información de la empresa</h3>

        <div class="info-grid">
            <div class="info-box">
                <strong>ID</strong>
                <?php echo limpiar($empresa["IdUsuario"] ?? "-"); ?>
            </div>

            <div class="info-box">
                <strong>Usuario</strong>
                <?php echo limpiar($empresa["NombreUsuario"] ?? "-"); ?>
            </div>

            <div class="info-box">
                <strong>Correo</strong>
                <?php echo limpiar($empresa["Correo"] ?? "-"); ?>
            </div>

            <div class="info-box">
                <strong>Categoría</strong>
                <?php echo limpiar($empresa["NombreCategoria"] ?? "No asignada"); ?>
            </div>

            <div class="info-box">
                <strong>Empresa</strong>
                <?php echo limpiar($empresa["NombreEmpresa"] ?? "No registrada"); ?>
            </div>

            <div class="info-box">
                <strong>NIT</strong>
                <?php echo limpiar($empresa["NitEmpresa"] ?? "-"); ?>
            </div>

            <div class="info-box">
                <strong>Teléfono</strong>
                <?php echo limpiar($empresa["TelefonoEmpresa"] ?? "-"); ?>
            </div>

            <div class="info-box">
                <strong>Estado</strong>
                <span class="badge <?php echo ($empresa["Estado"] ?? 0) == 1 ? "activo" : "inactivo"; ?>">
                    <?php echo ($empresa["Estado"] ?? 0) == 1 ? "Activo" : "Inactivo"; ?>
                </span>
            </div>
        </div>
    </div>

    <div class="card">
        <h2><?php echo $contadorEditar ? "Editar contador" : "Crear contador"; ?></h2>

        <form method="post" action="Empresa.php">
            <?php if ($contadorEditar): ?>
                <input type="hidden" name="idContador" value="<?php echo limpiar($contadorEditar["IdContador"]); ?>">
                <input type="hidden" name="idUsuarioContador" value="<?php echo limpiar($contadorEditar["IdUsuarioContador"]); ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div>
                    <label>Nombre completo:</label>
                    <input type="text" name="nombreContador" required
                           value="<?php echo $contadorEditar ? limpiar($contadorEditar["NombreContador"]) : ""; ?>">
                </div>

                <div>
                    <label>Usuario:</label>
                    <input type="text" name="usuarioContador" required
                           value="<?php echo $contadorEditar ? limpiar($contadorEditar["NombreUsuario"]) : ""; ?>">
                </div>

                <div>
                    <label>Correo:</label>
                    <input type="email" name="correoContador" required
                           value="<?php echo $contadorEditar ? limpiar($contadorEditar["Correo"]) : ""; ?>">
                </div>

                <div>
                    <label>Cédula:</label>
                    <input type="text" name="cedulaContador" required
                           value="<?php echo $contadorEditar ? limpiar($contadorEditar["CedulaContador"]) : ""; ?>">
                </div>

                <div>
                    <label>Contraseña:</label>
                    <input type="password" name="contrasenaContador" <?php echo $contadorEditar ? "" : "required"; ?>>
                    <?php if ($contadorEditar): ?>
                        <small>Déjala vacía si no deseas cambiarla.</small>
                    <?php endif; ?>
                </div>

                <div>
                    <label>Punto de control asignado:</label>
                    <select name="idPuntoControl">
                        <option value="">Sin punto asignado</option>

                        <?php foreach ($puntosControlEmpresa as $pc): ?>
                            <option value="<?php echo limpiar($pc["IdPuntoControl"]); ?>"
                                <?php echo ($contadorEditar && $contadorEditar["IdPuntoControl"] == $pc["IdPuntoControl"]) ? "selected" : ""; ?>>
                                <?php echo limpiar($pc["NombrePunto"]); ?>
                                <?php if (!empty($pc["NombreRuta"])): ?>
                                    - <?php echo limpiar($pc["NombreRuta"]); ?>
                                <?php endif; ?>
                                - Código <?php echo limpiar($pc["CodigoAcceso"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($contadorEditar): ?>
                    <div>
                        <label>Estado:</label>
                        <select name="estadoContador">
                            <option value="1" <?php echo $contadorEditar["Estado"] == 1 ? "selected" : ""; ?>>Activo</option>
                            <option value="0" <?php echo $contadorEditar["Estado"] == 0 ? "selected" : ""; ?>>Inactivo</option>
                        </select>
                    </div>

                    <div>
                        <label>Código acceso:</label>
                        <input type="text" readonly value="<?php echo limpiar($contadorEditar["CodigoAcceso"]); ?>">
                    </div>
                <?php endif; ?>
            </div>

            <br>

            <?php if ($contadorEditar): ?>
                <button type="submit" name="actualizarContador" class="btn-success">Actualizar contador</button>
                <button type="button" class="btn-secondary" onclick="window.location.href='Empresa.php'">Cancelar</button>
            <?php else: ?>
                <button type="submit" name="crearContador" class="btn-success">Crear contador</button>
            <?php endif; ?>
        </form>

        <br>

        <h3>Contadores registrados</h3>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Cédula</th>
                    <th>Punto asignado</th>
                    <th>Código contador</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>

                <?php foreach ($contadores as $c): ?>
                    <tr>
                        <td><?php echo limpiar($c["IdContador"]); ?></td>
                        <td><?php echo limpiar($c["NombreContador"]); ?></td>
                        <td><?php echo limpiar($c["NombreUsuario"]); ?></td>
                        <td><?php echo limpiar($c["Correo"]); ?></td>
                        <td><?php echo limpiar($c["CedulaContador"]); ?></td>
                        <td><?php echo limpiar($c["NombrePunto"] ?? "Sin punto"); ?></td>
                        <td><?php echo limpiar($c["CodigoAcceso"]); ?></td>
                        <td>
                            <span class="badge <?php echo $c["Estado"] == 1 ? "activo" : "inactivo"; ?>">
                                <?php echo $c["Estado"] == 1 ? "Activo" : "Inactivo"; ?>
                            </span>
                        </td>
                        <td>
                            <div class="acciones">
                                <a href="Empresa.php?contador=<?php echo limpiar($c["IdContador"]); ?>">Editar</a>

                                <a href="Empresa.php?eliminarContador=<?php echo limpiar($c["IdContador"]); ?>"
                                   onclick="return confirm('¿Desactivar este contador?')">
                                    Eliminar
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <div class="card">
        <h2><?php echo $rutaEditar ? "Editar ruta" : "Crear ruta"; ?></h2>

        <form method="post" action="Empresa.php">
            <?php if ($rutaEditar): ?>
                <input type="hidden" name="idRuta" value="<?php echo limpiar($rutaEditar["IdRuta"]); ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div>
                    <label>Nombre ruta:</label>
                    <input type="text" name="nombreRuta" required
                           value="<?php echo $rutaEditar ? limpiar($rutaEditar["NombreRuta"]) : ""; ?>">
                </div>

                <div>
                    <label>Precio ruta:</label>
                    <input type="number" name="precioRuta" min="0" step="100" required
                           value="<?php echo $rutaEditar ? limpiar($rutaEditar["PrecioRuta"]) : ""; ?>">
                </div>

                <div>
                    <label>Hora inicio de operación:</label>
                    <input type="time" name="horaInicio" required
                           value="<?php echo $rutaEditar ? limpiar(substr((string)$rutaEditar["HoraInicio"], 0, 5)) : ""; ?>">
                </div>

                <div>
                    <label>Hora fin de operación:</label>
                    <input type="time" name="horaFin" required
                           value="<?php echo $rutaEditar ? limpiar(substr((string)$rutaEditar["HoraFin"], 0, 5)) : ""; ?>">
                </div>

                <?php if ($rutaEditar): ?>
                    <div>
                        <label>Estado:</label>
                        <select name="estadoRuta">
                            <option value="1" <?php echo $rutaEditar["Estado"] == 1 ? "selected" : ""; ?>>Activo</option>
                            <option value="0" <?php echo $rutaEditar["Estado"] == 0 ? "selected" : ""; ?>>Inactivo</option>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="full">
                    <label>Descripción:</label>
                    <textarea name="descripcionRuta"><?php echo $rutaEditar ? limpiar($rutaEditar["DescripcionRuta"]) : ""; ?></textarea>
                </div>
            </div>

            <h3>Paradas</h3>

            <div id="contenedorParadas"></div>

            <br>

            <button type="button" class="btn-secondary" onclick="agregarParadaIntermedia()">
                Añadir parada intermedia
            </button>

            <br><br>

            <h3>Horarios de salida de busetas</h3>

            <div class="sub-card">
                <div class="form-grid">
                    <div>
                        <label>Modo de creación:</label>
                        <select id="modoSalidas" onchange="cambiarModoSalidas()">
                            <option value="manual">Manual</option>
                            <option value="automatico">Automático</option>
                        </select>
                    </div>
                </div>

                <div id="salidasAutomaticas" style="display:none; margin-top:18px;">
                    <div class="form-grid">
                        <div>
                            <label>Primera salida:</label>
                            <input type="time" id="autoHoraInicio">
                        </div>

                        <div>
                            <label>Última salida:</label>
                            <input type="time" id="autoHoraFin">
                        </div>

                        <div>
                            <label>Cada cuántos minutos:</label>
                            <input type="number" id="autoIntervalo" min="1" placeholder="Ej: 30">
                        </div>

                        <div>
                            <label>Lugar de salida:</label>
                            <input type="text" id="autoLugarSalida" placeholder="Ej: Terminal principal">
                        </div>
                    </div>

                    <br>

                    <button type="button" class="btn-secondary" onclick="generarSalidasAutomaticas()">
                        Generar salidas
                    </button>
                </div>
            </div>

            <div id="contenedorSalidas"></div>

            <br>

            <button type="button" class="btn-secondary" onclick="agregarSalidaManual()">
                Añadir salida manual
            </button>

            <br><br>

            <?php if ($rutaEditar): ?>
                <button type="submit" name="actualizarRuta" class="btn-success">Actualizar ruta</button>
                <button type="button" class="btn-secondary" onclick="window.location.href='Empresa.php'">Cancelar</button>
            <?php else: ?>
                <button type="submit" name="crearRuta" class="btn-success">Crear ruta</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <h2>Mis rutas registradas</h2>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Ruta</th>
                    <th>Precio</th>
                    <th>Origen</th>
                    <th>Destino</th>
                    <th>Operación</th>
                    <th>Salidas</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>

                <?php foreach ($rutas as $r): ?>
                    <tr>
                        <td><?php echo limpiar($r["IdRuta"]); ?></td>
                        <td><?php echo limpiar($r["NombreRuta"]); ?></td>
                        <td>$<?php echo number_format((float)$r["PrecioRuta"], 0, ",", "."); ?></td>

                        <td>
                            <?php
                                echo limpiar(
                                    ($r["MunicipioInicio"] ?? "-") .
                                    (!empty($r["DepartamentoInicio"]) ? " (" . $r["DepartamentoInicio"] . ")" : "")
                                );
                            ?>
                        </td>

                        <td>
                            <?php
                                echo limpiar(
                                    ($r["MunicipioFin"] ?? "-") .
                                    (!empty($r["DepartamentoFin"]) ? " (" . $r["DepartamentoFin"] . ")" : "")
                                );
                            ?>
                        </td>

                        <td>
                            <?php echo limpiar(substr((string)$r["HoraInicio"], 0, 5)); ?>
                            -
                            <?php echo limpiar(substr((string)$r["HoraFin"], 0, 5)); ?>
                        </td>

                        <td><?php echo limpiar($r["TotalSalidas"] ?? 0); ?></td>

                        <td><?php echo limpiar($r["DescripcionRuta"] ?? "-"); ?></td>

                        <td>
                            <span class="badge <?php echo $r["Estado"] == 1 ? "activo" : "inactivo"; ?>">
                                <?php echo $r["Estado"] == 1 ? "Activo" : "Inactivo"; ?>
                            </span>
                        </td>

                        <td>
                            <div class="acciones">
                                <a href="Empresa.php?ruta=<?php echo limpiar($r["IdRuta"]); ?>">Editar</a>

                                <a href="Empresa.php?eliminarRuta=<?php echo limpiar($r["IdRuta"]); ?>"
                                   onclick="return confirm('¿Desactivar esta ruta?')">
                                    Eliminar
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

</div>

<script>
let municipiosData = <?php echo json_encode($municipios, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

let paradasActuales = <?php
    if (!empty($paradasRuta)) {
        echo json_encode($paradasRuta, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    } else {
        echo json_encode([
            ["IdMunicipio" => "", "NombreParada" => "", "DireccionParada" => ""],
            ["IdMunicipio" => "", "NombreParada" => "", "DireccionParada" => ""]
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
?>;

let salidasActuales = <?php
    if (!empty($salidasRuta)) {
        echo json_encode($salidasRuta, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    } else {
        echo json_encode([
            ["HoraSalida" => "", "LugarSalida" => ""]
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
?>;

function escaparHTML(valor) {
    return String(valor ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function crearOptionsMunicipios(idSeleccionado = "") {
    let html = '<option value="">Seleccione municipio</option>';

    municipiosData.forEach(function(m) {
        let selected = String(m.IdMunicipio) === String(idSeleccionado) ? "selected" : "";

        html += `
            <option value="${escaparHTML(m.IdMunicipio)}" ${selected}>
                ${escaparHTML(m.NombreMunicipio)} (${escaparHTML(m.Departamento)})
            </option>
        `;
    });

    return html;
}

function guardarDatosAntesDeRender() {
    const municipios = document.querySelectorAll('select[name="municipio[]"]');
    const nombres = document.querySelectorAll('input[name="nombreParada[]"]');
    const direcciones = document.querySelectorAll('input[name="direccionParada[]"]');

    paradasActuales = [];

    for (let i = 0; i < municipios.length; i++) {
        paradasActuales.push({
            IdMunicipio: municipios[i].value,
            NombreParada: nombres[i].value,
            DireccionParada: direcciones[i].value
        });
    }
}

function renderParadas() {
    const contenedor = document.getElementById("contenedorParadas");
    contenedor.innerHTML = "";

    paradasActuales.forEach(function(parada, index) {
        let total = paradasActuales.length;
        let titulo = "";

        if (index === 0) {
            titulo = "Parada 1 (Inicio)";
        } else if (index === total - 1) {
            titulo = "Parada " + (index + 1) + " (Final)";
        } else {
            titulo = "Parada " + (index + 1);
        }

        let puedeEliminar = index !== 0 && index !== total - 1;

        let fieldset = document.createElement("fieldset");

        fieldset.innerHTML = `
            <legend>${escaparHTML(titulo)}</legend>

            <div class="form-grid">
                <div>
                    <label>Municipio:</label>
                    <select name="municipio[]" required>
                        ${crearOptionsMunicipios(parada.IdMunicipio || "")}
                    </select>
                </div>

                <div>
                    <label>Nombre parada:</label>
                    <input type="text" name="nombreParada[]" value="${escaparHTML(parada.NombreParada || "")}">
                </div>

                <div>
                    <label>Dirección parada:</label>
                    <input type="text" name="direccionParada[]" required value="${escaparHTML(parada.DireccionParada || "")}">
                </div>

                ${puedeEliminar ? `
                    <div style="align-self:end;">
                        <button type="button" class="btn-danger" onclick="eliminarParada(${index})">
                            Eliminar
                        </button>
                    </div>
                ` : ""}
            </div>
        `;

        contenedor.appendChild(fieldset);
    });
}

function agregarParadaIntermedia() {
    guardarDatosAntesDeRender();

    paradasActuales.splice(paradasActuales.length - 1, 0, {
        IdMunicipio: "",
        NombreParada: "",
        DireccionParada: ""
    });

    renderParadas();
}

function eliminarParada(index) {
    guardarDatosAntesDeRender();

    if (index !== 0 && index !== paradasActuales.length - 1) {
        paradasActuales.splice(index, 1);
        renderParadas();
    }
}

function cambiarModoSalidas() {
    const modo = document.getElementById("modoSalidas").value;
    const autoBox = document.getElementById("salidasAutomaticas");

    autoBox.style.display = modo === "automatico" ? "block" : "none";
}

function guardarSalidasAntesDeRender() {
    const horas = document.querySelectorAll('input[name="horaSalida[]"]');
    const lugares = document.querySelectorAll('input[name="lugarSalida[]"]');

    salidasActuales = [];

    for (let i = 0; i < horas.length; i++) {
        salidasActuales.push({
            HoraSalida: horas[i].value,
            LugarSalida: lugares[i].value
        });
    }
}

function renderSalidas() {
    const contenedor = document.getElementById("contenedorSalidas");
    contenedor.innerHTML = "";

    salidasActuales.forEach(function(salida, index) {
        let hora = salida.HoraSalida || "";

        if (hora.length >= 5) {
            hora = hora.substring(0, 5);
        }

        let fieldset = document.createElement("fieldset");

        fieldset.innerHTML = `
            <legend>Salida ${index + 1}</legend>

            <div class="form-grid">
                <div>
                    <label>Hora de salida:</label>
                    <input type="time" name="horaSalida[]" required value="${escaparHTML(hora)}">
                </div>

                <div>
                    <label>Lugar de salida:</label>
                    <input type="text" name="lugarSalida[]" required value="${escaparHTML(salida.LugarSalida || "")}">
                </div>

                <div style="align-self:end;">
                    <button type="button" class="btn-danger" onclick="eliminarSalida(${index})">
                        Eliminar
                    </button>
                </div>
            </div>
        `;

        contenedor.appendChild(fieldset);
    });
}

function agregarSalidaManual() {
    guardarSalidasAntesDeRender();

    salidasActuales.push({
        HoraSalida: "",
        LugarSalida: ""
    });

    renderSalidas();
}

function eliminarSalida(index) {
    guardarSalidasAntesDeRender();

    if (salidasActuales.length > 1) {
        salidasActuales.splice(index, 1);
        renderSalidas();
    }
}

function generarSalidasAutomaticas() {
    const horaInicio = document.getElementById("autoHoraInicio").value;
    const horaFin = document.getElementById("autoHoraFin").value;
    const intervalo = parseInt(document.getElementById("autoIntervalo").value);
    const lugar = document.getElementById("autoLugarSalida").value.trim();

    if (!horaInicio || !horaFin || !intervalo || !lugar) {
        alert("Completa todos los campos automáticos.");
        return;
    }

    let inicio = convertirMinutos(horaInicio);
    let fin = convertirMinutos(horaFin);

    if (inicio > fin) {
        alert("La primera salida no puede ser mayor que la última salida.");
        return;
    }

    salidasActuales = [];

    for (let minutos = inicio; minutos <= fin; minutos += intervalo) {
        salidasActuales.push({
            HoraSalida: convertirHora(minutos),
            LugarSalida: lugar
        });
    }

    renderSalidas();
}

function convertirMinutos(hora) {
    let partes = hora.split(":");
    return parseInt(partes[0]) * 60 + parseInt(partes[1]);
}

function convertirHora(minutos) {
    let h = Math.floor(minutos / 60);
    let m = minutos % 60;

    return String(h).padStart(2, "0") + ":" + String(m).padStart(2, "0");
}

renderParadas();
renderSalidas();
cambiarModoSalidas();
</script>

</body>
</html>